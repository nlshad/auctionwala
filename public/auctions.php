<?php
// public/auctions.php — Today's & Upcoming Player Auctions Hub
require_once __DIR__ . '/../config/db.php';

// Fetch categorized tournaments
$categorized = get_categorized_tournaments($pdo);
$liveAuctions = $categorized['live'];
$upcomingAuctions = $categorized['upcoming'];
$completedAuctions = $categorized['completed'];
$allTournaments = $categorized['all'];

$uploadPath = "uploads/";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Today's & Upcoming Player Auctions — AuctionWala</title>
    <meta name="description" content="Explore live player auctions, upcoming cricket league bidding events, registered franchises, and player registrations on AuctionWala.">
    <?php require_once __DIR__ . '/components/ui_head.php'; ?>
</head>
<body class="text-slate-900 min-h-screen flex flex-col justify-between selection:bg-amber-500 selection:text-slate-950">

    <!-- Top Navigation Header -->
    <header class="w-full glass-panel border-b border-white/60 px-4 py-3 sm:px-8 sm:py-4 flex items-center justify-between sticky top-0 z-40 shadow-md">
        <div class="flex items-center gap-3">
            <a href="landing.php" class="flex items-center gap-2">
                <img src="<?php echo $uploadPath; ?>auctionwala_logo.png" alt="AuctionWala Logo" class="h-8 sm:h-9 object-contain mix-blend-multiply">
            </a>
            <div class="h-6 w-px bg-slate-300 hidden sm:block"></div>
            <div class="hidden sm:block">
                <h1 class="text-xs font-black uppercase tracking-tight text-slate-900 leading-none">Auctions Hub</h1>
                <p class="text-[9px] text-slate-600 font-extrabold uppercase tracking-wider mt-0.5">Live & Scheduled Leagues</p>
            </div>
        </div>

        <!-- Right Header Nav Links & Login Button -->
        <div class="flex items-center gap-3">
            <a href="landing.php" class="text-xs font-black text-slate-700 hover:text-slate-900 uppercase tracking-wider hidden md:inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg hover:bg-slate-100 transition">
                <i class="fa-solid fa-house text-slate-500"></i> Home
            </a>
            <a href="register.php" class="text-xs font-black text-slate-700 hover:text-slate-900 uppercase tracking-wider hidden sm:inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg hover:bg-slate-100 transition">
                <i class="fa-solid fa-id-card text-amber-600"></i> Register Player
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

    <!-- Main Content Container -->
    <main class="flex-grow p-4 sm:p-8 max-w-7xl w-full mx-auto space-y-8 relative">

        <!-- Hero Header Card -->
        <div class="glass-panel rounded-3xl p-6 sm:p-10 border border-white/80 shadow-2xl relative overflow-hidden text-center sm:text-left flex flex-col sm:flex-row items-center justify-between gap-6">
            <div class="space-y-2 max-w-2xl">
                <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-amber-500/20 border border-amber-500/40 text-amber-900 text-[10px] font-black uppercase tracking-wider">
                    <i class="fa-solid fa-gavel text-amber-600 animate-bounce"></i> Real-time Sports Auction Platform
                </div>
                <h2 class="text-2xl sm:text-4xl font-black text-slate-900 uppercase tracking-tight leading-tight">
                    Player Auctions Hub
                </h2>
                <p class="text-xs sm:text-sm text-slate-700 font-extrabold">
                    Track Today's Live Bidding Events, Upcoming Tournaments, Registered Franchises & Official Squad Rosters in Real Time.
                </p>
            </div>

            <!-- Quick Counter Pills -->
            <div class="grid grid-cols-3 gap-3 w-full sm:w-auto shrink-0">
                <div class="bg-white/90 border border-slate-300 rounded-2xl p-3 sm:p-4 text-center shadow-sm">
                    <div class="text-xl sm:text-2xl font-black text-red-600 leading-none"><?php echo count($liveAuctions); ?></div>
                    <div class="text-[9px] sm:text-[10px] font-black text-slate-600 uppercase tracking-wider mt-1">Live Today</div>
                </div>
                <div class="bg-white/90 border border-slate-300 rounded-2xl p-3 sm:p-4 text-center shadow-sm">
                    <div class="text-xl sm:text-2xl font-black text-amber-800 leading-none"><?php echo count($upcomingAuctions); ?></div>
                    <div class="text-[9px] sm:text-[10px] font-black text-slate-600 uppercase tracking-wider mt-1">Upcoming</div>
                </div>
                <div class="bg-white/90 border border-slate-300 rounded-2xl p-3 sm:p-4 text-center shadow-sm">
                    <div class="text-xl sm:text-2xl font-black text-slate-900 leading-none"><?php echo count($completedAuctions); ?></div>
                    <div class="text-[9px] sm:text-[10px] font-black text-slate-600 uppercase tracking-wider mt-1">Finished</div>
                </div>
            </div>
        </div>

        <!-- Filter Bar & Search -->
        <div class="flex flex-col sm:flex-row items-center justify-between gap-4">
            <!-- Filter Tabs -->
            <div class="flex items-center gap-2 bg-white/90 border border-slate-300 p-1.5 rounded-2xl shadow-sm w-full sm:w-auto overflow-x-auto">
                <button onclick="filterTab('all')" id="tab-all" class="tab-btn bg-slate-900 text-white font-black text-xs px-4 py-2 rounded-xl transition shadow-sm flex items-center gap-2">
                    All <span class="bg-white/20 text-white px-2 py-0.5 rounded-md text-[10px]"><?php echo count($allTournaments); ?></span>
                </button>
                <button onclick="filterTab('live')" id="tab-live" class="tab-btn hover:bg-slate-100 text-slate-700 font-extrabold text-xs px-4 py-2 rounded-xl transition flex items-center gap-2">
                    🔴 Live Today <span class="bg-red-100 text-red-700 font-black px-2 py-0.5 rounded-md text-[10px]"><?php echo count($liveAuctions); ?></span>
                </button>
                <button onclick="filterTab('upcoming')" id="tab-upcoming" class="tab-btn hover:bg-slate-100 text-slate-700 font-extrabold text-xs px-4 py-2 rounded-xl transition flex items-center gap-2">
                    📅 Upcoming <span class="bg-amber-100 text-amber-800 font-black px-2 py-0.5 rounded-md text-[10px]"><?php echo count($upcomingAuctions); ?></span>
                </button>
                <button onclick="filterTab('completed')" id="tab-completed" class="tab-btn hover:bg-slate-100 text-slate-700 font-extrabold text-xs px-4 py-2 rounded-xl transition flex items-center gap-2">
                    🏆 Finished <span class="bg-slate-200 text-slate-800 font-black px-2 py-0.5 rounded-md text-[10px]"><?php echo count($completedAuctions); ?></span>
                </button>
            </div>

            <!-- Search Field -->
            <div class="relative w-full sm:w-80">
                <i class="fa-solid fa-magnifying-glass absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                <input type="text" id="auction-search" onkeyup="searchAuctions()" placeholder="Search league or tournament name..."
                       class="w-full bg-white/95 border-2 border-slate-300 rounded-xl pl-9 pr-4 py-2 text-xs text-slate-900 font-extrabold placeholder-slate-400 focus:outline-none focus:border-amber-500 shadow-sm transition">
            </div>
        </div>

        <!-- Section 1: TODAY'S LIVE PLAYER AUCTIONS -->
        <div class="auction-category-section" id="section-live">
            <div class="flex items-center gap-3 mb-4">
                <div class="w-3 h-3 rounded-full bg-red-600 animate-ping"></div>
                <h3 class="text-lg font-black text-slate-900 uppercase tracking-tight flex items-center gap-2">
                    🔴 Today's Live Player Auctions
                </h3>
                <span class="text-xs font-black text-red-700 bg-red-100 border border-red-300 px-2.5 py-0.5 rounded-full uppercase">
                    <?php echo count($liveAuctions); ?> Active
                </span>
            </div>

            <?php if (empty($liveAuctions)): ?>
                <div class="glass-panel rounded-2xl p-8 border border-white/60 text-center shadow-md">
                    <div class="w-12 h-12 rounded-full bg-slate-100 flex items-center justify-center mx-auto mb-3 text-slate-400 text-lg">
                        <i class="fa-solid fa-[#000] fa-gavel"></i>
                    </div>
                    <h4 class="text-sm font-black text-slate-800 uppercase">No Auctions Live Right Now</h4>
                    <p class="text-xs text-slate-600 font-extrabold mt-1">Check out the upcoming scheduled player auctions below!</p>
                </div>
            <?php else: ?>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <?php foreach ($liveAuctions as $t): ?>
                        <div class="auction-card glass-panel rounded-2xl p-6 border-2 border-red-500/40 shadow-xl flex flex-col justify-between space-y-4 hover:border-red-500 transition duration-300 relative overflow-hidden"
                             data-name="<?php echo htmlspecialchars(strtolower($t['name'])); ?>" data-category="live">
                            
                            <!-- Top Card Header -->
                            <div class="space-y-3">
                                <div class="flex items-center justify-between">
                                    <span class="bg-red-600 text-white font-black text-[10px] px-3 py-1 rounded-full uppercase tracking-wider flex items-center gap-1.5 shadow-sm">
                                        <i class="fa-solid fa-circle text-[8px] animate-pulse"></i> Live Bidding
                                    </span>
                                    <span class="text-[10px] font-mono font-black text-slate-600 uppercase bg-slate-100 px-2 py-0.5 rounded border border-slate-300">
                                        Code: <?php echo htmlspecialchars($t['code']); ?>
                                    </span>
                                </div>

                                <div class="flex items-center gap-3">
                                    <div class="w-12 h-12 rounded-xl overflow-hidden border border-slate-300 bg-white p-1 shrink-0 shadow-sm">
                                        <img src="<?php echo $uploadPath; ?><?php echo htmlspecialchars($t['logo'] ?: 'auctionwala_logo.png'); ?>" alt="Logo" class="w-full h-full object-contain">
                                    </div>
                                    <div>
                                        <h4 class="font-black text-slate-900 text-base leading-tight hover:text-amber-800 transition">
                                            <?php echo htmlspecialchars($t['name']); ?>
                                        </h4>
                                        <p class="text-xs text-slate-600 font-extrabold mt-0.5">Purse Default: ₹<?php echo number_format($t['total_purse_default']); ?></p>
                                    </div>
                                </div>
                            </div>

                            <!-- Metrics Bar -->
                            <div class="grid grid-cols-3 gap-2 bg-slate-900 text-white p-3 rounded-xl text-center shadow-inner">
                                <div>
                                    <div class="text-xs font-black font-mono text-amber-400"><?php echo $t['player_count']; ?></div>
                                    <div class="text-[8px] font-bold text-slate-400 uppercase">Players</div>
                                </div>
                                <div>
                                    <div class="text-xs font-black font-mono text-white"><?php echo $t['team_count']; ?></div>
                                    <div class="text-[8px] font-bold text-slate-400 uppercase">Teams</div>
                                </div>
                                <div>
                                    <div class="text-xs font-black font-mono text-emerald-400"><?php echo $t['sold_player_count']; ?></div>
                                    <div class="text-[8px] font-bold text-slate-400 uppercase">Sold</div>
                                </div>
                            </div>

                            <!-- Card Actions -->
                            <div class="pt-2 flex gap-2">
                                <a href="index.php?t_id=<?php echo $t['id']; ?>" class="flex-1 bg-red-600 hover:bg-red-500 text-white font-black text-xs py-2.5 px-3 rounded-xl transition shadow-md text-center flex items-center justify-center gap-1.5 uppercase tracking-wider">
                                    <i class="fa-solid fa-tv text-xs"></i> Watch Stream
                                </a>
                                <a href="register.php?t_id=<?php echo $t['id']; ?>" class="bg-white border border-slate-300 hover:bg-slate-100 text-slate-900 font-extrabold text-xs py-2.5 px-3 rounded-xl transition shadow-sm flex items-center justify-center gap-1">
                                    <i class="fa-solid fa-id-card text-amber-600"></i> Register
                                </a>
                            </div>

                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Section 2: UPCOMING PLAYER AUCTIONS -->
        <div class="auction-category-section pt-4" id="section-upcoming">
            <div class="flex items-center gap-3 mb-4">
                <div class="w-3 h-3 rounded-full bg-amber-500"></div>
                <h3 class="text-lg font-black text-slate-900 uppercase tracking-tight flex items-center gap-2">
                    📅 Upcoming Player Auctions
                </h3>
                <span class="text-xs font-black text-amber-900 bg-amber-100 border border-amber-300 px-2.5 py-0.5 rounded-full uppercase">
                    <?php echo count($upcomingAuctions); ?> Scheduled
                </span>
            </div>

            <?php if (empty($upcomingAuctions)): ?>
                <div class="glass-panel rounded-2xl p-8 border border-white/60 text-center shadow-md">
                    <h4 class="text-sm font-black text-slate-800 uppercase">No Upcoming Auctions Listed</h4>
                    <p class="text-xs text-slate-600 font-extrabold mt-1">Host your league on AuctionWala today!</p>
                </div>
            <?php else: ?>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <?php foreach ($upcomingAuctions as $t): ?>
                        <div class="auction-card glass-panel rounded-2xl p-6 border border-white/80 shadow-xl flex flex-col justify-between space-y-4 hover:border-amber-500 transition duration-300"
                             data-name="<?php echo htmlspecialchars(strtolower($t['name'])); ?>" data-category="upcoming">
                            
                            <!-- Top Card Header -->
                            <div class="space-y-3">
                                <div class="flex items-center justify-between">
                                    <?php if ($t['registration_enabled']): ?>
                                        <span class="bg-emerald-500/15 border border-emerald-500/30 text-emerald-800 font-black text-[10px] px-3 py-1 rounded-full uppercase tracking-wider flex items-center gap-1.5">
                                            <i class="fa-solid fa-circle-check text-emerald-600 text-[10px]"></i> Registration Open
                                        </span>
                                    <?php else: ?>
                                        <span class="bg-amber-500/15 border border-amber-500/30 text-amber-900 font-black text-[10px] px-3 py-1 rounded-full uppercase tracking-wider flex items-center gap-1.5">
                                            <i class="fa-solid fa-clock text-amber-700 text-[10px]"></i> Upcoming
                                        </span>
                                    <?php endif; ?>

                                    <span class="text-[10px] font-mono font-black text-slate-600 uppercase bg-slate-100 px-2 py-0.5 rounded border border-slate-300">
                                        Code: <?php echo htmlspecialchars($t['code']); ?>
                                    </span>
                                </div>

                                <div class="flex items-center gap-3">
                                    <div class="w-12 h-12 rounded-xl overflow-hidden border border-slate-300 bg-white p-1 shrink-0 shadow-sm">
                                        <img src="<?php echo $uploadPath; ?><?php echo htmlspecialchars($t['logo'] ?: 'auctionwala_logo.png'); ?>" alt="Logo" class="w-full h-full object-contain">
                                    </div>
                                    <div>
                                        <h4 class="font-black text-slate-900 text-base leading-tight hover:text-amber-800 transition">
                                            <?php echo htmlspecialchars($t['name']); ?>
                                        </h4>
                                        <p class="text-xs text-slate-600 font-extrabold mt-0.5">Purse Default: ₹<?php echo number_format($t['total_purse_default']); ?></p>
                                    </div>
                                </div>
                            </div>

                            <!-- Metrics Bar -->
                            <div class="grid grid-cols-3 gap-2 bg-slate-100 border border-slate-300 p-3 rounded-xl text-center shadow-inner">
                                <div>
                                    <div class="text-xs font-black font-mono text-slate-900"><?php echo $t['player_count']; ?></div>
                                    <div class="text-[8px] font-extrabold text-slate-600 uppercase">Players</div>
                                </div>
                                <div>
                                    <div class="text-xs font-black font-mono text-slate-900"><?php echo $t['team_count']; ?></div>
                                    <div class="text-[8px] font-extrabold text-slate-600 uppercase">Teams</div>
                                </div>
                                <div>
                                    <div class="text-xs font-black font-mono text-amber-800"><?php echo $t['max_squad_size_default']; ?></div>
                                    <div class="text-[8px] font-extrabold text-slate-600 uppercase">Squad Cap</div>
                                </div>
                            </div>

                            <!-- Card Actions -->
                            <div class="pt-2 flex gap-2">
                                <?php if ($t['registration_enabled']): ?>
                                    <a href="register.php?t_id=<?php echo $t['id']; ?>" class="flex-1 bg-amber-500 hover:bg-amber-400 text-slate-950 font-black text-xs py-2.5 px-3 rounded-xl transition shadow-md text-center flex items-center justify-center gap-1.5 uppercase tracking-wider">
                                        <i class="fa-solid fa-id-card text-xs"></i> Register Player
                                    </a>
                                <?php endif; ?>
                                <a href="index.php?t_id=<?php echo $t['id']; ?>" class="flex-1 bg-slate-900 hover:bg-slate-800 text-white font-black text-xs py-2.5 px-3 rounded-xl transition shadow-md text-center flex items-center justify-center gap-1.5 uppercase tracking-wider">
                                    <i class="fa-solid fa-eye text-xs"></i> View League
                                </a>
                            </div>

                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Section 3: FINISHED AUCTIONS ARCHIVE -->
        <?php if (!empty($completedAuctions)): ?>
            <div class="auction-category-section pt-4" id="section-completed">
                <div class="flex items-center gap-3 mb-4">
                    <div class="w-3 h-3 rounded-full bg-slate-600"></div>
                    <h3 class="text-lg font-black text-slate-900 uppercase tracking-tight flex items-center gap-2">
                        🏆 Finished Auctions Archive
                    </h3>
                    <span class="text-xs font-black text-slate-800 bg-slate-200 border border-slate-300 px-2.5 py-0.5 rounded-full uppercase">
                        <?php echo count($completedAuctions); ?> Completed
                    </span>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <?php foreach ($completedAuctions as $t): ?>
                        <div class="auction-card glass-panel rounded-2xl p-6 border border-white/80 shadow-xl flex flex-col justify-between space-y-4 hover:border-slate-400 transition duration-300"
                             data-name="<?php echo htmlspecialchars(strtolower($t['name'])); ?>" data-category="completed">
                            
                            <div class="space-y-3">
                                <div class="flex items-center justify-between">
                                    <span class="bg-slate-200 text-slate-800 font-black text-[10px] px-3 py-1 rounded-full uppercase tracking-wider flex items-center gap-1.5">
                                        <i class="fa-solid fa-trophy text-amber-600 text-[10px]"></i> Completed
                                    </span>
                                    <span class="text-[10px] font-mono font-black text-slate-600 uppercase bg-slate-100 px-2 py-0.5 rounded border border-slate-300">
                                        Code: <?php echo htmlspecialchars($t['code']); ?>
                                    </span>
                                </div>

                                <div class="flex items-center gap-3">
                                    <div class="w-12 h-12 rounded-xl overflow-hidden border border-slate-300 bg-white p-1 shrink-0 shadow-sm">
                                        <img src="<?php echo $uploadPath; ?><?php echo htmlspecialchars($t['logo'] ?: 'auctionwala_logo.png'); ?>" alt="Logo" class="w-full h-full object-contain">
                                    </div>
                                    <div>
                                        <h4 class="font-black text-slate-900 text-base leading-tight">
                                            <?php echo htmlspecialchars($t['name']); ?>
                                        </h4>
                                        <p class="text-xs text-slate-600 font-extrabold mt-0.5">Total Sold: <?php echo $t['sold_player_count']; ?> Players</p>
                                    </div>
                                </div>
                            </div>

                            <div class="pt-2">
                                <a href="index.php?t_id=<?php echo $t['id']; ?>" class="w-full bg-slate-900 hover:bg-slate-800 text-white font-black text-xs py-2.5 px-3 rounded-xl transition shadow-md text-center flex items-center justify-center gap-1.5 uppercase tracking-wider">
                                    <i class="fa-solid fa-list-check text-xs"></i> View Full Roster
                                </a>
                            </div>

                        </div>
                    <?php endforeach; ?>
                </div>
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
                <a href="auctions.php" class="hover:text-slate-900 transition">Auctions Hub</a>
                <a href="register.php" class="hover:text-slate-900 transition">Player Registration</a>
            </div>
        </div>
    </footer>

    <!-- Interactive Filter & Search Script -->
    <script>
    let currentFilter = 'all';

    function filterTab(category) {
        currentFilter = category;

        // Reset all buttons
        document.querySelectorAll('.tab-btn').forEach(btn => {
            btn.className = "tab-btn hover:bg-slate-100 text-slate-700 font-extrabold text-xs px-4 py-2 rounded-xl transition flex items-center gap-2";
        });

        // Highlight active button
        const activeBtn = document.getElementById(`tab-${category}`);
        if (activeBtn) {
            activeBtn.className = "tab-btn bg-slate-900 text-white font-black text-xs px-4 py-2 rounded-xl transition shadow-sm flex items-center gap-2";
        }

        // Show/Hide section containers
        const secLive = document.getElementById('section-live');
        const secUpcoming = document.getElementById('section-upcoming');
        const secCompleted = document.getElementById('section-completed');

        if (category === 'all') {
            if (secLive) secLive.style.display = 'block';
            if (secUpcoming) secUpcoming.style.display = 'block';
            if (secCompleted) secCompleted.style.display = 'block';
        } else if (category === 'live') {
            if (secLive) secLive.style.display = 'block';
            if (secUpcoming) secUpcoming.style.display = 'none';
            if (secCompleted) secCompleted.style.display = 'none';
        } else if (category === 'upcoming') {
            if (secLive) secLive.style.display = 'none';
            if (secUpcoming) secUpcoming.style.display = 'block';
            if (secCompleted) secCompleted.style.display = 'none';
        } else if (category === 'completed') {
            if (secLive) secLive.style.display = 'none';
            if (secUpcoming) secUpcoming.style.display = 'none';
            if (secCompleted) secCompleted.style.display = 'block';
        }

        searchAuctions();
    }

    function searchAuctions() {
        const query = document.getElementById('auction-search').value.toLowerCase().trim();
        const cards = document.querySelectorAll('.auction-card');

        cards.forEach(card => {
            const name = card.getAttribute('data-name') || '';
            const cat = card.getAttribute('data-category') || '';

            const matchesCategory = (currentFilter === 'all' || currentFilter === cat);
            const matchesQuery = (query === '' || name.includes(query));

            if (matchesCategory && matchesQuery) {
                card.style.display = 'flex';
            } else {
                card.style.display = 'none';
            }
        });
    }
    </script>
</body>
</html>
