<?php
/**
 * @file
 * Defines the Blocking class.
 *
 * @ingroup semaphore
 * @{
 */
namespace AKlump\LoftLib\Utils;

/**
 * Represents a Blocking object class.
 * 
 * @brief This object can be used to block concurrency in scripts.
 *
 * @code
 *   $blocking = new Blocking;
 *
 *   // First create the key by passing a unique id, in this case the filename.
 *   $key = $blocking->get(__FILE__);
 *   if ($blocking->acquire($key)) {
 *     // do something
 *     $blocking->release($key);
 *   }
 *   else {
 *     print "Already running!";
 *   }
 *
 *   // You can explicitly release the block; it is possible for a block to 
 *   // persist across scripts if you pass the autoRelease parameter of FALSE
 *   // to the get() method.  Otherwise blocks are released at the end of the 
 *   // script automatically.
 *   $blocking->release($key);
 * @endcode
 */
class Blocking {

  protected $register = array();

  public function __construct() {
    register_shutdown_function(function($blocking) {
      $blocking->shutdown();
    }, $this);
  }

  /**
   * Gets a blocking key to be used based on a unique id.
   *
   * @param  mixed $uniqueId A unique id to refer to a single block.
   *
   * @return string A key to be used by other methods to refer to this block.
   */
  public function get($uniqueId, $autoRelease = TRUE) {
    $key  = md5($uniqueId);
    $path = sys_get_temp_dir() . "/aklump-loftlib-utils-blocking-$key.json";
    $data = array(
      'key' => $key,
      'path' => $path,
      'autoRelease' => $autoRelease,
    );
    $this->register[$key] = (object) $data;

    return $key;
  }

  /**
   * Returns info about a particular blocker
   *
   * @return object|NULL
   *   - key
   *   - path Path the file that is holding the block.
   *   - autoRelease
   */
  public function info($key) {
    return isset($this->register[$key]) ? $this->register[$key] : NULL;
  }

  /**
   * Acquire a block on a resource.
   *
   * @param  string $key Obtained from get().
   *
   * @return bool
   */
  public function acquire($key) {
    if (!isset($this->register[$key])) {
      throw new \InvalidArgumentException("Blocking $key does not exist; use get() first.");
    }
    $path = $this->register[$key]->path;
    if (file_exists($path)) {
      return FALSE;
    }
    file_put_contents($path, '');

    return TRUE;
  }

  /**
   * Release a block on a resource.
   *
   * @param  string $key Obtained by get().
   *
   * @return bool
   */
  public function release($key) {
    if (file_exists($this->register[$key]->path)) {
      unlink($this->register[$key]->path);
    }

    return TRUE;
  }

  /**
   * Used by register_shutdown_function() to release all blocks.
   */
  public function shutdown() {
    foreach ($this->register as $semaphore) {
      if ($semaphore->autoRelease) {
        $this->release($semaphore->key);
      }
    }
  }
}
