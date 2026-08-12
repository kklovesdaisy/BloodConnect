<?php
$donations = $donations ?? [];
$total_donations = (int)($total_donations ?? count($donations));
$total_bags = (int)($total_bags ?? 0);
$last_donation_date = $last_donation_date ?? 'No donation yet';
$completed_status_id = (int)($completed_status_id ?? 0);

// Blood Type Helper Function matching your design ecosystem
if (!function_exists('getHistoryBloodGroupStyle')) {
    function getHistoryBloodGroupStyle($type)
    {
        $type = strtolower((string)$type);
        if (str_contains($type, 'negative') || str_contains($type, '-')) {
            return 'text-rose-600 bg-rose-50 border-rose-200/40';
        }
        return 'text-emerald-600 bg-emerald-50 border-emerald-200/40';
    }
}

if (!function_exists('getDonationStatusStyle')) {
    function getDonationStatusStyle($status)
    {
        $status = strtolower((string)$status);
        if (str_contains($status, 'completed')) {
            return 'text-emerald-600 bg-emerald-50 border-emerald-200/40';
        }
        if (str_contains($status, 'accepted')) {
            return 'text-amber-600 bg-amber-50 border-amber-200/40';
        }
        return 'text-slate-500 bg-slate-50 border-slate-200/40';
    }
}

