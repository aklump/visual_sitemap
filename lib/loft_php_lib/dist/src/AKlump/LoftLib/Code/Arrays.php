<?php


namespace AKlump\LoftLib\Code;


class Arrays {

    /**
     * @param      $formArray A multi-dimensional array.
     * @param      $name      The key by which to fuzzy match.
     * @param null $default   A Default value.
     *
     * @return mixed
     */
    public static function formFuzzyGet($formArray, $name, $default = null)
    {
        $a = Arrays::formExpand($formArray);
        $b = Arrays::formExpand(array($name => true));

        return static::_formFuzzyGet($a, $b, $default);
    }

    /**
     * Convert a formExport array back to it's multidimensional representation.
     *
     * @param array $array
     * @param int   $expansionKey
     *
     * @return array
     *
     * The expansion key instructs how to handle the following merge.  The
     * $expansionKey takes the place of the missing final key on the first
     * value.
     * @code
     *   _val[comp]          => dir/file.png,
     *   _val[comp][0][type] => mobile,
     * @endcode
     *
     * So the effect is the following:
     * @code
     *   _val[comp][0][$expansionKey] => dir/file.png,
     *   _val[comp][0][type]          => mobile,
     * @endcode
     */
    public static function formExpand(array $array, $expansionKey = 0)
    {
        // By putting these in reverse order, it builds the array correctly, as you might be expecting.
        ksort($array);
        $out = array();
        foreach ($array as $name => $value) {
            $parents = array($name);
            if (preg_match('/(.+?)\[(.+)\]/', $name, $matches)) {
                $parents = explode('][', $matches[2]);
                array_unshift($parents, $matches[1]);
            }
            static::_formExpandItem($out, $parents, $value, $expansionKey);
        }

        return $out;
    }

    /**
     * Return a new array with all keys from $a, whose keys begin with any of
     * the keys in $b.
     *
     * @param array $a
     * @param array $b
     *
     * @return array
     */
    public static function formFuzzyIntersectKey(array $a, array $b)
    {
        $intersection = array();
        foreach (array_keys($b) as $fuzzy) {
            foreach (array_keys($a) as $key) {
                if (strpos($key, $fuzzy) === 0) {
                    $intersection[$key] = $a[$key];
                }
            }
        }

        return $intersection;
    }

    /**
     * Compares they keys of $a against $b and returns the values in $a that
     * are not present in $b based on a fuzzy key match (The keys of $a do not
     * begin with the keys of $b).
     *
     * @param array $a
     * @param array $b
     *
     * @return array
     */
    public static function formFuzzyDiffKey(array $a, array $b)
    {
        foreach (array_keys($b) as $fuzzy) {
            foreach (array_keys($a) as $key) {
                if (strpos($key, $fuzzy) === 0) {
                    unset($a[$key]);
                }
            }
        }

        return $a;
    }

    /**
     * Reduce a multidimensional array to single to use as http form values.
     *
     * @param array        $array
     * @param string|array $prefix A parent tree string or array.
     *
     * @return array
     */
    public static function formExport(array $array, $parents = array())
    {
        $null = array();
        $parents = empty($parents) ? array() : $parents;
        $parents = is_string($parents) ? static::expandParents($parents) : $parents;

        return static::_formExport($array, $null, $parents);
    }

    /**
     * The opposite of flattenParents.
     *
     * @param $string , e.g. 'do[re][mi]'
     *
     * @return array
     *
     * @see flattenParents().
     */
    public static function expandParents($string)
    {
        $a = trim($string);
        $a = $a ? explode('[', $string) : array();
        array_walk($a, function (&$value) {
            $value = trim($value, '[]');
        });

        return $a;
    }

    /**
     * Given a single dimensional array, return the values as a string
     * representing the parent structure of a form item.
     *
     * @param array $array E.g., ['do', 're', 'mi']
     *
     * @return mixed|string E.g. 'do[re][mi]'
     *
     * @see expandParents().
     */
    public static function flattenParents(array $array)
    {
        $tree = array_shift($array);
        if ($array) {
            $tree .= '[' . implode('][', $array) . ']';
        }

        return strval($tree);
    }

