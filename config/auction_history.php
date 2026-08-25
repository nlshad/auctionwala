<?php
// config/auction_history.php
require_once __DIR__ . '/db.php';

/**
 * Capture a complete snapshot of the auction database state for a specific tournament.
 */
function get_current_auction_state_snapshot($pdo, $tournamentId = 1) {
    // 1. Get auction_state row for tournament
    $stmt = $pdo->prepare("SELECT current_player_id, current_bid_amount, current_highest_bidder_id, status FROM auction_state WHERE tournament_id = ?");
    $stmt->execute([$tournamentId]);
    $auctionState = $stmt->fetch();

    // 2. Get all players state for tournament
    $stmt = $pdo->prepare("SELECT id, team_id, auction_status, sold_price FROM players WHERE tournament_id = ?");
    $stmt->execute([$tournamentId]);
    $playersState = $stmt->fetchAll();

    // 3. Get all bids state for tournament
    $stmt = $pdo->prepare("SELECT id, player_id, team_id, bid_amount, created_at FROM bids WHERE tournament_id = ?");
    $stmt->execute([$tournamentId]);
    $bidsState = $stmt->fetchAll();

    // 4. Get all teams state for tournament
    $stmt = $pdo->prepare("SELECT id, remaining_purse, current_squad_size FROM teams WHERE tournament_id = ?");
    $stmt->execute([$tournamentId]);
    $teamsState = $stmt->fetchAll();

    return [
        'auction_state' => $auctionState,
        'players_state' => $playersState,
        'bids_state' => $bidsState,
        'teams_state' => $teamsState
    ];
}

/**
 * Restore database state from a snapshot for a specific tournament.
 */
function restore_auction_state_snapshot($pdo, $snapshot, $tournamentId = 1) {
    // 1. Restore auction_state
    $as = $snapshot['auction_state'];
    if ($as) {
        $stmt = $pdo->prepare("UPDATE auction_state SET current_player_id = ?, current_bid_amount = ?, current_highest_bidder_id = ?, status = ?, last_update = CURRENT_TIMESTAMP WHERE tournament_id = ?");
        $stmt->execute([
            $as['current_player_id'],
            $as['current_bid_amount'],
            $as['current_highest_bidder_id'],
            $as['status'],
            $tournamentId
        ]);
    }

    // 2. Restore players state
    foreach ($snapshot['players_state'] as $ps) {
        $stmt = $pdo->prepare("UPDATE players SET team_id = ?, auction_status = ?, sold_price = ? WHERE id = ? AND tournament_id = ?");
        $stmt->execute([
            $ps['team_id'],
            $ps['auction_status'],
            $ps['sold_price'],
            $ps['id'],
            $tournamentId
        ]);
    }

    // 3. Restore teams state
    foreach ($snapshot['teams_state'] as $ts) {
        $stmt = $pdo->prepare("UPDATE teams SET remaining_purse = ?, current_squad_size = ? WHERE id = ? AND tournament_id = ?");
        $stmt->execute([
            $ts['remaining_purse'],
            $ts['current_squad_size'],
            $ts['id'],
            $tournamentId
        ]);
    }

    // 4. Restore bids list
    $stmt = $pdo->prepare("DELETE FROM bids WHERE tournament_id = ?");
    $stmt->execute([$tournamentId]);
    
    foreach ($snapshot['bids_state'] as $bid) {
        $stmt = $pdo->prepare("INSERT INTO bids (id, tournament_id, player_id, team_id, bid_amount, created_at) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $bid['id'],
            $tournamentId,
            $bid['player_id'],
            $bid['team_id'],
            $bid['bid_amount'],
            $bid['created_at']
        ]);
    }
}

/**
 * Initialize history baseline if empty.
 */
function ensure_history_baseline($pdo, $tournamentId = 1) {
    // Check history pointer
    $stmt = $pdo->prepare("SELECT history_pointer FROM auction_state WHERE tournament_id = ?");
    $stmt->execute([$tournamentId]);
    $pointer = (int)$stmt->fetchColumn();

    if ($pointer === 0) {
        // Capture baseline state (Idle)
        $snapshot = get_current_auction_state_snapshot($pdo, $tournamentId);
        $snapshotJson = json_encode($snapshot);

        // Delete any existing history for this tournament
        $stmt = $pdo->prepare("DELETE FROM auction_history WHERE tournament_id = ?");
        $stmt->execute([$tournamentId]);

        // Insert baseline
        $stmt = $pdo->prepare("INSERT INTO auction_history (tournament_id, state_snapshot, action_type) VALUES (?, ?, 'baseline')");
        $stmt->execute([$tournamentId, $snapshotJson]);
        $baselineId = $pdo->lastInsertId();

        // Update pointer
        $stmt = $pdo->prepare("UPDATE auction_state SET history_pointer = ? WHERE tournament_id = ?");
        $stmt->execute([$baselineId, $tournamentId]);

        return $baselineId;
    }
    return $pointer;
}

/**
 * Record a state change in history (truncating redo branch).
 */
function record_auction_history_step($pdo, $actionType, $tournamentId = 1) {
    // 1. Ensure baseline exists
    $pointer = ensure_history_baseline($pdo, $tournamentId);

    // 2. Delete any future redo steps for this tournament
    $stmt = $pdo->prepare("DELETE FROM auction_history WHERE tournament_id = ? AND id > ?");
    $stmt->execute([$tournamentId, $pointer]);

    // 3. Capture and save new state snapshot
    $snapshot = get_current_auction_state_snapshot($pdo, $tournamentId);
    $snapshotJson = json_encode($snapshot);

    $stmt = $pdo->prepare("INSERT INTO auction_history (tournament_id, state_snapshot, action_type) VALUES (?, ?, ?)");
    $stmt->execute([$tournamentId, $snapshotJson, $actionType]);
    $newPointer = $pdo->lastInsertId();

    // 4. Update pointer
    $stmt = $pdo->prepare("UPDATE auction_state SET history_pointer = ? WHERE tournament_id = ?");
    $stmt->execute([$newPointer, $tournamentId]);

    return $newPointer;
}
?>
