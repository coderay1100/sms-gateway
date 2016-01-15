<?php

define('MAX_LENGTH', 160);
define('MAX_CHUNK_LENGTH', 153);

class SMS
{

  protected $recipient_phone_number;
  protected $message_content;

  public function __construct($data)
  {
    // Check argument data type
    if (gettype($data) != "array") {
      throw new InvalidArgumentException('$data argument must be of type array');
    }

    // Check argument property: recipient_phone_number
    if (!isset($data['recipient_phone_number'])) {
      throw new BadMethodCallException('$data parameter expects a "recipient_phone_number" property');
    }

    // Check argument property: content
    if (!isset($data['message_content'])) {
      throw new BadMethodCallException('$data parameter expects a "content" property');
    }

    $this->recipient_phone_number = $data['recipient_phone_number'];
    $this->message_content = $data['message_content'];
  }

  public function send()
  {
    // Check message length
    $messages = [];
    if (strlen($this->message_content) > MAX_LENGTH) {
      // If greater than maximum message length,
      // divide the message into chunks
      $messages = str_split($this->message_content, MAX_CHUNK_LENGTH);
    } else {
      // Else send the single message
      $messages[] = $this->message_content;
    }

    // Send every message
    $f3 = Base::instance();
    $db = new DB\SQL($f3->get('smsd_db'), $f3->get('smsd_username'));
    foreach ($messages as $message) {
      $db->exec("INSERT INTO outbox(DestinationNumber, TextDecoded, CreatorID) 
                 VALUES (?, ?, 'Beyond SMS Gateway API v{$f3->get('version')}')",
                 [1=>$this->recipient_phone_number, 2=>$message]);
    }

    // return the number of messages sent
    return count($messages);
  }

  public static function createAndSend($data)
  {
    return (new SMS($data))->send();
  }
}