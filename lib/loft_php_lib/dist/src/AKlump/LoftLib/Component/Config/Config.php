<?php
/**
 * @file
 * Defines the Config class.
 *
 * @ingroup name
 * @{
 */
namespace AKlump\LoftLib\Component\Config;

/**
 * Represents a Config object class.
 *
 * @brief Base class for configuration classes.
 */
abstract class Config implements ConfigInterface
{

    /**
     * Use this to cache expensive calculations in the object.
     *
     * Child classes must namespace like this:
     * $this->cache->{ChildClass}->{key}. And should declare the cache object
     * in the constructor like this:
     *
     * @code
     *   parent::__construct(...);
     *   $this->cache->{ChildClass} = new \StdClass;
     * @endcode
     *
     * @var object
     */
    protected $cache;
    /**
     * Stores the config storage object
     *
     * @var object
     */
    protected $storage;

    public function __construct()
    {
        if (!isset($this->getStorage()->type) || !isset($this->getStorage()->value)) {
            throw new \InvalidArgumentException("Missing storage object.");
        }
        $this->cache = new \stdClass;
        $this->cache->read = null;
    }

    public function getStorage()
    {
        if (!isset($this->storage)) {
            $this->storage = (object) array(
                'type'  => null,
                'value' => null,
            );
        }
        if (!is_object($this->storage)) {
            throw new \RuntimeException("Invalid storage object");
        }

        return $this->storage;
    }

    public function init()
    {
        $this->checkMethod(__FUNCTION__);

        return $this;
    }

    protected function checkMethod($methodBase)
    {
        $method = trim($methodBase, '_') . '_' . $this->getStorage()->type;
        if (!method_exists($this, $method)) {
            throw new \RuntimeException("Unknown storage type: " . $this->getStorage()->type);
        }
        $args = func_get_args();
        array_shift($args);

        return $this->{$method}($args);
    }

    public function read($key, $default = null)
    {
        if (!isset($this->cache->read)) {
            $this->cache->read = $this->_read();
        }

        return isset($this->cache->read[$key]) ? $this->cache->read[$key] : $default;
    }

    /**
     * Return the uncached data array.
     *
     * @return array
     *
     * @see read()
     */
    abstract protected function _read();

    public function readAll()
    {
        return (object) $this->_read();
    }

    public function write($key, $value)
    {
        $this->writeMany(array($key => $value));

        return $this;
    }

    public function writeMany($data)
    {
        unset($this->cache->read);
        $data = array_merge($this->_read(), $data);
        if (!($this->_write($data))) {
            throw new \RuntimeException("Unable to write data.");
        }

        return $this;
    }

    /**
     * Store the data persistently.
     *
     * @param  array $data
     *
     * @return bool FALSE if the data was not stored.
     */
    abstract protected function _write($data);

    public function defaultOptions()
    {
        return array();
    }

    public function destroy()
    {
        $this->checkMethod(__FUNCTION__);

        return $this;
    }

    protected function destroy_file()
    {
        $path = $this->getStorage()->value;
        if (is_file($path) && !unlink($path)) {
            throw new \RuntimeException("Could not destroy the configuration file at: $path");
        }
    }

}
