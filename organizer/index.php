<?php
// organizer/index.php
session_start();
require_once '../config/db.php';

// Auth Protection
if (!isset($_SESSION['user_logged_in']) && !isset($_SESSION['admin_logged_in'])) {
    header("Location: ../public/login.php");
    exit;
}

$userId = $_SESSION['user_id'] ?? 1;
$userName = $_SESSION['user_name'] ?? $_SESSION['admin_username'] ?? 'Organizer';
$userEmail = $_SESSION['user_email'] ?? 'admin@smcl.com';

// 1. Fetch public platform feeds (Live & Upcoming auctions)
$categorized = get_categorized_tournaments($pdo);
$liveAuctions = $categorized['live'];
$upcomingAuctions = $categorized['upcoming'];
$allPublicTournaments = $categorized['all'];

// 2. Fetch all tournaments owned by this organizer
$tournaments = [];
$activeTournament = null;

try {
    if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true && !isset($_SESSION['user_id'])) {
        // Superadmin sees all tournaments
        $stmt = $pdo->query("SELECT t.*, u.name as organizer_name FROM tournaments t LEFT JOIN users u ON t.organizer_id = u.id ORDER BY t.id DESC");
        $tournaments = $stmt->fetchAll();
    } else {
        $stmt = $pdo->prepare("SELECT * FROM tournaments WHERE organizer_id = ? ORDER BY id DESC");
        $stmt->execute([$userId]);
        $tournaments = $stmt->fetchAll();
    }

    // Determine current selected tournament
    $activeId = get_active_tournament_id($pdo);
    
    // Handle switching tournament via GET parameter
    if (isset($_GET['switch_t']) && is_numeric($_GET['switch_t'])) {
        $targetId = (int)$_GET['switch_t'];
        foreach ($tournaments as $t) {
            if ((int)$t['id'] === $targetId) {
                $_SESSION['tournament_id'] = $targetId;
                $_SESSION['tournament_code'] = $t['code'];
                header("Location: index.php");
                exit;
            }
        }
    }

    foreach ($tournaments as $t) {
        if ((int)$t['id'] === $activeId) {
            $activeTournament = $t;
            break;
        }
    }

    if (!$activeTournament && !empty($tournaments)) {
        $activeTournament = $tournaments[0];
        $_SESSION['tournament_id'] = (int)$activeTournament['id'];
        $_SESSION['tournament_code'] = $activeTournament['code'];
    }

    // Fetch live metrics for active tournament
    $stats = [
        'teams_count' => 0,
        'players_verified' => 0,
        'players_pending' => 0,
        'players_sold' => 0
    ];

    if ($activeTournament) {
        $tId = (int)$activeTournament['id'];
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM teams WHERE tournament_id = ?");
        $stmt->execute([$tId]);
        $stats['teams_count'] = (int)$stmt->fetchColumn();

        $stmt = $pdo->prepare("SELECT COUNT(*) FROM players WHERE tournament_id = ? AND payment_status = 'Verified'");
        $stmt->execute([$tId]);
        $stats['players_verified'] = (int)$stmt->fetchColumn();

        $stmt = $pdo->prepare("SELECT COUNT(*) FROM players WHERE tournament_id = ? AND payment_status = 'Pending'");
        $stmt->execute([$tId]);
        $stats['players_pending'] = (int)$stmt->fetchColumn();

        $stmt = $pdo->prepare("SELECT COUNT(*) FROM players WHERE tournament_id = ? AND auction_status = 'Sold'");
        $stmt->execute([$tId]);
        $stats['players_sold'] = (int)$stmt->fetchColumn();
    }

} catch (Exception $e) {
    die("Database Error: " . $e->getMessage());
}

$protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$baseUrl = $protocol . '://' . $host . rtrim(dirname(dirname($_SERVER['PHP_SELF'])), '/\\');

$uploadPath = is_dir('../public/uploads') ? '../public/uploads/' : (is_dir('uploads') ? 'uploads/' : '../uploads/');
$regUrl = $baseUrl . '/public/register.php?t=' . ($activeTournament['code'] ?? 'smcl-2026');
$specUrl = $baseUrl . '/public/index.php?t=' . ($activeTournament['code'] ?? 'smcl-2026');
?>
<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AuctionWala — SaaS League Control Hub</title>
    <?php require_once '../public/components/ui_head.php'; ?>
    <style>
        body {
            background-color: #0b1326;
            color: #dae2fd;
            font-family: 'Inter', sans-serif;
            position: relative;
            min-height: 100vh;
        }

        /* Fixed stadium background layer matching pro-broadcast theme */
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
            background: rgba(19, 27, 46, 0.88);
            backdrop-filter: blur(16px);
            border: 1px solid #1e293d;
            transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .pro-card-hover:hover {
            border-color: #ff5451;
            box-shadow: 0 10px 30px -10px rgba(255, 84, 81, 0.25);
            transform: translateY(-2px);
        }

        .whatsapp-float {
            position: fixed;
            bottom: 24px;
            right: 24px;
            width: 52px;
            height: 52px;
            background-color: #25d366;
            color: #fff;
            border-radius: 50px;
            text-align: center;
            font-size: 28px;
            box-shadow: 0 10px 25px rgba(37, 211, 102, 0.4);
            z-index: 100;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
        }
        .whatsapp-float:hover {
            transform: scale(1.1);
            background-color: #128c7e;
        }
    </style>
