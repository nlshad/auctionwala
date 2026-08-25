<?php
// api/auth_firebase.php
session_start();
require_once '../config/db.php';
header('Content-Type: application/json');

if (!$pdo) {
    echo json_encode(['success' => false, 'error' => 'Database connection offline.']);
    exit;
}

// Support JSON POST input or standard $_POST
$rawInput = file_get_contents('php://input');
$inputData = json_decode($rawInput, true) ?? $_POST;

$firebaseUid = trim($inputData['firebase_uid'] ?? $inputData['uid'] ?? '');
$email       = trim($inputData['email'] ?? '');
$name        = trim($inputData['name'] ?? $inputData['displayName'] ?? '');
$photoUrl    = trim($inputData['photo_url'] ?? $inputData['photoURL'] ?? '');
$idToken     = trim($inputData['id_token'] ?? '');

if (empty($email)) {
    echo json_encode(['success' => false, 'error' => 'Invalid Firebase Authentication payload. Email missing.']);
    exit;
}

if (empty($name)) {
    $name = explode('@', $email)[0];
}

try {
    // 1. Check if user exists in SaaS users table by google_id (firebase_uid) or email
    $stmt = $pdo->prepare("SELECT id, name, email FROM users WHERE google_id = :uid OR email = :email");
    $stmt->execute(['uid' => $firebaseUid, 'email' => $email]);
    $user = $stmt->fetch();

    if ($user) {
        $userId = (int)$user['id'];
        // Update google_id and provider if missing
        $up = $pdo->prepare("UPDATE users SET google_id = :uid, auth_provider = 'google' WHERE id = :id");
        $up->execute(['uid' => $firebaseUid, 'id' => $userId]);
    } else {
        // Create new SaaS Organizer user account
        $stmt = $pdo->prepare("INSERT INTO users (google_id, name, email, auth_provider) VALUES (:uid, :name, :email, 'google')");
        $stmt->execute([
            'uid'   => $firebaseUid,
            'name'  => $name,
            'email' => $email
        ]);
        $userId = (int)$pdo->lastInsertId();
    }

    // 2. Provision Session variables
    $_SESSION['user_logged_in'] = true;
    $_SESSION['user_id']        = $userId;
    $_SESSION['user_name']      = $name;
    $_SESSION['user_email']     = $email;
    $_SESSION['firebase_uid']   = $firebaseUid;
    $_SESSION['role']           = 'Organizer';
    $_SESSION['admin_logged_in']= true; // Organizer has admin rights over their tournaments

    // 3. Resolve or provision organizer's initial tournament
    $stmt = $pdo->prepare("SELECT id, code FROM tournaments WHERE organizer_id = :organizer_id ORDER BY id ASC LIMIT 1");
    $stmt->execute(['organizer_id' => $userId]);
    $tournament = $stmt->fetch();

    if (!$tournament) {
        // Auto-create initial default tournament
        $slugName = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $name));
        $code = ($slugName ?: 'league') . '-' . rand(100, 999);

        $stmt = $pdo->prepare("INSERT INTO tournaments (organizer_id, name, code, total_purse_default, max_squad_size_default) VALUES (:organizer_id, :name, :code, 10000, 11)");
        $stmt->execute([
            'organizer_id' => $userId,
            'name'         => $name . "'s Premier League",
            'code'         => $code
        ]);
        $tId = (int)$pdo->lastInsertId();

        // Initialize auction state for this new tournament
        $stmt = $pdo->prepare("INSERT INTO auction_state (tournament_id, status) VALUES (:tId, 'Idle')");
        $stmt->execute(['tId' => $tId]);

        $_SESSION['tournament_id']   = $tId;
        $_SESSION['tournament_code'] = $code;
    } else {
        $_SESSION['tournament_id']   = (int)$tournament['id'];
        $_SESSION['tournament_code'] = $tournament['code'];
    }

    echo json_encode([
        'success'      => true,
        'redirect_url' => '../organizer/index.php',
        'message'      => 'Firebase Authentication successful! Welcome, ' . htmlspecialchars($name)
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'Firebase session error: ' . $e->getMessage()]);
}
?>
