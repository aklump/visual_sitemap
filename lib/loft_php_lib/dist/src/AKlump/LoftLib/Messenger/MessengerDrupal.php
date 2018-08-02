<?php
namespace AKlump\LoftLib\Messenger;

/**
 * Represents a drupal watchdog wrapper.
 */
class MessengerDrupal extends Messenger implements MessengerInterface {

  public function add($message, $code = NULL, $tokens = array()) {
    parent::add($message, $code, $tokens);

    if (function_exists('watchdog')) {
      $severity = 'WATCHDOG_' . strtoupper($code);
      $code = defined($severity) ? constant($severity) : NULL;
      $code = $code === NULL ? WATCHDOG_DEBUG : $code;
      watchdog('MessengerDrupal', $message, $tokens, $code);
    }
  }

  public function tell() {
    if (function_exists('drupal_set_message')) {
      foreach ($this->getMessages() as $m) {
        drupal_set_message(t($m[0], $m[2]), $m[1], FALSE);
      }
    }

    return json_decode(parent::tell());
  }
}
