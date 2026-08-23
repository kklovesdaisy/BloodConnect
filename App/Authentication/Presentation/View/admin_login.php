<?php
$basePath = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));
if ($basePath === '/' || $basePath === '\\') {
    $basePath = '';
}
$errors = $_SESSION['errors'] ?? [];
$success = $_SESSION['success'] ?? '';
$old = $_SESSION['old'] ?? [];
unset($_SESSION['errors'], $_SESSION['success'], $_SESSION['old']);
?>

<!-- FontAwesome & Base Styling Dependencies -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<?php $recaptchaSiteKey = getenv('RECAPTCHA_SITE_KEY') ?: ''; ?>
<?php if ($recaptchaSiteKey): ?>
<script src="https://www.google.com/recaptcha/api.js" async defer></script>
<?php endif; ?>

<!-- ============ ADMIN CONSOLE LOGIN (DARK) ============ -->
<section class="min-h-screen bg-slate-950 relative overflow-hidden flex items-center justify-center px-4 py-10">

    <!-- Ambient Glow Layers -->
    <div class="absolute -top-32 -left-32 w-96 h-96 bg-red-600/20 rounded-full blur-3xl pointer-events-none"></div>
    <div class="absolute -bottom-40 -right-24 w-[28rem] h-[28rem] bg-red-900/20 rounded-full blur-3xl pointer-events-none"></div>
    <div class="absolute inset-0 opacity-[0.04] pointer-events-none select-none"
        style="background-image: linear-gradient(#fff 1px, transparent 1px), linear-gradient(90deg, #fff 1px, transparent 1px); background-size: 44px 44px;"></div>

    <div class="relative z-10 w-full max-w-md">
        <!-- Brand Row -->
        <div class="flex flex-col items-center mb-8">
            <div class="w-16 h-16 rounded-2xl bg-red-600 shadow-lg shadow-red-600/30 flex items-center justify-center mb-4">
                <i class="fa-solid fa-droplet text-white text-3xl"></i>
            </div>
            <h1 class="text-2xl font-black text-white tracking-tight">BloodConnect</h1>
            <p class="text-xs font-bold text-slate-500 uppercase tracking-[0.25em] mt-1.5">Administrator Console</p>
        </div>

        <!-- Login Card -->
        <div class="bg-slate-900 border border-slate-800 rounded-3xl p-8 shadow-2xl shadow-black/50">
            <div class="flex items-center gap-3 mb-6">
                <div class="w-10 h-10 rounded-xl bg-red-600/15 border border-red-600/30 text-red-500 flex items-center justify-center shrink-0">
                    <i class="fa-solid fa-user-shield"></i>
                </div>
                <div>
                    <h2 class="text-lg font-black text-white leading-tight">Admin Sign In</h2>
                    <p class="text-xs text-slate-500 font-medium">Restricted area — authorised staff only</p>
                </div>
            </div>

            <!-- Success Alert banner -->
            <?php if (!empty($success)): ?>
                <div class="mb-5 p-3.5 bg-emerald-500/10 border border-emerald-500/30 rounded-xl">
                    <p class="text-emerald-400 text-sm font-semibold"><?= htmlspecialchars($success, ENT_QUOTES) ?></p>
                </div>
            <?php endif; ?>

            <!-- Backend Form-Wide Errors Banner -->
            <?php if (!empty($errors) && isset($errors['form'])): ?>
                <div class="mb-5 p-3.5 bg-red-500/10 border border-red-500/30 rounded-xl">
                    <p class="text-red-400 text-sm font-semibold flex items-center gap-2">
                        <i class="fa-solid fa-circle-exclamation text-xs"></i> <?= htmlspecialchars($errors['form'], ENT_QUOTES) ?>
                    </p>
                </div>
            <?php endif; ?>

            <form id="adminLoginForm" novalidate method="post" action="<?= $basePath ?>/admin/login" class="space-y-5">

                <input type="hidden" name="context" value="admin">

                <!-- Username / Email -->
                <div class="space-y-1.5">
                    <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider">Email or Username</label>
                    <div class="relative">
                        <span class="absolute left-4 top-1/2 -translate-y-1/2 text-red-500 pointer-events-none">
                            <i class="fa-regular fa-user"></i>
                        </span>
                        <input type="text" id="loginId" name="login" placeholder="Enter admin email or username"
                            value="<?= htmlspecialchars($old['login'] ?? '', ENT_QUOTES) ?>" required autocomplete="username"
                            class="w-full bg-slate-950 border border-slate-700 rounded-xl pl-11 pr-4 py-3 text-sm text-white placeholder-slate-600 focus:outline-none focus:border-red-600 focus:ring-4 focus:ring-red-600/10 transition duration-200 font-medium">
                    </div>
                    <p class="hidden text-red-400 text-xs font-semibold mt-1 items-center gap-1.5" id="loginIdError">
                        <i class="fa-solid fa-circle-exclamation"></i> Please enter your email or username.
                    </p>
                    <?php if (!empty($errors) && isset($errors['login'])): ?>
                        <p class="text-red-400 text-xs font-semibold mt-1 flex items-center gap-1.5">
                            <i class="fa-solid fa-circle-exclamation"></i> <?= htmlspecialchars($errors['login'], ENT_QUOTES) ?>
                        </p>
                    <?php endif; ?>
                </div>

                <!-- Password -->
                <div class="space-y-1.5">
                    <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider">Password</label>
                    <div class="relative">
                        <span class="absolute left-4 top-1/2 -translate-y-1/2 text-red-500 pointer-events-none">
                            <i class="fa-solid fa-lock"></i>
                        </span>
                        <input type="password" id="loginPassword" name="password" placeholder="Enter password" required autocomplete="current-password"
                            class="w-full bg-slate-950 border border-slate-700 rounded-xl pl-11 pr-12 py-3 text-sm text-white placeholder-slate-600 focus:outline-none focus:border-red-600 focus:ring-4 focus:ring-red-600/10 transition duration-200 font-medium">
                        <button type="button" id="togglePassword" class="absolute right-4 top-1/2 -translate-y-1/2 text-slate-500 hover:text-slate-300 transition px-1">
                            <i class="fa-regular fa-eye" id="eyeIcon"></i>
                        </button>
                    </div>
                    <p class="hidden text-red-400 text-xs font-semibold mt-1 items-center gap-1.5" id="loginPasswordError">
                        <i class="fa-solid fa-circle-exclamation"></i> Please enter your password.
                    </p>
                    <?php if (!empty($errors) && isset($errors['password'])): ?>
                        <p class="text-red-400 text-xs font-semibold mt-1 flex items-center gap-1.5">
                            <i class="fa-solid fa-circle-exclamation"></i> <?= htmlspecialchars($errors['password'], ENT_QUOTES) ?>
                        </p>
                    <?php endif; ?>
                </div>

                <!-- reCAPTCHA v2 Widget -->
                <?php if ($recaptchaSiteKey): ?>
                <div class="flex flex-col items-center gap-2">
                    <div class="g-recaptcha" data-sitekey="<?= htmlspecialchars($recaptchaSiteKey, ENT_QUOTES) ?>" data-theme="dark"></div>
                    <p class="hidden text-red-400 text-xs font-semibold" id="recaptchaError">
                        <i class="fa-solid fa-circle-exclamation"></i> Please check the "I'm not a robot" box.
                    </p>
                </div>
                <?php endif; ?>

                <button type="submit"
                    class="w-full bg-red-600 hover:bg-red-700 text-white font-bold py-3.5 px-6 rounded-xl shadow-lg shadow-red-600/25 active:scale-[0.99] transition duration-150 flex items-center justify-center gap-2">
                    <i class="fa-solid fa-right-to-bracket"></i> Sign in to Console
                </button>

                <div class="flex items-center justify-between pt-1 text-xs font-semibold">
                    <a href="<?= $basePath ?>/login" class="text-slate-500 hover:text-slate-300 transition">
                        Regular login
                    </a>
                    <a href="<?= $basePath ?>/forgot-password" class="text-red-500 hover:text-red-400 transition">
                        Forgot password?
                    </a>
                </div>
            </form>
        </div>

        <p class="text-center text-[11px] text-slate-600 font-medium mt-6">
            <i class="fa-solid fa-shield-halved mr-1"></i> All sign-in activity is logged and monitored.
        </p>
    </div>
