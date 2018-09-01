<?php
// Check for vismap installed as composer global package.
if (file_exists(__DIR__ . '/../../../autoload.php')) {
  require_once __DIR__ . '/../../../autoload.php';
}
else {
  if (!file_exists(ROOT . '/vendor/autoload.php')) {
    echo "Missing dependencies; have you run composer install from " . ROOT . PHP_EOL;
    exit(1);
  }
  require_once ROOT . '/vendor/autoload.php';
}
