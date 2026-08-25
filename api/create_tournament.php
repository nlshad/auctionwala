<?php
// api/create_tournament.php
session_start();
require_once '../config/db.php';
require_once '../config/auction_history.php';
header('Content-Type: application/json');

if (!$pdo) {
    echo json_encode(['success' => false, 'error' => 'Database connection offline.']);
    exit;
}

// Check organizer session
if (!isset($_SESSION['user_id']) && !isset($_SESSION['admin_logged_in'])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized access. Please log in as an Organizer.']);
    exit;
}

$organizerId = (int)($_SESSION['user_id'] ?? 1);
$name = trim($_POST['name'] ?? '');
$code = trim($_POST['code'] ?? '');
$totalPurse = isset($_POST['total_purse']) ? (int)$_POST['total_purse'] : 10000;
$maxSquadSize = isset($_POST['max_squad_size']) ? (int)$_POST['max_squad_size'] : 11;

if (empty($name)) {
    echo json_encode(['success' => false, 'error' => 'Tournament name is required.']);
    exit;
}

// Generate code if empty
if (empty($code)) {
    $code = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $name)) . '-' . rand(100, 999);
} else {
    $code = strtolower(preg_replace('/[^a-zA-Z0-9\-]/', '', $code));
}

try {
    // Check if code already exists
    $stmt = $pdo->prepare("SELECT id FROM tournaments WHERE code = ?");
    $stmt->execute([$code]);
    if ($stmt->fetch()) {
        echo json_encode(['success' => false, 'error' => 'Tournament code already exists. Please choose another identifier.']);
        exit;
    }

    $pdo->beginTransaction();

    // 1. Create Tournament
    $stmt = $pdo->prepare("INSERT INTO tournaments (organizer_id, name, code, total_purse_default, max_squad_size_default) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$organizerId, $name, $code, $totalPurse, $maxSquadSize]);
    $tId = (int)$pdo->lastInsertId();

    // 2. Create Auction State row
    $stmt = $pdo->prepare("INSERT INTO auction_state (tournament_id, status) VALUES (?, 'Idle')");
    $stmt->execute([$tId]);

    $pdo->commit();

    // 3. Ensure baseline history
    ensure_history_baseline($pdo, $tId);

    // Switch session to newly created tournament
    $_SESSION['tournament_id'] = $tId;
    $_SESSION['tournament_code'] = $code;

    echo json_encode([
        'success' => true,
        'tournament_id' => $tId,
        'tournament_code' => $code,
        'message' => "Tournament '{$name}' created successfully!"
    ]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo json_encode(['success' => false, 'error' => 'Server database error: ' . $e->getMessage()]);
}
?>
