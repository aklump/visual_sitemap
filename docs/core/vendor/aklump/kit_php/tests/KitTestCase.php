<?php
/**
 * @file
 * Tests for the CodeKitTestCase class
 *
 * @ingroup kit_php
 * @{
 */
class KitTestCaseTest extends PHPUnit_Framework_TestCase {
  protected $paths;

  protected function getTempDir() {
    return sys_get_temp_dir() . '/com.aklump.kit_php';
  }

  /**
   * Write a string to a file
   *
   * @param string $contents
   * @param string $file
   *   Do not include the path in this argument
   * @param string $dir
   *   (Optional) Defaults to $this->getTempDir(). You may define a directory or
       directories that will be created inside the temp dir.
   *
   * @return string
   *   If the file is created the entire path to the file
   */
  protected function writeFile($contents, $file, $dir = NULL) {

    // Make sure the file is inside the temp dir
    if ($dir && strpos($dir, $this->getTempDir()) === 0) {
      $dir = substr($dir, strlen($this->getTempDir()));
    }
    $dir = $this->getTempDir() . '/' . trim($dir, '/');

    if (!is_dir($dir)) {
      mkdir($dir, 0700, TRUE);
    }

    if (is_writable($dir)) {
      $fp = fopen($dir . '/' . $file, 'w');
      fwrite($fp, $contents);
      fclose($fp);
      $this->paths[] = $dir . '/' . $file;
    }

    return is_file($dir . '/' . $file) ? $dir . '/' . $file : FALSE;
  }

  function __destruct() {
    // Delete all of our temporary files
    if (is_dir($this->getTempDir())) {
      $files = new RecursiveIteratorIterator(
          new RecursiveDirectoryIterator($this->getTempDir(), RecursiveDirectoryIterator::SKIP_DOTS),
          RecursiveIteratorIterator::CHILD_FIRST
      );

      foreach ($files as $fileinfo) {
        $todo = ($fileinfo->isDir() ? 'rmdir' : 'unlink');
        $todo($fileinfo->getRealPath());
      }

      rmdir($this->getTempDir());
    }
  }
}

/** @} */ //end of group: kit_php
