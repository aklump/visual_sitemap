<?php


namespace AKlump\LoftLib\Component\Forms;

use AKlump\LoftLib\Code\Arrays;

/**
 * Class BasicForm
 *
 * @package AKlump\LoftLib\Component\Forms
 */
abstract class BasicForm {

    protected $formState = array();
    protected $cache;

    /**
     * BasicForm constructor.
     */
    public function __construct()
    {
        $this->cache = new \stdClass;
    }

    /**
     * Return all form elements with default values, overridden by anything in
     * $data.
     *
     * @param array $values A multidimensional array.
     *
     * @return array A flattened, form array.
     */
    public static function getFormVars(array $values = array())
    {
        // Begin with the data provided to us...
        $vars = Arrays::formExport($values);

        // Pull in the defaults as defined in the schema for any value that is null.
        $defaults = static::getFormSchemaData('default');
        foreach (array_keys($defaults) as $key) {
            $vars[$key] = isset($vars[$key]) ? $vars[$key] : $defaults[$key];
        }

        if (count($vars)) {
            $callbacks = static::getFormSchemaData('preForm');
            static::applyCallbacks($callbacks, $vars);
        }

        // Make sure all form elements are present, finally, with null defaults.
        $vars += array_fill_keys(static::getFormElements(), null);

        return $vars;
    }

    /**
     * Return schema data by parameter.
     *
     * @param               $param
     * @param bool|callable $callback As a callable this will receive the value
     *                                of the parameter and should return true
     *                                to include this item.
     *
     * @return array Keyed by the form keys, values are the parameter values.
     */
    public static function getFormSchemaData($param, $callback = true)
    {
        $return = array();
        $schema = Arrays::formExport(static::getFormSchema());
        foreach ($schema as $key => $value) {
            if (preg_match('/(.+)\[' . preg_quote($param) . '\]$/', $key, $matches)
                && ((is_callable($callback) && $callback($value)) || $callback === true)
            ) {
                $return[$matches[1]] = $value;
            }
        }

        return $return;
    }

    /**
     * Returns an array of all form element names.
     *
     * @return array
     */
    public static function getFormElements()
    {
        $elements = Arrays::formExport(static::getFormSchema());
        $elements = array_keys($elements);
        array_walk($elements, function (&$value) {
            $value = preg_replace('/\[[^\[]+\]+$/', '', $value);
        });

        return array_unique($elements);
    }

    /**
     * Return the schema definition of the form.
     *
     * Each form item needs to be an array with the following options:
     *
     * - default mixed The default value.
     * - required bool True if empty invalidates form.
     * - regex string The regex to use to formValidate.
     * - preForm
     * - postValidate callable A transform function called after validation
     * with the arguments ($value, $is_valid, &$values).
     *
     * Here is the list of callback hooks, these should be key names which
     * define callables that happen on the form value at the time indicated by
     * the hook.
     * - preForm
     * - postValidate
     *
     * @return array
     * @codeCoverageIgnore
     */
    public static function getFormSchema()
    {
        return array();
    }

    /**
     * @param array $callbacks And array of callbacks, keyed by trees; values
     *                         are callables.
     * @param array $values    An formExport-ed array
     */
    protected static function applyCallbacks(array $callbacks, array &$values, array $context = [])
    {
        $defaults = static::getFormSchemaData('default');
        // Only apply callbacks on items in $values.
        $callbacks = Arrays::formFuzzyIntersectKey($callbacks, $values);
        foreach ($callbacks as $tree => $callback) {
            $default = isset($defaults[$tree]) ? $defaults[$tree] : null;
            $before = Arrays::formFuzzyGet($values, $tree, $default);

            // The callback may return a new value for $tree, AND it may also modify $values if it needs to add a key.
            $context['is_valid'] = empty($context['errors']) || !array_key_exists($tree, $context['errors']);
            is_callable($callback) && ($after = $callback($before, $values, $context));
            if ($after !== $before) {
                $values[$tree] = $after;
            }
            continue;
        }
    }

    public function getFormValue($key, $default = '')
    {
        $values = $this->getFormState()->values;

        return isset($values[$key]) ? $values[$key] : $default;
    }

    /**
     * @return array
     */
    public function getFormState()
    {
        return $this->formState;
    }

    public function setFormValue($key, $value)
    {
        $this->getFormState()->values[$key] = $value;

        return $this;
    }

