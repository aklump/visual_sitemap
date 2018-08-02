<?php

/**
 * @file
 * Header to use in controllers to validate CLI arguments.
 */

use AKlump\LoftLib\Component\Bash\Bash;
use AKlump\LoftLib\Component\Storage\FilePath;
use AKlump\VisualSitemap\VisualSitemap;

$cli = new Bash($argv);
$definition_file = $cli->getArg(1);

if (empty($definition_file)) {
  throw new \RuntimeException("Argument 1 must point to a configuration json file.");
}

if (!file_exists($definition_file)) {
  throw new \RuntimeException("Definition file does not exist: $definition_file");
}

$definition = FilePath::create($definition_file);
$json = $definition->load()->getJson(TRUE);
if (empty($json)) {
  throw new \RuntimeException("Invalid JSON in configuration file: $definition_file");
}

$twig = new Twig_Environment(new Twig_Loader_Filesystem(ROOT . '/templates'));

$vismap = new VisualSitemap(
  $definition,
  $twig,
  FilePath::create(ROOT . '/schema.json')
);

// Do not overwrite without the -f flag.
$output_path = $vismap->getOutputFilePath();
if (file_exists($output_path) && !$cli->hasFlag('f')) {
  throw new \RuntimeException("Output file \"" . basename($output_path) . "\" already exists, use the -f flag to overwrite.");
}

// Set the mode to develop.
if ($cli->hasParam('dev')) {
  $vismap->setMode(VisualSitemap::MODE_DEV);
}
