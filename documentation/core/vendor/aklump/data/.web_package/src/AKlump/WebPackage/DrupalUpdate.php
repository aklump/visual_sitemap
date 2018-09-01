<?php
/**
 * @file
 * Defines a new DrupalUpdate class.
 *
 * @ingroup web_package
 * @{
 */
namespace AKlump\WebPackage;

/**
 * Represents a DrupalUpdate object class.
 */
class DrupalUpdate {
  
  const DIR = "update";
  protected $basedir, $xml;
  
  /**
   * Constructor method for a new DrupalUpdate.
   */
  public function __construct($basedir) {
    if (is_dir($basedir)) {
      $this->basedir = $basedir;
    }
    else {
      throw new \Exception("Invalid base directory; $basedir");
    }
  }  
  
  // Public, protected, private methods
  public function setup() {
    if (!is_dir($this->basedir . '/' . self::DIR)) {
      mkdir($this->basedir . '/' . self::DIR);
    }

    return $this;
  }

  /**
   * Getter/Setter for the xml object.
   *
   * @param  SimpleXMLElement||NULL $xml
   *
   * @return SimpleXMLElement
   */
  public function xml($xml = NULL) {
    if (isset($xml)) {
      if (is_string($xml)) {
        if (!($xml = simplexml_load_string($xml))) {
          throw new \Exception("Bad format xml string.", 1);
        }
      }
      elseif (!$xml instanceof SimpleXMLElement) {
        throw new \Exception("Objects must be of type SimpleXMLElement", 1);
      }
      $this->xml = $xml;
    }

    return isset($this->xml) ? $this->xml : new \SimpleXMLElement('<project/>');
  }

  public function save($filename) {
    if (substr($filename, -4) !== '.xml') {
      $filename .= '.xml';
    }
    $this->setup()->xml()->asXml($this->basedir . '/' . self::DIR . '/' . $filename);

    return $this;
  }
  
  
}

/**
 * Factory function to return a new instance of DrupalUpdate.
 *
 * @return DrupalUpdate
 */
function web_package_get_instance($arg) {
  return new DrupalUpdate($arg);
}
