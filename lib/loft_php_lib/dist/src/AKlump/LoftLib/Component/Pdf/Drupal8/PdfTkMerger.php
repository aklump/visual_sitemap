<?php
namespace AKlump\LoftLib\Component\Pdf\Drupal8;

use AKlump\LoftLib\Component\Pdf\PdfMergerInterface;
use AKlump\LoftLib\Component\Pdf\PdfLoginRequiredException;
use Psr\Log\LoggerInterface;

/**
 * Class PdfTkMerger
 *
 * @package AKlump\LoftLib\Component\Pdf\Drupal8
 *
 * @link    https://www.pdflabs.com/tools/pdftk-server/
 */
class PdfTkMerger implements PdfMergerInterface {

    protected $pdftk;
    protected $tempDir;
    protected $options;
    protected $logger;

    /**
     * PdfTkMerger constructor.
     *
     * @param                          $tempDir
     * @param                          $pathToPdfTk
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function __construct($tempDir, $pathToPdfTk, LoggerInterface $logger = null)
    {
        $this->pdftk = $pathToPdfTk;
        if (!file_exists($this->pdftk)) {
            throw new \RuntimeException("pdftk not found at: " . $this->pdftk);
        }

        $this->tempDir = drupal_realpath($tempDir);
        if (!is_writable($this->tempDir)) {
            throw new \RuntimeException("The output directory (" . $this->tempDir . ") is not writable by php.");
        }
        $this->logger = $logger;
    }

    public function merge(array $filePaths)
    {
        if (empty($filePaths)) {
            throw new \RuntimeException('$filePaths cannot be empty.');
        }

        // Create a temporary filename
        if (!($mergedFilePath = tempnam($this->tempDir, 'merge--'))) {
            throw new \RuntimeException("Cannot get tempname.");
        }
        unlink($mergedFilePath);
        $mergedFilePath .= '.pdf';

        // Create the command to send to pdftk
        $command = array($this->pdftk);
        foreach ($filePaths as $filePath) {
            if (!file_exists($filePath)) {
                throw new \RuntimeException("Invalid filepath: $filePath");
            }
            $command[] = "'$filePath'";
        }
        $command[] = 'cat output';
        $command[] = "'$mergedFilePath'";
        $command[] = '2>&1';
        $command = implode(' ', $command);

        $output = array();
        $result = null;
        exec($command, $output, $result);

        if ($result) {
            $this->handleErrors($output);
        }

        if ($this->logger) {
            $this->logger->debug('PdfTK user is: %user', array('%user' => exec('whoami')));
            $this->logger->debug($command, array());
            $this->logger->debug('PdfTK says: ' . implode('; ', $output), array());
        }

        if (!is_file($mergedFilePath)) {
            throw new \RuntimeException("Unable to merge using PdfTK, file not found at $mergedFilePath.");
        }

        return $mergedFilePath;
    }

    protected function handleErrors(array $output)
    {
        while ($line = array_shift($output)) {
            if (strpos($line, 'Error:') === 0) {
                $this->handleError($output);
            }
        }
    }

    protected function handleError(array $error)
    {
        $path = trim(array_shift($error));
        $error = trim(array_shift($error));
        $message = $error . ': ' . $path;
        if (strstr($error, 'OWNER PASSWORD REQUIRED')) {
            throw new PdfLoginRequiredException($message);
        }
        else {
            throw new \RuntimeException($message);
        }
    }
}
