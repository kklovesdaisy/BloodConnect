<?php
$profile = $profile ?? [];
$user = $user ?? [];

if (!function_exists('hospitalFieldRow')) {
    function hospitalFieldRow(string $label, string $value): string
    {
        return '<div class="flex items-start justify-between gap-6 py-3.5 border-b border-slate-100 last:border-0">'
            . '<span class="text-xs font-bold uppercase tracking-wider text-slate-400 pt-0.5 shrink-0">' . htmlspecialchars($label) . '</span>'
            . '<span class="text-sm font-semibold text-slate-800 text-right">' . htmlspecialchars($value !== '' ? $value : '—') . '</span>'
            . '</div>';
    }
}
?>
<style>
    @keyframes fadeInUp { from { opacity: 0; transform: translateY(12px); } to { opacity: 1; transform: translateY(0); } }
    .animate-fade-in-up { animation: fadeInUp .4s cubic-bezier(.16,1,.3,1) forwards; }
</style>

<div class="max-w-3xl mx-auto space-y-6">

    <div class="bg-white border border-slate-200/60 rounded-2xl p-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 shadow-xs">
        <div class="flex items-center gap-4">
            <div class="w-12 h-12 bg-[#ce2424] text-white rounded-2xl flex items-center justify-center shadow-lg shadow-red-600/10 shrink-0">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z" /></svg>
            </div>
            <div>
                <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight leading-none">Hospital Profile</h1>
                <p class="text-xs sm:text-sm text-slate-400 font-medium mt-1.5">Your organisation details and contact information.</p>
            </div>
        </div>
        <a href="/BloodConnect/public/hospital/profile/update"
            class="inline-flex items-center gap-2 px-5 py-2.5 bg-[#ce2424] text-white text-sm font-bold rounded-xl hover:bg-[#b81f1f] transition-all active:scale-95 shadow-md shadow-red-600/20 self-start sm:self-auto">
            Edit Profile
        </a>
    </div>

    <div class="bg-white border border-slate-200/60 rounded-2xl p-6 sm:p-8 shadow-xs animate-fade-in-up" style="animation-delay:.05s">
        <div class="flex items-center gap-4 pb-4">
            <div class="w-16 h-16 rounded-2xl bg-red-50 border border-red-100 flex items-center justify-center font-black text-2xl text-[#ce2424] uppercase">
                <?= htmlspecialchars(mb_substr(trim((string)($profile['hospital_name'] ?? $user['username'] ?? 'H')), 0, 1)) ?>
            </div>
            <div>
                <h2 class="text-lg font-extrabold text-slate-900 tracking-tight"><?= htmlspecialchars((string)($profile['hospital_name'] ?? 'Hospital')) ?></h2>
                <p class="text-xs text-slate-400 font-medium">Hospital account</p>
            </div>
        </div>

        <?= hospitalFieldRow('Hospital Name', (string)($profile['hospital_name'] ?? '')) ?>
        <?= hospitalFieldRow('License No', (string)($profile['license_no'] ?? '')) ?>
        <?= hospitalFieldRow('State / Region', (string)($profile['state_region'] ?? '')) ?>
        <?= hospitalFieldRow('Township', (string)($profile['township'] ?? '')) ?>
        <?= hospitalFieldRow('Address', (string)($profile['address'] ?? '')) ?>
        <?= hospitalFieldRow('Phone', (string)($profile['phone'] ?? '')) ?>
        <?= hospitalFieldRow('Email', (string)($user['email'] ?? '')) ?>
    </div>
</div>
