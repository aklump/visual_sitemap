<?php
namespace AKlump\LoftLib\Component\Pdf\Drupal7;

use AKlump\LoftLib\Component\Pdf\LibreOfficeConverter as LibreOffice;

/**
 * Class LibreOfficeConverter
 *
 * Converts documents to pdf using LibreOffice 'soffice' binary.
 *
 * @link http://www.tecmint.com/install-libreoffice-on-rhel-centos-fedora-debian-ubuntu-linux-mint/
 * @link https://help.libreoffice.org/Common/Starting_the_Software_With_Parameters
 * @link https://ask.libreoffice.org/en/question/2641/convert-to-command-line-parameter/
 */
class LibreOfficeConverter extends LibreOffice {

    protected $soffice;
    protected $tempDir;
    protected $options;

    /**
     * LibreOfficeConverter constructor.
     *
     * @param       $tempDir
     * @param       $pathToSOffice
     * @param array $options
     */
    public function __construct($tempDir, $pathToSOffice, array $options = array())
    {
        $tempDir = drupal_realpath($tempDir);
        $this->options = $options;
        parent::__construct($tempDir, $pathToSOffice);
    }

    public function convert($filename)
    {
        if (!is_readable($filename)) {
            throw new \RuntimeException("Missing source file: $filename; unable to convert using LibreOffice.");
        }

        $converted = rtrim($this->tempDir, '/') . '/' . pathinfo($filename, PATHINFO_FILENAME) . '.pdf';
        if ($pass_thru = parent::convert($filename)) {
            file_unmanaged_copy($filename, $converted);

            return $converted;
        }
        list($command, $output) = $this->process($filename);

        if (!empty($options['debug'])) {
            watchdog('pdf_conversion', 'LibreOffice user is: %user', array('%user' => exec('whoami')), WATCHDOG_DEBUG);
            watchdog('pdf_conversion', $command, array(), WATCHDOG_DEBUG);
            watchdog('pdf_conversion', 'LibreOffice says: ' . implode('; ', $output), array(), WATCHDOG_DEBUG);
        }

        if (!is_file($converted)) {
            $this->result = static::FAILED;
            throw new \RuntimeException("Conversion using LibreOffice failed, file not found at $converted.");
        }

        $this->result = static::CONVERTED;

        return $converted;
    }
}
