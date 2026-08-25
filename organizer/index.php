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
    <header class="w-full glass-panel border-b border-slate-200 px-4 py-3 sm:px-6 sm:py-4 flex items-center justify-between sticky top-0 z-40">
        <div class="flex items-center gap-3">
            <a href="../public/landing.php">
                <img src="../public/uploads/auctionwala_logo.png" alt="AuctionWala Logo" class="h-9 object-contain mix-blend-multiply">
            </a>
            <div>
                <h1 class="text-lg font-black uppercase tracking-tight text-slate-900 flex items-center gap-2">
                    AuctionWala SaaS 
                    <span class="bg-gold-500/20 text-gold-700 border border-gold-500/30 text-[9px] font-extrabold px-2 py-0.5 rounded-full tracking-widest uppercase">Organizer</span>
                    <span class="bg-amber-50 text-amber-700 border border-amber-200 text-[9px] font-extrabold px-2 py-0.5 rounded-full tracking-widest uppercase flex items-center gap-1">
                        <i class="fa-solid fa-fire text-amber-600"></i> Firebase Connected
                    </span>
                </h1>
                <p class="text-xs text-slate-500 font-medium">Logged in as <strong class="text-gold-700"><?php echo htmlspecialchars($userName); ?></strong> (<?php echo htmlspecialchars($userEmail); ?>)</p>
            </div>
        </div>

        <div class="flex items-center gap-3">
            <button onclick="openCreateModal()" class="bg-gradient-to-r from-gold-500 to-amber-500 hover:from-gold-400 hover:to-amber-400 text-zinc-950 font-extrabold px-4 py-2 rounded-xl text-xs uppercase tracking-wider shadow-md transition flex items-center gap-2">
                <i class="fa-solid fa-plus text-sm"></i> New Tournament
            </button>
            <a href="../public/logout.php" class="bg-slate-100 hover:bg-red-50 hover:text-red-600 border border-slate-200 text-slate-600 px-3 py-2 rounded-xl text-xs font-bold transition flex items-center gap-1.5">
                <i class="fa-solid fa-right-from-bracket"></i> Logout
            </a>
        </div>
    </header>

    <!-- Main Bento Grid Container -->
    <main class="w-full max-w-7xl mx-auto px-4 py-8 flex-1 space-y-6">

        <!-- Active Tournament Banner & Selector Bento Row -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

            <!-- Active Tournament Hero Card (2 cols) -->
            <div class="lg:col-span-2 bento-card p-6 border border-slate-200 relative overflow-hidden flex flex-col justify-between shadow-sm bg-white">
                <div class="absolute -right-10 -bottom-10 opacity-5 pointer-events-none">
                    <i class="fa-solid fa-crown text-[200px] text-gold-500"></i>
                </div>

                <div>
                    <div class="flex items-center justify-between gap-4 mb-4">
                        <span class="bg-emerald-50 text-emerald-700 border border-emerald-200 text-[10px] font-extrabold px-3 py-1 rounded-full uppercase tracking-widest flex items-center gap-1.5 shadow-sm">
                            <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span> Active Environment
                        </span>
                        <span class="text-xs text-slate-500 font-mono">Code: <strong class="text-gold-700"><?php echo htmlspecialchars($activeTournament['code'] ?? 'N/A'); ?></strong></span>
                    </div>

                    <h2 class="text-2xl sm:text-3xl font-black text-slate-900 uppercase tracking-tight mb-2">
                        <?php echo htmlspecialchars($activeTournament['name'] ?? 'No Active Tournament'); ?>
                    </h2>
                    <p class="text-xs text-slate-600 leading-relaxed mb-6 font-medium">
                        Provisioned multi-tenant environment with automated purse limits (₹<?php echo number_format($activeTournament['total_purse_default'] ?? 10000); ?>) and max squad size (<?php echo $activeTournament['max_squad_size_default'] ?? 11; ?> players per team).
                    </p>
                </div>

                <!-- Shareable Link Boxes -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 pt-4 border-t border-slate-200">
                    <div class="bg-slate-50 p-3 rounded-xl border border-slate-200 flex flex-col justify-between shadow-sm">
                        <span class="text-[10px] text-slate-500 uppercase font-bold tracking-wider mb-1 flex items-center gap-1">
                            <i class="fa-solid fa-link text-gold-600"></i> Player Registration URL
                        </span>
                        <div class="flex items-center gap-2">
                            <input type="text" readonly value="<?php echo htmlspecialchars($regUrl); ?>" class="bg-transparent text-xs text-gold-700 font-mono font-bold w-full outline-none select-all truncate">
                            <button onclick="copyToClipboard('<?php echo htmlspecialchars($regUrl); ?>')" class="text-slate-500 hover:text-slate-800 text-xs px-2 py-1 bg-white border border-slate-200 rounded hover:bg-slate-100 transition shadow-sm">
                                <i class="fa-solid fa-copy"></i>
                            </button>
                        </div>
                    </div>

                    <div class="bg-slate-50 p-3 rounded-xl border border-slate-200 flex flex-col justify-between shadow-sm">
                        <span class="text-[10px] text-slate-500 uppercase font-bold tracking-wider mb-1 flex items-center gap-1">
                            <i class="fa-solid fa-tv text-cyan-600"></i> Spectator Live View
                        </span>
                        <div class="flex items-center gap-2">
                            <input type="text" readonly value="<?php echo htmlspecialchars($specUrl); ?>" class="bg-transparent text-xs text-cyan-700 font-mono font-bold w-full outline-none select-all truncate">
                            <a href="<?php echo htmlspecialchars($specUrl); ?>" target="_blank" class="text-slate-500 hover:text-slate-800 text-xs px-2 py-1 bg-white border border-slate-200 rounded hover:bg-slate-100 transition shadow-sm">
                                <i class="fa-solid fa-arrow-up-right-from-square"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tournament Environment Switcher Card (1 col) -->
            <div class="bento-card p-6 flex flex-col justify-between bg-white border border-slate-200 shadow-sm">
                <div>
                    <h3 class="text-xs font-extrabold uppercase text-slate-900 tracking-wider mb-4 flex items-center gap-2">
                        <i class="fa-solid fa-layer-group text-gold-600"></i> Your Tournaments (<?php echo count($tournaments); ?>)
                    </h3>

                    <div class="space-y-2 max-h-64 overflow-y-auto pr-1">
                        <?php foreach ($tournaments as $tourn): ?>
                            <?php $isSelected = ((int)$tourn['id'] === (int)($activeTournament['id'] ?? 0)); ?>
                            <a href="index.php?switch_t=<?php echo $tourn['id']; ?>" class="flex items-center justify-between p-3 rounded-xl border transition <?php echo $isSelected ? 'bg-amber-50 border-gold-400 text-slate-900 shadow-sm' : 'bg-slate-50 border-slate-200 text-slate-600 hover:border-slate-300 hover:bg-slate-100'; ?>">
                                <div class="truncate">
                                    <h4 class="text-xs font-extrabold truncate text-slate-900"><?php echo htmlspecialchars($tourn['name']); ?></h4>
                                    <span class="text-[10px] font-mono text-slate-500">Code: <?php echo htmlspecialchars($tourn['code']); ?></span>
                                </div>
                                <?php if ($isSelected): ?>
                                    <span class="text-gold-700 text-xs"><i class="fa-solid fa-circle-check"></i></span>
                                <?php endif; ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>

                <button onclick="openCreateModal()" class="w-full mt-4 bg-slate-100 hover:bg-slate-200 border border-slate-200 text-xs font-extrabold text-slate-800 py-2.5 rounded-xl transition flex items-center justify-center gap-2 shadow-sm">
                    <i class="fa-solid fa-plus text-gold-600"></i> Provision Another League
                </button>
            </div>
        </div>

        <!-- Metric Stat Cards Bento Row -->
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
            <div class="bento-card p-5 bg-white border border-slate-200 shadow-sm">
                <span class="text-[10px] font-extrabold uppercase text-slate-500 tracking-wider">Franchise Teams</span>
                <div class="text-2xl font-black text-slate-900 mt-1 flex items-center gap-2">
                    <i class="fa-solid fa-shield-halved text-gold-600 text-lg"></i> <?php echo $stats['teams_count']; ?>
                </div>
            </div>

            <div class="bento-card p-5 bg-white border border-slate-200 shadow-sm">
                <span class="text-[10px] font-extrabold uppercase text-slate-500 tracking-wider">Verified Players</span>
                <div class="text-2xl font-black text-emerald-700 mt-1 flex items-center gap-2">
                    <i class="fa-solid fa-user-check text-lg"></i> <?php echo $stats['players_verified']; ?>
                </div>
            </div>

            <div class="bento-card p-5 bg-white border border-slate-200 shadow-sm">
                <span class="text-[10px] font-extrabold uppercase text-slate-500 tracking-wider">Pending Verification</span>
                <div class="text-2xl font-black text-amber-600 mt-1 flex items-center gap-2">
                    <i class="fa-solid fa-clock-rotate-left text-lg"></i> <?php echo $stats['players_pending']; ?>
                </div>
            </div>

            <div class="bento-card p-5 bg-white border border-slate-200 shadow-sm">
                <span class="text-[10px] font-extrabold uppercase text-slate-500 tracking-wider">Players Sold</span>
                <div class="text-2xl font-black text-cyan-700 mt-1 flex items-center gap-2">
                    <i class="fa-solid fa-gavel text-lg"></i> <?php echo $stats['players_sold']; ?>
                </div>
            </div>
        </div>

        <!-- Quick Action Hub Bento Row -->
        <div class="bento-card p-6 bg-white border border-slate-200 shadow-sm">
            <h3 class="text-xs font-extrabold uppercase text-slate-900 tracking-wider mb-4 flex items-center gap-2">
                <i class="fa-solid fa-rocket text-gold-600"></i> Auction Operations Control Desk
            </h3>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <a href="../admin/auction.php" class="bg-slate-50 hover:bg-amber-50 p-5 rounded-xl border border-slate-200 hover:border-gold-400 transition group flex flex-col justify-between shadow-sm">
                    <div>
                        <div class="w-10 h-10 rounded-xl bg-gold-50 border border-gold-200 flex items-center justify-center mb-3 group-hover:scale-110 transition">
                            <i class="fa-solid fa-gavel text-gold-700 text-lg"></i>
                        </div>
                        <h4 class="text-sm font-black text-slate-900 uppercase tracking-tight">Live Auctioneer Desk</h4>
                        <p class="text-xs text-slate-600 mt-1 font-medium">Control real-time bidding, bring players to block, pause, sell, or undo actions.</p>
                    </div>
                    <span class="text-xs font-extrabold text-gold-700 uppercase tracking-wider mt-4 flex items-center gap-1">
                        Launch Console <i class="fa-solid fa-arrow-right group-hover:translate-x-1 transition"></i>
                    </span>
                </a>

                <a href="../admin/index.php" class="bg-slate-50 hover:bg-cyan-50 p-5 rounded-xl border border-slate-200 hover:border-cyan-400 transition group flex flex-col justify-between shadow-sm">
                    <div>
                        <div class="w-10 h-10 rounded-xl bg-cyan-50 border border-cyan-200 flex items-center justify-center mb-3 group-hover:scale-110 transition">
                            <i class="fa-solid fa-users-gear text-cyan-700 text-lg"></i>
                        </div>
                        <h4 class="text-sm font-black text-slate-900 uppercase tracking-tight">Team & Roster Manager</h4>
                        <p class="text-xs text-slate-600 mt-1 font-medium">Manage franchise accounts, create manager credentials, adjust purse balances.</p>
                    </div>
                    <span class="text-xs font-extrabold text-cyan-700 uppercase tracking-wider mt-4 flex items-center gap-1">
                        Manage Teams <i class="fa-solid fa-arrow-right group-hover:translate-x-1 transition"></i>
                    </span>
                </a>

                <a href="../admin/index.php#players" class="bg-slate-50 hover:bg-emerald-50 p-5 rounded-xl border border-slate-200 hover:border-emerald-400 transition group flex flex-col justify-between shadow-sm">
                    <div>
                        <div class="w-10 h-10 rounded-xl bg-emerald-50 border border-emerald-200 flex items-center justify-center mb-3 group-hover:scale-110 transition">
                            <i class="fa-solid fa-id-card text-emerald-700 text-lg"></i>
                        </div>
                        <h4 class="text-sm font-black text-slate-900 uppercase tracking-tight">Player Payment Verification</h4>
                        <p class="text-xs text-slate-600 mt-1 font-medium">Review player UTR payment receipts and approve registrations into pool.</p>
                    </div>
                    <span class="text-xs font-extrabold text-emerald-700 uppercase tracking-wider mt-4 flex items-center gap-1">
                        Verify Receipts <i class="fa-solid fa-arrow-right group-hover:translate-x-1 transition"></i>
                    </span>
                </a>
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
