<?php
// public/index.php
session_start();
require_once '../config/db.php';

$tournamentId = get_active_tournament_id($pdo);
if ($tournamentId > 0) {
    $_SESSION['tournament_id'] = $tournamentId;
}
$tournament = null;
$registrationEnabled = true;
$tCode = 'auctionwala-2026';
$tName = 'AuctionWala Live Arena';

try {
    if ($pdo) {
        $stmt = $pdo->prepare("SELECT * FROM tournaments WHERE id = ?");
        $stmt->execute([$tournamentId]);
        $tournament = $stmt->fetch();

        $stmt = $pdo->prepare("SELECT registration_enabled FROM auction_state WHERE tournament_id = ?");
        $stmt->execute([$tournamentId]);
        $regStatus = $stmt->fetch();

        $registrationEnabled = $tournament ? (bool)$tournament['registration_enabled'] : ($regStatus ? (bool)$regStatus['registration_enabled'] : true);
        if ($tournament) {
            $tCode = $tournament['code'];
            $tName = $tournament['name'];
        }
    }
} catch (Exception $e) {}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($tName); ?> — Live Auction Arena</title>
    <?php require_once 'components/ui_head.php'; ?>
    <script>
        window.activeTournamentCode = "<?php echo htmlspecialchars($tCode); ?>";
        window.activeTournamentId = <?php echo (int)$tournamentId; ?>;
    </script>
