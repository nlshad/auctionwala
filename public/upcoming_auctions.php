<?php
// public/upcoming_auctions.php — Dedicated Page for Upcoming Player Auctions
require_once '../config/db.php';
require_once '../config/auth.php';

// Fetch categorized tournaments
$categorized = get_categorized_tournaments($pdo);
$upcomingAuctions = $categorized['upcoming'];

$uploadPath = "../public/uploads/";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upcoming Player Auctions — AuctionWala</title>
    <meta name="description" content="Explore upcoming cricket league player auctions, player registration portals, franchise purse allocations, and squad limits on AuctionWala.">
    <?php require_once 'components/ui_head.php'; ?>
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
                <h1 class="text-xs font-black uppercase tracking-tight text-slate-900 leading-none">Upcoming Auctions</h1>
                <p class="text-[9px] text-amber-800 font-extrabold uppercase tracking-wider mt-0.5 flex items-center gap-1">
                    <i class="fa-solid fa-calendar-days text-amber-600"></i> Scheduled Tournaments
                </p>
            </div>
        </div>

        <!-- Right Header Nav Links -->
        <div class="flex items-center gap-2 sm:gap-3">
            <a href="landing.php" class="text-xs font-black text-slate-700 hover:text-slate-900 uppercase tracking-wider hidden md:inline-flex items-center gap-1 px-3 py-1.5 rounded-lg hover:bg-slate-100 transition">
                <i class="fa-solid fa-house text-slate-500"></i> Home
            </a>
            <a href="today_auctions.php" class="text-xs font-black text-slate-700 hover:text-slate-900 uppercase tracking-wider px-3 py-1.5 rounded-lg hover:bg-slate-100 transition hidden sm:inline-flex items-center gap-1">
                <i class="fa-solid fa-circle text-red-600 text-[8px] animate-pulse"></i> Live Today
            </a>
            <a href="upcoming_auctions.php" class="text-xs font-black text-amber-900 bg-amber-100 border border-amber-300 uppercase tracking-wider px-3 py-1.5 rounded-lg flex items-center gap-1.5">
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
        <div class="glass-panel rounded-3xl p-6 sm:p-10 border border-white/80 shadow-2xl relative overflow-hidden text-center sm:text-left flex flex-col sm:flex-row items-center justify-between gap-6">
            <div class="space-y-2 max-w-2xl">
                <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-amber-500/20 border border-amber-500/40 text-amber-900 text-[10px] font-black uppercase tracking-wider">
                    <i class="fa-solid fa-user-plus text-amber-600"></i> Player Registration Portals
                </div>
                <h2 class="text-2xl sm:text-4xl font-black text-slate-900 uppercase tracking-tight leading-tight">
                    Upcoming Player Auctions
                </h2>
                <p class="text-xs sm:text-sm text-slate-700 font-extrabold">
                    Discover upcoming sports leagues, register as a player, and track franchise purse parameters ahead of bidding day.
                </p>
            </div>

            <!-- Total Scheduled Pill -->
            <div class="bg-slate-900 text-white p-5 rounded-2xl text-center shadow-xl shrink-0 border border-slate-700 w-full sm:w-48">
                <div class="text-3xl font-black text-amber-400 leading-none">
                    <?php echo count($upcomingAuctions); ?>
                </div>
                <div class="text-[10px] font-black uppercase tracking-wider text-slate-400 mt-1">Scheduled Auctions</div>
            </div>
        </div>

        <!-- Search Bar -->
        <div class="flex items-center justify-between gap-4">
            <h3 class="text-lg font-black text-slate-900 uppercase tracking-tight flex items-center gap-2">
                <i class="fa-solid fa-calendar-check text-amber-600"></i> Upcoming Bidding Events
            </h3>
            <div class="relative w-full sm:w-80">
                <i class="fa-solid fa-magnifying-glass absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                <input type="text" id="upcoming-search" onkeyup="searchUpcomingAuctions()" placeholder="Search upcoming league by name..."
                       class="w-full bg-white/95 border-2 border-slate-300 rounded-xl pl-9 pr-4 py-2 text-xs text-slate-900 font-extrabold placeholder-slate-400 focus:outline-none focus:border-amber-500 shadow-sm transition">
            </div>
        </div>

        <!-- Upcoming Auctions Grid -->
        <?php if (empty($upcomingAuctions)): ?>
            <div class="glass-panel rounded-3xl p-12 border border-white/60 text-center shadow-xl my-8">
                <div class="w-16 h-16 rounded-full bg-amber-100 text-amber-700 flex items-center justify-center mx-auto mb-4 text-2xl">
                    <i class="fa-solid fa-calendar-xmark"></i>
                </div>
                <h4 class="text-lg font-black text-slate-900 uppercase">No Upcoming Auctions Scheduled</h4>
                <p class="text-xs text-slate-600 font-extrabold mt-1 max-w-md mx-auto">
                    There are no upcoming player auctions listed at this moment. Are you an organizer? Host your league on AuctionWala today!
                </p>
                <div class="mt-6">
                    <a href="login.php" class="bg-amber-500 hover:bg-amber-400 text-slate-950 font-black text-xs uppercase tracking-wider px-6 py-3 rounded-xl transition shadow-md inline-flex items-center gap-2">
                        <i class="fa-solid fa-trophy text-slate-950"></i> Host Your Tournament
                    </a>
                </div>
            </div>
        <?php else: ?>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6" id="upcoming-auctions-container">
                <?php foreach ($upcomingAuctions as $t): ?>
                    <div class="upcoming-card glass-panel rounded-2xl p-6 border border-white/80 shadow-xl flex flex-col justify-between space-y-4 hover:border-amber-500 transition duration-300"
                         data-name="<?php echo htmlspecialchars(strtolower($t['name'])); ?>">
                        
                        <!-- Top Card Info -->
                        <div class="space-y-3">
                            <div class="flex items-center justify-between">
                                <?php if ($t['registration_enabled']): ?>
                                    <span class="bg-emerald-500/15 border border-emerald-500/30 text-emerald-800 font-black text-[10px] px-3 py-1 rounded-full uppercase tracking-wider flex items-center gap-1.5 shadow-sm">
                                        <i class="fa-solid fa-circle-check text-emerald-600 text-[10px]"></i> Registration Open
                                    </span>
                                <?php else: ?>
                                    <span class="bg-amber-500/15 border border-amber-500/30 text-amber-900 font-black text-[10px] px-3 py-1 rounded-full uppercase tracking-wider flex items-center gap-1.5 shadow-sm">
                                        <i class="fa-solid fa-clock text-amber-700 text-[10px]"></i> Upcoming
                                    </span>
                                <?php endif; ?>

                                <span class="text-[10px] font-mono font-black text-slate-600 uppercase bg-slate-100 px-2.5 py-0.5 rounded border border-slate-300">
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
                                    <p class="text-xs text-slate-600 font-extrabold mt-0.5">Purse Cap: ₹<?php echo number_format($t['total_purse_default']); ?></p>
                                </div>
                            </div>
                        </div>

                        <!-- Metrics Bar -->
                        <div class="grid grid-cols-3 gap-2 bg-slate-100 border border-slate-300 p-3.5 rounded-xl text-center shadow-inner">
                            <div>
                                <div class="text-sm font-black font-mono text-slate-900"><?php echo $t['player_count']; ?></div>
                                <div class="text-[8px] font-extrabold text-slate-600 uppercase">Registered</div>
                            </div>
                            <div>
                                <div class="text-sm font-black font-mono text-slate-900"><?php echo $t['team_count']; ?></div>
                                <div class="text-[8px] font-extrabold text-slate-600 uppercase">Teams</div>
                            </div>
                            <div>
                                <div class="text-sm font-black font-mono text-amber-800"><?php echo $t['max_squad_size_default']; ?></div>
                                <div class="text-[8px] font-extrabold text-slate-600 uppercase">Squad Cap</div>
                            </div>
                        </div>

                        <!-- Action CTA Buttons -->
                        <div class="pt-2 flex gap-2">
                            <?php if ($t['registration_enabled']): ?>
                                <a href="register.php?t_id=<?php echo $t['id']; ?>" class="flex-1 bg-amber-500 hover:bg-amber-400 text-slate-950 font-black text-xs py-3 px-3 rounded-xl transition shadow-md text-center flex items-center justify-center gap-1.5 uppercase tracking-wider">
                                    <i class="fa-solid fa-id-card text-xs"></i> Register Player
                                </a>
                            <?php endif; ?>
                            <a href="index.php?t_id=<?php echo $t['id']; ?>" class="flex-1 bg-slate-900 hover:bg-slate-800 text-white font-black text-xs py-3 px-3 rounded-xl transition shadow-md text-center flex items-center justify-center gap-1.5 uppercase tracking-wider">
                                <i class="fa-solid fa-eye text-xs"></i> View League
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
    function searchUpcomingAuctions() {
        const query = document.getElementById('upcoming-search').value.toLowerCase().trim();
        const cards = document.querySelectorAll('.upcoming-card');

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
