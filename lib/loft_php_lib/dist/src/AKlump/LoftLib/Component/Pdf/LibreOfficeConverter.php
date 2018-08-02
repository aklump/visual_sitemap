<?php

namespace AKlump\LoftLib\Component\Pdf;

use AKlump\LoftLib\Component\Bash\Bash;
use AKlump\LoftLib\Component\Storage\FilePath;

/**
 * Class LibreOfficeConverter
 *
 * Converts documents to pdf using LibreOffice 'soffice' binary.
 *
 * @link http://www.tecmint.com/install-libreoffice-on-rhel-centos-fedora-debian-ubuntu-linux-mint/
 * @link https://help.libreoffice.org/Common/Starting_the_Software_With_Parameters
 * @link https://ask.libreoffice.org/en/question/2641/convert-to-command-line-parameter/
 */
abstract class LibreOfficeConverter extends PdfConverter {

    protected $soffice;

    protected $tempDir;

    protected $options;

    /**
     * LibreOfficeConverter constructor.
     *
     * @param       $tempDir
     * @param       $pathToSOffice
     */
    public function __construct($tempDir, $pathToSOffice)
    {
        $this->soffice = $pathToSOffice;
        if (!file_exists($this->soffice)) {
            throw new \RuntimeException("soffice not found at: " . $this->soffice);
        }

        // Make sure temp is writable.
        $this->tempDir = rtrim($tempDir, '/');
        if (!file_exists($this->tempDir)) {
            throw new \InvalidArgumentException("The temporary directory does not exist: $this->tempDir");
        }
        if (!is_writable($this->tempDir)) {
            throw new \InvalidArgumentException("The temporary directory is not writable: $this->tempDir");
        }
    }

    public function testConvert()
    {
        $temp = new FilePath($this->tempDir, 'txt');
        $temp->put('Hello World!')->save();

        $this->process($temp->getPath());

        // Test the conversion process, that a file comes out.
        $converted = preg_replace('/\.txt$/', '.pdf', $temp->getPath());
        $converted = new FilePath($converted);

        // Cleanup the temp files.
        $temp->exists() && $temp->destroy();
        ($result = $converted->exists()) && $converted->destroy();

        return $result;
    }

    protected function process($filename)
    {
        // TODO Check for file and rename if already there?

        // http://superuser.com/questions/627266/convert-file-to-pdf-using-libreoffice-under-user-apache-i-e-when-using-php
        $command = array();
        $command[] = 'export HOME="' . $this->tempDir . '" && ' . $this->soffice . ' --headless --convert-to pdf --outdir "' . $this->tempDir . '" "' . $filename . '"';
        $command[] = '2>&1';

        // Send off to LibreOffice (soffice) to convert.
        $output = array();
        Bash::exec($command, $output);

        return array($command, $output);
    }
}
