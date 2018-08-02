<?php
/**
 * @file
 * Use this class to use JSON based configuration files.
 */
namespace AKlump\LoftLib\Component\Config;

/**
 * Represents a ConfigPdfMetadata object class.
 *
 * @brief Handles configuration in a PDF Metadata file as exported using pdftk
 * @link  https://sejh.wordpress.com/2014/11/26/changing-pdf-titles-with-pdftk/
 * @link  https://www.pdflabs.com/blog/export-and-import-pdf-bookmarks/
 */
class ConfigPdfMetadata extends ConfigFileBasedStorage {

    const EXTENSION = "txt";

    public function _read()
    {
        $contents = trim(parent::_read());
        $data = array();
        $lines = $contents ? explode(PHP_EOL, $contents) : array();
        $groupPointer = 0;
        $group = null;
        for ($i = 0; $i < count($lines); ++$i) {
            $line = explode(': ', $lines[$i]);

            // See if our line defines a new group
            if (substr($line[0], -5) === 'Begin') {
                if ($groupPointer && $group === 'Info') {
                    throw new \RuntimeException("Syntax error; new info group begun before old one was completed.");
                }
                $groupPointer++;
                $group = substr($line[0], 0, -5);
            }

            // See if we're inside an info group
            elseif ($group === 'Info') {
                $groupPointer++;
                if ($line[0] === 'InfoKey') {
                    if (!$key = $line[1]) {
                        throw new \RuntimeException("Syntax error; info group key cannot be empty.");
                    }
                }
                elseif ($line[0] === 'InfoValue') {
                    $value = $line[1];
                }
                if ($groupPointer >= 3) {
                    $data[$key] = $this->decode($key, $value);
                    $groupPointer = 0;
                    $group = null;
                    $key = $value = null;
                }
            }
            else {
                list($key, $value) = $line;
                $data[$key] = $this->decode($key, $value);
            }
        }

        return $data;
    }

    public function _write($data)
    {
        $output = '';
        $currentGroup = null;
        foreach ($data as $key => $value) {

            $value = $this->encode($key, $value);

            if ($this->keyIsInfo($key)) {
                $output[] = "InfoBegin";
                $output[] = "InfoKey: $key";
                $output[] = "InfoValue: $value";
                $currentGroup = null;
            }
            elseif ($group = $this->keyGetGroup($key)) {
                if (is_null($currentGroup)) {
                    $currentGroup = $group;
                    $output[] = "{$group}Begin";
                }
                $output[] = "$key: $value";
            }
            else {
                $output[] = "$key: $value";
                $currentGroup = null;
            }
        }
        $contents = implode(PHP_EOL, $output);

        return parent::_write($contents);
    }

    public function defaultOptions()
    {
        return array(
                'convert_dates' => true,
            ) + parent::defaultOptions();
    }

    protected function decode($key, $value)
    {
        if ($this->options['convert_dates'] && substr($value, 0, 2) === 'D:') {
            $value = $this->createDate($value);
        }

        return $value;
    }

    /**
     * Convert a string from the storage format to a \DateTime
     *
     * @param $string
     *
     * @return \DateTime
     */
    public static function createDate($string)
    {
        if (!preg_match('/D:(\d{4})(\d{2})(\d{2})(\d{2})(\d{2})(\d{2})(.+)/', $string, $m)) {
            throw new \InvalidArgumentException("Bad format: $string");
        }
        $m[7] = str_replace("Z00'00'", 'Z', $m[7]);
        $datetime = $m[1] . '-' . $m[2] . '-' . $m[3] . 'T' . $m[4] . ':' . $m[5] . ':' . $m[6] . str_replace("'", '', $m[7]);

        return new \DateTime($datetime);
    }

    protected function encode($key, $value)
    {
        if ($this->options['convert_dates'] && $value instanceof \DateTime) {
            $value = $this->formatDate($value);
        }

        return $value;
    }

    /**
     * Determine if $key should be written using InfoBegin declaration
     *
     * @param string $key e.g. ModDate, PageMediaBegin
     *
     * @return bool
     */
    protected function keyIsInfo($key)
    {
        return !preg_match('/^(Pdf|NumberOfPages|PageMedia)/', $key);
    }

    protected function keyGetGroup($key)
    {
        return preg_match('/^(PageMedia)/', $key, $m) ? $m[0] : null;
    }

    /**
     * Convert a \DateTime to a storage string
     *
     * @param \DateTime $date
     *
     * @return string
     */
    public static function formatDate(\DateTime $date)
    {
        $return = $date->format('\D\:YmdHisO');
        $return = substr($return, 0, -2) . "'" . substr($return, -2) . "'";

        return str_replace("+00'00'", "Z00'00'", $return);
    }
}
