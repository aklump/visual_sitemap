<?php
namespace AKlump\LoftLib\Code;

/**
 * Represents a comment block generator.
 */
class PhpDocBlock {

  protected $data = array(
    'title' => '',
    'description' => '',
    'options' => array(),
    'indent' => 0,
  );

  /**
   * constructor
   *
   * @param string $title
   * @param array $options
   * - return
   * - var
   */
  public function __construct($title, $options = NULL) {
    // @todo Have not yet implemented $params
    $this->setTitle($title);
    $this->setOptions($options);
  }

  protected function getType($var) {
    $type = gettype($var);
    $type = $type === 'boolean' ? 'bool' : $type;
    $type = $type === 'integer' ? 'int' : $type;

    if ($type === 'object') {
      $type = '\\' . get_class($var);
    }

    return $type;
  }

  public function get() {
    $output   = array();
    $options = $this->getOptions();
    
    $output[] = '/**';
    $output[] = " * {$this->getTitle()}";

    if ($this->getDescription()) {
      $output[] = ' *';
      $output = array_merge($output, explode("\n", $this->getDescription()));
    }

    // Build the return vars
    if (array_key_exists('return', $options)) {
      $output[] = ' *';
      $type = $this->getType($options['return']);
      $output[] = " * @return $type";
      $return = explode("\n", print_r($options['return'], TRUE));
      
      // Get rid of some lines
      array_shift($return);
      array_shift($return);
      array_pop($return);
      array_pop($return);

      $output = array_merge($output, $return);    
    }
    if (array_key_exists('var', $options)) {
      $output[] = ' *';
      $type = $this->getType($options['var']);
      $output[] = " * @var $type";
    }
  
    // Make sure we start with '*'
    foreach ($output as $key => $value) {
      if (!in_array(substr(trim($value), 0, 1), array('/', '*'))) {
        $output[$key] = " * $value";
      }
    }
    $output[] = ' */';

    // Now apply indent
    foreach ($output as $key => $value) {
      $output[$key] = str_repeat(' ', $this->getIndent()) . $value;
      $output[$key] = rtrim($value);
    }
    
    $output   = implode("\n", $output) . "\n";
    
    return $output;
  }

  /**
   * Set the title.
   *
   * @param string $title
   *
   * @return $this
   */
  public function setTitle($title) {
    $this->data['title'] = (string) $title;
  
    return $this;
  }
  
  /**
   * Return the title.
   *
   * @return string
   */
  public function getTitle() {
    return $this->data['title'];
  }

  /**
   * Set the description.
   *
   * @param string $description
   *
   * @return $this
   */
  public function setDescription($description) {
    $this->data['description'] = (string) $description;
  
    return $this;
  }

  /**
   * Return the description.
   *
   * @return string
   */  
  public function getDescription() {
    return $this->data['description'];
  }

  /**
   * Set the indent.
   *
   * @param int $indent
   *
   * @return $this
   */
  public function setIndent($indent) {
    $this->data['indent'] = (int) $indent;
  
    return $this;
  }

  /**
   * Return the indent.
   *
   * @return int
   */  
  public function getIndent() {
    return $this->data['indent'];
  }

  /**
   * Set the options.
   *
   * @param array $options
   *
   * @return $this
   */
  public function setOptions($options) {
    $this->data['options'] = (array) $options;
  
    return $this;
  }

  /**
   * Return the options.
   *
   * @return array
   */
  public function getOptions() {
    return $this->data['options'];
  }
}
