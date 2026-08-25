<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Set default timezone globally to Indian Standard Time (IST)
date_default_timezone_set('Asia/Kolkata');

// config/db.php

$charset = 'utf8mb4';

// Robust Auto-detect environment (Looks strictly at what is in your browser's address bar or if running via CLI)
$hostHeader = $_SERVER['HTTP_HOST'] ?? '';
$isLocal = php_sapi_name() === 'cli'
           || $hostHeader === 'localhost' 
           || $hostHeader === '127.0.0.1' 
           || str_starts_with($hostHeader, '127.0.0.1:') 
           || str_starts_with($hostHeader, '192.168.');


if ($isLocal) {
    $host = '127.0.0.1';
    $db   = 'smcl_db';
    $user = 'root';
    $pass = '';
} else {
    // Failsafe: Search in 'config/', root 'public_html/', or ONE folder above public_html (Git-Safe & Permanent!)
    $prodFileInConfig = __DIR__ . '/db_prod.php';
    $prodFileInRoot = dirname(__DIR__) . '/db_prod.php';
    $prodFileAboveRoot = dirname(dirname(__DIR__)) . '/db_prod.php';

    // Self-healing scanner: Check all three paths and load the first one that is actually populated!
    $loaded = false;

    if (file_exists($prodFileInConfig)) {
        @include $prodFileInConfig;
        if (isset($host, $db, $user, $pass)) {
            $loaded = true;
        }
    }

    if (!$loaded && file_exists($prodFileInRoot)) {
        @include $prodFileInRoot;
        if (isset($host, $db, $user, $pass)) {
            $loaded = true;
        }
    }

    if (!$loaded && file_exists($prodFileAboveRoot)) {
        @include $prodFileAboveRoot;
        if (isset($host, $db, $user, $pass)) {
            $loaded = true;
        }
    }

    if (!$loaded) {
        $parentDir = dirname(dirname(__DIR__));
        $parentContents = [];
        $permissionStatus = "Readable";

        // Try to scan the parent directory to see what is actually there!
        if (is_dir($parentDir)) {
            $files = @scandir($parentDir);
            if ($files === false) {
                $permissionStatus = "Blocked by server security (open_basedir restriction is active!)";
            } else {
                $parentContents = array_filter($files, function($f) {
                    return $f !== '.' && $f !== '..';
                });
            }
        } else {
            $permissionStatus = "Parent folder not accessible";
        }

        $filesListHTML = "";
        if (!empty($parentContents)) {
            $filesListHTML = "<div style='margin-top: 15px; background: rgba(0,0,0,0.03); padding: 12px; border-radius: 8px; border: 1px solid rgba(0,0,0,0.05);'>
                                <strong style='color: #be123c; font-size: 11px; text-transform: uppercase;'>📁 Live Scan of Parent Folder ($parentDir):</strong>
                                <ul style='font-family: monospace; font-size: 11px; margin: 8px 0 0 0; padding-left: 20px; color: #4b5563;'>";
            foreach ($parentContents as $file) {
                $color = ($file === 'db_prod.php') ? '#047857; font-weight: bold;' : '#374151;';
                $filesListHTML .= "<li style='color: $color;'>$file</li>";
            }
            $filesListHTML .= "</ul></div>";
        } else {
            $filesListHTML = "<div style='margin-top: 15px; color: #991b1b; font-size: 11px; font-weight: bold;'>
                                ⚠️ Parent Folder Scan: Empty or $permissionStatus
                              </div>";
        }

        // Highly descriptive HTML diagnostic block to help you resolve this instantly!
        die("<div style='font-family: sans-serif; max-width: 650px; margin: 40px auto; padding: 25px; border: 1px solid #e11d48; background: #fff1f2; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.05);'>
                <h3 style='color: #be123c; margin-top: 0; font-size: 18px;'>🚨 Secret Credentials File Not Found</h3>
                <p style='color: #4c0519; font-size: 13px; line-height: 1.6;'>
                    The database system is running in Hostinger mode, but it could not locate a valid <strong>db_prod.php</strong> credentials file.
                </p>
                
                <p style='color: #4c0519; font-size: 13px;'>The loader searched these three exact paths:</p>
                <ol style='font-family: monospace; font-size: 11px; background: rgba(0,0,0,0.04); padding: 12px 12px 12px 30px; border-radius: 8px; border: 1px solid rgba(0,0,0,0.03); line-height: 1.7;'>
                    <li>$prodFileInConfig (Wiped on git push)</li>
                    <li>$prodFileInRoot (Wiped on git push)</li>
                    <li style='color: #047857; font-weight: bold;'>$prodFileAboveRoot (PERMANENT & Git-Safe!)</li>
                </ol>
                
                $filesListHTML

                <div style='margin-top: 15px; padding: 12px; background: #fef3c7; border: 1px solid #f59e0b; border-radius: 8px; font-size: 12px; color: #78350f; line-height: 1.5;'>
                    <strong>💡 How to fix this instantly:</strong><br>
                    1. Open your <strong>Hostinger File Manager</strong>.<br>
                    2. Navigate to your main directory <code>ppllive.online</code> (where you can see the <code>public_html</code> folder icon).<br>
                    3. Create a file named exactly <code style='background: rgba(0,0,0,0.06); padding: 2px 4px; border-radius: 4px;'>db_prod.php</code> right next to <code>public_html</code>.<br>
                    4. Save your MySQL details inside it starting with <code>&lt;?php</code>!
                </div>
             </div>");
             
        $host = 'localhost'; 
        $db   = 'your_hostinger_db'; 
        $user = 'your_hostinger_user'; 
        $pass = 'your_hostinger_password'; 
    }
}







$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
     $pdo = new PDO($dsn, $user, $pass, $options);
     
     // Self-healing schema migrations
     try {
         $pdo->exec("CREATE TABLE IF NOT EXISTS users (
             id INT AUTO_INCREMENT PRIMARY KEY,
             google_id VARCHAR(255) NULL UNIQUE,
             name VARCHAR(100) NOT NULL,
             email VARCHAR(150) NOT NULL UNIQUE,
             password VARCHAR(255) NULL,
             auth_provider ENUM('local', 'google') DEFAULT 'local',
             created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
         ) ENGINE=InnoDB;");
     } catch (\PDOException $ex) {}

     try {
         $pdo->exec("CREATE TABLE IF NOT EXISTS tournaments (
             id INT AUTO_INCREMENT PRIMARY KEY,
             organizer_id INT NOT NULL,
             name VARCHAR(150) NOT NULL,
             code VARCHAR(50) NOT NULL UNIQUE,
             logo VARCHAR(255) NULL,
             total_purse_default INT DEFAULT 10000,
             max_squad_size_default INT DEFAULT 11,
             registration_enabled TINYINT(1) DEFAULT 1,
             created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
         ) ENGINE=InnoDB;");
     } catch (\PDOException $ex) {}

     // Auto-alter core tables for tournament_id
     $tablesToMigrate = ['teams', 'players', 'bids', 'auction_state', 'auction_history'];
     foreach ($tablesToMigrate as $tName) {
         try {
             $pdo->exec("ALTER TABLE `$tName` ADD COLUMN tournament_id INT NOT NULL DEFAULT 1");
         } catch (\PDOException $ex) {}
     }

     // Auto-copy uploaded official AuctionWala logo
     try {
         $logoSource = "C:/Users/Nishad/.gemini/antigravity-ide/brain/eafe2e14-cc05-4376-ad3b-909586bddac2/media__1787575464288.png";
         $destDir = __DIR__ . '/../public/uploads/';
         if (!is_dir($destDir)) {
             @mkdir($destDir, 0777, true);
         }
         if (file_exists($logoSource)) {
             @copy($logoSource, $destDir . 'auctionwala_logo.png');
             @copy($logoSource, $destDir . 'league_logo.png');
         }
     } catch (\Exception $ex) {}

     // Ensure default tournament row exists
     try {
         $chk = $pdo->query("SELECT COUNT(*) FROM tournaments WHERE id = 1");
         if ($chk && $chk->fetchColumn() == 0) {
             $userChk = $pdo->query("SELECT id FROM users LIMIT 1");
             $uId = $userChk ? $userChk->fetchColumn() : null;
             if (!$uId) {
                 $pdo->exec("INSERT INTO users (name, email, password, auth_provider) VALUES ('AuctionWala Admin', 'admin@auctionwala.com', '" . password_hash('AuctionWala@Admin#2026_Secure', PASSWORD_BCRYPT) . "', 'local')");
                 $uId = $pdo->lastInsertId();
             }
             $pdo->exec("INSERT INTO tournaments (id, organizer_id, name, code, total_purse_default, max_squad_size_default) VALUES (1, $uId, 'AuctionWala Premier League 2026', 'auctionwala-2026', 10000, 11)");
         } else {
             // Update tournament 1 name to AuctionWala Premier League 2026 if it was SMCL
             $pdo->exec("UPDATE tournaments SET name = 'AuctionWala Premier League 2026', code = 'auctionwala-2026' WHERE id = 1 AND name LIKE '%SMCL%'");
         }
     } catch (\PDOException $ex) {}

     // Ensure default auction_state row exists for tournament 1
     try {
         $chkState = $pdo->query("SELECT COUNT(*) FROM auction_state WHERE tournament_id = 1");
         if ($chkState && $chkState->fetchColumn() == 0) {
             $pdo->exec("INSERT INTO auction_state (tournament_id, status) VALUES (1, 'Idle')");
         }
     } catch (\PDOException $ex) {}

} catch (\PDOException $e) {
     // Gracefully handle database missing (1049) or MySQL connection refused / server stopped (2002)
     $pdo = null;
     $db_error_message = $e->getMessage();
}

