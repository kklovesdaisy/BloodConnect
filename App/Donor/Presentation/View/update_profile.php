<?php

use App\Shared\Helpers\Session;

Session::start();

// Catch the incoming dataset variables directly from form submission or persistent session state fallback
$username  = $user['username'] ?? '';
$email     = $user['email'] ?? '';
$contact   = $user['phone'] ?? '';
$bloodType = $user['blood_group'] ?? 'Not set';
$address   = $user['address'] ?? '';
$donorDetails = $donorDetails ?? [];

$dateOfBirth = $donorDetails['date_of_birth'] ?? '';
$weight = $donorDetails['weight'] ?? '';
$hasSavedDob = !empty($dateOfBirth);

$errorMessage = null;

// Catch the Finalize Submitting Event triggered inside this secondary view screen
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_save_profile'])) {
    $newPassword     = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    // Secure JavaScript Form validation fallback mechanism
    if (!empty($newPassword) && $newPassword !== $confirmPassword) {
        $errorMessage = "Validation Error: Passwords do not match!";
    } else {
        // Sanitize incoming stream values to protect active parameters (Synced with HTML input names)
        $_SESSION['username'] = trim(filter_input(INPUT_POST, 'username', FILTER_SANITIZE_SPECIAL_CHARS));
        $_SESSION['email']    = trim(filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL));
        $_SESSION['contact']  = trim(filter_input(INPUT_POST, 'phone', FILTER_SANITIZE_SPECIAL_CHARS));
        $_SESSION['address']  = trim(filter_input(INPUT_POST, 'address', FILTER_SANITIZE_SPECIAL_CHARS));

        if (!empty($newPassword)) {
            /* 👉 DATABASE SAVING HOOK PLACEHOLDER:
               $hashedPassword = password_hash($newPassword, PASSWORD_BCRYPT);
               $db->query("UPDATE users SET password = ? WHERE id = ?", [$hashedPassword, ...]);
            */
            $_SESSION['profile_updated_toast'] = "Profile parameters and password updated successfully!";
        } else {
            $_SESSION['profile_updated_toast'] = "Profile specifications updated successfully!";
        }

        // Return the routing chain back home safely
        header("Location: /BloodConnect/public/donor/profile");
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en" class="h-full selection:bg-red-500 selection:text-white">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modify Account Specifications | BloodConnect</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background: radial-gradient(120% 120% at 50% 10%, #ffffff 40%, #fff1f1 100%);
        }

        @keyframes cubicFadeIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .page-enter {
            animation: cubicFadeIn 0.6s cubic-bezier(0.16, 1, 0.3, 1) forwards;
        }
    </style>
</head>

