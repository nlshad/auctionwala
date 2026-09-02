<?php
// public/register.php
session_start();
require_once '../config/db.php';

$tournamentId = get_active_tournament_id($pdo);

// Fetch tournament info
$stmt = $pdo->prepare("SELECT * FROM tournaments WHERE id = ?");
$stmt->execute([$tournamentId]);
$tournament = $stmt->fetch();
$tName = ($tournament && !empty($tournament['name'])) ? $tournament['name'] : 'Official Player Registration';

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
    <title>Player Registration — <?php echo htmlspecialchars($tName); ?></title>
    <link rel="icon" type="image/png" href="uploads/auctionwala_logo.png">
    <?php require_once 'components/ui_head.php'; ?>
    <!-- Cropper.js CDN -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.1/cropper.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.1/cropper.min.js"></script>
    <!-- html2canvas CDN for image download -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
</head>
<body class="text-slate-900 min-h-screen py-8 px-4 flex items-center justify-center font-inter">
    <div class="max-w-3xl w-full bg-white rounded-2xl border border-slate-200 overflow-hidden shadow-xl">
        <!-- Top Banner -->
        <div class="bg-white p-6 sm:p-8 border-b border-slate-200 text-center relative shadow-sm">
            <div class="mb-4">
                <a href="landing.php" class="text-xs uppercase tracking-widest text-amber-700 hover:text-amber-800 font-extrabold inline-flex items-center gap-1.5 transition">
                    <i class="fa-solid fa-arrow-left text-xs"></i> Back to Portal Home
                </a>
            </div>

            <a href="landing.php" class="inline-block mb-3">
                <img src="uploads/auctionwala_logo.png" alt="AuctionWala Logo" class="h-12 sm:h-14 object-contain mx-auto">
            </a>

            <h1 class="text-2xl sm:text-4xl font-montserrat font-black text-slate-900 uppercase tracking-tight mt-1">
                <?php echo htmlspecialchars($tName); ?>
            </h1>
            <p class="text-slate-600 text-xs mt-1.5 uppercase tracking-widest font-mono font-bold">Official Player Registration Pool</p>
        </div>

        <!-- Feedback Messages -->
        <?php if (!empty($successMsg)): ?>
            <div class="mx-6 mt-6 bg-emerald-500/15 border border-emerald-500/40 text-emerald-900 px-5 py-4 rounded-xl text-sm font-extrabold flex items-center gap-3 shadow-sm">
                <i class="fa-solid fa-circle-check text-emerald-600 text-xl"></i>
                <div><?php echo $successMsg; ?></div>
            </div>
        <?php endif; ?>

        <?php if (!empty($errorMsg)): ?>
            <div class="mx-6 mt-6 bg-red-500/15 border border-red-500/40 text-red-900 px-5 py-4 rounded-xl text-sm font-extrabold flex items-center gap-3 shadow-sm">
                <i class="fa-solid fa-circle-exclamation text-red-600 text-xl"></i>
                <div><?php echo $errorMsg; ?></div>
            </div>
        <?php endif; ?>

        <?php if (!$registrationEnabled): ?>
            <!-- Disabled Notice -->
            <div class="p-10 text-center space-y-5">
                <div class="w-16 h-16 bg-red-500/20 border border-red-500/40 text-red-600 rounded-full flex items-center justify-center mx-auto text-2xl shadow-sm">
                    <i class="fa-solid fa-ban font-bold"></i>
                </div>
                <div class="space-y-2">
                    <h3 class="text-2xl font-black text-slate-900 uppercase tracking-tight">Public Registrations Closed</h3>
                    <p class="text-xs text-slate-700 font-bold leading-relaxed max-w-md mx-auto">
                        Player registrations for this league are currently closed. Please contact your league organizer for more details.
                    </p>
                </div>
                <div class="pt-2">
                    <a href="index.php" class="bg-slate-900 text-white font-extrabold text-xs uppercase tracking-wider px-6 py-3 rounded-xl hover:bg-slate-800 transition inline-block shadow-md">
                        <i class="fa-solid fa-arrow-left mr-1.5 text-xs"></i> Back to Spectator View
                    </a>
                </div>
            </div>
        <?php elseif (!empty($registeredPlayer)): ?>
            <!-- REGISTRATION SUCCESS CARD -->
            <div class="p-6 md:p-8 max-w-xl mx-auto space-y-6 text-center">
                <!-- Gorgeous Broadcast Sports Registration Card -->
                <div class="ipl-card-frame ipl-card-live max-w-[340px] mx-auto p-6 mb-6 shadow-2xl relative text-left bg-slate-900 border-2 border-amber-500/60 rounded-2xl" id="registration-card" data-utr="<?php echo htmlspecialchars($registeredPlayer['utr']); ?>">
                    <!-- Top Header: Location & Verification Tag -->
                    <div class="flex items-center justify-between relative z-10 mb-3">
                        <div class="flex items-center gap-1.5 bg-black/60 border border-white/20 px-2.5 py-1 rounded-lg">
                            <span class="text-xs">🇮🇳</span>
                            <span class="text-[10px] font-black text-white uppercase tracking-wider font-mono">
                                <?php echo htmlspecialchars($registeredPlayer['place']); ?>
                            </span>
                        </div>
                        <span class="bg-amber-500/20 border border-amber-400/40 text-amber-300 text-[9px] font-black px-2.5 py-1 rounded-lg uppercase tracking-wider">
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
                        <div class="text-[10px] font-black text-amber-400 uppercase tracking-widest leading-tight"><?php echo htmlspecialchars($pFirstName); ?></div>
                        <h3 class="text-2xl font-black text-white uppercase tracking-tight leading-none"><?php echo htmlspecialchars($pLastName ? $pLastName : $pFirstName); ?></h3>
                    </div>

                    <!-- Center Stage: Cutout Player Image -->
                    <div class="relative z-10 my-4 flex justify-center">
                        <div class="w-36 h-40 rounded-2xl overflow-hidden border-2 border-amber-400/60 bg-slate-950 shadow-2xl relative">
                            <img src="uploads/<?php echo htmlspecialchars($registeredPlayer['profile_image']); ?>" alt="Candidate" class="w-full h-full object-cover">
                        </div>
                    </div>

                    <!-- Bottom Details Box -->
                    <div class="relative z-10 grid grid-cols-2 gap-2.5 pt-2">
                        <div class="bg-slate-800 border border-slate-700 p-2.5 rounded-xl flex flex-col justify-center">
                            <span class="text-[8px] uppercase font-black text-cyan-300 tracking-wider mb-0.5">Player Role</span>
                            <span class="text-xs font-black text-white uppercase truncate"><?php echo htmlspecialchars($registeredPlayer['role']); ?></span>
                        </div>
                        <div class="bg-slate-800 border border-slate-700 p-2.5 rounded-xl flex flex-col justify-center text-center">
                            <span class="text-[8px] uppercase font-black text-amber-300 tracking-wider mb-0.5">Base Price</span>
                            <span class="text-xs font-black text-white font-mono">₹<?php echo number_format($registeredPlayer['base_price'] ?? 100); ?></span>
                        </div>
                        <div class="col-span-2 bg-slate-950 border border-amber-500/40 rounded-xl p-2.5 text-center">
                            <span class="text-[8px] text-slate-400 font-extrabold uppercase tracking-wider block mb-0.5">Registration UTR Reference</span>
                            <span class="text-xs font-black text-amber-400 font-mono tracking-wider uppercase"><?php echo htmlspecialchars($registeredPlayer['utr']); ?></span>
                        </div>
                    </div>
                </div>

                <!-- Download Card Buttons -->
                <div class="pt-2 flex flex-col sm:flex-row gap-3 justify-center max-w-sm mx-auto">
                    <button id="download-card-btn" onclick="downloadRegistrationCard()"
                            class="flex-1 bg-amber-500 hover:bg-amber-400 text-slate-950 font-black uppercase text-xs tracking-wider py-3.5 px-6 rounded-xl transition duration-200 shadow-lg shadow-amber-500/20 active:scale-95 flex items-center justify-center gap-2">
                        <i class="fa-solid fa-download"></i> Download Card Image
                    </button>
                    <a href="register.php"
                       class="flex-1 bg-slate-900 hover:bg-slate-800 text-white font-black text-xs uppercase tracking-wider py-3.5 px-6 rounded-xl transition flex items-center justify-center gap-2 shadow-md">
                        <i class="fa-solid fa-user-plus"></i> Register Another
                    </a>
                </div>

                <!-- Important Notice -->
                <div class="max-w-sm mx-auto bg-white/90 border border-slate-300 text-left p-5 rounded-2xl text-xs space-y-3 mt-4 shadow-sm">
                    <div class="font-black flex items-center gap-1.5 text-slate-900 uppercase tracking-wider text-[11px] border-b border-slate-200 pb-2">
                        <i class="fa-solid fa-circle-info text-amber-600"></i> Important Instructions
                    </div>
                    <div class="space-y-3 text-slate-700 text-[11px] font-bold leading-relaxed">
                        <div>
                            <span class="font-black text-amber-800 uppercase tracking-wide block text-[10px]">1. Save your card</span>
                            Please download and save this card image to your device.
                        </div>
                        <div>
                            <span class="font-black text-amber-800 uppercase tracking-wide block text-[10px]">2. Manual Verification</span>
                            Your account will be verified by the League Organizer once payment is confirmed.
                        </div>
                        <div>
                            <span class="font-black text-amber-800 uppercase tracking-wide block text-[10px]">3. Join the auction</span>
                            Only verified candidates will be listed in the live auction bidding pool.
                        </div>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <!-- Form Container -->
            <form action="register.php?t_id=<?php echo $tournamentId; ?>" method="POST" enctype="multipart/form-data" class="p-6 md:p-8 max-w-xl mx-auto space-y-6">
            
            <div class="space-y-5">
                <h3 class="text-xl font-black text-slate-900 uppercase tracking-tight border-b border-slate-200 pb-3 flex items-center gap-2">
                    <i class="fa-solid fa-baseball-bat-ball text-amber-600 text-xl"></i> Player Registration Details
                </h3>

                <!-- Name Input -->
                <div>
                    <label class="block text-xs font-black text-slate-900 uppercase tracking-wider mb-2">Full Name</label>
                    <input type="text" name="name" required placeholder="e.g. Sanju Samson"
                           class="w-full bg-white/95 border-2 border-slate-300 rounded-xl px-4 py-3 text-sm text-slate-900 font-extrabold focus:outline-none focus:border-amber-500 focus:ring-4 focus:ring-amber-500/20 transition placeholder-slate-400 shadow-sm"
                           value="<?php echo htmlspecialchars($name ?? ''); ?>">
                </div>

                <!-- Mobile & Place -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-black text-slate-900 uppercase tracking-wider mb-2">Mobile Number</label>
                        <input type="tel" name="mobile" required placeholder="10-digit number" pattern="[0-9]{10}"
                               class="w-full bg-white/95 border-2 border-slate-300 rounded-xl px-4 py-3 text-sm text-slate-900 font-extrabold focus:outline-none focus:border-amber-500 focus:ring-4 focus:ring-amber-500/20 transition placeholder-slate-400 shadow-sm"
                               value="<?php echo htmlspecialchars($mobile ?? ''); ?>">
                    </div>
                    <div>
                        <label class="block text-xs font-black text-slate-900 uppercase tracking-wider mb-2">Place / Hometown</label>
                        <input type="text" name="place" required placeholder="e.g. Mananthavady"
                               class="w-full bg-white/95 border-2 border-slate-300 rounded-xl px-4 py-3 text-sm text-slate-900 font-extrabold focus:outline-none focus:border-amber-500 focus:ring-4 focus:ring-amber-500/20 transition placeholder-slate-400 shadow-sm"
                               value="<?php echo htmlspecialchars($place ?? ''); ?>">
                    </div>
                </div>

                <!-- Playing Role -->
                <div>
                    <label class="block text-xs font-black text-slate-900 uppercase tracking-wider mb-2">Playing Role</label>
                    <select name="role" required 
                            class="w-full bg-white/95 border-2 border-slate-300 rounded-xl px-4 py-3 text-sm text-slate-900 font-extrabold focus:outline-none focus:border-amber-500 focus:ring-4 focus:ring-amber-500/20 transition shadow-sm cursor-pointer">
                        <option value="" disabled selected class="bg-white text-slate-500">Select Playing Role</option>
                        <option value="Batsman" <?php echo ($role ?? '') === 'Batsman' ? 'selected' : ''; ?> class="bg-white text-slate-900 font-bold">Batsman</option>
                        <option value="Bowler" <?php echo ($role ?? '') === 'Bowler' ? 'selected' : ''; ?> class="bg-white text-slate-900 font-bold">Bowler</option>
                        <option value="All-Rounder" <?php echo ($role ?? '') === 'All-Rounder' ? 'selected' : ''; ?> class="bg-white text-slate-900 font-bold">All-Rounder</option>
                        <option value="Wicket-Keeper" <?php echo ($role ?? '') === 'Wicket-Keeper' ? 'selected' : ''; ?> class="bg-white text-slate-900 font-bold">Wicket-Keeper</option>
                    </select>
                </div>

                <!-- Profile Photo Upload -->
                <div>
                    <label class="block text-xs font-black text-slate-900 uppercase tracking-wider mb-2">Profile Image (Max 2MB, JPG/PNG)</label>
                    <div class="relative w-full bg-slate-50 border-2 border-dashed border-slate-300 hover:border-amber-500 rounded-xl p-5 text-center transition cursor-pointer">
                        <input type="file" name="profile_image" id="profile_image" required accept="image/*"
                               class="absolute inset-0 opacity-0 cursor-pointer w-full h-full">
                        <input type="hidden" name="cropped_image_data" id="cropped_image_data">
                        <div class="space-y-1" id="upload-prompt">
                            <i class="fa-solid fa-camera text-amber-600 text-3xl block mx-auto mb-1"></i>
                            <p class="text-xs text-slate-900 font-extrabold">Click to upload or drag & drop</p>
                            <p class="text-[10px] text-slate-600 font-bold">Supported formats: JPG, JPEG, PNG</p>
                        </div>
                        <div class="hidden space-y-1 text-amber-700 font-extrabold text-xs animate-pulse" id="upload-feedback">
                            <i class="fa-solid fa-check-circle text-emerald-600 text-2xl block mx-auto"></i>
                            <p id="file-name-display"></p>
                        </div>
                    </div>
                </div>

                <!-- Submit Button -->
                <button type="submit"
                        class="w-full bg-gradient-to-r from-amber-500 to-amber-600 hover:from-amber-400 hover:to-amber-500 text-slate-950 font-black uppercase text-xs tracking-wider py-4 px-6 rounded-xl transition duration-200 mt-2 shadow-xl shadow-amber-500/20 active:scale-95">
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
