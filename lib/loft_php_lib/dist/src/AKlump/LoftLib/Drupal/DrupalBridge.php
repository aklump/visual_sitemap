<?php
namespace AKlump\LoftLib\Drupal;

// https://www.drupal.org/node/1009966
if (!isset($_SERVER['REMOTE_ADDR'])) {
  $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
}

/**
 * Class DrupalBridge
 *
 * A class to expose drupal to native PHP, for example a database connection.
 *
 * @package AKlump\LoftLib\Drupal
 */
class DrupalBridge {
  protected $database, $version, $root;

  /**
   * Connection constructor.
   *
   * @param $pathToSettings
   */
  public function __construct($drupal_root) {
    $this->root = rtrim($drupal_root, '/');
    if (!$this->getVersion()) {
      throw new \InvalidArgumentException("$drupal_root does not appear to be a drupal root path.");
    }
    define('DRUPAL_ROOT', $this->root);
    switch ($this->getVersion()) {
      case 7:
        define('BRIDGE_DRUPAL_BOOTSTRAP', $drupal_root . '/includes/bootstrap.inc');
        break;
      case 8;
        //        throw new \RuntimeException("Drupal 8 is not yet supported!");
        //        define('BRIDGE_DRUPAL_BOOTSTRAP', $drupal_root . '/core/includes/bootstrap.inc');
        break;
    }
  }

  /**
   * Returns the version of drupal at the web root.
   *
   * @return string
   */
  public function getVersion() {
    if (!isset($this->version)) {

      // Drupal 6
      $file = $this->root . '/modules/system/system.module';
      if (is_file($file)) {
        require $file;
        if (defined('VERSION')) {
          $this->version = VERSION;

          return $this->version;
        }
      }

      // Drupal 7
      $file = $this->root . '/includes/bootstrap.inc';
      if (is_file($file)) {
        require $file;
        if (defined('VERSION')) {
          $this->version = VERSION;

          return $this->version;
        }
      }

      // Drupal 8
      $file = $this->root . '/core/lib/Drupal.php';
      if (is_file($file)) {
        require $file;
        $this->version = \Drupal::VERSION;

        return $this->version;
      }

      throw new \RuntimeException("Cannot determine drupal version.");
    }

    return strval($this->version);
  }

  /**
   * Returns the path to the root of Drupal.
   *
   * This is also available as DRUPAL_ROOT.
   *
   * @return string
   */
  public function getRoot() {
    return $this->root;
  }

  /**
   * @param string $database
   *
   * @return mixed
   * @throws \Doctrine\DBAL\DBALException If we can't connect.
   * @throws \RuntimeException If the $database configuration is not accessible.
   */
  public function getConnection($database = 'default') {
    if (empty($this->database[$database]['connection'])) {
      require_once BRIDGE_DRUPAL_BOOTSTRAP;
      drupal_bootstrap(BRIDGE_DRUPAL_BOOTSTRAP_CONFIGURATION);
      if (empty($GLOBALS['databases'][$database]['default'])) {
        throw new \RuntimeException("Missing configuration for \$databases[$database]");
      }
      $this->database[$database] = $GLOBALS['databases'][$database]['default'];
      $config = new \Doctrine\DBAL\Configuration();
      $db = $this->database[$database];
      $connectionParams = array(
        'dbname'   => $db['database'],
        'user'     => $db['username'],
        'password' => $db['password'],
        'host'     => $db['host'] === 'localhost' ? '127.0.0.1' : $db['host'],
        'driver'   => 'pdo_mysql',
      );
      $this->database[$database]['connection'] = \Doctrine\DBAL\DriverManager::getConnection($connectionParams, $config);
    }

    return $this->database[$database]['connection'];
  }

  public function getVar($varName, $default = NULL) {
    require_once BRIDGE_DRUPAL_BOOTSTRAP;
    drupal_bootstrap(BRIDGE_DRUPAL_BOOTSTRAP_VARIABLES);
    //    global $database;
    //    $database['default']['default']['host'] = '127.0.0.1';

    return variable_get($varName, $default);
  }
}
