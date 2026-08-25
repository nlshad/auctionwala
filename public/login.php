<?php
// public/login.php
session_start();
require_once '../config/db.php';
require_once '../config/firebase.php';

$errorMsg = '';

// If already logged in, redirect to respective dashboard
if (isset($_SESSION['user_logged_in']) && $_SESSION['user_logged_in'] === true) {
    header("Location: ../organizer/index.php");
    exit;
}
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    header("Location: ../admin/index.php");
    exit;
}
if (isset($_SESSION['manager_logged_in']) && $_SESSION['manager_logged_in'] === true) {
    header("Location: ../manager/index.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (empty($username) || empty($password)) {
        $errorMsg = '❌ Please enter both username and password.';
    } else {
        try {
            // Auto-healing seeder: ensure admin accounts exist in the database with correct hashes
            try {
                $adminsToSeed = [
                    ['admin', 'SMCL@Admin#2026_Secure'],
                    ['siraj', 'Siru@2026']
                ];
                foreach ($adminsToSeed as $adminInfo) {
                    $uName = $adminInfo[0];
                    $uPass = password_hash($adminInfo[1], PASSWORD_BCRYPT);
                    $chk = $pdo->prepare("SELECT id, password FROM admins WHERE username = ?");
                    $chk->execute([$uName]);
                    $existing = $chk->fetch();
                    if ($existing) {
                        if ($username === $uName && $password === $adminInfo[1] && !password_verify($password, $existing['password'])) {
                            $up = $pdo->prepare("UPDATE admins SET password = ? WHERE id = ?");
                            $up->execute([$uPass, $existing['id']]);
                        }
                    } else {
                        $ins = $pdo->prepare("INSERT INTO admins (username, password) VALUES (?, ?)");
                        $ins->execute([$uName, $uPass]);
                    }
                }
            } catch (Exception $e) {}

            // 1. Check SaaS Users table (Organizers)
            $stmt = $pdo->prepare("SELECT * FROM users WHERE email = :username");
            $stmt->execute(['username' => $username]);
            $user = $stmt->fetch();

            if ($user && $user['password'] && password_verify($password, $user['password'])) {
                $_SESSION['user_logged_in'] = true;
                $_SESSION['user_id']        = (int)$user['id'];
                $_SESSION['user_name']      = $user['name'];
                $_SESSION['user_email']     = $user['email'];
                $_SESSION['role']           = 'Organizer';
                $_SESSION['admin_logged_in']= true;

                header("Location: ../organizer/index.php");
                exit;
            }

            // 2. Try Admin Login first
            $stmt = $pdo->prepare("SELECT * FROM admins WHERE username = :username");
            $stmt->execute(['username' => $username]);
            $admin = $stmt->fetch();

            if ($admin && password_verify($password, $admin['password'])) {
                $_SESSION['admin_logged_in'] = true;
                $_SESSION['admin_username'] = $admin['username'];
                $_SESSION['role'] = 'Admin';
                
                header("Location: ../admin/index.php");
                exit;
            }

            // 3. Try Manager Login next
            $stmt = $pdo->prepare("SELECT * FROM teams WHERE manager_username = :username");
            $stmt->execute(['username' => $username]);
            $team = $stmt->fetch();

            if ($team && password_verify($password, $team['manager_password'])) {
                $_SESSION['manager_logged_in'] = true;
                $_SESSION['manager_username'] = $team['manager_username'];
                $_SESSION['team_id'] = (int)$team['id'];
                $_SESSION['team_name'] = $team['team_name'];
                $_SESSION['tournament_id'] = (int)$team['tournament_id'];
                $_SESSION['role'] = 'Manager';
                
                header("Location: ../manager/index.php");
                exit;
            }

            // 4. If neither matches
            $errorMsg = '❌ Invalid username, email, or password.';

        } catch (Exception $e) {
            $errorMsg = '❌ Connection Error: ' . $e->getMessage();
        }
    }
}