    /**
     * @param mixed $input A multi-dimensional array, usually $_POST.
     *
     * @return $this
     */
    public function setInput(array $input)
    {
        $state = array();
        $actions = isset($input['__actions']) ? array_keys($input['__actions']) : array();
        $state['trigger'] = reset($actions);

        foreach ($input as $key => $value) {
            if (strpos($key, '__') === 0) {
                $state['meta'][$key] = $value;
                unset($input[$key]);
            }

        }
        $state['input'] = (array) $input;
        $state['values'] = new \stdClass;
        $state['errors'] = array();
        $this->formState = (object) $state;


        return $this;
    }

    /**
     * This returns the form state values expanded to multidimensional array.
     *
     * @return mixed
     */
    public function getFormValues()
    {
        return Arrays::formExpand($this->getFormState()->values);
    }

    /**
     * Validate the input values for a form.
     *
     * @return $this
     * @throws \AKlump\LoftLib\Component\Forms\InvalidFormException
     *
     * @see getFormErrors().
     * @see getFormErrorsJson().
     */
    public function formValidate()
    {
        $processing = Arrays::formExport($this->getFormState()->input);

        //
        //
        // Check for any empty, required fields.
        //
        $required = Arrays::formExport($this->getFormSchemaData('required', function ($value) {
            return $value !== false;
        }));

        //
        //
        // Check for input keys that are not registered in the schema
        //
        $schema = Arrays::formExport(static::getFormSchema());
        foreach (array_keys($processing) as $key) {
            if (!(Arrays::formFuzzyGet($schema, $key))) {
                $this->getFormState()->errors[$key] = array(
                    'exception' => new InvalidFormItemException("Disallowed form item: $key"),
                    'item' => $key,
                );
            }
        }

        //
        //
        // Are we missing any required fields, or are they empty?
        //
        if (($missing = Arrays::formFuzzyDiffKey($required, array_filter($processing)))) {
            foreach (array_keys($missing) as $key) {
                $this->getFormState()->errors[$key] = array(
                    'exception' => new MissingRequiredFormItemException("$key is a required field"),
                    'item' => $key,
                );
            }
        }

        //
        //
        // Validate based on regex
        //
        $regex = Arrays::formExport($this->getFormSchemaData('regex'));
        array_walk($processing, function ($value, $key) use ($regex) {
            if ($value && !empty($regex[$key]) && !preg_match($regex[$key], $value)) {
                $this->getFormState()->errors[$key] = array(
                    'exception' => new InvalidFormItemValueException("The submitted value of: $key is invalid"),
                    'item' => $key,
                );;
            }
        });

        //
        //
        // Post Validation hooks
        //
        $callbacks = static::getFormSchemaData('postValidate');
        static::applyCallbacks($callbacks, $processing, [
            'errors' => $this->getFormErrors(),
        ]);

        if ($this->getFormErrors()) {
            throw new InvalidFormException('Form did not validate.');
        }
        $this->getFormState()->values = $processing;

        return $this;
    }

    /**
     * @return array
     */
    public function getFormErrors()
    {
        return $this->getFormState()->errors;
    }

    public function formSubmit()
    {
        $this->fileUploadHandler($_FILES);
        if ($this->getFormErrors()) {
            throw new InvalidFormException('Form could not submit.');
        }

        return $this;
    }

    /**
     * @return array
     */
    public function getFormErrorsAsJson()
    {
        $json = array();
        foreach ($this->getFormErrors() as $error) {
            $json[] = $error['exception']->getMessage();
        }

        return json_encode($json);
    }

    /**
     * Return an array of form var keys that are required.
     *
     * @return array
     */
    public function getRequired()
    {
        $data = $this->getFormSchemaData('required', function ($value) {
            return !empty($value);
        });

        return array_keys($data);
    }

    protected function fileUploadHandler(array $files)
    {
        if (empty($files)) {
            return;
        }

        // First use the schema to only allow through the files defined therein.
        $elements = array_flip(static::getFormElements());
        $allow = array();
        foreach ($files as $name => $file) {
            foreach (array_keys($file['name']) as $index) {
                $data = array();
                $attributes = array_keys($file);
                foreach ($attributes as $attribute) {
                    $data[$attribute] = $file[$attribute][$index];
                }
                $allow[$name][$index] = json_encode($data);
            }
        }
        $allow = Arrays::formExport($allow);
        $process = array_intersect_key($allow, $elements);
        $handlers = static::getFormSchemaData('submit');
        foreach ($process as $name => $upload) {
            try {
                $upload = json_decode($upload);
                $upload->form = $name;
                if (!empty($upload->tmp_name) && isset($handlers[$name])) {
                    $handlers[$name]($this, $upload);
                }
            } catch (\Exception $exception) {
                $this->getFormState()->errors[$name] = array(
                    'exception' => $exception,
                    'item'      => $upload,
                );
            }
        }

    }
}
