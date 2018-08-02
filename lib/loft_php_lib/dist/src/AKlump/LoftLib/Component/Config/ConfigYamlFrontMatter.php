<?php
/**
 * @file
 * Use this class to use YAML based configuration files.
 */

namespace AKlump\LoftLib\Component\Config;

use AKlump\LoftDataGrids\ExportData;
use AKlump\LoftDataGrids\YAMLFrontMatterExporter;
use AKlump\LoftDataGrids\YAMLFrontMatterImporter;

/**
 * Represents a ConfigYamlFrontMatter object class.
 *
 * @brief Handles configuration in a Text file (.md, .html) with YAML front
 *        matter.
 *
 * $options for __construct()
 *        - custom_extension: To use an extension other than 'html' set this.
 *        - body_key: By default the body of the text file (non-front matter)
 *        will be assigned to the key 'body'.  To change this behavior, enter
 *        the value of the key here.
 */
class ConfigYamlFrontMatter extends ConfigFileBasedStorage {

    const EXTENSION = "html";

    public function _read()
    {
        if (($source = parent::_read())) {
            $importer = new YAMLFrontMatterImporter();
            $obj = $importer->addSetting('bodyKey', $this->options['body_key'])
                            ->import($source);
            $data = $obj->getPage();
            $data = reset($data);
        }

        return $source ? $data : array();
    }

    public function _write($data)
    {
        $obj = new ExportData;
        foreach ($data as $key => $datum) {
            $obj->add($key, $datum);
        }
        $exporter = new YAMLFrontMatterExporter($obj);
        $data = $exporter->addSetting('bodyKey', $this->options['body_key'])
                         ->export();

        return parent::_write($data);
    }

    public function defaultOptions()
    {
        return array(
                'body_key' => 'body',
            ) + parent::defaultOptions();
    }
}
