<?php

// Import fat free library.
$f3 = require(__DIR__ . "/lib/base.php");

// Import php-jwt library
require(__DIR__ . "/vendor/firebase/php-jwt/src/BeforeValidException.php");
require(__DIR__ . "/vendor/firebase/php-jwt/src/ExpiredException.php");
require(__DIR__ . "/vendor/firebase/php-jwt/src/SignatureInvalidException.php");
require(__DIR__ . "/vendor/firebase/php-jwt/src/JWT.php");
use \Firebase\JWT\JWT;

// Import SMS library
require(__DIR__ . "/engine/SMS.php");

require(__DIR__ . "/controllers/API.php");

// Load configuration (config.ini) file.
$f3->config(__DIR__ . "/config.ini");

$f3->route('POST /api/auth', function($f3) {
  // Validate POST data
  // TODO

  // Set response type
  header('Content-type: application/json');

  // Get Bcrypt instance
  $crypt = Bcrypt::instance();

  // Instantiate DB object
  $db = new DB\SQL($f3->get('main_db'), $f3->get('main_username'), $f3->get('main_password'));
  $user = $db->exec("SELECT id, username, password, description FROM users WHERE username = ?", $f3->get('POST.username'));
  if ($user) {
    $user = $user[0];
    // Verify password
    if ($crypt->verify($f3->get('POST.password'), $user['password'])) {
      // Generate token
      $claim = $user;
      $jwtToken = JWT::encode($claim, $f3->get('key'));

      // Spit out the result
      echo json_encode([
        "status" => "OK",
        "token" => $jwtToken
      ]);
    } else {
      // Send error: wrong username or password
      echo json_encode([
        "status" => "ERR",
        "description" => "Invalid username or password."
      ]);
    }
  } else {
    // Send error: wrong username or password
    echo json_encode([
      "status" => "ERR",
      "description" => "Invalid username or password."
    ]);
  }
});

$f3->route('GET /api/unauthorized', function() {
  header('Content-type: application-json');
  echo json_encode([
    "status" => "ERR",
    "description" => "You don't have access to this service."
  ]);
});

// DEVELOPMENT ONLY
// REGISTER USER
$f3->route('GET /api/@user/@password', function($f3) {
  header('Content-type: application/json');
  $db = new DB\SQL($f3->get('main_db'), $f3->get('main_username'), $f3->get('main_password'));
  $crypt = Bcrypt::instance();
  $success = $db->exec("INSERT INTO users (username, password) VALUES (:username, :password)",
    [':username' => $f3->get('PARAMS.user'), ':password' => $crypt->hash($f3->get('PARAMS.password'))]);
  if ($success) {
    echo json_encode([
      "status" => "OK"
    ]);
  } else {
    echo json_encode([
      "status" => "ERR",
      "description" => "Invalid username or password."
    ]);
  }
});

// Route: POST /api/sms
// Args:
//  - recipient_phone_number: string 
//  - message_content: string
$f3->route('POST /api/sms', 'API->sendSMS');

// Run the app
$f3->run();