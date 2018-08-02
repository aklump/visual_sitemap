<?php


namespace AKlump\LoftLib\Code;

/**
 * Class Cache
 *
 * A Utility to help with caching data.
 *
 * @package AKlump\LoftLib\Code
 */
class Cache {

    /**
     * Return a cache id based on a configuration array.
     *
     * This will be an md5 hash of the contents of $config, which is sorted, so the order of keys does not matter.
     *
     * @param array $config
     *
     * @return string.
     */
    public static function id(array $config)
    {
        return md5(json_encode(static::sort($config)));
    }

    protected static function sort(array $array)
    {
        ksort($array);
        foreach (array_keys($array) as $key) {
            if (is_array($array[$key])) {
                $array[$key] = static::sort($array[$key]);
            }
        }

        return $array;
    }
}
