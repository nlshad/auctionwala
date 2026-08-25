<?php
// Resolve base path dynamically for public assets depending on file URL context
$assetBase = (strpos($_SERVER['SCRIPT_NAME'], '/admin/') !== false || strpos($_SERVER['SCRIPT_NAME'], '/manager/') !== false) ? '../public/' : '';
?>
<link rel="icon" type="image/png" href="<?php echo $assetBase; ?>uploads/league_logo.png">
<link rel="manifest" href="<?php echo $assetBase; ?>manifest.json">
<meta name="theme-color" content="#d4a30c">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
<meta name="apple-mobile-web-app-title" content="SMCL 2026">
<link rel="apple-touch-icon" href="<?php echo $assetBase; ?>uploads/league_logo.png">

<script>
if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => {
        const isPublicScope = <?php echo (strpos($_SERVER['SCRIPT_NAME'], '/admin/') === false && strpos($_SERVER['SCRIPT_NAME'], '/manager/') === false) ? 'true' : 'false'; ?>;
        if (isPublicScope) {
            navigator.serviceWorker.register('sw.js')
                .then(reg => console.log('SMCL Service Worker Registered! Scope: ', reg.scope))
                .catch(err => console.error('Service Worker registration failed: ', err));
        }
    });
}
</script>
<!-- Tailwind CSS Play CDN -->
<script src="https://cdn.tailwindcss.com"></script>
<script>
    tailwind.config = {
        theme: {
            extend: {
                colors: {
                    gold: {
                        50: '#fdfbeb',
                        100: '#fbf5c4',
                        200: '#f7e985',
                        300: '#f3d744',
                        400: '#eebf17',
                        500: '#d4a30c',
                        600: '#a77c08',
                        700: '#7e5a07',
                        800: '#533c07',
                        900: '#2b1f03',
                    },
                    cyber: {
                        cyan: '#00f0ff',
                        purple: '#bd00ff',
                        pink: '#ff0055',
                        green: '#39ff14'
                    }
                }
            }
        }
    }