    //    /**
    //     * Merge an array recursively, either overwriting or merging as defined by
    //     * the schema.
    //     *
    //     * @param array $schema An empty array or with keys:
    //     *                      - multiple array All keys that should be allowed to
    //     *                      be multiple. If a key is not here, then the first
    //     *                      value will persist and any additionals will be
    //     *                      discarded. KEYS SHOULD FOLLOW THE PATTERN OF
    //     *                      flattenParents().
    //     *
    //     * @return mixed
    //     *
    //     * @see flattenParents().
    //     */
    //    public static function mergeSmart($schema)
    //    {
    //        $args = func_get_args();
    //        $schema = array_shift($args);
    //
    //        return static::drupal_array_merge_deep_array($args);
    //
    //
    //        $arrays = func_get_args();
    //        $count = count($arrays);
    //        if ($count === 0) {
    //            return [];
    //        }
    //        elseif ($count > 1) {
    //            $arrays[0] = call_user_func_array('array_merge_recursive', $arrays);
    //        }
    //        $result = static::_mergeSmart($schema, $arrays[0]);
    //
    //        return $result;
    //    }
    //
    //    public static function drupal_array_merge_deep_array($arrays)
    //    {
    //        $result = array();
    //
    //        foreach ($arrays as $array) {
    //            foreach ($array as $key => $value) {
    //                // Renumber integer keys as array_merge_recursive() does. Note that PHP
    //                // automatically converts array keys that are integer strings (e.g., '1')
    //                // to integers.
    //                if (is_integer($key)) {
    //                    $result[] = $value;
    //                }
    //                // Recurse when both values are arrays.
    //                elseif (isset($result[$key]) && is_array($result[$key]) && is_array($value)) {
    //                    $result[$key] = static::drupal_array_merge_deep_array(array(
    //                        $result[$key],
    //                        $value,
    //                    ));
    //                }
    //                // Otherwise, use the latter value, overriding any previous value.
    //                else {
    //                    $result[$key] = $value;
    //                }
    //            }
    //        }
    //
    //        return $result;
    //    }

    public static function replaceKey($array, $oldKey, $newKey)
    {
        $keys = array_keys($array);
        $index = array_search($oldKey, $keys);

        if ($index !== false) {
            $keys[$index] = $newKey;
            $array = array_combine($keys, $array);
        }

        return $array;
    }

    /**
     * Replace all $oldValue with $newValue in an array.
     *
     * @param array $array
     * @param $oldValue
     * @param $newValue
     *
     * @return array
     */
    public static function replaceValue(array $array, $oldValue, $newValue)
    {
        $keys = array_keys($array, $oldValue, true);
        foreach ($keys as $key) {
            $array[$key] = $newValue;
        }

        return $array;
    }

    /**
     * Insert an array or string in an array before a given value returning the resulting first key.
     *
     * @param array $array
     * @param       $search
     * @param mixed $insert
     *
     * @return int|string The index of the start of the $insert.
     */
    public static function insertBeforeValue(array &$array, $search, $insert)
    {
        if (($key = array_search($search, $array)) !== false) {
            if (!is_numeric($key)) {
                throw new \InvalidArgumentException("Only indexed arrays are supported.");
            }
            $position = $key;
            array_splice($array, $position, 0, $insert);

            return $position;
        }
        $array[] = $insert;

        return count($insert) - 1;
    }

    /**
     * Insert an array or string in an array after a given value returning the resulting first key.
     *
     * @param array $array
     * @param       $search
     * @param mixed $insert
     *
     * @return int|string The index of the start of the $insert.
     */
    public static function insertAfterValue(array &$array, $search, $insert)
    {
        if (($key = array_search($search, $array)) !== false) {
            if (!is_numeric($key)) {
                throw new \InvalidArgumentException("Only indexed arrays are supported.");
            }
            $position = $key + 1;
            array_splice($array, $position, 0, $insert);

            return $position;
        }
        $array[] = $insert;

        return count($insert) - 1;
    }

