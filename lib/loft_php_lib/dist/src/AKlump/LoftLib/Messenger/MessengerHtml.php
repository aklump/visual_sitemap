<?php
namespace AKlump\LoftLib\Messenger;

/**
 * Represents a Messengar class.
 */
class MessengerHtml extends Messenger implements MessengerInterface {

  protected function tokenReplace($token, $value, $content) {
    switch (substr($token, 0, 1)) {
      case '@':
        $value = $this->checkPlain($value);
        break;

      case '%':
        $value = $this->checkPlain($value);
        $value = "<em class=\"placeholder\">$value</em>";
        break;

    }

    return str_replace($token, $value, $content);
  }

  protected function checkPlain($value) {
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
  }

  public function tell() {
    $output = array();
    $groups = json_decode(parent::tell());
    foreach ($groups as $level => $messages) {
      $output[] = '<ul class="messages ' . $level . '">';
      foreach ($messages as $message) {
        $output[] = '  <li class="message">' . $message . '</li>';
      }
      $output[] = '</ul>';
    }

    return implode(PHP_EOL, $output);
  }
}
