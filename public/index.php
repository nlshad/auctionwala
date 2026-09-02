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
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($tName); ?> — Live Auction Arena</title>
    <?php require_once 'components/ui_head.php'; ?>
    <script>
        window.activeTournamentCode = "<?php echo htmlspecialchars($tCode); ?>";
        window.activeTournamentId = <?php echo (int)$tournamentId; ?>;
    </script>
    <style>
        body {
            background-color: #f8fafc;
            color: #0f172a;
            font-family: 'Inter', sans-serif;
            min-height: 100vh;
        }

        .pro-card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -2px rgba(0, 0, 0, 0.05);
            transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
        }
    </style>
</head>
<body class="min-h-screen flex flex-col justify-between selection:bg-red-500 selection:text-white">
    
    <!-- Header / Brand Bar -->
    <header class="w-full bg-white border-b border-slate-200 px-4 py-3 sm:px-8 flex items-center justify-between sticky top-0 z-40 shadow-sm">
        <div class="flex items-center gap-3 sm:gap-4">
            <a href="landing.php" class="flex items-center gap-2">
                <img src="uploads/auctionwala_logo.png" alt="AuctionWala Logo" class="h-8 sm:h-9 object-contain">
            </a>
            <div class="h-6 w-px bg-slate-200 hidden sm:block"></div>
            <div>
                <h1 class="text-sm sm:text-base font-montserrat font-black uppercase tracking-tight text-slate-900 leading-none">
                    <?php echo htmlspecialchars($tName); ?>
                </h1>
                <p class="text-[9px] font-mono text-amber-700 font-bold uppercase tracking-wider mt-0.5">Live Bidding Arena</p>
            </div>
        </div>

        <!-- Live Auction Status Indicator -->
        <div class="flex items-center gap-3 sm:gap-4">
            <button id="sound-toggle-btn" onclick="toggleMute()" class="flex items-center justify-center bg-slate-100 border border-slate-200 hover:bg-slate-200 w-9 h-9 rounded-xl text-xs transition shadow-sm" title="Toggle Sound Effects">
                <i id="sound-icon" class="fa-solid fa-volume-high text-xs text-amber-600"></i>
            </button>

            <div class="hidden sm:flex items-center gap-2 bg-slate-100 border border-slate-200 rounded-xl px-3 py-1.5 text-xs shadow-sm">
                <span class="w-2.5 h-2.5 rounded-full bg-red-600 animate-pulse live-dot" id="status-light"></span>
                <span class="text-slate-900 font-montserrat font-extrabold tracking-wider uppercase text-[10px]" id="status-text">Live Arena</span>
            </div>

            <!-- Login Quick Redirects -->
            <div class="flex gap-2 text-xs">
                <!-- PWA Install Button -->
                <button id="pwa-install-btn" class="hidden text-[10px] font-montserrat font-bold uppercase tracking-wider bg-slate-100 border border-slate-200 text-amber-700 hover:bg-slate-200 px-3 py-2 rounded-xl transition flex items-center justify-center gap-1 shadow-sm">
                    <i class="fa-solid fa-cloud-arrow-down text-amber-600"></i> App
                </button>
                <?php if ($registrationEnabled): ?>
                    <a href="register.php?t_id=<?php echo $tournamentId; ?>" class="text-[10px] font-montserrat font-bold uppercase tracking-wider bg-red-600 hover:bg-red-700 text-white px-3.5 py-2 rounded-xl transition flex items-center justify-center shadow-sm">
                        Register
                    </a>
                <?php else: ?>
                    <span class="text-[10px] font-montserrat font-bold uppercase tracking-wider bg-slate-100 border border-red-200 text-red-600 px-3.5 py-2 rounded-xl cursor-not-allowed flex items-center justify-center shadow-sm">
                        Closed
                    </span>
                <?php endif; ?>
                <a href="login.php" class="text-[10px] font-montserrat font-extrabold uppercase tracking-wider bg-amber-500 hover:bg-amber-600 text-slate-950 px-3.5 py-2 rounded-xl transition shadow-sm flex items-center justify-center">
                    Portals
                </a>
            </div>
        </div>
    </header>

    <!-- Main Live Dashboard Arena -->
    <main class="flex-grow p-4 md:p-6 max-w-7xl w-full mx-auto grid grid-cols-12 gap-6 relative">

        <!-- LEFT SIDE: Bento-Grid Auction Section (8 Cols) -->
        <div class="col-span-12 lg:col-span-8 grid grid-cols-12 gap-6" id="auction-grid">
            
            <!-- Standard Standby Box (Will show when current_player is NULL) -->
            <div id="standby-box" class="col-span-12 pro-card rounded-2xl p-10 text-center flex flex-col items-center justify-center min-h-[450px] shadow-sm">
                <i class="fa-solid fa-gavel text-6xl text-amber-600 animate-bounce mb-4 block"></i>
                <h2 class="text-2xl sm:text-3xl font-montserrat font-black text-slate-900 uppercase tracking-tight">AuctionWala Live Bidding Arena</h2>
                <p class="text-slate-600 max-w-md mt-2 text-xs sm:text-sm font-inter leading-relaxed">
                    Welcome to the AuctionWala Live Bidding Arena. Bidding will commence as the Auctioneer brings the next player to the block.
                </p>
                <div class="mt-6 flex flex-wrap justify-center gap-3">
                    <span class="text-xs font-mono font-bold text-blue-700 uppercase tracking-widest bg-slate-100 border border-slate-200 px-3.5 py-2 rounded-xl shadow-sm">
                        ⚡ Real-Time Concurrency Active
                    </span>
                    <span class="text-xs font-mono font-bold text-amber-700 uppercase tracking-widest bg-slate-100 border border-slate-200 px-3.5 py-2 rounded-xl shadow-sm">
                        🏆 Season 2026
                    </span>
                </div>
            </div>

            <!-- Completed Stats Box -->
            <div id="completed-stats-box" class="hidden col-span-12 pro-card rounded-2xl p-6 flex flex-col gap-6 relative overflow-hidden shadow-sm">
                <!-- Header -->
                <div class="flex items-center justify-between pb-4 border-b border-slate-100 relative z-10">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-amber-50 border border-amber-200 flex items-center justify-center text-amber-600">
                            <i class="fa-solid fa-trophy text-lg"></i>
                        </div>
                        <div>
                            <h2 class="text-base font-montserrat font-black text-slate-900 uppercase tracking-tight">Auction Completed</h2>
                            <p class="text-[9px] font-mono text-slate-500 uppercase tracking-wider font-bold">Final Stats & Leaderboards</p>
                        </div>
                    </div>
                    <span class="px-2.5 py-1 rounded-full bg-amber-100 border border-amber-300 text-amber-800 text-[9px] font-mono uppercase tracking-wider font-bold flex items-center gap-1.5 shadow-sm">
                        <i class="fa-solid fa-circle text-[6px] text-amber-600 animate-pulse"></i> Final Summary
                    </span>
                </div>

                <!-- Stats Cards Grid -->
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 relative z-10">
                    <div class="bg-slate-50 border border-slate-200 rounded-xl p-4 text-center shadow-sm">
                        <span class="text-[9px] text-slate-500 uppercase tracking-wider block font-bold">Total Purse Spent</span>
                        <span class="text-xl font-montserrat font-black text-amber-700 font-mono mt-1 block" id="stats-total-spent">₹0</span>
                    </div>
                    <div class="bg-slate-50 border border-slate-200 rounded-xl p-4 text-center shadow-sm">
                        <span class="text-[9px] text-slate-500 uppercase tracking-wider block font-bold">Avg. Player Bid</span>
                        <span class="text-xl font-montserrat font-black text-slate-900 font-mono mt-1 block" id="stats-avg-price">₹0</span>
                    </div>
                    <div class="bg-slate-50 border border-slate-200 rounded-xl p-4 text-center shadow-sm">
                        <span class="text-[9px] text-slate-500 uppercase tracking-wider block font-bold">Players Sold</span>
                        <span class="text-xl font-montserrat font-black text-emerald-600 font-mono mt-1 block" id="stats-sold-count">0</span>
                    </div>
                    <div class="bg-slate-50 border border-slate-200 rounded-xl p-4 text-center shadow-sm">
                        <span class="text-[9px] text-slate-500 uppercase tracking-wider block font-bold">Unsold Players</span>
                        <span class="text-xl font-montserrat font-black text-red-600 font-mono mt-1 block" id="stats-unsold-count">0</span>
                    </div>
                </div>

                <!-- Double Columns -->
                <div class="grid grid-cols-1 md:grid-cols-12 gap-6 relative z-10 mt-2">
                    <div class="md:col-span-7 space-y-3.5">
                        <h3 class="text-xs font-montserrat font-bold text-amber-700 uppercase tracking-wider flex items-center gap-1.5 pb-2 border-b border-slate-100">
                            <i class="fa-solid fa-crown text-amber-600"></i> Top Valued Players
                        </h3>
                        <div class="space-y-2.5 max-h-[360px] overflow-y-auto pr-1" id="stats-top-players">
                            <!-- Populated dynamically -->
                        </div>
                    </div>
                    <div class="md:col-span-5 space-y-3.5">
                        <h3 class="text-xs font-montserrat font-bold text-amber-700 uppercase tracking-wider flex items-center gap-1.5 pb-2 border-b border-slate-100">
                            <i class="fa-solid fa-chart-line text-amber-600"></i> Franchise Pursetracker
                        </h3>
                        <div class="space-y-2.5 max-h-[360px] overflow-y-auto pr-1" id="stats-teams-spent">
                            <!-- Populated dynamically -->
                        </div>
                    </div>
                </div>
            </div>

            <!-- Active Player Profile Bento (5 Cols) -->
            <div id="player-card" class="hidden col-span-12 md:col-span-5 pro-card p-5 rounded-2xl flex flex-col justify-between relative group shadow-sm">
                <!-- Top Header: Country/Location & Role -->
                <div class="flex items-center justify-between relative z-10 mb-3">
                    <div class="flex items-center gap-1.5 bg-slate-100 border border-slate-200 px-3 py-1 rounded-lg">
                        <span class="text-xs">🇮🇳</span>
                        <span class="text-[9px] font-mono font-bold text-slate-800 uppercase tracking-wider" id="player-place">
                            Wayanad
                        </span>
                    </div>
                    <span class="bg-red-100 border border-red-200 text-red-700 text-[9px] font-mono font-bold px-3 py-1 rounded-lg uppercase tracking-wider shadow-sm" id="player-role">
                        ALL-ROUNDER
                    </span>
                </div>

                <!-- Stacked Player Name -->
                <div class="relative z-10 mb-2 text-center">
                    <h2 class="text-2xl font-montserrat font-black text-slate-900 uppercase tracking-tight leading-none" id="player-name">---</h2>
                </div>

                <!-- Center Stage: Cutout Player Image -->
                <div class="relative z-10 my-3 flex justify-center">
                    <div class="w-36 h-40 rounded-2xl overflow-hidden border-2 border-red-500 bg-slate-100 shadow-md relative cursor-pointer" onclick="openImageLightbox(document.getElementById('player-image').src, document.getElementById('player-name').innerText);">
                        <img src="uploads/player_placeholder.jpg" id="player-image" alt="Player Image" class="w-full h-full object-cover" onerror="this.onerror=null; this.src='uploads/player_placeholder.jpg';">
                        <div class="absolute top-2 right-2 bg-red-600 text-white font-mono font-bold px-2 py-0.5 rounded text-[8px] uppercase tracking-wider shadow-sm" id="player-status-tag">
                            Bidding
                        </div>
                    </div>
                </div>

                <!-- Bottom Stats Bar: Base Price -->
                <div class="relative z-10 bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 flex justify-between items-center text-xs shadow-inner">
                    <span class="text-slate-500 font-mono font-bold uppercase tracking-wider text-[10px]">Base Price</span>
                    <span class="text-blue-700 font-mono font-black text-lg" id="player-base-price">₹100</span>
                </div>
            </div>

            <!-- Active Bid Center Console Bento (7 Cols) -->
            <div id="bid-card" class="hidden col-span-12 md:col-span-7 flex flex-col gap-6">
                <!-- Current High Bid Box -->
                <div class="pro-card rounded-2xl p-6 border border-slate-200 flex flex-col justify-between relative overflow-hidden min-h-[200px] flex-grow shadow-sm">
                    <div>
                        <span class="text-[9px] font-mono uppercase tracking-widest font-bold text-slate-500">Active High Bid</span>
                        <div class="flex items-baseline mt-1 gap-2">
                            <span class="text-5xl font-montserrat font-black text-red-600 tracking-tight transition duration-200" id="current-bid">
                                ₹0
                            </span>
                        </div>
                    </div>
                    <div class="mt-4 border-t border-slate-100 pt-4">
                        <span class="text-[9px] font-mono uppercase tracking-widest font-bold text-slate-500">Leading Franchise</span>
                        <div class="flex items-center gap-3 mt-1.5">
                            <div id="leading-team-logo-container" class="w-9 h-9 rounded bg-slate-100 border border-slate-200 flex items-center justify-center overflow-hidden p-0.5">
                                <i class="fa-solid fa-crown text-xl text-amber-600" id="leading-team-crown"></i>
                            </div>
                            <div>
                                <h3 class="text-base font-montserrat font-black text-slate-900" id="leading-team">---</h3>
                                <p class="text-[9px] text-amber-700 font-mono uppercase tracking-widest font-bold">High Bidholder</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Live Bids History Timeline -->
                <div class="pro-card rounded-2xl p-5 border border-slate-200 flex flex-col min-h-[160px] shadow-sm">
                    <h3 class="text-xs font-montserrat font-bold text-slate-900 uppercase tracking-wider mb-3 flex items-center gap-2 border-b border-slate-100 pb-2">
                        <i class="fa-solid fa-chart-line text-xs text-amber-600"></i> Bid Flow History
                    </h3>
                    <div class="flex-grow overflow-y-auto pr-1 space-y-2.5 max-h-36" id="bid-history-list">
                        <!-- Polled Bids will append here -->
                    </div>
                </div>
            </div>

        </div>

        <!-- RIGHT SIDE: Franchise Standings Leaderboard (4 Cols) -->
        <aside class="col-span-12 lg:col-span-4 pro-card rounded-2xl p-5 border border-slate-200 flex flex-col shadow-sm">
            <div class="border-b border-slate-100 pb-3 mb-4">
                <h3 class="text-base font-montserrat font-black text-slate-900 flex items-center gap-2 uppercase tracking-tight">
                    <i class="fa-solid fa-wallet text-base text-amber-600"></i> Franchise Purses
                </h3>
                <p class="text-[10px] text-slate-500 font-mono mt-1 uppercase tracking-widest font-bold">Live Budget & Squad Sizes</p>
            </div>

            <!-- Team Leaderboard Grid -->
            <div class="flex-grow space-y-3" id="teams-leaderboard">
                <!-- Polled leaderboard will render here -->
            </div>
        </aside>

        <!-- COMPLETED PLAYERS SECTION -->
        <section class="col-span-12 pro-card rounded-2xl p-6 border border-slate-200 mt-2 relative overflow-hidden shadow-sm">
            <div class="border-b border-slate-100 pb-4 mb-6 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <h3 class="text-base font-montserrat font-black text-slate-900 flex items-center gap-2 uppercase tracking-tight">
                        <i class="fa-solid fa-clipboard-list text-base text-red-600"></i> Player Auctions Status
                    </h3>
                    <p class="text-[10px] text-slate-500 font-mono mt-0.5 font-bold uppercase tracking-wider">Real-time status of all verified player auctions</p>
                </div>
                <!-- Search and Filters Container -->
                <div class="flex flex-col sm:flex-row items-start sm:items-center gap-3.5 w-full sm:w-auto">
                    <!-- Filter Chips -->
                    <div class="flex items-center gap-2 overflow-x-auto pb-1 shrink-0 scrollbar-none" id="public-status-filter-container">
                        <button onclick="setStatusFilter('all')" class="status-chip px-3 py-1.5 rounded-lg border text-[10px] font-montserrat uppercase font-extrabold tracking-wider transition bg-red-600 border-red-600 text-white shadow-sm" data-filter="all">ALL</button>
                        <button onclick="setStatusFilter('Sold')" class="status-chip px-3 py-1.5 rounded-lg border text-[10px] font-montserrat uppercase font-extrabold tracking-wider transition border-slate-200 bg-slate-100 text-slate-700 hover:border-red-600 hover:text-slate-900" data-filter="Sold">SOLD</button>
                        <button onclick="setStatusFilter('Unsold')" class="status-chip px-3 py-1.5 rounded-lg border text-[10px] font-montserrat uppercase font-extrabold tracking-wider transition border-slate-200 bg-slate-100 text-slate-700 hover:border-red-600 hover:text-slate-900" data-filter="Unsold">UNSOLD</button>
                        <button onclick="setStatusFilter('Available')" class="status-chip px-3 py-1.5 rounded-lg border text-[10px] font-montserrat uppercase font-extrabold tracking-wider transition border-slate-200 bg-slate-100 text-slate-700 hover:border-red-600 hover:text-slate-900" data-filter="Available">AVAILABLE</button>
                    </div>
                    <!-- Search Input -->
                    <div class="relative w-full sm:w-56">
                        <i class="fa-solid fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-[10px]"></i>
                        <input type="text" id="player-search-input" oninput="renderCompletedPlayers()" placeholder="Search players, roles, places..."
                               class="w-full bg-slate-50 border border-slate-200 rounded-xl pl-8 pr-3 py-1.5 text-[11px] text-slate-900 font-inter focus:outline-none focus:border-red-600 transition placeholder-slate-400 shadow-inner">
                    </div>
                </div>
            </div>

            <!-- Completed Players Grid (Adaptive cols) -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6" id="completed-players-grid">
                <!-- Dynamically populated completed cards -->
            </div>
            
            <!-- Standby Empty state inside completed table -->
            <div id="completed-empty-box" class="text-center text-xs text-slate-500 py-10 uppercase tracking-widest font-mono hidden">
                No finalized auctions yet. Bids are ongoing!
            </div>
        </section>

    </main>

    <!-- Footer Area -->
    <footer class="w-full bg-white border-t border-slate-200 px-6 py-4 text-center text-xs text-slate-500 font-inter mt-6">
        <p>© 2026 AuctionWala Premier League. Built for premium turf events.</p>
    </footer>

    <script>
        let activePlayerId = null;
        let lastBidAmount = 0;
        let isMuted = false;
        let activeStatusFilter = 'all';
        let allCompletedPlayers = [];

        // Web Audio Synthesizer Engine (Zero external dependencies)
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
                    
                    const osc = this.ctx.createOscillator();
                    const gain = this.ctx.createGain();
                    osc.connect(gain);
                    gain.connect(this.ctx.destination);
                    
                    osc.type = 'sine';
                    osc.frequency.setValueAtTime(880, now);
                    osc.frequency.exponentialRampToValueAtTime(1760, now + 0.12);
                    
                    gain.gain.setValueAtTime(0.3, now);
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
                    
                    const freqs = [523.25, 659.25, 783.99];
                    freqs.forEach((freq, idx) => {
                        const osc = this.ctx.createOscillator();
                        const gain = this.ctx.createGain();
                        osc.connect(gain);
                        gain.connect(this.ctx.destination);
                        
                        osc.type = 'triangle';
                        osc.frequency.setValueAtTime(freq, now + (idx * 0.1));
                        
                        gain.gain.setValueAtTime(0.35, now + (idx * 0.1));
                        gain.gain.exponentialRampToValueAtTime(0.001, now + (idx * 0.1) + 0.4);
                        
                        osc.start(now + (idx * 0.1));
                        osc.stop(now + (idx * 0.1) + 0.4);
                    });
                } catch(e) {}
            },
            playUnsold() {
                if (isMuted) return;
                try {
                    this.init();
                    const now = this.ctx.currentTime;
                    
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
                icon.className = "fa-solid fa-volume-xmark text-xs text-slate-400";
            } else {
                icon.className = "fa-solid fa-volume-high text-xs text-amber-600";
            }
            if (!isMuted) {
                SMCLSoundEngine.init();
            }
        }

        // Initialize audio engine on first click anywhere
        document.addEventListener('click', () => {
            SMCLSoundEngine.init();
        }, { once: true });

        // Fetch live state immediately on load and then every 1.5 seconds
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
                    statusLight.className = "w-2.5 h-2.5 rounded-full bg-red-600 animate-pulse live-dot";
                    statusText.innerText = "Bidding Active";
                    statusText.className = "text-red-600 font-montserrat font-extrabold tracking-wider uppercase text-[10px]";
                } else if (data.status === 'Paused') {
                    statusLight.className = "w-2.5 h-2.5 rounded-full bg-amber-500 animate-pulse";
                    statusText.innerText = "Bidding Paused";
                    statusText.className = "text-amber-700 font-montserrat font-extrabold tracking-wider uppercase text-[10px]";
                } else {
                    statusLight.className = "w-2.5 h-2.5 rounded-full bg-slate-400";
                    statusText.innerText = "Arena Idle";
                    statusText.className = "text-slate-500 font-montserrat font-extrabold tracking-wider uppercase text-[10px]";
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
                            ? 'absolute top-2 right-2 bg-amber-500 text-slate-950 px-2 py-0.5 rounded text-[8px] font-mono font-bold uppercase tracking-wider shadow-sm' 
                            : 'absolute top-2 right-2 bg-red-600 text-white px-2 py-0.5 rounded text-[8px] font-mono font-bold uppercase tracking-wider shadow-sm';

                        // Sync Bidding Box details
                        const bidText = document.getElementById('current-bid');
                        bidText.innerText = "₹" + data.highest_bid;

                        // Micro-animation flash if bid changes
                        if (activePlayerId !== newPlayerId) {
                            activePlayerId = newPlayerId;
                            lastBidAmount = data.highest_bid;
                        } else {
                            if (lastBidAmount !== 0 && data.highest_bid > lastBidAmount) {
                                bidText.classList.add('scale-105', 'text-amber-600');
                                setTimeout(() => {
                                    bidText.classList.remove('scale-105', 'text-amber-600');
                                }, 400);
                                SMCLSoundEngine.playBid();
                            }
                            lastBidAmount = data.highest_bid;
                        }

                        const logoContainer = document.getElementById('leading-team-logo-container');
                        const leadingTeamEl = document.getElementById('leading-team');
                        if (data.leading_team_name) {
                            leadingTeamEl.innerText = data.leading_team_name;
                            leadingTeamEl.className = "text-base font-montserrat font-black text-slate-900 cursor-pointer hover:text-red-600 transition";
                            leadingTeamEl.onclick = () => openTeamDetailsModal(data.leading_team_id);
                            const logoSrc = data.leading_team_logo ? "uploads/" + data.leading_team_logo : "uploads/team_placeholder.jpg";
                            logoContainer.innerHTML = `<img src="${logoSrc}" class="w-full h-full object-contain">`;
                        } else {
                            leadingTeamEl.innerText = "No bids placed yet";
                            leadingTeamEl.className = "text-base font-montserrat font-black text-slate-900";
                            leadingTeamEl.onclick = null;
                            logoContainer.innerHTML = `<i class="fa-solid fa-crown text-xl text-amber-600" id="leading-team-crown"></i>`;
                        }

                        // Sync Bids History Feed
                        const historyList = document.getElementById('bid-history-list');
                        historyList.innerHTML = '';

                        if (data.bid_history && data.bid_history.length > 0) {
                            data.bid_history.forEach(log => {
                                const li = document.createElement('div');
                                li.className = "flex items-center justify-between p-2.5 rounded-xl bg-slate-50 border border-slate-200 text-xs transition";
                                li.innerHTML = `
                                    <div class="flex items-center gap-2">
                                        <span class="text-amber-700 font-mono font-bold">₹${log.bid_amount}</span>
                                        <span class="text-slate-900 font-montserrat font-bold">${log.team_name}</span>
                                    </div>
                                `;
                                historyList.appendChild(li);
                            });
                        } else {
                            historyList.innerHTML = `
                                <div class="text-center text-[10px] text-slate-500 font-mono py-6 uppercase font-bold tracking-wider">
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
                                ? 'bg-amber-50 border-amber-400 shadow-sm' 
                                : 'bg-slate-50 border-slate-200 hover:border-slate-300'
                        }`;
                        div.onclick = () => openTeamDetailsModal(team.id);
                        const logoSrc = team.logo ? "uploads/" + team.logo : "uploads/team_placeholder.jpg";
                        div.innerHTML = `
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-2.5">
                                    <img src="${logoSrc}" class="w-7 h-7 rounded object-contain bg-white p-0.5 border border-slate-200 shadow-sm">
                                    <span class="text-xs sm:text-sm font-montserrat font-bold text-slate-900">${team.team_name}</span>
                                    ${isLeading ? '<span class="text-[8px] uppercase tracking-widest font-mono font-bold text-white bg-red-600 px-1.5 py-0.5 rounded shadow-sm animate-pulse">High Bidder</span>' : ''}
                                </div>
                                <span class="text-xs font-mono font-bold text-amber-700">₹${Number(team.remaining_purse).toLocaleString('en-IN')} left</span>
                            </div>
                            <div class="flex items-center justify-between mt-2.5 text-[10px] text-slate-600 font-inter">
                                <span>Squad: <strong class="text-slate-900 font-mono">${team.current_squad_size}/${team.max_squad_size} players</strong></span>
                                <span>Spent: <strong class="text-blue-700 font-mono">₹${Number(spent).toLocaleString('en-IN')}</strong></span>
                            </div>
                        `;
                        leaderboard.appendChild(div);
                    });
                } else {
                    const emptyMsg = (data && data.error) 
                        ? 'MySQL server is offline.' 
                        : 'No franchise teams registered yet.';
                    leaderboard.innerHTML = `
                        <div class="text-center text-xs text-slate-500 py-8 px-4 font-medium flex flex-col items-center gap-2">
                            <i class="fa-solid fa-database text-amber-600 text-xl opacity-60"></i>
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
                    chip.className = "status-chip px-3 py-1.5 rounded-lg border text-[10px] font-montserrat uppercase font-extrabold tracking-wider transition bg-red-600 border-red-600 text-white shadow-sm";
                } else {
                    chip.className = "status-chip px-3 py-1.5 rounded-lg border text-[10px] font-montserrat uppercase font-extrabold tracking-wider transition border-slate-200 bg-slate-100 text-slate-700 hover:border-red-600 hover:text-slate-900";
                }
            });

            renderCompletedPlayers();
        }

        // Check if player was sold or unsold for sound effects
        async function checkPastPlayerStatus(playerId) {
            try {
                const res = await fetch(`../api/get_player_status.php?player_id=${playerId}`);
                const p = await res.json();
                if (p.auction_status === 'Sold') {
                    SMCLSoundEngine.playSold();
                } else if (p.auction_status === 'Unsold') {
                    SMCLSoundEngine.playUnsold();
                }
            } catch(e) {}
        }

        function renderCompletedPlayers() {
            const container = document.getElementById('completed-players-grid');
            const emptyBox = document.getElementById('completed-empty-box');
            const searchInput = document.getElementById('player-search-input');
            const searchQuery = searchInput ? searchInput.value.toLowerCase().trim() : '';

            container.innerHTML = '';

            let filtered = allCompletedPlayers.filter(p => {
                if (activeStatusFilter === 'all') {
                    return p.auction_status === 'Sold' || p.auction_status === 'Unsold' || p.auction_status === 'Available';
                }
                return p.auction_status === activeStatusFilter;
            });

            if (searchQuery) {
                filtered = filtered.filter(p => {
                    const name = (p.name || '').toLowerCase();
                    const role = (p.role || '').toLowerCase();
                    const place = (p.place || '').toLowerCase();
                    const team = (p.team_name || '').toLowerCase();
                    return name.includes(searchQuery) || role.includes(searchQuery) || place.includes(searchQuery) || team.includes(searchQuery);
                });
            }

            if (filtered.length === 0) {
                emptyBox.classList.remove('hidden');
                return;
            }

            emptyBox.classList.add('hidden');

            filtered.forEach(p => {
                const isSold = (p.auction_status === 'Sold');
                const isUnsold = (p.auction_status === 'Unsold');

                let badgeColor = 'bg-amber-500 text-slate-950';
                if (isSold) badgeColor = 'bg-emerald-600 text-white';
                if (isUnsold) badgeColor = 'bg-red-600 text-white';

                const card = document.createElement('div');
                card.className = "bg-slate-50 border border-slate-200 rounded-xl p-3 flex items-center gap-3 shadow-sm hover:border-slate-300 transition";
                card.innerHTML = `
                    <div class="w-12 h-12 rounded-lg bg-slate-200 border border-slate-300 overflow-hidden shrink-0">
                        <img src="uploads/${p.profile_image || 'player_placeholder.jpg'}" class="w-full h-full object-cover" onerror="this.src='uploads/player_placeholder.jpg'">
                    </div>
                    <div class="flex-grow min-w-0">
                        <div class="flex items-center justify-between gap-1 mb-0.5">
                            <span class="text-xs font-montserrat font-bold text-slate-900 truncate">${p.name}</span>
                            <span class="text-[9px] font-mono font-bold px-1.5 py-0.5 rounded ${badgeColor} uppercase">${p.auction_status}</span>
                        </div>
                        <div class="text-[10px] text-slate-600 font-inter truncate">
                            ${p.role} &bull; ${p.place} ${p.team_name ? '&bull; <strong class="text-slate-900">' + p.team_name + '</strong>' : ''}
                        </div>
                        <div class="text-[11px] font-mono font-bold text-amber-700 mt-0.5">
                            ₹${Number(p.sold_price || p.base_price).toLocaleString('en-IN')}
                        </div>
                    </div>
                `;
                container.appendChild(card);
            });
        }
    </script>
</body>
</html>
