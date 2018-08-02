<?php
namespace AKlump\LoftLib\Messenger;

/**
 * Interface OvaDeviceInterface
 */
interface MessengerInterface {

  /**
   * Clears all messages
   *
   * @return $this
   */
  public function clear();

  /**
   * Return the messages array.
   *
   * @return array
   */
  public function getMessages();

  /**
   * Adds a message
   *
   * @param string||Exception $message
   * @param string $code
   *   One of:
   *   - error
   *   - warning
   *   - status
   *   - debug
   * @param array  $tokens
   *   Used for token replacement in message if supported.
   */
  public function add($message, $code = 'status', $tokens = array());

  /**
   * Return a string that represents all the messages in the object
   *
   * In some cases you will use this hook to fire framework output methods
   * such as drupal_set_message.  You should then return parent::tell()
   * so that a string is returned.  Refer to MessengerDrupal.
   *
   * This is how you will display messages and then clear them...
   *
   * @code
   *   print $obj->tell()
   *   $obj->clear();
   * @endcode
   *
   * @return string
   */
  public function tell();
}