/**
 * Helper function to fetch tournament details by code.
 */
function get_tournament_by_code($pdo, $code) {
    if (!$pdo || empty($code)) return null;
    try {
        $stmt = $pdo->prepare("SELECT * FROM tournaments WHERE code = ?");
        $stmt->execute([$code]);
        return $stmt->fetch();
    } catch (Exception $e) {
        return null;
    }
}

/**
 * Helper function to check if user/admin/manager is logged in.
 */
function isLoggedIn() {
    if (session_status() === PHP_SESSION_NONE) {
        @session_start();
    }
    return (isset($_SESSION['user_logged_in']) && $_SESSION['user_logged_in'] === true)
        || (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true)
        || (isset($_SESSION['manager_logged_in']) && $_SESSION['manager_logged_in'] === true);
}

/**
 * Helper function to resolve active tournament ID from session or request parameter.
 */
function get_active_tournament_id($pdo = null, $requestParam = null) {
    if (session_status() === PHP_SESSION_NONE) {
        @session_start();
    }
    
    // 1. Check request parameter if provided or in $_GET / $_POST
    $code = $requestParam ?? $_GET['t'] ?? $_POST['tournament_code'] ?? null;
    if ($code && $pdo) {
        $tourn = get_tournament_by_code($pdo, $code);
        if ($tourn) return (int)$tourn['id'];
    }
    
    // 2. Check explicitly provided numerical ID (supports t_id, tournament_id)
    $tId = $_GET['t_id'] ?? $_GET['tournament_id'] ?? $_POST['tournament_id'] ?? null;
    if ($tId && is_numeric($tId)) {
        return (int)$tId;
    }

    // 3. Fallback to active session
    if (isset($_SESSION['tournament_id']) && $_SESSION['tournament_id'] > 0) {
        return (int)$_SESSION['tournament_id'];
    }

    // 4. Default to tournament 1
    return 1;
}