</head>
<body class="min-h-screen flex flex-col justify-between selection:bg-red-500 selection:text-white">

    <!-- Header Navigation -->
    <header class="w-full bg-[#060e20]/95 backdrop-blur-md border-b border-[#1e293d] px-4 py-3 sm:px-8 flex items-center justify-between sticky top-0 z-40 shadow-xl">
        <div class="flex items-center gap-3">
            <a href="../public/landing.php" class="flex items-center gap-2">
                <img src="../public/uploads/auctionwala_logo.png" alt="AuctionWala Logo" class="h-8 sm:h-9 object-contain">
            </a>
            <div class="h-6 w-px bg-[#1e293d] hidden sm:block"></div>
            <div class="hidden sm:block">
                <h1 class="text-sm font-montserrat font-black uppercase tracking-tight text-[#F8FAFC] leading-none">Control Center</h1>
                <p class="text-[9px] font-mono text-[#94A3B8] font-bold uppercase tracking-wider mt-0.5">Organizer Desk</p>
            </div>
        </div>

        <nav class="hidden lg:flex items-center gap-6 font-montserrat font-bold text-xs text-[#dae2fd] uppercase tracking-wider">
            <a href="#today-auctions" class="hover:text-[#ff5451] transition flex items-center gap-1.5">
                <span class="w-2 h-2 rounded-full bg-[#ff5451] animate-ping"></span> Live Auctions
            </a>
            <a href="#upcoming-auctions" class="hover:text-[#ffb95f] transition">Upcoming Schedule</a>
            <a href="#my-leagues" class="hover:text-[#7bd0ff] transition">My Created Leagues</a>
            <a href="#control-panel" class="hover:text-[#22C55E] transition">Actions Desk</a>
        </nav>

        <!-- User Profile Pill & Quick Actions -->
        <div class="flex items-center gap-3">
            <!-- User Pill -->
            <div class="flex items-center gap-2.5 bg-[#131b2e] border border-[#1e293d] px-3 py-1.5 rounded-xl shadow-sm">
                <div class="w-7 h-7 rounded-full bg-[#ffb95f] text-[#060e20] font-montserrat font-black text-xs flex items-center justify-center shadow-sm">
                    <?php echo strtoupper(substr($userName ?? 'U', 0, 1)); ?>
                </div>
                <div class="text-left">
                    <span class="text-xs font-montserrat font-bold text-[#F8FAFC] block leading-tight truncate max-w-[120px] sm:max-w-[160px]"><?php echo htmlspecialchars($userName); ?></span>
                    <span class="text-[8px] font-mono uppercase tracking-widest font-bold text-[#ffb95f] block">Organizer</span>
                </div>
            </div>

            <button onclick="openCreateModal()" class="bg-[#ff5451] hover:bg-[#ef4444] text-white font-montserrat font-extrabold px-3.5 py-2 rounded-xl text-xs uppercase tracking-wider shadow-lg shadow-red-500/20 transition flex items-center gap-1.5">
                <i class="fa-solid fa-plus text-xs"></i> <span class="hidden sm:inline">New League</span>
            </button>

            <a href="../public/logout.php" class="bg-[#131b2e] hover:bg-red-500/20 text-[#dae2fd] hover:text-[#ff5451] border border-[#1e293d] hover:border-[#ff5451]/50 w-9 h-9 sm:w-auto sm:h-auto sm:px-3 sm:py-2 rounded-xl text-xs font-montserrat font-bold transition flex items-center justify-center gap-1.5 shadow-sm" title="Logout">
                <i class="fa-solid fa-power-off text-[#ff5451]"></i> <span class="hidden sm:inline">Logout</span>
            </a>
        </div>
    </header>

    <!-- Main Content Hub Container -->
    <main class="w-full max-w-7xl mx-auto px-4 py-8 flex-1 space-y-10">

        <!-- ================= SECTION 1: TODAY'S LIVE PLAYER AUCTIONS (PUBLIC FEED) ================= -->
        <section id="today-auctions" class="space-y-4">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-xl sm:text-2xl font-montserrat font-black text-[#F8FAFC] uppercase tracking-tight flex items-center gap-2">
                        🔴 Today's Live Player Auctions
                    </h2>
                    <p class="text-xs font-inter text-[#94A3B8]">Active bidding rooms happening right now across the platform.</p>
                </div>
                <a href="../public/today_auctions.php" class="text-xs font-mono font-bold text-[#ff5451] hover:text-[#ffb3ad] uppercase tracking-wider flex items-center gap-1 transition">
                    VIEW ALL LIVE <i class="fa-solid fa-chevron-right text-[10px]"></i>
                </a>
            </div>

            <?php if (empty($liveAuctions)): ?>
                <div class="pro-card rounded-xl p-8 text-center shadow-lg">
                    <div class="w-12 h-12 rounded-full bg-[#171f33] flex items-center justify-center mx-auto mb-3 text-[#94A3B8] text-xl">
                        <i class="fa-solid fa-hourglass-half"></i>
                    </div>
                    <h3 class="text-sm font-montserrat font-bold text-[#F8FAFC] uppercase">No Auctions Live Right Now</h3>
                    <p class="text-xs text-[#94A3B8] font-inter mt-1">There are no live bidding sessions at this exact moment. Check out upcoming schedule below to see what's starting soon!</p>
                </div>
            <?php else: ?>
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <?php foreach (array_slice($liveAuctions, 0, 4) as $t): ?>
                        <div class="pro-card pro-card-hover rounded-xl p-5 flex flex-col sm:flex-row gap-4 items-start sm:items-center relative cursor-pointer"
                             onclick="window.location.href='../public/index.php?t_id=<?php echo $t['id']; ?>'">
                            
                            <!-- Left Banner / Logo -->
                            <div class="w-full sm:w-36 h-32 sm:h-36 rounded-lg overflow-hidden bg-[#060e20] border border-[#1e293d] shrink-0 relative">
                                <img src="<?php echo $uploadPath; ?><?php echo htmlspecialchars($t['logo'] ?: 'auctionwala_logo.png'); ?>" alt="Logo" class="w-full h-full object-cover">
                                <span class="absolute top-2 left-2 bg-[#ff5451] text-white font-mono font-bold text-[9px] px-2 py-0.5 rounded uppercase tracking-wider flex items-center gap-1 shadow">
                                    <span class="w-1.5 h-1.5 rounded-full bg-white animate-ping"></span> LIVE
                                </span>
                            </div>

                            <!-- Right Details Content -->
                            <div class="flex-grow space-y-3 w-full">
                                <h3 class="font-montserrat font-bold text-[#F8FAFC] text-base sm:text-lg leading-snug hover:text-[#ff5451] transition">
                                    <?php echo htmlspecialchars($t['name']); ?>
                                </h3>

                                <div class="grid grid-cols-2 gap-x-3 gap-y-1.5 text-xs text-[#94A3B8] font-inter">
                                    <div class="flex items-center gap-1.5">
                                        <i class="fa-solid fa-users text-[#7bd0ff] text-xs w-4"></i>
                                        <span><?php echo $t['max_squad_size_default']; ?> Players/Team</span>
                                    </div>
                                    <div class="flex items-center gap-1.5">
                                        <i class="fa-solid fa-trophy text-[#ffb95f] text-xs w-4"></i>
                                        <span><?php echo number_format($t['total_purse_default']); ?> Points</span>
                                    </div>
                                    <div class="flex items-center gap-1.5">
                                        <i class="fa-regular fa-clock text-[#94A3B8] text-xs w-4"></i>
                                        <span><?php echo date('h:i A', strtotime($t['created_at'])); ?></span>
                                    </div>
                                    <div class="flex items-center gap-1.5">
                                        <i class="fa-regular fa-calendar text-[#94A3B8] text-xs w-4"></i>
                                        <span><?php echo date('d-m-Y', strtotime($t['created_at'])); ?></span>
                                    </div>
                                </div>

                                <div class="flex items-center justify-between gap-2 pt-1">
                                    <div class="bg-[#171f33] text-[#7bd0ff] font-mono px-3 py-1.5 rounded text-xs flex items-center gap-1.5 border border-[#1e293d] flex-grow truncate">
                                        <i class="fa-solid fa-location-dot text-[#7bd0ff] text-xs"></i>
                                        <span class="truncate"><?php echo htmlspecialchars($t['place'] ?? 'League Arena'); ?></span>
                                    </div>
                                    <a href="../public/index.php?t_id=<?php echo $t['id']; ?>" onclick="event.stopPropagation();" class="bg-[#ff5451] hover:bg-[#ef4444] text-white font-montserrat font-bold text-xs px-4 py-1.5 rounded transition uppercase tracking-wider shrink-0 flex items-center gap-1">
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
        <section id="upcoming-auctions" class="space-y-4 pt-4 border-t border-[#1e293d]">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-xl sm:text-2xl font-montserrat font-black text-[#F8FAFC] uppercase tracking-tight flex items-center gap-2">
                        📅 Upcoming Scheduled Auctions
                    </h2>
                    <p class="text-xs font-inter text-[#94A3B8]">Explore upcoming tournaments and secure your spot.</p>
                </div>
                <a href="../public/upcoming_auctions.php" class="text-xs font-mono font-bold text-[#ffb95f] hover:text-[#ffddb8] uppercase tracking-wider flex items-center gap-1 transition">
                    VIEW ALL UPCOMING <i class="fa-solid fa-chevron-right text-[10px]"></i>
                </a>
            </div>

            <?php if (empty($upcomingAuctions)): ?>
                <div class="pro-card rounded-xl p-8 text-center shadow-md">
                    <h3 class="text-sm font-montserrat font-bold text-[#F8FAFC] uppercase">No Upcoming Auctions Listed</h3>
                    <p class="text-xs text-[#94A3B8] font-inter mt-1">Host your league on AuctionWala today!</p>
                </div>
            <?php else: ?>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <?php foreach (array_slice($upcomingAuctions, 0, 3) as $t): ?>
                        <div class="pro-card pro-card-hover rounded-xl p-5 flex flex-col justify-between space-y-4 relative cursor-pointer"
                             onclick="window.location.href='../public/index.php?t_id=<?php echo $t['id']; ?>'">
                            
                            <!-- Top Thumbnail Image Banner -->
                            <div class="w-full h-40 rounded-lg overflow-hidden bg-[#060e20] border border-[#1e293d] relative">
                                <img src="<?php echo $uploadPath; ?><?php echo htmlspecialchars($t['logo'] ?: 'app_bg.jpg'); ?>" alt="Tournament Banner" class="w-full h-full object-cover">
                                <div class="absolute top-2 right-2">
                                    <?php if ($t['registration_enabled']): ?>
                                        <span class="bg-[#ff5451] text-white font-mono font-bold text-[9px] px-2.5 py-1 rounded uppercase tracking-wider shadow">
                                            UPCOMING
                                        </span>
                                    <?php else: ?>
                                        <span class="bg-[#ffb95f] text-[#060e20] font-mono font-bold text-[9px] px-2.5 py-1 rounded uppercase tracking-wider shadow">
                                            UPCOMING
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- Details -->
                            <div class="space-y-3">
                                <h3 class="font-montserrat font-bold text-[#F8FAFC] text-base leading-snug hover:text-[#ff5451] transition">
                                    <?php echo htmlspecialchars($t['name']); ?>
                                </h3>

                                <div class="space-y-1.5 text-xs text-[#94A3B8] font-inter">
                                    <div class="flex justify-between border-b border-[#1e293d] pb-1">
                                        <span>Players/Team:</span>
                                        <span class="font-mono font-bold text-[#F8FAFC]"><?php echo $t['max_squad_size_default']; ?></span>
                                    </div>
                                    <div class="flex justify-between border-b border-[#1e293d] pb-1">
                                        <span>Total Purse/Team:</span>
                                        <span class="font-mono font-bold text-[#ffb95f]"><?php echo number_format($t['total_purse_default']); ?></span>
                                    </div>
                                    <div class="flex justify-between border-b border-[#1e293d] pb-1">
                                        <span>Date:</span>
                                        <span class="font-mono text-[#F8FAFC]"><?php echo date('F d, Y', strtotime($t['created_at'])); ?></span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span>Time:</span>
                                        <span class="font-mono text-[#F8FAFC]"><?php echo date('h:i A', strtotime($t['created_at'])); ?> IST</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Action Button -->
                            <div class="pt-2">
                                <?php if ($t['id'] == 3 || strpos(strtolower($t['name']), 'auctionwala') !== false): ?>
                                    <a href="../public/register.php?t_id=<?php echo $t['id']; ?>" onclick="event.stopPropagation();" class="w-full bg-[#ff5451] hover:bg-[#ef4444] text-white font-montserrat font-bold text-xs py-2.5 rounded transition uppercase tracking-wider text-center block shadow">
                                        REGISTER NOW
                                    </a>
                                <?php else: ?>
                                    <a href="../public/index.php?t_id=<?php echo $t['id']; ?>" onclick="event.stopPropagation();" class="w-full border border-[#31394d] hover:border-[#ff5451] bg-[#171f33] text-[#F8FAFC] font-montserrat font-bold text-xs py-2.5 rounded transition uppercase tracking-wider text-center block">
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
        <section id="control-panel" class="space-y-6 pt-4 border-t border-[#1e293d]">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <h2 class="text-xl sm:text-2xl font-montserrat font-black text-[#F8FAFC] uppercase tracking-tight flex items-center gap-2">
                        🛠️ Organizer Control Center
                    </h2>
                    <p class="text-xs font-inter text-[#94A3B8]">Manage your private leagues, launch live auctioneer desk, and generate registration links.</p>
                </div>

                <button onclick="openCreateModal()" class="bg-[#ff5451] hover:bg-[#ef4444] text-white font-montserrat font-extrabold px-5 py-2.5 rounded-xl text-xs uppercase tracking-wider shadow-lg shadow-red-500/20 transition flex items-center justify-center gap-2 w-fit">
                    <i class="fa-solid fa-plus text-xs"></i> Provision New League
                </button>
            </div>

            <!-- Welcome Banner & Active Tournament Hero Card -->
            <div class="pro-card p-6 sm:p-8 rounded-2xl shadow-2xl relative overflow-hidden flex flex-col md:flex-row md:items-center justify-between gap-6 border-l-4 border-l-[#ff5451]">
                <div class="space-y-2 max-w-2xl">
                    <div class="flex items-center gap-2">
                        <span class="bg-[#22C55E]/15 text-[#22C55E] border border-[#22C55E]/30 text-[10px] font-mono font-bold px-3 py-1 rounded-full uppercase tracking-widest flex items-center gap-1.5 shadow-sm">
                            <span class="w-2 h-2 rounded-full bg-[#22C55E] animate-pulse"></span> Active League Environment
                        </span>
                        <span class="text-xs text-[#94A3B8] font-mono">Code: <strong class="text-[#ffb95f] font-mono font-bold"><?php echo htmlspecialchars($activeTournament['code'] ?? 'N/A'); ?></strong></span>
                    </div>

                    <h3 class="text-2xl sm:text-3xl font-montserrat font-black text-[#F8FAFC] uppercase tracking-tight">
                        <?php echo htmlspecialchars($activeTournament['name'] ?? 'No Active Tournament'); ?>
                    </h3>
                    <p class="text-xs text-[#dae2fd] font-inter leading-relaxed">
                        Default Team Purse: <strong class="text-[#ffb95f] font-mono font-bold">₹<?php echo number_format($activeTournament['total_purse_default'] ?? 10000); ?></strong> &bull; Max Squad Size: <strong class="text-[#7bd0ff] font-mono font-bold"><?php echo $activeTournament['max_squad_size_default'] ?? 11; ?> Players</strong>
                    </p>
                </div>

                <!-- Tournament Environment Switcher Button -->
                <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3 shrink-0">
                    <?php if (count($tournaments) > 1): ?>
                        <div class="relative">
                            <select onchange="window.location.href='index.php?switch_t=' + this.value" class="bg-[#060e20] border border-[#1e293d] rounded-xl px-4 py-2.5 text-xs text-[#F8FAFC] font-montserrat font-bold outline-none shadow-sm cursor-pointer hover:border-[#ff5451] transition">
                                <?php foreach ($tournaments as $tourn): ?>
                                    <option value="<?php echo $tourn['id']; ?>" <?php echo ((int)$tourn['id'] === (int)($activeTournament['id'] ?? 0)) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($tourn['name']); ?> (<?php echo htmlspecialchars($tourn['code']); ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    <?php endif; ?>
                    <button onclick="openCreateModal()" class="bg-[#ff5451] hover:bg-[#ef4444] text-white font-montserrat font-extrabold px-4 py-2.5 rounded-xl text-xs uppercase tracking-wider shadow-lg shadow-red-500/20 transition flex items-center justify-center gap-2">
                        <i class="fa-solid fa-plus text-xs"></i> New League
                    </button>
                    <?php if ($activeTournament): ?>
                        <button onclick="deleteLeague(<?php echo (int)$activeTournament['id']; ?>, '<?php echo htmlspecialchars(addslashes($activeTournament['name'])); ?>')" class="bg-[#060e20] hover:bg-red-500/20 text-[#94A3B8] hover:text-[#ff5451] border border-[#1e293d] hover:border-red-500/50 font-montserrat font-bold px-3 py-2.5 rounded-xl text-xs uppercase tracking-wider transition flex items-center justify-center gap-1.5" title="Delete Active League">
                            <i class="fa-solid fa-trash-can text-[#ff5451]"></i> <span class="hidden sm:inline">Delete</span>
                        </button>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Metric Stat Cards Row -->
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                <div class="pro-card p-5 rounded-2xl shadow-md flex items-center justify-between">
                    <div>
                        <span class="text-[10px] font-mono font-bold uppercase text-[#94A3B8] tracking-wider block">Franchise Teams</span>
                        <div class="text-2xl sm:text-3xl font-montserrat font-black text-[#F8FAFC] mt-1">
                            <?php echo $stats['teams_count']; ?>
                        </div>
                    </div>
                    <div class="w-11 h-11 rounded-xl bg-[#ffb95f]/15 border border-[#ffb95f]/30 flex items-center justify-center text-[#ffb95f]">
                        <i class="fa-solid fa-shield-halved text-xl"></i>
                    </div>
                </div>

                <div class="pro-card p-5 rounded-2xl shadow-md flex items-center justify-between">
                    <div>
                        <span class="text-[10px] font-mono font-bold uppercase text-[#94A3B8] tracking-wider block">Verified Players</span>
                        <div class="text-2xl sm:text-3xl font-montserrat font-black text-[#22C55E] mt-1">
                            <?php echo $stats['players_verified']; ?>
                        </div>
                    </div>
                    <div class="w-11 h-11 rounded-xl bg-[#22C55E]/15 border border-[#22C55E]/30 flex items-center justify-center text-[#22C55E]">
                        <i class="fa-solid fa-user-check text-xl"></i>
                    </div>
                </div>

                <div class="pro-card p-5 rounded-2xl shadow-md flex items-center justify-between">
                    <div>
                        <span class="text-[10px] font-mono font-bold uppercase text-[#94A3B8] tracking-wider block">Pending Approval</span>
                        <div class="text-2xl sm:text-3xl font-montserrat font-black text-[#ffb95f] mt-1">
                            <?php echo $stats['players_pending']; ?>
                        </div>
                    </div>
                    <div class="w-11 h-11 rounded-xl bg-[#ffb95f]/20 border border-[#ffb95f]/40 flex items-center justify-center text-[#ffb95f]">
                        <i class="fa-solid fa-clock-rotate-left text-xl"></i>
                    </div>
                </div>

                <div class="pro-card p-5 rounded-2xl shadow-md flex items-center justify-between">
                    <div>
                        <span class="text-[10px] font-mono font-bold uppercase text-[#94A3B8] tracking-wider block">Players Sold</span>
                        <div class="text-2xl sm:text-3xl font-montserrat font-black text-[#7bd0ff] mt-1">
                            <?php echo $stats['players_sold']; ?>
                        </div>
                    </div>
                    <div class="w-11 h-11 rounded-xl bg-[#7bd0ff]/15 border border-[#7bd0ff]/30 flex items-center justify-center text-[#7bd0ff]">
                        <i class="fa-solid fa-gavel text-xl"></i>
                    </div>
                </div>
            </div>

            <!-- Primary Quick Actions 4-Card Grid -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
                <!-- 1. Live Auctioneer Desk -->
                <a href="../admin/auction.php" class="pro-card pro-card-hover p-6 rounded-2xl border border-[#ff5451]/40 hover:border-[#ff5451] transition group flex flex-col justify-between shadow-xl">
                    <div>
                        <div class="w-12 h-12 rounded-2xl bg-[#ff5451]/20 border border-[#ff5451]/40 flex items-center justify-center mb-4 group-hover:scale-110 transition">
                            <i class="fa-solid fa-gavel text-[#ff5451] text-2xl"></i>
                        </div>
                        <h3 class="text-base font-montserrat font-bold text-[#F8FAFC] uppercase tracking-tight">Live Auctioneer Desk</h3>
                        <p class="text-xs text-[#94A3B8] mt-2 font-inter leading-relaxed">Control real-time bidding, bring players to block, pause, sell, or undo actions.</p>
                    </div>
                    <div class="mt-6 pt-4 border-t border-[#1e293d] flex items-center justify-between">
                        <span class="text-xs font-montserrat font-bold text-[#ff5451] uppercase tracking-wider">Launch Desk</span>
                        <i class="fa-solid fa-arrow-right text-[#ff5451] group-hover:translate-x-1 transition"></i>
                    </div>
                </a>

                <!-- 2. Team & Roster Manager -->
                <a href="../admin/index.php" class="pro-card pro-card-hover p-6 rounded-2xl border border-[#7bd0ff]/40 hover:border-[#7bd0ff] transition group flex flex-col justify-between shadow-xl">
                    <div>
                        <div class="w-12 h-12 rounded-2xl bg-[#7bd0ff]/20 border border-[#7bd0ff]/40 flex items-center justify-center mb-4 group-hover:scale-110 transition">
                            <i class="fa-solid fa-users-gear text-[#7bd0ff] text-2xl"></i>
                        </div>
                        <h3 class="text-base font-montserrat font-bold text-[#F8FAFC] uppercase tracking-tight">Teams & Purses</h3>
                        <p class="text-xs text-[#94A3B8] mt-2 font-inter leading-relaxed">Manage franchise accounts, create manager credentials, adjust purse balances.</p>
                    </div>
                    <div class="mt-6 pt-4 border-t border-[#1e293d] flex items-center justify-between">
                        <span class="text-xs font-montserrat font-bold text-[#7bd0ff] uppercase tracking-wider">Manage Teams</span>
                        <i class="fa-solid fa-arrow-right text-[#7bd0ff] group-hover:translate-x-1 transition"></i>
                    </div>
                </a>

                <!-- 3. Player Registration Link -->
                <div class="pro-card p-6 rounded-2xl border border-[#ffb95f]/40 transition group flex flex-col justify-between shadow-xl">
                    <div>
                        <div class="w-12 h-12 rounded-2xl bg-[#ffb95f]/20 border border-[#ffb95f]/40 flex items-center justify-center mb-4">
                            <i class="fa-solid fa-link text-[#ffb95f] text-2xl"></i>
                        </div>
                        <h3 class="text-base font-montserrat font-bold text-[#F8FAFC] uppercase tracking-tight">Player Registration Link</h3>
                        <p class="text-xs text-[#94A3B8] mt-2 font-inter leading-relaxed">Share this registration URL with players to join your league pool.</p>
                    </div>
                    <div class="mt-4 pt-3 border-t border-[#1e293d] space-y-2">
                        <input type="text" readonly value="<?php echo htmlspecialchars($regUrl); ?>" class="w-full bg-[#060e20] border border-[#1e293d] rounded-xl px-3 py-2 text-[11px] text-[#ffb95f] font-mono font-bold outline-none select-all truncate shadow-inner">
                        <button onclick="copyToClipboard('<?php echo htmlspecialchars($regUrl); ?>')" class="w-full bg-[#171f33] hover:bg-[#1e293d] text-[#F8FAFC] border border-[#1e293d] font-montserrat font-bold text-xs py-2 rounded-xl uppercase tracking-wider transition flex items-center justify-center gap-1.5 shadow-sm">
                            <i class="fa-solid fa-copy"></i> Copy Link
                        </button>
                    </div>
                </div>

                <!-- 4. Spectator Live View -->
                <div class="pro-card p-6 rounded-2xl border border-[#22C55E]/40 transition group flex flex-col justify-between shadow-xl">
                    <div>
                        <div class="w-12 h-12 rounded-2xl bg-[#22C55E]/20 border border-[#22C55E]/40 flex items-center justify-center mb-4">
                            <i class="fa-solid fa-tv text-[#22C55E] text-2xl"></i>
                        </div>
                        <h3 class="text-base font-montserrat font-bold text-[#F8FAFC] uppercase tracking-tight">Spectator Stream</h3>
                        <p class="text-xs text-[#94A3B8] mt-2 font-inter leading-relaxed">Share live bidding broadcast screen with audience and spectators.</p>
                    </div>
                    <div class="mt-4 pt-3 border-t border-[#1e293d] space-y-2">
                        <input type="text" readonly value="<?php echo htmlspecialchars($specUrl); ?>" class="w-full bg-[#060e20] border border-[#1e293d] rounded-xl px-3 py-2 text-[11px] text-[#22C55E] font-mono font-bold outline-none select-all truncate shadow-inner">
                        <a href="<?php echo htmlspecialchars($specUrl); ?>" target="_blank" class="w-full bg-[#22C55E] hover:bg-[#16a34a] text-slate-950 font-montserrat font-extrabold text-xs py-2 rounded-xl uppercase tracking-wider transition flex items-center justify-center gap-1.5 shadow-md">
                            <i class="fa-solid fa-arrow-up-right-from-square"></i> Open Stream
                        </a>
                    </div>
                </div>
            </div>

            <!-- My Created Leagues List Section -->
            <div id="my-leagues" class="pro-card p-6 rounded-2xl space-y-4">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-lg font-montserrat font-black text-[#F8FAFC] uppercase tracking-tight">
                            🏆 Your Created Leagues (<?php echo count($tournaments); ?>)
                        </h3>
                        <p class="text-xs text-[#94A3B8] font-inter">Switch between your active leagues or access their specific controls.</p>
                    </div>
                    <button onclick="openCreateModal()" class="text-xs font-mono font-bold text-[#ff5451] hover:text-[#ffb3ad] uppercase tracking-wider flex items-center gap-1 transition">
                        <i class="fa-solid fa-plus text-[10px]"></i> Create New
                    </button>
                </div>

                <?php if (empty($tournaments)): ?>
                    <div class="text-center py-6 text-[#94A3B8] text-xs">
                        No tournaments created yet. Click "Provision New League" above to get started!
                    </div>
                <?php else: ?>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        <?php foreach ($tournaments as $tItem): 
                            $isCurrent = ((int)$tItem['id'] === (int)($activeTournament['id'] ?? 0));
                        ?>
                            <div class="bg-[#060e20] border <?php echo $isCurrent ? 'border-[#ff5451] shadow-lg shadow-red-500/10' : 'border-[#1e293d]'; ?> rounded-xl p-4 flex flex-col justify-between space-y-3">
                                <div>
                                    <div class="flex items-center justify-between gap-2 mb-1">
                                        <span class="text-[10px] font-mono text-[#ffb95f] font-bold uppercase">Code: <?php echo htmlspecialchars($tItem['code']); ?></span>
                                        <?php if ($isCurrent): ?>
                                            <span class="bg-[#ff5451] text-white font-mono font-bold text-[8px] px-2 py-0.5 rounded uppercase tracking-wider">ACTIVE</span>
                                        <?php endif; ?>
                                    </div>
                                    <h4 class="font-montserrat font-bold text-[#F8FAFC] text-sm leading-snug">
                                        <?php echo htmlspecialchars($tItem['name']); ?>
                                    </h4>
                                    <div class="text-[11px] text-[#94A3B8] font-inter mt-1.5 flex gap-3">
                                        <span>Purse: <strong class="text-[#ffb95f] font-mono">₹<?php echo number_format($tItem['total_purse_default']); ?></strong></span>
                                        <span>Squad: <strong class="text-[#7bd0ff] font-mono"><?php echo $tItem['max_squad_size_default']; ?></strong></span>
                                    </div>
                                </div>

                                <div class="pt-2 border-t border-[#1e293d] flex items-center justify-between gap-2">
                                    <?php if (!$isCurrent): ?>
                                        <a href="index.php?switch_t=<?php echo $tItem['id']; ?>" class="flex-grow bg-[#171f33] hover:bg-[#1e293d] text-[#F8FAFC] font-montserrat font-bold text-[11px] py-1.5 rounded transition text-center uppercase tracking-wider">
                                            Switch to League
                                        </a>
                                    <?php else: ?>
                                        <a href="../admin/auction.php" class="flex-grow bg-[#ff5451] hover:bg-[#ef4444] text-white font-montserrat font-bold text-[11px] py-1.5 rounded transition text-center uppercase tracking-wider shadow">
                                            Open Auction Desk
                                        </a>
                                    <?php endif; ?>
                                    <button onclick="deleteLeague(<?php echo $tItem['id']; ?>, '<?php echo htmlspecialchars(addslashes($tItem['name'])); ?>')" class="bg-[#171f33] hover:bg-red-500/20 text-[#94A3B8] hover:text-[#ff5451] border border-[#1e293d] hover:border-red-500/40 px-2.5 py-1.5 rounded transition text-xs" title="Delete League">
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
    <div id="create-modal" class="fixed inset-0 z-50 bg-[#060e20]/85 backdrop-blur-md hidden flex items-center justify-center p-4">
        <div class="bg-[#131b2e] border border-[#1e293d] rounded-2xl p-6 sm:p-8 max-w-md w-full shadow-2xl relative text-[#F8FAFC]">
            <button onclick="closeCreateModal()" class="absolute top-4 right-4 text-[#94A3B8] hover:text-[#F8FAFC] text-lg">
                <i class="fa-solid fa-xmark"></i>
            </button>

            <h3 class="text-xl font-montserrat font-black uppercase text-[#F8FAFC] tracking-tight mb-1 flex items-center gap-2">
                <i class="fa-solid fa-trophy text-[#ffb95f]"></i> Provision New Tournament
            </h3>
            <p class="text-xs text-[#94A3B8] mb-6 font-inter">Create an isolated auction environment for your league.</p>

            <form id="create-tournament-form" onsubmit="handleCreateTournament(event)" class="space-y-4">
                <div>
                    <label class="block text-xs font-montserrat font-bold uppercase text-[#94A3B8] mb-1">Tournament Name *</label>
                    <input type="text" name="name" required placeholder="e.g. Premier League Season 4" class="w-full bg-[#060e20] border border-[#1e293d] rounded-xl px-4 py-2.5 text-xs text-[#F8FAFC] placeholder-slate-500 focus:border-[#ff5451] outline-none font-inter">
                </div>

                <div>
                    <label class="block text-xs font-montserrat font-bold uppercase text-[#94A3B8] mb-1">Tournament Unique Code (Slug)</label>
                    <input type="text" name="code" placeholder="e.g. pl-season-4 (auto-generated if empty)" class="w-full bg-[#060e20] border border-[#1e293d] rounded-xl px-4 py-2.5 text-xs text-[#F8FAFC] placeholder-slate-500 focus:border-[#ff5451] outline-none font-inter">
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-montserrat font-bold uppercase text-[#94A3B8] mb-1">Default Purse (₹)</label>
                        <input type="number" name="total_purse" value="10000" step="100" class="w-full bg-[#060e20] border border-[#1e293d] rounded-xl px-4 py-2.5 text-xs text-[#F8FAFC] outline-none font-mono font-bold">
                    </div>
                    <div>
                        <label class="block text-xs font-montserrat font-bold uppercase text-[#94A3B8] mb-1">Max Squad Size</label>
                        <input type="number" name="max_squad_size" value="11" class="w-full bg-[#060e20] border border-[#1e293d] rounded-xl px-4 py-2.5 text-xs text-[#F8FAFC] outline-none font-mono font-bold">
                    </div>
                </div>

                <div class="pt-4 flex gap-3">
                    <button type="button" onclick="closeCreateModal()" class="w-1/2 bg-[#171f33] hover:bg-[#1e293d] border border-[#1e293d] text-[#dae2fd] font-montserrat font-extrabold py-2.5 rounded-xl text-xs uppercase transition">
                        Cancel
                    </button>
                    <button type="submit" class="w-1/2 bg-[#ff5451] hover:bg-[#ef4444] text-white font-montserrat font-extrabold py-2.5 rounded-xl text-xs uppercase shadow-lg shadow-red-500/20 transition">
                        Create League
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Floating WhatsApp Support Button -->
    <a href="https://wa.me/917698767767" target="_blank" class="whatsapp-float" title="Contact Support on WhatsApp">
        <i class="fa-brands fa-whatsapp"></i>
    </a>

    <!-- Footer Section -->
    <footer class="bg-[#060e20] border-t border-[#1e293d] py-6 px-4 text-center text-xs text-[#94A3B8] font-inter mt-8">
        <div class="max-w-7xl mx-auto flex flex-col sm:flex-row items-center justify-between gap-4">
            <div class="flex items-center gap-2">
                <img src="../public/uploads/auctionwala_logo.png" alt="AuctionWala" class="h-6 object-contain">
                <span>© 2026 AuctionWala Organizer Control Center.</span>
            </div>
            <div class="flex gap-4">
                <a href="../public/landing.php" class="hover:text-white transition">Home</a>
                <a href="../public/today_auctions.php" class="hover:text-white transition">Live Arena</a>
                <a href="https://wa.me/917698767767" target="_blank" class="hover:text-[#22C55E] transition">Support</a>
            </div>
        </div>
    </footer>

    <!-- Script for Clipboard & Modal -->
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
