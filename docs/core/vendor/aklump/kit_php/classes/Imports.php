<?php
/**
 * @file
 * Defines the Imports Class
 *
 * @ingroup kit_php
 * @{
 */
namespace aklump\kit_php;

/**
 * Interface ImportsInterface
 */
interface ImportsInterface extends KitInterface {

  /**
   * Return the working directory of the source file
   *
   * @return string
   */
  public function getDirname();

  /**
   * Set the base directory for include files
   *
   * @param string $dirname
   *
   * @return $this
   */
  public function setDirname($dirname);

  /**
   * Return a list of all included import files (not full paths).
   *
   * @return array
   */
  public function getImports();

  /**
   * Extract all import declarations from a string
   *
   * @param string $string
   *
   * @return array
   */
  public function extract($string);
}


/**
 * Class Imports
 */
class Imports extends Kit implements ImportsInterface {
  protected $dirname, $imports, $result;

  /**
   * Constructor
   *
   * @param type $source
   *   (Optional) Defaults to NULL. Can be a string, in which case you need to
       set $is_file to FALSE.  Otherwise the string should be a path to a file.
     @param bool $is_file
   */
  public function __construct($source = NULL, $is_file = TRUE) {
    $this->dirname = '';
    if ($source && $is_file) {
      $info = pathinfo($source);
      $this->dirname = $info['dirname'];
    }
    parent::__construct($source, $is_file);
    $this->imports = array();
  }

  public function extract($string) {
    preg_match_all('/<!--\s*(?:@import|@include) [\'"]*([^"]*?)[\'"]*\s*-->/', $string, $matches);
    $extracted = array();
    foreach (array_keys($matches[0]) as $key) {
      $files = explode(', ', $matches[1][$key]);
      foreach (array_keys($files) as $file_key) {
        $files[$file_key] = trim($files[$file_key], ', ');
      }
      $extracted[$matches[0][$key]] = $files;
    }

    return $extracted;
  }

  public function apply() {
    $this->result = $this->source;

    return $this->_apply($this->source);
  }

  /**
   * Recursively apply import files and return the compiled result
   *
   * @param string $source
   *   The source code to parse/compile.
   * @param string $relative_dir
   *   (Optional) Defaults to NULL. Used by the recursion to track relative
       directories to the parent file.
   *
   * @return string
   *   The fully compiled string.
   */
  protected function _apply($source, $relative_dir = NULL) {

    // There are imports to load and include
    if ($matches = $this->extract($source)) {
      foreach ($matches as $find => $array) {

        // Add this list of imports to our running list
        $this->imports += array_combine($array, $array);

        $replace = '';
        foreach ($array as $import_path) {
          $info = pathinfo($import_path);
          $partial = '_' . ltrim($info['basename'], '_');
          $variants = array(
            $this->dirname . '/' . $import_path,
            str_replace($info['basename'], $partial, $this->dirname . '/' . $import_path),
            $this->dirname . '/' . $relative_dir . '/' . $import_path,
            str_replace($info['basename'], $partial, $this->dirname . '/' . $relative_dir . '/' . $import_path),
            $import_path,
            str_replace($info['basename'], $partial, $import_path),
            $info['basename'],
            $partial,
          );
          $variants = array_unique($variants);
          foreach ($variants as $actual_path) {
            if (is_readable($actual_path) && ($contents = file_get_contents($actual_path))) {
              $replace .= $contents;
              $this->imports[$import_path] = $actual_path;
              break;
            }
          }
        }
        $this->result = str_replace($find, $replace, $this->result);
      }

      // Test to see if we have any more includes, if so process
      if ($this->extract($this->result)) {
        $this->_apply($this->result, $info['dirname']);
      }
    }

    // End of the line, no more imports, we're done
    return $this->result;
  }

  public function getDirname() {
    return $this->dirname;
  }

  public function setDirname($dir) {
    if (is_dir($dir)) {
      $this->dirname = $dir;
    }

    return $this;
  }

  public function getImports() {
    return $this->imports;
  }
}

/** @} */ //end of group: kit_php
