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
    <title>AuctionWala — Real-Time SaaS Cricket & Sports Auction Platform</title>
    <link rel="icon" type="image/png" href="<?php echo $uploadPath; ?>auctionwala_logo.png">
    <?php require_once 'components/ui_head.php'; ?>
    <style>
        .glass-hero {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(24px);
            border: 1px solid rgba(226, 232, 240, 0.9);
            box-shadow: 0 20px 50px -10px rgba(0, 0, 0, 0.06);
        }
        .bento-card {
            background: rgba(255, 255, 255, 0.92);
            backdrop-filter: blur(16px);
            border: 1px solid rgba(226, 232, 240, 0.9);
            border-radius: 1.25rem;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.04);
            transition: all 0.25s ease;
        }
        .bento-card:hover {
            border-color: rgba(212, 163, 12, 0.5);
            box-shadow: 0 15px 35px -5px rgba(212, 163, 12, 0.15);
            transform: translateY(-2px);
        }
        .bento-card-live {
            border-color: rgba(239, 68, 68, 0.4);
            box-shadow: 0 0 25px rgba(239, 68, 68, 0.1);
        }
        .accordion-content {
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.3s ease;
        }
    </style>
</head>
<body class="text-slate-800 min-h-screen flex flex-col justify-between selection:bg-gold-500 selection:text-black">

    <!-- Header Navigation Bar -->
    <header class="w-full glass-panel border-b border-slate-200 px-4 py-3 sm:px-8 sm:py-4 flex items-center justify-between sticky top-0 z-50">
        <div class="flex items-center gap-3">
            <a href="landing.php" class="flex items-center gap-2">
                <img src="<?php echo $uploadPath; ?>auctionwala_logo.png" alt="AuctionWala Logo" class="h-9 sm:h-10 object-contain mix-blend-multiply">
                <span class="bg-red-600 text-white text-[8px] font-black px-1.5 py-0.5 rounded tracking-widest uppercase animate-pulse">LIVE</span>
            </a>
        </div>
        <nav class="hidden md:flex items-center gap-6 text-xs uppercase font-extrabold tracking-wider text-slate-700">
            <a href="today_auctions.php" class="text-red-600 hover:text-red-700 transition flex items-center gap-1.5 font-black">
                <span class="w-2 h-2 rounded-full bg-red-600 animate-ping"></span> Today's Live
            </a>
            <a href="upcoming_auctions.php" class="text-amber-800 hover:text-amber-900 transition font-black flex items-center gap-1">
                <i class="fa-solid fa-calendar-days text-amber-600"></i> Upcoming Auctions
            </a>
            <a href="#completed-auctions" class="hover:text-slate-900 transition">Completed</a>
            <a href="#features" class="hover:text-slate-900 transition">Features</a>
            <a href="#help" class="hover:text-slate-900 transition">Help & FAQ</a>
        </nav>

        <!-- Right Navigation Action Links -->
        <div class="flex items-center gap-2 sm:gap-3">
            <a href="login.php" class="bg-gradient-to-r from-gold-500 to-amber-500 hover:from-gold-400 hover:to-amber-400 text-zinc-950 font-extrabold px-4 py-2 rounded-xl text-xs uppercase tracking-wider shadow-lg shadow-gold-500/20 transition flex items-center gap-1.5">
                <i class="fa-solid fa-crown text-sm"></i> Host Your Auction
            </a>
        </div>
    </header>

    <main class="w-full flex-1 space-y-16">

        <!-- 1. HERO BANNER SECTION -->
        <section class="relative px-4 pt-12 pb-8 max-w-7xl mx-auto">
            <div class="glass-hero rounded-3xl p-6 sm:p-12 relative overflow-hidden text-center border border-slate-200">
                <div class="absolute -top-24 -left-24 w-96 h-96 bg-gold-500/10 rounded-full blur-3xl pointer-events-none"></div>
                <div class="absolute -bottom-24 -right-24 w-96 h-96 bg-amber-600/10 rounded-full blur-3xl pointer-events-none"></div>

                <span class="inline-flex items-center gap-2 bg-gold-500/10 border border-gold-500/30 text-gold-700 text-xs font-extrabold px-4 py-1.5 rounded-full uppercase tracking-widest mb-6 shadow-sm">
                    <i class="fa-solid fa-fire text-amber-600"></i> Powered by Multi-Tenant Firebase & PDO Engine
                </span>

                <h1 class="text-3xl sm:text-5xl lg:text-6xl font-black uppercase tracking-tight text-slate-900 max-w-4xl mx-auto leading-tight">
                    Next-Gen Real-Time <span class="text-transparent bg-clip-text bg-gradient-to-r from-gold-600 via-amber-600 to-yellow-600">Sports Auction</span> Platform
                </h1>

                <p class="text-xs sm:text-sm text-slate-600 max-w-2xl mx-auto mt-4 leading-relaxed font-medium">
                    Host live cricket & sports player bidding events with sub-second concurrent locking, automated team purse tracking, Google SSO onboarding, and multi-tenant tournament isolation.
                </p>

                <!-- Hero Action CTAs -->
                <div class="flex flex-wrap items-center justify-center gap-4 mt-8">
                    <a href="auctions.php" class="bg-slate-900 hover:bg-slate-800 text-white font-black text-xs uppercase tracking-widest px-6 py-3.5 rounded-xl transition flex items-center gap-2 shadow-sm">
                        <i class="fa-solid fa-gavel text-amber-400"></i> Today's & Upcoming Auctions
                    </a>
                    <a href="login.php" class="bg-gradient-to-r from-gold-500 via-amber-500 to-yellow-500 hover:from-gold-400 hover:to-amber-400 text-zinc-950 font-black text-xs uppercase tracking-widest px-6 py-3.5 rounded-xl shadow-xl shadow-gold-500/20 transition flex items-center gap-2 active:scale-95">
                        <i class="fa-solid fa-trophy text-sm"></i> Host an Auction Now
                    </a>
                </div>

                <!-- Aggregated Live Metric Stats -->
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mt-12 pt-8 border-t border-slate-200">
                    <div>
                        <div class="text-2xl sm:text-3xl font-black text-slate-900"><?php echo $totalTournaments; ?></div>
                        <div class="text-[10px] text-slate-500 uppercase font-bold tracking-wider mt-1">Tournaments Hosted</div>
                    </div>
                    <div>
                        <div class="text-2xl sm:text-3xl font-black text-gold-600"><?php echo $totalTeams; ?></div>
                        <div class="text-[10px] text-slate-500 uppercase font-bold tracking-wider mt-1">Franchise Teams</div>
                    </div>
                    <div>
                        <div class="text-2xl sm:text-3xl font-black text-emerald-600"><?php echo $totalPlayers; ?></div>
                        <div class="text-[10px] text-slate-500 uppercase font-bold tracking-wider mt-1">Verified Players</div>
                    </div>
                    <div>
                        <div class="text-2xl sm:text-3xl font-black text-cyan-600"><?php echo $totalBids; ?></div>
                        <div class="text-[10px] text-slate-500 uppercase font-bold tracking-wider mt-1">Live Bids Submitted</div>
                    </div>
                </div>
            </div>
        </section>


        <!-- 2. TODAY'S AUCTIONS (LIVE NOW) BLOCK -->
        <section id="live-auctions" class="px-4 max-w-7xl mx-auto">
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h2 class="text-xl sm:text-2xl font-black uppercase text-slate-900 tracking-tight flex items-center gap-2">
                        <span class="w-3 h-3 rounded-full bg-red-600 animate-ping"></span> Today's Live Auctions
                    </h2>
                    <p class="text-xs text-slate-500 mt-0.5">Leagues currently on the block with live real-time bidding active.</p>
                </div>
                <span class="bg-red-50 border border-red-200 text-red-600 text-[10px] font-extrabold px-3 py-1 rounded-full uppercase tracking-wider">
                    <?php echo count($liveAuctions); ?> Live Now
                </span>
            </div>

            <?php if (!empty($liveAuctions)): ?>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <?php foreach ($liveAuctions as $auction): ?>
                        <div class="bento-card bento-card-live p-6 flex flex-col justify-between relative overflow-hidden bg-white">
                            <div>
                                <div class="flex items-center justify-between mb-3">
                                    <span class="bg-red-600 text-white text-[9px] font-black px-2 py-0.5 rounded tracking-widest uppercase animate-pulse flex items-center gap-1">
                                        <i class="fa-solid fa-broadcast-tower"></i> Live Bidding
                                    </span>
                                    <span class="text-[10px] font-mono text-gold-600 font-bold">Code: <?php echo htmlspecialchars($auction['code']); ?></span>
                                </div>

                                <h3 class="text-lg font-black text-slate-900 uppercase tracking-tight mb-2">
                                    <?php echo htmlspecialchars($auction['name']); ?>
                                </h3>

                                <div class="space-y-2 my-4 text-xs text-slate-600">
                                    <div class="flex justify-between py-1 border-b border-slate-100">
                                        <span>Franchises Participating:</span>
                                        <strong class="text-slate-900"><?php echo $auction['team_count']; ?> Teams</strong>
                                    </div>
                                    <div class="flex justify-between py-1 border-b border-slate-100">
                                        <span>Verified Player Pool:</span>
                                        <strong class="text-emerald-600"><?php echo $auction['player_count']; ?> Players</strong>
                                    </div>
                                    <div class="flex justify-between py-1 border-b border-slate-100">
                                        <span>Default Purse Limit:</span>
                                        <strong class="text-gold-600">₹<?php echo number_format($auction['total_purse_default']); ?></strong>
                                    </div>
                                </div>
                            </div>

                            <div class="pt-4 border-t border-slate-100 flex gap-2">
                                <a href="index.php?t=<?php echo htmlspecialchars($auction['code']); ?>" class="w-full bg-gradient-to-r from-red-600 to-amber-600 hover:from-red-500 hover:to-amber-500 text-white font-extrabold py-2.5 rounded-xl text-xs uppercase tracking-wider transition text-center shadow-md shadow-red-600/20">
                                    Watch Live Arena <i class="fa-solid fa-arrow-right ml-1"></i>
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="bento-card p-8 text-center border-dashed border-slate-300 bg-white">
                    <div class="w-12 h-12 rounded-full bg-slate-100 border border-slate-200 flex items-center justify-center mx-auto mb-3 text-gold-600">
                        <i class="fa-solid fa-hourglass-start text-xl"></i>
                    </div>
                    <h3 class="text-sm font-extrabold uppercase text-slate-900 tracking-wider">No Live Auctions Right Now</h3>
                    <p class="text-xs text-slate-500 mt-1 max-w-md mx-auto">Check out upcoming scheduled tournaments below or log in to host your league's bidding event today!</p>
                </div>
            <?php endif; ?>
        </section>


        <!-- 3. UPCOMING AUCTIONS BLOCK -->
        <section id="upcoming-auctions" class="px-4 max-w-7xl mx-auto">
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h2 class="text-xl sm:text-2xl font-black uppercase text-slate-900 tracking-tight flex items-center gap-2">
                        <i class="fa-solid fa-calendar-days text-gold-600"></i> Upcoming Auctions & Player Registrations
                    </h2>
                    <p class="text-xs text-slate-500 mt-0.5">Leagues preparing for bidding. Register early to enter the draft pool.</p>
                </div>
            </div>

            <?php if (!empty($upcomingAuctions)): ?>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <?php foreach ($upcomingAuctions as $auction): ?>
                        <div class="bento-card p-6 flex flex-col justify-between bg-white">
                            <div>
                                <div class="flex items-center justify-between mb-3">
                                    <span class="bg-emerald-50 text-emerald-700 border border-emerald-200 text-[9px] font-extrabold px-2 py-0.5 rounded tracking-widest uppercase">
                                        Registration Open
                                    </span>
                                    <span class="text-[10px] font-mono text-slate-500">Code: <?php echo htmlspecialchars($auction['code']); ?></span>
                                </div>

                                <h3 class="text-lg font-black text-slate-900 uppercase tracking-tight mb-2">
                                    <?php echo htmlspecialchars($auction['name']); ?>
                                </h3>

                                <div class="space-y-2 my-4 text-xs text-slate-600">
                                    <div class="flex justify-between py-1 border-b border-slate-100">
                                        <span>Purse Per Team:</span>
                                        <strong class="text-gold-600">₹<?php echo number_format($auction['total_purse_default']); ?></strong>
                                    </div>
                                    <div class="flex justify-between py-1 border-b border-slate-100">
                                        <span>Max Squad Limit:</span>
                                        <strong class="text-slate-900"><?php echo $auction['max_squad_size_default']; ?> Players</strong>
                                    </div>
                                    <div class="flex justify-between py-1 border-b border-slate-100">
                                        <span>Registered Players:</span>
                                        <strong class="text-cyan-600"><?php echo $auction['player_count']; ?> Registered</strong>
                                    </div>
                                </div>
                            </div>

                            <div class="pt-4 border-t border-slate-100 flex gap-2">
                                <a href="register.php?t=<?php echo htmlspecialchars($auction['code']); ?>" class="w-1/2 bg-gradient-to-r from-gold-500 to-amber-500 hover:from-gold-400 hover:to-amber-400 text-zinc-950 font-extrabold py-2.5 rounded-xl text-xs uppercase tracking-wider transition text-center shadow-sm">
                                    Register <i class="fa-solid fa-user-plus ml-1"></i>
                                </a>
                                <a href="index.php?t=<?php echo htmlspecialchars($auction['code']); ?>" class="w-1/2 bg-slate-100 hover:bg-slate-200 border border-slate-200 text-slate-800 font-bold py-2.5 rounded-xl text-xs uppercase tracking-wider transition text-center">
                                    Arena View
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="bento-card p-8 text-center border-dashed border-slate-300 bg-white">
                    <p class="text-xs text-slate-500">No upcoming tournaments scheduled yet.</p>
                </div>
            <?php endif; ?>
        </section>


        <!-- 4. COMPLETED AUCTIONS BLOCK -->
        <?php if (!empty($completedAuctions)): ?>
            <section id="completed-auctions" class="px-4 max-w-7xl mx-auto">
                <div class="flex items-center justify-between mb-6">
                    <div>
                        <h2 class="text-xl sm:text-2xl font-black uppercase text-slate-900 tracking-tight flex items-center gap-2">
                            <i class="fa-solid fa-square-check text-cyan-600"></i> Completed Auctions
                        </h2>
                        <p class="text-xs text-slate-500 mt-0.5">Finalized leagues with completed draft rosters.</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <?php foreach ($completedAuctions as $auction): ?>
                        <div class="bento-card p-6 flex flex-col justify-between bg-white">
                            <div>
                                <div class="flex items-center justify-between mb-3">
                                    <span class="bg-cyan-50 text-cyan-700 border border-cyan-200 text-[9px] font-extrabold px-2 py-0.5 rounded tracking-widest uppercase">
                                        Finalized
                                    </span>
                                    <span class="text-[10px] font-mono text-slate-500">Code: <?php echo htmlspecialchars($auction['code']); ?></span>
                                </div>

                                <h3 class="text-lg font-black text-slate-900 uppercase tracking-tight mb-2">
                                    <?php echo htmlspecialchars($auction['name']); ?>
                                </h3>

                                <div class="space-y-2 my-4 text-xs text-slate-600">
                                    <div class="flex justify-between py-1 border-b border-slate-100">
                                        <span>Total Sold Players:</span>
                                        <strong class="text-cyan-600"><?php echo $auction['sold_player_count']; ?> Players</strong>
                                    </div>
                                    <div class="flex justify-between py-1 border-b border-slate-100">
                                        <span>Teams Drafted:</span>
                                        <strong class="text-slate-900"><?php echo $auction['team_count']; ?> Franchises</strong>
                                    </div>
                                </div>
                            </div>

                            <div class="pt-4 border-t border-slate-100">
                                <a href="index.php?t=<?php echo htmlspecialchars($auction['code']); ?>" class="w-full bg-slate-100 hover:bg-slate-200 border border-slate-200 text-slate-800 font-bold py-2.5 rounded-xl text-xs uppercase tracking-wider transition text-center block">
                                    View Final Roster <i class="fa-solid fa-clipboard-list ml-1"></i>
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php endif; ?>


        <!-- 5. OUR FEATURES SECTION (BENTO GRID) -->
        <section id="features" class="px-4 max-w-7xl mx-auto">
            <div class="text-center mb-10">
                <span class="text-gold-600 text-xs font-extrabold uppercase tracking-widest">Enterprise Architecture</span>
                <h2 class="text-2xl sm:text-4xl font-black uppercase text-slate-900 tracking-tight mt-1">
                    Built for High-Stakes Live Auctions
                </h2>
                <p class="text-xs text-slate-500 max-w-xl mx-auto mt-2">Engineered with multi-tenant database isolation, real-time concurrency locks, and instant Firebase SSO.</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <!-- Feature 1 -->
                <div class="bento-card p-6 bg-white">
                    <div class="w-12 h-12 rounded-2xl bg-gold-50 border border-gold-200 flex items-center justify-center text-gold-600 text-xl mb-4">
                        <i class="fa-solid fa-bolt"></i>
                    </div>
                    <h3 class="text-base font-black uppercase text-slate-900 tracking-tight mb-2">Sub-Second Concurrency Locking</h3>
                    <p class="text-xs text-slate-600 leading-relaxed">PDO transactions with database <code class="bg-slate-100 px-1 py-0.5 rounded text-gold-700 font-semibold">FOR UPDATE</code> locks ensure concurrent bids never cause budget overruns or race conditions.</p>
                </div>

                <!-- Feature 2 -->
                <div class="bento-card p-6 bg-white">
                    <div class="w-12 h-12 rounded-2xl bg-cyan-50 border border-cyan-200 flex items-center justify-center text-cyan-600 text-xl mb-4">
                        <i class="fa-solid fa-shield-halved"></i>
                    </div>
                    <h3 class="text-base font-black uppercase text-slate-900 tracking-tight mb-2">Multi-Tenant League Isolation</h3>
                    <p class="text-xs text-slate-600 leading-relaxed">Every organizer gets isolated leagues with unique tournament codes, shareable player registration links, and dedicated team manager accounts.</p>
                </div>

                <!-- Feature 3 -->
                <div class="bento-card p-6 bg-white">
                    <div class="w-12 h-12 rounded-2xl bg-amber-50 border border-amber-200 flex items-center justify-center text-amber-600 text-xl mb-4">
                        <i class="fa-solid fa-fire"></i>
                    </div>
                    <h3 class="text-base font-black uppercase text-slate-900 tracking-tight mb-2">Google Firebase Auth SSO</h3>
                    <p class="text-xs text-slate-600 leading-relaxed">Seamless 1-click Google OAuth 2.0 authentication for league organizers via Firebase Web SDK v10 with instant session provisioning.</p>
                </div>

                <!-- Feature 4 -->
                <div class="bento-card p-6 bg-white">
                    <div class="w-12 h-12 rounded-2xl bg-emerald-50 border border-emerald-200 flex items-center justify-center text-emerald-600 text-xl mb-4">
                        <i class="fa-solid fa-rotate-left"></i>
                    </div>
                    <h3 class="text-base font-black uppercase text-slate-900 tracking-tight mb-2">Deterministic Undo / Redo Engine</h3>
                    <p class="text-xs text-slate-600 leading-relaxed">Every auction step captures a full database snapshot. Revert accidental bids or player sales instantly without corrupting purse balances.</p>
                </div>

                <!-- Feature 5 -->
                <div class="bento-card p-6 bg-white">
                    <div class="w-12 h-12 rounded-2xl bg-purple-50 border border-purple-200 flex items-center justify-center text-purple-600 text-xl mb-4">
                        <i class="fa-solid fa-id-card"></i>
                    </div>
                    <h3 class="text-base font-black uppercase text-slate-900 tracking-tight mb-2">UTR Receipt & Photo Cropper</h3>
                    <p class="text-xs text-slate-600 leading-relaxed">Players register with custom image cropping and UTR payment references. Organizers approve profiles before they enter the bidding block.</p>
                </div>

                <!-- Feature 6 -->
                <div class="bento-card p-6 bg-white">
                    <div class="w-12 h-12 rounded-2xl bg-rose-50 border border-rose-200 flex items-center justify-center text-rose-600 text-xl mb-4">
                        <i class="fa-solid fa-desktop"></i>
                    </div>
                    <h3 class="text-base font-black uppercase text-slate-900 tracking-tight mb-2">4K Projector & Mobile Display</h3>
                    <p class="text-xs text-slate-600 leading-relaxed">Responsive glassmorphism UI designed for live auditorium screen projectors, tablet consoles, and mobile spectator dashboards.</p>
                </div>
            </div>
        </section>


        <!-- 6. HELP & FREQUENTLY ASKED QUESTIONS (FAQ) -->
        <section id="help" class="px-4 max-w-4xl mx-auto">
            <div class="text-center mb-8">
                <span class="text-gold-600 text-xs font-extrabold uppercase tracking-widest">Support & Guide</span>
                <h2 class="text-2xl sm:text-3xl font-black uppercase text-slate-900 tracking-tight mt-1">
                    Help & Frequently Asked Questions
                </h2>
            </div>

            <div class="space-y-4">
                <!-- FAQ 1 -->
                <div class="bento-card p-5 bg-white">
                    <button onclick="toggleFaq('faq-1')" class="w-full flex items-center justify-between text-left font-bold text-sm text-slate-900 outline-none">
                        <span>How do I host a new auction tournament as an Organizer?</span>
                        <i id="faq-1-icon" class="fa-solid fa-chevron-down text-gold-600 text-xs transition-transform duration-200"></i>
                    </button>
                    <div id="faq-1" class="accordion-content text-xs text-slate-600 mt-3 leading-relaxed">
                        Click <strong>"Host Your Auction"</strong> in the top menu to sign in using Google Firebase SSO or your credentials. Once in your Organizer SaaS Hub, click <strong>"New Tournament"</strong>, set your league parameters (default purse and max squad size), and generate your shareable player registration link!
                    </div>
                </div>

                <!-- FAQ 2 -->
                <div class="bento-card p-5 bg-white">
                    <button onclick="toggleFaq('faq-2')" class="w-full flex items-center justify-between text-left font-bold text-sm text-slate-900 outline-none">
                        <span>How do Team Managers log in and place bids during live auctions?</span>
                        <i id="faq-2-icon" class="fa-solid fa-chevron-down text-gold-600 text-xs transition-transform duration-200"></i>
                    </button>
                    <div id="faq-2" class="accordion-content text-xs text-slate-600 mt-3 leading-relaxed">
                        League Organizers create team accounts for franchise managers inside the Team & Roster Manager panel. Team Managers log in via <a href="login.php" class="text-gold-600 underline">login.php</a> using their assigned credentials to access their live bidding console.
                    </div>
                </div>

                <!-- FAQ 3 -->
                <div class="bento-card p-5 bg-white">
                    <button onclick="toggleFaq('faq-3')" class="w-full flex items-center justify-between text-left font-bold text-sm text-slate-900 outline-none">
                        <span>How do players register for an upcoming auction?</span>
                        <i id="faq-3-icon" class="fa-solid fa-chevron-down text-gold-600 text-xs transition-transform duration-200"></i>
                    </button>
                    <div id="faq-3" class="accordion-content text-xs text-slate-600 mt-3 leading-relaxed">
                        Players visit the unique registration link provided by their league organizer (e.g. <code>register.php?t=auctionwala-2026</code>), fill in their playing role, crop their photo, and submit their UTR receipt. Once approved by the organizer, they enter the live bidding pool.
                    </div>
                </div>

                <!-- FAQ 4 -->
                <div class="bento-card p-5 bg-white">
                    <button onclick="toggleFaq('faq-4')" class="w-full flex items-center justify-between text-left font-bold text-sm text-slate-900 outline-none">
                        <span>What happens if an accidental bid or wrong sale is executed?</span>
                        <i id="faq-4-icon" class="fa-solid fa-chevron-down text-gold-600 text-xs transition-transform duration-200"></i>
                    </button>
                    <div id="faq-4" class="accordion-content text-xs text-slate-600 mt-3 leading-relaxed">
                        The Live Auctioneer Desk includes a 1-click <strong>Undo & Redo</strong> history engine. Clicking Undo restores the previous state snapshot, reversing team purse deductions and player statuses instantly.
                    </div>
                </div>
            </div>
        </section>

    </main>

    <!-- 7. FOOTER BLOCK WITH TOURNAMENT LOOKUP TOOL -->
    <footer class="w-full bg-white border-t border-slate-200 mt-16 pt-12 pb-8 px-4 sm:px-8">
        <div class="max-w-7xl mx-auto grid grid-cols-1 md:grid-cols-4 gap-8 mb-12">

            <!-- Brand Info -->
            <div class="md:col-span-2 space-y-4">
                <div class="flex items-center gap-3">
                    <img src="<?php echo $uploadPath; ?>auctionwala_logo.png" alt="AuctionWala Logo" class="h-8 object-contain mix-blend-multiply">
                    <span class="text-lg font-black uppercase text-slate-900 tracking-tight">AUCTION WALA SAAS</span>
                </div>
                <p class="text-xs text-slate-600 max-w-md leading-relaxed">
                    AuctionWala is an enterprise-grade real-time sports auction platform designed for cricket leagues, turf tournaments, and sports franchises worldwide.
                </p>

                <!-- Tournament Direct Code Lookup Tool -->
                <div class="pt-2 max-w-sm">
                    <label class="block text-[10px] font-extrabold uppercase text-gold-700 tracking-widest mb-1.5 flex items-center gap-1">
                        <i class="fa-solid fa-magnifying-glass"></i> Jump to Tournament by Code
                    </label>
                    <form onsubmit="handleDirectCodeLookup(event)" class="flex gap-2">
                        <input type="text" id="lookup-code" required placeholder="e.g. auctionwala-2026" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs text-slate-900 uppercase outline-none focus:border-gold-500 font-mono">
                        <button type="submit" class="bg-gold-500 hover:bg-gold-400 text-zinc-950 font-extrabold px-4 py-2 rounded-xl text-xs uppercase tracking-wider transition shadow-sm">
                            Go
                        </button>
                    </form>
                </div>
            </div>

            <!-- Quick Links -->
            <div>
                <h4 class="text-xs font-black uppercase text-slate-900 tracking-wider mb-4">Quick Navigation</h4>
                <ul class="space-y-2 text-xs text-slate-600">
                    <li><a href="#live-auctions" class="hover:text-gold-600 transition">Today's Live Auctions</a></li>
                    <li><a href="#upcoming-auctions" class="hover:text-gold-600 transition">Upcoming Auctions</a></li>
                    <li><a href="#completed-auctions" class="hover:text-gold-600 transition">Completed Auctions</a></li>
                    <li><a href="index.php" class="hover:text-gold-600 transition">Spectator Arena</a></li>
                </ul>
            </div>

            <!-- Organizer & Account Links -->
            <div>
                <h4 class="text-xs font-black uppercase text-slate-900 tracking-wider mb-4">Organizer & Auth</h4>
                <ul class="space-y-2 text-xs text-slate-600">
                    <li><a href="login.php" class="hover:text-gold-600 transition">Host Your Auction (Login)</a></li>
                    <li><a href="../organizer/index.php" class="hover:text-gold-600 transition">SaaS Organizer Hub</a></li>
                    <li><a href="../admin/auction.php" class="hover:text-gold-600 transition">Auctioneer Control Desk</a></li>
                    <li><a href="#help" class="hover:text-gold-600 transition">Help & Documentation</a></li>
                </ul>
            </div>
        </div>

        <div class="max-w-7xl mx-auto pt-6 border-t border-slate-100 flex flex-col sm:flex-row items-center justify-between text-[11px] text-slate-500 gap-4">
            <div>
                &copy; <?php echo date('Y'); ?> AuctionWala Sports Auction Engine. All rights reserved.
            </div>
            <div class="flex items-center gap-4">
                <a href="landing.php" class="hover:text-slate-900 font-medium">Privacy Policy</a>
                <span>•</span>
                <a href="landing.php" class="hover:text-slate-900 font-medium">Terms of Service</a>
                <span>•</span>
                <a href="login.php" class="hover:text-gold-600 font-medium">Firebase Auth</a>
            </div>
        </div>
    </footer>

    <!-- Interactive Scripts -->
    <script>
        function toggleFaq(id) {
            const content = document.getElementById(id);
            const icon = document.getElementById(id + '-icon');
            if (content.style.maxHeight) {
                content.style.maxHeight = null;
                icon.style.transform = 'rotate(0deg)';
            } else {
                content.style.maxHeight = content.scrollHeight + 'px';
                icon.style.transform = 'rotate(180deg)';
            }
        }

        function handleDirectCodeLookup(e) {
            e.preventDefault();
            const code = document.getElementById('lookup-code').value.trim();
            if (code) {
                window.location.href = 'index.php?t=' + encodeURIComponent(code);
            }
        }
    </script>
</body>
</html>
