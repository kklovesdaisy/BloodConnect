<?php
$user = $user ?? [];
$profile = $profile ?? [];
$eligibility = $eligibility ?? [];

$bloodGroup = trim((string)($user['blood_group'] ?? ''));
$available = (bool)($eligibility['is_available'] ?? false);
$nextEligible = (string)($eligibility['next_eligible_date'] ?? '');
$cooldownDate = (string)($eligibility['next_available_date'] ?? '');

$flashMessage = \App\Shared\Helpers\Session::get('flash_message', '');
$flashStatus = \App\Shared\Helpers\Session::get('flash_status', '');
\App\Shared\Helpers\Session::remove('flash_message');
\App\Shared\Helpers\Session::remove('flash_status');
?>
<style>
    .profile-input {
        transition: all 0.2s ease;
    }
    .profile-input:focus {
        border-color: #ce2424;
        box-shadow: 0 0 0 4px rgba(206,36,36,0.08);
        outline: none;
    }
    @keyframes fadeUp {
        0% { opacity: 0; transform: translateY(20px); }
        100% { opacity: 1; transform: translateY(0); }
    }
    .animate-in {
        animation: fadeUp 0.5s cubic-bezier(0.16, 1, 0.3, 1) forwards;
    }
    .donate-option {
        transition: all 0.2s ease;
    }
    .donate-option:hover {
        border-color: #ce2424;
        background: #fef2f2;
    }
</style>

<div class="max-w-2xl mx-auto px-4 sm:px-6 py-8 sm:py-12 animate-in">
    <div class="bg-white rounded-3xl border border-slate-200/70 p-6 sm:p-8 lg:p-10 shadow-sm">
        <!-- Header -->
        <div class="text-center mb-8">
            <div class="w-16 h-16 rounded-2xl bg-rose-50 border border-rose-100 flex items-center justify-center text-rose-500 mx-auto mb-4">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-8 h-8">
                    <path d="M12 2.5C12 2.5 5 9.5 5 14.5C5 18.37 8.13 21.5 12 21.5C15.87 21.5 19 18.37 19 14.5C19 9.5 12 2.5 12 2.5Z" />
                </svg>
            </div>
            <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight">Donate Blood</h1>
            <p class="text-sm text-slate-500 mt-1.5 max-w-md mx-auto">
                Record a standalone donation to add blood directly to central stock.
            </p>
        </div>

        <?php if ($flashMessage !== ''): ?>
            <div class="mb-6 px-4 py-3 rounded-xl text-sm font-semibold <?= $flashStatus === 'error' ? 'bg-red-50 border border-red-200 text-red-700' : 'bg-emerald-50 border border-emerald-200 text-emerald-700' ?>">
                <?= htmlspecialchars($flashMessage) ?>
            </div>
        <?php endif; ?>

        <?php if ($bloodGroup === ''): ?>
            <div class="mb-6 px-4 py-3 bg-amber-50 border border-amber-200 rounded-xl text-sm text-amber-800">
                <p class="font-bold mb-1">Blood group not set</p>
                <p class="text-amber-700">Please set your blood group in
                    <a href="/BloodConnect/public/donor/complete-profile" class="underline font-semibold">your profile</a>
                    before recording a donation.</p>
            </div>
        <?php elseif (!$available): ?>
            <div class="mb-6 px-4 py-3 bg-amber-50 border border-amber-200 rounded-xl text-sm text-amber-800">
                <p class="font-bold mb-1">Not eligible to donate right now</p>
                <?php if ($nextEligible !== ''): ?>
                    <p class="text-amber-700">You will be eligible again on <span class="font-semibold"><?= htmlspecialchars($nextEligible) ?></span>.</p>
                <?php elseif ($cooldownDate !== ''): ?>
                    <p class="text-amber-700">You will be eligible again on <span class="font-semibold"><?= htmlspecialchars($cooldownDate) ?></span>.</p>
                <?php else: ?>
                    <p class="text-amber-700">Please review your profile eligibility.</p>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="/BloodConnect/public/donor/donate" class="space-y-6">
            <!-- Blood Group -->
            <div>
                <label for="blood_group" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                    Your Blood Group
                </label>
                <input type="text" name="blood_group" id="blood_group"
                    value="<?= htmlspecialchars($bloodGroup) ?>"
                    readonly
                    class="profile-input w-full px-4 py-3 bg-slate-100 border border-slate-200 rounded-xl text-sm text-slate-700">
            </div>

            <!-- Bags -->
            <div>
                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                    Number of Bags
                </label>
                <div class="grid grid-cols-2 gap-4">
                    <?php foreach ([1, 2] as $bagOption): ?>
                        <label class="donate-option cursor-pointer border-2 border-slate-200 rounded-2xl p-4 text-center <?= $bagOption === 1 ? 'border-[#ce2424] bg-rose-50' : '' ?>">
                            <input type="radio" name="bags" value="<?= $bagOption ?>"
                                class="hidden donate-bag-option" <?= $bagOption === 1 ? 'checked' : '' ?>>
                            <span class="block text-3xl font-extrabold text-slate-900"><?= $bagOption ?></span>
                            <span class="block text-xs font-bold text-slate-500 mt-1 uppercase tracking-wider">Bag<?= $bagOption > 1 ? 's' : '' ?></span>
                        </label>
                    <?php endforeach; ?>
                </div>
                <p class="text-xs text-slate-400 mt-2">You may donate up to 2 bags in a single donation.</p>
            </div>

            <button type="submit"
                <?= ($bloodGroup === '' || !$available) ? 'disabled' : '' ?>
                class="w-full py-3.5 px-4 rounded-xl font-bold text-base text-white transition-all duration-200 <?= ($bloodGroup === '' || !$available) ? 'bg-slate-300 cursor-not-allowed' : 'bg-[#ce2424] hover:bg-red-700 shadow-sm hover:shadow-md' ?>">
                Record Donation
            </button>
        </form>
    </div>
</div>

<script>
    document.querySelectorAll('.donate-option').forEach(label => {
        const radio = label.querySelector('input[type="radio"]');
        if (!radio) return;
        radio.addEventListener('change', () => {
            document.querySelectorAll('.donate-option').forEach(l => {
                l.classList.remove('border-[#ce2424]', 'bg-rose-50');
                l.classList.add('border-slate-200');
            });
            label.classList.add('border-[#ce2424]', 'bg-rose-50');
            label.classList.remove('border-slate-200');
        });
    });
</script>
