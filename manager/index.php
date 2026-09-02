<?php
// manager/index.php
session_start();
require_once '../config/db.php';

// Auth Protection
if (!isset($_SESSION['manager_logged_in']) || $_SESSION['manager_logged_in'] !== true) {
    header("Location: ../public/login.php");
    exit;
}

// Self-healing uploads path checker
$uploadPath = is_dir('../public/uploads') ? '../public/uploads/' : (is_dir('uploads') ? 'uploads/' : '../uploads/');

$teamId = (int)$_SESSION['team_id'];
$managerUser = $_SESSION['manager_username'];
$tournamentId = get_active_tournament_id($pdo);

try {
    // Fetch latest manager team data for active tournament
    $stmt = $pdo->prepare("SELECT * FROM teams WHERE id = :id AND tournament_id = :t_id");
    $stmt->execute(['id' => $teamId, 't_id' => $tournamentId]);
    $team = $stmt->fetch();
    
    if (!$team) {
        // Fallback search without t_id lock if session was transferred
        $stmt = $pdo->prepare("SELECT * FROM teams WHERE id = :id");
        $stmt->execute(['id' => $teamId]);
        $team = $stmt->fetch();
        if ($team) {
            $_SESSION['tournament_id'] = (int)$team['tournament_id'];
        } else {
            session_destroy();
            header("Location: ../public/login.php");
            exit;
        }
    } else {
        $_SESSION['tournament_id'] = (int)$team['tournament_id'];
    }

    // Fetch verified sold or unsold players
    $stmt = $pdo->prepare("SELECT p.id, p.name, p.mobile, p.place, p.role, p.profile_image, p.base_price, p.sold_price, p.auction_status, t.team_name, t.logo as team_logo 
                           FROM players p 
                           LEFT JOIN teams t ON p.team_id = t.id 
                           WHERE p.payment_status = 'Verified' AND p.auction_status IN ('Sold', 'Unsold', 'Available') 
                           ORDER BY p.id DESC");
    $stmt->execute();
    $completedPool = $stmt->fetchAll();
} catch (Exception $e) {
    die("Database Connection Error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AuctionWala — Franchise Bidding Dashboard</title>
    <?php require_once '../public/components/ui_head.php'; ?>
    <style>
        body {
            background-color: #0b1326;
            color: #dae2fd;
            font-family: 'Inter', sans-serif;
            position: relative;
            min-height: 100vh;
        }

        /* Fixed stadium background layer matching pro-broadcast landing theme */
        body::before {
            content: "";
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(180deg, rgba(6, 14, 32, 0.8) 0%, rgba(11, 19, 38, 0.95) 100%), url('../public/uploads/app_bg.jpg') center/cover no-repeat;
            z-index: -2;
            pointer-events: none;
        }

        .pro-card {
            background: rgba(19, 27, 46, 0.90);
            backdrop-filter: blur(16px);
            border: 1px solid #1e293d;
            transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .bid-btn {
            transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .bid-btn:not(:disabled):hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(255, 84, 81, 0.35);
        }
        .bid-btn:not(:disabled):active {
            transform: translateY(1px);
        }
    </style>
</head>
<body class="min-h-screen flex flex-col justify-between selection:bg-red-500 selection:text-white">

    <!-- Toast Notification Overlay -->
    <div id="toast-container" class="fixed top-6 right-6 z-50 space-y-3 pointer-events-none max-w-sm w-full"></div>

    <!-- Header Navigation -->
    <header class="w-full bg-[#060e20]/95 backdrop-blur-md border-b border-[#1e293d] px-4 py-3 sm:px-8 flex items-center justify-between sticky top-0 z-40 shadow-xl">
        <div class="flex items-center gap-3">
            <a href="../public/landing.php" class="flex items-center gap-2">
                <img src="<?php echo $uploadPath; ?>auctionwala_logo.png" alt="AuctionWala Logo" class="h-8 sm:h-9 object-contain">
            </a>
            <div class="h-6 w-px bg-[#1e293d] hidden sm:block"></div>
            <div class="flex items-center gap-2">
                <img src="<?php echo $uploadPath; ?><?php echo htmlspecialchars($team['logo'] ? $team['logo'] : 'team_placeholder.jpg'); ?>" alt="Team Logo" class="w-7 h-7 sm:w-8 sm:h-8 object-contain bg-[#060e20] p-0.5 rounded-lg border border-[#1e293d] shadow-sm">
                <div>
                    <h1 class="text-sm font-montserrat font-black uppercase tracking-tight text-[#F8FAFC] leading-none">
                        <?php echo htmlspecialchars($team['team_name']); ?>
                    </h1>
                    <p class="text-[9px] font-mono text-[#ffb95f] font-bold uppercase tracking-wider mt-0.5">Manager Console</p>
                </div>
            </div>
        </div>

        <div class="flex items-center gap-2 sm:gap-3">
            <!-- Active Bidding Indicator -->
            <div class="flex items-center gap-1.5 bg-[#131b2e] border border-[#1e293d] rounded-xl px-3 py-1.5 text-xs shadow-sm">
                <span class="w-2 h-2 rounded-full bg-[#94A3B8] animate-pulse" id="status-light"></span>
                <span class="text-[#F8FAFC] font-montserrat font-extrabold tracking-wider uppercase text-[10px]" id="status-text">Arena Idle</span>
            </div>

            <!-- Sound Toggle -->
            <button id="sound-toggle-btn" onclick="toggleMute()" class="flex items-center justify-center bg-[#131b2e] border border-[#1e293d] hover:bg-[#171f33] w-9 h-9 rounded-xl text-xs transition shadow-sm" title="Toggle Sound Effects">
                <i id="sound-icon" class="fa-solid fa-volume-high text-xs text-[#ffb95f]"></i>
            </button>

            <!-- Logout -->
            <a href="../public/logout.php" class="bg-[#131b2e] hover:bg-red-500/20 text-[#dae2fd] hover:text-[#ff5451] border border-[#1e293d] hover:border-[#ff5451]/50 w-9 h-9 sm:w-auto sm:h-auto sm:px-3 sm:py-2 rounded-xl text-xs font-montserrat font-bold transition flex items-center justify-center gap-1.5 shadow-sm" title="Logout">
                <i class="fa-solid fa-power-off text-[#ff5451]"></i> <span class="hidden sm:inline">Logout</span>
            </a>
        </div>
    </header>

    <!-- Main Content Arena -->
    <main class="flex-grow p-4 md:p-6 max-w-7xl w-full mx-auto space-y-6 relative">

        <!-- Top Section: Stats & Active Bidding Panel -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">

        <!-- LEFT SIDE: Franchise Dashboard Stats (4 Cols) -->
        <div class="lg:col-span-4 space-y-6">
            <!-- Budget Purse Card -->
            <div class="pro-card rounded-2xl p-6 border-l-4 border-l-[#ffb95f] relative overflow-hidden shadow-xl">
                <span class="text-[10px] text-[#94A3B8] uppercase font-mono tracking-widest font-bold block">Remaining Purse Limit</span>
                <h2 class="text-4xl sm:text-5xl font-montserrat font-black text-[#ffb95f] mt-1 tracking-tight" id="manager-purse">
                    ₹<?php echo number_format($team['remaining_purse']); ?>
                </h2>
                <div class="mt-4 flex items-center justify-between text-xs text-[#94A3B8] border-t border-[#1e293d] pt-3 font-inter">
                    <span>Total Starting Purse:</span>
                    <span class="font-bold font-mono text-[#F8FAFC]">₹<?php echo number_format($team['total_purse']); ?></span>
                </div>
            </div>

            <!-- Squad List Bento Card -->
            <div class="pro-card rounded-2xl p-6 flex flex-col h-[380px] shadow-xl">
                <div class="flex items-center justify-between border-b border-[#1e293d] pb-4 mb-4">
                    <div>
                        <span class="text-[10px] text-[#94A3B8] uppercase font-mono tracking-widest font-bold">Squad List</span>
                        <h3 class="text-2xl font-montserrat font-black text-[#F8FAFC] mt-1" id="manager-squad-size">
                            <?php echo $team['current_squad_size']; ?> / <?php echo $team['max_squad_size']; ?>
                        </h3>
                    </div>
                    <i class="fa-solid fa-users text-3xl text-[#7bd0ff]"></i>
                </div>
                <!-- Dynamic Player List Container -->
                <div class="flex-grow overflow-y-auto space-y-2 pr-1" id="manager-squad-list">
                    <!-- Fetched dynamically via JS -->
                    <div class="text-center text-xs text-[#94A3B8] py-8 uppercase font-semibold">No players purchased yet.</div>
                </div>
                <p class="text-[10px] text-[#94A3B8] font-mono mt-4 border-t border-[#1e293d] pt-3">Maximum roster is limited to <?php echo $team['max_squad_size']; ?> slots.</p>
            </div>
        </div>

        <!-- CENTER SIDE: Active Auction Dashboard (8 Cols) -->
        <div class="lg:col-span-8 grid grid-cols-1 md:grid-cols-12 gap-6" id="auction-console">
            <!-- Standby Box -->
            <div id="standby-box" class="col-span-12 pro-card rounded-2xl p-10 text-center flex flex-col items-center justify-center min-h-[380px] shadow-xl">
                <i class="fa-solid fa-tower-broadcast text-5xl text-[#ffb95f] animate-pulse mb-4 block"></i>
                <h2 class="text-2xl font-montserrat font-black text-[#F8FAFC] uppercase tracking-tight">Waiting for Live Auctioneer</h2>
                <p class="text-[#dae2fd]/80 max-w-md mt-2 text-xs leading-relaxed font-inter">
                    Please stand by. When the Auctioneer opens bidding for a player, this panel will unlock instantly, presenting your live quick-bidding controls.
                </p>
            </div>

            <!-- Completed Stats Box -->
            <div id="completed-stats-box" class="hidden col-span-12 pro-card rounded-2xl p-6 flex flex-col gap-6 relative overflow-hidden shadow-2xl">
                <!-- Header -->
                <div class="flex items-center justify-between pb-4 border-b border-[#1e293d] relative z-10">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-[#ffb95f]/15 border border-[#ffb95f]/30 flex items-center justify-center text-[#ffb95f]">
                            <i class="fa-solid fa-trophy text-lg"></i>
                        </div>
                        <div>
                            <h2 class="text-base font-montserrat font-black text-[#F8FAFC] uppercase tracking-tight">Auction Completed</h2>
                            <p class="text-[9px] font-mono text-[#94A3B8] uppercase tracking-wider font-bold">Final Stats & Leaderboards</p>
                        </div>
                    </div>
                    <span class="px-2.5 py-1 rounded-full bg-[#ffb95f]/15 border border-[#ffb95f]/30 text-[#ffb95f] text-[9px] font-mono uppercase tracking-wider font-bold flex items-center gap-1.5 shadow-sm">
                        <i class="fa-solid fa-circle text-[6px] text-[#ffb95f] animate-pulse"></i> Final Summary
                    </span>
                </div>

                <!-- Stats Cards Grid -->
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 relative z-10">
                    <div class="bg-[#060e20] border border-[#1e293d] rounded-xl p-4 text-center shadow-sm">
                        <span class="text-[9px] text-[#94A3B8] uppercase tracking-wider block font-bold">Total Purse Spent</span>
                        <span class="text-xl font-montserrat font-black text-[#ffb95f] mt-1 block" id="stats-total-spent">₹0</span>
                    </div>
                    <div class="bg-[#060e20] border border-[#1e293d] rounded-xl p-4 text-center shadow-sm">
                        <span class="text-[9px] text-[#94A3B8] uppercase tracking-wider block font-bold">Avg. Player Bid</span>
                        <span class="text-xl font-montserrat font-black text-[#F8FAFC] mt-1 block" id="stats-avg-price">₹0</span>
                    </div>
                    <div class="bg-[#060e20] border border-[#1e293d] rounded-xl p-4 text-center shadow-sm">
                        <span class="text-[9px] text-[#94A3B8] uppercase tracking-wider block font-bold">Players Sold</span>
                        <span class="text-xl font-montserrat font-black text-[#22C55E] mt-1 block" id="stats-sold-count">0</span>
                    </div>
                    <div class="bg-[#060e20] border border-[#1e293d] rounded-xl p-4 text-center shadow-sm">
                        <span class="text-[9px] text-[#94A3B8] uppercase tracking-wider block font-bold">Unsold Players</span>
                        <span class="text-xl font-montserrat font-black text-[#ff5451] mt-1 block" id="stats-unsold-count">0</span>
                    </div>
                </div>

                <!-- Double Columns -->
                <div class="grid grid-cols-1 md:grid-cols-12 gap-6 relative z-10 mt-2">
                    <div class="md:col-span-7 space-y-3.5">
                        <h3 class="text-xs font-montserrat font-bold text-[#ffb95f] uppercase tracking-wider flex items-center gap-1.5 pb-2 border-b border-[#1e293d]">
                            <i class="fa-solid fa-crown text-[#ffb95f]"></i> Top Valued Players
                        </h3>
                        <div class="space-y-2.5 max-h-[360px] overflow-y-auto pr-1" id="stats-top-players">
                            <!-- Populated dynamically -->
                        </div>
                    </div>
                    <div class="md:col-span-5 space-y-3.5">
                        <h3 class="text-xs font-montserrat font-bold text-[#ffb95f] uppercase tracking-wider flex items-center gap-1.5 pb-2 border-b border-[#1e293d]">
                            <i class="fa-solid fa-chart-line text-[#ffb95f]"></i> Franchise Pursetracker
                        </h3>
                        <div class="space-y-2.5 max-h-[360px] overflow-y-auto pr-1" id="stats-teams-spent">
                            <!-- Populated dynamically -->
                        </div>
                    </div>
                </div>
            </div>

            <!-- Player Profile (4 Cols) -->
            <div id="player-card" class="hidden col-span-12 md:col-span-4 pro-card p-5 rounded-2xl flex flex-col justify-between relative shadow-2xl">
                <!-- Top Header: Country/Location & Role -->
                <div class="flex items-center justify-between relative z-10 mb-3">
                    <div class="flex items-center gap-1.5 bg-[#060e20] border border-[#1e293d] px-2.5 py-1 rounded-lg">
                        <span class="text-xs">🇮🇳</span>
                        <span class="text-[9px] font-mono font-bold text-[#dae2fd] uppercase tracking-wider" id="player-place">
                            Wayanad
                        </span>
                    </div>
                    <span class="bg-[#ff5451]/20 border border-[#ff5451]/40 text-[#ffb3ad] text-[8px] font-mono font-bold px-2.5 py-1 rounded-lg uppercase tracking-wider" id="player-role">
                        ROLE
                    </span>
                </div>

                <!-- Stacked Player Name -->
                <div class="relative z-10 mb-2 text-center">
                    <h2 class="text-xl font-montserrat font-black text-[#F8FAFC] uppercase tracking-tight leading-none" id="player-name">Player Name</h2>
                </div>

                <!-- Center Stage: Cutout Player Image -->
                <div class="relative z-10 my-3 flex justify-center">
                    <div class="w-32 h-36 rounded-2xl overflow-hidden border-2 border-[#ff5451]/50 bg-[#060e20] shadow-2xl relative cursor-pointer" onclick="openImageLightbox(document.getElementById('player-image').src, document.getElementById('player-name').innerText);">
                        <img src="" id="player-image" alt="Player" class="w-full h-full object-cover" onerror="this.onerror=null; this.src='<?php echo $uploadPath; ?>player_placeholder.jpg';">
                    </div>
                </div>

                <!-- Bottom Stats Bar: Base Price -->
                <div class="relative z-10 bg-[#060e20] border border-[#1e293d] rounded-xl px-4 py-2.5 flex justify-between items-center text-xs">
                    <span class="text-[#94A3B8] font-mono font-bold uppercase tracking-wider text-[10px]">Base Price</span>
                    <span class="text-[#7bd0ff] font-mono font-black text-sm" id="player-base-price">₹100</span>
                </div>
            </div>

            <!-- Bidding Action Controls (5 Cols) -->
            <div id="bid-action-card" class="hidden col-span-12 md:col-span-5 space-y-6 flex flex-col justify-between">
                
                <!-- Bidding Panel -->
                <div class="pro-card rounded-2xl p-5 border border-[#1e293d] flex-grow flex flex-col justify-between shadow-xl">
                    <div>
                        <div class="flex justify-between items-center">
                            <span class="text-[10px] text-[#94A3B8] uppercase font-mono tracking-widest font-bold">Current Active Bid</span>
                            <span class="text-[8px] font-mono uppercase tracking-widest font-bold text-white bg-[#ff5451] border border-[#ff5451] px-2 py-0.5 rounded shadow animate-pulse" id="high-bidder-indicator" style="display:none;">Leading</span>
                        </div>
                        <h3 class="text-4xl font-montserrat font-black text-[#ff5451] mt-1 tracking-tight" id="active-bid">₹0</h3>
                        <div class="flex items-center gap-2 mt-1.5" id="leading-team-wrapper">
                            <div id="leading-team-logo-container" class="w-7 h-7 rounded bg-[#060e20] border border-[#1e293d] flex items-center justify-center overflow-hidden p-0.5" style="display: none;">
                                <img src="" id="leading-team-logo" class="w-full h-full object-contain">
                            </div>
                            <p class="text-xs text-[#94A3B8] font-inter" id="leading-team">No bids placed yet</p>
                        </div>
                    </div>

                    <!-- Bid Increments Buttons -->
                    <div class="mt-6">
                        <span class="block text-[9px] text-[#94A3B8] font-mono uppercase tracking-widest font-bold mb-3">Quick Incremental Bids</span>
                        <div class="grid grid-cols-2 gap-3" id="quick-bids-grid">
                            <!-- Populated dynamically via JavaScript -->
                        </div>
                    </div>

                    <!-- Custom Bid Field -->
                    <div class="mt-6 border-t border-[#1e293d] pt-4">
                        <span class="block text-[9px] text-[#94A3B8] font-mono uppercase tracking-widest font-bold mb-2">Place Custom Bid (₹)</span>
                        <div class="flex gap-2">
                            <input type="number" id="custom-bid-input" placeholder="Enter bid amount" min="100" step="100"
                                   class="flex-grow bg-[#060e20] border border-[#1e293d] rounded-xl px-4 py-2.5 text-xs text-[#F8FAFC] placeholder-slate-500 focus:outline-none focus:border-[#ff5451] transition font-mono font-bold">
                            <button id="custom-bid-submit" onclick="submitCustomBid()"
                                   class="bg-[#ff5451] hover:bg-[#ef4444] text-white font-montserrat font-extrabold uppercase text-xs tracking-wider px-5 rounded-xl transition shadow">
                                Bid
                            </button>
                        </div>
                    </div>
                </div>
            </div> <!-- Close Bidding Action Controls (#bid-action-card) -->

            <!-- Bids Flow list in manager (3 Cols) -->
            <div id="bid-history-card" class="hidden col-span-12 md:col-span-3 pro-card rounded-2xl p-4 border border-[#1e293d] flex flex-col h-full overflow-y-auto shadow-xl">
                <span class="block text-[9px] text-[#94A3B8] font-mono uppercase tracking-widest font-bold mb-2.5 border-b border-[#1e293d] pb-1.5 flex items-center gap-1.5">
                    <i class="fa-solid fa-clock-rotate-left text-[#ffb95f]"></i> Live Bid Stream
                </span>
                <div class="space-y-2 pr-1" id="manager-bids-stream">
                    <!-- Polled list will render here -->
                </div>
            </div> <!-- Close Bids Flow list in manager (#bid-history-card) -->
        </div> <!-- Close Active Auction Dashboard (#auction-console) -->
    </div> <!-- Close Top Section (12 Cols Grid) -->

    <!-- Bottom Section: Player Auctions Status (Full Width) -->
    <div class="w-full">
            
            <!-- Block 2: Player Auctions Status -->
            <div class="pro-card rounded-2xl p-6 shadow-xl flex flex-col min-h-[350px]">
                <div class="border-b border-[#1e293d] pb-4 mb-4 space-y-3">
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <span class="text-[10px] text-[#94A3B8] font-mono uppercase tracking-widest font-bold">Player Auction Pool</span>
                            <h3 class="text-base font-montserrat font-black text-[#F8FAFC] flex items-center gap-1.5 uppercase">
                                <i class="fa-solid fa-circle-check text-[#22C55E]"></i> Player Auctions Status
                            </h3>
                        </div>
                        <div class="relative w-40 sm:w-52">
                            <i class="fa-solid fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-[#94A3B8] text-[10px]"></i>
                            <input type="text" id="completed-search-input" onkeyup="filterCompletedPlayers()" placeholder="Search status..." 
                                   class="w-full bg-[#060e20] border border-[#1e293d] rounded-xl pl-8 pr-3 py-1.5 text-xs text-[#F8FAFC] placeholder-slate-500 focus:outline-none focus:border-[#ff5451] transition font-inter">
                        </div>
                    </div>
                    <!-- Filter Chips -->
                    <div class="flex items-center gap-2 overflow-x-auto pb-1 shrink-0 scrollbar-none" id="manager-status-filter-container">
                        <button onclick="setManagerStatusFilter('all')" class="status-chip px-3 py-1.5 rounded-lg border text-[10px] font-montserrat uppercase font-extrabold tracking-wider transition bg-[#ff5451] border-[#ff5451] text-white" data-filter="all">ALL</button>
                        <button onclick="setManagerStatusFilter('Sold')" class="status-chip px-3 py-1.5 rounded-lg border text-[10px] font-montserrat uppercase font-extrabold tracking-wider transition border-[#1e293d] bg-[#060e20] text-[#94A3B8] hover:border-[#ff5451] hover:text-white" data-filter="Sold">SOLD</button>
                        <button onclick="setManagerStatusFilter('Unsold')" class="status-chip px-3 py-1.5 rounded-lg border text-[10px] font-montserrat uppercase font-extrabold tracking-wider transition border-[#1e293d] bg-[#060e20] text-[#94A3B8] hover:border-[#ff5451] hover:text-white" data-filter="Unsold">UNSOLD</button>
                        <button onclick="setManagerStatusFilter('Available')" class="status-chip px-3 py-1.5 rounded-lg border text-[10px] font-montserrat uppercase font-extrabold tracking-wider transition border-[#1e293d] bg-[#060e20] text-[#94A3B8] hover:border-[#ff5451] hover:text-white" data-filter="Available">AVAILABLE</button>
                    </div>
                </div>
                <div class="flex-grow overflow-y-auto max-h-[300px] space-y-2.5 pr-1" id="completed-pool-container">
                    <?php if (empty($completedPool)): ?>
                        <div class="text-center text-[#94A3B8] text-xs py-10 uppercase tracking-widest font-semibold no-players-msg font-inter">
                            No completed players recorded yet.
                        </div>
                    <?php else: ?>
                        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-3">
                            <?php foreach ($completedPool as $cp): 
                                $statusBadgeClass = 'bg-slate-700 text-white';
                                if ($cp['auction_status'] === 'Sold') $statusBadgeClass = 'bg-[#22C55E] text-white';
                                elseif ($cp['auction_status'] === 'Unsold') $statusBadgeClass = 'bg-[#ff5451] text-white';
                                elseif ($cp['auction_status'] === 'Available') $statusBadgeClass = 'bg-[#ffb95f] text-slate-950';
                            ?>
                                <div class="completed-player-card bg-[#060e20] border border-[#1e293d] rounded-xl p-3 flex items-center gap-3"
                                     data-name="<?php echo htmlspecialchars(strtolower($cp['name'])); ?>"
                                     data-[#94A3B8]="<?php echo htmlspecialchars(strtolower($cp['role'])); ?>"
                                     data-[#ff5451]="<?php echo htmlspecialchars(strtolower($cp['place'])); ?>"
                                     data-status="<?php echo htmlspecialchars($cp['auction_status']); ?>">
                                    <div class="w-12 h-12 rounded-lg bg-[#131b2e] border border-[#1e293d] overflow-hidden shrink-0">
                                        <img src="<?php echo $uploadPath; ?><?php echo htmlspecialchars($cp['profile_image'] ?: 'player_placeholder.jpg'); ?>" class="w-full h-full object-cover">
                                    </div>
                                    <div class="flex-grow min-w-0">
                                        <div class="flex items-center justify-between gap-1 mb-0.5">
                                            <span class="text-xs font-montserrat font-bold text-[#F8FAFC] truncate"><?php echo htmlspecialchars($cp['name']); ?></span>
                                            <span class="text-[9px] font-mono font-bold px-1.5 py-0.5 rounded <?php echo $statusBadgeClass; ?> uppercase"><?php echo $cp['auction_status']; ?></span>
                                        </div>
                                        <div class="text-[10px] text-[#94A3B8] font-inter truncate">
                                            <?php echo htmlspecialchars($cp['role']); ?> &bull; <?php echo htmlspecialchars($cp['place']); ?>
                                        </div>
                                        <div class="text-[11px] font-mono font-bold text-[#ffb95f] mt-0.5">
                                            ₹<?php echo number_format($cp['sold_price'] ?: $cp['base_price']); ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

    </div> <!-- Close Bottom Section -->

    </main>

    <!-- Footer Section -->
    <footer class="bg-[#060e20] border-t border-[#1e293d] py-6 px-4 text-center text-xs text-[#94A3B8] font-inter mt-8">
        <div class="max-w-7xl mx-auto flex flex-col sm:flex-row items-center justify-between gap-4">
            <div class="flex items-center gap-2">
                <img src="<?php echo $uploadPath; ?>auctionwala_logo.png" alt="AuctionWala" class="h-6 object-contain">
                <span>© 2026 AuctionWala Franchise Manager Console. All Rights Reserved.</span>
            </div>
            <div class="flex gap-4">
                <a href="../public/landing.php" class="hover:text-white transition">Home</a>
                <a href="../public/today_auctions.php" class="hover:text-white transition">Live Arena</a>
                <a href="https://wa.me/917698767767" target="_blank" class="hover:text-[#22C55E] transition">Support</a>
            </div>
        </div>
    </footer>

    <!-- Pass Team ID to JavaScript -->
    <script>
        const MANAGER_TEAM_ID = <?php echo $teamId; ?>;
        const TOURNAMENT_ID = <?php echo $tournamentId; ?>;
        let currentStatusFilter = 'all';

        function setManagerStatusFilter(filter) {
            currentStatusFilter = filter;
            document.querySelectorAll('#manager-status-filter-container .status-chip').forEach(btn => {
                if (btn.getAttribute('data-filter') === filter) {
                    btn.className = 'status-chip px-3 py-1.5 rounded-lg border text-[10px] font-montserrat uppercase font-extrabold tracking-wider transition bg-[#ff5451] border-[#ff5451] text-white';
                } else {
                    btn.className = 'status-chip px-3 py-1.5 rounded-lg border text-[10px] font-montserrat uppercase font-extrabold tracking-wider transition border-[#1e293d] bg-[#060e20] text-[#94A3B8] hover:border-[#ff5451] hover:text-white';
                }
            });
            filterCompletedPlayers();
        }

        function filterCompletedPlayers() {
            const query = (document.getElementById('completed-search-input').value || '').toLowerCase().trim();
            const cards = document.querySelectorAll('#completed-pool-container .completed-player-card');
            
            cards.forEach(card => {
                const name = card.getAttribute('data-name') || '';
                const role = card.getAttribute('data-[#94A3B8]') || '';
                const place = card.getAttribute('data-[#ff5451]') || '';
                const status = card.getAttribute('data-status') || '';

                const matchesQuery = !query || name.includes(query) || role.includes(query) || place.includes(query);
                const matchesFilter = (currentStatusFilter === 'all') || (status === currentStatusFilter);

                if (matchesQuery && matchesFilter) {
                    card.style.display = 'flex';
                } else {
                    card.style.display = 'none';
                }
            });
        }
    </script>
    <script src="js/manager_bidding.js"></script>
</body>
</html>
