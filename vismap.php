#!/usr/bin/env php
<?php

/**
 * @file
 * Controller for the visual sitemap.
 */

define('ROOT', dirname(__FILE__));
require_once ROOT . '/vendor/autoload.php';

use AKlump\LoftLib\Component\Storage\FilePath;
use AKlump\VisualSitemap\VisualSitemap;

try {
  require ROOT . '/includes/bootstrap.inc';

  $vismap->generate()->save();
}
catch (\Exception $exception) {
  print $exception->getMessage() . PHP_EOL . PHP_EOL;
  exit(1);
}
