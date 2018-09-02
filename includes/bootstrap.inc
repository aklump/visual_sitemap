<?php

/**
 * @file
 * Header to use in controllers to validate CLI arguments.
 */

use AKlump\LoftLib\Bash\Bash;
use AKlump\LoftLib\Bash\Color;
use AKlump\LoftLib\Storage\FilePath;
use AKlump\VisualSitemap\VisualSitemap;

require_once ROOT . '/includes/autoload.inc';

if (!isset($cli)) {
  $cli = new Bash($argv);
}

// Display the version.
if ($cli->hasFlag('v')) {
  echo Color::wrap('cyan', VisualSitemap::getCredits()) . PHP_EOL;
  exit(0);
}

$definition_file = $cli->getArg(1);

if (empty($definition_file)) {
  throw new \RuntimeException("Argument 1 must point to a configuration json file.");
}

$definition = FilePath::create($definition_file);
$basename = $definition->getBasename();
if (!$definition->exists()) {
  if (!$cli->hasFlag('c')) {
    throw new \RuntimeException("Definition file does not exist: \"$basename\"; use -c to create it.");
  }
  else {

    // Uses quick_start.json as the template.
    $template = FilePath::create(ROOT . '/quick_start.json');
    $definition->copyFrom($template);
    if ($definition->exists()) {
      echo Color::wrap('green', "\"$definition_file\" has been created.") . PHP_EOL;
      exit(0);
    }
    throw new \RuntimeException("Could not create \"$basename\"");
  }
}
elseif ($cli->hasFlag('c')) {
  throw new \RuntimeException("You may not use -c when \"$basename\" already exists.");
}

$json = $definition->load()->getJson(TRUE);
if (empty($json)) {
  throw new \RuntimeException("Invalid JSON in configuration file: $definition_file");
}

$state = $cli->getParam('state');
if ($state && !in_array($state, ($valid_states = VisualSitemap::getDefinedStates($json)))) {
  throw new \RuntimeException("Invalid state \"$state\"; must be one of: " . implode(', ', $valid_states));
}

// Template discovery.
$templates = [];
$user_templates = NULL;
if ($cli->hasParam('theme')) {
  $templates[] = $user_templates = realpath($cli->getParam('theme'));
}
$templates[] = ROOT . '/templates';

$twig = new Twig_Environment(new Twig_Loader_Filesystem($templates));

$vismap = new VisualSitemap(
  $definition,
  $twig,
  FilePath::create(ROOT . '/schema.json'),
  $user_templates,
  $state
);

// Do not overwrite without the -f flag.
if ($output = $cli->getParam('out')) {
  $vismap->setOutputFilePath($output);
}
$output_path = $vismap->getOutputFilePath();
if (file_exists($output_path) && !$cli->hasFlag('f')) {
  throw new \RuntimeException("Output file \"" . basename($output_path) . "\" already exists, use the -f flag to overwrite.");
}

// Set the mode to develop.
if ($cli->hasParam('dev')) {
  $vismap->setMode(VisualSitemap::MODE_DEV);
}