$historyCount = count($donations);
?>
<style>
    @keyframes fadeInUp {
        from {
            opacity: 0;
            transform: translateY(12px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .animate-fade-in-up {
        animation: fadeInUp 0.4s cubic-bezier(0.16, 1, 0.3, 1) forwards;
    }
</style>

<div class="max-w-7xl mx-auto p-4 sm:p-6 lg:p-8 min-h-screen" id="donationHistoryContainer">

    <div class="relative bg-white border border-slate-200/60 rounded-2xl p-6 mb-8 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-6 shadow-xs">
        <div class="flex items-center gap-4">
            <div class="w-12 h-12 bg-[#ce2424] text-white rounded-2xl flex items-center justify-center shadow-lg shadow-red-600/10 shrink-0">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-6 h-6 text-white">
                    <path d="M12 2.69l5.66 5.66a8 8 0 1 1-11.31 0z" />
                </svg>
            </div>
            <div>
                <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight leading-none">Donation History</h1>
                <p class="text-xs sm:text-sm text-slate-400 font-medium mt-1.5">Review every donation you have made to BloodConnect and your contribution summary.</p>
            </div>
        </div>

        <div class="bg-[#111625] rounded-xl px-4 py-2.5 flex items-center justify-between gap-6 self-start sm:self-auto shadow-md">
            <div>
                <span class="text-[9px] uppercase tracking-wider font-bold text-slate-400 block">Total Donations</span>
                <div class="flex items-baseline gap-1 mt-0.5">
                    <span class="text-white font-black text-lg font-mono leading-none"><?= $total_donations ?></span>
                    <span class="text-[10px] text-slate-500 font-medium">records</span>
                </div>
            </div>
            <div class="w-2 h-2 rounded-full bg-emerald-500 shadow-xs shadow-emerald-500/50 animate-pulse shrink-0"></div>
        </div>
    </div>

    <!-- Donation Summary Card -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-8">
        <div class="bg-white rounded-2xl border border-slate-200/70 p-5 shadow-2xs flex items-center gap-4 animate-fade-in-up">
            <div class="w-11 h-11 rounded-xl bg-red-50 border border-red-100 text-red-500 flex items-center justify-center shrink-0">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="w-5 h-5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 7.5v3m0 0v3m0-3h3m-3 0h-3m-2.25-4.125a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zM4 19.235v-.11a6.375 6.375 0 0112.75 0v.109A12.318 12.318 0 0110.374 21c-2.331 0-4.512-.645-6.374-1.766z" />
                </svg>
            </div>
            <div>
                <span class="text-[10px] font-bold uppercase tracking-widest text-slate-400 block">Total Donations</span>
                <span class="text-2xl font-extrabold text-slate-900 tracking-tight mono"><?= $total_donations ?></span>
                <span class="text-[11px] text-slate-400 font-medium ml-1">times</span>
            </div>
        </div>

        <div class="bg-white rounded-2xl border border-slate-200/70 p-5 shadow-2xs flex items-center gap-4 animate-fade-in-up" style="animation-delay: 0.05s">
            <div class="w-11 h-11 rounded-xl bg-indigo-50 border border-indigo-100 text-indigo-500 flex items-center justify-center shrink-0">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="w-5 h-5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 6.375c0 2.278-3.694 4.125-8.25 4.125S3.75 8.653 3.75 6.375m16.5 0c0-2.278-3.694-4.125-8.25-4.125S3.75 4.097 3.75 6.375m16.5 0v11.25c0 2.278-3.694 4.125-8.25 4.125s-8.25-1.847-8.25-4.125V6.375m16.5 5.625c0 2.278-3.694 4.125-8.25 4.125s-8.25-1.847-8.25-4.125" />
                </svg>
            </div>
            <div>
                <span class="text-[10px] font-bold uppercase tracking-widest text-slate-400 block">Total Bags Donated</span>
                <span class="text-2xl font-extrabold text-slate-900 tracking-tight mono"><?= $total_bags ?></span>
                <span class="text-[11px] text-slate-400 font-medium ml-1">bags</span>
            </div>
        </div>

        <div class="bg-white rounded-2xl border border-slate-200/70 p-5 shadow-2xs flex items-center gap-4 animate-fade-in-up" style="animation-delay: 0.1s">
            <div class="w-11 h-11 rounded-xl bg-amber-50 border border-amber-100 text-amber-500 flex items-center justify-center shrink-0">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="w-5 h-5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5" />
                </svg>
            </div>
            <div>
                <span class="text-[10px] font-bold uppercase tracking-widest text-slate-400 block">Last Donation</span>
                <span class="text-xl sm:text-2xl font-extrabold text-slate-900 tracking-tight mono leading-tight"><?= htmlspecialchars($last_donation_date) ?></span>
            </div>
        </div>
    </div>

    <div class="space-y-3">
        <?php if (empty($donations)): ?>
            <div class="bg-white border border-slate-200/70 rounded-2xl p-12 text-center text-slate-400 font-medium animate-fade-in-up">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-8 h-8 text-slate-300 mx-auto mb-2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 13.5h3.86a2.25 2.25 0 0 1 2.008 1.24l.885 1.77a2.25 2.25 0 0 0 2.007 1.24h1.98a2.25 2.25 0 0 0 2.007-1.24l.885-1.77a2.25 2.25 0 0 1 2.007-1.24h3.86m-18 0h18" />
                </svg>
                No donations recorded yet.
            </div>
        <?php else: ?>

            <div class="hidden lg:block space-y-3">
                <?php foreach ($donations as $index => $donation):
                    $donationId = (int)($donation['donation_id'] ?? 0);
                    $requestId = (int)($donation['request_id'] ?? 0);
                    $donationDate = !empty($donation['donation_date']) ? (string)$donation['donation_date'] : date('Y-m-d');
                    $bags = (int)($donation['bags'] ?? 1);
                    $status = $donation['status_label'] ?? ($donation['status'] ?? '');
                    $bloodGroup = (string)($donation['blood_group'] ?? $donation['blood_group_needed'] ?? '-');
                    $location = !empty($donation['hospital_name']) ? (string)$donation['hospital_name'] : 'Central Stock';
                    $isRequestLinked = $requestId > 0;
                    $isCompleted = (int)($donation['status'] ?? 0) === (int)($completed_status_id ?? 0);
                ?>
                    <div class="bg-white border border-slate-200/70 rounded-2xl p-4 grid grid-cols-12 items-center gap-4 opacity-0 animate-fade-in-up shadow-2xs transition-all duration-200 hover:border-slate-300" style="animation-delay: <?= $index * 0.04 ?>s;">

                        <div class="col-span-3 flex items-center gap-4 min-w-0">
                            <div class="relative shrink-0">
                                <div class="w-11 h-11 rounded-xl bg-red-50 border border-red-100 text-red-500 flex items-center justify-center">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-5 h-5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                                    </svg>
                                </div>
                            </div>
                            <div class="truncate">
                                <span class="text-[10px] font-bold uppercase text-slate-400 tracking-wider font-mono block">Donated on</span>
                                <span class="font-bold text-slate-800 text-sm tracking-tight truncate block mt-0.5"><?= date('M d, Y', strtotime($donationDate)) ?></span>
                                <?php if ($isRequestLinked): ?>
                                    <span class="text-xs text-slate-400 font-medium block truncate"><?= htmlspecialchars((string)($donation['request_code'] ?? 'Request #' . $requestId)) ?></span>
                                <?php else: ?>
                                    <span class="text-xs text-slate-400 font-medium block truncate">Standalone Donation</span>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="col-span-2 min-w-0 pl-2 flex items-center gap-3">
                            <span class="inline-flex items-center justify-center px-2.5 py-1 rounded-lg border font-black text-xs shadow-3xs font-mono <?= getHistoryBloodGroupStyle($bloodGroup) ?>">
                                <?= htmlspecialchars($bloodGroup) ?>
                            </span>
                        </div>

                        <div class="col-span-2 text-slate-500 pl-2">
                            <span class="text-lg font-extrabold text-slate-900 mono"><?= $bags ?></span>
                            <span class="text-xs text-slate-400 font-semibold"> bag<?= $bags > 1 ? 's' : '' ?></span>
                        </div>

                        <div class="col-span-3 text-slate-500 pl-2 min-w-0">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-md border font-bold text-[10px] uppercase tracking-wider shadow-3xs <?= getDonationStatusStyle($status) ?>">
                                <?= htmlspecialchars((string)($status ?: 'Completed')) ?>
                            </span>
                            <span class="text-xs text-slate-500 font-semibold block mt-1 truncate"><?= htmlspecialchars($location) ?></span>
                        </div>

                        <div class="col-span-2 flex items-center justify-end gap-2 pr-2">
                            <?php if ($isCompleted): ?>
                                <a href="/BloodConnect/public/donor/donation/certificate/view?id=<?= $donationId ?>"
                                    class="px-4 py-2 text-xs font-bold border border-red-200 text-red-600 hover:bg-red-50 rounded-xl transition-all active:scale-95 shadow-3xs">
                                    Certificate
                                </a>
                            <?php endif; ?>
                            <?php if ($isRequestLinked): ?>
                                <a href="/BloodConnect/public/donor/history/view?id=<?= $requestId ?>"
                                    class="px-4 py-2 text-xs font-bold bg-red-600 border border-red-600 text-white hover:bg-red-700 rounded-xl transition-all active:scale-95 shadow-3xs">
                                    Inspect Logs
                                </a>
                            <?php else: ?>
                                <span class="inline-flex items-center px-3 py-2 text-xs font-bold bg-slate-50 border border-slate-200 text-slate-500 rounded-xl">
                                    Central Stock
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="block lg:hidden space-y-3.5">
                <?php foreach ($donations as $index => $donation):
                    $donationId = (int)($donation['donation_id'] ?? 0);
                    $requestId = (int)($donation['request_id'] ?? 0);
                    $donationDate = !empty($donation['donation_date']) ? (string)$donation['donation_date'] : date('Y-m-d');
                    $bags = (int)($donation['bags'] ?? 1);
                    $status = $donation['status_label'] ?? ($donation['status'] ?? '');
                    $bloodGroup = (string)($donation['blood_group'] ?? $donation['blood_group_needed'] ?? '-');
                    $location = !empty($donation['hospital_name']) ? (string)$donation['hospital_name'] : 'Central Stock';
                    $isRequestLinked = $requestId > 0;
                    $isCompleted = (int)($donation['status'] ?? 0) === (int)($completed_status_id ?? 0);
                ?>
                    <div class="bg-white border border-slate-200 rounded-2xl p-5 space-y-4 shadow-3xs opacity-0 animate-fade-in-up transform transition-all duration-200 active:scale-[0.99]" style="animation-delay: <?= $index * 0.04 ?>s;">

                        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                            <div class="flex items-center gap-2">
                                <span class="inline-flex items-center justify-center px-2.5 py-1 rounded-lg border font-black text-xs shadow-3xs font-mono <?= getHistoryBloodGroupStyle($bloodGroup) ?>">
                                    <?= htmlspecialchars($bloodGroup) ?>
                                </span>
                                <span class="px-2 py-0.5 text-[9px] font-extrabold rounded-md uppercase tracking-wider bg-slate-100 text-slate-600 border border-slate-200 font-mono">
                                    <?= $bags ?> bag<?= $bags > 1 ? 's' : '' ?>
                                </span>
                            </div>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-md border font-bold text-[10px] uppercase tracking-wider shadow-3xs <?= getDonationStatusStyle($status) ?>">
                                <?= htmlspecialchars((string)($status ?: 'Completed')) ?>
                            </span>
                        </div>

                        <div class="flex items-start justify-between gap-4">
                            <div class="space-y-1 truncate">
                                <h4 class="font-extrabold text-slate-900 text-base tracking-tight truncate leading-tight"><?= date('M d, Y', strtotime($donationDate)) ?></h4>
                                <p class="text-xs font-medium text-slate-600 truncate"><?= htmlspecialchars($location) ?></p>
                                <p class="text-[10px] text-slate-400 font-bold uppercase tracking-wider pt-1">
                                    <?= $isRequestLinked
                                        ? htmlspecialchars((string)($donation['request_code'] ?? 'Request record'))
                                        : 'Standalone Donation Record' ?>
                                </p>
                            </div>
                            <div class="w-11 h-11 rounded-xl border font-black text-sm flex items-center justify-center font-mono shrink-0 <?= getHistoryBloodGroupStyle($bloodGroup) ?>">
                                <?= htmlspecialchars($bloodGroup) ?>
                            </div>
                        </div>

                        <div class="pt-1 flex flex-col gap-2">
                            <?php if ($isCompleted): ?>
                            <a href="/BloodConnect/public/donor/donation/certificate/view?id=<?= $donationId ?>"
                                class="flex items-center justify-center gap-2 py-2.5 px-4 bg-white border border-red-200 text-red-600 hover:bg-red-50 rounded-xl font-bold text-xs transition-all active:scale-95 shadow-3xs w-full">
                                <i class="fa-solid fa-award"></i> View Certificate
                            </a>
                            <?php endif; ?>
                            <?php if ($isRequestLinked): ?>
                                <a href="/BloodConnect/public/donor/history/view?id=<?= $requestId ?>"
                                    class="flex items-center justify-center gap-2 py-2.5 px-4 bg-red-600 border border-red-600 text-white hover:bg-red-700 rounded-xl font-bold text-xs transition-all active:scale-95 shadow-3xs w-full">
                                    Inspect Full Ticket History
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

        <?php endif; ?>
    </div>
</div>