</head>
<body class="text-gray-200 min-h-screen flex flex-col justify-between selection:bg-gold-500 selection:text-black">
    
    <!-- Header / Brand Bar -->
    <header class="w-full glass-panel border-b border-gold-500/10 px-3 py-2.5 sm:px-6 sm:py-3 flex items-center justify-between sticky top-0 z-40">
        <div class="flex items-center gap-2.5 sm:gap-3">
            <a href="landing.php">
                <img src="uploads/auctionwala_logo.png" alt="AuctionWala Logo" class="h-7 sm:h-9 object-contain mix-blend-multiply">
            </a>
            <div>
                <h1 class="text-base sm:text-lg font-black uppercase tracking-tight text-transparent bg-clip-text bg-gradient-to-r from-gold-300 via-gold-400 to-amber-600 leading-none">
                    <?php echo htmlspecialchars($tName); ?>
                </h1>
                <p class="text-[8px] sm:text-[9px] text-gray-500 uppercase tracking-widest font-bold mt-0.5">Live Bidding Arena</p>
            </div>
        </div>

        <!-- Live Auction Status Indicator -->
        <div class="flex items-center gap-2 sm:gap-4">
            <button id="sound-toggle-btn" onclick="toggleMute()" class="flex items-center justify-center bg-black/40 border border-gold-500/10 hover:border-gold-500/35 w-8 h-8 rounded-full text-xs transition duration-200" title="Toggle Sound Effects">
                <i id="sound-icon" class="fa-solid fa-volume-high text-xs sm:text-sm text-gold-400"></i>
            </button>

            <div class="hidden sm:flex items-center gap-2 bg-black/40 border border-white/5 rounded-full px-3 py-1 text-xs">
                <span class="w-2.5 h-2.5 rounded-full bg-red-500 animate-pulse live-dot" id="status-light"></span>
                <span class="text-gray-400 font-semibold tracking-wider uppercase text-[10px]" id="status-text">Live Arena</span>
            </div>

            <!-- Login Quick Redirects -->
            <div class="flex gap-1.5 sm:gap-2 text-[9px] sm:text-xs">
                <!-- PWA Install Button -->
                <button id="pwa-install-btn" class="hidden text-[9px] sm:text-[10px] font-bold uppercase tracking-wider bg-gold-950/40 border border-gold-500/20 text-gold-400 hover:bg-gold-500/10 hover:border-gold-500/40 px-2.5 py-1.5 sm:px-3.5 sm:py-2 rounded-lg transition flex items-center justify-center gap-1">
                    <i class="fa-solid fa-cloud-arrow-down text-gold-400"></i> App
                </button>
                <?php if ($registrationEnabled): ?>
                    <a href="register.php" class="text-[9px] sm:text-[10px] font-bold uppercase tracking-wider bg-gold-950/40 border border-gold-500/20 text-gold-400 hover:bg-gold-500/10 hover:border-gold-500/40 px-2.5 py-1.5 sm:px-3.5 sm:py-2 rounded-lg transition flex items-center justify-center">
                        Register
                    </a>
                <?php else: ?>
                    <span class="text-[9px] sm:text-[10px] font-bold uppercase tracking-wider bg-red-950/20 border border-red-500/20 text-red-400 px-2.5 py-1.5 sm:px-3.5 sm:py-2 rounded-lg cursor-not-allowed flex items-center justify-center">
                        Closed
                    </span>
                <?php endif; ?>
                <a href="login.php" class="text-[9px] sm:text-[10px] font-bold uppercase tracking-wider bg-gold-500 hover:bg-gold-400 text-black px-2.5 py-1.5 sm:px-3.5 sm:py-2 rounded-lg transition font-extrabold shadow-md shadow-gold-500/5 flex items-center justify-center">
                    Portals
                </a>
            </div>
        </div>
    </header>

    <!-- Main Live Dashboard Arena -->
    <main class="flex-grow p-4 md:p-6 max-w-7xl w-full mx-auto grid grid-cols-12 gap-6 relative">
        <div class="absolute inset-0 bg-[radial-gradient(circle_at_center,rgba(212,163,12,0.03)_0%,transparent_75%)] pointer-events-none"></div>

        <!-- LEFT SIDE: Bento-Grid Auction Section (8 Cols) -->
        <div class="col-span-12 lg:col-span-8 grid grid-cols-12 gap-6" id="auction-grid">
            
            <!-- Standard Standby Box (Will show when current_player is NULL) -->
            <div id="standby-box" class="col-span-12 glass-panel rounded-2xl p-10 text-center flex flex-col items-center justify-center border border-slate-200 min-h-[450px] bg-white">
                <i class="fa-solid fa-gavel text-6xl text-gold-600 animate-bounce mb-4 block"></i>
                <h2 class="text-2xl font-extrabold text-slate-900">AuctionWala Live Bidding Arena</h2>
                <p class="text-slate-600 max-w-md mt-2 text-sm">
                    Welcome to the AuctionWala Live Bidding Arena. Bidding will commence as the Auctioneer brings the next player to the block.
                </p>
                <div class="mt-6 flex gap-4">
                    <span class="text-xs text-slate-500 uppercase tracking-widest bg-slate-100 border border-slate-200 px-3 py-1.5 rounded-lg">
                        Real-Time Concurrency Active
                    </span>
                    <span class="text-xs text-slate-500 uppercase tracking-widest bg-slate-100 border border-slate-200 px-3 py-1.5 rounded-lg">
                        Season 2026
                    </span>
                </div>
            </div>

            <!-- Completed Stats Box -->
            <div id="completed-stats-box" class="hidden col-span-12 glass-panel rounded-2xl p-6 border border-white/60 flex flex-col gap-6 relative overflow-hidden shadow-2xl">
                <!-- Header -->
                <div class="flex items-center justify-between pb-4 border-b border-slate-900/10 relative z-10">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-gold-500/10 border border-gold-500/30 flex items-center justify-center text-gold-700">
                            <i class="fa-solid fa-trophy text-lg"></i>
                        </div>
                        <div>
                            <h2 class="text-base font-black text-slate-900 uppercase tracking-tight">Auction Completed</h2>
                            <p class="text-[9px] text-slate-600 uppercase tracking-wider font-bold">Final Stats & Leaderboards</p>
                        </div>
                    </div>
                    <span class="px-2.5 py-1 rounded-full bg-gold-500/15 border border-gold-500/30 text-gold-800 text-[9px] uppercase tracking-wider font-extrabold flex items-center gap-1.5 shadow-sm">
                        <i class="fa-solid fa-circle text-[6px] text-gold-600 animate-pulse"></i> Final Summary
                    </span>
                </div>

                <!-- Stats Cards Grid -->
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 relative z-10">
                    <div class="glass-card-subtle rounded-xl p-4 text-center shadow-sm hover:border-gold-500/40 transition duration-300">
                        <span class="text-[9px] text-slate-600 uppercase tracking-wider block font-extrabold">Total Purse Spent</span>
                        <span class="text-xl font-black text-gold-700 font-mono mt-1 block" id="stats-total-spent">₹0</span>
                    </div>
                    <div class="glass-card-subtle rounded-xl p-4 text-center shadow-sm hover:border-gold-500/40 transition duration-300">
                        <span class="text-[9px] text-slate-600 uppercase tracking-wider block font-extrabold">Avg. Player Bid</span>
                        <span class="text-xl font-black text-slate-900 font-mono mt-1 block" id="stats-avg-price">₹0</span>
                    </div>
                    <div class="glass-card-subtle rounded-xl p-4 text-center shadow-sm hover:border-gold-500/40 transition duration-300">
                        <span class="text-[9px] text-slate-600 uppercase tracking-wider block font-extrabold">Players Sold</span>
                        <span class="text-xl font-black text-emerald-700 font-mono mt-1 block" id="stats-sold-count">0</span>
                    </div>
                    <div class="glass-card-subtle rounded-xl p-4 text-center shadow-sm hover:border-gold-500/40 transition duration-300">
                        <span class="text-[9px] text-slate-600 uppercase tracking-wider block font-extrabold">Unsold Players</span>
                        <span class="text-xl font-black text-red-600 font-mono mt-1 block" id="stats-unsold-count">0</span>
                    </div>
                </div>

                <!-- Double Columns -->
                <div class="grid grid-cols-1 md:grid-cols-12 gap-6 relative z-10 mt-2">
                    <div class="md:col-span-7 space-y-3.5">
                        <h3 class="text-xs font-black text-slate-900 uppercase tracking-wider flex items-center gap-1.5 pb-2 border-b border-slate-900/10">
                            <i class="fa-solid fa-crown text-amber-600"></i> Top Valued Players
                        </h3>
                        <div class="space-y-2.5 max-h-[360px] overflow-y-auto pr-1" id="stats-top-players">
                            <!-- Populated dynamically -->
                        </div>
                    </div>
                    <div class="md:col-span-5 space-y-3.5">
                        <h3 class="text-xs font-black text-slate-900 uppercase tracking-wider flex items-center gap-1.5 pb-2 border-b border-slate-900/10">
                            <i class="fa-solid fa-chart-line text-amber-600"></i> Franchise Pursetracker
                        </h3>
                        <div class="space-y-2.5 max-h-[360px] overflow-y-auto pr-1" id="stats-teams-spent">
                            <!-- Populated dynamically -->
                        </div>
                    </div>
                </div>
            </div>

            <!-- Active Player Profile Bento (5 Cols) -->
            <div id="player-card" class="hidden col-span-12 md:col-span-5 ipl-card-frame ipl-card-live p-5 flex flex-col justify-between relative group shadow-2xl">
                <!-- Diagonal Watermark Background & Stage Spotlight -->
                <div class="ipl-watermark-text">LIVE BID</div>
                <div class="ipl-card-stage-glow"></div>

                <!-- Top Header: Country/Location & Role -->
                <div class="flex items-center justify-between relative z-10 mb-3">
                    <div class="flex items-center gap-1.5 bg-black/60 border border-white/20 px-3 py-1 rounded-lg backdrop-blur-md">
                        <span class="text-xs">🇮🇳</span>
                        <span class="text-[9px] font-black text-white uppercase tracking-wider font-mono" id="player-place">
                            Wayanad
                        </span>
                    </div>
                    <span class="bg-amber-400 border border-amber-300 text-slate-950 text-[9px] font-black px-3 py-1 rounded-lg uppercase tracking-wider shadow-md" id="player-role">
                        ALL-ROUNDER
                    </span>
                </div>

                <!-- Stacked Player Name -->
                <div class="relative z-10 mb-2 text-center">
                    <h2 class="text-2xl font-black text-white uppercase tracking-tight leading-none drop-shadow-md" id="player-name">---</h2>
                </div>

                <!-- Center Stage: Cutout Player Image -->
                <div class="relative z-10 my-3 flex justify-center">
                    <div class="w-36 h-40 rounded-2xl overflow-hidden border-2 border-white/60 bg-slate-900/60 shadow-2xl relative cursor-zoom-in group-hover:scale-105 transition duration-300" onclick="openImageLightbox(document.getElementById('player-image').src, document.getElementById('player-name').innerText);">
                        <img src="uploads/player_placeholder.jpg" id="player-image" alt="Player Image" class="w-full h-full object-cover" onerror="this.onerror=null; this.src='uploads/player_placeholder.jpg';">
                        <div class="absolute top-2 right-2 bg-amber-500 text-slate-950 font-black px-2 py-0.5 rounded text-[8px] uppercase tracking-wider shadow-md" id="player-status-tag">
                            Bidding
                        </div>
                    </div>
                </div>

                <!-- Bottom Stats Bar: Base Price -->
                <div class="relative z-10 ipl-price-container px-4 py-3 flex justify-between items-center text-xs">
                    <span class="text-slate-200 font-extrabold uppercase tracking-wider text-[10px]">Base Price</span>
                    <span class="text-white font-black text-lg font-mono drop-shadow-md" id="player-base-price">₹100</span>
                </div>
            </div>

            <!-- Active Bid Center Console Bento (7 Cols) -->
            <div id="bid-card" class="hidden col-span-12 md:col-span-7 flex flex-col gap-6">
                <!-- Current High Bid Box -->
                <div class="glass-panel rounded-2xl p-6 border border-gold-500/15 flex flex-col justify-between relative overflow-hidden min-h-[200px] flex-grow">
                    <div class="absolute inset-0 bg-[radial-gradient(circle_at_right,rgba(218,165,32,0.06)_0%,transparent_50%)] pointer-events-none"></div>
                    <div>
                        <span class="text-[9px] uppercase tracking-widest font-bold text-gray-500">Active High Bid</span>
                        <div class="flex items-baseline mt-1 gap-2">
                            <span class="text-5xl font-black text-transparent bg-clip-text bg-gradient-to-r from-gold-300 via-gold-400 to-amber-500 tracking-tight transition duration-200" id="current-bid">
                                ₹0
                            </span>
                        </div>
                    </div>
                    <div class="mt-4 border-t border-white/5 pt-4">
                        <span class="text-[9px] uppercase tracking-widest font-bold text-gray-500">Leading Franchise</span>
                        <div class="flex items-center gap-3 mt-1.5">
                            <div id="leading-team-logo-container" class="w-9 h-9 rounded bg-black/40 border border-gold-500/20 flex items-center justify-center overflow-hidden p-0.5">
                                <i class="fa-solid fa-crown text-xl text-gold-400" id="leading-team-crown"></i>
                            </div>
                            <div>
                                <h3 class="text-base font-extrabold text-white" id="leading-team">---</h3>
                                <p class="text-[9px] text-gold-500 uppercase tracking-widest font-bold">High Bidholder</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Live Bids History Timeline -->
                <div class="glass-panel rounded-2xl p-5 border border-gold-500/15 flex flex-col min-h-[160px]">
                    <h3 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-3 flex items-center gap-2 border-b border-white/5 pb-2">
                        <i class="fa-solid fa-chart-line text-xs text-gold-400"></i> Bid Flow History
                    </h3>
                    <div class="flex-grow overflow-y-auto pr-1 space-y-2.5 max-h-36" id="bid-history-list">
                        <!-- Polled Bids will append here -->
                    </div>
                </div>
            </div>

        </div>

        <!-- RIGHT SIDE: Franchise Standings Leaderboard (4 Cols) -->
        <aside class="col-span-12 lg:col-span-4 glass-panel rounded-2xl p-5 border border-white/60 flex flex-col shadow-2xl">
            <div class="border-b border-slate-900/10 pb-3 mb-4">
                <h3 class="text-base font-black text-slate-900 flex items-center gap-2 uppercase tracking-tight">
                    <i class="fa-solid fa-wallet text-base text-amber-600"></i> Franchise Purses
                </h3>
                <p class="text-[10px] text-slate-700 mt-1 uppercase tracking-widest font-extrabold">Live Budget & Squad Sizes</p>
            </div>

            <!-- Team Leaderboard Grid -->
            <div class="flex-grow space-y-3" id="teams-leaderboard">
                <!-- Polled leaderboard will render here -->
            </div>
        </aside>

        <!-- COMPLETED PLAYERS SECTION -->
        <section class="col-span-12 glass-panel rounded-2xl p-6 border border-white/60 mt-2 relative overflow-hidden shadow-2xl">
            <div class="absolute inset-0 bg-[radial-gradient(circle_at_bottom,rgba(212,163,12,0.02)_0%,transparent_70%)] pointer-events-none"></div>
            
            <div class="border-b border-slate-900/10 pb-4 mb-6 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <h3 class="text-base font-black text-slate-900 flex items-center gap-2 uppercase tracking-tight">
                        <i class="fa-solid fa-clipboard-list text-base text-amber-600"></i> Player Auctions Status
                    </h3>
                    <p class="text-[10px] text-slate-700 mt-0.5 font-bold uppercase tracking-wider">Real-time status of all verified player auctions</p>
                </div>
                <!-- Search and Filters Container -->
                <div class="flex flex-col sm:flex-row items-start sm:items-center gap-3.5 w-full sm:w-auto">
                    <!-- Filter Chips -->
                    <div class="flex items-center gap-1.5 overflow-x-auto pb-1 shrink-0 scrollbar-none" id="public-status-filter-container">
                        <button onclick="setStatusFilter('all')" class="status-chip px-3 py-1.5 rounded-lg border text-[10px] uppercase font-black tracking-wider transition bg-slate-900 border-slate-900 text-white shadow-sm" data-filter="all">ALL</button>
                        <button onclick="setStatusFilter('Sold')" class="status-chip px-3 py-1.5 rounded-lg border text-[10px] uppercase font-extrabold tracking-wider transition border-slate-300 bg-white/90 text-slate-800 hover:bg-slate-900 hover:text-white" data-filter="Sold">SOLD</button>
                        <button onclick="setStatusFilter('Unsold')" class="status-chip px-3 py-1.5 rounded-lg border text-[10px] uppercase font-extrabold tracking-wider transition border-slate-300 bg-white/90 text-slate-800 hover:bg-slate-900 hover:text-white" data-filter="Unsold">UNSOLD</button>
                        <button onclick="setStatusFilter('Available')" class="status-chip px-3 py-1.5 rounded-lg border text-[10px] uppercase font-extrabold tracking-wider transition border-slate-300 bg-white/90 text-slate-800 hover:bg-slate-900 hover:text-white" data-filter="Available">AVAILABLE</button>
                    </div>
                    <!-- Search Input -->
                    <div class="relative w-full sm:w-56">
                        <i class="fa-solid fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-slate-500 text-[10px]"></i>
                        <input type="text" id="player-search-input" oninput="renderCompletedPlayers()" placeholder="Search players, roles, places..."
                               class="w-full bg-white/90 border border-slate-300 rounded-xl pl-8 pr-3 py-1.5 text-[11px] text-slate-900 font-medium focus:outline-none focus:border-amber-500 focus:ring-1 focus:ring-amber-500 transition placeholder-slate-500 shadow-sm">
                    </div>
                </div>
            </div>

            <!-- Completed Players Grid (Adaptive cols) -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6" id="completed-players-grid">
                <!-- Dynamically populated completed cards -->
            </div>
            
            <!-- Standby Empty state inside completed table -->
            <div id="completed-empty-box" class="text-center text-[10px] text-gray-500 py-10 uppercase tracking-widest font-bold hidden">
                No finalized auctions yet. Bids are ongoing!
            </div>
        </section>

    </main>

    <!-- Footer Area -->
    <footer class="w-full glass-panel border-t border-gold-500/10 px-6 py-4 text-center text-xs text-gray-500 mt-6">
        <p>© 2026 Shamsu Memorial Cricket League (SMCL). Built for premium turf events.</p>
    </footer>

    <script>
        // Track the current bidding state parameters locally to detect changes
        let activePlayerId = null;
        let lastBidAmount = 0;
        let isMuted = false;
        let allCompletedPlayers = [];
        let activeStatusFilter = 'all';

        // Premium Sound Engine (Zero-latency Web Audio API Synth)
        const SMCLSoundEngine = {
            ctx: null,
            init() {
                if (!this.ctx) {
                    this.ctx = new (window.AudioContext || window.webkitAudioContext)();
                }
                if (this.ctx.state === 'suspended') {
                    this.ctx.resume();
                }
            },
            playBid() {
                if (isMuted) return;
                try {
                    this.init();
                    const now = this.ctx.currentTime;
                    
                    // Satisfaction gavel-click knock sound
                    const osc = this.ctx.createOscillator();
                    const gain = this.ctx.createGain();
                    osc.connect(gain);
                    gain.connect(this.ctx.destination);
                    
                    osc.type = 'triangle';
                    osc.frequency.setValueAtTime(480, now);
                    osc.frequency.exponentialRampToValueAtTime(120, now + 0.12);
                    
                    gain.gain.setValueAtTime(0.45, now);
                    gain.gain.exponentialRampToValueAtTime(0.001, now + 0.12);
                    
                    osc.start(now);
                    osc.stop(now + 0.12);
                } catch(e) {}
            },
            playSold() {
                if (isMuted) return;
                try {
                    this.init();
                    const now = this.ctx.currentTime;
                    
                    // Double Gavel Strike
                    for (let i = 0; i < 2; i++) {
                        const time = now + i * 0.14;
                        const osc = this.ctx.createOscillator();
                        const gain = this.ctx.createGain();
                        osc.connect(gain);
                        gain.connect(this.ctx.destination);
                        osc.type = 'triangle';
                        osc.frequency.setValueAtTime(580, time);
                        osc.frequency.exponentialRampToValueAtTime(90, time + 0.14);
                        gain.gain.setValueAtTime(0.55, time);
                        gain.gain.exponentialRampToValueAtTime(0.001, time + 0.14);
                        osc.start(time);
                        osc.stop(time + 0.14);
                    }
                    
                    // Premium Gold Major Celebration Arpeggio (C5 -> E5 -> G5 -> C6)
                    const notes = [523.25, 659.25, 783.99, 1046.50];
                    notes.forEach((freq, idx) => {
                        const time = now + 0.28 + idx * 0.08;
                        const osc = this.ctx.createOscillator();
                        const gain = this.ctx.createGain();
                        osc.connect(gain);
                        gain.connect(this.ctx.destination);
                        osc.type = 'sine';
                        osc.frequency.setValueAtTime(freq, time);
                        gain.gain.setValueAtTime(0.12, time);
                        gain.gain.exponentialRampToValueAtTime(0.001, time + 0.85);
                        osc.start(time);
                        osc.stop(time + 0.85);
                    });
                } catch(e) {}
            },
            playUnsold() {
                if (isMuted) return;
                try {
                    this.init();
                    const now = this.ctx.currentTime;
                    
                    // Sad downward slide tone
                    const osc = this.ctx.createOscillator();
                    const gain = this.ctx.createGain();
                    osc.connect(gain);
                    gain.connect(this.ctx.destination);
                    
                    osc.type = 'sawtooth';
                    osc.frequency.setValueAtTime(260, now);
                    osc.frequency.linearRampToValueAtTime(60, now + 0.75);
                    
                    gain.gain.setValueAtTime(0.25, now);
                    gain.gain.exponentialRampToValueAtTime(0.001, now + 0.75);
                    
                    osc.start(now);
                    osc.stop(now + 0.75);
                } catch(e) {}
            }
        };

        function toggleMute() {
            isMuted = !isMuted;
            const icon = document.getElementById('sound-icon');
            if (isMuted) {
                icon.className = "fa-solid fa-volume-xmark text-sm text-gray-500";
            } else {
                icon.className = "fa-solid fa-volume-high text-sm text-gold-400";
            }
            if (!isMuted) {
                SMCLSoundEngine.init();
            }
        }

        // Initialize audio engine on first click anywhere (bypasses browser autoplay limits)
        document.addEventListener('click', () => {
            SMCLSoundEngine.init();
        }, { once: true });

        // Fetch live state immediately on load and then every 1.5 seconds (1500ms)
        fetchState();
        setInterval(fetchState, 1500);

        async function fetchState() {
            try {
                const response = await fetch('../api/get_live_state.php');
                const data = await response.json();

                if (data.error) {
                    console.error(data.error);
                    return;
                }

                // 1. Update Status Header Bar
                const statusLight = document.getElementById('status-light');
                const statusText = document.getElementById('status-text');

                if (data.status === 'Bidding') {
                    statusLight.className = "w-2.5 h-2.5 rounded-full bg-red-500 animate-pulse live-dot";
                    statusText.innerText = "Bidding Active";
                    statusText.className = "text-red-400 font-extrabold tracking-wider uppercase text-[10px]";
                } else if (data.status === 'Paused') {
                    statusLight.className = "w-2.5 h-2.5 rounded-full bg-yellow-500 animate-pulse";
                    statusLight.style.boxShadow = "0 0 10px #eab308";
                    statusText.innerText = "Bidding Paused";
                    statusText.className = "text-yellow-400 font-extrabold tracking-wider uppercase text-[10px]";
                } else {
                    statusLight.className = "w-2.5 h-2.5 rounded-full bg-gray-500";
                    statusLight.style.boxShadow = "none";
                    statusText.innerText = "Arena Idle";
                    statusText.className = "text-gray-500 font-extrabold tracking-wider uppercase text-[10px]";
                }

                // 2. Control Layout Visibility based on Active Player
                const standbyBox = document.getElementById('standby-box');
                const playerCard = document.getElementById('player-card');
                const bidCard = document.getElementById('bid-card');
                const completedStatsBox = document.getElementById('completed-stats-box');

                const totalVerified = data.all_players ? data.all_players.length : 0;
                const hasAvailable = data.all_players ? data.all_players.some(p => p.auction_status === 'Available') : false;
                const isAuctionComplete = totalVerified > 0 && data.status === 'Idle' && !data.current_player_id && !hasAvailable;

                if (isAuctionComplete) {
                    standbyBox.classList.add('hidden');
                    playerCard.classList.add('hidden');
                    bidCard.classList.add('hidden');
                    completedStatsBox.classList.remove('hidden');
                    
                    renderAuctionStats(data);
                } else {
                    completedStatsBox.classList.add('hidden');

                    if (data.current_player && data.status !== 'Idle') {
                        standbyBox.classList.add('hidden');
                        playerCard.classList.remove('hidden');
                        bidCard.classList.remove('hidden');

                        const newPlayerId = parseInt(data.current_player.id);

                        // Sync Player details
                        document.getElementById('player-name').innerText = data.current_player.name;
                        document.getElementById('player-role').innerText = data.current_player.role.toUpperCase();
                        document.getElementById('player-place').innerText = data.current_player.place;
                        document.getElementById('player-base-price').innerText = "₹" + data.current_player.base_price;
                        document.getElementById('player-image').src = "uploads/" + data.current_player.profile_image;
                        
                        const tag = document.getElementById('player-status-tag');
                        tag.innerText = data.status === 'Paused' ? 'PAUSED' : 'BIDDING';
                        tag.className = data.status === 'Paused' 
                            ? 'absolute top-2 right-2 bg-yellow-500/80 px-2 py-0.5 rounded text-[8px] border border-yellow-400/30 uppercase tracking-wider text-black font-extrabold' 
                            : 'absolute top-2 right-2 bg-red-600/80 px-2 py-0.5 rounded text-[8px] border border-red-500/30 uppercase tracking-wider text-white';

                        // Sync Bidding Box details
                        const bidText = document.getElementById('current-bid');
                        bidText.innerText = "₹" + data.highest_bid;

                        // Micro-animation flash if bid changes & play Sound!
                        if (activePlayerId !== newPlayerId) {
                            activePlayerId = newPlayerId;
                            lastBidAmount = data.highest_bid;
                        } else {
                            if (lastBidAmount !== 0 && data.highest_bid > lastBidAmount) {
                                bidText.classList.add('scale-105', 'text-yellow-300');
                                setTimeout(() => {
                                    bidText.classList.remove('scale-105', 'text-yellow-300');
                                }, 400);
                                SMCLSoundEngine.playBid();
                            }
                            lastBidAmount = data.highest_bid;
                        }

                        const logoContainer = document.getElementById('leading-team-logo-container');
                        const leadingTeamEl = document.getElementById('leading-team');
                        if (data.leading_team_name) {
                            leadingTeamEl.innerText = data.leading_team_name;
                            leadingTeamEl.className = "text-base font-extrabold text-white cursor-pointer hover:text-gold-400 transition";
                            leadingTeamEl.onclick = () => openTeamDetailsModal(data.leading_team_id);
                            const logoSrc = data.leading_team_logo ? "uploads/" + data.leading_team_logo : "uploads/team_placeholder.jpg";
                            logoContainer.innerHTML = `<img src="${logoSrc}" class="w-full h-full object-contain">`;
                        } else {
                            leadingTeamEl.innerText = "No bids placed yet";
                            leadingTeamEl.className = "text-base font-extrabold text-white";
                            leadingTeamEl.onclick = null;
                            logoContainer.innerHTML = `<i class="fa-solid fa-crown text-xl text-gold-400" id="leading-team-crown"></i>`;
                        }
                        // Sync Bids History Feed
                        const historyList = document.getElementById('bid-history-list');
                        historyList.innerHTML = '';

                        if (data.bid_history && data.bid_history.length > 0) {
                            data.bid_history.forEach(log => {
                                const li = document.createElement('div');
                                li.className = "flex items-center justify-between p-2.5 rounded-lg bg-white/5 border border-white/5 text-xs transition hover:bg-white/10";
                                li.innerHTML = `
                                    <div class="flex items-center gap-2">
                                        <span class="text-gold-400 font-bold">₹${log.bid_amount}</span>
                                        <span class="text-gray-300 font-medium">${log.team_name}</span>
                                    </div>
                                `;
                                historyList.appendChild(li);
                            });
                        } else {
                            historyList.innerHTML = `
                                <div class="text-center text-[10px] text-gray-500 py-6 uppercase font-semibold tracking-wider">
                                    Waiting for opening bid...
                                </div>
                            `;
                        }

                    } else {
                        // Player has transitioned off the block!
                        if (activePlayerId !== null) {
                            const oldPlayerId = activePlayerId;
                            activePlayerId = null;
                            lastBidAmount = 0;
                            checkPastPlayerStatus(oldPlayerId);
                        }

                        standbyBox.classList.remove('hidden');
                        playerCard.classList.add('hidden');
                        bidCard.classList.add('hidden');
                    }
                }

                // 3. Sync Leaderboards Standings
                const leaderboard = document.getElementById('teams-leaderboard');
                leaderboard.innerHTML = '';

                if (data.teams && data.teams.length > 0) {
                    data.teams.forEach(team => {
                        const spent = (team.total_purse - team.remaining_purse);
                        const isLeading = (data.leading_team_id && data.leading_team_id === parseInt(team.id));

                        const div = document.createElement('div');
                        div.className = `p-3.5 rounded-xl border transition cursor-pointer flex flex-col justify-between shadow-sm ${
                            isLeading 
                                ? 'bg-amber-100/90 border-amber-500 shadow-md hover:bg-amber-100' 
                                : 'bg-white/90 border-slate-200 hover:border-amber-500/50 hover:bg-white'
                        }`;
                        div.onclick = () => openTeamDetailsModal(team.id);
                        const logoSrc = team.logo ? "uploads/" + team.logo : "uploads/team_placeholder.jpg";
                        div.innerHTML = `
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-2.5">
                                    <img src="${logoSrc}" class="w-7 h-7 rounded object-contain bg-white p-0.5 border border-slate-200 shadow-sm">
                                    <span class="text-sm font-black text-slate-900">${team.team_name}</span>
                                    ${isLeading ? '<span class="text-[8px] uppercase tracking-widest font-black text-amber-900 bg-amber-200 px-1.5 py-0.5 rounded border border-amber-400 animate-pulse">High Bidder</span>' : ''}
                                </div>
                                <span class="text-xs font-mono font-black text-amber-800">₹${Number(team.remaining_purse).toLocaleString('en-IN')} left</span>
                            </div>
                            <div class="flex items-center justify-between mt-2.5 text-[10px] text-slate-700 font-bold">
                                <span>Squad: <strong class="text-slate-900 font-extrabold">${team.current_squad_size}/${team.max_squad_size} players</strong></span>
                                <span>Spent: <strong class="text-slate-900 font-mono font-extrabold">₹${Number(spent).toLocaleString('en-IN')}</strong></span>
                            </div>
                        `;
                        leaderboard.appendChild(div);
                    });
                } else {
                    const emptyMsg = (data && data.error) 
                        ? 'MySQL server is stopped. Please start MySQL in XAMPP Control Panel.' 
                        : 'No franchise teams registered yet.';
                    leaderboard.innerHTML = `
                        <div class="text-center text-xs text-slate-500 py-8 px-4 font-medium flex flex-col items-center gap-2">
                            <i class="fa-solid fa-database text-gold-600 text-xl opacity-60"></i>
                            <span>${emptyMsg}</span>
                        </div>
                    `;
                }

                // 4. Sync Completed Player Auctions
                allCompletedPlayers = data.all_players || [];
                renderCompletedPlayers();

            } catch (error) {
                console.error("Dashboard synchronization error:", error);
            }
        }

        // Filter handler
        function setStatusFilter(filterValue) {
            activeStatusFilter = filterValue;
            
            // Update chip styles
            const chips = document.querySelectorAll('#public-status-filter-container .status-chip');
            chips.forEach(chip => {
                if (chip.getAttribute('data-filter') === filterValue) {
                    chip.className = "status-chip px-3 py-1.5 rounded-lg border text-[10px] uppercase font-black tracking-wider transition bg-slate-900 border-slate-900 text-white shadow-sm";
                } else {
                    chip.className = "status-chip px-3 py-1.5 rounded-lg border text-[10px] uppercase font-extrabold tracking-wider transition border-slate-300 bg-white/90 text-slate-800 hover:bg-slate-900 hover:text-white";
                }
            });

            renderCompletedPlayers();
        }

        // Dynamic Team & Status Color Palette Engine
        function getPlayerCardTheme(p) {
            const isSold = (p.auction_status === 'Sold');
            const isUnsold = (p.auction_status === 'Unsold');

            if (isUnsold) {
                return {
                    bg: 'linear-gradient(135deg, #334155 0%, #1e293b 40%, #7f1d1d 80%, #450a0a 100%)',
                    watermark: 'UNSOLD',
                    watermarkColor: 'rgba(239, 68, 68, 0.28)',
                    badgeBg: 'linear-gradient(145deg, rgba(30, 41, 59, 0.9) 0%, rgba(51, 65, 85, 0.95) 100%)',
                    badgeBorder: 'rgba(239, 68, 68, 0.4)',
                    priceBg: 'linear-gradient(145deg, rgba(153, 27, 27, 0.9) 0%, rgba(185, 28, 28, 0.95) 100%)',
                    priceBorder: 'rgba(239, 68, 68, 0.5)',
                    roleBadge: 'bg-red-500/30 border border-red-400/40 text-red-200 font-black'
                };
            }

            if (!isSold) {
                // Available
                return {
                    bg: 'linear-gradient(135deg, #0f766e 0%, #115e59 40%, #1e3a8a 80%, #0f172a 100%)',
                    watermark: 'AVAILABLE',
                    watermarkColor: 'rgba(45, 212, 191, 0.28)',
                    badgeBg: 'linear-gradient(145deg, rgba(15, 118, 110, 0.9) 0%, rgba(17, 94, 89, 0.95) 100%)',
                    badgeBorder: 'rgba(45, 212, 191, 0.5)',
                    priceBg: 'linear-gradient(145deg, rgba(30, 58, 138, 0.9) 0%, rgba(30, 27, 75, 0.95) 100%)',
                    priceBorder: 'rgba(129, 140, 248, 0.5)',
                    roleBadge: 'bg-teal-400 text-slate-950 font-black'
                };
            }

            // Sold Player: Calculate team-specific color palette based on team_id or team_name
            const teamPalettes = [
                // 0: Royal Blue & Crimson Red (Delhi / Mumbai IPL style)
                {
                    bg: 'linear-gradient(135deg, #1d4ed8 0%, #2563eb 35%, #dc2626 70%, #991b1b 100%)',
                    badgeBg: 'linear-gradient(145deg, rgba(30, 58, 138, 0.95) 0%, rgba(29, 78, 216, 0.95) 100%)',
                    badgeBorder: 'rgba(147, 197, 253, 0.6)',
                    priceBg: 'linear-gradient(145deg, rgba(153, 27, 27, 0.95) 0%, rgba(220, 38, 38, 0.95) 100%)',
                    priceBorder: 'rgba(252, 165, 165, 0.6)',
                    roleBadge: 'bg-amber-400 text-slate-950 font-black'
                },
                // 1: Emerald Green & Dark Teal (Calicut Warriors style)
                {
                    bg: 'linear-gradient(135deg, #065f46 0%, #059669 35%, #10b981 70%, #047857 100%)',
                    badgeBg: 'linear-gradient(145deg, rgba(6, 78, 59, 0.95) 0%, rgba(4, 120, 87, 0.95) 100%)',
                    badgeBorder: 'rgba(110, 231, 183, 0.6)',
                    priceBg: 'linear-gradient(145deg, rgba(6, 95, 70, 0.95) 0%, rgba(16, 185, 129, 0.95) 100%)',
                    priceBorder: 'rgba(167, 243, 208, 0.6)',
                    roleBadge: 'bg-emerald-300 text-slate-950 font-black'
                },
                // 2: Royal Purple & Amber Gold (Kochi Kings style)
                {
                    bg: 'linear-gradient(135deg, #581c87 0%, #7e22ce 35%, #d97706 70%, #b45309 100%)',
                    badgeBg: 'linear-gradient(145deg, rgba(88, 28, 135, 0.95) 0%, rgba(126, 34, 206, 0.95) 100%)',
                    badgeBorder: 'rgba(216, 180, 254, 0.6)',
                    priceBg: 'linear-gradient(145deg, rgba(180, 83, 9, 0.95) 0%, rgba(217, 119, 6, 0.95) 100%)',
                    priceBorder: 'rgba(253, 230, 138, 0.6)',
                    roleBadge: 'bg-amber-300 text-slate-950 font-black'
                },
                // 3: Ocean Cyan & Deep Navy (Trivandrum Titans style)
                {
                    bg: 'linear-gradient(135deg, #0e7490 0%, #0284c7 35%, #1e3a8a 70%, #1e1b4b 100%)',
                    badgeBg: 'linear-gradient(145deg, rgba(14, 116, 144, 0.95) 0%, rgba(2, 132, 199, 0.95) 100%)',
                    badgeBorder: 'rgba(165, 243, 252, 0.6)',
                    priceBg: 'linear-gradient(145deg, rgba(30, 58, 138, 0.95) 0%, rgba(30, 27, 75, 0.95) 100%)',
                    priceBorder: 'rgba(199, 210, 254, 0.6)',
                    roleBadge: 'bg-cyan-300 text-slate-950 font-black'
                },
                // 4: Flame Orange & Crimson Amber (Malabar Mavericks style)
                {
                    bg: 'linear-gradient(135deg, #c2410c 0%, #ea580c 35%, #b91c1c 70%, #881337 100%)',
                    badgeBg: 'linear-gradient(145deg, rgba(194, 65, 12, 0.95) 0%, rgba(234, 88, 12, 0.95) 100%)',
                    badgeBorder: 'rgba(253, 186, 116, 0.6)',
                    priceBg: 'linear-gradient(145deg, rgba(136, 19, 55, 0.95) 0%, rgba(185, 28, 28, 0.95) 100%)',
                    priceBorder: 'rgba(254, 205, 211, 0.6)',
                    roleBadge: 'bg-orange-300 text-slate-950 font-black'
                }
            ];

            const teamIdNum = parseInt(p.team_id) || 0;
            const themeIndex = teamIdNum % teamPalettes.length;
            const theme = teamPalettes[themeIndex];

            return {
                bg: theme.bg,
                watermark: 'SOLD SOLD SOLD',
                watermarkColor: 'rgba(255, 255, 255, 0.28)',
                badgeBg: theme.badgeBg,
                badgeBorder: theme.badgeBorder,
                priceBg: theme.priceBg,
                priceBorder: theme.priceBorder,
                roleBadge: theme.roleBadge
            };
        }

        // Render completed players list dynamically (supports real-time search filtering)
        function renderCompletedPlayers() {
            const completedGrid = document.getElementById('completed-players-grid');
            const completedEmpty = document.getElementById('completed-empty-box');
            const searchInput = document.getElementById('player-search-input');
            const searchQuery = searchInput ? searchInput.value.toLowerCase().trim() : '';

            completedGrid.innerHTML = '';

            let soldCount = 0;
            let unsoldCount = 0;
            let availableCount = 0;

            // Compute overall totals from unfiltered array
            allCompletedPlayers.forEach(p => {
                if (p.auction_status === 'Sold') soldCount++;
                else if (p.auction_status === 'Unsold') unsoldCount++;
                else if (p.auction_status === 'Available') availableCount++;
            });

            // Update chip counts
            const allChip = document.querySelector('#public-status-filter-container [data-filter="all"]');
            if (allChip) allChip.innerText = `ALL (${allCompletedPlayers.length})`;

            const soldChip = document.querySelector('#public-status-filter-container [data-filter="Sold"]');
            if (soldChip) soldChip.innerText = `SOLD (${soldCount})`;

            const unsoldChip = document.querySelector('#public-status-filter-container [data-filter="Unsold"]');
            if (unsoldChip) unsoldChip.innerText = `UNSOLD (${unsoldCount})`;

            const availChip = document.querySelector('#public-status-filter-container [data-filter="Available"]');
            if (availChip) availChip.innerText = `AVAILABLE (${availableCount})`;

            // Apply search query and status filter
            const filteredPlayers = allCompletedPlayers.filter(p => {
                // Status Filter
                if (activeStatusFilter !== 'all' && p.auction_status !== activeStatusFilter) {
                    return false;
                }

                if (!searchQuery) return true;
                return (
                    p.name.toLowerCase().includes(searchQuery) ||
                    p.role.toLowerCase().includes(searchQuery) ||
                    p.place.toLowerCase().includes(searchQuery) ||
                    (p.team_name && p.team_name.toLowerCase().includes(searchQuery))
                );
            });

            if (filteredPlayers.length > 0) {
                completedEmpty.classList.add('hidden');
                completedGrid.classList.remove('hidden');

                filteredPlayers.forEach(p => {
                    const card = document.createElement('div');
                    
                    const isSold = (p.auction_status === 'Sold');
                    const isUnsold = (p.auction_status === 'Unsold');
                    
                    const theme = getPlayerCardTheme(p);

                    card.className = `ipl-card-frame p-5 flex flex-col justify-between cursor-pointer transition-all duration-300 relative group overflow-hidden shadow-2xl`;
                    card.style.background = theme.bg;
                    card.onclick = () => openPlayerDetailsModal(p.id);
                    
                    const nameParts = p.name.trim().split(' ');
                    const firstName = nameParts[0] || p.name;
                    const lastName = nameParts.slice(1).join(' ') || '';
                    
                    const profileImg = p.profile_image ? p.profile_image : 'player_placeholder.jpg';
                    const teamLogo = p.team_logo ? p.team_logo : 'team_placeholder.jpg';
                    const teamName = p.team_name || 'No Team';

                    card.innerHTML = `
                        <!-- Diagonal Watermark Background -->
                        <div class="ipl-watermark-text" style="color: ${theme.watermarkColor}">${theme.watermark}</div>
                        <div class="ipl-card-stage-glow"></div>

                        <!-- Top Header: Country/Location & Role -->
                        <div class="flex items-center justify-between relative z-10 mb-3">
                            <div class="flex items-center gap-1.5 bg-black/50 border border-white/20 px-2.5 py-1 rounded-lg backdrop-blur-md">
                                <span class="text-xs">🇮🇳</span>
                                <div class="text-[9px] font-black text-white uppercase tracking-wider font-mono">
                                    ${p.place}
                                </div>
                            </div>
                            <span class="px-2.5 py-1 rounded-lg text-[8px] uppercase tracking-wider shadow-md ${theme.roleBadge}">
                                ${p.role}
                            </span>
                        </div>

                        <!-- Stacked Player Name -->
                        <div class="relative z-10 mb-2">
                            <div class="text-[10px] font-extrabold text-gold-300 uppercase tracking-widest leading-tight drop-shadow-sm">${firstName}</div>
                            <h4 class="text-xl font-black text-white uppercase tracking-tight leading-none drop-shadow-md">${lastName ? lastName : firstName}</h4>
                        </div>

                        <!-- Center Stage: Cutout Player Image -->
                        <div class="relative z-10 my-3 flex justify-center">
                            <div class="w-32 h-36 rounded-2xl overflow-hidden border-2 border-white/50 bg-slate-900/60 shadow-2xl relative cursor-zoom-in group-hover:scale-105 transition duration-300" onclick="event.stopPropagation(); openImageLightbox('uploads/${profileImg}', '${p.name.replace(/'/g, "\\\'")}');">
                                <img src="uploads/${profileImg}" alt="${p.name}" class="w-full h-full object-cover" onerror="this.onerror=null; this.src='uploads/player_placeholder.jpg';">
                            </div>
                        </div>

                        <!-- Bottom Broadcast Grid (Sold To & Price) -->
                        <div class="grid grid-cols-2 gap-2.5 relative z-10 pt-2">
                            <!-- Left: Sold To / Team -->
                            <div class="p-2.5 flex flex-col items-center justify-center text-center rounded-xl shadow-lg transition ${isSold ? 'cursor-pointer hover:opacity-90' : ''}" style="background: ${theme.badgeBg}; border: 1.5px solid ${theme.badgeBorder};" ${isSold ? `onclick="event.stopPropagation(); openTeamDetailsModal(${p.team_id})"` : ''}>
                                <span class="text-[7.5px] uppercase font-black text-white/90 tracking-wider bg-black/40 px-2 py-0.5 rounded border border-white/20 mb-1">
                                    ${isSold ? 'SOLD TO' : 'STATUS'}
                                </span>
                                ${isSold 
                                    ? `<div class="flex items-center gap-1.5 mt-0.5 justify-center max-w-[100px]">
                                         <img src="uploads/${teamLogo}" class="w-5 h-5 rounded object-contain bg-white/20 p-0.5 border border-white/30">
                                         <span class="text-[10px] font-extrabold text-white tracking-tight truncate">${teamName}</span>
                                       </div>` 
                                    : `<span class="text-xs font-black text-white mt-0.5">${p.auction_status}</span>`
                                }
                            </div>

                            <!-- Right: Price & Base Price -->
                            <div class="p-2.5 flex flex-col items-center justify-center text-center rounded-xl shadow-lg" style="background: ${theme.priceBg}; border: 1.5px solid ${theme.priceBorder};">
                                <span class="text-[7.5px] uppercase font-black text-white/90 tracking-wider mb-0.5">
                                    ${isSold ? 'FINAL PRICE' : 'BASE PRICE'}
                                </span>
                                <span class="text-sm font-black text-white font-mono tracking-tight drop-shadow-md">
                                    ${isSold ? '₹' + Number(p.sold_price).toLocaleString('en-IN') : '₹' + Number(p.base_price).toLocaleString('en-IN')}
                                </span>
                                ${isSold ? `<span class="text-[7px] text-white/80 font-semibold uppercase mt-0.5">BASE: ₹${Number(p.base_price).toLocaleString('en-IN')}</span>` : ''}
                            </div>
                        </div>
                    `;
                    completedGrid.appendChild(card);
                });
            } else {
                completedEmpty.classList.remove('hidden');
                completedEmpty.innerText = searchQuery ? "No completed players match your query." : "No finalized auctions yet. Bids are ongoing!";
            }
        }

        // Fetch whether player was sold or unsold to trigger exact sound
        async function checkPastPlayerStatus(playerId) {
            try {
                const response = await fetch(`../api/get_player_status.php?player_id=${playerId}`);
                const res = await response.json();
                if (res.success) {
                    if (res.auction_status === 'Sold') {
                        SMCLSoundEngine.playSold();
                    } else if (res.auction_status === 'Unsold') {
                        SMCLSoundEngine.playUnsold();
                    }
                }
            } catch (e) {
                console.error("Failed to lookup past player status:", e);
            }
        }

        // Render final auction statistics
        function renderAuctionStats(data) {
            const allPlayers = data.all_players || [];
            const teams = data.teams || [];

            // 1. Calculations
            const soldPlayers = allPlayers.filter(p => p.auction_status === 'Sold');
            const unsoldPlayers = allPlayers.filter(p => p.auction_status === 'Unsold');

            const totalSpent = soldPlayers.reduce((sum, p) => sum + (parseInt(p.sold_price) || 0), 0);
            const avgPrice = soldPlayers.length > 0 ? Math.round(totalSpent / soldPlayers.length) : 0;
            const soldCount = soldPlayers.length;
            const unsoldCount = unsoldPlayers.length;

            // Update stats cards text
            document.getElementById('stats-total-spent').innerText = "₹" + totalSpent.toLocaleString('en-IN');
            document.getElementById('stats-avg-price').innerText = "₹" + avgPrice.toLocaleString('en-IN');
            document.getElementById('stats-sold-count').innerText = soldCount;
            document.getElementById('stats-unsold-count').innerText = unsoldCount;

            // 2. Render Top Valued Players (Top 5)
            const topPlayersEl = document.getElementById('stats-top-players');
            topPlayersEl.innerHTML = '';

            const topSold = [...soldPlayers]
                .sort((a, b) => (parseInt(b.sold_price) || 0) - (parseInt(a.sold_price) || 0))
                .slice(0, 5);

            if (topSold.length === 0) {
            const topPlayers = [...soldPlayers]
                .sort((a, b) => (parseInt(b.sold_price) || 0) - (parseInt(a.sold_price) || 0));

            if (topPlayers.length === 0) {
                topPlayersEl.innerHTML = `
                    <div class="text-center text-[10px] text-gray-500 py-8 uppercase font-semibold">
                        No players sold yet.
                    </div>
                `;
            } else {
                topPlayers.slice(0, 5).forEach((p, idx) => {
                    const row = document.createElement('div');
                    row.className = "p-3 rounded-xl bg-white/90 border border-slate-200 text-xs flex items-center justify-between hover:bg-white transition cursor-pointer relative group shadow-sm";
                    row.onclick = () => openPlayerDetailsModal(p.id);

                    // Rank indicator badge
                    let rankBadge = '';
                    if (idx === 0) {
                        rankBadge = '<span class="w-6 h-6 rounded-lg bg-amber-100 border border-amber-300 text-amber-800 flex items-center justify-center font-black text-[10px] flex-shrink-0"><i class="fa-solid fa-crown"></i></span>';
                    } else if (idx === 1) {
                        rankBadge = '<span class="w-6 h-6 rounded-lg bg-slate-200 border border-slate-300 text-slate-900 flex items-center justify-center font-black text-[10px] flex-shrink-0">2</span>';
                    } else if (idx === 2) {
                        rankBadge = '<span class="w-6 h-6 rounded-lg bg-amber-200/60 border border-amber-300 text-amber-900 flex items-center justify-center font-black text-[10px] flex-shrink-0">3</span>';
                    } else {
                        rankBadge = `<span class="w-6 h-6 rounded-lg bg-slate-100 border border-slate-200 text-slate-800 flex items-center justify-center font-extrabold text-[10px] flex-shrink-0">${idx + 1}</span>`;
                    }

                    const profileImg = p.profile_image ? p.profile_image : 'player_placeholder.jpg';
                    const teamLogo = p.team_logo ? p.team_logo : 'team_placeholder.jpg';

                    row.innerHTML = `
                        <div class="flex items-center gap-3 min-w-0">
                            ${rankBadge}
                            <div class="w-10 h-10 rounded-lg overflow-hidden border border-slate-200 bg-white flex-shrink-0 cursor-zoom-in shadow-sm" onclick="event.stopPropagation(); openImageLightbox('uploads/${profileImg}', '${p.name.replace(/'/g, "\\\'")}');">
                                <img src="uploads/${profileImg}" class="w-full h-full object-cover" onerror="this.onerror=null; this.src='uploads/player_placeholder.jpg';">
                            </div>
                            <div class="min-w-0">
                                <span class="text-slate-900 font-black block truncate group-hover:text-amber-700 transition">${p.name}</span>
                                <span class="text-[9px] text-slate-700 font-bold uppercase tracking-widest block truncate mt-0.5">${p.role} &bull; ${p.place}</span>
                            </div>
                        </div>
                        <div class="flex items-center gap-3 flex-shrink-0">
                            <div class="flex items-center gap-1.5 bg-slate-100 px-2 py-1 rounded-lg border border-slate-200 max-w-[120px] truncate" onclick="event.stopPropagation(); openTeamDetailsModal(${p.team_id});">
                                <img src="uploads/${teamLogo}" class="w-4 h-4 rounded object-contain bg-white p-0.5 border border-slate-200">
                                <span class="text-[9px] font-extrabold text-slate-900 truncate max-w-[80px]">${p.team_name}</span>
                            </div>
                            <span class="text-amber-800 font-black font-mono text-sm min-w-[65px] text-right">₹${Number(p.sold_price).toLocaleString('en-IN')}</span>
                        </div>
                    `;
                    topPlayersEl.appendChild(row);
                });
            }

            // 3. Render Franchise Leaderboard
            const teamsSpentEl = document.getElementById('stats-teams-spent');
            teamsSpentEl.innerHTML = '';

            const teamSpendData = teams.map(t => {
                const spent = parseInt(t.total_purse) - parseInt(t.remaining_purse);
                
                // Find most expensive buy
                const teamSoldPlayers = soldPlayers.filter(p => p.team_id == t.id);
                let topBuy = null;
                if (teamSoldPlayers.length > 0) {
                    topBuy = [...teamSoldPlayers].sort((a, b) => (parseInt(b.sold_price) || 0) - (parseInt(a.sold_price) || 0))[0];
                }

                return {
                    ...t,
                    spent,
                    topBuy
                };
            }).sort((a, b) => b.spent - a.spent);

            teamSpendData.forEach(t => {
                const row = document.createElement('div');
                row.className = "p-3 rounded-xl bg-slate-50 border border-slate-200 text-xs hover:bg-slate-100 transition cursor-pointer relative group flex flex-col gap-2 shadow-sm";
                row.onclick = () => openTeamDetailsModal(t.id);

                const logoSrc = t.logo ? "uploads/" + t.logo : "uploads/team_placeholder.jpg";
                const spentPct = Math.round((t.spent / t.total_purse) * 100);

                let topBuyHtml = '<span class="text-[9px] text-slate-500 font-semibold uppercase">Top Buy: None</span>';
                if (t.topBuy) {
                    topBuyHtml = `
                        <div class="flex items-center justify-between text-[9px] border-t border-slate-200 pt-1.5 mt-0.5">
                            <span class="text-slate-500 uppercase font-semibold">Top Buy</span>
                            <span class="text-slate-900 font-bold max-w-[120px] truncate">${t.topBuy.name}</span>
                            <span class="text-gold-700 font-bold font-mono">₹${Number(t.topBuy.sold_price).toLocaleString('en-IN')}</span>
                        </div>
                    `;
                }

                row.innerHTML = `
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2.5 min-w-0">
                            <img src="${logoSrc}" class="w-8 h-8 rounded object-contain bg-white p-0.5 border border-slate-200 shadow-sm">
                            <div class="min-w-0">
                                <span class="text-slate-900 font-black block truncate group-hover:text-gold-700 transition">${t.team_name}</span>
                                <span class="text-[9px] text-slate-500 uppercase tracking-widest font-semibold">Purse Spent: ${spentPct}%</span>
                            </div>
                        </div>
                        <div class="text-right">
                            <span class="text-gold-700 font-black font-mono block text-sm">₹${Number(t.spent).toLocaleString('en-IN')}</span>
                            <span class="text-[9px] text-slate-600 font-bold uppercase tracking-wider block mt-0.5">Purse Left: ₹${Number(t.remaining_purse).toLocaleString('en-IN')}</span>
                        </div>
                    </div>

                    <!-- Squad Limit Progress Bar -->
                    <div class="space-y-1">
                        <div class="flex justify-between items-center text-[9px] text-slate-600 font-semibold">
                            <span>Squad size</span>
                            <span class="font-bold text-slate-900">${t.current_squad_size} / ${t.max_squad_size}</span>
                        </div>
                        <div class="w-full bg-slate-200 rounded-full h-1 border border-slate-300 overflow-hidden">
                            <div class="bg-gold-500 h-full rounded-full transition-all duration-300" style="width: ${(t.current_squad_size / t.max_squad_size) * 100}%"></div>
                        </div>
                    </div>

                    ${topBuyHtml}
                `;
                teamsSpentEl.appendChild(row);
            });
        }

        // PWA Install Prompt Script
        let deferredPrompt;
        const installBtn = document.getElementById('pwa-install-btn');

        window.addEventListener('beforeinstallprompt', (e) => {
            e.preventDefault();
            deferredPrompt = e;
            if (installBtn) {
                installBtn.classList.remove('hidden');
            }
        });

        if (installBtn) {
            installBtn.addEventListener('click', (e) => {
                if (!deferredPrompt) return;
                installBtn.classList.add('hidden');
                deferredPrompt.prompt();
                deferredPrompt.userChoice.then((choiceResult) => {
                    if (choiceResult.outcome === 'accepted') {
                        console.log('User installed the PWA app');
                    }
                    deferredPrompt = null;
                });
            });
        }

    </script>
    <?php 
        $uploadPath = "uploads/";
        require_once 'components/modals.php'; 
    ?>
</body>
</html>
