<?php
// organizer/index.php
session_start();
require_once '../config/db.php';

// Auth Protection
if (!isset($_SESSION['user_logged_in']) && !isset($_SESSION['admin_logged_in'])) {
    header("Location: ../public/login.php");
    exit;
}

$userName = $_SESSION['user_name'] ?? ($_SESSION['admin_username'] ?? 'Organizer');
$userId = $_SESSION['user_id'] ?? 1;
$uploadPath = is_dir('../public/uploads') ? '../public/uploads/' : (is_dir('uploads') ? 'uploads/' : '../uploads/');

// Handle Tournament Environment Switching
if (isset($_GET['switch_t'])) {
    $switchId = (int)$_GET['switch_t'];
    $stmt = $pdo->prepare("SELECT id, code FROM tournaments WHERE id = ?");
    $stmt->execute([$switchId]);
    $tSwitch = $stmt->fetch();
    if ($tSwitch) {
        $_SESSION['tournament_id'] = $tSwitch['id'];
        $_SESSION['tournament_code'] = $tSwitch['code'];
    }
    header("Location: index.php");
    exit;
}

// Fetch all tournaments owned by user or overall if admin
try {
    if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
        $stmt = $pdo->query("SELECT * FROM tournaments ORDER BY id DESC");
    } else {
        $stmt = $pdo->prepare("SELECT * FROM tournaments WHERE organizer_id = ? ORDER BY id DESC");
        $stmt->execute([$userId]);
    }
    $tournaments = $stmt->fetchAll();

    // Ensure session active tournament is set
    $activeTournamentId = get_active_tournament_id($pdo);
    $_SESSION['tournament_id'] = $activeTournamentId;

    $stmt = $pdo->prepare("SELECT * FROM tournaments WHERE id = ?");
    $stmt->execute([$activeTournamentId]);
    $activeTournament = $stmt->fetch();

    if (!$activeTournament && !empty($tournaments)) {
        $activeTournament = $tournaments[0];
        $_SESSION['tournament_id'] = $activeTournament['id'];
        $_SESSION['tournament_code'] = $activeTournament['code'];
    }

    // Fetch Stats for active tournament
    $tId = $activeTournament ? $activeTournament['id'] : 1;

    $tStmt = $pdo->prepare("SELECT COUNT(*) FROM teams WHERE tournament_id = ?");
    $tStmt->execute([$tId]);
    $teamsCount = $tStmt->fetchColumn();

    $pVerifiedStmt = $pdo->prepare("SELECT COUNT(*) FROM players WHERE tournament_id = ? AND payment_status = 'Verified'");
    $pVerifiedStmt->execute([$tId]);
    $playersVerified = $pVerifiedStmt->fetchColumn();

    $pPendingStmt = $pdo->prepare("SELECT COUNT(*) FROM players WHERE tournament_id = ? AND payment_status = 'Pending'");
    $pPendingStmt->execute([$tId]);
    $playersPending = $pPendingStmt->fetchColumn();

    $pSoldStmt = $pdo->prepare("SELECT COUNT(*) FROM players WHERE tournament_id = ? AND auction_status = 'Sold'");
    $pSoldStmt->execute([$tId]);
    $playersSold = $pSoldStmt->fetchColumn();

    $stats = [
        'teams_count' => $teamsCount,
        'players_verified' => $playersVerified,
        'players_pending' => $playersPending,
        'players_sold' => $playersSold
    ];

    // Fetch Public Feed: Today's Live Auctions
    $liveStmt = $pdo->query("
        SELECT t.*, a.status as auction_status 
        FROM tournaments t 
        INNER JOIN auction_state a ON t.id = a.tournament_id 
        WHERE a.status IN ('Bidding', 'Paused') 
        ORDER BY t.id DESC
    ");
    $liveAuctions = $liveStmt ? $liveStmt->fetchAll() : [];

    // Fetch Public Feed: Upcoming Auctions
    $upcomingStmt = $pdo->query("
        SELECT t.*, a.status as auction_status 
        FROM tournaments t 
        LEFT JOIN auction_state a ON t.id = a.tournament_id 
        WHERE a.status IS NULL OR a.status = 'Idle' 
        ORDER BY t.id DESC
    ");
    $upcomingAuctions = $upcomingStmt ? $upcomingStmt->fetchAll() : [];

} catch (Exception $e) {
    die("Database Error: " . $e->getMessage());
}

$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$regUrl = "{$protocol}://{$host}/public/register.php?t_id=" . ($activeTournament['id'] ?? 1);
$specUrl = "{$protocol}://{$host}/public/index.php?t_id=" . ($activeTournament['id'] ?? 1);
?>
<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AuctionWala — Organizer Control Hub</title>
    <?php require_once '../public/components/ui_head.php'; ?>
    <style>
        body {
            background-color: #f8fafc;
            color: #0f172a;
            font-family: 'Inter', sans-serif;
            min-height: 100vh;
        }

        .pro-card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -2px rgba(0, 0, 0, 0.05);
            transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .pro-card-hover:hover {
            border-color: #dc2626;
            box-shadow: 0 10px 20px -5px rgba(220, 38, 38, 0.15);
            transform: translateY(-2px);
        }
    </style>
</head>
<body class="min-h-screen flex flex-col justify-between selection:bg-red-500 selection:text-white">

    <!-- Header Navigation -->
    <header class="w-full bg-white border-b border-slate-200 px-4 py-3 sm:px-8 flex items-center justify-between sticky top-0 z-40 shadow-sm">
        <div class="flex items-center gap-3">
            <a href="../public/landing.php" class="flex items-center gap-2">
                <img src="../public/uploads/auctionwala_logo.png" alt="AuctionWala Logo" class="h-8 sm:h-9 object-contain">
            </a>
            <div class="h-6 w-px bg-slate-200 hidden sm:block"></div>
            <div class="hidden sm:block">
                <h1 class="text-sm font-montserrat font-black uppercase tracking-tight text-slate-900 leading-none">Control Center</h1>
                <p class="text-[9px] font-mono text-slate-500 font-bold uppercase tracking-wider mt-0.5">Organizer Desk</p>
            </div>
        </div>

        <nav class="hidden lg:flex items-center gap-6 font-montserrat font-bold text-xs text-slate-700 uppercase tracking-wider">
            <a href="#today-auctions" class="hover:text-red-600 transition flex items-center gap-1.5">
                <span class="w-2 h-2 rounded-full bg-red-600 animate-ping"></span> Live Auctions
            </a>
            <a href="#upcoming-auctions" class="hover:text-amber-600 transition">Upcoming Schedule</a>
            <a href="#my-leagues" class="hover:text-blue-600 transition">My Created Leagues</a>
            <a href="#control-panel" class="hover:text-emerald-600 transition">Actions Desk</a>
        </nav>

        <!-- User Profile Pill & Quick Actions -->
        <div class="flex items-center gap-3">
            <!-- User Pill -->
            <div class="flex items-center gap-2.5 bg-slate-100 border border-slate-200 px-3 py-1.5 rounded-xl shadow-sm">
                <div class="w-7 h-7 rounded-full bg-amber-500 text-slate-950 font-montserrat font-black text-xs flex items-center justify-center shadow-sm">
                    <?php echo strtoupper(substr($userName ?? 'U', 0, 1)); ?>
                </div>
                <div class="text-left">
                    <span class="text-xs font-montserrat font-bold text-slate-900 block leading-tight truncate max-w-[120px] sm:max-w-[160px]"><?php echo htmlspecialchars($userName); ?></span>
                    <span class="text-[8px] font-mono uppercase tracking-widest font-bold text-amber-700 block">Organizer</span>
                </div>
            </div>

            <button onclick="openCreateModal()" class="bg-red-600 hover:bg-red-700 text-white font-montserrat font-extrabold px-3.5 py-2 rounded-xl text-xs uppercase tracking-wider shadow-sm transition flex items-center gap-1.5">
                <i class="fa-solid fa-plus text-xs"></i> <span class="hidden sm:inline">New League</span>
            </button>

            <a href="../public/logout.php" class="bg-slate-100 hover:bg-red-50 text-slate-700 hover:text-red-600 border border-slate-200 hover:border-red-200 w-9 h-9 sm:w-auto sm:h-auto sm:px-3 sm:py-2 rounded-xl text-xs font-montserrat font-bold transition flex items-center justify-center gap-1.5 shadow-sm" title="Logout">
                <i class="fa-solid fa-power-off text-red-600"></i> <span class="hidden sm:inline">Logout</span>
            </a>
        </div>
    </header>

    <!-- Main Content Hub Container -->
    <main class="w-full max-w-7xl mx-auto px-4 py-8 flex-1 space-y-10">

        <!-- ================= SECTION 1: TODAY'S LIVE PLAYER AUCTIONS (PUBLIC FEED) ================= -->
        <section id="today-auctions" class="space-y-4">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-xl sm:text-2xl font-montserrat font-black text-slate-900 uppercase tracking-tight flex items-center gap-2">
                        🔴 Today's Live Player Auctions
                    </h2>
                    <p class="text-xs font-inter text-slate-500">Active bidding rooms happening right now across the platform.</p>
                </div>
                <a href="../public/today_auctions.php" class="text-xs font-mono font-bold text-red-600 hover:text-red-700 uppercase tracking-wider flex items-center gap-1 transition">
                    VIEW ALL LIVE <i class="fa-solid fa-chevron-right text-[10px]"></i>
                </a>
            </div>

            <?php if (empty($liveAuctions)): ?>
                <div class="pro-card rounded-xl p-8 text-center shadow-sm">
                    <div class="w-12 h-12 rounded-full bg-slate-100 flex items-center justify-center mx-auto mb-3 text-slate-500 text-xl">
                        <i class="fa-solid fa-hourglass-half"></i>
                    </div>
                    <h3 class="text-sm font-montserrat font-bold text-slate-900 uppercase">No Auctions Live Right Now</h3>
                    <p class="text-xs text-slate-500 font-inter mt-1">There are no live bidding sessions at this exact moment. Check out upcoming schedule below to see what's starting soon!</p>
                </div>
            <?php else: ?>
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <?php foreach (array_slice($liveAuctions, 0, 4) as $t): ?>
                        <div class="pro-card pro-card-hover rounded-xl p-5 flex flex-col sm:flex-row gap-4 items-start sm:items-center relative cursor-pointer"
                             onclick="window.location.href='../public/index.php?t_id=<?php echo $t['id']; ?>'">
                            
                            <!-- Left Banner / Logo -->
                            <div class="w-full sm:w-36 h-32 sm:h-36 rounded-lg overflow-hidden bg-slate-100 border border-slate-200 shrink-0 relative">
                                <img src="<?php echo $uploadPath; ?><?php echo htmlspecialchars($t['logo'] ?: 'auctionwala_logo.png'); ?>" alt="Logo" class="w-full h-full object-cover">
                                <span class="absolute top-2 left-2 bg-red-600 text-white font-mono font-bold text-[9px] px-2 py-0.5 rounded uppercase tracking-wider flex items-center gap-1 shadow">
                                    <span class="w-1.5 h-1.5 rounded-full bg-white animate-ping"></span> LIVE
                                </span>
                            </div>

                            <!-- Right Details Content -->
                            <div class="flex-grow space-y-3 w-full">
                                <h3 class="font-montserrat font-bold text-slate-900 text-base sm:text-lg leading-snug hover:text-red-600 transition">
                                    <?php echo htmlspecialchars($t['name']); ?>
                                </h3>

                                <div class="grid grid-cols-2 gap-x-3 gap-y-1.5 text-xs text-slate-600 font-inter">
                                    <div class="flex items-center gap-1.5">
                                        <i class="fa-solid fa-users text-blue-600 text-xs w-4"></i>
                                        <span><?php echo $t['max_squad_size_default']; ?> Players/Team</span>
                                    </div>
                                    <div class="flex items-center gap-1.5">
                                        <i class="fa-solid fa-trophy text-amber-600 text-xs w-4"></i>
                                        <span><?php echo number_format($t['total_purse_default']); ?> Points</span>
                                    </div>
                                    <div class="flex items-center gap-1.5">
                                        <i class="fa-regular fa-clock text-slate-500 text-xs w-4"></i>
                                        <span><?php echo date('h:i A', strtotime($t['created_at'])); ?></span>
                                    </div>
                                    <div class="flex items-center gap-1.5">
                                        <i class="fa-regular fa-calendar text-slate-500 text-xs w-4"></i>
                                        <span><?php echo date('d-m-Y', strtotime($t['created_at'])); ?></span>
                                    </div>
                                </div>

                                <div class="flex items-center justify-between gap-2 pt-1">
                                    <div class="bg-slate-100 text-slate-800 font-mono px-3 py-1.5 rounded text-xs flex items-center gap-1.5 border border-slate-200 flex-grow truncate">
                                        <i class="fa-solid fa-location-dot text-blue-600 text-xs"></i>
                                        <span class="truncate"><?php echo htmlspecialchars($t['place'] ?? 'League Arena'); ?></span>
                                    </div>
                                    <a href="../public/index.php?t_id=<?php echo $t['id']; ?>" onclick="event.stopPropagation();" class="bg-red-600 hover:bg-red-700 text-white font-montserrat font-bold text-xs px-4 py-1.5 rounded transition uppercase tracking-wider shrink-0 flex items-center gap-1 shadow-sm">
                                        <i class="fa-solid fa-tv text-xs"></i> Watch Stream
                                    </a>
                                </div>
                            </div>

                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>

        <!-- ================= SECTION 2: UPCOMING SCHEDULED AUCTIONS (PUBLIC FEED) ================= -->
        <section id="upcoming-auctions" class="space-y-4 pt-4 border-t border-slate-200">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-xl sm:text-2xl font-montserrat font-black text-slate-900 uppercase tracking-tight flex items-center gap-2">
                        📅 Upcoming Scheduled Auctions
                    </h2>
                    <p class="text-xs font-inter text-slate-500">Explore upcoming tournaments and secure your spot.</p>
                </div>
                <a href="../public/upcoming_auctions.php" class="text-xs font-mono font-bold text-amber-700 hover:text-amber-800 uppercase tracking-wider flex items-center gap-1 transition">
                    VIEW ALL UPCOMING <i class="fa-solid fa-chevron-right text-[10px]"></i>
                </a>
            </div>

            <?php if (empty($upcomingAuctions)): ?>
                <div class="pro-card rounded-xl p-8 text-center shadow-sm">
                    <h3 class="text-sm font-montserrat font-bold text-slate-900 uppercase">No Upcoming Auctions Listed</h3>
                    <p class="text-xs text-slate-500 font-inter mt-1">Host your league on AuctionWala today!</p>
                </div>
            <?php else: ?>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <?php foreach (array_slice($upcomingAuctions, 0, 3) as $t): ?>
                        <div class="pro-card pro-card-hover rounded-xl p-5 flex flex-col justify-between space-y-4 relative cursor-pointer"
                             onclick="window.location.href='../public/index.php?t_id=<?php echo $t['id']; ?>'">
                            
                            <!-- Top Thumbnail Image Banner -->
                            <div class="w-full h-40 rounded-lg overflow-hidden bg-slate-100 border border-slate-200 relative">
                                <img src="<?php echo $uploadPath; ?><?php echo htmlspecialchars($t['logo'] ?: 'app_bg.jpg'); ?>" alt="Tournament Banner" class="w-full h-full object-cover">
                                <div class="absolute top-2 right-2">
                                    <span class="bg-amber-500 text-slate-950 font-mono font-bold text-[9px] px-2.5 py-1 rounded uppercase tracking-wider shadow">
                                        UPCOMING
                                    </span>
                                </div>
                            </div>

                            <!-- Details -->
                            <div class="space-y-3">
                                <h3 class="font-montserrat font-bold text-slate-900 text-base leading-snug hover:text-red-600 transition">
                                    <?php echo htmlspecialchars($t['name']); ?>
                                </h3>

                                <div class="space-y-1.5 text-xs text-slate-600 font-inter">
                                    <div class="flex justify-between border-b border-slate-100 pb-1">
                                        <span>Players/Team:</span>
                                        <span class="font-mono font-bold text-slate-900"><?php echo $t['max_squad_size_default']; ?></span>
                                    </div>
                                    <div class="flex justify-between border-b border-slate-100 pb-1">
                                        <span>Total Purse/Team:</span>
                                        <span class="font-mono font-bold text-amber-700"><?php echo number_format($t['total_purse_default']); ?></span>
                                    </div>
                                    <div class="flex justify-between border-b border-slate-100 pb-1">
                                        <span>Date:</span>
                                        <span class="font-mono text-slate-900"><?php echo date('F d, Y', strtotime($t['created_at'])); ?></span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span>Time:</span>
                                        <span class="font-mono text-slate-900"><?php echo date('h:i A', strtotime($t['created_at'])); ?> IST</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Action Button -->
                            <div class="pt-2">
                                <?php if ($t['id'] == 3 || strpos(strtolower($t['name']), 'auctionwala') !== false): ?>
                                    <a href="../public/register.php?t_id=<?php echo $t['id']; ?>" onclick="event.stopPropagation();" class="w-full bg-red-600 hover:bg-red-700 text-white font-montserrat font-bold text-xs py-2.5 rounded transition uppercase tracking-wider text-center block shadow-sm">
                                        REGISTER NOW
                                    </a>
                                <?php else: ?>
                                    <a href="../public/index.php?t_id=<?php echo $t['id']; ?>" onclick="event.stopPropagation();" class="w-full border border-slate-300 hover:border-red-600 bg-slate-50 text-slate-900 font-montserrat font-bold text-xs py-2.5 rounded transition uppercase tracking-wider text-center block">
                                        VIEW DETAILS
                                    </a>
                                <?php endif; ?>
                            </div>

                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>

        <!-- ================= SECTION 3: ORGANIZER CONTROL CENTER & MANAGED AUCTIONS ================= -->
        <section id="control-panel" class="space-y-6 pt-4 border-t border-slate-200">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <h2 class="text-xl sm:text-2xl font-montserrat font-black text-slate-900 uppercase tracking-tight flex items-center gap-2">
                        🛠️ Organizer Control Center
                    </h2>
                    <p class="text-xs font-inter text-slate-500">Manage your private leagues, launch live auctioneer desk, and generate registration links.</p>
                </div>

                <button onclick="openCreateModal()" class="bg-red-600 hover:bg-red-700 text-white font-montserrat font-extrabold px-5 py-2.5 rounded-xl text-xs uppercase tracking-wider shadow-sm transition flex items-center justify-center gap-2 w-fit">
                    <i class="fa-solid fa-plus text-xs"></i> Provision New League
                </button>
            </div>

            <!-- Welcome Banner & Active Tournament Hero Card -->
            <div class="pro-card p-6 sm:p-8 rounded-2xl shadow-sm relative overflow-hidden flex flex-col md:flex-row md:items-center justify-between gap-6 border-l-4 border-l-red-600">
                <div class="space-y-2 max-w-2xl">
                    <div class="flex items-center gap-2">
                        <span class="bg-emerald-100 text-emerald-800 border border-emerald-300 text-[10px] font-mono font-bold px-3 py-1 rounded-full uppercase tracking-widest flex items-center gap-1.5 shadow-sm">
                            <span class="w-2 h-2 rounded-full bg-emerald-600 animate-pulse"></span> Active League Environment
                        </span>
                        <span class="text-xs text-slate-500 font-mono">Code: <strong class="text-amber-700 font-mono font-bold"><?php echo htmlspecialchars($activeTournament['code'] ?? 'N/A'); ?></strong></span>
                    </div>

                    <h3 class="text-2xl sm:text-3xl font-montserrat font-black text-slate-900 uppercase tracking-tight">
                        <?php echo htmlspecialchars($activeTournament['name'] ?? 'No Active Tournament'); ?>
                    </h3>
                    <p class="text-xs text-slate-600 font-inter leading-relaxed">
                        Default Team Purse: <strong class="text-amber-700 font-mono font-bold">₹<?php echo number_format($activeTournament['total_purse_default'] ?? 10000); ?></strong> &bull; Max Squad Size: <strong class="text-blue-700 font-mono font-bold"><?php echo $activeTournament['max_squad_size_default'] ?? 11; ?> Players</strong>
                    </p>
                </div>

                <!-- Tournament Environment Switcher Button -->
                <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3 shrink-0">
                    <?php if (count($tournaments) > 1): ?>
                        <div class="relative">
                            <select onchange="window.location.href='index.php?switch_t=' + this.value" class="bg-slate-50 border border-slate-300 rounded-xl px-4 py-2.5 text-xs text-slate-900 font-montserrat font-bold outline-none shadow-sm cursor-pointer hover:border-red-600 transition">
                                <?php foreach ($tournaments as $tourn): ?>
                                    <option value="<?php echo $tourn['id']; ?>" <?php echo ((int)$tourn['id'] === (int)($activeTournament['id'] ?? 0)) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($tourn['name']); ?> (<?php echo htmlspecialchars($tourn['code']); ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    <?php endif; ?>
                    <button onclick="openCreateModal()" class="bg-red-600 hover:bg-red-700 text-white font-montserrat font-extrabold px-4 py-2.5 rounded-xl text-xs uppercase tracking-wider shadow-sm transition flex items-center justify-center gap-2">
                        <i class="fa-solid fa-plus text-xs"></i> New League
                    </button>
                    <?php if ($activeTournament): ?>
                        <button onclick="deleteLeague(<?php echo (int)$activeTournament['id']; ?>, '<?php echo htmlspecialchars(addslashes($activeTournament['name'])); ?>')" class="bg-slate-100 hover:bg-red-50 text-slate-700 hover:text-red-600 border border-slate-200 hover:border-red-300 font-montserrat font-bold px-3 py-2.5 rounded-xl text-xs uppercase tracking-wider transition flex items-center justify-center gap-1.5 shadow-sm" title="Delete Active League">
                            <i class="fa-solid fa-trash-can text-red-600"></i> <span class="hidden sm:inline">Delete</span>
                        </button>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Metric Stat Cards Row -->
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                <div class="pro-card p-5 rounded-2xl shadow-sm flex items-center justify-between">
                    <div>
                        <span class="text-[10px] font-mono font-bold uppercase text-slate-500 tracking-wider block">Franchise Teams</span>
                        <div class="text-2xl sm:text-3xl font-montserrat font-black text-slate-900 mt-1">
                            <?php echo $stats['teams_count']; ?>
                        </div>
                    </div>
                    <div class="w-11 h-11 rounded-xl bg-amber-50 border border-amber-200 flex items-center justify-center text-amber-600">
                        <i class="fa-solid fa-shield-halved text-xl"></i>
                    </div>
                </div>

                <div class="pro-card p-5 rounded-2xl shadow-sm flex items-center justify-between">
                    <div>
                        <span class="text-[10px] font-mono font-bold uppercase text-slate-500 tracking-wider block">Verified Players</span>
                        <div class="text-2xl sm:text-3xl font-montserrat font-black text-emerald-600 mt-1">
                            <?php echo $stats['players_verified']; ?>
                        </div>
                    </div>
                    <div class="w-11 h-11 rounded-xl bg-emerald-50 border border-emerald-200 flex items-center justify-center text-emerald-600">
                        <i class="fa-solid fa-user-check text-xl"></i>
                    </div>
                </div>

                <div class="pro-card p-5 rounded-2xl shadow-sm flex items-center justify-between">
                    <div>
                        <span class="text-[10px] font-mono font-bold uppercase text-slate-500 tracking-wider block">Pending Approval</span>
                        <div class="text-2xl sm:text-3xl font-montserrat font-black text-amber-600 mt-1">
                            <?php echo $stats['players_pending']; ?>
                        </div>
                    </div>
                    <div class="w-11 h-11 rounded-xl bg-amber-50 border border-amber-200 flex items-center justify-center text-amber-600">
                        <i class="fa-solid fa-user-clock text-xl"></i>
                    </div>
                </div>

                <div class="pro-card p-5 rounded-2xl shadow-sm flex items-center justify-between">
                    <div>
                        <span class="text-[10px] font-mono font-bold uppercase text-slate-500 tracking-wider block">Players Sold</span>
                        <div class="text-2xl sm:text-3xl font-montserrat font-black text-blue-600 mt-1">
                            <?php echo $stats['players_sold']; ?>
                        </div>
                    </div>
                    <div class="w-11 h-11 rounded-xl bg-blue-50 border border-blue-200 flex items-center justify-center text-blue-600">
                        <i class="fa-solid fa-gavel text-xl"></i>
                    </div>
                </div>
            </div>

            <!-- Organizer Action Modules (4 Cards Grid) -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">

                <!-- 1. Live Auctioneer Control Desk -->
                <div class="pro-card p-6 rounded-2xl border border-red-200 transition group flex flex-col justify-between shadow-sm">
                    <div>
                        <div class="w-12 h-12 rounded-2xl bg-red-50 border border-red-200 flex items-center justify-center mb-4 text-red-600">
                            <i class="fa-solid fa-tower-cell text-2xl"></i>
                        </div>
                        <h3 class="text-base font-montserrat font-bold text-slate-900 uppercase tracking-tight">Live Auctioneer Desk</h3>
                        <p class="text-xs text-slate-500 mt-2 font-inter leading-relaxed">Launch the primary auctioneer controller to run live player bidding.</p>
                    </div>
                    <div class="mt-6 pt-3 border-t border-slate-100">
                        <a href="../admin/auction.php" class="w-full bg-red-600 hover:bg-red-700 text-white font-montserrat font-extrabold text-xs py-2.5 rounded-xl uppercase tracking-wider transition flex items-center justify-center gap-2 shadow-sm">
                            <i class="fa-solid fa-gavel"></i> Launch Console
                        </a>
                    </div>
                </div>

                <!-- 2. Team Franchise Management -->
                <div class="pro-card p-6 rounded-2xl border border-blue-200 transition group flex flex-col justify-between shadow-sm">
                    <div>
                        <div class="w-12 h-12 rounded-2xl bg-blue-50 border border-blue-200 flex items-center justify-center mb-4 text-blue-600">
                            <i class="fa-solid fa-shield-halved text-2xl"></i>
                        </div>
                        <h3 class="text-base font-montserrat font-bold text-slate-900 uppercase tracking-tight">Teams & Purses</h3>
                        <p class="text-xs text-slate-500 mt-2 font-inter leading-relaxed">Create franchise teams, set team purse budgets, and assign team logos.</p>
                    </div>
                    <div class="mt-6 pt-3 border-t border-slate-100">
                        <a href="../admin/index.php" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-montserrat font-bold text-xs py-2.5 rounded-xl uppercase tracking-wider transition flex items-center justify-center gap-2 shadow-sm">
                            <i class="fa-solid fa-users-gear"></i> Manage Teams
                        </a>
                    </div>
                </div>

                <!-- 3. Player Registration Link -->
                <div class="pro-card p-6 rounded-2xl border border-amber-200 transition group flex flex-col justify-between shadow-sm">
                    <div>
                        <div class="w-12 h-12 rounded-2xl bg-amber-50 border border-amber-200 flex items-center justify-center mb-4 text-amber-600">
                            <i class="fa-solid fa-link text-2xl"></i>
                        </div>
                        <h3 class="text-base font-montserrat font-bold text-slate-900 uppercase tracking-tight">Player Registration Link</h3>
                        <p class="text-xs text-slate-500 mt-2 font-inter leading-relaxed">Share this registration URL with players to join your league pool.</p>
                    </div>
                    <div class="mt-4 pt-3 border-t border-slate-100 space-y-2">
                        <input type="text" readonly value="<?php echo htmlspecialchars($regUrl); ?>" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-[11px] text-amber-800 font-mono font-bold outline-none select-all truncate shadow-inner">
                        <button onclick="copyToClipboard('<?php echo htmlspecialchars($regUrl); ?>')" class="w-full bg-slate-100 hover:bg-slate-200 text-slate-900 border border-slate-200 font-montserrat font-bold text-xs py-2 rounded-xl uppercase tracking-wider transition flex items-center justify-center gap-1.5 shadow-sm">
                            <i class="fa-solid fa-copy"></i> Copy Link
                        </button>
                    </div>
                </div>

                <!-- 4. Spectator Live View -->
                <div class="pro-card p-6 rounded-2xl border border-emerald-200 transition group flex flex-col justify-between shadow-sm">
                    <div>
                        <div class="w-12 h-12 rounded-2xl bg-emerald-50 border border-emerald-200 flex items-center justify-center mb-4 text-emerald-600">
                            <i class="fa-solid fa-tv text-2xl"></i>
                        </div>
                        <h3 class="text-base font-montserrat font-bold text-slate-900 uppercase tracking-tight">Spectator Stream</h3>
                        <p class="text-xs text-slate-500 mt-2 font-inter leading-relaxed">Share live bidding broadcast screen with audience and spectators.</p>
                    </div>
                    <div class="mt-4 pt-3 border-t border-slate-100 space-y-2">
                        <input type="text" readonly value="<?php echo htmlspecialchars($specUrl); ?>" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-[11px] text-emerald-700 font-mono font-bold outline-none select-all truncate shadow-inner">
                        <a href="<?php echo htmlspecialchars($specUrl); ?>" target="_blank" class="w-full bg-emerald-600 hover:bg-emerald-700 text-white font-montserrat font-extrabold text-xs py-2 rounded-xl uppercase tracking-wider transition flex items-center justify-center gap-1.5 shadow-sm">
                            <i class="fa-solid fa-arrow-up-right-from-square"></i> Open Stream
                        </a>
                    </div>
                </div>
            </div>

            <!-- My Created Leagues List Section -->
            <div id="my-leagues" class="pro-card p-6 rounded-2xl space-y-4">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-lg font-montserrat font-black text-slate-900 uppercase tracking-tight">
                            🏆 Your Created Leagues (<?php echo count($tournaments); ?>)
                        </h3>
                        <p class="text-xs text-slate-500 font-inter">Switch between your active leagues or access their specific controls.</p>
                    </div>
                    <button onclick="openCreateModal()" class="text-xs font-mono font-bold text-red-600 hover:text-red-700 uppercase tracking-wider flex items-center gap-1 transition">
                        <i class="fa-solid fa-plus text-[10px]"></i> Create New
                    </button>
                </div>

                <?php if (empty($tournaments)): ?>
                    <div class="text-center py-6 text-slate-500 text-xs">
                        No tournaments created yet. Click "Provision New League" above to get started!
                    </div>
                <?php else: ?>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        <?php foreach ($tournaments as $tItem): 
                            $isCurrent = ((int)$tItem['id'] === (int)($activeTournament['id'] ?? 0));
                        ?>
                            <div class="bg-slate-50 border <?php echo $isCurrent ? 'border-red-600 shadow-sm' : 'border-slate-200'; ?> rounded-xl p-4 flex flex-col justify-between space-y-3">
                                <div>
                                    <div class="flex items-center justify-between gap-2 mb-1">
                                        <span class="text-[10px] font-mono text-amber-700 font-bold uppercase">Code: <?php echo htmlspecialchars($tItem['code']); ?></span>
                                        <?php if ($isCurrent): ?>
                                            <span class="bg-red-600 text-white font-mono font-bold text-[8px] px-2 py-0.5 rounded uppercase tracking-wider">ACTIVE</span>
                                        <?php endif; ?>
                                    </div>
                                    <h4 class="font-montserrat font-bold text-slate-900 text-sm leading-snug">
                                        <?php echo htmlspecialchars($tItem['name']); ?>
                                    </h4>
                                    <div class="text-[11px] text-slate-600 font-inter mt-1.5 flex gap-3">
                                        <span>Purse: <strong class="text-amber-700 font-mono">₹<?php echo number_format($tItem['total_purse_default']); ?></strong></span>
                                        <span>Squad: <strong class="text-blue-700 font-mono"><?php echo $tItem['max_squad_size_default']; ?></strong></span>
                                    </div>
                                </div>

                                <div class="pt-2 border-t border-slate-200 flex items-center justify-between gap-2">
                                    <?php if (!$isCurrent): ?>
                                        <a href="index.php?switch_t=<?php echo $tItem['id']; ?>" class="flex-grow bg-slate-200 hover:bg-slate-300 text-slate-900 font-montserrat font-bold text-[11px] py-1.5 rounded transition text-center uppercase tracking-wider">
                                            Switch to League
                                        </a>
                                    <?php else: ?>
                                        <a href="../admin/auction.php" class="flex-grow bg-red-600 hover:bg-red-700 text-white font-montserrat font-bold text-[11px] py-1.5 rounded transition text-center uppercase tracking-wider shadow-sm">
                                            Open Auction Desk
                                        </a>
                                    <?php endif; ?>
                                    <button onclick="deleteLeague(<?php echo $tItem['id']; ?>, '<?php echo htmlspecialchars(addslashes($tItem['name'])); ?>')" class="bg-slate-200 hover:bg-red-100 text-slate-600 hover:text-red-600 border border-slate-300 hover:border-red-300 px-2.5 py-1.5 rounded transition text-xs" title="Delete League">
                                        <i class="fa-solid fa-trash-can"></i>
                                    </button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

        </section>

    </main>

    <!-- Create Tournament Modal -->
    <div id="create-modal" class="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-sm hidden flex items-center justify-center p-4">
        <div class="bg-white border border-slate-200 rounded-2xl p-6 sm:p-8 max-w-md w-full shadow-2xl relative text-slate-900">
            <button onclick="closeCreateModal()" class="absolute top-4 right-4 text-slate-400 hover:text-slate-700 text-lg">
                <i class="fa-solid fa-xmark"></i>
            </button>

            <h3 class="text-xl font-montserrat font-black uppercase text-slate-900 tracking-tight mb-1 flex items-center gap-2">
                <i class="fa-solid fa-trophy text-amber-600"></i> Provision New Tournament
            </h3>
            <p class="text-xs text-slate-500 mb-6 font-inter">Create an isolated auction room with custom team purses and squad limits.</p>

            <form onsubmit="handleCreateTournament(event)" class="space-y-4">
                <div>
                    <label class="block text-xs font-mono font-bold uppercase text-slate-700 mb-1">Tournament Name *</label>
                    <input type="text" name="name" required placeholder="e.g. Wayanad Super League 2026"
                           class="w-full bg-slate-50 border border-slate-300 rounded-xl px-4 py-2.5 text-xs text-slate-900 placeholder-slate-400 focus:outline-none focus:border-red-600 font-montserrat font-bold">
                </div>

                <div>
                    <label class="block text-xs font-mono font-bold uppercase text-slate-700 mb-1">League Identifier Code (Optional)</label>
                    <input type="text" name="code" placeholder="e.g. wsl-2026 (Auto-generated if blank)"
                           class="w-full bg-slate-50 border border-slate-300 rounded-xl px-4 py-2.5 text-xs text-slate-900 placeholder-slate-400 focus:outline-none focus:border-red-600 font-mono">
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-mono font-bold uppercase text-slate-700 mb-1">Total Purse (Points)</label>
                        <input type="number" name="total_purse" value="10000" min="1000" step="500" required
                               class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3 py-2.5 text-xs text-slate-900 font-mono font-bold focus:outline-none focus:border-red-600">
                    </div>
                    <div>
                        <label class="block text-xs font-mono font-bold uppercase text-slate-700 mb-1">Max Squad Size</label>
                        <input type="number" name="max_squad_size" value="11" min="1" max="50" required
                               class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3 py-2.5 text-xs text-slate-900 font-mono font-bold focus:outline-none focus:border-red-600">
                    </div>
                </div>

                <div class="pt-2">
                    <button type="submit" class="w-full bg-red-600 hover:bg-red-700 text-white font-montserrat font-extrabold py-3 rounded-xl uppercase text-xs tracking-wider transition shadow-sm">
                        Create & Activate Tournament
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- WhatsApp Floating Action Button -->
    <a href="https://wa.me/917698767767?text=Hi%20AuctionWala%20Support%2C%20I%20need%20help%20with%20my%20league" target="_blank" class="whatsapp-float" title="Contact Live WhatsApp Support">
        <i class="fa-brands fa-whatsapp"></i>
    </a>

    <!-- Footer -->
    <footer class="w-full bg-white border-t border-slate-200 py-6 px-4 text-center text-xs text-slate-500 font-inter mt-10">
        <p>© 2026 AuctionWala Premier League. Built for premium turf events.</p>
    </footer>

    <script>
        function openCreateModal() {
            document.getElementById('create-modal').classList.remove('hidden');
        }

        function closeCreateModal() {
            document.getElementById('create-modal').classList.add('hidden');
        }

        function copyToClipboard(text) {
            navigator.clipboard.writeText(text).then(() => {
                alert('Copied registration link to clipboard!');
            });
        }

        async function handleCreateTournament(e) {
            e.preventDefault();
            const form = e.target;
            const formData = new FormData(form);

            try {
                const res = await fetch('../api/create_tournament.php', {
                    method: 'POST',
                    body: formData
                });
                const data = await res.json();
                if (data.success) {
                    alert(data.message);
                    window.location.reload();
                } else {
                    alert(data.error || 'Failed to create tournament.');
                }
            } catch (err) {
                alert('Error creating tournament: ' + err.message);
            }
        }

        async function deleteLeague(id, name) {
            if (!confirm(`Are you sure you want to permanently delete "${name}"?\n\nThis will remove all teams, registered players, and live bidding records for this league!`)) {
                return;
            }

            try {
                const formData = new FormData();
                formData.append('tournament_id', id);

                const res = await fetch('../api/delete_tournament.php', {
                    method: 'POST',
                    body: formData
                });
                const data = await res.json();
                if (data.success) {
                    alert(data.message);
                    window.location.href = 'index.php';
                } else {
                    alert(data.error || 'Failed to delete tournament.');
                }
            } catch (err) {
                alert('Error deleting tournament: ' + err.message);
            }
        }
    </script>
</body>
</html>