</script>
<link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700;800&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
<style>
    body {
        font-family: 'Inter', sans-serif;
        background: url('<?php echo $assetBase; ?>uploads/app_bg.jpg') no-repeat center center fixed;
        background-size: cover;
        color: #0f172a;
        overflow-x: hidden;
        min-height: 100vh;
    }
    h1, h2, h3, h4, .font-title {
        font-family: 'Outfit', sans-serif;
        color: #0f172a;
    }
    .glass-panel {
        background: linear-gradient(135deg, rgba(255, 255, 255, 0.55) 0%, rgba(255, 255, 255, 0.35) 100%);
        backdrop-filter: blur(25px) saturate(210%);
        -webkit-backdrop-filter: blur(25px) saturate(210%);
        border: 1.5px solid rgba(255, 255, 255, 0.75);
        box-shadow: 0 25px 50px -10px rgba(0, 0, 0, 0.18), inset 0 1.5px 2px rgba(255, 255, 255, 0.9);
    }
    .bento-card {
        background: linear-gradient(135deg, rgba(255, 255, 255, 0.55) 0%, rgba(255, 255, 255, 0.35) 100%);
        backdrop-filter: blur(25px) saturate(210%);
        -webkit-backdrop-filter: blur(25px) saturate(210%);
        border: 1.5px solid rgba(255, 255, 255, 0.75);
        border-radius: 1.25rem;
        box-shadow: 0 25px 50px -10px rgba(0, 0, 0, 0.18), inset 0 1.5px 2px rgba(255, 255, 255, 0.9);
        transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
    }
    .bento-card:hover {
        background: linear-gradient(135deg, rgba(255, 255, 255, 0.65) 0%, rgba(255, 255, 255, 0.45) 100%);
        border-color: rgba(234, 179, 8, 0.8);
        box-shadow: 0 30px 60px -10px rgba(212, 163, 12, 0.3), inset 0 2px 3px rgba(255, 255, 255, 0.95);
        transform: translateY(-3px);
    }
    .glass-card-subtle {
        background: linear-gradient(135deg, rgba(255, 255, 255, 0.40) 0%, rgba(255, 255, 255, 0.25) 100%);
        backdrop-filter: blur(16px) saturate(190%);
        -webkit-backdrop-filter: blur(16px) saturate(190%);
        border: 1px solid rgba(255, 255, 255, 0.65);
        box-shadow: inset 0 1px 1px rgba(255, 255, 255, 0.7);
    }
    .live-dot {
        box-shadow: 0 0 10px #ef4444;
    }
    /* Custom scrollbar */
    ::-webkit-scrollbar {
        width: 6px;
    }
    ::-webkit-scrollbar-track {
        background: #f1f5f9;
    }
    ::-webkit-scrollbar-thumb {
        background: rgba(212, 163, 12, 0.4);
        border-radius: 4px;
    }
    ::-webkit-scrollbar-thumb:hover {
        background: rgba(212, 163, 12, 0.8);
    }
    @keyframes pulse-gold {
        0%, 100% { transform: scale(1); filter: drop-shadow(0 0 0px rgba(212, 163, 12, 0)); }
        50% { transform: scale(1.02); filter: drop-shadow(0 0 15px rgba(212, 163, 12, 0.35)); }
    }
    .pulse-card {
        animation: pulse-gold 3s infinite ease-in-out;
    }

    /* --- IPL BROADCAST-STYLE PLAYER CARD STYLES --- */
    .ipl-card-frame {
        position: relative;
        background: linear-gradient(135deg, #1d4ed8 0%, #2563eb 35%, #dc2626 70%, #991b1b 100%);
        border: 4px solid #ffffff;
        border-radius: 1.5rem;
        box-shadow: 0 25px 50px -12px rgba(29, 78, 216, 0.4), inset 0 0 30px rgba(255, 255, 255, 0.2);
        overflow: hidden;
        color: #ffffff;
    }
    .ipl-card-frame::before {
        content: '';
        position: absolute;
        inset: 0;
        border: 3px solid rgba(255, 255, 255, 0.3);
        border-radius: 1.25rem;
        pointer-events: none;
        z-index: 2;
    }
    .ipl-card-stage-glow {
        position: absolute;
        top: 30%;
        left: 50%;
        transform: translate(-50%, -50%);
        width: 280px;
        height: 280px;
        background: radial-gradient(circle, rgba(255, 255, 255, 0.35) 0%, rgba(255, 255, 255, 0) 70%);
        pointer-events: none;
        z-index: 1;
    }
    .ipl-watermark-text {
        position: absolute;
        top: 32%;
        left: 50%;
        transform: translate(-50%, -50%) rotate(-25deg);
        font-family: 'Outfit', sans-serif;
        font-size: 4.2rem;
        font-weight: 900;
        line-height: 0.85;
        text-transform: uppercase;
        color: rgba(255, 255, 255, 0.28);
        white-space: nowrap;
        pointer-events: none;
        user-select: none;
        z-index: 1;
        letter-spacing: -2px;
        text-shadow: 0 4px 10px rgba(0, 0, 0, 0.15);
    }
    .ipl-card-sold .ipl-watermark-text {
        color: rgba(255, 255, 255, 0.32);
    }
    .ipl-card-live .ipl-watermark-text {
        color: rgba(254, 240, 138, 0.35);
    }
    .ipl-badge-container {
        background: linear-gradient(145deg, rgba(30, 58, 138, 0.92) 0%, rgba(29, 78, 216, 0.95) 100%);
        backdrop-filter: blur(12px);
        border: 2px solid rgba(147, 197, 253, 0.6);
        border-radius: 1rem;
        box-shadow: 0 10px 25px rgba(30, 58, 138, 0.5), inset 0 1px 1px rgba(255, 255, 255, 0.3);
    }
    .ipl-price-container {
        background: linear-gradient(145deg, rgba(153, 27, 27, 0.92) 0%, rgba(220, 38, 38, 0.95) 100%);
        backdrop-filter: blur(12px);
        border: 2px solid rgba(252, 165, 165, 0.6);
        border-radius: 1rem;
        box-shadow: 0 10px 25px rgba(220, 38, 38, 0.5), inset 0 1px 1px rgba(255, 255, 255, 0.3);
    }
</style>
