<?php
namespace AKlump\LoftLib\Component\Pdf;

use AKlump\LoftLib\Component\Config\ConfigCookieJar;
use Knp\Snappy\Pdf as Snappy;
use Psr\Log\LoggerInterface;

/**
 * Class PdfFromUrlWkHtmlToPdf
 *
 * @package AKlump\LoftLib\Component\Pdf
 *
 * @brief   Obtains a pdf file from one or more urls using wkhtmltopdf.
 *          Supports session-based authentication.  For urls that require
 *          session authentication, you can use the setSession() method.  To
 *          see how this can work with a login method refer to
 *          AKlump\LoftLib\Component\Pdf\Drupal8\PdfFromUrl.
 *
 * @link    https://github.com/KnpLabs/snappy
 * @link    http://wkhtmltopdf.org/usage/wkhtmltopdf.txt
 */
class PdfFromUrl implements PdfFromUrlInterface {

    protected $wkhtmltopdf;
    protected $tempDir;
    protected $urls;
    protected $session;
    protected $options = array();
    protected $specificOptions = array();

    /**
     * PdfFromUrlWkHtmlToPdf constructor.
     *
     * @param string $tempDir The base folder where pdfs will be written.
     * @param string $pathToWkHtmlToPdf
     */
    public function __construct($tempDir, $pathToWkHtmlToPdf, LoggerInterface $logger = null)
    {
        $this->logger = $logger;
        $this->wkhtmltopdf = $pathToWkHtmlToPdf;
        if (!file_exists($this->wkhtmltopdf)) {
            throw new \RuntimeException("wkhtmltopdf not found at: " . $this->wkhtmltopdf);
        }

        $this->tempDir = realpath(rtrim($tempDir, '/'));
        if (!file_exists($this->tempDir)) {
            throw new \InvalidArgumentException("The temporary directory does not exist: $this->tempDir");
        }
        if (!is_writable($this->tempDir)) {
            throw new \InvalidArgumentException("The temporary directory is not writable: $this->tempDir");
        }
    }

    public function addUrl($url)
    {
        $this->urls[] = $url;

        return $this;
    }

    /**
     * Stream the pdf from the url(s).
     *
     * @param string $filename Optional. Defaults to file.pdf.
     *
     * @return string The full path to the output pdf.
     *
     * @throws \RuntimeException if the file could not be saved.
     */
    public function stream($filename = 'file.pdf')
    {
        $data = $this->getPdfContents();
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        print $data;
    }

    /**
     * Save a pdf from the url(s).
     *
     * @param string $filename Optional.  The filename of the output.  The file
     *                         will be written in $baseDir.  .pdf will be added
     *                         if not already present at the end of the
     *                         $filename.
     *
     * @return string
     */
    public function save($filename = null)
    {
        if (empty($filename)) {
            $filename = uniqid('PdfFromUrl', true);
        }
        $outputFile = $this->tempDir . '/' . $filename;
        if (substr($outputFile, -4) !== '.pdf') {
            $outputFile .= '.pdf';
        }
        $data = $this->getPdfContents();
        file_put_contents($outputFile, $data);
        if (!file_exists($outputFile)) {
            throw new \RuntimeException("Could not save file at $outputFile");
        }

        return $outputFile;
    }

    public function setSession($name, $value)
    {
        $this->session = array($name => $value);
    }

    public function clearUrls()
    {
        $this->urls = array();

        return $this;
    }

    public function setPageSize($size)
    {
        // http://qt-project.org/doc/qt-4.8/qprinter.html#PaperSize-enum
        $options = array('A4', 'A3', 'Letter', 'Legal');
        if (!preg_match('/' . implode('|', $options) . '/i', $size)) {
            throw new \InvalidArgumentException("Page size must be one of " . implode(', ', $options));
        }
        $this->specificOptions['page-size'] = ucwords($size);

        return $this;
    }

    public function setMargin()
    {
        $m = func_get_args();
        switch (count($m)) {
            case 1:
                $m = array_fill(0, 4, $m[0]);
                break;
            case 2;
                $m = array($m[0], $m[1], $m[0], $m[1]);
                break;
            case 3;
                $m = array($m[0], $m[1], $m[2], $m[1]);
                break;
        }
        if (count($m) !== 4) {
            throw new \InvalidArgumentException("Enter margins as you would for css: margin; 1-4 arguments.");
        }
        list($this->specificOptions['margin-top'], $this->specificOptions['margin-right'], $this->specificOptions['margin-bottom'], $this->specificOptions['margin-left']) = $m;

        return $this;
    }

    /**
     * @param array $options
     *
     * It is preferred to use the interface option methods, e.g. setMargin()
     * over setting the margin via this method.  Always use the interface
     * method first, use this as a last resort.
     *
     * @link http://wkhtmltopdf.org/usage/wkhtmltopdf.txt
     * @return $this
     */
    public function setOptions(array $options)
    {
        $this->options = $options;

        return $this;
    }

    /**
     * Obtain the pdf file contents
     *
     * @return string
     */
    protected function getPdfContents()
    {
        if (!$this->urls) {
            throw new \RuntimeException("Missing url(s); call addUrl at least once.");
        }

        // options should not be able to crush specific options.
        $options = $this->specificOptions + $this->options;

        $snappy = new Snappy($this->wkhtmltopdf, $options);
        if (!$this->session) {
            $this->login();
        }
        if ($this->session) {
            // For some reason using the cookie jar directly doesn't work with wkhtmltopdf. 2017-02-13T15:19, aklump
            $snappy->setOption('cookie', $this->session);
        }
        $data = $snappy->getOutput($this->urls);;

        return $data;
    }

    protected function login()
    {
        // Placeholder for child classes to perform some logic and populate $this->session.
        return $this;
    }

    /**
     * Any classes implementing login() may use this to extract a session id
     * from the cookie jar file
     *
     * @param $file
     */
    protected function setCookieJar($file)
    {
        $cookies = new ConfigCookieJar($file);
        list($name, $value) = $cookies->getSession();
        $this->setSession($name, $value);
        unlink($file);
    }
}