</section>

<!-- Front-End Interactive Form Controller Validation -->
<script>
    const adminLoginForm = document.getElementById("adminLoginForm");
    const togglePasswordBtn = document.getElementById("togglePassword");
    const passwordInput = document.getElementById("loginPassword");
    const eyeIcon = document.getElementById("eyeIcon");

    if (togglePasswordBtn && passwordInput && eyeIcon) {
        togglePasswordBtn.addEventListener("click", () => {
            const isPassword = passwordInput.type === "password";
            passwordInput.type = isPassword ? "text" : "password";
            eyeIcon.className = isPassword ? "fa-regular fa-eye-slash" : "fa-regular fa-eye";
        });
    }

    if (adminLoginForm) {
        adminLoginForm.addEventListener("submit", function(e) {
            const id = document.getElementById("loginId");
            const password = document.getElementById("loginPassword");
            const idError = document.getElementById("loginIdError");
            const passwordError = document.getElementById("loginPasswordError");

            const isIdInvalid = !id?.value.trim();
            const isPasswordInvalid = !password?.value.trim();

            if (id) {
                id.classList.toggle("border-red-500", isIdInvalid);
                idError?.classList.toggle("hidden", !isIdInvalid);
                idError?.classList.toggle("flex", isIdInvalid);
            }

            if (password) {
                password.classList.toggle("border-red-500", isPasswordInvalid);
                passwordError?.classList.toggle("hidden", !isPasswordInvalid);
                passwordError?.classList.toggle("flex", isPasswordInvalid);
            }

            if (isIdInvalid || isPasswordInvalid) {
                e.preventDefault();
                return;
            }

            const recaptchaResponse = document.getElementById("g-recaptcha-response");
            const recaptchaError = document.getElementById("recaptchaError");
            if (recaptchaResponse && !recaptchaResponse.value) {
                e.preventDefault();
                recaptchaError?.classList.remove("hidden");
            } else {
                recaptchaError?.classList.add("hidden");
            }
        });
    }
</script>
