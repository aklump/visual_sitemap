<?php
namespace AKlump\LoftLib\Component\Config;

/**
 * Represents the interface for a configuration object.
 */
interface ConfigInterface
{

    /**
     * Return the default options
     *
     * @return array
     */
    public function defaultOptions();

    /**
     * Initialize a configuration for the storage object so that read/writes
     * may be performed.
     *
     * In the case of file-based, this creates the dir and path.
     *
     * @return this
     */
    public function init();

    /**
     * Destroy an entire configuration
     *
     * @return this
     */
    public function destroy();

    /**
     * Read a config value by key.
     *
     * @param  string $key
     * @param  mixed  $default A default value to use when no set.
     *
     * @return mixed
     */
    public function read($key, $default = null);

    /**
     * Read all config values into an object.
     *
     * @return object
     */
    public function readAll();

    /**
     * Write a config value by key
     *
     * @param  string $key
     * @param  mixed  $value
     *
     * @return this
     */
    public function write($key, $value);

    /**
     * Write several values at once.
     *
     * In some classes this is faster due to disk access.
     *
     * @param  array $data
     *
     * @return this
     */
    public function writeMany($data);

    /**
     * Returns the storage object
     *
     * @return object
     *   - type string e.g., 'file'
     *   - value string
     *     - file: The filepath
     */
    public function getStorage();

}
