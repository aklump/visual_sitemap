<?php
/**
 * @file
 * Use this class to use JSON based configuration files.
 */
namespace AKlump\LoftLib\Component\Config;

/**
 * Represents a ConfigJson object class.
 *
 * @brief Handles configuration in a Json file.
 *
 * $options for __construct()
 *   - encode @see json_encode.options
 */
class ConfigJson extends ConfigFileBasedStorage
{

    const EXTENSION = "json";

    public function _read()
    {
        $data = parent::_read();
        if ($data && !($json = json_decode($data, true))) {
            throw new \RuntimeException("Invalid JSON in " . $this->getStorage()->value);
        }

        return $data ? $json : array();
    }

    public function _write($data)
    {
        $options = null;
        if (isset($this->options['encode'])) {
            $options = $this->options['encode'];
        }
        elseif (defined('JSON_PRETTY_PRINT')) {
            $options = JSON_PRETTY_PRINT;
        }
        $data = json_encode($data, $options);

        return parent::_write($data);
    }

    public function defaultOptions()
    {
        return array('encode'  => null,
                     'eof_eol' => false,
        ) + parent::defaultOptions();
    }
}
