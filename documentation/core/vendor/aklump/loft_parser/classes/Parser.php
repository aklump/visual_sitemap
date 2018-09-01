<?php
/**
 * @file
 * Defines the abstract parse class
 *
 * @ingroup loft_parser
 * @{
 */
namespace aklump\loft_parser;

/**
 * Interface Parser
 */
interface ParserInterface {

  /**
   * Parse and return the results
   *
   * @return string
   */
  public function parse();

  public function setSource($source);

  /**
   * Attempts to copy the contents of a file to self::source
   *
   * self::$source will be set even if the path is invalid!
   *
   * @param string $path A filepath to a source file.
   */
  public function setSourceFromFile($path);

  public function getSource();
  
  /**
   * Returns the filepath of the source if applicable
   *
   * @return NULL|string
   */
  public function getSourcePath();

  public function addAction(ParseActionInterface $action);

  /**
   * Return all actions used in the parse method
   *
   * @return array
   *   An array of ParseAction objects.
   */
  public function getActions();


  /**
   * Add arbitrary data to self::data
   *
   * This data can be used as needed during parse operations, note any key that
   * conflicts with a previous key will overwrite the older data.
   *
   * @param array|object   $data
   *
   * @return object $this
   */
  public function addData($data);

  /**
   * Return the arbitrary data as an object
   *
   * @code
   *   $this->getData()->scope
   * @endcode
   *
   * @return object
   */
  public function getData();
}

/**
 * Class Parser
 */
class Parser implements ParserInterface {

  protected $actions, $data = array();
  protected $source_path = NULL;
  public $parsed = '';

  /**
   * Constructor
   *
   * @param string $source
   *   (Optional) Defaults to NULL.
   * @param bool $is_path.
   *   (Optional) Defaults to FALSE.  Set to TRUE if $source is a filepath.
   */
  public function __construct($source = NULL, $is_path = FALSE) {
    if ($is_path) {
      $this->setSourceFromFile($source);
    }
    else {
      $this->setSource($source);
    }
  }

  public function setSource($source) {
    $this->source = $source;

    return $this;
  }

  public function setSourceFromFile($source) {
    $this->source_path = $source;
    if (is_readable($source)) {
      $source = file_get_contents($source);
    }
    $this->setSource($source);

    return $this;
  }
  public function getSourcePath() {
    return $this->source_path;
  }

  public function getSource() {
    return $this->source;
  }

  public function parse() {
    $this->getData();
    $parsed = $this->getSource();
    foreach ($this->actions as $action) {
      $action->parse($parsed);
    }

    return $parsed;
  }

  public function addAction(ParseActionInterface $action) {
    $this->actions[] = $action;

    return $this;
  }

  public function getActions() {
    return $this->actions;
  }

  public function addData($data) {
    $this->data = (array) $data + $this->data;

    return $this;
  }

  public function getData() {
    return (object) $this->data;
  }
}

/** @} */ //end of group: loft_parser
