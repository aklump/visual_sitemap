<?php
namespace AKlump\LoftLib\Component\Pdf;

abstract class PdfConverter implements PdfConverterInterface
{
    const FAILED = -1;
    const NO_SOURCE = -2;
    const IS_PDF = 0;
    const CONVERTED = 2;

    protected $result;

    public function getResultCode()
    {
        return $this->result;
    }

    public function convert($filename)
    {
        if (!file_exists($filename)) {
            $this->result = static::NO_SOURCE;
            throw new \InvalidArgumentException("$filename does not exist.");
        }
        if (!is_writable($this->tempDir)) {
            $this->result = static::FAILED;
            throw new \RuntimeException("The output directory is not writable by php: " . $this->tempDir);
        }

        // If already a pdf then pass through.
        if (pathinfo($filename, PATHINFO_EXTENSION) === 'pdf') {
            $this->result = static::IS_PDF;

            return $filename;
        }

        // ... this needs to do something in the child class, obviously
        // @see LibreOfficeConverter for example.
    }
}
