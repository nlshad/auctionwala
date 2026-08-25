<?php
// public/register.php
session_start();
require_once '../config/db.php';

$tournamentId = get_active_tournament_id($pdo);

// Fetch tournament info
$stmt = $pdo->prepare("SELECT * FROM tournaments WHERE id = ?");
$stmt->execute([$tournamentId]);
$tournament = $stmt->fetch();

// Fetch registration status for tournament
$stmt = $pdo->prepare("SELECT registration_enabled FROM auction_state WHERE tournament_id = ?");
$stmt->execute([$tournamentId]);
$regStatus = $stmt->fetch();
$registrationEnabled = $tournament ? (bool)$tournament['registration_enabled'] : ($regStatus ? (bool)$regStatus['registration_enabled'] : true);

$successMsg = '';
$errorMsg = '';
$registeredPlayer = null;
$duplicateFound = false;
$duplicatePlayerDetails = null;

// Handle Registration Form Post
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (!$registrationEnabled) {
        $errorMsg = '❌ Registration is currently closed by the League Organizer.';
    } else {
        $name = trim($_POST['name'] ?? '');
        $mobile = trim($_POST['mobile'] ?? '');
        $place = trim($_POST['place'] ?? '');
        $role = trim($_POST['role'] ?? '');

    // Form Validations
    if (empty($name) || empty($mobile) || empty($place) || empty($role)) {
        $errorMsg = '❌ All fields are required.';
    } elseif (!preg_match('/^[0-9]{10}$/', $mobile)) {
        $errorMsg = '❌ Mobile number must be exactly 10 digits.';
    } else {
        try {
            // Check if player with same mobile number already exists in this tournament
            $checkStmt = $pdo->prepare("SELECT name, place, role, payment_status, payment_utr FROM players WHERE mobile = ? AND tournament_id = ? LIMIT 1");
            $checkStmt->execute([$mobile, $tournamentId]);
            $existingPlayer = $checkStmt->fetch(PDO::FETCH_ASSOC);
            if ($existingPlayer) {
                $duplicateFound = true;
                $duplicatePlayerDetails = $existingPlayer;
                $errorMsg = '❌ A player is already registered with this mobile number in this tournament.';
            } else {
                // Auto-generate unique registration reference (UTR)
                $utr = 'REG-' . strtoupper(bin2hex(random_bytes(6)));
                
                // File Upload / Cropping Handling
                $croppedData = $_POST['cropped_image_data'] ?? '';
                
                if (!empty($croppedData)) {
                    if (preg_match('/^data:image\/(\w+);base64,/', $croppedData, $typeMatches)) {
                        $imageType = strtolower($typeMatches[1]);
                        $fileExt = ($imageType === 'png') ? 'png' : 'jpg';
                        
                        $croppedData = substr($croppedData, strpos($croppedData, ',') + 1);
                        $croppedData = base64_decode($croppedData);
                        
                        if ($croppedData === false) {
                            $errorMsg = '❌ Invalid cropped image data.';
                        } else {
                            $newFileName = uniqid('player_', true) . '.' . $fileExt;
                            $uploadDir = __DIR__ . '/uploads/';
                            
                            if (!is_dir($uploadDir)) {
                                mkdir($uploadDir, 0777, true);
                            }
                            
                            $destPath = $uploadDir . $newFileName;
                            
                            if (file_put_contents($destPath, $croppedData)) {
                                // Save to Database under active tournament
                                $stmt = $pdo->prepare("INSERT INTO players (tournament_id, name, mobile, place, role, profile_image, payment_utr, payment_status, base_price) VALUES (?, ?, ?, ?, ?, ?, ?, 'Pending', 100)");
                                $stmt->execute([$tournamentId, $name, $mobile, $place, $role, $newFileName, $utr]);
                                
                                $successMsg = "🎉 Registration submitted successfully! Your profile photo is queued for Organizer approval.";
                                $registeredPlayer = [
                                    'name' => $name,
                                    'mobile' => $mobile,
                                    'place' => $place,
                                    'role' => $role,
                                    'profile_image' => $newFileName,
                                    'utr' => $utr
                                ];
                                $name = $mobile = $place = $role = '';
                            } else {
                                $errorMsg = '❌ Saving cropped image failed. Please try again.';
                            }
                        }
                    } else {
                        $errorMsg = '❌ Invalid image format.';
                    }
                } else {
                    if (!isset($_FILES['profile_image']) || $_FILES['profile_image']['error'] !== UPLOAD_ERR_OK) {
                        $errorMsg = '❌ Please select a profile image to upload.';
                    } else {
                        $fileTmpPath = $_FILES['profile_image']['tmp_name'];
                        $fileName = $_FILES['profile_image']['name'];
                        $fileSize = $_FILES['profile_image']['size'];
                        $fileType = $_FILES['profile_image']['type'];
                        
                        $maxSize = 2 * 1024 * 1024;
                        if ($fileSize > $maxSize) {
                            $errorMsg = '❌ Image size too large. Maximum limit is 2MB.';
                        } else {
                            $allowedMimes = ['image/jpeg', 'image/png', 'image/jpg'];
                            $actualMime = function_exists('mime_content_type') ? mime_content_type($fileTmpPath) : $fileType;
                            
                            if (!in_array($actualMime, $allowedMimes)) {
                                $errorMsg = '❌ Invalid file type. Only JPG, JPEG, and PNG images are allowed.';
                            } else {
                                $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
                                if (!in_array($fileExt, ['jpg', 'jpeg', 'png'])) {
                                    $fileExt = ($actualMime === 'image/png') ? 'png' : 'jpg';
                                }
                                
                                $newFileName = uniqid('player_', true) . '.' . $fileExt;
                                $uploadDir = __DIR__ . '/uploads/';
                                
                                if (!is_dir($uploadDir)) {
                                    mkdir($uploadDir, 0777, true);
                                }
                                
                                $destPath = $uploadDir . $newFileName;
                                
                                if (move_uploaded_file($fileTmpPath, $destPath)) {
                                    $stmt = $pdo->prepare("INSERT INTO players (tournament_id, name, mobile, place, role, profile_image, payment_utr, payment_status, base_price) VALUES (?, ?, ?, ?, ?, ?, ?, 'Pending', 100)");
                                    $stmt->execute([$tournamentId, $name, $mobile, $place, $role, $newFileName, $utr]);
                                    
                                    $successMsg = "🎉 Registration submitted successfully! Your profile is queued for Organizer approval.";
                                    $registeredPlayer = [
                                        'name' => $name,
                                        'mobile' => $mobile,
                                        'place' => $place,
                                        'role' => $role,
                                        'profile_image' => $newFileName,
                                        'utr' => $utr
                                    ];
                                    $name = $mobile = $place = $role = '';
                                } else {
                                    $errorMsg = '❌ Image upload failed. Please try again.';
                                }
                            }
                        }
                    }
                }
            }
        } catch (Exception $e) {
            $errorMsg = '❌ Database Error: ' . $e->getMessage();
        }
    }
}
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Player Registration — SMCL 2026</title>
    <link rel="icon" type="image/png" href="uploads/league_logo.png">
    <!-- Tailwind CSS CDN -->
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
                        }
                    }
                }
            }
        }
    </script>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;800&family=Inter:wght@300;400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
    <!-- Cropper.js CDN -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.1/cropper.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.1/cropper.min.js"></script>
    <!-- html2canvas CDN for image download -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
            color: #0f172a;
        }
        h1, h2, h3, h4 {
            font-family: 'Outfit', sans-serif;
        }
        .glass-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(16px);
            border: 1px solid rgba(226, 232, 240, 0.9);
            box-shadow: 0 12px 40px 0 rgba(0, 0, 0, 0.06);
        }
    </style>
