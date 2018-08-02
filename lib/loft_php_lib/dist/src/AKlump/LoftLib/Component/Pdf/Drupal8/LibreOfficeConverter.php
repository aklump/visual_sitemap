<?php
namespace AKlump\LoftLib\Component\Pdf\Drupal8;

use AKlump\LoftLib\Component\Pdf\LibreOfficeConverter as LibreOffice;
use Psr\Log\LoggerInterface;

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
    protected $logger;

    /**
     * LibreOfficeConverter constructor.
     *
     * @param string          $tempDir
     * @param string          $pathToSOffice
     * @param LoggerInterface $logger Send this if you want extra debug info to
     *                                appear in your log.
     *
     * @code
     *   $obj = new LibreOfficeConverter(file_directory_temp(),
     *   '/usr/bin/soffice', \Drupal::service('logger.factory');
     * @endcode
     */
    public function __construct($tempDir, $pathToSOffice, LoggerInterface $logger = null)
    {
        $this->logger = $logger;
        $tempDir = drupal_realpath($tempDir);
        parent::__construct($tempDir, $pathToSOffice);
    }

    public function convert($filename)
    {
        if (!is_readable($filename)) {
            throw new \RuntimeException("Missing source file: $filename; unable to convert using LibreOffice . ");
        }

        $converted = rtrim($this->tempDir, '/') . '/' . pathinfo($filename, PATHINFO_FILENAME) . '.pdf';
        if ($pass_thru = parent::convert($filename)) {

            // If the file gets renamed, then we'll know that based on the return value.
            return file_unmanaged_copy($filename, $converted);
        }
        list($command, $output) = $this->process($filename);

        if ($this->logger) {
            $this->logger->debug('LibreOffice user is: %user', array('%user' => exec('whoami')));
            $this->logger->debug($command, array());
            $this->logger->debug('LibreOffice says: ' . implode('; ', $output), array());
        }

        if (!is_file($converted)) {
            $this->result = static::FAILED;
            throw new \RuntimeException("Conversion using LibreOffice failed, file not found at $converted . ");
        }

        $this->result = static::CONVERTED;

        return $converted;
    }
}
