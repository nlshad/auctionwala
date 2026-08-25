<?php
// api/admin_control.php
session_start();
require_once '../config/db.php';
require_once '../config/auction_history.php';
header('Content-Type: application/json');

// Check database connection
if (!$pdo) {
    echo json_encode(['success' => false, 'error' => 'Database connection offline.']);
    exit;
}

// Enforce admin/organizer login check for API security
if (!isset($_SESSION['admin_logged_in']) && !isset($_SESSION['user_logged_in'])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized access. Session expired or admin not logged in.']);
    exit;
}

$tournamentId = get_active_tournament_id($pdo);
$action = isset($_POST['action']) ? $_POST['action'] : '';

try {
    switch ($action) {
        case 'start':
            $playerId = isset($_POST['player_id']) ? (int)$_POST['player_id'] : 0;
            $basePrice = isset($_POST['base_price']) ? (int)$_POST['base_price'] : 100;

            if ($playerId === 0) {
                echo json_encode(['success' => false, 'error' => 'Invalid Player Selected.']);
                exit;
            }

            if ($basePrice % 100 !== 0) {
                echo json_encode(['success' => false, 'error' => 'Starting base price must be a multiple of 100.']);
                exit;
            }

            // Verify player is Verified and Available in this tournament
            $stmt = $pdo->prepare("SELECT id, name, payment_status, auction_status FROM players WHERE id = :player_id AND tournament_id = :t_id");
            $stmt->execute(['player_id' => $playerId, 't_id' => $tournamentId]);
            $player = $stmt->fetch();

            if (!$player) {
                echo json_encode(['success' => false, 'error' => 'Player not found in this tournament.']);
                exit;
            }
            if ($player['payment_status'] !== 'Verified') {
                echo json_encode(['success' => false, 'error' => 'Player payment is not verified.']);
                exit;
            }
            if ($player['auction_status'] === 'Sold') {
                echo json_encode(['success' => false, 'error' => 'Player already sold.']);
                exit;
            }

            $pdo->beginTransaction();

            // Clear old bids for this player in this tournament
            $stmt = $pdo->prepare("DELETE FROM bids WHERE player_id = :player_id AND tournament_id = :t_id");
            $stmt->execute(['player_id' => $playerId, 't_id' => $tournamentId]);

            // Update player base price and state
            $stmt = $pdo->prepare("UPDATE players SET base_price = :base_price, auction_status = 'Bidding' WHERE id = :player_id AND tournament_id = :t_id");
            $stmt->execute([
                'base_price' => $basePrice,
                'player_id'  => $playerId,
                't_id'       => $tournamentId
            ]);

            // Set tournament auction state to Bidding
            $stmt = $pdo->prepare("UPDATE auction_state SET current_player_id = :player_id, current_bid_amount = :base_price, current_highest_bidder_id = NULL, status = 'Bidding', last_update = CURRENT_TIMESTAMP WHERE tournament_id = :t_id");
            $stmt->execute([
                'player_id' => $playerId,
                'base_price' => $basePrice,
                't_id'       => $tournamentId
            ]);

            $pdo->commit();
            record_auction_history_step($pdo, 'start', $tournamentId);
            
            echo json_encode(['success' => true, 'message' => "Bidding started for {$player['name']} at ₹{$basePrice}."]);
            break;

        case 'sold':
            // Finalize current bidding player as SOLD
            $pdo->beginTransaction();

            // Fetch current state
            $stmt = $pdo->prepare("SELECT current_player_id, current_bid_amount, current_highest_bidder_id FROM auction_state WHERE tournament_id = :t_id FOR UPDATE");
            $stmt->execute(['t_id' => $tournamentId]);
            $state = $stmt->fetch();

            if (!$state || !$state['current_player_id']) {
                echo json_encode(['success' => false, 'error' => 'No active player is on the auction block.']);
                $pdo->rollBack();
                exit;
            }

            $playerId = (int)$state['current_player_id'];
            $bidAmount = (int)$state['current_bid_amount'];
            $teamId = $state['current_highest_bidder_id'] ? (int)$state['current_highest_bidder_id'] : null;

            if (!$teamId) {
                echo json_encode(['success' => false, 'error' => 'No bids have been placed yet. Cannot sell.']);
                $pdo->rollBack();
                exit;
            }

            // Verify team exists, has enough purse and space
            $stmt = $pdo->prepare("SELECT team_name, remaining_purse, current_squad_size, max_squad_size FROM teams WHERE id = :team_id AND tournament_id = :t_id FOR UPDATE");
            $stmt->execute(['team_id' => $teamId, 't_id' => $tournamentId]);
            $team = $stmt->fetch();

            if (!$team) {
                echo json_encode(['success' => false, 'error' => 'Winning team not found in database.']);
                $pdo->rollBack();
                exit;
            }

            if ($team['current_squad_size'] >= $team['max_squad_size']) {
                echo json_encode(['success' => false, 'error' => "Winning team's squad is already full!"]);
                $pdo->rollBack();
                exit;
            }

            if ($bidAmount > $team['remaining_purse']) {
                echo json_encode(['success' => false, 'error' => "Winning team has insufficient purse remaining (₹{$team['remaining_purse']}) for this bid."]);
                $pdo->rollBack();
                exit;
            }

            // 1. Update Player table: sold price, team_id, and mark as Sold
            $stmt = $pdo->prepare("UPDATE players SET sold_price = :sold_price, team_id = :team_id, auction_status = 'Sold' WHERE id = :player_id AND tournament_id = :t_id");
            $stmt->execute([
                'sold_price' => $bidAmount,
                'team_id'    => $teamId,
                'player_id'  => $playerId,
                't_id'       => $tournamentId
            ]);

            // 2. Deduct Purse & Increment Squad Size in Teams table
            $stmt = $pdo->prepare("UPDATE teams SET remaining_purse = remaining_purse - :bid_amount, current_squad_size = current_squad_size + 1 WHERE id = :team_id AND tournament_id = :t_id");
            $stmt->execute([
                'bid_amount' => $bidAmount,
                'team_id'    => $teamId,
                't_id'       => $tournamentId
            ]);

            // 3. Reset Global Auction State for tournament
            $stmt = $pdo->prepare("UPDATE auction_state SET current_player_id = NULL, current_bid_amount = 0, current_highest_bidder_id = NULL, status = 'Idle', last_update = CURRENT_TIMESTAMP WHERE tournament_id = :t_id");
            $stmt->execute(['t_id' => $tournamentId]);

            $pdo->commit();
            record_auction_history_step($pdo, 'sold', $tournamentId);

            echo json_encode(['success' => true, 'message' => "Player sold successfully to {$team['team_name']} for ₹{$bidAmount}!"]);
            break;

        case 'unsold':
            // Finalize current bidding player as UNSOLD
            $pdo->beginTransaction();

            $stmt = $pdo->prepare("SELECT current_player_id FROM auction_state WHERE tournament_id = :t_id FOR UPDATE");
            $stmt->execute(['t_id' => $tournamentId]);
            $state = $stmt->fetch();

            if (!$state || !$state['current_player_id']) {
                echo json_encode(['success' => false, 'error' => 'No active player is on the auction block.']);
                $pdo->rollBack();
                exit;
            }

            $playerId = (int)$state['current_player_id'];

            // 1. Update Player Table to Unsold
            $stmt = $pdo->prepare("UPDATE players SET auction_status = 'Unsold' WHERE id = :player_id AND tournament_id = :t_id");
            $stmt->execute(['player_id' => $playerId, 't_id' => $tournamentId]);

            // 2. Reset Auction State
            $stmt = $pdo->prepare("UPDATE auction_state SET current_player_id = NULL, current_bid_amount = 0, current_highest_bidder_id = NULL, status = 'Idle', last_update = CURRENT_TIMESTAMP WHERE tournament_id = :t_id");
            $stmt->execute(['t_id' => $tournamentId]);

            $pdo->commit();
            record_auction_history_step($pdo, 'unsold', $tournamentId);

            echo json_encode(['success' => true, 'message' => 'Player marked as Unsold. Returning to pool.']);
            break;

        case 'toggle_pause':
            // Toggle bidding status between Bidding and Paused
            $pdo->beginTransaction();

            $stmt = $pdo->prepare("SELECT status FROM auction_state WHERE tournament_id = :t_id FOR UPDATE");
            $stmt->execute(['t_id' => $tournamentId]);
            $currentStatus = $stmt->fetchColumn();

            if ($currentStatus === 'Idle') {
                echo json_encode(['success' => false, 'error' => 'Auction is currently idle. Bring a player to start.']);
                $pdo->rollBack();
                exit;
            }

            $newStatus = ($currentStatus === 'Bidding') ? 'Paused' : 'Bidding';

            $stmt = $pdo->prepare("UPDATE auction_state SET status = :status, last_update = CURRENT_TIMESTAMP WHERE tournament_id = :t_id");
            $stmt->execute(['status' => $newStatus, 't_id' => $tournamentId]);

            $pdo->commit();
            record_auction_history_step($pdo, 'toggle_pause', $tournamentId);

            echo json_encode(['success' => true, 'status' => $newStatus, 'message' => "Auction has been " . ($newStatus === 'Paused' ? 'PAUSED' : 'RESUMED') . "."]);
            break;

        case 'undo':
            $pointer = ensure_history_baseline($pdo, $tournamentId);

            // Find previous state in history
            $stmt = $pdo->prepare("SELECT id, state_snapshot FROM auction_history WHERE tournament_id = ? AND id < ? ORDER BY id DESC LIMIT 1");
            $stmt->execute([$tournamentId, $pointer]);
            $prevStep = $stmt->fetch();

            if (!$prevStep) {
                echo json_encode(['success' => false, 'error' => 'No more actions to undo.']);
                exit;
            }

            // Restore state
            $prevSnapshot = json_decode($prevStep['state_snapshot'], true);
            restore_auction_state_snapshot($pdo, $prevSnapshot, $tournamentId);

            // Update history pointer
            $stmt = $pdo->prepare("UPDATE auction_state SET history_pointer = ? WHERE tournament_id = ?");
            $stmt->execute([$prevStep['id'], $tournamentId]);

            echo json_encode(['success' => true, 'message' => 'Action undone successfully.']);
            break;

        case 'redo':
            $pointer = ensure_history_baseline($pdo, $tournamentId);

            // Find next state in history
            $stmt = $pdo->prepare("SELECT id, state_snapshot FROM auction_history WHERE tournament_id = ? AND id > ? ORDER BY id ASC LIMIT 1");
            $stmt->execute([$tournamentId, $pointer]);
            $nextStep = $stmt->fetch();

            if (!$nextStep) {
                echo json_encode(['success' => false, 'error' => 'No more actions to redo.']);
                exit;
            }

            // Restore state
            $nextSnapshot = json_decode($nextStep['state_snapshot'], true);
            restore_auction_state_snapshot($pdo, $nextSnapshot, $tournamentId);

            // Update history pointer
            $stmt = $pdo->prepare("UPDATE auction_state SET history_pointer = ? WHERE tournament_id = ?");
            $stmt->execute([$nextStep['id'], $tournamentId]);

            echo json_encode(['success' => true, 'message' => 'Action redone successfully.']);
            break;

        default:
            echo json_encode(['success' => false, 'error' => 'Invalid action requested.']);
            break;
    }
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo json_encode(['success' => false, 'error' => 'Server database error: ' . $e->getMessage()]);
}
?>