<body class="min-h-full flex items-center justify-center p-3 sm:p-6 md:p-8">

    <div class="w-full max-w-4xl mx-auto bg-white rounded-2xl sm:rounded-3xl shadow-xl shadow-red-950/5 border border-slate-100 p-5 sm:p-6 md:p-8 page-enter">

        <div class="flex flex-col-reverse sm:flex-row sm:items-center sm:justify-between gap-4 border-b border-slate-100 pb-4 mb-4 sm:mb-6">
            <div>
                <h2 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight">Modify Account Specifications</h2>
                <p class="text-xs sm:text-sm text-slate-400 mt-1">Update editable profile details or refresh your structural access password security credentials below.</p>
            </div>
            <div class="w-12 h-12 rounded-xl bg-red-50 text-red-600 flex items-center justify-center text-xl shadow-inner self-start sm:self-auto">
                <i class="fa-solid fa-user-gear"></i>
            </div>
        </div>

        <?php if ($errorMessage): ?>
            <div class="mb-6 p-4 bg-rose-50 border border-rose-100 rounded-xl text-rose-700 text-sm font-semibold flex items-center gap-3 animate-pulse">
                <i class="fa-solid fa-circle-exclamation text-base"></i>
                <span><?= $errorMessage ?></span>
            </div>
        <?php endif; ?>

        <form action="/BloodConnect/public/donor/profile/update"
            method="POST"
            onsubmit="return validatePasswords()"
            class="space-y-4">
            <input type="hidden" name="action_save_profile" value="1">

            <div>
                <h3 class="text-xs font-bold text-slate-400 uppercase tracking-widest border-b border-slate-100 pb-2 mb-4">Profile Matrix</h3>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-3 sm:gap-4">
                    <div class="space-y-1.5">
                        <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider">Username Name</label>
                        <div class="relative rounded-xl transition-all focus-within:ring-2 focus-within:ring-red-500/20">
                            <span class="absolute inset-y-0 left-0 flex items-center pl-4 text-slate-400"><i class="fa-regular fa-user text-xs sm:text-sm"></i></span>
                            <input type="text" name="username" value="<?= htmlspecialchars($username) ?>" class="w-full bg-slate-50 border border-slate-200/80 rounded-xl pl-11 pr-4 py-2 text-sm font-semibold text-slate-800 focus:outline-none focus:border-red-500 focus:bg-white transition-all" required>
                        </div>
                    </div>

                    <div class="space-y-1.5">
                        <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider">Email Address</label>
                        <div class="relative rounded-xl transition-all focus-within:ring-2 focus-within:ring-red-500/20">
                            <span class="absolute inset-y-0 left-0 flex items-center pl-4 text-slate-400"><i class="fa-regular fa-envelope text-xs sm:text-sm"></i></span>
                            <input type="email" name="email" value="<?= htmlspecialchars($email) ?>" class="w-full bg-slate-50 border border-slate-200/80 rounded-xl pl-11 pr-4 py-2 text-sm font-semibold text-slate-800 focus:outline-none focus:border-red-500 focus:bg-white transition-all" required>
                        </div>
                    </div>

                    <div class="space-y-1.5">
                        <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider">Phone Number</label>
                        <div class="relative rounded-xl transition-all focus-within:ring-2 focus-within:ring-red-500/20">
                            <span class="absolute inset-y-0 left-0 flex items-center pl-4 text-slate-400"><i class="fa-solid fa-phone-flip text-xs"></i></span>
                            <input
                                type="tel"
                                id="phone"
                                name="phone"
                                value="<?= htmlspecialchars($contact) ?>"
                                maxlength="11"
                                inputmode="numeric"
                                pattern="09[0-9]{9}"
                                class="w-full bg-slate-50 border border-slate-200/80 rounded-xl pl-11 pr-4 py-2 text-sm font-semibold text-slate-800 focus:outline-none focus:border-red-500 focus:bg-white transition-all"
                                required>
                        </div>
                    </div>

                    <div class="space-y-1.5">
                        <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider">Blood Group</label>
                        <div class="relative rounded-xl transition-all focus-within:ring-2 focus-within:ring-red-500/20">
                            <span class="absolute inset-y-0 left-0 flex items-center pl-4 text-red-500"><i class="fa-solid fa-droplet text-xs"></i></span>
                            <div class="w-full bg-slate-50 border border-slate-200/80 rounded-xl pl-11 pr-4 py-2 text-sm font-semibold text-slate-800">
                                <?= htmlspecialchars($bloodType) ?>
                            </div>
                        </div>
                        <p class="text-xs text-slate-400">This value is managed by the system and cannot be changed here.</p>
                    </div>

                    <div class="space-y-1.5">
                        <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider">Date of Birth</label>
                        <div class="relative rounded-xl transition-all focus-within:ring-2 focus-within:ring-red-500/20">
                            <span class="absolute inset-y-0 left-0 flex items-center pl-4 text-slate-400"><i class="fa-solid fa-calendar text-xs"></i></span>
                            <?php if ($hasSavedDob): ?>
                                <div class="w-full bg-slate-100 border border-slate-200/80 rounded-xl pl-11 pr-4 py-2 text-sm font-semibold text-slate-600">
                                    <?= htmlspecialchars($dateOfBirth) ?>
                                </div>
                            <?php else: ?>
                                <input type="date" name="date_of_birth" value="<?= htmlspecialchars($dateOfBirth) ?>"
                                    max="<?= date('Y-m-d', strtotime('-18 years')) ?>"
                                    class="w-full bg-slate-50 border border-slate-200/80 rounded-xl pl-11 pr-4 py-2 text-sm font-semibold text-slate-800 focus:outline-none focus:border-red-500 focus:bg-white transition-all">
                            <?php endif; ?>
                        </div>
                        <p class="text-xs text-slate-400"><?= $hasSavedDob ? 'Date of birth cannot be changed after saving.' : 'Required for eligibility verification.' ?></p>
                    </div>

                    <div class="space-y-1.5">
                        <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider">Weight (kg)</label>
                        <div class="relative rounded-xl transition-all focus-within:ring-2 focus-within:ring-red-500/20">
                            <span class="absolute inset-y-0 left-0 flex items-center pl-4 text-slate-400"><i class="fa-solid fa-weight-scale text-xs"></i></span>
                            <input type="number" name="weight" step="0.1" min="1" max="300"
                                value="<?= htmlspecialchars($weight) ?>"
                                placeholder="e.g. 70"
                                class="w-full bg-slate-50 border border-slate-200/80 rounded-xl pl-11 pr-4 py-2 text-sm font-semibold text-slate-800 focus:outline-none focus:border-red-500 focus:bg-white transition-all">
                            <span class="absolute inset-y-0 right-0 flex items-center pr-4 text-xs font-medium text-slate-400">kg</span>
                        </div>
                        <p class="text-xs text-slate-400">Minimum 50 kg required for donation eligibility.</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-3 sm:gap-4 mt-4">
                    <div class="space-y-1.5">
                        <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider">State / Region</label>
                        <div class="relative rounded-xl transition-all focus-within:ring-2 focus-within:ring-red-500/20">
                            <span class="absolute inset-y-0 left-0 flex items-center pl-4 text-slate-400"><i class="fa-solid fa-map-location-dot text-xs"></i></span>
                            <input type="text" name="state_region" value="<?= htmlspecialchars($donorDetails['state_region'] ?? '') ?>"
                                placeholder="e.g. Yangon Region"
                                class="w-full bg-slate-50 border border-slate-200/80 rounded-xl pl-11 pr-4 py-2 text-sm font-semibold text-slate-800 focus:outline-none focus:border-red-500 focus:bg-white transition-all">
                        </div>
                    </div>
                    <div class="space-y-1.5">
                        <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider">Township</label>
                        <div class="relative rounded-xl transition-all focus-within:ring-2 focus-within:ring-red-500/20">
                            <span class="absolute inset-y-0 left-0 flex items-center pl-4 text-slate-400"><i class="fa-solid fa-location-dot text-xs"></i></span>
                            <input type="text" name="township" value="<?= htmlspecialchars($donorDetails['township'] ?? '') ?>"
                                placeholder="e.g. Hlaingthaya"
                                class="w-full bg-slate-50 border border-slate-200/80 rounded-xl pl-11 pr-4 py-2 text-sm font-semibold text-slate-800 focus:outline-none focus:border-red-500 focus:bg-white transition-all">
                        </div>
                    </div>
                </div>

                <?php
                $storedPregnancy = $donorDetails['is_pregnant'] ?? null;
                $selectedPregnancy = ($storedPregnancy === null || $storedPregnancy === '')
                    ? ''
                    : (!empty($storedPregnancy) ? 'pregnant' : 'not_pregnant');
                ?>
                <div class="space-y-1.5 mt-4">
                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider">Pregnancy Status</label>
                    <div class="grid grid-cols-2 gap-3 sm:gap-4">
                        <label class="pregnancy-option cursor-pointer border-2 rounded-lg px-3 py-2 text-center transition-all <?= $selectedPregnancy === 'not_pregnant' ? 'border-emerald-500 bg-emerald-50' : 'border-slate-200/80 bg-slate-50 hover:border-emerald-300' ?>">
                            <input type="radio" name="pregnancy_status" value="not_pregnant"
                                class="hidden pregnancy-radio" <?= $selectedPregnancy === 'not_pregnant' ? 'checked' : '' ?> required>
                            <span class="block text-xs font-bold text-slate-800"><i class="fa-solid fa-circle-check text-emerald-500 mr-1"></i>Not Pregnant</span>
                            <span class="block text-[9px] font-bold text-slate-400 mt-0.5 uppercase tracking-wider">Eligible to donate</span>
                        </label>
                        <label class="pregnancy-option cursor-pointer border-2 rounded-lg px-3 py-2 text-center transition-all <?= $selectedPregnancy === 'pregnant' ? 'border-amber-500 bg-amber-50' : 'border-slate-200/80 bg-slate-50 hover:border-amber-300' ?>">
                            <input type="radio" name="pregnancy_status" value="pregnant"
                                class="hidden pregnancy-radio" <?= $selectedPregnancy === 'pregnant' ? 'checked' : '' ?>>
                            <span class="block text-xs font-bold text-slate-800"><i class="fa-solid fa-baby text-amber-500 mr-1"></i>Pregnant</span>
                            <span class="block text-[9px] font-bold text-slate-400 mt-0.5 uppercase tracking-wider">Cannot donate</span>
                        </label>
                    </div>
                    <p class="text-xs text-slate-400">Pregnant donors are temporarily ineligible and will be marked Unavailable.</p>
                </div>

                <div class="space-y-1.5 mt-4">
                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider">Address / Location</label>
                    <div class="relative rounded-xl transition-all focus-within:ring-2 focus-within:ring-red-500/20">
                        <span class="absolute top-3.5 left-4 text-slate-400"><i class="fa-solid fa-location-dot text-xs sm:text-sm"></i></span>
                        <textarea name="address" rows="2" class="w-full bg-slate-50 border border-slate-200/80 rounded-xl pl-11 pr-4 py-2 text-sm font-semibold text-slate-800 focus:outline-none focus:border-red-500 focus:bg-white transition-all" required><?= htmlspecialchars($address) ?></textarea>
                    </div>
                </div>
            </div>

            <div>
                <h3 class="text-xs font-bold text-slate-400 uppercase tracking-widest border-b border-slate-100 pb-2 mb-4">Security Specifications</h3>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-3 sm:gap-4">
                    <div class="space-y-1.5">
                        <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider">New Password</label>
                        <div class="relative rounded-xl transition-all focus-within:ring-2 focus-within:ring-red-500/20">
                            <span class="absolute inset-y-0 left-0 flex items-center pl-4 text-slate-400"><i class="fa-solid fa-lock text-xs"></i></span>
                            <input type="password" id="new_password" name="new_password" placeholder="Leave blank to keep current" class="w-full bg-slate-50 border border-slate-200/80 rounded-xl pl-11 pr-4 py-2 text-sm font-semibold text-slate-800 focus:outline-none focus:border-red-500 focus:bg-white transition-all">
                        </div>
                    </div>

                    <div class="space-y-1.5">
                        <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider">Confirm Password</label>
                        <div class="relative rounded-xl transition-all focus-within:ring-2 focus-within:ring-red-500/20">
                            <span class="absolute inset-y-0 left-0 flex items-center pl-4 text-slate-400"><i class="fa-solid fa-shield-halved text-xs"></i></span>
                            <input type="password" id="confirm_password" name="confirm_password" placeholder="Confirm new password" class="w-full bg-slate-50 border border-slate-200/80 rounded-xl pl-11 pr-4 py-2 text-sm font-semibold text-slate-800 focus:outline-none focus:border-red-500 focus:bg-white transition-all">
                        </div>
                    </div>
                </div>
            </div>

            <div class="flex flex-col-reverse sm:flex-row items-stretch sm:items-center sm:justify-end gap-3 pt-6 border-t border-slate-100">

                <a href="/BloodConnect/public/donor/profile"
                    class="px-6 py-3.5 rounded-xl border border-slate-200 text-sm font-bold text-slate-500 hover:bg-slate-50 transition-colors flex items-center justify-center gap-2">
                    Discard Changes
                </a>

                <button type="submit"
                    class="inline-flex items-center justify-center gap-2 bg-gradient-to-r from-red-600 to-red-500 hover:from-red-700 hover:to-red-600 text-white font-bold px-7 py-3.5 rounded-xl shadow-md hover:shadow-lg transition-all">
                    <i class="fa-solid fa-floppy-disk"></i>
                    Confirm & Finalize Changes
                </button>

            </div>
        </form>
    </div>

    <script>
        function validatePasswords() {
            const pass = document.getElementById('new_password').value;
            const confirm = document.getElementById('confirm_password').value;
            if (pass !== '' && pass !== confirm) {
                alert("Error: Confirm Password field must match New Password input field exactly!");
                return false;
            }
            return true;
        }

        document.getElementById("phone").addEventListener("input", function() {
            this.value = this.value.replace(/\D/g, '');
            if (this.value.length > 11) {
                this.value = this.value.substring(0, 11);
            }
        });

        document.querySelectorAll('.pregnancy-option').forEach(label => {
            const radio = label.querySelector('input[type="radio"]');
            if (!radio) return;
            radio.addEventListener('change', () => {
                document.querySelectorAll('.pregnancy-option').forEach(l => {
                    l.classList.remove('border-emerald-500', 'bg-emerald-50', 'border-amber-500', 'bg-amber-50');
                    l.classList.add('border-slate-200/80');
                });
                const active = radio.value === 'not_pregnant'
                    ? ['border-emerald-500', 'bg-emerald-50']
                    : ['border-amber-500', 'bg-amber-50'];
                label.classList.remove('border-slate-200/80');
                label.classList.add(...active);
            });
        });
    </script>
</body>

</html>