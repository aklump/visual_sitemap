<?php


namespace AKlump\LoftLib\Code;

/**
 * Trait ObjectCacheTrait
 *
 * Add instance caching to any class
 *
 * @package AKlump\LoftLib\Code
 */
trait ObjectCacheTrait {

    protected $cache = [];

    /**
     * Return the cached value by key.
     *
     * @param string $key
     * @param mixed  $default Optional, a default value other than null.
     *
     * @return array
     */
    protected function getCached($key, $default = null)
    {
        return isset($this->cache[$key]) ? $this->cache[$key] : $default;
    }

    /**
     * Set the cached value by $key.
     *
     * The cached value will exist for the duration of the apply() method.  Each new call to apply() first clears the
     * internal instance cache.
     *
     * @param array $key
     * @param array $value
     *
     * @return __CLASS__
     */
    protected function setCached($key, $value)
    {
        $this->cache[$key] = $value;

        return $this;
    }

    /**
     * Clear the cached value by $key or entirely
     *
     * @param array $key Optional, omit to clear all cached items.
     *
     * @return __CLASS__
     */
    protected function clearCached($key = null)
    {
        if (func_num_args() === 0) {
            $this->cache = [];
        }
        elseif (isset($this->cache[$key])) {
            unset($this->cache[$key]);
        }

        return $this;
    }
}