</head>
<body class="text-slate-800 min-h-screen py-10 px-4 flex items-center justify-center">
    <div class="max-w-4xl w-full glass-card rounded-2xl border border-slate-200 overflow-hidden shadow-xl">
        <!-- Top Banner -->
        <div class="bg-gradient-to-r from-slate-900 via-slate-800 to-slate-900 p-6 border-b border-slate-700 text-center relative">
            <div class="absolute inset-0 bg-[radial-gradient(circle_at_center,rgba(218,165,32,0.15)_0%,transparent_70%)] pointer-events-none"></div>
            <a href="landing.php" class="inline-block mb-3">
                <img src="uploads/auctionwala_logo.png" alt="AuctionWala Logo" class="h-12 sm:h-14 object-contain mx-auto mix-blend-multiply">
            </a>
            <div>
                <a href="landing.php" class="text-xs uppercase tracking-widest text-gold-400 hover:text-gold-300 font-semibold mb-2 inline-flex items-center gap-1.5 transition">
                    <i class="fa-solid fa-arrow-left text-[10px]"></i> Back to Startup Portal
                </a>
            </div>
            <h1 class="text-2xl sm:text-3xl font-extrabold text-white mt-1">
                <?php echo htmlspecialchars($tName); ?>
            </h1>
            <p class="text-slate-300 text-xs mt-1 uppercase tracking-widest font-semibold">AuctionWala — Official Player Registration Pool</p>
        </div>

        <!-- Feedback Messages -->
        <?php if (!empty($successMsg)): ?>
            <div class="mx-6 mt-6 bg-gold-950/20 border border-gold-500/40 text-gold-300 px-5 py-4 rounded-xl text-sm flex items-center gap-3">
                <i class="fa-solid fa-circle-check text-emerald-400 text-lg"></i>
                <div><?php echo $successMsg; ?></div>
            </div>
        <?php endif; ?>

        <?php if (!empty($errorMsg)): ?>
            <div class="mx-6 mt-6 bg-red-950/20 border border-red-500/40 text-red-300 px-5 py-4 rounded-xl text-sm flex items-center gap-3">
                <i class="fa-solid fa-circle-exclamation text-red-400 text-lg"></i>
                <div><?php echo $errorMsg; ?></div>
            </div>
        <?php endif; ?>

        <?php if (!$registrationEnabled): ?>
            <!-- Disabled Notice -->
            <div class="p-10 text-center space-y-5">
                <div class="w-16 h-16 bg-red-950/20 border border-red-500/30 text-red-400 rounded-full flex items-center justify-center mx-auto text-2xl">
                    <i class="fa-solid fa-ban font-bold"></i>
                </div>
                <div class="space-y-2">
                    <h3 class="text-xl font-bold text-white tracking-tight">Public Registrations Closed</h3>
                    <p class="text-xs text-gray-400 leading-relaxed max-w-md mx-auto">
                        Player registrations for the SMCL 2026 Season are currently closed. Please contact the league administrators or your franchise managers for more information.
                    </p>
                </div>
                <div class="pt-2">
                    <a href="index.php" class="bg-white/5 border border-white/10 hover:border-white/20 text-[10px] font-bold uppercase tracking-wider px-6 py-3 rounded-xl text-gray-300 hover:text-white transition inline-block">
                        <i class="fa-solid fa-arrow-left mr-1.5 text-[10px]"></i> Back to Live Auction
                    </a>
                </div>
            </div>
        <?php elseif (!empty($registeredPlayer)): ?>
            <!-- REGISTRATION SUCCESS CARD -->
            <div class="p-6 md:p-8 max-w-xl mx-auto space-y-6 text-center">
                <!-- Gorgeous Sports Card -->
                <!-- Gorgeous IPL Broadcast Sports Registration Card -->
                <div class="ipl-card-frame ipl-card-live max-w-[340px] mx-auto p-6 mb-6 shadow-2xl relative text-left" id="registration-card" data-utr="<?php echo htmlspecialchars($registeredPlayer['utr']); ?>">
                    <!-- Diagonal Watermark Background -->
                    <div class="ipl-watermark-text">REGISTERED</div>

                    <!-- Top Header: Flag/Location & Verification Tag -->
                    <div class="flex items-center justify-between relative z-10 mb-3">
                        <div class="flex items-center gap-1.5 bg-black/50 border border-white/10 px-2.5 py-1 rounded-lg">
                            <span class="text-xs">🇮🇳</span>
                            <span class="text-[9px] font-black text-slate-200 uppercase tracking-wider font-mono">
                                <?php echo htmlspecialchars($registeredPlayer['place']); ?>
                            </span>
                        </div>
                        <span class="bg-amber-500/20 border border-amber-500/40 text-amber-300 text-[8px] font-black px-2.5 py-1 rounded-lg uppercase tracking-wider">
                            Pending Verification
                        </span>
                    </div>

                    <!-- Stacked Player Name -->
                    <?php 
                        $pNames = explode(' ', trim($registeredPlayer['name']));
                        $pFirstName = $pNames[0] ?? $registeredPlayer['name'];
                        $pLastName = count($pNames) > 1 ? implode(' ', array_slice($pNames, 1)) : '';
                    ?>
                    <div class="relative z-10 mb-2">
                        <div class="text-[10px] font-extrabold text-gold-400 uppercase tracking-widest leading-tight"><?php echo htmlspecialchars($pFirstName); ?></div>
                        <h3 class="text-2xl font-black text-white uppercase tracking-tight leading-none"><?php echo htmlspecialchars($pLastName ? $pLastName : $pFirstName); ?></h3>
                    </div>

                    <!-- Center Stage: Cutout Player Image -->
                    <div class="relative z-10 my-4 flex justify-center">
                        <div class="w-36 h-40 rounded-2xl overflow-hidden border-2 border-gold-500/40 bg-slate-900/80 shadow-2xl relative">
                            <img src="uploads/<?php echo htmlspecialchars($registeredPlayer['profile_image']); ?>" alt="Candidate" class="w-full h-full object-cover">
                        </div>
                    </div>

                    <!-- Bottom Details Box -->
                    <div class="relative z-10 grid grid-cols-2 gap-2.5 pt-2">
                        <div class="ipl-badge-container p-2.5 flex flex-col justify-center">
                            <span class="text-[7.5px] uppercase font-black text-blue-300 tracking-wider mb-0.5">Player Role</span>
                            <span class="text-xs font-black text-white uppercase truncate"><?php echo htmlspecialchars($registeredPlayer['role']); ?></span>
                        </div>
                        <div class="ipl-price-container p-2.5 flex flex-col justify-center text-center">
                            <span class="text-[7.5px] uppercase font-black text-red-200 tracking-wider mb-0.5">Base Price</span>
                            <span class="text-xs font-black text-white font-mono">₹<?php echo number_format($registeredPlayer['base_price'] ?? 100); ?></span>
                        </div>
                        <div class="col-span-2 bg-slate-900/80 border border-slate-700/60 rounded-xl p-2 text-center">
                            <span class="text-[7.5px] text-slate-400 font-bold uppercase tracking-wider block mb-0.5">Registration UTR Reference</span>
                            <span class="text-xs font-black text-amber-400 font-mono tracking-wider uppercase"><?php echo htmlspecialchars($registeredPlayer['utr']); ?></span>
                        </div>
                    </div>
                </div>

                <!-- Download Card Buttons -->
                <div class="pt-2 flex flex-col sm:flex-row gap-3 justify-center max-w-sm mx-auto">
                    <button id="download-card-btn" onclick="downloadRegistrationCard()"
                            class="flex-1 bg-gradient-to-r from-gold-500 to-amber-600 text-black font-extrabold uppercase text-xs tracking-wider py-3.5 px-6 rounded-xl hover:from-gold-400 hover:to-gold-500 transition duration-300 shadow-lg shadow-gold-500/20 active:scale-95 flex items-center justify-center gap-2">
                        <i class="fa-solid fa-download"></i> Download Card Image
                    </button>
                    <a href="register.php"
                       class="flex-1 bg-white/5 border border-white/10 hover:border-white/20 text-xs font-bold uppercase tracking-wider py-3.5 px-6 rounded-xl text-gray-300 hover:text-white transition flex items-center justify-center gap-2">
                        <i class="fa-solid fa-user-plus"></i> Register Another
                    </a>
                </div>

                <!-- Important Notice -->
                <div class="max-w-sm mx-auto bg-gold-950/10 border border-gold-500/25 text-left p-5 rounded-xl text-xs space-y-3 mt-4">
                    <div class="font-extrabold flex items-center gap-1.5 text-gold-400 uppercase tracking-wider text-[10px] border-b border-gold-500/10 pb-2">
                        <i class="fa-solid fa-circle-info"></i> Important Instructions
                    </div>
                    <div class="space-y-3.5 text-gray-400 text-[11px] leading-relaxed">
                        <div>
                            <span class="font-bold text-gold-400 uppercase tracking-wide block text-[9.5px]">Save your card</span>
                            Please download and save this card to your device.
                        </div>
                        <div>
                            <span class="font-bold text-gold-400 uppercase tracking-wide block text-[9.5px]">Make your payment</span>
                            Payment is collected manually. Your account will be verified once we receive and confirm your payment.
                        </div>
                        <div>
                            <span class="font-bold text-gold-400 uppercase tracking-wide block text-[9.5px]">Join the auction</span>
                            Only verified candidates will be listed on the auction dashboard.
                        </div>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <!-- Form Container -->
            <form action="register.php" method="POST" enctype="multipart/form-data" class="p-6 md:p-8 max-w-xl mx-auto space-y-6">
            
            <div class="space-y-6">
                <h3 class="text-lg font-bold text-gold-400 border-b border-white/5 pb-2 flex items-center gap-2">
                    <i class="fa-solid fa-baseball-bat-ball text-gold-400 text-lg"></i> Player Registration Details
                </h3>

                <!-- Name Input -->
                <div>
                    <label class="block text-xs font-semibold text-gray-400 uppercase tracking-wider mb-2">Full Name</label>
                    <input type="text" name="name" required placeholder="e.g. Sanju Samson"
                           class="w-full bg-black/40 border border-white/10 rounded-xl px-4 py-3 text-sm text-white focus:outline-none focus:border-gold-500 focus:ring-1 focus:ring-gold-500/30 transition placeholder-gray-600"
                           value="<?php echo htmlspecialchars($name ?? ''); ?>">
                </div>

                <!-- Mobile & Place -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-gray-400 uppercase tracking-wider mb-2">Mobile Number</label>
                        <input type="tel" name="mobile" required placeholder="10-digit number" pattern="[0-9]{10}"
                               class="w-full bg-black/40 border border-white/10 rounded-xl px-4 py-3 text-sm text-white focus:outline-none focus:border-gold-500 focus:ring-1 focus:ring-gold-500/30 transition placeholder-gray-600"
                               value="<?php echo htmlspecialchars($mobile ?? ''); ?>">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-400 uppercase tracking-wider mb-2">Place / Hometown</label>
                        <input type="text" name="place" required placeholder="e.g. Palamukk"
                               class="w-full bg-black/40 border border-white/10 rounded-xl px-4 py-3 text-sm text-white focus:outline-none focus:border-gold-500 focus:ring-1 focus:ring-gold-500/30 transition placeholder-gray-600"
                               value="<?php echo htmlspecialchars($place ?? ''); ?>">
                    </div>
                </div>

                <!-- Playing Role -->
                <div>
                    <label class="block text-xs font-semibold text-gray-400 uppercase tracking-wider mb-2">Playing Role</label>
                    <select name="role" required 
                            class="w-full bg-black/40 border border-white/10 rounded-xl px-4 py-3 text-sm text-white focus:outline-none focus:border-gold-500 focus:ring-1 focus:ring-gold-500/30 transition">
                        <option value="" disabled selected class="bg-zinc-950">Select Playing Role</option>
                        <option value="Batsman" <?php echo ($role ?? '') === 'Batsman' ? 'selected' : ''; ?> class="bg-zinc-950">Batsman</option>
                        <option value="Bowler" <?php echo ($role ?? '') === 'Bowler' ? 'selected' : ''; ?> class="bg-zinc-950">Bowler</option>
                        <option value="All-Rounder" <?php echo ($role ?? '') === 'All-Rounder' ? 'selected' : ''; ?> class="bg-zinc-950">All-Rounder</option>
                        <option value="Wicket-Keeper" <?php echo ($role ?? '') === 'Wicket-Keeper' ? 'selected' : ''; ?> class="bg-zinc-950">Wicket-Keeper</option>
                    </select>
                </div>

                <!-- Profile Photo Upload -->
                <div>
                    <label class="block text-xs font-semibold text-gray-400 uppercase tracking-wider mb-2">Profile Image (Max 2MB, JPG/PNG)</label>
                    <div class="relative w-full bg-black/40 border border-dashed border-white/10 rounded-xl p-4 text-center hover:border-gold-500/40 transition">
                        <input type="file" name="profile_image" id="profile_image" required accept="image/*"
                               class="absolute inset-0 opacity-0 cursor-pointer w-full h-full">
                        <input type="hidden" name="cropped_image_data" id="cropped_image_data">
                        <div class="space-y-1" id="upload-prompt">
                            <i class="fa-solid fa-camera text-gold-400 text-2xl block mx-auto"></i>
                            <p class="text-xs text-gray-300 font-semibold">Click to upload or drag & drop</p>
                            <p class="text-[10px] text-gray-500">MIME validation will enforce real image files only.</p>
                        </div>
                        <div class="hidden space-y-1 text-gold-400 font-medium text-xs animate-pulse" id="upload-feedback">
                            <i class="fa-solid fa-star text-gold-400 text-xl block mx-auto"></i>
                            <p id="file-name-display"></p>
                        </div>
                    </div>
                </div>

                <!-- Submit Button -->
                <button type="submit"
                        class="w-full bg-gradient-to-r from-gold-500 to-amber-600 text-black font-extrabold uppercase text-xs tracking-wider py-4 px-6 rounded-xl hover:from-gold-400 hover:to-gold-500 transition duration-300 mt-2 shadow-lg shadow-gold-500/10 active:scale-95">
                    Submit Registration
                </button>
            </div>
        </form>
        <?php endif; ?>
    </div>

    <!-- Cropping Modal -->
    <div id="cropModal" class="fixed inset-0 z-[100] hidden bg-black/90 backdrop-blur-md flex items-center justify-center p-4">
        <div class="max-w-md w-full bg-zinc-950 border border-gold-500/20 rounded-2xl p-6 shadow-2xl space-y-4">
            <div class="flex justify-between items-center border-b border-white/5 pb-3">
                <h3 class="text-base font-bold text-gold-400 flex items-center gap-1.5">
                    <i class="fa-solid fa-crop text-gold-400"></i> Adjust & Crop Photo
                </h3>
                <button type="button" onclick="closeCropModal()" class="text-gray-400 hover:text-white flex items-center justify-center w-6 h-6 rounded-full hover:bg-white/5">
                    <i class="fa-solid fa-xmark text-sm"></i>
                </button>
            </div>
            
            <!-- Cropper Area -->
            <div class="w-full max-h-[50vh] overflow-hidden rounded-xl bg-black border border-white/10 flex items-center justify-center">
                <img id="cropper-target" src="" class="max-w-full max-h-[50vh]">
            </div>
            
            <p class="text-[10px] text-gray-500 text-center uppercase tracking-wider">Drag to position • Pinch/Scroll to zoom</p>
            
            <!-- Actions -->
            <div class="flex gap-3 pt-2">
                <button type="button" onclick="closeCropModal()"
                        class="flex-1 bg-zinc-900 border border-white/5 text-gray-400 font-bold uppercase text-[10px] tracking-wider py-3 rounded-xl hover:bg-white/5 transition">
                    Cancel
                </button>
                <button type="button" onclick="performCrop()"
                        class="flex-1 bg-gold-500 text-black font-extrabold uppercase text-[10px] tracking-wider py-3 rounded-xl hover:bg-gold-400 transition">
                    Crop & Save
                </button>
            </div>
        </div>
    </div>

    <!-- Script for File Input Preview & Cropping -->
    <script>
        let cropper = null;
        const fileInput = document.getElementById('profile_image');
        const promptDiv = document.getElementById('upload-prompt');
        const feedbackDiv = document.getElementById('upload-feedback');
        const nameDisplay = document.getElementById('file-name-display');
        
        const cropModal = document.getElementById('cropModal');
        const cropperTarget = document.getElementById('cropper-target');
        const croppedInput = document.getElementById('cropped_image_data');

        fileInput.addEventListener('change', (e) => {
            if (fileInput.files.length > 0) {
                const file = fileInput.files[0];
                
                // Show modal & start cropper
                const reader = new FileReader();
                reader.onload = function(event) {
                    cropperTarget.src = event.target.result;
                    cropModal.classList.remove('hidden');
                    
                    if (cropper) {
                        cropper.destroy();
                    }
                    
                    cropper = new Cropper(cropperTarget, {
                        aspectRatio: 9 / 10,
                        viewMode: 1,
                        dragMode: 'move',
                        autoCropArea: 1,
                        restore: false,
                        guides: true,
                        center: true,
                        highlight: false,
                        cropBoxMovable: true,
                        cropBoxResizable: true,
                        toggleDragModeOnDblclick: false,
                    });
                };
                reader.readAsDataURL(file);
            }
        });

        function closeCropModal() {
            cropModal.classList.add('hidden');
            fileInput.value = ''; // Reset input
            croppedInput.value = '';
            promptDiv.classList.remove('hidden');
            feedbackDiv.classList.add('hidden');
        }

        function performCrop() {
            if (!cropper) return;
            
            // Get cropped canvas optimized for profile cards
            const canvas = cropper.getCroppedCanvas({
                width: 360,
                height: 400,
                imageSmoothingEnabled: true,
                imageSmoothingQuality: 'high',
            });
            
            // Convert to base64
            const dataUrl = canvas.toDataURL('image/jpeg', 0.85);
            croppedInput.value = dataUrl;
            
            // UI feedback
            nameDisplay.innerText = '✓ Photo cropped and formatted successfully!';
            promptDiv.classList.add('hidden');
            feedbackDiv.classList.remove('hidden');
            cropModal.classList.add('hidden');
        }

        // Generate and Download Registration Card as High-Res PNG Image
        function downloadRegistrationCard() {
            const card = document.getElementById('registration-card');
            const downloadBtn = document.getElementById('download-card-btn');
            
            // Temporary loading state
            const originalBtnText = downloadBtn.innerHTML;
            downloadBtn.disabled = true;
            downloadBtn.innerHTML = '<i class="fa-solid fa-spinner animate-spin"></i> Generating Image...';
            
            html2canvas(card, {
                useCORS: true,
                scale: 3, // Premium quality high-DPI scaling
                backgroundColor: '#0a0a0a',
                logging: false
            }).then(canvas => {
                // Restore button
                downloadBtn.disabled = false;
                downloadBtn.innerHTML = originalBtnText;
                
                const link = document.createElement('a');
                link.download = 'SMCL_Registration_' + card.getAttribute('data-utr') + '.png';
                link.href = canvas.toDataURL('image/png');
                link.click();
            }).catch(err => {
                console.error('Failed to generate image:', err);
                downloadBtn.disabled = false;
                downloadBtn.innerHTML = originalBtnText;
                alert('Could not download image. Please screenshot the card instead.');
            });
        }
    </script>

    <?php if ($duplicateFound && $duplicatePlayerDetails): ?>
        <!-- DUPLICATE REGISTRATION MODAL -->
        <div id="duplicateModal" class="fixed inset-0 z-[120] bg-black/85 backdrop-blur-md flex items-center justify-center p-4">
            <div class="max-w-md w-full bg-zinc-950 border border-red-500/30 rounded-2xl p-6 shadow-2xl space-y-4 animate-scale-up">
                <div class="flex justify-between items-center border-b border-white/5 pb-3">
                    <h3 class="text-base font-bold text-red-400 flex items-center gap-1.5">
                        <i class="fa-solid fa-circle-exclamation text-red-500"></i> Registration Exists
                    </h3>
                    <button type="button" onclick="closeDuplicateModal()" class="text-gray-400 hover:text-white flex items-center justify-center w-6 h-6 rounded-full hover:bg-white/5">
                        <i class="fa-solid fa-xmark text-sm"></i>
                    </button>
                </div>
                
                <div class="space-y-3">
                    <p class="text-xs text-gray-400 leading-relaxed">
                        A player is already registered with this mobile number (<strong class="text-white"><?php echo htmlspecialchars($mobile); ?></strong>). Here are the details of the existing registration:
                    </p>
                    
                    <div class="bg-black/60 border border-white/5 rounded-xl p-4 space-y-3 text-xs">
                        <div class="grid grid-cols-2 gap-2">
                            <div>
                                <div class="text-[8px] text-gray-500 font-bold uppercase tracking-wider">Candidate Name</div>
                                <div class="text-sm font-bold text-white"><?php echo htmlspecialchars($duplicatePlayerDetails['name']); ?></div>
                            </div>
                            <div>
                                <div class="text-[8px] text-gray-500 font-bold uppercase tracking-wider">Verification Status</div>
                                <div>
                                    <?php if ($duplicatePlayerDetails['payment_status'] === 'Verified'): ?>
                                        <span class="bg-gold-950/60 border border-gold-500/20 text-gold-400 font-bold px-1.5 py-0.5 rounded text-[8px] uppercase tracking-wide">Verified</span>
                                    <?php elseif ($duplicatePlayerDetails['payment_status'] === 'Rejected'): ?>
                                        <span class="bg-red-950/60 border border-red-500/20 text-red-400 font-bold px-1.5 py-0.5 rounded text-[8px] uppercase tracking-wide">Rejected</span>
                                    <?php else: ?>
                                        <span class="bg-yellow-950/60 border border-yellow-500/20 text-yellow-400 font-bold px-1.5 py-0.5 rounded text-[8px] uppercase tracking-wide animate-pulse">Pending Payment/Approval</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        
                        <div class="grid grid-cols-2 gap-2 border-t border-white/5 pt-2">
                            <div>
                                <div class="text-[8px] text-gray-500 font-bold uppercase tracking-wider">Playing Role</div>
                                <div class="text-xs font-bold text-gray-300"><?php echo htmlspecialchars($duplicatePlayerDetails['role']); ?></div>
                            </div>
                            <div>
                                <div class="text-[8px] text-gray-500 font-bold uppercase tracking-wider">Place / Hometown</div>
                                <div class="text-xs font-bold text-gray-300"><?php echo htmlspecialchars($duplicatePlayerDetails['place']); ?></div>
                            </div>
                        </div>
                        
                        <div class="border-t border-white/5 pt-2">
                            <div class="text-[8px] text-gray-500 font-bold uppercase tracking-wider">Registration Reference ID</div>
                            <div class="text-xs font-mono font-bold text-gold-400"><?php echo htmlspecialchars($duplicatePlayerDetails['payment_utr']); ?></div>
                        </div>
                    </div>
                </div>

                <div class="pt-2 text-center">
                    <button type="button" onclick="closeDuplicateModal()"
                            class="w-full bg-gradient-to-r from-red-600 to-amber-700 text-white font-extrabold uppercase text-[10px] tracking-wider py-3 rounded-xl hover:from-red-500 hover:to-amber-600 transition shadow-lg shadow-red-500/10">
                        Okay, Close
                    </button>
                </div>
            </div>
        </div>
        
        <script>
            function closeDuplicateModal() {
                const modal = document.getElementById('duplicateModal');
                if (modal) {
                    modal.classList.add('hidden');
                }
            }
        </script>
    <?php endif; ?>
</body>
</html>
