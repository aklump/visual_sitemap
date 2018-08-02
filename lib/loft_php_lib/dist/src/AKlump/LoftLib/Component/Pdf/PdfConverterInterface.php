<?php
namespace AKlump\LoftLib\Component\Pdf;

interface PdfConverterInterface
{
    /**
     * Convert a file to a pdf document.
     *
     *
     * @param string $filePath
     *
     * @return string The filepath to the PDF document.
     *
     * @throws RuntimeException If the conversion cannot take place.
     * @throws InvalidArgumentException If the filepath is not correct.
     */
    public function convert($filePath);

    /**
     * Returns information about the last conversion.
     *
     * @return mixed
     */
    public function getResultCode();

    /**
     * Tests if the conversion system is functioning.
     *
     * @return true
     *
     * @throws \RuntimeException if the test fails, with possible failure reason.
     */
    public function testConvert();
}
