<?php
namespace AKlump\LoftLib\Component\Pdf;

/**
 * Interface PdfMetadataInterface
 *
 * Work with PDF metadata
 *
 * @package AKlump\LoftLib
 * @link    https://code.google.com/archive/p/pdf-meta/
 */
interface PdfMetadataInterface {

    public function setAuthor($author);

    public function setTitle($title);

    public function setSubject($subject);

    public function setCreator($creator);

    public function setProducer($producer);

    public function setCreated(\DateTime $date);

    public function setModified(\DateTime $date);

    public function setKeywords($keywords);

    public function getAuthor();

    public function getTitle();

    public function getCreator();

    public function getProducer();

    public function getSubject();

    public function getCreated();

    public function getModified();

    /**
     * @return mixed
     *
     * @link https://forums.adobe.com/thread/967380
     * The Keywords entry in the document properties for a PDF file is stored
     * as a string, so Acrobat does not try and break apart your entries or
     * reformat the delimiters. It's suggested that you use commas to separate
     * keywords in the list, and you can use double-quotes to identify a phrase
     * - though PDFs created by other software will often use semicolons. There
     * are no absolute rules in the ISO PDF specification.
     *
     * Interpreting the keywords string is up to the software performing the
     * search (e.g. a Web search engine spider or document management server).
     * Most will be equally-happy with commas or semicolons, and most will
     * understand the idea that "a quoted string" is to be matched as a block.
     */
    public function getKeywords();

    /**
     * Read in metadata from a pdf document.
     *
     * Use the various getters following a call to this method.
     *
     * @param string $path
     *
     * @return $this
     */
    public function read($path);

    /**
     * Write the metadata to a pdf file.
     *
     * Upon completion the metadata is still in tact; subsequent calls to
     * various files will write the same metadata.  Use reset() to clear
     * metadata.
     *
     * @param string $path The path to an existing pdf document, to which the
     *                     metadata previously set using the setters is written
     *                     to.
     *
     * @return $this
     *
     * @see ::reset().
     */
    public function write($path);

    /**
     * Clears all meta data stored in the object.
     *
     * @return $this
     */
    public function reset();
}
