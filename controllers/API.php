<?php

use \Firebase\JWT\JWT;

class API
{
  protected $db;

  public function beforeRoute($f3)
  {
    $token = $f3->get('HEADERS.Authorization');
    if ($token) {
      // Validate token
      try {
        $user = JWT::decode($token, $f3->get('key'), array('HS256'));
        $db = new DB\SQL($f3->get('main_db'), $f3->get('main_username'), $f3->get('main_password'));
        $result = $db->exec("SELECT id FROM users WHERE id = ?", $user->id);
        if (!$result) {
          $f3->reroute('/api/unauthorized');
        }
        $f3->set('REQUEST.user', $user);
      } catch (Exception $e) {
        $f3->reroute('/api/unauthorized');
      }
    } else {
      $f3->reroute('/api/unauthorized');
    }
  }

  public function sendSMS($f3)
  {
    // Set response type
    header('Content-type: application/json');

    // Send SMS
    try {
      $sentMessages = SMS::createAndSend([
        'recipient_phone_number' => $f3->get('POST.recipient_phone_number'),
        'message_content' => $f3->get('POST.message_content')
      ]);
      
      echo json_encode([
        'status' => 'OK',
        'number_of_messages_sent' => $sentMessages
      ]);
    } catch (Exception $e) {
      echo json_encode([
        'status' => 'ERR',
        'cause' => $e->getMessage()
      ]);
    }
  }

}