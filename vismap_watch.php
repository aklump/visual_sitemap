#!/usr/bin/env php
<?php

/**
 * @file
 * File watcher controller for the visual sitemap.
 */

define('ROOT', dirname(__FILE__));
require_once ROOT . '/vendor/autoload.php';

use AKlump\LoftLib\Component\Bash\Bash;
use AKlump\LoftLib\Component\Bash\Color;
use Illuminate\Filesystem\Filesystem;
use JasonLewis\ResourceWatcher\Tracker;
use JasonLewis\ResourceWatcher\Watcher;

try {
  require ROOT . '/includes/bootstrap.inc';

  // Watch for and build on changes.
  $watcher = new Watcher(new Tracker(), new Filesystem());
  $listener = $watcher->watch($definition->getPath());
  $listener->modify(function () use ($cli, $definition_file) {
    echo '.';
    try {
      Bash::exec([
        ROOT . '/vismap.php',
        $definition_file,
        $cli->hasFlag('f') ? '-f' : '',
        $cli->hasParam('out') ? '--out=' . $cli->getParam('out') : '',
      ]);
    }
    catch (\Exception $exception) {
      echo Color::wrap('red', trim($exception->getMessage()));
    }
  });

  $poll_interval = 1;
  echo "Watching for changes every $poll_interval seconds..." . PHP_EOL;
  echo "Press CTRL-C to exit" . PHP_EOL;
  echo '.';
  try {
    Bash::exec([
      ROOT . '/vismap.php',
      $definition_file,
      $cli->hasFlag('f') ? '-f' : '',
      $cli->hasParam('out') ? '--out=' . $cli->getParam('out') : '',
    ]);
  }
  catch (\Exception $exception) {
    echo Color::wrap('red', trim($exception->getMessage()));
  }
  $watcher->start($poll_interval * 1000000);
}
catch (\Exception $exception) {
  echo Color::wrap('red', $exception->getMessage() . PHP_EOL);
  exit(1);
}
