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
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AuctionWala — SaaS League Organizer Desk</title>
    <?php require_once '../public/components/ui_head.php'; ?>
</head>
<body class="text-slate-800 min-h-screen flex flex-col justify-between">

    <!-- Header Navigation -->
    <header class="w-full glass-panel border-b border-white/60 px-4 py-3 sm:px-6 sm:py-3.5 flex items-center justify-between sticky top-0 z-40 shadow-md">
        <div class="flex items-center gap-3">
            <a href="../public/landing.php" class="flex items-center gap-2">
                <img src="../public/uploads/auctionwala_logo.png" alt="AuctionWala Logo" class="h-8 sm:h-9 object-contain mix-blend-multiply">
            </a>
            <div class="h-6 w-px bg-slate-300 hidden sm:block"></div>
            <div class="hidden sm:block">
                <h1 class="text-sm font-black uppercase tracking-tight text-slate-900 leading-none">Organizer Portal</h1>
                <p class="text-[9px] text-slate-600 font-bold uppercase tracking-wider mt-0.5">Control Center</p>
            </div>
        </div>

        <!-- User Profile Pill & Quick Actions -->
        <div class="flex items-center gap-3">
            <!-- User Pill -->
            <div class="flex items-center gap-2.5 bg-white/90 border border-slate-300 px-3 py-1.5 rounded-xl shadow-sm">
                <div class="w-7 h-7 rounded-full bg-amber-500 text-slate-950 font-black text-xs flex items-center justify-center shadow-sm">
                    <?php echo strtoupper(substr($userName ?? 'U', 0, 1)); ?>
                </div>
                <div class="text-left">
                    <span class="text-xs font-black text-slate-900 block leading-tight truncate max-w-[120px] sm:max-w-[160px]"><?php echo htmlspecialchars($userName); ?></span>
                    <span class="text-[8px] uppercase tracking-widest font-black text-amber-800 block">Organizer</span>
                </div>
            </div>

            <button onclick="openCreateModal()" class="bg-slate-900 hover:bg-slate-800 text-white font-extrabold px-3.5 py-2 rounded-xl text-xs uppercase tracking-wider shadow-sm transition flex items-center gap-1.5">
                <i class="fa-solid fa-plus text-amber-400 text-xs"></i> <span class="hidden sm:inline">New League</span>
            </button>

            <a href="../public/logout.php" class="bg-white/90 hover:bg-red-50 hover:text-red-600 border border-slate-300 text-slate-700 w-9 h-9 sm:w-auto sm:h-auto sm:px-3 sm:py-2 rounded-xl text-xs font-extrabold transition flex items-center justify-center gap-1.5 shadow-sm" title="Logout">
                <i class="fa-solid fa-power-off text-red-500"></i> <span class="hidden sm:inline">Logout</span>
            </a>
        </div>
    </header>

    <!-- Main Bento Grid Container -->
    <main class="w-full max-w-7xl mx-auto px-4 py-8 flex-1 space-y-6">

        <!-- Welcome Banner & Active Tournament Hero Card -->
        <div class="glass-panel p-6 sm:p-8 rounded-2xl border border-white/60 shadow-2xl relative overflow-hidden flex flex-col md:flex-row md:items-center justify-between gap-6">
            <div class="space-y-2 max-w-2xl">
                <div class="flex items-center gap-2">
                    <span class="bg-emerald-500/15 text-emerald-800 border border-emerald-500/30 text-[10px] font-black px-3 py-1 rounded-full uppercase tracking-widest flex items-center gap-1.5 shadow-sm">
                        <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span> Active League Environment
                    </span>
                    <span class="text-xs text-slate-700 font-mono font-bold">Code: <strong class="text-amber-800 font-black"><?php echo htmlspecialchars($activeTournament['code'] ?? 'N/A'); ?></strong></span>
                </div>

                <h2 class="text-2xl sm:text-4xl font-black text-slate-900 uppercase tracking-tight">
                    <?php echo htmlspecialchars($activeTournament['name'] ?? 'No Active Tournament'); ?>
                </h2>
                <p class="text-xs text-slate-700 font-bold leading-relaxed">
                    Default Team Purse: <strong class="text-amber-800 font-mono">₹<?php echo number_format($activeTournament['total_purse_default'] ?? 10000); ?></strong> &bull; Max Squad Size: <strong class="text-slate-900 font-mono"><?php echo $activeTournament['max_squad_size_default'] ?? 11; ?> Players</strong>
                </p>
            </div>

            <!-- Tournament Environment Switcher Button -->
            <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3 shrink-0">
                <?php if (count($tournaments) > 1): ?>
                    <div class="relative">
                        <select onchange="window.location.href='index.php?switch_t=' + this.value" class="bg-white/90 border border-slate-300 rounded-xl px-4 py-2.5 text-xs text-slate-900 font-black outline-none shadow-sm cursor-pointer hover:border-amber-500">
                            <?php foreach ($tournaments as $tourn): ?>
                                <option value="<?php echo $tourn['id']; ?>" <?php echo ((int)$tourn['id'] === (int)($activeTournament['id'] ?? 0)) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($tourn['name']); ?> (<?php echo htmlspecialchars($tourn['code']); ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                <?php endif; ?>
                <button onclick="openCreateModal()" class="bg-slate-900 hover:bg-slate-800 text-white font-extrabold px-4 py-2.5 rounded-xl text-xs uppercase tracking-wider shadow-md transition flex items-center justify-center gap-2">
                    <i class="fa-solid fa-plus text-amber-400"></i> New League
                </button>
            </div>
        </div>

        <!-- Metric Stat Cards Row -->
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
            <div class="glass-card-subtle p-5 rounded-2xl shadow-sm flex items-center justify-between">
                <div>
                    <span class="text-[10px] font-black uppercase text-slate-700 tracking-wider block">Franchise Teams</span>
                    <div class="text-2xl sm:text-3xl font-black text-slate-900 mt-1 font-mono">
                        <?php echo $stats['teams_count']; ?>
                    </div>
                </div>
                <div class="w-11 h-11 rounded-xl bg-amber-500/15 border border-amber-500/30 flex items-center justify-center text-amber-700">
                    <i class="fa-solid fa-shield-halved text-xl"></i>
                </div>
            </div>

            <div class="glass-card-subtle p-5 rounded-2xl shadow-sm flex items-center justify-between">
                <div>
                    <span class="text-[10px] font-black uppercase text-slate-700 tracking-wider block">Verified Players</span>
                    <div class="text-2xl sm:text-3xl font-black text-emerald-800 mt-1 font-mono">
                        <?php echo $stats['players_verified']; ?>
                    </div>
                </div>
                <div class="w-11 h-11 rounded-xl bg-emerald-500/15 border border-emerald-500/30 flex items-center justify-center text-emerald-700">
                    <i class="fa-solid fa-user-check text-xl"></i>
                </div>
            </div>

            <div class="glass-card-subtle p-5 rounded-2xl shadow-sm flex items-center justify-between">
                <div>
                    <span class="text-[10px] font-black uppercase text-slate-700 tracking-wider block">Pending Approval</span>
                    <div class="text-2xl sm:text-3xl font-black text-amber-800 mt-1 font-mono">
                        <?php echo $stats['players_pending']; ?>
                    </div>
                </div>
                <div class="w-11 h-11 rounded-xl bg-amber-500/20 border border-amber-500/40 flex items-center justify-center text-amber-800">
                    <i class="fa-solid fa-clock-rotate-left text-xl"></i>
                </div>
            </div>

            <div class="glass-card-subtle p-5 rounded-2xl shadow-sm flex items-center justify-between">
                <div>
                    <span class="text-[10px] font-black uppercase text-slate-700 tracking-wider block">Players Sold</span>
                    <div class="text-2xl sm:text-3xl font-black text-cyan-800 mt-1 font-mono">
                        <?php echo $stats['players_sold']; ?>
                    </div>
                </div>
                <div class="w-11 h-11 rounded-xl bg-cyan-500/15 border border-cyan-500/30 flex items-center justify-center text-cyan-700">
                    <i class="fa-solid fa-gavel text-xl"></i>
                </div>
            </div>
        </div>

        <!-- Primary Quick Actions 4-Card Grid -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
            <!-- 1. Live Auctioneer Desk -->
            <a href="../admin/auction.php" class="glass-panel p-6 rounded-2xl border border-amber-500/40 hover:border-amber-500 transition group flex flex-col justify-between shadow-lg hover:-translate-y-1 duration-200">
                <div>
                    <div class="w-12 h-12 rounded-2xl bg-amber-500/20 border border-amber-500/40 flex items-center justify-center mb-4 group-hover:scale-110 transition">
                        <i class="fa-solid fa-gavel text-amber-800 text-2xl"></i>
                    </div>
                    <h3 class="text-base font-black text-slate-900 uppercase tracking-tight">Live Auctioneer Desk</h3>
                    <p class="text-xs text-slate-700 mt-2 font-bold leading-relaxed">Control real-time bidding, bring players to block, pause, sell, or undo actions.</p>
                </div>
                <div class="mt-6 pt-4 border-t border-slate-900/10 flex items-center justify-between">
                    <span class="text-xs font-black text-amber-800 uppercase tracking-wider">Launch Desk</span>
                    <i class="fa-solid fa-arrow-right text-amber-800 group-hover:translate-x-1 transition"></i>
                </div>
            </a>

            <!-- 2. Team & Roster Manager -->
            <a href="../admin/index.php" class="glass-panel p-6 rounded-2xl border border-white/60 hover:border-cyan-500/50 transition group flex flex-col justify-between shadow-lg hover:-translate-y-1 duration-200">
                <div>
                    <div class="w-12 h-12 rounded-2xl bg-cyan-500/20 border border-cyan-500/40 flex items-center justify-center mb-4 group-hover:scale-110 transition">
                        <i class="fa-solid fa-users-gear text-cyan-800 text-2xl"></i>
                    </div>
                    <h3 class="text-base font-black text-slate-900 uppercase tracking-tight">Teams & Purses</h3>
                    <p class="text-xs text-slate-700 mt-2 font-bold leading-relaxed">Manage franchise accounts, create manager credentials, adjust purse balances.</p>
                </div>
                <div class="mt-6 pt-4 border-t border-slate-900/10 flex items-center justify-between">
                    <span class="text-xs font-black text-cyan-800 uppercase tracking-wider">Manage Teams</span>
                    <i class="fa-solid fa-arrow-right text-cyan-800 group-hover:translate-x-1 transition"></i>
                </div>
            </a>

            <!-- 3. Player Registration Link -->
            <div class="glass-panel p-6 rounded-2xl border border-white/60 transition group flex flex-col justify-between shadow-lg">
                <div>
                    <div class="w-12 h-12 rounded-2xl bg-gold-500/20 border border-gold-500/40 flex items-center justify-center mb-4">
                        <i class="fa-solid fa-link text-amber-800 text-2xl"></i>
                    </div>
                    <h3 class="text-base font-black text-slate-900 uppercase tracking-tight">Player Registration Link</h3>
                    <p class="text-xs text-slate-700 mt-2 font-bold leading-relaxed">Share this registration URL with players to join your league pool.</p>
                </div>
                <div class="mt-4 pt-3 border-t border-slate-900/10 space-y-2">
                    <input type="text" readonly value="<?php echo htmlspecialchars($regUrl); ?>" class="w-full bg-white/90 border border-slate-300 rounded-xl px-3 py-1.5 text-[11px] text-amber-900 font-mono font-bold outline-none select-all truncate shadow-sm">
                    <button onclick="copyToClipboard('<?php echo htmlspecialchars($regUrl); ?>')" class="w-full bg-slate-900 hover:bg-slate-800 text-white font-extrabold text-xs py-2 rounded-xl uppercase tracking-wider transition flex items-center justify-center gap-1.5 shadow-sm">
                        <i class="fa-solid fa-copy"></i> Copy Link
                    </button>
                </div>
            </div>

            <!-- 4. Spectator Live View -->
            <div class="glass-panel p-6 rounded-2xl border border-white/60 transition group flex flex-col justify-between shadow-lg">
                <div>
                    <div class="w-12 h-12 rounded-2xl bg-emerald-500/20 border border-emerald-500/40 flex items-center justify-center mb-4">
                        <i class="fa-solid fa-tv text-emerald-800 text-2xl"></i>
                    </div>
                    <h3 class="text-base font-black text-slate-900 uppercase tracking-tight">Spectator Stream</h3>
                    <p class="text-xs text-slate-700 mt-2 font-bold leading-relaxed">Share live bidding broadcast screen with audience and spectators.</p>
                </div>
                <div class="mt-4 pt-3 border-t border-slate-900/10 space-y-2">
                    <input type="text" readonly value="<?php echo htmlspecialchars($specUrl); ?>" class="w-full bg-white/90 border border-slate-300 rounded-xl px-3 py-1.5 text-[11px] text-emerald-900 font-mono font-bold outline-none select-all truncate shadow-sm">
                    <a href="<?php echo htmlspecialchars($specUrl); ?>" target="_blank" class="w-full bg-emerald-700 hover:bg-emerald-800 text-white font-extrabold text-xs py-2 rounded-xl uppercase tracking-wider transition flex items-center justify-center gap-1.5 shadow-sm">
                        <i class="fa-solid fa-arrow-up-right-from-square"></i> Open Stream
                    </a>
                </div>
            </div>
        </div>
    </main>

    <!-- Create Tournament Modal -->
    <div id="create-modal" class="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-md hidden flex items-center justify-center p-4">
        <div class="bg-white border border-slate-200 rounded-2xl p-6 sm:p-8 max-w-md w-full shadow-2xl relative">
            <button onclick="closeCreateModal()" class="absolute top-4 right-4 text-slate-400 hover:text-slate-800 text-lg">
                <i class="fa-solid fa-xmark"></i>
            </button>

            <h3 class="text-xl font-black uppercase text-slate-900 tracking-tight mb-1 flex items-center gap-2">
                <i class="fa-solid fa-trophy text-gold-600"></i> Provision New Tournament
            </h3>
            <p class="text-xs text-slate-500 mb-6 font-medium">Create an isolated auction environment for your league.</p>

            <form id="create-tournament-form" onsubmit="handleCreateTournament(event)" class="space-y-4">
                <div>
                    <label class="block text-xs font-bold uppercase text-slate-700 mb-1">Tournament Name *</label>
                    <input type="text" name="name" required placeholder="e.g. Premier League Season 4" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 text-xs text-slate-900 placeholder-slate-400 focus:border-gold-500 outline-none font-medium">
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase text-slate-700 mb-1">Tournament Unique Code (Slug)</label>
                    <input type="text" name="code" placeholder="e.g. pl-season-4 (auto-generated if empty)" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 text-xs text-slate-900 placeholder-slate-400 focus:border-gold-500 outline-none font-medium">
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold uppercase text-slate-700 mb-1">Default Purse (₹)</label>
                        <input type="number" name="total_purse" value="10000" step="100" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 text-xs text-slate-900 outline-none font-mono font-bold">
                    </div>
                    <div>
                        <label class="block text-xs font-bold uppercase text-slate-700 mb-1">Max Squad Size</label>
                        <input type="number" name="max_squad_size" value="11" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 text-xs text-slate-900 outline-none font-mono font-bold">
                    </div>
                </div>

                <div class="pt-4 flex gap-3">
                    <button type="button" onclick="closeCreateModal()" class="w-1/2 bg-slate-100 hover:bg-slate-200 border border-slate-200 text-slate-700 font-extrabold py-2.5 rounded-xl text-xs uppercase transition">
                        Cancel
                    </button>
                    <button type="submit" class="w-1/2 bg-gradient-to-r from-gold-500 to-amber-500 hover:from-gold-400 hover:to-amber-400 text-slate-950 font-extrabold py-2.5 rounded-xl text-xs uppercase shadow-lg shadow-gold-500/20 transition">
                        Create League
                    </button>
                </div>
            </form>
        </div>
    </div>

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
                alert('Copied link to clipboard!');
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
