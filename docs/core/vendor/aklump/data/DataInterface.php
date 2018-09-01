<?php

namespace AKlump\Data;

interface DataInterface {

    /**
     * Gets the value at path of subject. If the resolved value is undefined,
     * the defaultValue is returned in its place.
     *
     * If $subject is empty, regardless of path, $defaultValue is returned.
     *
     * @param mixed        $subject
     * @param string|array $path
     * @param mixed        $defaultValue  Defaults to null.
     * @param Callable     $valueCallback Defaults to null. Optional callback
     *                                    if the value does not match
     *                                    $defaultValue, receives the
     *                                    arguments: $value, $defaultValue,
     *                                    $pathExists.  $pathExists will be
     *                                    false if the $defaultValue was used;
     *                                    true if the value came from the
     *                                    $path.
     *
     * @return mixed
     *
     * @throws \InvalidArgumentException If the path is invalid.
     */
    public function get($subject, $path, $defaultValue = null, $valueCallback = null);

    /**
     * Return the intval() of a value.
     *
     * This is a shortcut for using a callback function on ::get().
     *
     * @param mixed        $subject
     * @param string|array $path
     * @param int          $defaultValue Optional.  Defaults to 0.  You may
     *                                   pass a non-integer value as default,
     *                                   it will be passed through if the value
     *                                   is determined to be null; IT WILL NOT
     *                                   BE CONVERTED TO AN INTEGER.
     *
     * @return mixed
     */
    public function getInt($subject, $path, $defaultValue = 0);

    /**
     * Get a value and return the object for chaining.
     *
     * Use value() to retrieve the value.
     *
     * @param mixed        $subject
     * @param string|array $path
     * @param mixed        $defaultValue  Defaults to null.
     * @param Callable     $valueCallback Defaults to null. Optional callback
     *                                    if the value does not match
     *                                    $defaultValue, receives the
     *                                    arguments: $value, $defaultValue.
     *
     * @return $this
     */
    public function getThen($subject, $path, $defaultValue = null, $valueCallback = null);

    /**
     * Set a value on $subject, including parents as needed.
     *
     * @param array|object $subject       The base subject.
     * @param string       $path
     * @param mixed        $value         The value to set.  This can only be
     *                                    omitted if a conditional method is
     *                                    chained before this.
     * @param null|mixed   $childTemplate If an child element must be created
     *                                    to
     *                                    establish $path, you may pass the
     *                                    default value of a child here.  You
     *                                    may leave this null if your $subject
     *                                    is either an array or a stdClass
     *                                    object as these are auto-detected.
     *                                    If you have a class that does not use
     *                                    constructor arguments, then you may
     *                                    also leave this set to null.
     *                                    Finally, if $subject is a class that
     *                                    needs constructor arguments, then you
     *                                    can pass a template object, which
     *                                    will be cloned.
     *
     * @return $this|\AKlump\Data\Data
     */
    public function set(&$subject, $path, $value = null, $childTemplate = null);

    /**
     * Ensure that a variable path exists, by creating a default value at $path
     * if the $path doesn't already exist.  BEWARE THAT IF THE PATH EXISTS BUT
     * THE VALUE IS NULL, THEN THE DEFAULT VALUE WILL BE USED.
     *
     * @param array|object $subject       The base subject.
     * @param string       $path
     * @param mixed        $default       The value to set, if the item doesn't
     *                                    already exist. This can only be
     *                                    omitted if a conditional method is
     *                                    chained before this.
     * @param null|mixed   $childTemplate If an child element must be created
     *                                    to establish $path, you may pass the
     *                                    default value of a child here.  You
     *                                    may leave this null if your $subject
     *                                    is either an array or a stdClass
     *                                    object as these are auto-detected.
     *                                    If you have a class that does not use
     *                                    constructor arguments, then you may
     *                                    also leave this set to null.
     *                                    Finally, if $subject is a class that
     *                                    needs constructor arguments, then you
     *                                    can pass a template object, which
     *                                    will be cloned.
     *
     * @return $this
     */
    public function ensure(&$subject, $path, $default = null, $childTemplate = null);

    /**
     *
     * Fill in a value if the current value has no value (or other test).
     *
     * This differs from Data::set() in that it will get(), test the value, and
     * set() in one step.
     *
     * This differs from Data::ensure() in that Data::ensure() will never
     * overwrite an existing value.  This method may.
     *
     * THINK OF THIS AS A CONDITIONAL SET() METHOD.
     *
     * If $value is string, it will be replaced if the current value is '' or
     * the key does not exist. If $value is numeric, it will be replaced if the
     * current value is 0. In other words if the current value is empty AND the
     * same variable type as value, it will be replaced with $value.
     *
     * For more control you can use the $test callable, which receives the
     * current $value and must return true if the replacement should occur.
     *
     * $test...
     *
     * The default test is to check if the current value is both empty and the
     * same type as $replace.  If both are true, then the value will be
     * replaced.
     *
     * built-in strings:
     *      - 'empty' Will only replace it if the current value is empty based
     *      on php's empty() construct.  This is the default.
     *      - 'strict' Will only replace if empty and same type as $value.
     *      - 'not_exists' Will only replace it if the final path element does
     *      not exist as an array key or an object property as figured by
     *      array_key_exists() or property_exists.
     *
     * callable:
     *      You may pass a callable, which receives ($oldValue, $pathExists,
     *      $newValue) must also return true to affect a replacement.
     *      $oldValue is based on $subject and $path.  $pathExists will be true
     *      if the final key or property of the $path exists.  $newValue is the
     *      value that will replace $oldValue, if $test passes.
     *
     * @param array|object $subject The base subject.
     * @param string       $path
     * @param mixed        $value   The value to set. This can only be omitted
     *                              if a conditional method is chained before
     *                              this.
     * @param mixed        $test    See notes above.
     * @param null         $childTemplate
     *
     * @return $this
     */
    public function fill(&$subject, $path, $value = null, $test = null, $childTemplate = null);

    /**
     * Apply a conditional test and carry the value if not empty
     *
     * @param mixed        $subject
     * @param string|array $path
     * @param callable     $test A function that takes the value as it's
     *                           argument and returns true if the value should
     *                           be used.
     *
     * @return $this
     * @code
     *   $o = new Data;
     *   $data = ['id' => 1];
     *   $output = [];
     *   $o->onlyIf($data, 'id')->set($other, 'id);
     * @endcode
     */
    public function onlyIf($subject, $path, $test = null);

    /**
     * Conditional tests for null value when chaining.
     *
     * Only carries the value if the value at $path is not null.
     *
     * @param $subject
     * @param $path
     *
     * @return $this
     */
    public function onlyIfNull($subject, $path);


    /**
     * Conditional tests for the existence of path when chaining.
     *
     * Only carries the value if the $path exists.
     *
     * @param $subject
     * @param $path
     *
     * @return $this
     */
    public function onlyIfHas($subject, $path);

    /**
     * Call a function with carry value.
     *
     * @param string $function_name A function name that takes $value as it's
     *                              first argument, e.g. intval, strval,
     *                              array_filter, etc.
     *
     * @return $this
     */
    public function call($function_name);

    /**
     * Leverage PHP's filter_var function on carry values.
     *
     * @param $filter      List of available filters can be found at
     *                     http://php.net/manual/en/filter.filters.php.
     * @param $options     Optional. Defaults to null.
     *
     * @return $this
     */
    public function filter($filter, $options = null);

    /**
     * Return the value at the end of a set of chained methods.
     *
     * @return mixed
     */
    public function value();
}
