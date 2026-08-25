<?php
// config/firebase.php
// Dynamic Firebase Client Credentials Loader

define('FIREBASE_PROJECT_ID', 'player-auction-31d1e');
define('FIREBASE_AUTH_DOMAIN', 'player-auction-31d1e.firebaseapp.com');

/**
 * Returns Firebase Client Configuration Array.
 * Reads custom user credentials from config/firebase_keys.json if available.
 */
function get_firebase_config() {
    $keysFile = __DIR__ . '/firebase_keys.json';
    if (file_exists($keysFile)) {
        $data = json_decode(file_get_contents($keysFile), true);
        if ($data && !empty($data['apiKey'])) {
            return $data;
        }
    }

    return [
        'apiKey'            => 'AIzaSyC6-WVioI8crk0wabaX7PzEHQ8EPflF6RU',
        'authDomain'        => 'player-auction-31d1e.firebaseapp.com',
        'projectId'         => 'player-auction-31d1e',
        'storageBucket'     => 'player-auction-31d1e.firebasestorage.app',
        'messagingSenderId' => '135416654629',
        'appId'             => '1:135416654629:web:ff73b77ebf99e08d8b08cc',
        'measurementId'     => 'G-5E7SZ9TJDM'
    ];
}
?>
