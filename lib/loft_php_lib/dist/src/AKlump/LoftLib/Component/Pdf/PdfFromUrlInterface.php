<?php


namespace AKlump\LoftLib\Component\Pdf;


interface PdfFromUrlInterface {

    public function clearUrls();

    public function addUrl($url);

    public function stream();

    public function save($filename);

    public function setSession($name, $value);

    /**
     * @param string $size One of: A4,A3,Letter,Legal
     *
     * @return this
     */
    public function setPageSize($size);

    /**
     * Set the margin(s) as you would the css declaration; 1-4 arguments.
     *
     * Each argument should be an int + unit, e.g. 10mm, .5in
     */
    public function setMargin();

    /**
     * Set an array of options.
     *
     * These will vary depending upon the engine being used.
     *
     * @param array $options
     *
     * @return mixed
     */
    public function setOptions(array $options);
}
