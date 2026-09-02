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

// Fetch all tournaments owned by this organizer
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

$regUrl = $baseUrl . '/public/register.php?t=' . ($activeTournament['code'] ?? 'smcl-2026');
$specUrl = $baseUrl . '/public/index.php?t=' . ($activeTournament['code'] ?? 'smcl-2026');
?>
<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AuctionWala — SaaS League Organizer Control Center</title>
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
            background: linear-gradient(180deg, rgba(6, 14, 32, 0.75) 0%, rgba(11, 19, 38, 0.94) 100%), url('../public/uploads/app_bg.jpg') center/cover no-repeat;
            z-index: -2;
            pointer-events: none;
        }

        .pro-card {
            background: rgba(19, 27, 46, 0.85);
            backdrop-filter: blur(16px);
            border: 1px solid #1e293d;
            transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .pro-card-hover:hover {
            border-color: #ff5451;
            box-shadow: 0 10px 30px -10px rgba(255, 84, 81, 0.25);
            transform: translateY(-2px);
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
                <h1 class="text-sm font-montserrat font-black uppercase tracking-tight text-[#F8FAFC] leading-none">Organizer Portal</h1>
                <p class="text-[9px] font-mono text-[#94A3B8] font-bold uppercase tracking-wider mt-0.5">Control Center</p>
            </div>
        </div>

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

    <!-- Main Bento Grid Container -->
    <main class="w-full max-w-7xl mx-auto px-4 py-8 flex-1 space-y-6">

        <!-- Welcome Banner & Active Tournament Hero Card -->
        <div class="pro-card p-6 sm:p-8 rounded-2xl shadow-2xl relative overflow-hidden flex flex-col md:flex-row md:items-center justify-between gap-6">
            <div class="space-y-2 max-w-2xl">
                <div class="flex items-center gap-2">
                    <span class="bg-[#22C55E]/15 text-[#22C55E] border border-[#22C55E]/30 text-[10px] font-mono font-bold px-3 py-1 rounded-full uppercase tracking-widest flex items-center gap-1.5 shadow-sm">
                        <span class="w-2 h-2 rounded-full bg-[#22C55E] animate-pulse"></span> Active League Environment
                    </span>
                    <span class="text-xs text-[#94A3B8] font-mono">Code: <strong class="text-[#ffb95f] font-mono font-bold"><?php echo htmlspecialchars($activeTournament['code'] ?? 'N/A'); ?></strong></span>
                </div>

                <h2 class="text-2xl sm:text-4xl font-montserrat font-black text-[#F8FAFC] uppercase tracking-tight">
                    <?php echo htmlspecialchars($activeTournament['name'] ?? 'No Active Tournament'); ?>
                </h2>
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
    </main>

    <!-- Create Tournament Modal -->
    <div id="create-modal" class="fixed inset-0 z-50 bg-[#060e20]/80 backdrop-blur-md hidden flex items-center justify-center p-4">
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
    </script>
</body>
</html>