    /**
     * Insert an associative array right before a key.
     *
     * @param array  $array
     * @param string $search The key to insert before.
     * @param array  $insert The array to insert.
     */
    public static function insertBeforeKey(array &$array, $search, array $insert)
    {
        if (is_numeric($search)) {
            throw new \InvalidArgumentException("\$search may not be numeric.");
        }
        $a = [];
        $b = $array;
        if (($offset = array_search($search, array_keys($array)))) {
            $a = array_slice($array, 0, $offset);
            $b = array_slice($array, $offset);
        }
        $array = array_merge($a, $insert, $b);
    }

    /**
     * Insert an associative array right after a key.
     *
     * @param array  $array
     * @param string $search The key to insert after.
     * @param array  $insert The array to insert.
     */
    public static function insertAfterKey(array &$array, $search, array $insert)
    {
        if (is_numeric($search)) {
            throw new \InvalidArgumentException("\$search may not be numeric.");
        }
        $offset = array_search($search, array_keys($array));
        $a = array_slice($array, 0, $offset + 1);
        $b = array_slice($array, $offset + 1);
        $array = array_merge($a, $insert, $b);
    }

    /**
     * Shuffle an array maintaining keys.
     *
     * @param array $array
     *
     * @return array The new array with shuffled order and preserved keys.
     */
    public static function shuffleWithKeys(array $array)
    {
        $keys = array_keys($array);
        shuffle($keys);

        return array_map(function ($key) use ($array) {
            return $array[$key];
        }, array_combine($keys, $keys));
    }
    //
    //    protected static function __mergeSmart($schema, array $array, &$parents = [], &$merge = [])
    //    {
    //        foreach ($array as $key => $value) {
    //            if (is_array($value)) {
    //                $parents[] = $key;
    //                $merge[$key] = static::_mergeSmart($schema, $value, $parents);
    //            }
    //            else {
    //                $parent = static::flattenParents($parents);
    //                $multi = isset($schema['multiple']) && in_array($parent, $schema['multiple']);
    //                if (!$multi) {
    //                    $array = reset($array);
    //                }
    //                array_pop($parents);
    //
    //                return $array;
    //            }
    //        }
    //
    //        return $merge;
    //    }

    protected static function _formFuzzyGet($a, $b, $default)
    {
        if (!is_array($b)) {
            return $a;
        }
        else {
            foreach (array_keys($b) as $key) {
                if (!array_key_exists($key, $a)) {
                    return $default;
                }

                if ($return = static::_formFuzzyGet($a[$key], $b[$key], $default)) {
                    break;
                }
            }
        }

        return $return;
    }

    protected static function _formExpandItem(array &$build, array $parents, $value, $expansionKey)
    {
        $parent = array_shift($parents);
        if (count($parents)) {
            if (isset($build[$parent])) {
                $build[$parent] = is_array($build[$parent]) ? $build[$parent] : array(0 => array($expansionKey => $build[$parent]));
            }
            else {
                $build[$parent] = array();
            }
            static::_formExpandItem($build[$parent], $parents, $value, $expansionKey);
        }
        else {
            $build[$parent] = $value;
        }
    }

    /**
     * Recursive helper method.
     *
     * @see formExport().
     */
    protected static function _formExport($value, array &$export = array(), array &$parents = array())
    {
        if (is_array($value) && !empty($value)) {
            foreach ($value as $key => $item) {
                $parents[] = $key;
                static::_formExport($item, $export, $parents);
            }
            array_pop($parents);
        }
        elseif ($parents) {
            $export[static::flattenParents($parents)] = $value;
            array_pop($parents);
        }

        return $export;
    }
}
