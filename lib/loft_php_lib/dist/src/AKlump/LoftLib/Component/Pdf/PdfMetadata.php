<?php
namespace AKlump\LoftLib\Component\Pdf;

abstract class PdfMetadata implements PdfMetadataInterface {

    protected $data = array();
    protected $tempDir;

    public function setCreated(\DateTime $date)
    {
        $this->data['CreationDate'] = $date;

        return $this;
    }

    public function setModified(\DateTime $date)
    {
        $this->data['ModDate'] = $date;

        return $this;
    }

    public function setAuthor($author)
    {
        $this->data['Author'] = $author;

        return $this;
    }

    public function setSubject($subject)
    {
        $this->data['Subject'] = $subject;

        return $this;
    }

    public function setCreator($creator)
    {
        $this->data['Creator'] = $creator;

        return $this;
    }

    public function setProducer($producer)
    {
        $this->data['Producer'] = $producer;

        return $this;
    }

    public function setKeywords($keywords)
    {
        $this->data['Keywords'] = $keywords;

        return $this;
    }

    public function setTitle($title)
    {
        $this->data['Title'] = $title;

        return $this;
    }

    public function getCreated()
    {
        return $this->_get('CreationDate');
    }

    public function getModified()
    {
        return $this->_get('ModDate');
    }

    public function getAuthor()
    {
        return $this->_get('Author');
    }

    public function getCreator()
    {
        return $this->_get('Creator');
    }

    public function getProducer()
    {
        return $this->_get('Producer');
    }

    public function getSubject()
    {
        return $this->_get('Subject');
    }

    public function getKeywords()
    {
        return $this->_get('Keywords');
    }

    public function getTitle()
    {
        return $this->_get('Title');
    }

    public function reset()
    {
        $this->data = array();

        return $this;
    }

    protected function _get($key, $default = null)
    {
        return isset($this->data[$key]) ? $this->data[$key] : $default;
    }
}
