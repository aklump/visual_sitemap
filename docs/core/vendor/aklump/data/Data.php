<?php

namespace AKlump\Data;


class Data implements DataInterface {

    /**
     * Extend this class and alter the default value of $pathSeparator if want
     * to use a different separator for your path strings.
     *
     * @var string
     */
    protected $pathSeparator = '.';

    /**
     * May be used to track internal data for recursive processing.
     *
     * @var array
     */
    protected $cache = array(
        'set' => array(),
        'get' => array(),
        'validate' => array(),
        'carry' => array(
            'value' => null,
            'path' => null,
            'set' => false,
            'abort' => false,
        ),
    );

    protected $defaults = array();

    /**
     * Data constructor.
     */
    public function __construct()
    {
        $this->defaults = $this->cache;
    }

    /**
     * @inheritdoc
     */
    public function getInt($subject, $path, $defaultValue = 0)
    {
        return $this->get($subject, $path, $defaultValue, function ($value, $default) {
            return is_null($value) ? $default : intval($value);
        });
    }

    /**
     * @inheritdoc
     */
    public function getThen($subject, $path, $defaultValue = null, $valueCallback = null)
    {
        $this->cache['carry']['abort'] = null;
        $value = $this->get($subject, $path, $defaultValue, $valueCallback);
        $this->setCarry($path, $value);

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function value()
    {
        $value = null;
        $path = null;
        $this->useCarry($path, $value);

        return $value;
    }

    /**
     * @inheritdoc
     */
    public function get($subject, $path, $defaultValue = null, $valueCallback = null)
    {
        $this->cacheSet(__FUNCTION__, $subject, $path, $defaultValue, $valueCallback);

        if (empty($subject)) {
            return $this->postGet($defaultValue, $defaultValue, $valueCallback, false);
        }

        $this->validate($subject, $path);
        $key = array_shift($path);
        $base = $subject;
        $pathExists = false;

        if (is_array($base)) {
            if (array_key_exists($key, $base)) {
                $base = $base[$key];
                $pathExists = true;
            }
            else {
                $base = $defaultValue;
                $pathExists = false;
            }
        }
        elseif (is_object($base)) {
            $base = clone $base;
            if (property_exists($base, $key)) {
                $base = $base->{$key};
                $pathExists = true;
            }
            else {
                $processed = false;
                foreach ($this->childGetters() as $method => $callback) {
                    if (method_exists($base, $method)) {
                        $base = $callback($base, $key, $defaultValue);
                        $processed = true;
                        break;
                    }
                }
                if ($processed) {
                    $pathExists = true;
                }
                else {
                    $pathExists = false;
                    $base = $defaultValue;
                }
            }
        }
        elseif (is_scalar($base)) {
            // If we don't have an object or an array we have to stop traversing.
            $path = array();
            $pathExists = false;
            $base = $defaultValue;
        }
        if (count($path) === 0) {
            return $this->postGet($base, $defaultValue, $valueCallback, $pathExists);
        }
        $function = __FUNCTION__;

        return $this->{$function}($base, implode($this->pathSeparator, $path), $defaultValue, $valueCallback);
    }

    /**
     * @inheritdoc
     */
    public function set(&$subject, $path = null, $value = null, $childTemplate = null)
    {
        if ($this->cache['carry']['abort']) {
            return $this->resetChain();
        }

        $this->writeArgHandler(func_num_args());
        $this->useCarry($path, $value);
        $this->cacheSet(__FUNCTION__, $subject, $path, $value, $childTemplate);
        $this->validate($subject, $path);

        // Establish the childTemplate on first pass.
        if (is_null($childTemplate)) {
            if (is_array($subject)) {
                $childTemplate = array();
            }
            elseif (is_object($subject)) {
                $childTemplate = get_class($subject);
                $childTemplate = new $childTemplate;
            }
        }

        $childTemplate = is_object($childTemplate) ? clone $childTemplate : $childTemplate;

        $key = array_shift($path);
        if (is_array($subject)) {
            $subject[$key] = isset($subject[$key]) ? $subject[$key] : $childTemplate;
            $next = &$subject[$key];
        }
        elseif (is_object($subject)) {
            $subject->{$key} = isset($subject->{$key}) ? $subject->{$key} : $childTemplate;
            $next = &$subject->{$key};
        }

        if (empty($path)) {
            $next = $value;

            return $this->resetChain();
        }

        return $this->set($next, $path, $value, $childTemplate);
    }

    /**
     * @inheritdoc
     */
    public function ensure(&$subject, $path = null, $default = null, $childTemplate = null)
    {
        if ($this->cache['carry']['abort']) {
            return $this->resetChain();
        }
        $this->writeArgHandler(func_num_args());
        $this->useCarry($path, $default);

        $value = $this->get($subject, $path, $default, function ($value, $default) {
            return is_null($value) ? $default : $value;
        });
        $this->set($subject, $path, $value, $childTemplate);

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function fill(&$subject, $path = null, $value = null, $test = null, $childTemplate = null)
    {
        if ($this->cache['carry']['abort']) {
            return $this->resetChain();
        }
        $this->writeArgHandler(func_num_args());
        $this->useCarry($path, $value);

        // Our default test is based on variable type.
        $test = is_null($test) ? 'empty' : $test;

        // Figure out what the default value is based on type of $value or on $childTemplate.
        if (is_null($childTemplate)) {
            $default = null;
            settype($default, gettype($value));
        }
        else {
            $default = $childTemplate;
        }

        list ($pathExists, $oldValue) = $this->getExists($subject, $path, $default);

        // Basic language constructs
        if (is_string($test)) {
            switch ($test) {
                case 'empty':
                    $test = function ($oldValue) {
                        return empty($oldValue);
                    };
                    break;
                case 'is_null':
                    $test = function ($oldValue) {
                        return is_null($oldValue);
                    };
                    break;
                // empty plus type comparison
                case 'strict':
                    $test = function ($oldValue) use ($default) {
                        return $oldValue === $default;
                    };
                    break;
                // Custom 'array_key_exists', 'property_exists'
                case 'not_exists':
                    $test = function ($oldValue, $pathExists, $value) {
                        return !$pathExists;
                    };
                    break;
            }
        }

        if (!is_callable($test)) {
            throw new \InvalidArgumentException("\$test must be a callable, null or predefined string.");
        }

        if ($test($oldValue, $pathExists, $value)) {
            $this->set($subject, $path, $value, $childTemplate);
        }

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function onlyIf($subject, $path, $test = null)
    {

        // Default test is !empty().
        if (is_null($test)) {
            $test = function ($value) {
                return !empty($value);
            };
        }

        list($pathExists, $value) = $this->getExists($subject, $path);
        if (!($this->cache['carry']['abort'] = !$test($value, $pathExists))) {
            $this->setCarry($path, $value);
        }

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function onlyIfNull($subject, $path)
    {
        return $this->onlyIf($subject, $path, function ($value) {
            return is_null($value);
        });
    }

    public function onlyIfHas($subject, $path)
    {
        list($exists, $value) = $this->getExists($subject, $path);
        $this->cache['carry']['abort'] = !$exists;
        if ($exists) {
            $this->setCarry($path, $value);
        }

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function call($callable)
    {
        if ($this->cache['carry']['abort']) {
            return $this;
        }

        $value = $path = null;
        $this->useCarry($path, $value);

        if (is_callable($callable)) {
            $args = func_get_args();
            array_shift($args);
            array_unshift($args, $value);
            $value = call_user_func_array($callable, $args);
        }
        else {
            throw new \InvalidArgumentException("Invalid function called $callable");
        }

        $this->setCarry($path, $value);

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function filter($filter, $options = null)
    {
        if ($this->cache['carry']['abort']) {
            return $this;
        }

        $value = null;
        $path = null;
        $this->useCarry($path, $value);
        $value = filter_var($value, $filter, $options);
        $this->setCarry($path, $value);

        return $this;
    }

    /**
     * Reset internal caching to be ready for a new chaining.
     *
     * @return $this
     */
    protected function resetChain()
    {
        $this->cache = $this->defaults;

        return $this;
    }

    /**
     * Returns a flag if the path exists and the value at $path.
     *
     * @param      $subject
     * @param      $path
     * @param null $defaultValue
     * @param null $valueCallback
     *
     * @return array - 0 bool If the path exists or not
     * - 0 bool If the path exists or not
     * - 1 mixed The value at path if it exists or $defaultValue
     */
    protected function getExists($subject, $path, $defaultValue = null, $valueCallback = null)
    {

        // TODO Can this be refactored now with the get() callback?

        $ancestry = $path;
        $key = $this->pathPop($ancestry);
        if (empty($ancestry)) {
            $parent = $subject;
        }
        else {
            $empty_subject = null;
            settype($empty_subject, gettype($subject));
            $parent = $this->get($subject, $ancestry, $empty_subject);
        }

        // Determine if the path exists or not.
        $pathExists = false;
        if (is_array($parent) && array_key_exists($key, $parent)) {
            $pathExists = true;
            $currentValue = $parent[$key];
        }
        elseif (is_object($parent) && property_exists($parent, $key)) {
            $pathExists = true;
            $currentValue = $parent->{$key};
        }
        else {
            $currentValue = $this->get($subject, $path, $defaultValue);
        }

        if (is_callable($valueCallback)) {
            $currentValue = $valueCallback($currentValue);
        }

        return array($pathExists, $currentValue);
    }

    protected function writeArgHandler($numArguments)
    {
        if ($numArguments < 2 && !$this->cache['carry']['set']) {
            throw new \InvalidArgumentException("Missing argument 2 for " . __CLASS__ . '::' . __FUNCTION__ . '(), called in ' . __FILE__ . ' on line ' . __LINE__);
        }
        elseif ($numArguments < 3 && !$this->cache['carry']['set']) {
            throw new \InvalidArgumentException("Missing argument 3 for " . __CLASS__ . '::' . __FUNCTION__ . '(), called in ' . __FILE__ . ' on line ' . __LINE__);
        }
    }

    /**
     * Overwrites the value of $path and $value, only if null, using carry
     * values if they exist.
     *
     * @param &$path
     * @param &$value
     */
    protected function useCarry(&$path, &$value)
    {
        if ($this->cache['carry']['set']) {
            $path = is_null($path) ? $this->cache['carry']['path'] : $path;
            $value = is_null($value) ? $this->cache['carry']['value'] : $value;
            $this->cache['carry'] = $this->defaults['carry'];
        }
    }

    protected function setCarry($path, $value)
    {
        $this->cache['carry']['path'] = $path;
        $this->cache['carry']['value'] = $value;
        $this->cache['carry']['set'] = true;
    }

    /**
     * Removes $count elements from $path, right to left
     *
     * @param string|array &$path
     *
     * @return mixed
     */
    protected function pathPop(&$path)
    {
        $type = gettype($path);
        $this->validate(__FUNCTION__, $path);
        $return = array_pop($path);
        $path = $type === 'string' ? implode($this->pathSeparator, $path) : $path;

        return $return;
    }

    /**
     * Removes $count elements from $path, right to left
     *
     * @param string|array &$path
     *
     * @return mixed
     */
    protected function pathExplode($path)
    {
        $this->validate(__FUNCTION__, $path);

        return $path;
    }

    protected function cacheSet($op)
    {
        $args = func_get_args();
        array_shift($args);
        $this->cache[$op]['args'] = isset($this->cache[$op]['args']) ? $this->cache[$op]['args'] : $args;
        $this->cache[$op]['level'] = isset($this->cache[$op]['level']) ? ++$this->cache[$op]['level'] : 0;
    }

    /**
     * Post process the get method.
     *
     * @param mixed    $value The discovered value, or default.
     * @param mixed    $defaultValue
     * @param Callable $valueCallback
     *
     * @return mixed
     */
    protected function postGet($value, $defaultValue, $valueCallback, $pathExists)
    {
        if (is_callable($valueCallback)) {
            $value = $valueCallback($value, $defaultValue, $pathExists);
        }
        $this->cacheClear('get', 'validate');

        return $value;
    }

    /**
     * Clear internal caches by key.
     *
     * @param... a list of keys to clear.
     *
     * @return $this
     */
    protected function cacheClear()
    {
        $keys = func_get_args();
        foreach ($keys as $key) {
            unset($this->cache[$key]);
        }

        return $this;
    }

    protected function validate($subject, &$path)
    {
        $this->cacheSet(__FUNCTION__, $subject, $path);
        if (empty($this->cache['validate']['validated'])) {
            // Convert integers/floats to a string, if possible
            $path = is_numeric($path) && strval($path) == $path ? strval($path) : $path;

            // Explode strings
            $path = is_string($path) ? explode($this->pathSeparator, $path) : $path;
            if (!is_array($path)) {
                throw new \InvalidArgumentException("\$path must be an array of $this->pathSeparator separated string.");
            }
        }

        return $this;
    }

    /**
     * Return an array of possible methods to call on objects for getting
     * property values.
     *
     * Extend this method as needed if you have child classes using
     * methods other than 'get' for extracting a property value.
     *
     * By returning an array of methods, it allows this class to support
     * non-homogenous objects under a common banner.  The first matched
     * method on the child will be used, subsequent methods will not be called.
     *
     * @return array
     *   - Keys are the methods to call on the child class
     *   - Values are callbacks which need to return a value, arguments
     *   received are $object, $property, $default.
     */
    protected function childGetters()
    {
        return array(
            'get' => function ($childObject, $property, $defaultValue) {
                return $childObject->get($property, $defaultValue);
            },
        );
    }

}
