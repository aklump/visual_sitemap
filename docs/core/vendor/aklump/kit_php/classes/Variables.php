<?php
/**
 * @file
 * The Variables class
 *
 * @ingroup kit_php
 * @{
 */
namespace aklump\kit_php;

/**
 * Interface VariablesInterface
 */
interface VariablesInterface extends KitInterface {

  /**
   * Extract variables from kit code
   *
   * @param string $kit_code
   *
   * @return array
   *   An associate array
   */
  public function extract($kit_code);

  /**
   * Return all vars extracted since instantiation
   *
   * @return array
   */
  public function getVars();

  /**
   * Set the variables to use in an apply
   *
   * @param array $variables
   *
   * @return object $this
   */
  public function setVars($variables);
}

/**
 * Class Variables
 */
class Variables extends Kit implements VariablesInterface {

  protected $variables, $declarations;

  /**
   * Constructor
   *
   * @param type $source
   *   (Optional) Defaults to NULL.
   */
  public function __construct($source = NULL, $is_file = TRUE) {
    parent::__construct($source, $is_file);
    $this->variables    = array();
    $this->declarations = array();
  }

  public function extract($source = NULL) {
    if ($source !== NULL) {
      $this->setSource($source);
    }
    if (empty($this->source)
        || !preg_match_all('/<!--\s*(?:\$|@)([^ =:]+)\s*[ =:]\s*(.*)?-->/', $this->source, $matches)) {
      return array();
    }
    $variables = array();
    foreach ($matches[1] as $key => $match) {
      $variables[$match] = trim($matches[2][$key], ' \'"');
      $this->declarations[$match] = $matches[0][$key];
    }
    $this->variables += $variables;

    // Return only the vars extracted on this call
    return $variables;
  }

  public function getVars() {
    return $this->variables;
  }

  public function setVars($vars) {
    if (is_array($vars)) {
      $this->variables = $vars;
    }

    return $this;
  }

  public function apply() {
    $result = $this->source;
    foreach ($this->variables as $key => $value) {
      $find = "<!--\$$key-->";
      $result = str_replace($find, $value, $result);
      $find = "<!--@$key-->";
      $result = str_replace($find, $value, $result);
    }

    // Go through and remove entire lines if they are just declarations
    $result = explode(PHP_EOL, $result);
    foreach ($result as $key => $value) {
      if (in_array($value, $this->declarations)) {
        unset($result[$key]);
      }
    }
    $result = implode(PHP_EOL, $result);
    // Now go through and remove any declarations not just single lines
    foreach ($this->declarations as $value) {
      $result = str_replace($value, '', $result);
    }

    return $result;
  }
}




/** @} */ //end of group: kit_php
