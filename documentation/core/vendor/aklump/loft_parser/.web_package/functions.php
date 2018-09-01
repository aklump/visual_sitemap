<?php
/**
 * @file
 * Provides functions for build scripts
 *
 * @ingroup web_package
 * @{
 */
function js_replace_name_version(&$text, $package_name, $new_version) {
  $regex = '/(.*?)( JQuery JavaScript Plugin v)([^\s]+)/i';
  $text = preg_replace($regex, ' * ' . $package_name . '${2}' . $new_version,  $text);
}

function js_replace_description(&$text, $description) {
  $text = " * $description";
}

function js_replace_date(&$text, $date) {
  $text = " * Date: $date";
}

function js_replace_homepage(&$text, $homepage) {
  $text = " * $homepage";
}

function js_replace_version_function(&$source, $new_version) {
  $source = preg_replace('/(\$\.fn.+version.+\')([\d-.]*)\'/', '${1}' . $new_version . "'", $source);
}

/**
 * Copy files from root into demo.
 *
 * @param  array $files
 * @param  string $demo_dir
 */
function to_demo_copy($path_to_root, $files, $demo_dir = 'demo') {
  foreach ($files as $file) {
    copy($path_to_root . "/$file", $path_to_root . "/$demo_dir/$file");
  }
}

/**
 * Copy files from demo into root.
 *
 * @param  array $files
 * @param  string $demo_dir
 */
function from_demo_copy($path_to_root, $files, $demo_dir = 'demo') {
  foreach ($files as $file) {
    copy($path_to_root . "/$demo_dir/$file", $path_to_root . "/$file");
  }
}