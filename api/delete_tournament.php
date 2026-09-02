<?php
// api/delete_tournament.php
session_start();
require_once '../config/db.php';
header('Content-Type: application/json');

if (!$pdo) {
    echo json_encode(['success' => false, 'error' => 'Database connection offline.']);
    exit;
}

// Check organizer / admin session
if (!isset($_SESSION['user_logged_in']) && !isset($_SESSION['admin_logged_in'])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized access. Please log in as an Organizer or Admin.']);
    exit;
}

$userId = (int)($_SESSION['user_id'] ?? 1);
$isAdmin = isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;

$tournamentId = isset($_POST['tournament_id']) ? (int)$_POST['tournament_id'] : 0;
if ($tournamentId <= 0 && isset($_GET['tournament_id'])) {
    $tournamentId = (int)$_GET['tournament_id'];
}

if ($tournamentId <= 0) {
    echo json_encode(['success' => false, 'error' => 'Invalid tournament ID provided.']);
    exit;
}

try {
    // 1. Verify ownership
    $stmt = $pdo->prepare("SELECT * FROM tournaments WHERE id = ?");
    $stmt->execute([$tournamentId]);
    $tourn = $stmt->fetch();

    if (!$tourn) {
        echo json_encode(['success' => false, 'error' => 'Tournament not found.']);
        exit;
    }

    if (!$isAdmin && (int)$tourn['organizer_id'] !== $userId) {
        echo json_encode(['success' => false, 'error' => 'Permission denied. You can only delete leagues you created.']);
        exit;
    }

    // 2. Prevent deleting default tournament if it's the only one left
    $countStmt = $pdo->query("SELECT COUNT(*) FROM tournaments");
    $totalTournaments = (int)$countStmt->fetchColumn();
    if ($totalTournaments <= 1) {
        echo json_encode(['success' => false, 'error' => 'Cannot delete the only remaining league environment. Please create another league first.']);
        exit;
    }

    $pdo->beginTransaction();

    // Cascade delete related records
    // a. Bids
    $stmt = $pdo->prepare("DELETE FROM bids WHERE tournament_id = ?");
    $stmt->execute([$tournamentId]);

    // b. Players
    $stmt = $pdo->prepare("DELETE FROM players WHERE tournament_id = ?");
    $stmt->execute([$tournamentId]);

    // c. Teams
    $stmt = $pdo->prepare("DELETE FROM teams WHERE tournament_id = ?");
    $stmt->execute([$tournamentId]);

    // d. Auction State
    $stmt = $pdo->prepare("DELETE FROM auction_state WHERE tournament_id = ?");
    $stmt->execute([$tournamentId]);

    // e. Auction History (if table exists)
    try {
        $stmt = $pdo->prepare("DELETE FROM auction_history WHERE tournament_id = ?");
        $stmt->execute([$tournamentId]);
    } catch (Exception $eHistory) {}

    // f. Tournament itself
    $stmt = $pdo->prepare("DELETE FROM tournaments WHERE id = ?");
    $stmt->execute([$tournamentId]);

    $pdo->commit();

    // If active session tournament was deleted, switch session to another tournament
    if (isset($_SESSION['tournament_id']) && (int)$_SESSION['tournament_id'] === $tournamentId) {
        $nextStmt = $pdo->query("SELECT id, code FROM tournaments ORDER BY id DESC LIMIT 1");
        $nextTourn = $nextStmt->fetch();
        if ($nextTourn) {
            $_SESSION['tournament_id'] = (int)$nextTourn['id'];
            $_SESSION['tournament_code'] = $nextTourn['code'];
        } else {
            unset($_SESSION['tournament_id']);
            unset($_SESSION['tournament_code']);
        }
    }

    echo json_encode([
        'success' => true,
        'message' => "League '{$tourn['name']}' and all associated teams, players, and bidding history have been permanently deleted."
    ]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo json_encode(['success' => false, 'error' => 'Server database error: ' . $e->getMessage()]);
}
?>
