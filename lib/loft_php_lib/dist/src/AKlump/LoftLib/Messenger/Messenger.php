<?php
namespace AKlump\LoftLib\Messenger;

/**
 * Represents a Messengar class.
 */
abstract class Messenger implements MessengerInterface {
  protected $data = array(
    'messages' => array(),
  );

  /**
   * Clears all messages
   *
   * @return $this
   */
  public function clear() {
    $this->data['messages'] = array();

    return $this;
  }

  public function add($message, $level = 'status', $tokens = array()) {

    if ($message instanceof \Exception) {
      $message = (string)$message;
    }

    if (empty($this->data['messages'][$level])) {
      $this->data['messages'][$level] = array();
    }
    $this->data['messages'][$level][] = array($message, $tokens);

    return $this;
  }

  public function getMessages() {
    return $this->data['messages'];
  }

  public function tell() {
    $output = array();
    $messages = $this->getMessages();

    // Put these in a respectable order of importance
    $levels = array_unique(array_merge(array('error', 'warning', 'status', 'debug'), array_keys($messages)));
    foreach ($levels as $level) {
      if (empty($messages[$level])) {
        continue;
      }
      foreach ($messages[$level] as $item) {
        foreach ($item[1] as $token => $value) {
          $item[0] = $this->tokenReplace($token, $value, $item[0]);
        }
        $output[$level][] = $item[0];
      }
    }

    // We have to return a string by interface, however as json our
    // extended classes can use this very easily as a structure.
    return json_encode($output);
  }

  protected function tokenReplace($token, $value, $content) {
    return str_replace($token, $value, $content);
  }
}
