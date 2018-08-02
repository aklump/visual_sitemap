<?php
namespace AKlump\LoftLib\Messenger;

/**
 * Represents a drupal watchdog wrapper.
 */
class MessengerShell extends Messenger implements MessengerInterface {

  protected function tokenReplace($token, $value, $content) {
    switch (substr($token, 0, 1)) {
      case '%':
        $value = "_{$value}_";
        break;
    }

    return str_replace($token, $value, $content);
  }

  public function tell() {
    $output = array();
    $groups = json_decode(parent::tell());
    foreach ($groups as $level => $messages) {
      foreach ($messages as $message) {
        $output[] = strtoupper($level) . ": $message";
      }
    }

    return implode(PHP_EOL, $output) . PHP_EOL;
  }
}
