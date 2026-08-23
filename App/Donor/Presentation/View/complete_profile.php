<?php
$errors = $errors ?? [];
$old = $old ?? [];
$donorDetails = $donorDetails ?? [];
$eligibility = $eligibility ?? null;
$isUpdate = $isUpdate ?? false;
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
</style>

<div class="max-w-2xl mx-auto px-4 sm:px-6 py-8 sm:py-12 animate-in">
    <div class="bg-white rounded-3xl border border-slate-200/70 p-6 sm:p-8 lg:p-10 shadow-sm">
        <!-- Header -->
        <div class="text-center mb-8">
            <div class="w-16 h-16 rounded-2xl bg-rose-50 border border-rose-100 flex items-center justify-center text-rose-500 mx-auto mb-4">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-8 h-8">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z" />
                </svg>
            </div>
            <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight">
                <?= $isUpdate ? 'Update Donor Profile' : 'Complete Your Donor Profile' ?>
            </h1>
            <p class="text-sm text-slate-500 mt-1.5 max-w-md mx-auto">
                <?= $isUpdate
                    ? 'Update your age, weight and pregnancy status below.'
                    : 'Please provide your details so we can verify your donor eligibility.' ?>
            </p>
        </div>

        <?php if (!empty($errors['form'])): ?>
            <div class="mb-6 px-4 py-3 bg-red-50 border border-red-200 rounded-xl text-sm font-semibold text-red-700">
                <?= htmlspecialchars($errors['form']) ?>
            </div>
        <?php endif; ?>

        <?php if ($eligibility && !$eligibility['eligible']): ?>
            <div class="mb-6 px-4 py-3 bg-amber-50 border border-amber-200 rounded-xl text-sm text-amber-800">
                <p class="font-bold mb-1">Eligibility Notice</p>
                <ul class="list-disc list-inside space-y-0.5 text-amber-700">
                    <?php foreach ($eligibility['reasons'] as $reason): ?>
                        <li><?= htmlspecialchars($reason) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form method="POST" action="" class="space-y-6">
            <!-- Age -->
            <?php
            $storedDob = trim((string)($donorDetails['date_of_birth'] ?? ''));
            $prefillAge = (string)($old['age'] ?? '');
            if ($prefillAge === '' && $storedDob !== '') {
                try {
                    $prefillAge = (string)(new \DateTime($storedDob))->diff(new \DateTime())->y;
                } catch (\Exception $e) {
                    $prefillAge = '';
                }
            }
            ?>
            <div>
                <label for="age" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                    Age (years)
                </label>
                <input type="number" name="age" id="age" min="1" max="120" required
                    value="<?= htmlspecialchars($prefillAge) ?>"
                    placeholder="e.g. 25"
                    class="profile-input w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-sm text-slate-900 placeholder-slate-400 <?= isset($errors['age']) ? 'border-red-400 bg-red-50' : '' ?>">
                <?php if (isset($errors['age'])): ?>
                    <p class="text-xs text-red-500 mt-1 font-medium"><?= $errors['age'] ?></p>
                <?php endif; ?>
                <p class="text-xs text-slate-400 mt-1">Must be between 18 and 65 years old for blood donation eligibility.</p>
            </div>

            <!-- Weight -->
            <div>
                <label for="weight" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                    Weight (kg)
                </label>
                <div class="relative">
                    <input type="number" name="weight" id="weight" step="0.1" min="1" max="300"
                        value="<?= htmlspecialchars($old['weight'] ?? $donorDetails['weight'] ?? '') ?>"
                        placeholder="e.g. 70"
                        class="profile-input w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-sm text-slate-900 placeholder-slate-400 <?= isset($errors['weight']) ? 'border-red-400 bg-red-50' : '' ?>">
                    <span class="absolute right-4 top-1/2 -translate-y-1/2 text-sm font-medium text-slate-400">kg</span>
                </div>
                <?php if (isset($errors['weight'])): ?>
                    <p class="text-xs text-red-500 mt-1 font-medium"><?= $errors['weight'] ?></p>
                <?php endif; ?>
                <p class="text-xs text-slate-400 mt-1">Minimum 50 kg is required for blood donation eligibility.</p>
            </div>

            <!-- Pregnancy Status -->
            <?php
            $selectedPregnancy = (string)($old['pregnancy_status'] ?? '');
            if ($selectedPregnancy === '') {
                $selectedPregnancy = !empty($donorDetails['is_pregnant']) ? 'pregnant' : '';
            }
            ?>
            <div>
                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                    Pregnancy Status
                </label>
                <div class="grid grid-cols-2 gap-4">
                    <label class="pregnancy-option cursor-pointer border-2 rounded-xl px-3 py-2.5 text-center transition-all <?= $selectedPregnancy === 'not_pregnant' ? 'border-emerald-500 bg-emerald-50' : 'border-slate-200' ?>">
                        <input type="radio" name="pregnancy_status" value="not_pregnant"
                            class="hidden pregnancy-radio" <?= $selectedPregnancy === 'not_pregnant' ? 'checked' : '' ?> required>
                        <span class="block text-sm font-bold text-slate-900">Not Pregnant</span>
                        <span class="block text-[9px] font-bold text-slate-500 mt-0.5 uppercase tracking-wider">Eligible to donate</span>
                    </label>
                    <label class="pregnancy-option cursor-pointer border-2 rounded-xl px-3 py-2.5 text-center transition-all <?= $selectedPregnancy === 'pregnant' ? 'border-amber-500 bg-amber-50' : 'border-slate-200' ?>">
                        <input type="radio" name="pregnancy_status" value="pregnant"
                            class="hidden pregnancy-radio" <?= $selectedPregnancy === 'pregnant' ? 'checked' : '' ?>>
                        <span class="block text-sm font-bold text-slate-900">Pregnant</span>
                        <span class="block text-[9px] font-bold text-slate-500 mt-0.5 uppercase tracking-wider">Cannot donate</span>
                    </label>
                </div>
                <?php if (isset($errors['pregnancy_status'])): ?>
                    <p class="text-xs text-red-500 mt-1 font-medium"><?= $errors['pregnancy_status'] ?></p>
                <?php endif; ?>
            </div>

            <!-- State/Region + Township -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label for="state_region" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                        State / Region
                    </label>
                    <input type="text" name="state_region" id="state_region"
                        value="<?= htmlspecialchars($old['state_region'] ?? $donorDetails['state_region'] ?? '') ?>"
                        placeholder="e.g. Yangon Region"
                        class="profile-input w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-sm text-slate-900 placeholder-slate-400">
                </div>
                <div>
                    <label for="township" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                        Township
                    </label>
                    <input type="text" name="township" id="township"
                        value="<?= htmlspecialchars($old['township'] ?? $donorDetails['township'] ?? '') ?>"
                        placeholder="e.g. Hlaingthaya"
                        class="profile-input w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-sm text-slate-900 placeholder-slate-400">
                </div>
            </div>

            <div class="pt-2">
                <button type="submit"
                    class="w-full sm:w-auto px-8 py-3.5 bg-[#ce2424] text-white font-bold text-sm rounded-xl shadow-md shadow-rose-200 hover:shadow-lg hover:-translate-y-0.5 transition-all duration-200 flex items-center justify-center gap-2.5">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-4 h-4">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <?= $isUpdate ? 'Save & Continue' : 'Save & Continue' ?>
                </button>
            </div>
        </form>
    </div>

    <!-- Eligibility Info -->
    <div class="mt-6 bg-white rounded-2xl border border-slate-200/70 p-5 sm:p-6 shadow-sm">
        <h3 class="text-sm font-bold text-slate-900 tracking-tight mb-3">Donor Eligibility Requirements</h3>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <div class="flex items-start gap-3">
                <div class="w-8 h-8 rounded-lg bg-rose-50 flex items-center justify-center text-rose-500 shrink-0 mt-0.5">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z" />
                    </svg>
                </div>
                <div>
                    <p class="text-xs font-bold text-slate-700">Age</p>
                    <p class="text-xs text-slate-500">Must be between 18 and 65 years old</p>
                </div>
            </div>
            <div class="flex items-start gap-3">
                <div class="w-8 h-8 rounded-lg bg-rose-50 flex items-center justify-center text-rose-500 shrink-0 mt-0.5">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v2.25m6.364.386l-1.591 1.591M21 12h-2.25m-.386 6.364l-1.591-1.591M12 18.75V21m-4.773-4.227l-1.591 1.591M5.25 12H3m4.227-4.773L5.636 5.636M15.75 12a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0z" />
                    </svg>
                </div>
                <div>
                    <p class="text-xs font-bold text-slate-700">Weight</p>
                    <p class="text-xs text-slate-500">Minimum 50 kg required</p>
                </div>
            </div>
            <div class="flex items-start gap-3">
                <div class="w-8 h-8 rounded-lg bg-rose-50 flex items-center justify-center text-rose-500 shrink-0 mt-0.5">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                    </svg>
                </div>
                <div>
                    <p class="text-xs font-bold text-slate-700">Pregnancy</p>
                    <p class="text-xs text-slate-500">Pregnant donors are not eligible</p>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    document.querySelectorAll('.pregnancy-option').forEach(label => {
        const radio = label.querySelector('input[type="radio"]');
        if (!radio) return;
        radio.addEventListener('change', () => {
            document.querySelectorAll('.pregnancy-option').forEach(l => {
                l.classList.remove('border-emerald-500', 'bg-emerald-50', 'border-amber-500', 'bg-amber-50');
                l.classList.add('border-slate-200');
            });
            const active = radio.value === 'not_pregnant'
                ? ['border-emerald-500', 'bg-emerald-50']
                : ['border-amber-500', 'bg-amber-50'];
            label.classList.remove('border-slate-200');
            label.classList.add(...active);
        });
    });

    document.querySelector('.pregnancy-radio:checked')?.dispatchEvent(new Event('change'));
</script>
