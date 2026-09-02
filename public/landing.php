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
        .pro-surface {
            background-color: #f8fafc;
            color: #0f172a;
        }
        .pro-container {
            background-color: #ffffff;
            border: 1px solid #e2e8f0;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
        }
        .pro-container-hover {
            transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .pro-container-hover:hover {
            border-color: #dc2626;
            box-shadow: 0 10px 20px -5px rgba(220, 38, 38, 0.15);
            transform: translateY(-2px);
        }
        .pro-card-popular {
            border: 2px solid #dc2626;
            box-shadow: 0 10px 25px rgba(220, 38, 38, 0.15);
        }
        .hero-bg {
            background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
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
<body class="pro-surface min-h-screen flex flex-col justify-between selection:bg-red-500 selection:text-white font-inter">

    <!-- Header Navigation Bar -->
    <header class="w-full bg-white border-b border-slate-200 px-4 py-3 sm:px-8 flex items-center justify-between sticky top-0 z-50 shadow-sm">
        <div class="flex items-center gap-3">
            <a href="landing.php" class="flex items-center gap-2">
                <img src="<?php echo $uploadPath; ?>auctionwala_logo.png" alt="AuctionWala Logo" class="h-8 sm:h-9 object-contain">
            </a>
        </div>

        <nav class="hidden lg:flex items-center gap-7 font-montserrat font-bold text-xs text-slate-700 uppercase tracking-wider">
            <a href="#hero" class="hover:text-red-600 transition">Auctions</a>
            <a href="#today-auctions" class="hover:text-red-600 transition flex items-center gap-1.5">
                <span class="w-2 h-2 rounded-full bg-red-600 animate-ping"></span> Live Today
            </a>
            <a href="#upcoming-auctions" class="hover:text-red-600 transition">Tournaments</a>
            <a href="#features" class="hover:text-red-600 transition">Features</a>
            <a href="#how-it-works" class="hover:text-red-600 transition">How It Works</a>
            <a href="#pricing" class="hover:text-red-600 transition">Pricing</a>
        </nav>

        <div class="flex items-center gap-3">
            <a href="login.php" class="bg-red-600 hover:bg-red-700 text-white font-montserrat font-extrabold text-xs px-5 py-2.5 rounded-xl transition uppercase tracking-wider flex items-center gap-2 shadow-sm">
                Sign In
            </a>
        </div>
    </header>

    <!-- Full-Bleed Hero Section -->
    <section id="hero" class="hero-bg relative pt-10 pb-16 px-4 sm:px-8 w-full border-b border-slate-200">
        <div class="max-w-7xl mx-auto grid grid-cols-1 lg:grid-cols-12 gap-8 items-center relative z-10">
            <!-- Left Hero Content -->
            <div class="lg:col-span-7 space-y-6">
                <div class="inline-flex items-center gap-2 bg-red-50 border border-red-200 px-3.5 py-1.5 rounded-full text-xs font-mono font-bold text-red-700 uppercase tracking-wider shadow-sm">
                    <i class="fa-solid fa-bolt text-red-600"></i> NEXT-GEN REAL-TIME AUCTION ENGINE
                </div>

                <h1 class="text-3xl sm:text-5xl font-montserrat font-black text-slate-900 uppercase tracking-tight leading-tight">
                    Online Cricket Auction <br>
                    App For Live Player <br>
                    Bidding
                </h1>

                <p class="text-slate-600 font-inter text-sm sm:text-base leading-relaxed max-w-xl">
                    Streamline your teams, league auctions with real-time bidding, instant squad updates, player photo registration, franchise budget tracking, and broadcast-ready YouTube stream overlays.
                </p>

                <div class="flex flex-wrap items-center gap-4 pt-2">
                    <a href="login.php" class="bg-red-600 hover:bg-red-700 text-white font-montserrat font-extrabold text-xs sm:text-sm px-6 py-3.5 rounded-xl transition shadow-sm uppercase tracking-wider flex items-center gap-2">
                        Host Your Auction
                    </a>
                    <a href="#today-auctions" class="border border-slate-300 hover:border-red-600 bg-white text-slate-900 font-montserrat font-bold text-xs sm:text-sm px-6 py-3.5 rounded-xl transition uppercase tracking-wider flex items-center gap-2 shadow-sm">
                        <i class="fa-regular fa-circle-play text-amber-600"></i> Watch Live Arena
                    </a>
                </div>

                <div class="flex items-center gap-2 text-xs font-inter text-slate-600 pt-2">
                    <i class="fa-solid fa-circle-check text-emerald-600"></i>
                    <span>Google & Trustpilot Verified Software (4.9 / 5.0 Rating)</span>
                </div>
            </div>

            <!-- Right Stadium Visual Showcase -->
            <div class="lg:col-span-5 relative">
                <div class="pro-container rounded-2xl overflow-hidden border border-slate-200 shadow-md relative">
                    <img src="<?php echo $uploadPath; ?>app_bg.jpg" alt="Live Match Broadcast" class="w-full h-64 sm:h-80 object-cover">
                    <div class="absolute inset-0 bg-gradient-to-t from-slate-900 via-transparent to-transparent opacity-75"></div>
                    <div class="absolute bottom-4 left-4 right-4 flex items-center justify-between text-xs font-mono">
                        <span class="bg-red-600 text-white font-bold px-2.5 py-1 rounded-lg uppercase tracking-widest flex items-center gap-1.5 shadow">
                            <span class="w-2 h-2 rounded-full bg-white animate-ping"></span> PRO BROADCAST
                        </span>
                        <span class="bg-slate-900/90 text-white px-2.5 py-1 rounded-lg border border-slate-700">
                            1080p60 FULL HD
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Metrics Bar (4-Column Bento Stat Bar) -->
    <section class="py-6 px-4 max-w-7xl mx-auto w-full">
        <div class="bg-white rounded-2xl p-6 border border-slate-200 grid grid-cols-2 lg:grid-cols-4 gap-6 text-center shadow-sm">
            <div class="space-y-1">
                <div class="text-2xl sm:text-3xl font-montserrat font-black text-amber-700 tracking-tight">
                    <?php echo max(1890, $totalBids); ?>+
                </div>
                <div class="text-[11px] font-mono font-bold text-slate-500 uppercase tracking-widest">
                    LIVE BIDS
                </div>
            </div>

            <div class="space-y-1 border-l-0 sm:border-l border-slate-200">
                <div class="text-2xl sm:text-3xl font-montserrat font-black text-slate-900 tracking-tight">
                    <?php echo max(64, $totalTournaments); ?>+
                </div>
                <div class="text-[11px] font-mono font-bold text-slate-500 uppercase tracking-widest">
                    AUCTIONS
                </div>
            </div>

            <div class="space-y-1 border-l-0 lg:border-l border-slate-200">
                <div class="text-2xl sm:text-3xl font-montserrat font-black text-blue-700 tracking-tight">
                    <?php echo max(106, $totalTeams); ?>+
                </div>
                <div class="text-[11px] font-mono font-bold text-slate-500 uppercase tracking-widest">
                    TEAMS
                </div>
            </div>

            <div class="space-y-1 border-l-0 sm:border-l border-slate-200">
                <div class="text-2xl sm:text-3xl font-montserrat font-black text-emerald-600 tracking-tight">
                    <?php echo max(542, $totalPlayers); ?>+
                </div>
                <div class="text-[11px] font-mono font-bold text-slate-500 uppercase tracking-widest">
                    PLAYERS
                </div>
            </div>
        </div>
    </section>

    <!-- Live Player Auctions (Today) Section -->
    <section id="today-auctions" class="py-10 px-4 max-w-7xl mx-auto w-full">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h2 class="text-xl sm:text-2xl font-montserrat font-black text-slate-900 uppercase tracking-tight flex items-center gap-2">
                    Live Player Auctions (Today)
                </h2>
                <p class="text-xs font-inter text-slate-500">Active bidding rooms happening right now across the platform.</p>
            </div>
            <a href="today_auctions.php" class="text-xs font-mono font-bold text-red-600 hover:text-red-700 uppercase tracking-wider flex items-center gap-1 transition">
                VIEW ALL LIVE <i class="fa-solid fa-chevron-right text-[10px]"></i>
            </a>
        </div>

        <?php if (empty($liveAuctions)): ?>
            <div class="pro-container rounded-2xl p-10 text-center shadow-sm">
                <div class="w-12 h-12 rounded-full bg-slate-100 flex items-center justify-center mx-auto mb-3 text-slate-400 text-xl">
                    <i class="fa-solid fa-hourglass-half"></i>
                </div>
                <h3 class="text-sm font-montserrat font-bold text-slate-900 uppercase">No Auctions Live Right Now</h3>
                <p class="text-xs text-slate-500 font-inter mt-1">There are no live bidding sessions at this exact moment. Check out upcoming schedule below to see what's starting soon!</p>
            </div>
        <?php else: ?>
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <?php foreach (array_slice($liveAuctions, 0, 4) as $t): ?>
                    <div class="pro-container pro-container-hover rounded-2xl p-5 flex flex-col sm:flex-row gap-4 items-start sm:items-center relative cursor-pointer"
                         onclick="window.location.href='index.php?t_id=<?php echo $t['id']; ?>'">
                        
                        <!-- Left Logo -->
                        <div class="w-full sm:w-36 h-32 sm:h-36 rounded-xl overflow-hidden bg-slate-100 border border-slate-200 shrink-0 relative">
                            <img src="<?php echo $uploadPath; ?><?php echo htmlspecialchars($t['logo'] ?: 'auctionwala_logo.png'); ?>" alt="Logo" class="w-full h-full object-cover">
                            <span class="absolute top-2 left-2 bg-red-600 text-white font-mono font-bold text-[9px] px-2 py-0.5 rounded uppercase tracking-wider flex items-center gap-1">
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
                                <div class="bg-slate-100 text-slate-800 font-mono px-3 py-1.5 rounded-lg text-xs flex items-center gap-1.5 border border-slate-200 flex-grow truncate">
                                    <i class="fa-solid fa-location-dot text-blue-600 text-xs"></i>
                                    <span class="truncate"><?php echo htmlspecialchars($t['place'] ?? 'League Arena'); ?></span>
                                </div>
                                <a href="index.php?t_id=<?php echo $t['id']; ?>" onclick="event.stopPropagation();" class="bg-red-600 hover:bg-red-700 text-white font-montserrat font-bold text-xs px-4 py-1.5 rounded-lg transition uppercase tracking-wider shrink-0 flex items-center gap-1 shadow-sm">
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
    <section id="upcoming-auctions" class="py-10 px-4 max-w-7xl mx-auto w-full border-t border-slate-200">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h2 class="text-xl sm:text-2xl font-montserrat font-black text-slate-900 uppercase tracking-tight flex items-center gap-2">
                    Upcoming Scheduled Auctions
                </h2>
                <p class="text-xs font-inter text-slate-500">Explore upcoming tournaments and secure your spot.</p>
            </div>
            <a href="upcoming_auctions.php" class="text-xs font-mono font-bold text-amber-700 hover:text-amber-800 uppercase tracking-wider flex items-center gap-1 transition">
                VIEW ALL UPCOMING <i class="fa-solid fa-chevron-right text-[10px]"></i>
            </a>
        </div>

        <?php if (empty($upcomingAuctions)): ?>
            <div class="pro-container rounded-2xl p-10 text-center shadow-sm">
                <h3 class="text-sm font-montserrat font-bold text-slate-900 uppercase">No Upcoming Auctions Listed</h3>
                <p class="text-xs text-slate-500 font-inter mt-1">Host your league on AuctionWala today!</p>
            </div>
        <?php else: ?>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php foreach (array_slice($upcomingAuctions, 0, 3) as $t): ?>
                    <div class="pro-container pro-container-hover rounded-2xl p-5 flex flex-col justify-between space-y-4 relative cursor-pointer"
                         onclick="window.location.href='index.php?t_id=<?php echo $t['id']; ?>'">
                        
                        <!-- Top Image Banner -->
                        <div class="w-full h-40 rounded-xl overflow-hidden bg-slate-100 border border-slate-200 relative">
                            <img src="<?php echo $uploadPath; ?><?php echo htmlspecialchars($t['logo'] ?: 'app_bg.jpg'); ?>" alt="Tournament Banner" class="w-full h-full object-cover">
                            <div class="absolute top-2 right-2">
                                <span class="bg-amber-500 text-slate-950 font-mono font-bold text-[9px] px-2.5 py-1 rounded uppercase tracking-wider shadow-sm">
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
                                <a href="register.php?t_id=<?php echo $t['id']; ?>" onclick="event.stopPropagation();" class="w-full bg-red-600 hover:bg-red-700 text-white font-montserrat font-bold text-xs py-2.5 rounded-xl transition uppercase tracking-wider text-center block shadow-sm">
                                    REGISTER NOW
                                </a>
                            <?php else: ?>
                                <a href="index.php?t_id=<?php echo $t['id']; ?>" onclick="event.stopPropagation();" class="w-full border border-slate-300 hover:border-red-600 bg-slate-50 text-slate-900 font-montserrat font-bold text-xs py-2.5 rounded-xl transition uppercase tracking-wider text-center block">
                                    VIEW DETAILS
                                </a>
                            <?php endif; ?>
                        </div>

                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>

    <!-- WhatsApp Floating Button -->
    <a href="https://wa.me/917698767767?text=Hi%20AuctionWala%20Support%2C%20I%20want%20to%20inquire%20about%20hosting%20a%20league" target="_blank" class="whatsapp-float" title="Contact Live WhatsApp Support">
        <i class="fa-brands fa-whatsapp"></i>
    </a>

    <!-- Footer -->
    <footer class="w-full bg-white border-t border-slate-200 py-6 px-4 text-center text-xs text-slate-500 font-inter mt-10">
        <p>© 2026 AuctionWala Premier League. Built for premium turf events.</p>
    </footer>
</body>
</html>
