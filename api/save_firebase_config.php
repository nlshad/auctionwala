<?php
// api/save_firebase_config.php
session_start();
header('Content-Type: application/json');

$rawInput = file_get_contents('php://input');
$inputData = json_decode($rawInput, true) ?? $_POST;

$apiKey = trim($inputData['apiKey'] ?? '');
$authDomain = trim($inputData['authDomain'] ?? '');
$projectId = trim($inputData['projectId'] ?? '');

if (empty($apiKey)) {
    echo json_encode(['success' => false, 'error' => 'API Key cannot be empty.']);
    exit;
}

$configData = [
    'apiKey' => $apiKey,
    'authDomain' => $authDomain ?: ($projectId ? $projectId . '.firebaseapp.com' : 'smcl-auction.firebaseapp.com'),
    'projectId' => $projectId ?: 'smcl-auction',
    'storageBucket' => ($projectId ?: 'smcl-auction') . '.appspot.com',
    'messagingSenderId' => '123456789012',
    'appId' => '1:123456789012:web:abc123def456789'
];

$filePath = __DIR__ . '/../config/firebase_keys.json';

if (file_put_contents($filePath, json_encode($configData, JSON_PRETTY_PRINT))) {
    echo json_encode(['success' => true, 'message' => 'Firebase credentials saved successfully!']);
} else {
    echo json_encode(['success' => false, 'error' => 'Failed to write credentials file.']);
}
?>
