<?php
namespace AKlump\LoftLib\Component\Pdf\Drupal7;

use AKlump\LoftLib\Component\Pdf\PdfMergerInterface;

class PdfTkMerger implements PdfMergerInterface
{
    protected $pdftk;
    protected $tempDir;
    protected $options;

    /**
     * PdfTkMerger constructor.
     *
     * @param       $tempDir
     * @param       $pathToPdfTk
     * @param array $options
     */
    public function __construct($tempDir, $pathToPdfTk, array $options= array())
    {
        $this->pdftk = $pathToPdfTk;
        if (!file_exists($this->pdftk)) {
            throw new \RuntimeException("pdftk not found at: " . $this->pdftk);
        }

        $this->tempDir = drupal_realpath($tempDir);
        if (!is_writable($this->tempDir)) {
            throw new \RuntimeException("The output directory (" . $this->tempDir . ") is not writable by php.");
        }
        $this->options = $options;
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
        exec($command, $output);

        if (!empty($options['debug'])) {
            watchdog('pdf_conversion', 'PdfTK user is: %user', array('%user' => exec('whoami')), WATCHDOG_DEBUG);
            watchdog('pdf_conversion', $command, array(), WATCHDOG_DEBUG);
            watchdog('pdf_conversion', 'PdfTK says: ' . implode('; ', $output), array(), WATCHDOG_DEBUG);
        }


        if (!is_file($mergedFilePath)) {
            throw new \RuntimeException("Unable to merge using PdfTK, file not found at $mergedFilePath.");
        }

        return $mergedFilePath;
    }
}
