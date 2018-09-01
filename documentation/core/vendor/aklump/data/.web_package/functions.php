<?php
/**
 * @file
 * Provides functions for build scripts
 *
 * @ingroup web_package
 * @{
 */
function js_replace_name_version(&$text, $package_name, $new_version) {
  $regex = '/(.*?)(\s+(?:JQuery|JavaScript|JS).*v)(\{\{\s*version\s*\}\}|[\d\.a-z\-]+)$/i';
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

/**
 * Replace the copyright year and holder in a string.
 *
 * @param  string &$text The source string.
 * @param  string $holder The name of the copyright holder.
 * @param  string $year Optional, defaults to 'now' for the current year.
 */
function js_replace_copyright(&$text, $holder, $year = 'now') {
  $regex = '/copyright\s*((\d{4})(?:\-(\d{4}))?),\s*(.*)$/i';
  if (preg_match($regex, $text, $matches)) {
    list(, $find_date, $original_date, , $find_name) = $matches;
    $replace_date[] = $original_date;

    if ($year === 'now') {
      $now = new \DateTime('now', new \DateTimeZone('America/Los_Angeles'));
      $year = $now->format('Y');
    }
    $replace_date[] = $year;

    $replace_date = implode('-', array_unique($replace_date));
    $text = str_replace($find_date, $replace_date, $text);

    $text = str_replace($find_name, $holder, $text);
  }
}

function js_replace_version_function(&$source, $new_version) {
  $source = preg_replace('/((?:this|\$\.fn\..+)\.version.+(?:\'|"))([\d\.]+?)((?:\'|").*)/s', '${1}' . $new_version . '${3}', $source);
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