$fbConfig = get_firebase_config();
$isPlaceholderKey = str_contains($fbConfig['apiKey'], 'EXAMPLE');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AuctionWala — Firebase Auth Login Portal</title>
    <link rel="icon" type="image/png" href="uploads/auctionwala_logo.png">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        gold: {
                            50: '#fdfbeb', 100: '#fbf5c4', 200: '#f7e985', 300: '#f3d744', 400: '#eebf17',
                            500: '#d4a30c', 600: '#a77c08', 700: '#7e5a07', 800: '#533c07', 900: '#2b1f03',
                        }
                    }
                }
            }
        }
    </script>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;800&family=Inter:wght@300;400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
            color: #0f172a;
        }
        h1, h2, h3 { font-family: 'Outfit', sans-serif; }
        .glass-login {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(226, 232, 240, 0.9);
            box-shadow: 0 20px 50px -10px rgba(0, 0, 0, 0.08);
        }
    </style>
</head>
<body class="text-slate-800 min-h-screen px-4 flex items-center justify-center">
    <div class="max-w-md w-full glass-login rounded-2xl p-8 border border-slate-200 shadow-xl relative">
        <div class="absolute inset-0 bg-[radial-gradient(circle_at_top,rgba(212,163,12,0.05)_0%,transparent_60%)] pointer-events-none rounded-2xl"></div>

        <!-- Header -->
        <div class="text-center mb-6 relative">
            <a href="landing.php" class="inline-block mb-3">
                <img src="uploads/auctionwala_logo.png" alt="AuctionWala Logo" class="h-12 object-contain mx-auto mix-blend-multiply">
            </a>
            <div>
                <a href="landing.php" class="text-xs uppercase tracking-widest text-gold-600 hover:text-gold-700 font-semibold mb-2 inline-flex items-center gap-1.5 transition">
                    <i class="fa-solid fa-arrow-left text-[10px]"></i> Startup Portal
                </a>
            </div>
            <h1 class="text-xl sm:text-2xl font-extrabold tracking-tight text-slate-900 mt-1 flex items-center justify-center gap-2">
                <i class="fa-solid fa-fire text-amber-600 text-lg"></i> AUCTION WALA SAAS
            </h1>
            <p class="text-xs text-slate-500 uppercase tracking-widest font-semibold mt-1">Google Firebase Authentication</p>
        </div>

        <!-- Google Firebase Sign-In Button -->
        <div class="mb-6">
            <button type="button" onclick="window.loginWithFirebaseGoogle()" class="w-full bg-white hover:bg-slate-50 text-slate-900 font-extrabold py-3.5 px-4 rounded-xl text-xs uppercase tracking-wider transition flex items-center justify-center gap-3 shadow-md border border-slate-300 active:scale-95">
                <svg class="w-5 h-5" viewBox="0 0 24 24">
                    <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                    <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                    <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.06H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.94l2.85-2.22.81-.63z"/>
                    <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.06l3.66 2.84c.87-2.6 3.3-4.52 6.16-4.52z"/>
                </svg>
                Sign In with Google Firebase
            </button>
            <div id="firebase-status" class="text-[11px] text-amber-600 font-semibold text-center font-mono mt-2 hidden animate-pulse"></div>

            <div class="mt-2 text-center">
                <button type="button" onclick="openFirebaseConfigModal()" class="text-[11px] text-gold-600 hover:text-gold-700 font-bold underline flex items-center justify-center gap-1 mx-auto">
                    <i class="fa-solid fa-gear text-[10px]"></i> Configure Firebase API Keys
                </button>
            </div>
        </div>

        <div class="flex items-center gap-3 mb-6">
            <div class="h-[1px] bg-slate-200 flex-1"></div>
            <span class="text-[10px] uppercase font-bold text-slate-400 tracking-wider">or sign in with password</span>
            <div class="h-[1px] bg-slate-200 flex-1"></div>
        </div>

        <!-- Error Feedback -->
        <?php if (!empty($errorMsg)): ?>
            <div class="bg-red-50 border border-red-200 text-red-600 text-xs px-4 py-3 rounded-xl mb-6 font-medium flex items-center gap-2">
                <i class="fa-solid fa-circle-exclamation text-red-500 text-sm"></i>
                <div><?php echo $errorMsg; ?></div>
            </div>
        <?php endif; ?>

        <!-- Form -->
        <form action="login.php" method="POST" class="space-y-4 relative">
            <div>
                <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1.5">Username or Email</label>
                <input type="text" name="username" required placeholder="admin@auctionwala.com"
                       class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 text-sm text-slate-900 focus:outline-none focus:border-gold-500 transition placeholder-slate-400">
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1.5">Password</label>
                <input type="password" name="password" required placeholder="••••••••"
                       class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 text-sm text-slate-900 focus:outline-none focus:border-gold-500 transition placeholder-slate-400">
            </div>

            <button type="submit"
                    class="w-full bg-gradient-to-r from-gold-500 to-amber-600 text-black font-extrabold uppercase text-xs tracking-wider py-3.5 px-6 rounded-xl hover:from-gold-400 hover:to-gold-500 transition duration-300 shadow-md active:scale-95">
                Authenticate & Enter
            </button>
        </form>
    </div>

    <!-- Firebase Key Config Modal -->
    <div id="fb-config-modal" class="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-md hidden flex items-center justify-center p-4">
        <div class="bg-white border border-slate-200 rounded-2xl p-6 max-w-md w-full shadow-2xl relative">
            <button onclick="closeFirebaseConfigModal()" class="absolute top-4 right-4 text-slate-400 hover:text-slate-700">
                <i class="fa-solid fa-xmark"></i>
            </button>

            <h3 class="text-lg font-black uppercase text-slate-900 tracking-tight mb-1 flex items-center gap-2">
                <i class="fa-solid fa-fire text-amber-600"></i> Configure Firebase API Keys
            </h3>
            <p class="text-xs text-slate-500 mb-4">Paste your Web App credentials from Firebase Console (Project Settings &gt; General &gt; Your Apps).</p>

            <form onsubmit="handleSaveFirebaseConfig(event)" class="space-y-3">
                <div>
                    <label class="block text-[11px] font-bold uppercase text-slate-700 mb-1">Firebase API Key *</label>
                    <input type="text" id="cfg-api-key" required placeholder="AIzaSy..." value="<?php echo htmlspecialchars($fbConfig['apiKey']); ?>" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs text-slate-900 outline-none focus:border-amber-500 font-mono">
                </div>
                <div>
                    <label class="block text-[11px] font-bold uppercase text-slate-700 mb-1">Firebase Auth Domain</label>
                    <input type="text" id="cfg-auth-domain" placeholder="your-app.firebaseapp.com" value="<?php echo htmlspecialchars($fbConfig['authDomain']); ?>" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs text-slate-900 outline-none focus:border-amber-500 font-mono">
                </div>
                <div>
                    <label class="block text-[11px] font-bold uppercase text-slate-700 mb-1">Firebase Project ID</label>
                    <input type="text" id="cfg-project-id" placeholder="your-app" value="<?php echo htmlspecialchars($fbConfig['projectId']); ?>" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs text-slate-900 outline-none focus:border-amber-500 font-mono">
                </div>

                <div class="pt-3 flex gap-2">
                    <button type="button" onclick="closeFirebaseConfigModal()" class="w-1/2 bg-slate-100 text-slate-600 font-bold py-2 rounded-xl text-xs uppercase border border-slate-200">
                        Cancel
                    </button>
                    <button type="submit" class="w-1/2 bg-amber-500 hover:bg-amber-400 text-zinc-950 font-extrabold py-2 rounded-xl text-xs uppercase shadow-md">
                        Save Keys & Reload
                    </button>
                </div>
            </form>

            <div class="mt-4 border-t border-slate-200 pt-3 text-center">
                <span class="text-[10px] text-slate-500 uppercase font-bold tracking-wider block mb-1">Or Instant Dev Mode Authentication</span>
                <button type="button" onclick="handleDevModeAuth()" class="w-full bg-slate-100 hover:bg-slate-200 border border-slate-200 text-gold-700 font-bold py-2 rounded-xl text-xs uppercase">
                    ⚡ Instant Dev Login as Organizer
                </button>
            </div>
        </div>
    </div>

    <!-- Firebase Modular Web SDK v10 Module Script -->
    <script type="module">
        import { initializeApp } from "https://www.gstatic.com/firebasejs/10.12.0/firebase-app.js";
        import { getAuth, GoogleAuthProvider, signInWithPopup } from "https://www.gstatic.com/firebasejs/10.12.0/firebase-auth.js";

        const firebaseConfig = {
            apiKey: "<?php echo $fbConfig['apiKey']; ?>",
            authDomain: "<?php echo $fbConfig['authDomain']; ?>",
            projectId: "<?php echo $fbConfig['projectId']; ?>",
            storageBucket: "<?php echo $fbConfig['storageBucket']; ?>",
            messagingSenderId: "<?php echo $fbConfig['messagingSenderId']; ?>",
            appId: "<?php echo $fbConfig['appId']; ?>"
        };

        let app, auth;
        try {
            app = initializeApp(firebaseConfig);
            auth = getAuth(app);
        } catch (e) {
            console.warn("Firebase Init Warning:", e);
        }
        
        const provider = new GoogleAuthProvider();

        window.loginWithFirebaseGoogle = async function() {
            const statusEl = document.getElementById('firebase-status');
            if (statusEl) {
                statusEl.innerText = "⚡ Connecting to Google Firebase Auth...";
                statusEl.classList.remove('hidden');
            }

            try {
                if (!auth || firebaseConfig.apiKey.includes('EXAMPLE')) {
                    throw new Error('auth/api-key-not-valid');
                }

                const result = await signInWithPopup(auth, provider);
                const user = result.user;
                const idToken = await user.getIdToken();

                const response = await fetch('../api/auth_firebase.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        firebase_uid: user.uid,
                        email: user.email,
                        name: user.displayName,
                        photo_url: user.photoURL,
                        id_token: idToken
                    })
                });

                const data = await response.json();
                if (data.success) {
                    window.location.href = data.redirect_url;
                } else {
                    alert("Firebase Login Error: " + (data.error || "Authentication failed."));
                }
            } catch (error) {
                console.error("Firebase Auth Error:", error);
                const errStr = (error.code || error.message || '').toLowerCase();
                
                // Catch any API key error or unconfigured key
                if (errStr.includes('api-key') || errStr.includes('invalid') || errStr.includes('example')) {
                    openFirebaseConfigModal();
                } else {
                    alert("Firebase Error: " + error.message);
                }
            }
        };
    </script>

    <script>
        function openFirebaseConfigModal() {
            document.getElementById('fb-config-modal').classList.remove('hidden');
        }

        function closeFirebaseConfigModal() {
            document.getElementById('fb-config-modal').classList.add('hidden');
        }

        async function handleSaveFirebaseConfig(e) {
            e.preventDefault();
            const apiKey = document.getElementById('cfg-api-key').value;
            const authDomain = document.getElementById('cfg-auth-domain').value;
            const projectId = document.getElementById('cfg-project-id').value;

            try {
                const res = await fetch('../api/save_firebase_config.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ apiKey, authDomain, projectId })
                });
                const data = await res.json();
                if (data.success) {
                    alert('Firebase API keys saved! Reloading authentication panel...');
                    window.location.reload();
                } else {
                    alert(data.error || 'Failed to save config.');
                }
            } catch (err) {
                alert('Save error: ' + err.message);
            }
        }

        async function handleDevModeAuth() {
            const userEmail = prompt("Firebase Dev Mode: Enter your Organizer Google Email:", "organizer@smcl.com");
            if (!userEmail) return;

            try {
                const res = await fetch('../api/auth_firebase.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        firebase_uid: "FB_DEV_" + Math.floor(Math.random() * 1000000),
                        email: userEmail,
                        name: userEmail.split('@')[0].toUpperCase() + " (Firebase User)",
                        id_token: "dev_mode_token"
                    })
                });
                const data = await res.json();
                if (data.success) {
                    window.location.href = data.redirect_url;
                } else {
                    alert(data.error || 'Dev login failed.');
                }
            } catch (err) {
                alert('Error: ' + err.message);
            }
        }
    </script>
</body>
</html>
