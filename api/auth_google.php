<?php
// api/auth_google.php
session_start();
require_once '../config/db.php';
header('Content-Type: application/json');

if (!$pdo) {
    echo json_encode(['success' => false, 'error' => 'Database connection offline.']);
    exit;
}

// Support JSON POST or standard POST
$inputData = json_decode(file_get_contents('php://input'), true) ?? $_POST;

$googleId = trim($inputData['google_id'] ?? $inputData['sub'] ?? '');
$email    = trim($inputData['email'] ?? '');
$name     = trim($inputData['name'] ?? '');

if (empty($email)) {
    echo json_encode(['success' => false, 'error' => 'Invalid Google authentication payload. Email missing.']);
    exit;
}

if (empty($name)) {
    $name = explode('@', $email)[0];
}

try {
    // Check if user exists by google_id or email
    $stmt = $pdo->prepare("SELECT id, name, email FROM users WHERE google_id = :google_id OR email = :email");
    $stmt->execute(['google_id' => $googleId, 'email' => $email]);
    $user = $stmt->fetch();

    if ($user) {
        $userId = (int)$user['id'];
        // Update google_id if missing
        if ($googleId) {
            $up = $pdo->prepare("UPDATE users SET google_id = :google_id, auth_provider = 'google' WHERE id = :id");
            $up->execute(['google_id' => $googleId, 'id' => $userId]);
        }
    } else {
        // Insert new user
        $stmt = $pdo->prepare("INSERT INTO users (google_id, name, email, auth_provider) VALUES (:google_id, :name, :email, 'google')");
        $stmt->execute([
            'google_id' => $googleId,
            'name'      => $name,
            'email'     => $email
        ]);
        $userId = (int)$pdo->lastInsertId();
    }

    // Set Session variables
    $_SESSION['user_logged_in'] = true;
    $_SESSION['user_id']        = $userId;
    $_SESSION['user_name']      = $name;
    $_SESSION['user_email']     = $email;
    $_SESSION['role']           = 'Organizer';
    $_SESSION['admin_logged_in']= true; // Superadmin access for their own tournaments

    // Resolve or create an initial tournament for this organizer if none exists
    $stmt = $pdo->prepare("SELECT id, code FROM tournaments WHERE organizer_id = :organizer_id ORDER BY id ASC LIMIT 1");
    $stmt->execute(['organizer_id' => $userId]);
    $tournament = $stmt->fetch();

    if (!$tournament) {
        // Auto-create initial default tournament for organizer
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
        'message'      => 'Google Authentication successful! Welcome, ' . htmlspecialchars($name)
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'Authentication server error: ' . $e->getMessage()]);
}
?>
