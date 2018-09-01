<?php
/**
 * Copy a markdown file from A to B removing all FrontMatter
 */
use Webuni\FrontMatter\FrontMatter;

require_once dirname(__FILE__) . '/../vendor/autoload.php';
$from = $argv[1];
$to = $argv[2];

if (!file_exists($from)) {
    return 1;
}

$fm = new FrontMatter();
if (($contents = file_get_contents($from))) {
    $document = $fm->parse($contents);
    file_put_contents($to, $document->getContent());
}

return file_exists($to) ? 0 : 1;
