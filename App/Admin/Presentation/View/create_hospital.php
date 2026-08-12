<?php
$errors = $errors ?? [];
$old = $old ?? [];
$formError = $errors['form'] ?? '';
?>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

<style>
    body {
        font-family: 'Plus Jakarta Sans', sans-serif;
    }
    .profile-input {
        transition: all 0.2s ease;
    }
    .profile-input:focus {
        border-color: #ce2424;
        box-shadow: 0 0 0 4px rgba(206,36,36,0.08);
        outline: none;
    }
</style>

<div class="max-w-3xl mx-auto animate-fade-in">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight">Create Hospital Account</h1>
            <p class="text-sm text-slate-500 mt-1">Hospitals can log in to request blood bags from the central stock.</p>
        </div>
        <a href="/BloodConnect/public/admin/hospitals"
            class="inline-flex items-center gap-2 px-4 py-2.5 bg-white border border-slate-200 rounded-xl text-sm font-bold text-slate-600 hover:bg-slate-50 transition-colors">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4">
                <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" />
            </svg>
            Back to Hospitals
        </a>
    </div>

    <?php if ($formError !== ''): ?>
        <div class="mb-6 px-4 py-3 bg-red-50 border border-red-200 rounded-xl text-sm font-semibold text-red-700">
            <?= htmlspecialchars($formError) ?>
        </div>
    <?php endif; ?>

    <div class="bg-white rounded-3xl border border-slate-200/70 p-6 sm:p-8 shadow-sm">
        <form method="POST" action="/BloodConnect/public/admin/hospitals/create" class="space-y-6">
            <div>
                <label for="hospital_name" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Hospital Name</label>
                <input type="text" name="hospital_name" id="hospital_name" required
                    value="<?= htmlspecialchars($old['hospital_name'] ?? '') ?>"
                    placeholder="e.g. Yangon General Hospital"
                    class="profile-input w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-sm text-slate-900">
            </div>

            <div class="grid sm:grid-cols-2 gap-6">
                <div>
                    <label for="username" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Username</label>
                    <input type="text" name="username" id="username" required
                        value="<?= htmlspecialchars($old['username'] ?? '') ?>"
                        placeholder="Login username"
                        class="profile-input w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-sm text-slate-900">
                </div>
                <div>
                    <label for="email" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Email</label>
                    <input type="email" name="email" id="email" required
                        value="<?= htmlspecialchars($old['email'] ?? '') ?>"
                        placeholder="Credentials will be emailed here"
                        class="profile-input w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-sm text-slate-900">
                </div>
            </div>

            <div class="grid sm:grid-cols-2 gap-6">
                <div>
                    <label for="phone" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Phone</label>
                    <input type="text" name="phone" id="phone"
                        value="<?= htmlspecialchars($old['phone'] ?? '') ?>"
                        placeholder="Contact number"
                        class="profile-input w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-sm text-slate-900">
                </div>
                <div>
                    <label for="password" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Temporary Password</label>
                    <input type="text" name="password" id="password" required
                        value="<?= htmlspecialchars($old['password'] ?? '') ?>"
                        placeholder="At least 6 characters"
                        class="profile-input w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-sm text-slate-900">
                    <p class="text-xs text-slate-400 mt-1">Share this securely; the hospital should change it after first login.</p>
                </div>
            </div>

            <div class="grid sm:grid-cols-2 gap-6">
                <div>
                    <label for="license_no" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">License No.</label>
                    <input type="text" name="license_no" id="license_no"
                        value="<?= htmlspecialchars($old['license_no'] ?? '') ?>"
                        class="profile-input w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-sm text-slate-900">
                </div>
                <div>
                    <label for="address" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Address</label>
                    <input type="text" name="address" id="address"
                        value="<?= htmlspecialchars($old['address'] ?? '') ?>"
                        class="profile-input w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-sm text-slate-900">
                </div>
            </div>

            <div class="grid sm:grid-cols-2 gap-6">
                <div>
                    <label for="state_region" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">State / Region</label>
                    <input type="text" name="state_region" id="state_region"
                        value="<?= htmlspecialchars($old['state_region'] ?? '') ?>"
                        class="profile-input w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-sm text-slate-900">
                </div>
                <div>
                    <label for="township" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Township</label>
                    <input type="text" name="township" id="township"
                        value="<?= htmlspecialchars($old['township'] ?? '') ?>"
                        class="profile-input w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-sm text-slate-900">
                </div>
            </div>

            <button type="submit"
                class="w-full py-3.5 px-4 rounded-xl font-bold text-base text-white bg-[#ce2424] hover:bg-red-700 shadow-sm hover:shadow-md transition-all duration-200">
                Create Hospital Account
            </button>
        </form>
    </div>
</div>
