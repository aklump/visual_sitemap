#!/usr/bin/env php
<?php

/**
 * @file
 * Controller for the visual sitemap.
 */
use AKlump\LoftLib\Component\Bash\Color;

define('ROOT', dirname(__FILE__));
require_once ROOT . '/vendor/autoload.php';

try {
  require ROOT . '/includes/bootstrap.inc';
  $vismap->generate()->save();
}
catch (\Exception $exception) {
  echo Color::wrap('red', $exception->getMessage() . PHP_EOL);
  exit(1);
}
