<?php
/**
 * @file
 * Use this class to use BASH based configuration files.
 */

namespace AKlump\LoftLib\Component\Config;

/**
 * Represents a ConfigBash object class.
 *
 * @brief Handles configuration in a Bash file.
 *
 * $options for __construct()
 *   - encode @see json_encode.options
 */
class ConfigBash extends ConfigFileBasedStorage {

    const EXTENSION = "sh";

    public function _read()
    {
        if ($data = parent::_read()) {
            $data = explode(PHP_EOL, rtrim($data, PHP_EOL));

            $translated = array();
            array_walk($data, function ($value, $key) use (&$translated) {
                if (strpos($value, '#') === false && preg_match('/(.+)=["\(](.+)["\)]/', $value, $parts)) {

                    $value = $parts[2];

                    // Array expansion
                    if (preg_match_all('/\"(.*?)\"/', $value, $values)) {
                        $value = $values[1];
                    }
                    $translated[$parts[1]] = $value;
                }
            });
        }

        return $data ? $translated : array();
    }

    public function _write($data
    ) {
        $options = null;
        $body = array();
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                if (array_values($value) !== $value) {
                    throw new \InvalidArgumentException("Only numeric arrays are supported by this file format.");
                }
                else {
                    // Flatten numeric arrays
                    $value = json_encode(array_values($value));
                    $value = substr($value, 1);
                    $value = substr($value, 0, -1);
                    $value = '(' . str_replace('","', '" "', $value) . ')';
                }
            }
            elseif (!is_scalar($value)) {
                throw new \InvalidArgumentException("Values may only be scalars or numeric arrays in this file format; try changing formats to json or yaml.");
            }
            else {
                $value = '"' . $value . '"';
            }
            $body[] = "$key=$value";
        }

        $data = implode(PHP_EOL, $body);

        return parent::_write($data);
    }

    protected function getFileHeader()
    {
        return '#!/bin/bash' . PHP_EOL;
    }
}
