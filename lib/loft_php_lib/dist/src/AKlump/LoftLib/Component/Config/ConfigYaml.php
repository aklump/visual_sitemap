<?php
/**
 * @file
 * Use this class to use YAML based configuration files.
 */
namespace AKlump\LoftLib\Component\Config;

use Symfony\Component\Yaml\Yaml;

/**
 * Represents a ConfigYaml object class.
 *
 * @brief Handles configuration in a Yaml file.
 *
 * $options for __construct()
 *   - inline: The level where you switch to inline YAML
 *   - indent: The amount of spaces to use for indentation of nested nodes.
 */
class ConfigYaml extends ConfigFileBasedStorage
{

    const EXTENSION = "yaml";

    public function _read()
    {
        $data = parent::_read();

        return $data ? Yaml::parse($data) : array();
    }

    public function _write($data)
    {
        $data = Yaml::dump($data, $this->options['inline'], $this->options['indent']);

        return parent::_write($data);
    }

    public function defaultOptions()
    {
        return array(
            'inline' => 3,
            'indent' => 2,
        ) + parent::defaultOptions();
    }
}
