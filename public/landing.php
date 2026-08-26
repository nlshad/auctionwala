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
    <title>AuctionWala Pro-Broadcast — #1 Sports Auction App for Cricket, Football & Turf Leagues</title>
    <meta name="description" content="AuctionWala is the premier sports auction app for live player bidding, player registration, team purse management, and tournament streaming worldwide.">
    <link rel="icon" type="image/png" href="<?php echo $uploadPath; ?>auctionwala_logo.png">
    <?php require_once 'components/ui_head.php'; ?>
    <style>
        .pro-surface {
            background-color: #0b1326;
            color: #dae2fd;
        }
        .pro-container {
            background-color: #131b2e;
            border: 1px solid #1e293d;
        }
        .pro-container-hover {
            transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .pro-container-hover:hover {
            border-color: #ff5451;
            box-shadow: 0 10px 30px -10px rgba(255, 84, 81, 0.25);
            transform: translateY(-2px);
        }
        .pro-card-popular {
            border: 2px solid #ff5451;
            box-shadow: 0 0 25px rgba(255, 84, 81, 0.2);
        }
        .hero-bg {
            background: linear-gradient(180deg, rgba(6, 14, 32, 0.45) 0%, rgba(11, 19, 38, 0.95) 100%), url('<?php echo $uploadPath; ?>app_bg.jpg') center/cover no-repeat;
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
    <header class="w-full bg-[#060e20]/95 backdrop-blur-md border-b border-[#1e293d] px-4 py-3 sm:px-8 flex items-center justify-between sticky top-0 z-50">
        <div class="flex items-center gap-3">
            <a href="landing.php" class="flex items-center gap-2">
                <img src="<?php echo $uploadPath; ?>auctionwala_logo.png" alt="AuctionWala Logo" class="h-8 sm:h-9 object-contain">
            </a>
        </div>

        <nav class="hidden lg:flex items-center gap-7 font-montserrat font-bold text-xs text-[#dae2fd] uppercase tracking-wider">
            <a href="#hero" class="hover:text-[#ff5451] transition">Auctions</a>
            <a href="#today-auctions" class="hover:text-[#ff5451] transition flex items-center gap-1.5">
                <span class="w-2 h-2 rounded-full bg-[#ff5451] animate-ping"></span> Live Today
            </a>
            <a href="#upcoming-auctions" class="hover:text-[#ff5451] transition">Tournaments</a>
            <a href="#features" class="hover:text-[#ff5451] transition">Features</a>
            <a href="#how-it-works" class="hover:text-[#ff5451] transition">How It Works</a>
            <a href="#pricing" class="hover:text-[#ff5451] transition">Pricing</a>
        </nav>

        <div class="flex items-center gap-3">
            <a href="login.php" class="bg-[#ff5451] hover:bg-[#ef4444] text-white font-montserrat font-extrabold text-xs px-5 py-2.5 rounded transition uppercase tracking-wider flex items-center gap-2 shadow-lg shadow-red-500/20">
                Sign In
            </a>
        </div>
    </header>

    <!-- Full-Bleed Stadium Hero Section -->
    <section id="hero" class="hero-bg relative pt-10 pb-16 px-4 sm:px-8 w-full border-b border-[#1e293d]">
        <div class="max-w-7xl mx-auto grid grid-cols-1 lg:grid-cols-12 gap-8 items-center relative z-10">
            <!-- Left Hero Content -->
            <div class="lg:col-span-7 space-y-6">
                <div class="inline-flex items-center gap-2 bg-[#060e20]/80 backdrop-blur border border-[#ff5451]/50 px-3 py-1 rounded text-xs font-mono text-[#ffb3ad] uppercase tracking-wider shadow">
                    <i class="fa-solid fa-bolt text-[#ff5451]"></i> NEXT-GEN REAL-TIME AUCTION ENGINE
                </div>

                <h1 class="text-3xl sm:text-5xl font-montserrat font-black text-[#F8FAFC] uppercase tracking-tight leading-tight drop-shadow-md">
                    Online Cricket Auction <br>
                    App For Live Player <br>
                    Bidding
                </h1>

                <p class="text-[#dae2fd]/90 font-inter text-sm sm:text-base leading-relaxed max-w-xl">
                    Streamline your teams, league auctions with real-time bidding, instant squad updates, player photo registration, franchise budget tracking, and broadcast-ready YouTube stream overlays.
                </p>

                <div class="flex flex-wrap items-center gap-4 pt-2">
                    <a href="login.php" class="bg-[#ff5451] hover:bg-[#ef4444] text-white font-montserrat font-extrabold text-xs sm:text-sm px-6 py-3 rounded transition shadow-lg uppercase tracking-wider flex items-center gap-2 shadow-red-500/30">
                        Host Your Auction
                    </a>
                    <a href="#today-auctions" class="border border-[#31394d] hover:border-[#ff5451] bg-[#060e20]/80 backdrop-blur text-[#F8FAFC] font-montserrat font-bold text-xs sm:text-sm px-6 py-3 rounded transition uppercase tracking-wider flex items-center gap-2">
                        <i class="fa-regular fa-circle-play text-[#ffb95f]"></i> Watch Live Arena
                    </a>
                </div>

                <div class="flex items-center gap-2 text-xs font-inter text-[#dae2fd]/80 pt-2">
                    <i class="fa-solid fa-circle-check text-[#22C55E]"></i>
                    <span>Google & Trustpilot Verified Software (4.9 / 5.0 Rating)</span>
                </div>
            </div>

            <!-- Right Stadium Visual Showcase -->
            <div class="lg:col-span-5 relative">
                <div class="pro-container rounded-xl overflow-hidden border border-[#1e293d] shadow-2xl relative">
                    <img src="<?php echo $uploadPath; ?>app_bg.jpg" alt="Live Match Broadcast" class="w-full h-64 sm:h-80 object-cover">
                    <div class="absolute inset-0 bg-gradient-to-t from-[#060e20] via-transparent to-transparent opacity-85"></div>
                    <div class="absolute bottom-4 left-4 right-4 flex items-center justify-between text-xs font-mono">
                        <span class="bg-[#ff5451] text-white font-bold px-2.5 py-1 rounded uppercase tracking-widest flex items-center gap-1.5 shadow">
                            <span class="w-2 h-2 rounded-full bg-white animate-pulse"></span> PRO BROADCAST
                        </span>
                        <span class="bg-[#171f33]/90 text-[#7bd0ff] px-2.5 py-1 rounded border border-[#1e293d]">
                            1080p60 FULL HD
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Metrics Bar (4-Column Bento Stat Bar) -->
    <section class="py-6 px-4 max-w-7xl mx-auto w-full">
        <div class="bg-[#060e20]/90 backdrop-blur-md rounded-xl p-6 border border-[#1e293d] grid grid-cols-2 lg:grid-cols-4 gap-6 text-center shadow-xl">
            <div class="space-y-1">
                <div class="text-2xl sm:text-3xl font-montserrat font-black text-[#ffb95f] tracking-tight">
                    <?php echo max(1890, $totalBids); ?>+
                </div>
                <div class="text-[11px] font-mono font-medium text-[#94A3B8] uppercase tracking-widest">
                    LIVE BIDS
                </div>
            </div>

            <div class="space-y-1 border-l-0 sm:border-l border-[#1e293d]">
                <div class="text-2xl sm:text-3xl font-montserrat font-black text-[#F8FAFC] tracking-tight">
                    <?php echo max(64, $totalTournaments); ?>+
                </div>
                <div class="text-[11px] font-mono font-medium text-[#94A3B8] uppercase tracking-widest">
                    AUCTIONS
                </div>
            </div>

            <div class="space-y-1 border-l-0 lg:border-l border-[#1e293d]">
                <div class="text-2xl sm:text-3xl font-montserrat font-black text-[#7bd0ff] tracking-tight">
                    <?php echo max(106, $totalTeams); ?>+
                </div>
                <div class="text-[11px] font-mono font-medium text-[#94A3B8] uppercase tracking-widest">
                    TEAMS
                </div>
            </div>

            <div class="space-y-1 border-l-0 sm:border-l border-[#1e293d]">
                <div class="text-2xl sm:text-3xl font-montserrat font-black text-[#22C55E] tracking-tight">
                    <?php echo max(542, $totalPlayers); ?>+
                </div>
                <div class="text-[11px] font-mono font-medium text-[#94A3B8] uppercase tracking-widest">
                    PLAYERS
                </div>
            </div>
        </div>
    </section>

    <!-- Live Player Auctions (Today) Section -->
    <section id="today-auctions" class="py-10 px-4 max-w-7xl mx-auto w-full">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h2 class="text-xl sm:text-2xl font-montserrat font-black text-[#F8FAFC] uppercase tracking-tight flex items-center gap-2">
                    Live Player Auctions (Today)
                </h2>
                <p class="text-xs font-inter text-[#94A3B8]">Active bidding rooms happening right now across the platform.</p>
            </div>
            <a href="today_auctions.php" class="text-xs font-mono font-bold text-[#ff5451] hover:text-[#ffb3ad] uppercase tracking-wider flex items-center gap-1 transition">
                VIEW ALL LIVE <i class="fa-solid fa-chevron-right text-[10px]"></i>
            </a>
        </div>

        <?php if (empty($liveAuctions)): ?>
            <div class="pro-container rounded-xl p-10 text-center shadow-lg">
                <div class="w-12 h-12 rounded-full bg-[#171f33] flex items-center justify-center mx-auto mb-3 text-[#94A3B8] text-xl">
                    <i class="fa-solid fa-hourglass-half"></i>
                </div>
                <h3 class="text-sm font-montserrat font-bold text-[#F8FAFC] uppercase">No Auctions Live Right Now</h3>
                <p class="text-xs text-[#94A3B8] font-inter mt-1">There are no live bidding sessions at this exact moment. Check out upcoming schedule below to see what's starting soon!</p>
            </div>
        <?php else: ?>
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <?php foreach (array_slice($liveAuctions, 0, 4) as $t): ?>
                    <div class="pro-container pro-container-hover rounded-xl p-5 flex flex-col sm:flex-row gap-4 items-start sm:items-center relative cursor-pointer"
                         onclick="window.location.href='index.php?t_id=<?php echo $t['id']; ?>'">
                        
                        <!-- Left Logo -->
                        <div class="w-full sm:w-36 h-32 sm:h-36 rounded-lg overflow-hidden bg-[#060e20] border border-[#1e293d] shrink-0 relative">
                            <img src="<?php echo $uploadPath; ?><?php echo htmlspecialchars($t['logo'] ?: 'auctionwala_logo.png'); ?>" alt="Logo" class="w-full h-full object-cover">
                            <span class="absolute top-2 left-2 bg-[#ff5451] text-white font-mono font-bold text-[9px] px-2 py-0.5 rounded uppercase tracking-wider flex items-center gap-1">
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
                                <a href="index.php?t_id=<?php echo $t['id']; ?>" onclick="event.stopPropagation();" class="bg-[#ff5451] hover:bg-[#ef4444] text-white font-montserrat font-bold text-xs px-4 py-1.5 rounded transition uppercase tracking-wider shrink-0 flex items-center gap-1">
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
            <div>
                <h2 class="text-xl sm:text-2xl font-montserrat font-black text-[#F8FAFC] uppercase tracking-tight flex items-center gap-2">
                    Upcoming Scheduled Auctions
                </h2>
                <p class="text-xs font-inter text-[#94A3B8]">Explore upcoming tournaments and secure your spot.</p>
            </div>
            <a href="upcoming_auctions.php" class="text-xs font-mono font-bold text-[#ffb95f] hover:text-[#ffddb8] uppercase tracking-wider flex items-center gap-1 transition">
                VIEW ALL UPCOMING <i class="fa-solid fa-chevron-right text-[10px]"></i>
            </a>
        </div>

        <?php if (empty($upcomingAuctions)): ?>
            <div class="pro-container rounded-xl p-8 text-center shadow-md">
                <h3 class="text-sm font-montserrat font-bold text-[#F8FAFC] uppercase">No Upcoming Auctions Listed</h3>
                <p class="text-xs text-[#94A3B8] font-inter mt-1">Host your league on AuctionWala today!</p>
            </div>
        <?php else: ?>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php foreach (array_slice($upcomingAuctions, 0, 3) as $t): ?>
                    <div class="pro-container pro-container-hover rounded-xl p-5 flex flex-col justify-between space-y-4 relative cursor-pointer"
                         onclick="window.location.href='index.php?t_id=<?php echo $t['id']; ?>'">
                        
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
                                <a href="register.php?t_id=<?php echo $t['id']; ?>" onclick="event.stopPropagation();" class="w-full bg-[#ff5451] hover:bg-[#ef4444] text-white font-montserrat font-bold text-xs py-2.5 rounded transition uppercase tracking-wider text-center block shadow">
                                    REGISTER NOW
                                </a>
                            <?php else: ?>
                                <a href="index.php?t_id=<?php echo $t['id']; ?>" onclick="event.stopPropagation();" class="w-full border border-[#31394d] hover:border-[#ff5451] bg-[#171f33] text-[#F8FAFC] font-montserrat font-bold text-xs py-2.5 rounded transition uppercase tracking-wider text-center block">
                                    VIEW DETAILS
                                </a>
                            <?php endif; ?>
                        </div>

                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>

    <!-- Core Auction Features Section -->
    <section id="features" class="py-16 px-4 max-w-7xl mx-auto w-full">
        <div class="text-center max-w-2xl mx-auto mb-12">
            <h2 class="text-2xl sm:text-4xl font-montserrat font-black text-[#F8FAFC] uppercase tracking-tight">
                Core Auction Features
            </h2>
            <p class="text-xs text-[#94A3B8] font-inter mt-1.5">Everything you need to host professional sports auctions online with real-time sync.</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <!-- Feature 1 -->
            <div class="pro-container pro-container-hover rounded-xl p-6">
                <div class="w-10 h-10 rounded bg-[#ff5451]/15 border border-[#ff5451]/30 flex items-center justify-center text-[#ff5451] mb-4 text-lg">
                    <i class="fa-solid fa-gavel"></i>
                </div>
                <h3 class="text-base font-montserrat font-bold text-[#F8FAFC] uppercase">Live Bidding</h3>
                <p class="text-xs text-[#94A3B8] font-inter mt-2 leading-relaxed">Experience real-time player bidding with dynamic updates and seamless functionality across all spectator screens.</p>
            </div>

            <!-- Feature 2 -->
            <div class="pro-container pro-container-hover rounded-xl p-6">
                <div class="w-10 h-10 rounded bg-[#ffb95f]/15 border border-[#ffb95f]/30 flex items-center justify-center text-[#ffb95f] mb-4 text-lg">
                    <i class="fa-solid fa-trophy"></i>
                </div>
                <h3 class="text-base font-montserrat font-bold text-[#F8FAFC] uppercase">Tournament Management</h3>
                <p class="text-xs text-[#94A3B8] font-inter mt-2 leading-relaxed">Create and manage tournaments effortlessly with customizable settings for teams, players, bid increments, and purse limits.</p>
            </div>

            <!-- Feature 3 -->
            <div class="pro-container pro-container-hover rounded-xl p-6">
                <div class="w-10 h-10 rounded bg-[#7bd0ff]/15 border border-[#7bd0ff]/30 flex items-center justify-center text-[#7bd0ff] mb-4 text-lg">
                    <i class="fa-solid fa-users-gear"></i>
                </div>
                <h3 class="text-base font-montserrat font-bold text-[#F8FAFC] uppercase">Franchise Portals</h3>
                <p class="text-xs text-[#94A3B8] font-inter mt-2 leading-relaxed">Dedicated portals for franchise team owners to track live purse balance, squad roster Composition, and bid history.</p>
            </div>

            <!-- Feature 4 -->
            <div class="pro-container pro-container-hover rounded-xl p-6">
                <div class="w-10 h-10 rounded bg-[#ff5451]/15 border border-[#ff5451]/30 flex items-center justify-center text-[#ff5451] mb-4 text-lg">
                    <i class="fa-solid fa-id-card"></i>
                </div>
                <h3 class="text-base font-montserrat font-bold text-[#F8FAFC] uppercase">Player Registration</h3>
                <p class="text-xs text-[#94A3B8] font-inter mt-2 leading-relaxed">Allow players to register online with profile photos, stats, and base prices ready for the live player auction pool.</p>
            </div>

            <!-- Feature 5 -->
            <div class="pro-container pro-container-hover rounded-xl p-6">
                <div class="w-10 h-10 rounded bg-[#22C55E]/15 border border-[#22C55E]/30 flex items-center justify-center text-[#22C55E] mb-4 text-lg">
                    <i class="fa-solid fa-rotate"></i>
                </div>
                <h3 class="text-base font-montserrat font-bold text-[#F8FAFC] uppercase">Real-Time Concurrency</h3>
                <p class="text-xs text-[#94A3B8] font-inter mt-2 leading-relaxed">Ultra-low latency socket state engine ensures instantaneous bid updates across mobile and web users.</p>
            </div>

            <!-- Feature 6 -->
            <div class="pro-container pro-container-hover rounded-xl p-6">
                <div class="w-10 h-10 rounded bg-[#7bd0ff]/15 border border-[#7bd0ff]/30 flex items-center justify-center text-[#7bd0ff] mb-4 text-lg">
                    <i class="fa-solid fa-share-nodes"></i>
                </div>
                <h3 class="text-base font-montserrat font-bold text-[#F8FAFC] uppercase">Shareable Spectator Links</h3>
                <p class="text-xs text-[#94A3B8] font-inter mt-2 leading-relaxed">Share unique tournament room links for live public spectator view without requiring login rights.</p>
            </div>
        </div>
    </section>

    <!-- How It Works Section -->
    <section id="how-it-works" class="py-16 bg-[#060e20] border-y border-[#1e293d]">
        <div class="max-w-7xl mx-auto px-4">
            <div class="text-center max-w-2xl mx-auto mb-12">
                <h2 class="text-2xl sm:text-4xl font-montserrat font-black text-[#F8FAFC] uppercase tracking-tight">
                    How It Works
                </h2>
                <p class="text-xs text-[#94A3B8] font-inter mt-1.5">Get your professional auction up and running in four simple steps.</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                <!-- Step 1 -->
                <div class="pro-container rounded-xl p-6 text-center space-y-3">
                    <div class="w-12 h-12 rounded-full border-2 border-[#ff5451] bg-[#171f33] text-[#ff5451] font-mono font-bold flex items-center justify-center text-lg mx-auto shadow-lg">
                        01
                    </div>
                    <h3 class="text-base font-montserrat font-bold text-[#F8FAFC] uppercase">Create Auction</h3>
                    <p class="text-xs text-[#94A3B8] font-inter">Set up your tournament rules, total purse, and base prices.</p>
                </div>

                <!-- Step 2 -->
                <div class="pro-container rounded-xl p-6 text-center space-y-3">
                    <div class="w-12 h-12 rounded-full border-2 border-[#ffb95f] bg-[#171f33] text-[#ffb95f] font-mono font-bold flex items-center justify-center text-lg mx-auto shadow-lg">
                        02
                    </div>
                    <h3 class="text-base font-montserrat font-bold text-[#F8FAFC] uppercase">Add Teams</h3>
                    <p class="text-xs text-[#94A3B8] font-inter">Register franchise teams and assign initial purse budgets.</p>
                </div>

                <!-- Step 3 -->
                <div class="pro-container rounded-xl p-6 text-center space-y-3">
                    <div class="w-12 h-12 rounded-full border-2 border-[#7bd0ff] bg-[#171f33] text-[#7bd0ff] font-mono font-bold flex items-center justify-center text-lg mx-auto shadow-lg">
                        03
                    </div>
                    <h3 class="text-base font-montserrat font-bold text-[#F8FAFC] uppercase">Add Players</h3>
                    <p class="text-xs text-[#94A3B8] font-inter">Import players or share a link for self-registration.</p>
                </div>

                <!-- Step 4 -->
                <div class="pro-container rounded-xl p-6 text-center space-y-3">
                    <div class="w-12 h-12 rounded-full border-2 border-[#22C55E] bg-[#171f33] text-[#22C55E] font-mono font-bold flex items-center justify-center text-lg mx-auto shadow-lg">
                        04
                    </div>
                    <h3 class="text-base font-montserrat font-bold text-[#F8FAFC] uppercase">Start Bidding</h3>
                    <p class="text-xs text-[#94A3B8] font-inter">Launch live auction and execute real-time player bids.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Pricing Plans Section -->
    <section id="pricing" class="py-16 px-4 max-w-7xl mx-auto w-full">
        <div class="text-center max-w-2xl mx-auto mb-12">
            <h2 class="text-2xl sm:text-4xl font-montserrat font-black text-[#F8FAFC] uppercase tracking-tight">
                Pricing Plans
            </h2>
            <p class="text-xs text-[#94A3B8] font-inter mt-1.5">Choose the perfect plan for your tournament size and needs.</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            <!-- Plan 1: Starter -->
            <div class="pro-container rounded-xl p-6 flex flex-col justify-between space-y-6">
                <div class="space-y-4">
                    <div>
                        <h3 class="text-lg font-montserrat font-bold text-[#F8FAFC] uppercase">Starter</h3>
                        <div class="text-2xl font-montserrat font-black text-[#F8FAFC] mt-2">
                            ₹999 <span class="text-xs text-[#94A3B8] font-inter font-normal">/ auction</span>
                        </div>
                    </div>
                    <ul class="space-y-2 text-xs font-inter text-[#94A3B8]">
                        <li class="flex items-center gap-2">
                            <i class="fa-solid fa-circle-check text-[#22C55E]"></i> Up to 4 Teams
                        </li>
                        <li class="flex items-center gap-2">
                            <i class="fa-solid fa-circle-check text-[#22C55E]"></i> Up to 60 Players
                        </li>
                        <li class="flex items-center gap-2">
                            <i class="fa-solid fa-circle-check text-[#22C55E]"></i> Real-time Arena
                        </li>
                    </ul>
                </div>
                <a href="login.php" class="w-full border border-[#31394d] hover:border-[#ff5451] bg-[#171f33] text-[#F8FAFC] font-montserrat font-bold text-xs py-2.5 rounded transition uppercase tracking-wider text-center block">
                    CHOOSE PLAN
                </a>
            </div>

            <!-- Plan 2: League (Popular Highlighted) -->
            <div class="pro-container pro-card-popular rounded-xl p-6 flex flex-col justify-between space-y-6 relative overflow-hidden">
                <span class="absolute top-0 right-0 bg-[#ff5451] text-white font-mono font-bold text-[9px] px-3 py-1 rounded-bl uppercase tracking-wider">
                    POPULAR
                </span>
                <div class="space-y-4">
                    <div>
                        <h3 class="text-lg font-montserrat font-bold text-[#F8FAFC] uppercase">League</h3>
                        <div class="text-2xl font-montserrat font-black text-[#F8FAFC] mt-2">
                            ₹1,999 <span class="text-xs text-[#94A3B8] font-inter font-normal">/ auction</span>
                        </div>
                    </div>
                    <ul class="space-y-2 text-xs font-inter text-[#94A3B8]">
                        <li class="flex items-center gap-2 text-[#F8FAFC]">
                            <i class="fa-solid fa-circle-check text-[#22C55E]"></i> Up to 8 Teams
                        </li>
                        <li class="flex items-center gap-2 text-[#F8FAFC]">
                            <i class="fa-solid fa-circle-check text-[#22C55E]"></i> Up to 120 Players
                        </li>
                        <li class="flex items-center gap-2 text-[#F8FAFC]">
                            <i class="fa-solid fa-circle-check text-[#22C55E]"></i> Shareable Domain
                        </li>
                        <li class="flex items-center gap-2 text-[#F8FAFC]">
                            <i class="fa-solid fa-circle-check text-[#22C55E]"></i> Player Self-Registration
                        </li>
                    </ul>
                </div>
                <a href="login.php" class="w-full bg-[#ff5451] hover:bg-[#ef4444] text-white font-montserrat font-bold text-xs py-2.5 rounded transition uppercase tracking-wider text-center block shadow">
                    CHOOSE PLAN
                </a>
            </div>

            <!-- Plan 3: Pro League -->
            <div class="pro-container rounded-xl p-6 flex flex-col justify-between space-y-6">
                <div class="space-y-4">
                    <div>
                        <h3 class="text-lg font-montserrat font-bold text-[#F8FAFC] uppercase">Pro League</h3>
                        <div class="text-2xl font-montserrat font-black text-[#F8FAFC] mt-2">
                            ₹2,999 <span class="text-xs text-[#94A3B8] font-inter font-normal">/ auction</span>
                        </div>
                    </div>
                    <ul class="space-y-2 text-xs font-inter text-[#94A3B8]">
                        <li class="flex items-center gap-2">
                            <i class="fa-solid fa-circle-check text-[#22C55E]"></i> Up to 12 Teams
                        </li>
                        <li class="flex items-center gap-2">
                            <i class="fa-solid fa-circle-check text-[#22C55E]"></i> Up to 200 Players
                        </li>
                        <li class="flex items-center gap-2">
                            <i class="fa-solid fa-circle-check text-[#22C55E]"></i> Pro Live Analytics
                        </li>
                        <li class="flex items-center gap-2">
                            <i class="fa-solid fa-circle-check text-[#22C55E]"></i> Stream Overlay
                        </li>
                    </ul>
                </div>
                <a href="login.php" class="w-full border border-[#31394d] hover:border-[#ff5451] bg-[#171f33] text-[#F8FAFC] font-montserrat font-bold text-xs py-2.5 rounded transition uppercase tracking-wider text-center block">
                    CHOOSE PLAN
                </a>
            </div>

            <!-- Plan 4: Premier -->
            <div class="pro-container rounded-xl p-6 flex flex-col justify-between space-y-6">
                <div class="space-y-4">
                    <div>
                        <h3 class="text-lg font-montserrat font-bold text-[#F8FAFC] uppercase">Premier</h3>
                        <div class="text-2xl font-montserrat font-black text-[#F8FAFC] mt-2">
                            ₹4,999 <span class="text-xs text-[#94A3B8] font-inter font-normal">/ auction</span>
                        </div>
                    </div>
                    <ul class="space-y-2 text-xs font-inter text-[#94A3B8]">
                        <li class="flex items-center gap-2">
                            <i class="fa-solid fa-circle-check text-[#22C55E]"></i> Unlimited Teams
                        </li>
                        <li class="flex items-center gap-2">
                            <i class="fa-solid fa-circle-check text-[#22C55E]"></i> Unlimited Players
                        </li>
                        <li class="flex items-center gap-2">
                            <i class="fa-solid fa-circle-check text-[#22C55E]"></i> Priority Support
                        </li>
                        <li class="flex items-center gap-2">
                            <i class="fa-solid fa-circle-check text-[#22C55E]"></i> Custom Branding
                        </li>
                    </ul>
                </div>
                <a href="login.php" class="w-full border border-[#31394d] hover:border-[#ff5451] bg-[#171f33] text-[#F8FAFC] font-montserrat font-bold text-xs py-2.5 rounded transition uppercase tracking-wider text-center block">
                    CHOOSE PLAN
                </a>
            </div>
        </div>
    </section>

    <!-- Floating WhatsApp Support Button -->
    <a href="https://wa.me/917698767767" target="_blank" class="whatsapp-float" title="Contact Support on WhatsApp">
        <i class="fa-brands fa-whatsapp"></i>
    </a>

    <!-- Footer Section -->
    <footer class="bg-[#060e20] border-t border-[#1e293d] py-12 px-4 mt-12">
        <div class="max-w-7xl mx-auto grid grid-cols-1 md:grid-cols-4 gap-8 mb-8 text-xs">
            <div class="space-y-3">
                <img src="<?php echo $uploadPath; ?>auctionwala_logo.png" alt="AuctionWala Logo" class="h-8 object-contain">
                <p class="text-[#94A3B8] leading-relaxed">The ultimate platform for hosting professional-grade online sports auctions. Real-time bidding, team account management, and broadcast-ready features.</p>
                <div class="flex items-center gap-3 pt-2 text-[#94A3B8]">
                    <a href="https://wa.me/917698767767" target="_blank" class="hover:text-white"><i class="fa-brands fa-whatsapp text-lg"></i></a>
                    <a href="#" class="hover:text-white"><i class="fa-solid fa-share-nodes text-lg"></i></a>
                </div>
            </div>

            <div>
                <h4 class="font-montserrat font-bold text-[#F8FAFC] text-xs uppercase tracking-wider mb-3">QUICK NAVIGATION</h4>
                <ul class="space-y-2 text-[#94A3B8]">
                    <li><a href="#today-auctions" class="hover:text-[#ff5451] transition">Live Auctions</a></li>
                    <li><a href="#upcoming-auctions" class="hover:text-[#ff5451] transition">Upcoming Tournaments</a></li>
                    <li><a href="#features" class="hover:text-[#ff5451] transition">Feature Overview</a></li>
                    <li><a href="#pricing" class="hover:text-[#ff5451] transition">Pricing Plans</a></li>
                    <li><a href="#how-it-works" class="hover:text-[#ff5451] transition">About Us</a></li>
                </ul>
            </div>

            <div>
                <h4 class="font-montserrat font-bold text-[#F8FAFC] text-xs uppercase tracking-wider mb-3">PORTALS & SUPPORT</h4>
                <ul class="space-y-2 text-[#94A3B8]">
                    <li><a href="login.php" class="hover:text-[#ff5451] transition">Organizer Login</a></li>
                    <li><a href="login.php" class="hover:text-[#ff5451] transition">Franchise Portal</a></li>
                    <li><a href="register.php" class="hover:text-[#ff5451] transition">Player Registration</a></li>
                    <li><a href="https://wa.me/917698767767" target="_blank" class="hover:text-[#22C55E] transition">Help Center & FAQs</a></li>
                    <li><a href="https://wa.me/917698767767" target="_blank" class="hover:text-[#22C55E] transition">Contact Support</a></li>
                </ul>
            </div>

            <div class="space-y-3">
                <h4 class="font-montserrat font-bold text-[#F8FAFC] text-xs uppercase tracking-wider mb-3">AUTHENTICATED RATINGS</h4>
                <div class="pro-container rounded p-3 text-xs space-y-1.5">
                    <div class="flex items-center gap-1.5 text-[#ffb95f]">
                        <i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star-half-stroke"></i>
                        <span class="font-mono text-[#F8FAFC] font-bold">4.9 / 5.0</span>
                    </div>
                    <p class="text-[10px] text-[#94A3B8]">Trustpilot & Google Verified Sports Software</p>
                </div>
            </div>
        </div>

        <div class="max-w-7xl mx-auto border-t border-[#1e293d] pt-6 flex flex-col sm:flex-row items-center justify-between text-[11px] text-[#94A3B8] font-inter gap-4">
            <div class="flex gap-4">
                <a href="#" class="hover:text-white">Privacy Policy</a>
                <a href="#" class="hover:text-white">Terms of Service</a>
                <a href="#" class="hover:text-white">API Documentation</a>
                <a href="#" class="hover:text-white">Press Kit</a>
            </div>
            <div>
                © 2026 AuctionWala. All Rights Reserved. Broadcast Ready Systems.
            </div>
        </div>
    </footer>

</body>
</html>
