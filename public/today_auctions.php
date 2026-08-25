<?php
// public/today_auctions.php — Dedicated Page for Today's Live Player Auctions
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/auth.php';

// Fetch categorized tournaments
$categorized = get_categorized_tournaments($pdo);
$liveAuctions = $categorized['live'];

$uploadPath = "uploads/";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Today's Live Player Auctions — AuctionWala</title>
    <meta name="description" content="Watch Today's Live Cricket & Sports Player Auctions in real time on AuctionWala with live bidding, franchise purses, and instant squad updates.">
    <?php require_once __DIR__ . '/components/ui_head.php'; ?>
</head>
<body class="text-slate-900 min-h-screen flex flex-col justify-between selection:bg-red-500 selection:text-white">

    <!-- Top Navigation Header -->
    <header class="w-full glass-panel border-b border-white/60 px-4 py-3 sm:px-8 sm:py-4 flex items-center justify-between sticky top-0 z-40 shadow-md">
        <div class="flex items-center gap-3">
            <a href="landing.php" class="flex items-center gap-2">
                <img src="<?php echo $uploadPath; ?>auctionwala_logo.png" alt="AuctionWala Logo" class="h-8 sm:h-9 object-contain mix-blend-multiply">
            </a>
            <div class="h-6 w-px bg-slate-300 hidden sm:block"></div>
            <div class="hidden sm:block">
                <h1 class="text-xs font-black uppercase tracking-tight text-slate-900 leading-none">Today's Auctions</h1>
                <p class="text-[9px] text-red-600 font-extrabold uppercase tracking-wider mt-0.5 flex items-center gap-1">
                    <span class="w-1.5 h-1.5 rounded-full bg-red-600 animate-ping"></span> Live Bidding Desk
                </p>
            </div>
        </div>

        <!-- Right Header Nav Links -->
        <div class="flex items-center gap-2 sm:gap-3">
            <a href="landing.php" class="text-xs font-black text-slate-700 hover:text-slate-900 uppercase tracking-wider hidden md:inline-flex items-center gap-1 px-3 py-1.5 rounded-lg hover:bg-slate-100 transition">
                <i class="fa-solid fa-house text-slate-500"></i> Home
            </a>
            <a href="today_auctions.php" class="text-xs font-black text-red-600 bg-red-50 border border-red-200 uppercase tracking-wider px-3 py-1.5 rounded-lg flex items-center gap-1.5">
                <i class="fa-solid fa-circle text-[8px] animate-pulse text-red-600"></i> Live Today
            </a>
            <a href="upcoming_auctions.php" class="text-xs font-black text-slate-700 hover:text-slate-900 uppercase tracking-wider px-3 py-1.5 rounded-lg hover:bg-slate-100 transition hidden sm:inline-flex items-center gap-1">
                <i class="fa-solid fa-calendar-days text-amber-600"></i> Upcoming
            </a>
            <a href="register.php" class="text-xs font-black text-slate-700 hover:text-slate-900 uppercase tracking-wider hidden lg:inline-flex items-center gap-1 px-3 py-1.5 rounded-lg hover:bg-slate-100 transition">
                <i class="fa-solid fa-id-card text-slate-500"></i> Register
            </a>
            <?php if (isLoggedIn()): ?>
                <a href="../organizer/index.php" class="bg-slate-900 hover:bg-slate-800 text-white font-black text-xs px-4 py-2 rounded-xl transition shadow-md flex items-center gap-2">
                    <i class="fa-solid fa-gauge-high text-amber-400"></i> Dashboard
                </a>
            <?php else: ?>
                <a href="login.php" class="bg-amber-500 hover:bg-amber-400 text-slate-950 font-black text-xs px-4 py-2 rounded-xl transition shadow-md flex items-center gap-2 uppercase tracking-wider">
                    <i class="fa-solid fa-right-to-bracket text-slate-950"></i> Host / Login
                </a>
            <?php endif; ?>
        </div>
    </header>

    <!-- Main Content -->
    <main class="flex-grow p-4 sm:p-8 max-w-7xl w-full mx-auto space-y-8 relative">

        <!-- Hero Header -->
        <div class="glass-panel rounded-3xl p-6 sm:p-10 border-2 border-red-500/30 shadow-2xl relative overflow-hidden text-center sm:text-left flex flex-col sm:flex-row items-center justify-between gap-6">
            <div class="space-y-2 max-w-2xl">
                <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-red-600 text-white text-[10px] font-black uppercase tracking-wider shadow-sm">
                    <span class="w-2 h-2 rounded-full bg-white animate-ping"></span> Live Broadcast Stream
                </div>
                <h2 class="text-2xl sm:text-4xl font-black text-slate-900 uppercase tracking-tight leading-tight">
                    Today's Live Player Auctions
                </h2>
                <p class="text-xs sm:text-sm text-slate-700 font-extrabold">
                    Watch real-time player bidding, view current active bids, franchise purse allocations, and live squad roster updates.
                </p>
            </div>

            <!-- Total Live Pill -->
            <div class="bg-slate-900 text-white p-5 rounded-2xl text-center shadow-xl shrink-0 border border-slate-700 w-full sm:w-48">
                <div class="text-3xl font-black text-red-500 leading-none flex items-center justify-center gap-2">
                    <span class="w-3 h-3 rounded-full bg-red-500 animate-ping"></span>
                    <?php echo count($liveAuctions); ?>
                </div>
                <div class="text-[10px] font-black uppercase tracking-wider text-slate-400 mt-1">Auctions Live Now</div>
            </div>
        </div>

        <!-- Search Bar -->
        <div class="flex items-center justify-between gap-4">
            <h3 class="text-lg font-black text-slate-900 uppercase tracking-tight flex items-center gap-2">
                <i class="fa-solid fa-broadcast-tower text-red-600"></i> Active Bidding Desks
            </h3>
            <div class="relative w-full sm:w-80">
                <i class="fa-solid fa-magnifying-glass absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                <input type="text" id="live-search" onkeyup="searchLiveAuctions()" placeholder="Search active auction by name..."
                       class="w-full bg-white/95 border-2 border-slate-300 rounded-xl pl-9 pr-4 py-2 text-xs text-slate-900 font-extrabold placeholder-slate-400 focus:outline-none focus:border-red-500 shadow-sm transition">
            </div>
        </div>

        <!-- Live Auctions Grid -->
        <?php if (empty($liveAuctions)): ?>
            <div class="glass-panel rounded-3xl p-12 border border-white/60 text-center shadow-xl my-8">
                <div class="w-16 h-16 rounded-full bg-red-100 text-red-600 flex items-center justify-center mx-auto mb-4 text-2xl">
                    <i class="fa-solid fa-tv"></i>
                </div>
                <h4 class="text-lg font-black text-slate-900 uppercase">No Auctions Live Right Now</h4>
                <p class="text-xs text-slate-600 font-extrabold mt-1 max-w-md mx-auto">
                    There are no live bidding sessions currently active. Check out upcoming auctions scheduled for future dates!
                </p>
                <div class="mt-6">
                    <a href="upcoming_auctions.php" class="bg-slate-900 hover:bg-slate-800 text-white font-black text-xs uppercase tracking-wider px-6 py-3 rounded-xl transition shadow-md inline-flex items-center gap-2">
                        <i class="fa-solid fa-calendar-days text-amber-400"></i> View Upcoming Auctions
                    </a>
                </div>
            </div>
        <?php else: ?>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6" id="live-auctions-container">
                <?php foreach ($liveAuctions as $t): ?>
                    <div class="live-card glass-panel rounded-2xl p-6 border-2 border-red-500/50 shadow-xl flex flex-col justify-between space-y-4 hover:border-red-600 transition duration-300 relative overflow-hidden"
                         data-name="<?php echo htmlspecialchars(strtolower($t['name'])); ?>">
                        
                        <!-- Top Card Info -->
                        <div class="space-y-3">
                            <div class="flex items-center justify-between">
                                <span class="bg-red-600 text-white font-black text-[10px] px-3 py-1 rounded-full uppercase tracking-wider flex items-center gap-1.5 shadow-sm">
                                    <i class="fa-solid fa-circle text-[8px] animate-pulse"></i> Live Bidding
                                </span>
                                <span class="text-[10px] font-mono font-black text-slate-600 uppercase bg-slate-100 px-2.5 py-0.5 rounded border border-slate-300">
                                    Code: <?php echo htmlspecialchars($t['code']); ?>
                                </span>
                            </div>

                            <div class="flex items-center gap-3">
                                <div class="w-12 h-12 rounded-xl overflow-hidden border border-slate-300 bg-white p-1 shrink-0 shadow-sm">
                                    <img src="<?php echo $uploadPath; ?><?php echo htmlspecialchars($t['logo'] ?: 'auctionwala_logo.png'); ?>" alt="Logo" class="w-full h-full object-contain">
                                </div>
                                <div>
                                    <h4 class="font-black text-slate-900 text-base leading-tight hover:text-red-700 transition">
                                        <?php echo htmlspecialchars($t['name']); ?>
                                    </h4>
                                    <p class="text-xs text-slate-600 font-extrabold mt-0.5">Purse Cap: ₹<?php echo number_format($t['total_purse_default']); ?></p>
                                </div>
                            </div>
                        </div>

                        <!-- Live Stats Bar -->
                        <div class="grid grid-cols-3 gap-2 bg-slate-900 text-white p-3.5 rounded-xl text-center shadow-inner">
                            <div>
                                <div class="text-sm font-black font-mono text-amber-400"><?php echo $t['player_count']; ?></div>
                                <div class="text-[8px] font-bold text-slate-400 uppercase">Players</div>
                            </div>
                            <div>
                                <div class="text-sm font-black font-mono text-white"><?php echo $t['team_count']; ?></div>
                                <div class="text-[8px] font-bold text-slate-400 uppercase">Teams</div>
                            </div>
                            <div>
                                <div class="text-sm font-black font-mono text-emerald-400"><?php echo $t['sold_player_count']; ?></div>
                                <div class="text-[8px] font-bold text-slate-400 uppercase">Sold</div>
                            </div>
                        </div>

                        <!-- Action CTA Buttons -->
                        <div class="pt-2 flex gap-2">
                            <a href="index.php?t_id=<?php echo $t['id']; ?>" class="flex-1 bg-red-600 hover:bg-red-500 text-white font-black text-xs py-3 px-3 rounded-xl transition shadow-md text-center flex items-center justify-center gap-1.5 uppercase tracking-wider">
                                <i class="fa-solid fa-tv text-xs"></i> Join Live Stream
                            </a>
                            <a href="register.php?t_id=<?php echo $t['id']; ?>" class="bg-white border border-slate-300 hover:bg-slate-100 text-slate-900 font-extrabold text-xs py-3 px-3 rounded-xl transition shadow-sm flex items-center justify-center gap-1">
                                <i class="fa-solid fa-id-card text-amber-600"></i> Register
                            </a>
                        </div>

                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

    </main>

    <!-- Footer -->
    <footer class="w-full glass-panel border-t border-white/60 px-6 py-5 text-center text-xs text-slate-700 font-extrabold mt-12 shadow-md">
        <div class="max-w-7xl mx-auto flex flex-col sm:flex-row items-center justify-between gap-3">
            <div class="flex items-center gap-2">
                <img src="<?php echo $uploadPath; ?>auctionwala_logo.png" alt="AuctionWala Logo" class="h-6 object-contain mix-blend-multiply">
                <span>© 2026 AuctionWala — SaaS Sports Auction Platform.</span>
            </div>
            <div class="flex items-center gap-4">
                <a href="landing.php" class="hover:text-slate-900 transition">Home</a>
                <a href="today_auctions.php" class="hover:text-slate-900 transition">Today's Live</a>
                <a href="upcoming_auctions.php" class="hover:text-slate-900 transition">Upcoming</a>
                <a href="register.php" class="hover:text-slate-900 transition">Register Player</a>
            </div>
        </div>
    </footer>

    <!-- Search Script -->
    <script>
    function searchLiveAuctions() {
        const query = document.getElementById('live-search').value.toLowerCase().trim();
        const cards = document.querySelectorAll('.live-card');

        cards.forEach(card => {
            const name = card.getAttribute('data-name') || '';
            if (query === '' || name.includes(query)) {
                card.style.display = 'flex';
            } else {
                card.style.display = 'none';
            }
        });
    }
    </script>
</body>
</html>
