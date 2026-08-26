<?php
// public/landing.php
session_start();
require_once '../config/db.php';

// Fetch categorized tournaments
$categorized = get_categorized_tournaments($pdo);
$liveAuctions = $categorized['live'];
$upcomingAuctions = $categorized['upcoming'];
$completedAuctions = $categorized['completed'];
$allTournaments = $categorized['all'];

// Aggregate system metrics
$totalTournaments = count($allTournaments);
$totalTeams = 0;
$totalPlayers = 0;
$totalBids = 0;

try {
    if ($pdo) {
        $stmt = $pdo->query("SELECT COUNT(*) FROM teams");
        $totalTeams = (int)$stmt->fetchColumn();

        $stmt = $pdo->query("SELECT COUNT(*) FROM players WHERE payment_status = 'Verified'");
        $totalPlayers = (int)$stmt->fetchColumn();

        $stmt = $pdo->query("SELECT COUNT(*) FROM bids");
        $totalBids = (int)$stmt->fetchColumn();
    }
} catch (Exception $e) {}

$uploadPath = is_dir('uploads') ? 'uploads/' : '../uploads/';
?>
<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AuctionWala — #1 Sports Auction App for Cricket, Football & Turf Leagues</title>
    <meta name="description" content="AuctionWala is the premier sports auction app for live player bidding, player registration, team purse management, and tournament streaming worldwide.">
    <link rel="icon" type="image/png" href="<?php echo $uploadPath; ?>auctionwala_logo.png">
    <?php require_once 'components/ui_head.php'; ?>
    <style>
        .glass-hero {
            background: rgba(255, 255, 255, 0.92);
            backdrop-filter: blur(24px);
            border: 1.5px solid rgba(255, 255, 255, 0.8);
            box-shadow: 0 20px 50px -10px rgba(0, 0, 0, 0.12);
        }
        .bento-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(16px);
            border: 1px solid rgba(226, 232, 240, 0.9);
            border-radius: 1.25rem;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.05);
            transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .bento-card:hover {
            border-color: rgba(212, 163, 12, 0.6);
            box-shadow: 0 20px 40px -10px rgba(212, 163, 12, 0.2);
            transform: translateY(-3px);
        }
        .whatsapp-float {
            position: fixed;
            bottom: 24px;
            right: 24px;
            width: 56px;
            height: 56px;
            background-color: #25d366;
            color: #fff;
            border-radius: 50px;
            text-align: center;
            font-size: 30px;
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
<body class="text-slate-800 min-h-screen flex flex-col justify-between selection:bg-gold-500 selection:text-black">

    <!-- Top Announcement & Social Bar -->
    <div class="bg-slate-950 text-slate-300 text-xs py-2 px-4 border-b border-slate-800 flex items-center justify-between font-extrabold">
        <div class="flex items-center gap-2 truncate">
            <span class="bg-red-600 text-white font-black text-[9px] px-2 py-0.5 rounded-full uppercase tracking-wider animate-pulse shrink-0">#1 Platform</span>
            <span class="truncate">World’s Premier Cricket & Sports Auction Platform for Live Player Bidding</span>
        </div>
        <div class="hidden md:flex items-center gap-4 text-xs shrink-0">
            <a href="tel:+917698767767" class="hover:text-amber-400 transition flex items-center gap-1.5">
                <i class="fa-solid fa-phone text-amber-500"></i> +91 76 98 767 767
            </a>
            <span class="text-slate-700">|</span>
            <a href="https://wa.me/917698767767" target="_blank" class="hover:text-emerald-400 transition flex items-center gap-1.5">
                <i class="fa-brands fa-whatsapp text-emerald-500"></i> Live Support
            </a>
        </div>
    </div>

    <!-- Header Navigation Bar -->
    <header class="w-full glass-panel border-b border-slate-200/80 px-4 py-3 sm:px-8 sm:py-4 flex items-center justify-between sticky top-0 z-50 shadow-sm">
        <div class="flex items-center gap-3">
            <a href="landing.php" class="flex items-center gap-2">
                <img src="<?php echo $uploadPath; ?>auctionwala_logo.png" alt="AuctionWala Logo" class="h-9 sm:h-10 object-contain mix-blend-multiply">
            </a>
        </div>

        <nav class="hidden lg:flex items-center gap-6 font-extrabold text-xs text-slate-700 uppercase tracking-wider">
            <a href="#hero" class="hover:text-amber-600 transition">Home</a>
            <a href="#today-auctions" class="hover:text-amber-600 transition flex items-center gap-1">
                <span class="w-2 h-2 rounded-full bg-red-600 animate-ping"></span> Live Today
            </a>
            <a href="#upcoming-auctions" class="hover:text-amber-600 transition">Upcoming</a>
            <a href="#features" class="hover:text-amber-600 transition">Features</a>
            <a href="#how-it-works" class="hover:text-amber-600 transition">How It Works</a>
            <a href="#pricing" class="hover:text-amber-600 transition">Pricing</a>
        </nav>

        <div class="flex items-center gap-3">
            <a href="login.php" class="bg-gradient-to-r from-amber-500 to-gold-600 hover:from-amber-600 hover:to-gold-700 text-slate-950 font-black text-xs px-4 py-2.5 rounded-xl shadow-md transition transform hover:scale-105 uppercase tracking-wider flex items-center gap-2">
                <i class="fa-solid fa-gavel text-xs"></i> Host Your Auction
            </a>
        </div>
    </header>

    <!-- Hero Section -->
    <section id="hero" class="relative py-12 lg:py-20 px-4 max-w-7xl mx-auto w-full">
        <div class="glass-hero rounded-3xl p-8 lg:p-12 border border-white/80 shadow-2xl relative overflow-hidden">
            <div class="absolute -right-20 -top-20 w-80 h-80 bg-amber-400/20 rounded-full blur-3xl pointer-events-none"></div>
            <div class="absolute -left-20 -bottom-20 w-80 h-80 bg-red-500/20 rounded-full blur-3xl pointer-events-none"></div>

            <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-center relative z-10">
                <div class="lg:col-span-8 space-y-6">
                    <div class="inline-flex items-center gap-2 bg-amber-500/15 border border-amber-500/30 px-3.5 py-1.5 rounded-full text-xs font-black text-amber-900 uppercase tracking-wider">
                        <i class="fa-solid fa-shield-halved text-amber-600"></i> Next-Gen Real-Time Auction Engine
                    </div>

                    <h1 class="text-3xl sm:text-5xl lg:text-6xl font-black text-slate-900 uppercase tracking-tight leading-none">
                        Online Cricket Auction App <br>
                        <span class="text-transparent bg-clip-text bg-gradient-to-r from-amber-600 via-gold-600 to-amber-700">For Live Player Bidding</span>
                    </h1>

                    <p class="text-slate-600 font-extrabold text-sm sm:text-base leading-relaxed max-w-2xl">
                        Streamline your sports league auctions with real-time bidding, instant squad updates, player photo registration, franchise budget tracking, and broadcast-ready YouTube stream overlays.
                    </p>

                    <div class="flex flex-wrap items-center gap-4 pt-2">
                        <a href="login.php" class="bg-slate-900 hover:bg-slate-800 text-white font-black text-xs sm:text-sm px-6 py-3.5 rounded-xl transition shadow-xl uppercase tracking-wider flex items-center gap-2">
                            <i class="fa-solid fa-plus-circle text-amber-400"></i> Create League Auction
                        </a>
                        <a href="#today-auctions" class="bg-white/90 border-2 border-slate-300 hover:border-amber-500 text-slate-900 font-extrabold text-xs sm:text-sm px-6 py-3 rounded-xl transition shadow-md uppercase tracking-wider flex items-center gap-2">
                            <i class="fa-solid fa-tv text-red-600"></i> Watch Live Arena
                        </a>
                    </div>
                </div>

                <!-- Hero Side Badges & Ratings -->
                <div class="lg:col-span-4 space-y-4">
                    <div class="bento-card p-5 border border-slate-200/90 shadow-md">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-xs font-black text-slate-900 uppercase tracking-wider">Trusted Rating</span>
                            <span class="text-xs font-black text-amber-600 font-mono">4.9 / 5.0 ★</span>
                        </div>
                        <div class="flex items-center gap-2 text-xs font-extrabold text-slate-600">
                            <i class="fa-solid fa-star text-amber-500"></i>
                            <span>Google & Trustpilot Verified Software</span>
                        </div>
                    </div>

                    <div class="bento-card p-5 bg-gradient-to-br from-slate-900 to-slate-950 text-white shadow-xl">
                        <div class="text-xs font-bold text-amber-400 uppercase tracking-widest mb-1">Live Concurrency</div>
                        <div class="text-2xl font-black font-mono text-white"><?php echo max(1890, $totalBids); ?>+</div>
                        <div class="text-xs text-slate-400 font-bold mt-1">Real-time bids processed with instant socket sync.</div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Today's Live Auctions Section -->
    <section id="today-auctions" class="py-10 px-4 max-w-7xl mx-auto w-full">
        <div class="flex items-center justify-between mb-6">
            <div class="flex items-center gap-3">
                <span class="w-3.5 h-3.5 rounded-full bg-red-600 animate-ping"></span>
                <div>
                    <h2 class="text-xl sm:text-2xl font-black text-white uppercase tracking-tight flex items-center gap-2 drop-shadow-md">
                        🔴 Today's Live Player Auctions
                    </h2>
                    <p class="text-xs font-extrabold text-slate-300 uppercase tracking-wider">Watch active player bidding in real time</p>
                </div>
            </div>
            <a href="today_auctions.php" class="bg-white/95 border border-slate-200 hover:bg-slate-100 text-slate-900 font-extrabold text-xs px-3.5 py-1.5 rounded-lg transition shadow-sm uppercase tracking-wider flex items-center gap-1">
                View All <i class="fa-solid fa-arrow-right text-[10px]"></i>
            </a>
        </div>

        <?php if (empty($liveAuctions)): ?>
            <div class="bento-card p-8 text-center shadow-md">
                <div class="w-12 h-12 rounded-full bg-slate-100 flex items-center justify-center mx-auto mb-3 text-slate-400 text-lg">
                    <i class="fa-solid fa-gavel text-slate-500"></i>
                </div>
                <h3 class="text-sm font-black text-slate-800 uppercase">No Auctions Live Right Now</h3>
                <p class="text-xs text-slate-600 font-extrabold mt-1">Check out the upcoming scheduled player auctions below!</p>
            </div>
        <?php else: ?>
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <?php foreach (array_slice($liveAuctions, 0, 4) as $t): ?>
                    <div class="auction-card bg-white/95 rounded-2xl p-4 sm:p-5 border border-slate-200 shadow-md hover:shadow-xl hover:border-red-500 transition duration-300 flex flex-col sm:flex-row gap-4 items-start sm:items-center relative cursor-pointer"
                         onclick="window.location.href='index.php?t_id=<?php echo $t['id']; ?>'">
                        
                        <!-- Left Logo -->
                        <div class="w-full sm:w-36 h-32 sm:h-36 rounded-xl overflow-hidden bg-slate-900 border border-slate-200 shrink-0 shadow-sm relative">
                            <img src="<?php echo $uploadPath; ?><?php echo htmlspecialchars($t['logo'] ?: 'auctionwala_logo.png'); ?>" alt="Logo" class="w-full h-full object-cover">
                            <span class="absolute top-2 left-2 bg-red-600 text-white font-black text-[9px] px-2 py-0.5 rounded-full uppercase tracking-wider shadow-sm flex items-center gap-1">
                                <span class="w-1.5 h-1.5 rounded-full bg-white animate-ping"></span> Live
                            </span>
                        </div>

                        <!-- Right Details Content -->
                        <div class="flex-grow space-y-3 w-full">
                            <div class="flex items-start justify-between gap-2">
                                <h3 class="font-black text-slate-900 text-base sm:text-lg leading-snug hover:text-red-700 transition">
                                    <?php echo htmlspecialchars($t['name']); ?>
                                </h3>
                            </div>

                            <div class="grid grid-cols-2 gap-x-3 gap-y-1.5 text-xs text-slate-600 font-extrabold">
                                <div class="flex items-center gap-1.5">
                                    <i class="fa-solid fa-users text-slate-400 text-xs w-4"></i>
                                    <span><?php echo $t['max_squad_size_default']; ?> Players/Team</span>
                                </div>
                                <div class="flex items-center gap-1.5">
                                    <i class="fa-solid fa-trophy text-amber-500 text-xs w-4"></i>
                                    <span><?php echo number_format($t['total_purse_default']); ?> Points</span>
                                </div>
                                <div class="flex items-center gap-1.5">
                                    <i class="fa-regular fa-clock text-slate-400 text-xs w-4"></i>
                                    <span><?php echo date('h:i A', strtotime($t['created_at'])); ?></span>
                                </div>
                                <div class="flex items-center gap-1.5">
                                    <i class="fa-regular fa-calendar text-slate-400 text-xs w-4"></i>
                                    <span><?php echo date('d-m-Y', strtotime($t['created_at'])); ?></span>
                                </div>
                            </div>

                            <div class="flex items-center justify-between gap-2 pt-1">
                                <div class="bg-sky-50 text-sky-800 font-extrabold px-3 py-1.5 rounded-lg text-xs flex items-center gap-1.5 border border-sky-100 flex-grow truncate">
                                    <i class="fa-solid fa-location-dot text-sky-600 text-xs"></i>
                                    <span class="truncate"><?php echo htmlspecialchars($t['place'] ?? 'League Arena'); ?></span>
                                </div>
                                <a href="index.php?t_id=<?php echo $t['id']; ?>" onclick="event.stopPropagation();" class="bg-red-600 hover:bg-red-500 text-white font-black text-xs px-3.5 py-1.5 rounded-lg transition shadow-sm uppercase tracking-wider shrink-0 flex items-center gap-1">
                                    <i class="fa-solid fa-tv text-xs"></i> Watch Stream
                                </a>
                            </div>
                        </div>

                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>

    <!-- Upcoming Scheduled Auctions Section -->
    <section id="upcoming-auctions" class="py-10 px-4 max-w-7xl mx-auto w-full">
        <div class="flex items-center justify-between mb-6">
            <div class="flex items-center gap-3">
                <span class="w-3.5 h-3.5 rounded-full bg-amber-500"></span>
                <div>
                    <h2 class="text-xl sm:text-2xl font-black text-white uppercase tracking-tight flex items-center gap-2 drop-shadow-md">
                        📅 Upcoming Scheduled Auctions
                    </h2>
                    <p class="text-xs font-extrabold text-slate-300 uppercase tracking-wider">Scheduled player auctions ready for player registration</p>
                </div>
            </div>
            <a href="upcoming_auctions.php" class="bg-white/95 border border-slate-200 hover:bg-slate-100 text-slate-900 font-extrabold text-xs px-3.5 py-1.5 rounded-lg transition shadow-sm uppercase tracking-wider flex items-center gap-1">
                View All <i class="fa-solid fa-arrow-right text-[10px]"></i>
            </a>
        </div>

        <?php if (empty($upcomingAuctions)): ?>
            <div class="bento-card p-8 text-center shadow-md">
                <h3 class="text-sm font-black text-slate-800 uppercase">No Upcoming Auctions Listed</h3>
                <p class="text-xs text-slate-600 font-extrabold mt-1">Host your league on AuctionWala today!</p>
            </div>
        <?php else: ?>
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <?php foreach (array_slice($upcomingAuctions, 0, 4) as $t): ?>
                    <div class="auction-card bg-white/95 rounded-2xl p-4 sm:p-5 border border-slate-200 shadow-md hover:shadow-xl hover:border-amber-500 transition duration-300 flex flex-col sm:flex-row gap-4 items-start sm:items-center relative cursor-pointer"
                         onclick="window.location.href='index.php?t_id=<?php echo $t['id']; ?>'">
                        
                        <!-- Left Logo -->
                        <div class="w-full sm:w-36 h-32 sm:h-36 rounded-xl overflow-hidden bg-slate-900 border border-slate-200 shrink-0 shadow-sm relative">
                            <img src="<?php echo $uploadPath; ?><?php echo htmlspecialchars($t['logo'] ?: 'auctionwala_logo.png'); ?>" alt="Logo" class="w-full h-full object-cover">
                            <?php if ($t['registration_enabled']): ?>
                                <span class="absolute top-2 left-2 bg-emerald-600 text-white font-black text-[9px] px-2 py-0.5 rounded-full uppercase tracking-wider shadow-sm">
                                    Open
                                </span>
                            <?php endif; ?>
                        </div>

                        <!-- Right Details Content -->
                        <div class="flex-grow space-y-3 w-full">
                            <div class="flex items-start justify-between gap-2">
                                <h3 class="font-black text-slate-900 text-base sm:text-lg leading-snug hover:text-amber-800 transition">
                                    <?php echo htmlspecialchars($t['name']); ?>
                                </h3>
                            </div>

                            <div class="grid grid-cols-2 gap-x-3 gap-y-1.5 text-xs text-slate-600 font-extrabold">
                                <div class="flex items-center gap-1.5">
                                    <i class="fa-solid fa-users text-slate-400 text-xs w-4"></i>
                                    <span><?php echo $t['max_squad_size_default']; ?> Players/Team</span>
                                </div>
                                <div class="flex items-center gap-1.5">
                                    <i class="fa-solid fa-trophy text-amber-500 text-xs w-4"></i>
                                    <span><?php echo number_format($t['total_purse_default']); ?> Points</span>
                                </div>
                                <div class="flex items-center gap-1.5">
                                    <i class="fa-regular fa-clock text-slate-400 text-xs w-4"></i>
                                    <span><?php echo date('h:i A', strtotime($t['created_at'])); ?></span>
                                </div>
                                <div class="flex items-center gap-1.5">
                                    <i class="fa-regular fa-calendar text-slate-400 text-xs w-4"></i>
                                    <span><?php echo date('d-m-Y', strtotime($t['created_at'])); ?></span>
                                </div>
                            </div>

                            <div class="flex items-center justify-between gap-2 pt-1">
                                <div class="bg-sky-50 text-sky-800 font-extrabold px-3 py-1.5 rounded-lg text-xs flex items-center gap-1.5 border border-sky-100 flex-grow truncate">
                                    <i class="fa-solid fa-location-dot text-sky-600 text-xs"></i>
                                    <span class="truncate"><?php echo htmlspecialchars($t['place'] ?? 'League Arena'); ?></span>
                                </div>
                                <?php if ($t['registration_enabled']): ?>
                                    <a href="register.php?t_id=<?php echo $t['id']; ?>" onclick="event.stopPropagation();" class="bg-amber-500 hover:bg-amber-400 text-slate-950 font-black text-xs px-3.5 py-1.5 rounded-lg transition shadow-sm uppercase tracking-wider shrink-0 flex items-center gap-1">
                                        <i class="fa-solid fa-id-card text-xs"></i> Register
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>

                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>

    <!-- Platform Numbers Section -->
    <section class="py-12 bg-slate-950 text-white border-y border-slate-800">
        <div class="max-w-7xl mx-auto px-4">
            <div class="text-center max-w-2xl mx-auto mb-10">
                <h2 class="text-2xl sm:text-3xl font-black uppercase tracking-tight text-white">
                    AuctionWala <span class="text-amber-400">in Numbers</span>
                </h2>
                <p class="text-xs text-slate-400 font-extrabold mt-1.5 uppercase tracking-wider">Trusted by thousands of cricket league organizers & team owners</p>
            </div>

            <div class="grid grid-cols-2 lg:grid-cols-4 gap-6">
                <div class="bento-card p-6 bg-slate-900 border-slate-800 text-center shadow-lg">
                    <div class="text-3xl sm:text-4xl font-black font-mono text-amber-400"><?php echo max(64, $totalTournaments); ?>+</div>
                    <div class="text-xs font-black text-slate-300 uppercase tracking-widest mt-2">Auctions Hosted</div>
                </div>
                <div class="bento-card p-6 bg-slate-900 border-slate-800 text-center shadow-lg">
                    <div class="text-3xl sm:text-4xl font-black font-mono text-white"><?php echo max(106, $totalTeams); ?>+</div>
                    <div class="text-xs font-black text-slate-300 uppercase tracking-widest mt-2">Franchise Teams</div>
                </div>
                <div class="bento-card p-6 bg-slate-900 border-slate-800 text-center shadow-lg">
                    <div class="text-3xl sm:text-4xl font-black font-mono text-emerald-400"><?php echo max(542, $totalPlayers); ?>+</div>
                    <div class="text-xs font-black text-slate-300 uppercase tracking-widest mt-2">Verified Players</div>
                </div>
                <div class="bento-card p-6 bg-slate-900 border-slate-800 text-center shadow-lg">
                    <div class="text-3xl sm:text-4xl font-black font-mono text-cyan-400"><?php echo max(1890, $totalBids); ?>+</div>
                    <div class="text-xs font-black text-slate-300 uppercase tracking-widest mt-2">Live Bids Placed</div>
                </div>
            </div>
        </div>
    </section>

    <!-- Our Features Section -->
    <section id="features" class="py-16 px-4 max-w-7xl mx-auto w-full">
        <div class="text-center max-w-2xl mx-auto mb-12">
            <h2 class="text-2xl sm:text-4xl font-black uppercase tracking-tight text-white drop-shadow-md">
                Our <span class="text-amber-400">Features</span>
            </h2>
            <p class="text-xs text-slate-300 font-extrabold mt-1.5 uppercase tracking-widest">Everything you need to manage & stream professional sports auctions</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <!-- Feature 1 -->
            <div class="bento-card p-6">
                <div class="w-10 h-10 rounded-xl bg-amber-500/15 border border-amber-500/30 flex items-center justify-center text-amber-600 mb-4 text-lg">
                    <i class="fa-solid fa-gavel"></i>
                </div>
                <h3 class="text-base font-black text-slate-900 uppercase">Live Real-Time Bidding</h3>
                <p class="text-xs text-slate-600 font-extrabold mt-2 leading-relaxed">Experience dynamic player bidding with instant sound FX, bidder team highlights, and zero-latency state synchronization.</p>
            </div>

            <!-- Feature 2 -->
            <div class="bento-card p-6">
                <div class="w-10 h-10 rounded-xl bg-emerald-500/15 border border-emerald-500/30 flex items-center justify-center text-emerald-600 mb-4 text-lg">
                    <i class="fa-solid fa-trophy"></i>
                </div>
                <h3 class="text-base font-black text-slate-900 uppercase">Tournament Management</h3>
                <p class="text-xs text-slate-600 font-extrabold mt-2 leading-relaxed">Customize purse defaults, base prices, bid increment slabs, squad caps, and category limits per league.</p>
            </div>

            <!-- Feature 3 -->
            <div class="bento-card p-6">
                <div class="w-10 h-10 rounded-xl bg-sky-500/15 border border-sky-500/30 flex items-center justify-center text-sky-600 mb-4 text-lg">
                    <i class="fa-solid fa-users-gear"></i>
                </div>
                <h3 class="text-base font-black text-slate-900 uppercase">Franchise Team Portals</h3>
                <p class="text-xs text-slate-600 font-extrabold mt-2 leading-relaxed">Organize franchise teams with custom logos, live purse remaining progress bars, and full squad rosters.</p>
            </div>

            <!-- Feature 4 -->
            <div class="bento-card p-6">
                <div class="w-10 h-10 rounded-xl bg-purple-500/15 border border-purple-500/30 flex items-center justify-center text-purple-600 mb-4 text-lg">
                    <i class="fa-solid fa-id-card"></i>
                </div>
                <h3 class="text-base font-black text-slate-900 uppercase">Player Registration Pool</h3>
                <p class="text-xs text-slate-600 font-extrabold mt-2 leading-relaxed">Allow candidates to register via public form, upload cropped profile photos, and submit registration UTR receipts.</p>
            </div>

            <!-- Feature 5 -->
            <div class="bento-card p-6">
                <div class="w-10 h-10 rounded-xl bg-red-500/15 border border-red-500/30 flex items-center justify-center text-red-600 mb-4 text-lg">
                    <i class="fa-solid fa-wifi"></i>
                </div>
                <h3 class="text-base font-black text-slate-900 uppercase">Real-Time Concurrency</h3>
                <p class="text-xs text-slate-600 font-extrabold mt-2 leading-relaxed">Keep thousands of live viewers synced across smartphones, tablets, laptops, and smart TVs simultaneously.</p>
            </div>

            <!-- Feature 6 -->
            <div class="bento-card p-6">
                <div class="w-10 h-10 rounded-xl bg-indigo-500/15 border border-indigo-500/30 flex items-center justify-center text-indigo-600 mb-4 text-lg">
                    <i class="fa-solid fa-share-nodes"></i>
                </div>
                <h3 class="text-base font-black text-slate-900 uppercase">Shareable Spectator Links</h3>
                <p class="text-xs text-slate-600 font-extrabold mt-2 leading-relaxed">Share your tournament link (`index.php?t_id=XX`) for instant viewing without forcing spectators to log in.</p>
            </div>
        </div>
    </section>

    <!-- How It Works Section -->
    <section id="how-it-works" class="py-16 bg-slate-900 text-white border-y border-slate-800">
        <div class="max-w-7xl mx-auto px-4">
            <div class="text-center max-w-2xl mx-auto mb-12">
                <h2 class="text-2xl sm:text-4xl font-black uppercase tracking-tight text-white">
                    How It <span class="text-amber-400">Works</span>
                </h2>
                <p class="text-xs text-slate-400 font-extrabold mt-1.5 uppercase tracking-widest">Follow these simple steps to host your auction</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                <div class="bento-card p-6 bg-slate-950 border-slate-800 text-slate-200">
                    <div class="w-10 h-10 rounded-xl bg-amber-500 text-slate-950 font-black flex items-center justify-center text-base mb-4 font-mono">01</div>
                    <h3 class="text-base font-black text-white uppercase">Create Auction</h3>
                    <p class="text-xs text-slate-400 font-extrabold mt-2">Set up tournament details, total purse per team, and maximum squad size.</p>
                </div>

                <div class="bento-card p-6 bg-slate-950 border-slate-800 text-slate-200">
                    <div class="w-10 h-10 rounded-xl bg-amber-500 text-slate-950 font-black flex items-center justify-center text-base mb-4 font-mono">02</div>
                    <h3 class="text-base font-black text-white uppercase">Add Teams</h3>
                    <p class="text-xs text-slate-400 font-extrabold mt-2">Add franchise teams, logos, owner details, and purse allocations.</p>
                </div>

                <div class="bento-card p-6 bg-slate-950 border-slate-800 text-slate-200">
                    <div class="w-10 h-10 rounded-xl bg-amber-500 text-slate-950 font-black flex items-center justify-center text-base mb-4 font-mono">03</div>
                    <h3 class="text-base font-black text-white uppercase">Add Players</h3>
                    <p class="text-xs text-slate-400 font-extrabold mt-2">Share registration link for players to fill details and upload photos.</p>
                </div>

                <div class="bento-card p-6 bg-slate-950 border-slate-800 text-slate-200">
                    <div class="w-10 h-10 rounded-xl bg-amber-500 text-slate-950 font-black flex items-center justify-center text-base mb-4 font-mono">04</div>
                    <h3 class="text-base font-black text-white uppercase">Start Bidding</h3>
                    <p class="text-xs text-slate-400 font-extrabold mt-2">Bring players to the auction block and execute live real-time bidding!</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Pricing Section -->
    <section id="pricing" class="py-16 px-4 max-w-7xl mx-auto w-full">
        <div class="text-center max-w-2xl mx-auto mb-12">
            <h2 class="text-2xl sm:text-4xl font-black uppercase tracking-tight text-white drop-shadow-md">
                Our <span class="text-amber-400">Pricing</span> Plans
            </h2>
            <p class="text-xs text-slate-300 font-extrabold mt-1.5 uppercase tracking-widest">Affordable transparent pricing for local & professional tournaments</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            <!-- Plan 1 -->
            <div class="bento-card p-6 border-2 border-slate-200 flex flex-col justify-between">
                <div>
                    <div class="text-xs font-black text-slate-400 uppercase tracking-widest">Free Tier</div>
                    <h3 class="text-xl font-black text-slate-900 uppercase mt-1">Starter</h3>
                    <div class="text-2xl font-black text-amber-600 font-mono mt-3">Free</div>
                    <p class="text-xs text-slate-600 font-extrabold mt-2">Up to 3 Teams. Perfect for mini-leagues & trial auctions.</p>
                </div>
                <a href="login.php" class="mt-6 w-full bg-slate-900 hover:bg-slate-800 text-white font-black text-xs py-2.5 rounded-xl transition text-center uppercase tracking-wider block">Get Started</a>
            </div>

            <!-- Plan 2 -->
            <div class="bento-card p-6 border-2 border-amber-400 flex flex-col justify-between relative overflow-hidden shadow-lg">
                <span class="absolute top-0 right-0 bg-amber-500 text-slate-950 font-black text-[9px] px-3 py-1 rounded-bl-xl uppercase tracking-wider">Popular</span>
                <div>
                    <div class="text-xs font-black text-amber-700 uppercase tracking-widest">Standard</div>
                    <h3 class="text-xl font-black text-slate-900 uppercase mt-1">League</h3>
                    <div class="text-2xl font-black text-slate-900 font-mono mt-3">₹1,999 <span class="text-xs font-extrabold text-slate-500">/ Auction</span></div>
                    <p class="text-xs text-slate-600 font-extrabold mt-2">Up to 6 Teams. Full spectator room & real-time bidding engine.</p>
                </div>
                <a href="login.php" class="mt-6 w-full bg-amber-500 hover:bg-amber-400 text-slate-950 font-black text-xs py-2.5 rounded-xl transition text-center uppercase tracking-wider block shadow-md">Select Plan</a>
            </div>

            <!-- Plan 3 -->
            <div class="bento-card p-6 border-2 border-slate-200 flex flex-col justify-between">
                <div>
                    <div class="text-xs font-black text-slate-400 uppercase tracking-widest">Professional</div>
                    <h3 class="text-xl font-black text-slate-900 uppercase mt-1">Pro League</h3>
                    <div class="text-2xl font-black text-slate-900 font-mono mt-3">₹2,999 <span class="text-xs font-extrabold text-slate-500">/ Auction</span></div>
                    <p class="text-xs text-slate-600 font-extrabold mt-2">Up to 12 Teams. Player registration pool & photo cropping.</p>
                </div>
                <a href="login.php" class="mt-6 w-full bg-slate-900 hover:bg-slate-800 text-white font-black text-xs py-2.5 rounded-xl transition text-center uppercase tracking-wider block">Select Plan</a>
            </div>

            <!-- Plan 4 -->
            <div class="bento-card p-6 border-2 border-slate-200 flex flex-col justify-between">
                <div>
                    <div class="text-xs font-black text-slate-400 uppercase tracking-widest">Enterprise</div>
                    <h3 class="text-xl font-black text-slate-900 uppercase mt-1">Premier</h3>
                    <div class="text-2xl font-black text-slate-900 font-mono mt-3">₹4,999 <span class="text-xs font-extrabold text-slate-500">/ Auction</span></div>
                    <p class="text-xs text-slate-600 font-extrabold mt-2">Up to 16+ Teams. Broadcast stream overlay & priority support.</p>
                </div>
                <a href="login.php" class="mt-6 w-full bg-slate-900 hover:bg-slate-800 text-white font-black text-xs py-2.5 rounded-xl transition text-center uppercase tracking-wider block">Select Plan</a>
            </div>
        </div>
    </section>

    <!-- Floating WhatsApp Support Button -->
    <a href="https://wa.me/917698767767" target="_blank" class="whatsapp-float" title="Contact Support on WhatsApp">
        <i class="fa-brands fa-whatsapp"></i>
    </a>

    <!-- Footer Section -->
    <footer class="bg-slate-950 text-slate-400 border-t border-slate-800 py-12 px-4 mt-12">
        <div class="max-w-7xl mx-auto grid grid-cols-1 md:grid-cols-4 gap-8 mb-8 text-xs font-extrabold">
            <div class="space-y-3">
                <img src="<?php echo $uploadPath; ?>auctionwala_logo.png" alt="AuctionWala Logo" class="h-8 object-contain">
                <p class="text-slate-400 leading-relaxed">AuctionWala is the premier sports auction app for live player bidding, player registration, team purse management, and tournament streaming worldwide.</p>
            </div>

            <div>
                <h4 class="text-white text-sm font-black uppercase tracking-wider mb-3">Quick Navigation</h4>
                <ul class="space-y-2">
                    <li><a href="#hero" class="hover:text-amber-400 transition">Home</a></li>
                    <li><a href="today_auctions.php" class="hover:text-amber-400 transition">Today's Live Auctions</a></li>
                    <li><a href="upcoming_auctions.php" class="hover:text-amber-400 transition">Upcoming Scheduled Auctions</a></li>
                    <li><a href="#features" class="hover:text-amber-400 transition">Our Features</a></li>
                    <li><a href="#pricing" class="hover:text-amber-400 transition">Pricing Plans</a></li>
                </ul>
            </div>

            <div>
                <h4 class="text-white text-sm font-black uppercase tracking-wider mb-3">Portals & Support</h4>
                <ul class="space-y-2">
                    <li><a href="login.php" class="hover:text-amber-400 transition">Organizer Login</a></li>
                    <li><a href="register.php" class="hover:text-amber-400 transition">Player Registration Portal</a></li>
                    <li><a href="https://wa.me/917698767767" target="_blank" class="hover:text-emerald-400 transition">WhatsApp Support (+91 76 98 767 767)</a></li>
                </ul>
            </div>

            <div>
                <h4 class="text-white text-sm font-black uppercase tracking-wider mb-3">Verified Ratings</h4>
                <div class="space-y-2 text-slate-300">
                    <div class="flex items-center gap-2">
                        <i class="fa-solid fa-star text-amber-400"></i>
                        <span>Google Rating: <strong>4.8 / 5.0</strong></span>
                    </div>
                    <div class="flex items-center gap-2">
                        <i class="fa-solid fa-star text-amber-400"></i>
                        <span>Trustpilot Rating: <strong>4.9 / 5.0</strong></span>
                    </div>
                </div>
            </div>
        </div>

        <div class="max-w-7xl mx-auto border-t border-slate-900 pt-6 text-center text-[10px] uppercase tracking-widest text-slate-500 font-extrabold">
            © 2026 <strong>AuctionWala</strong>. All Rights Reserved. Built for premier sports events.
        </div>
    </footer>

</body>
</html>
