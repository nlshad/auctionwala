<?php
// api/place_bid.php
require_once '../config/db.php';
require_once '../config/auction_history.php';
header('Content-Type: application/json');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Check database connection
    if (!$pdo) {
        echo json_encode(['success' => false, 'error' => 'Database connection offline.']);
        exit;
    }

    $playerId = isset($_POST['player_id']) ? (int)$_POST['player_id'] : 0;
    $teamId = isset($_POST['team_id']) ? (int)$_POST['team_id'] : 0;
    $bidAmount = isset($_POST['bid_amount']) ? (int)$_POST['bid_amount'] : 0;

    session_start();
    $tournamentId = get_active_tournament_id($pdo);

    // Verify authorized actor (either admin/organizer or the specific manager for this team)
    $isAuthorized = false;
    if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
        $isAuthorized = true;
    } elseif (isset($_SESSION['user_logged_in']) && $_SESSION['user_logged_in'] === true) {
        $isAuthorized = true;
    } elseif (isset($_SESSION['manager_logged_in']) && $_SESSION['manager_logged_in'] === true && (int)$_SESSION['team_id'] === $teamId) {
        $isAuthorized = true;
    }

    if (!$isAuthorized) {
        echo json_encode(['success' => false, 'error' => 'Unauthorized action. Bidding blocked.']);
        exit;
    }

    if ($playerId === 0 || $teamId === 0 || $bidAmount === 0) {
        echo json_encode(['success' => false, 'error' => 'Invalid bid parameters.']);
        exit;
    }

    if ($bidAmount % 100 !== 0) {
        echo json_encode(['success' => false, 'error' => 'Bid amount must be a multiple of 100.']);
        exit;
    }

    try {
        // Start a database transaction for concurrency control
        $pdo->beginTransaction();

        // 1. Verify that the global auction state for this tournament is active and matches this player
        $stmt = $pdo->prepare("SELECT current_player_id, current_bid_amount, current_highest_bidder_id, status FROM auction_state WHERE tournament_id = :t_id FOR UPDATE");
        $stmt->execute(['t_id' => $tournamentId]);
        $state = $stmt->fetch();

        if (!$state || $state['status'] !== 'Bidding') {
            echo json_encode(['success' => false, 'error' => 'Bidding is not open for this player.']);
            $pdo->rollBack();
            exit;
        }

        if ((int)$state['current_player_id'] !== $playerId) {
            echo json_encode(['success' => false, 'error' => 'This player is not currently on the block.']);
            $pdo->rollBack();
            exit;
        }

        // 2. Prevent team from outbidding itself
        if ((int)$state['current_highest_bidder_id'] === $teamId) {
            echo json_encode(['success' => false, 'error' => 'Your team already holds the highest bid!']);
            $pdo->rollBack();
            exit;
        }

        // 3. Retrieve Team Data (Check remaining purse and squad limits for this tournament)
        $stmt = $pdo->prepare("SELECT team_name, remaining_purse, current_squad_size, max_squad_size FROM teams WHERE id = :team_id AND tournament_id = :t_id FOR UPDATE");
        $stmt->execute(['team_id' => $teamId, 't_id' => $tournamentId]);
        $team = $stmt->fetch();

        if (!$team) {
            echo json_encode(['success' => false, 'error' => 'Franchise team not found in this tournament.']);
            $pdo->rollBack();
            exit;
        }

        if ($team['current_squad_size'] >= $team['max_squad_size']) {
            echo json_encode(['success' => false, 'error' => "Squad full! Max squad limit is {$team['max_squad_size']} players."]);
            $pdo->rollBack();
            exit;
        }

        if ($bidAmount > $team['remaining_purse']) {
            echo json_encode(['success' => false, 'error' => "Insufficient funds. Remaining purse: ₹{$team['remaining_purse']}."]);
            $pdo->rollBack();
            exit;
        }

        // 4. Verify that this bid is higher than current bid
        $currentHighest = (int)$state['current_bid_amount'];
        $isFirstBid = ($state['current_highest_bidder_id'] === null);

        if ($isFirstBid) {
            if ($bidAmount < $currentHighest) {
                echo json_encode(['success' => false, 'error' => "Opening bid must be at least the base price of ₹{$currentHighest}."]);
                $pdo->rollBack();
                exit;
            }
        } else {
            if ($bidAmount <= $currentHighest) {
                echo json_encode(['success' => false, 'error' => "Bid must be higher than the current bid of ₹{$currentHighest}."]);
                $pdo->rollBack();
                exit;
            }
        }

        // Double check against bids table max
        $stmt = $pdo->prepare("SELECT MAX(bid_amount) as max_bid FROM bids WHERE player_id = :player_id AND tournament_id = :t_id");
        $stmt->execute(['player_id' => $playerId, 't_id' => $tournamentId]);
        $dbMaxRow = $stmt->fetch();
        $dbMax = $dbMaxRow['max_bid'] !== null ? (int)$dbMaxRow['max_bid'] : null;

        if ($dbMax !== null) {
            if ($bidAmount <= $dbMax) {
                echo json_encode(['success' => false, 'error' => "A higher bid of ₹{$dbMax} was already submitted."]);
                $pdo->rollBack();
                exit;
            }
        }

        // 5. Insert bid log row
        $stmt = $pdo->prepare("INSERT INTO bids (tournament_id, player_id, team_id, bid_amount) VALUES (:t_id, :player_id, :team_id, :bid_amount)");
        $stmt->execute([
            't_id'       => $tournamentId,
            'player_id'  => $playerId,
            'team_id'    => $teamId,
            'bid_amount' => $bidAmount
        ]);

        // 6. Update the central auction state row
        $stmt = $pdo->prepare("UPDATE auction_state SET current_bid_amount = :bid_amount, current_highest_bidder_id = :team_id, last_update = CURRENT_TIMESTAMP WHERE tournament_id = :t_id");
        $stmt->execute([
            'bid_amount' => $bidAmount,
            'team_id'    => $teamId,
            't_id'       => $tournamentId
        ]);

        // Commit transaction
        $pdo->commit();
        
        // Record step in history
        record_auction_history_step($pdo, 'bid', $tournamentId);
        
        echo json_encode(['success' => true]);

    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Invalid request method.']);
}
?>
