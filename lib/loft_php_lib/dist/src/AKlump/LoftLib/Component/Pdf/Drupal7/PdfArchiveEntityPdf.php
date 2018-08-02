<?php
namespace AKlump\LoftLib\Component\Pdf\Drupal7;

/**
 * Class Drupal7PdfArchiveEntityPdf
 *
 * Converts entities to pdfs using the pdf_archive module
 *
 * @link https://www.drupal.org/project/pdf_archive
 */
class PdfArchiveEntityPdf implements EntityPdfInterface
{
    protected $viewMode;
    protected $rid;
    protected $header;
    protected $footer;
    protected $output_dir;

    /**
     * EntityPdf constructor.
     *
     * @param $output_dir
     * @param $viewMode
     * @param $rid
     * @param $header
     * @param $footer
     */
    public function __construct($output_dir, $rid = DRUPAL_ANONYMOUS_RID, $viewMode = 'pdf_archive_file', $header = '', $footer = '')
    {
        $this->tempDir = drupal_realpath($output_dir);
        if (!is_writable($this->tempDir)) {
            $this->result = static::FAILED;
            throw new \RuntimeException("The output directory is not writable by php: " . $this->tempDir);
        }

        $this->viewMode = $viewMode;
        $this->rid = $rid;
        $this->header = $header;
        $this->footer = $footer;
    }

    public function create($entityTypeId, \stdClass $entity)
    {
        list($id) = entity_extract_ids($entityTypeId, $entity);
        $pdf = pdf_archive_create(
            $entityTypeId,
            array($id => $entity),
            $this->header,
            $this->footer,
            $this->viewMode,
            $this->rid
        );

        if (!($filename = tempnam($this->tempDir, 'entity--'))) {
            throw new \RuntimeException("Cannot get tempname");
        }
        unlink($filename);
        $filename .= '.pdf';
        $data = pdf_archive_output($pdf, 'S', $filename);
        if (file_put_contents($filename, $data) === false) {
            throw new \RuntimeException("Could not create PDF.");
        }

        return $filename;
    }
}
