<?php
/**
 * @file
 * Reads the filesystem and writes new .json format as a file.
 *
 */
use AKlump\Data\Data;
use Webuni\FrontMatter\FrontMatter;

$file = __FILE__;
require_once dirname($file) . '/../vendor/autoload.php';

if (count($argv) < 3
    || ((list(, $source_dir, $json_file, $merge_file) = $argv)
        && (empty($source_dir) || empty($json_file)))
) {
    echo "Missing parameters to $file" . PHP_EOL;

    return;
}

if (file_exists($json_file)) {
    echo "Cannot create $json_file as it already exists." . PHP_EOL;

    return;
}

$info = array();
$first_level = scandir($source_dir);
$fm = new FrontMatter();
$g = new Data();
foreach ($first_level as $file) {
    if (substr($file, 0, 1) === '.') {
        continue;
    }

    // Do not include search--results.md
    if ($file === 'search--results.md') {
        continue;
    }

    $path = $source_dir . '/' . $file;

    $frontmatter = array();
    $contents = $body = '';
    if (is_file($path) && ($contents = file_get_contents($path))) {
        $document = $fm->parse($contents);
        $frontmatter = $document->getData();
        $body = $document->getContent();
    }

    // Allow this file to be skipped for indexing.
    if ($g->get($frontmatter, 'noindex')) {
        continue;
    }

    $meta = array();

    //
    // 1. Frontmatter
    $meta['chapter'] = $g->get($frontmatter, 'chapter');
    $meta['section'] = $g->get($frontmatter, 'section');
    $meta['title'] = $g->get($frontmatter, 'title');
    $meta['sort'] = $g->get($frontmatter, 'sort');

    //
    // 2. html heading
    if (!$g->get($meta, 'title') && preg_match('/^\s*#[^#]\s*(.+?)\s*$/m', $body, $matches)) {
        $g->fill($meta, 'title', $matches[1]);
    }

    //
    // 3. file-name derived meta
    //
    // We check for chapter--section.md format
    if (($parts = explode('--', $file)) && count($parts) > 1) {
        $g->fill($meta, 'chapter', array_shift($parts));
        $g->fill($meta, 'section', implode('', $parts));
    }

    $g->fill($meta, 'title', clean_title($file));

    // In the top level there is no chapter indication.
    if (path_is_section($file)) {
        $info[pathinfo($file, PATHINFO_FILENAME)] = array(
            'file'   => $file,
            'title'  => $g->get($meta, 'title'),
            'parent' => $g->get($meta, 'chapter'),
            'weight' => $g->get($meta, 'sort'),
        );
    }

    // One level in, designates a chapter by dirname.
    // elseif (is_dir($source_dir . '/' . $meta['section'])) {
    //   $meta['chapter'_level = scandir($source_dir . '/' . $meta['section']);
    //   foreach ($meta['chapter'_level as $meta['chapter'_section) {
    //     if (substr($meta['chapter'_section, 0, 1) === '.') {
    //       continue;
    //     }

    //     if (path_is_section($meta['chapter'_section)) {
    //       $info[pathinfo($meta['chapter'_section, PATHINFO_FILENAME)] = array(
    //         'file' => $meta['section'] . '/' . $meta['chapter'_section,
    //         'title' => clean_title($meta['chapter'_section),
    //         'parent' => clean_id($meta['section']),
    //       );
    //     }
    //   }
    // }
}
require_once dirname(__FILE__) . '/json.inc';