/**
 * Helper function to categorize all tournaments into Live, Upcoming, and Completed.
 */
function get_categorized_tournaments($pdo) {
    $result = [
        'live' => [],
        'upcoming' => [],
        'completed' => [],
        'all' => []
    ];
    if (!$pdo) return $result;

    try {
        $stmt = $pdo->query("
            SELECT 
                t.*,
                COALESCE(ast.status, 'Idle') as auction_status,
                ast.current_player_id,
                ast.current_bid_amount,
                (SELECT COUNT(*) FROM teams tm WHERE tm.tournament_id = t.id) as team_count,
                (SELECT COUNT(*) FROM players pl WHERE pl.tournament_id = t.id AND pl.payment_status = 'Verified') as player_count,
                (SELECT COUNT(*) FROM players pl WHERE pl.tournament_id = t.id AND pl.auction_status = 'Sold') as sold_player_count
            FROM tournaments t
            LEFT JOIN auction_state ast ON ast.tournament_id = t.id
            ORDER BY t.id DESC
        ");
        $allTournaments = $stmt->fetchAll();
        $result['all'] = $allTournaments;

        foreach ($allTournaments as $t) {
            $status = $t['auction_status'];
            $soldCount = (int)$t['sold_player_count'];
            $totalCount = (int)$t['player_count'];

            if ($status === 'Bidding' || $status === 'Paused') {
                $result['live'][] = $t;
            } elseif ($totalCount > 0 && $soldCount >= $totalCount) {
                $result['completed'][] = $t;
            } else {
                $result['upcoming'][] = $t;
            }
        }
    } catch (Exception $e) {}

    return $result;
}
?>
