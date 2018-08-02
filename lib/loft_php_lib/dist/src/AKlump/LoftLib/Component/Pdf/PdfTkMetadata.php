<?php
namespace AKlump\LoftLib\Component\Pdf;

use AKlump\LoftLib\Component\Config\ConfigPdfMetadata as Config;
use AKlump\LoftLib\Component\Storage\FilePath;

/**
 * Class PdfTkMetadata
 *
 * @package AKlump\LoftLib\Component\Pdf
 *
 * @link    https://sejh.wordpress.com/2014/11/26/changing-pdf-titles-with-pdftk/
 */
class PdfTkMetadata extends PdfMetadata {

    protected $data = array();
    protected $cache;
    protected $configFileHandler;

    /**
     * PdfTkMetadata constructor.
     *
     * @param string $tempDir
     * @param string $pathToPdfTk
     */
    public function __construct($tempDir, $pathToPdfTk)
    {
        $this->pdftk = $pathToPdfTk;
        if (!file_exists($this->pdftk)) {
            throw new \RuntimeException("pdftk not found at: " . $this->pdftk);
        }

        // Make sure temp is writable.
        $this->tempDir = rtrim($tempDir, '/');
        if (!file_exists($this->tempDir)) {
            throw new \InvalidArgumentException("The temporary directory does not exist: $this->tempDir");
        }
        if (!is_writable($this->tempDir)) {
            throw new \InvalidArgumentException("The temporary directory is not writable: $this->tempDir");
        }

        $this->cache = new \stdClass;
        $this->configFileHandler = new Config($this->tempDir, FilePath::tempName());
        $this->cache->scratch = $this->configFileHandler->getStorage()->value;
    }

    public function write($path)
    {
        $temp = new FilePath($this->tempDir, 'pdf');

        // Create the data file that will be used by pdftk to import the metadata
        $this->configFileHandler->writeMany($this->data);

        // Import the metadata using pdftk
        $command = array();
        $command[] = $this->pdftk . ' "' . $path . '" update_info "' . $this->configFileHandler->getStorage()->value . '" output "' . $temp->getPath() . '"';
        $command[] = '2>&1';
        $command = implode(' ', $command);

        exec($command, $output);

        // Overwrite the original pdf with the (metadata updated) version and delete the temp file.
        copy($temp->getPath(), $path);
        $temp->destroy();

        return $this;
    }

    public function read($path)
    {
        $this->validateFile($path);
        $output = '';
        $command = array();
        $command[] = $this->pdftk . ' "' . $path . '" dump_data output "' . $this->cache->scratch . '"';
        $command[] = '2>&1';
        $command = implode(' ', $command);

        exec($command, $output);
        $this->data = (array) $this->configFileHandler->readAll();

        return $this;
    }

    protected function validateFile($path)
    {
        if (!file_exists($path)) {
            throw new \InvalidArgumentException("\"$path\" does not exist.");
        }
    }

    public function __destruct()
    {
        // Delete the temp file used to migrate metadata.
        $this->configFileHandler->destroy();
    }
}
