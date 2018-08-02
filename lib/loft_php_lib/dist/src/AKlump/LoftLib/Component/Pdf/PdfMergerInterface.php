<?php
namespace AKlump\LoftLib\Component\Pdf;

interface PdfMergerInterface
{
    /**
     * Merge multiple pdfs into a single pdf.
     *
     * @param array $filePaths An array of file paths to be merged.
     *
     * @return string  The output path of the merged file.
     *
     * @throws RuntimeException If merge file cannot be created.
     */
    public function merge(array $filePaths);
}
