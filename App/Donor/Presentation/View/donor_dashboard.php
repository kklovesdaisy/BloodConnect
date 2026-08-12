<?php
// 1. Data Layer Integration (Replace with actual queries/sessions later)
$donor_name = $donor_name ?? '';
$short_name = $short_name ?? '';
$blood_group = $user['blood_group'] ?? '';
$blood_type_status = $blood_type_status ?? '';
$availability = $availability ?? '';
$availability_message = $availability_message ?? 'You are ready to donate';
$next_eligible_date = $next_eligible_date ?? '';
$last_donation_date = $last_donation_date ?? '';
$last_donation_location = $last_donation_location ?? '';
$blood_requests = $blood_requests ?? [];

$recent_activities = $recent_activities ?? [];
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Donor Dashboard - BloodConnect</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <style>
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
        }
    </style>
</head>

<body class="bg-slate-50 text-slate-800 antialiased min-h-screen flex">

    <div class="flex-1 flex flex-col min-h-screen">

        <main class="flex-1 p-4 sm:p-8 max-w-7xl w-full mx-auto space-y-8">

            <div>
                <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight">
                    Welcome, <span class="text-[#E63946]"> <?= htmlspecialchars($user['username'] ?? 'Donor') ?></span>
                    <span class="inline-block origin-bottom animate-[wave_2s_infinite]">👋</span>
                </h1>
                <p class="text-sm text-slate-500 mt-0.5">Here's your donor dashboard overview.</p>
            </div>

            <!-- Summary Cards Section -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 md:gap-6">

                <!-- Blood Group Card -->
                <div class="group relative bg-white rounded-2xl shadow-sm border border-slate-100 hover:border-rose-200 hover:shadow-lg hover:-translate-y-0.5 transition-all duration-300 ease-out overflow-hidden">
                    <div class="absolute inset-x-0 top-0 h-0.5 bg-gradient-to-r from-rose-400 to-rose-500 opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
                    <div class="p-5 sm:p-6">
                        <div class="flex items-start justify-between mb-3">
                            <div class="w-10 h-10 rounded-xl bg-rose-50 border border-rose-100 flex items-center justify-center text-rose-500 shrink-0 group-hover:scale-110 group-hover:bg-rose-100 transition-all duration-300">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 2.25c-5.385 4.897-9 9.53-9 13.313 0 4.418 3.582 8 8 8s8-3.582 8-8c0-3.782-3.615-8.416-9-13.313z" />
                                </svg>
                            </div>
                            <div class="text-right">
                                <p class="text-[10px] font-semibold text-slate-400 uppercase tracking-widest">Blood Group</p>
                                <p class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight mt-0.5">
                                    <?= htmlspecialchars($user['blood_group'] ?? 'N/A'); ?>
                                </p>
                            </div>
                        </div>
                        <div class="flex items-center justify-between pt-2 border-t border-slate-50">
                            <span class="text-xs font-medium text-slate-400">Type</span>
                            <span class="text-xs font-bold <?= str_contains(strtolower($blood_type_status ?? ''), 'positive') ? 'text-emerald-600' : 'text-rose-600' ?>"><?= htmlspecialchars($blood_type_status ?? 'Standard'); ?></span>
                        </div>
                    </div>
                </div>

                <!-- Availability Status Card -->
                <div class="group relative bg-white rounded-2xl shadow-sm border border-slate-100 hover:shadow-lg hover:-translate-y-0.5 transition-all duration-300 ease-out overflow-hidden">
                    <div class="absolute inset-x-0 top-0 h-0.5 <?= ($availability === 'Available' ? 'bg-gradient-to-r from-emerald-400 to-emerald-500 opacity-100' : 'bg-gradient-to-r from-rose-400 to-rose-500 opacity-100') ?>"></div>
                    <div class="p-5 sm:p-6">
                        <div class="flex items-start justify-between mb-3">
                            <div class="<?= ($availability === 'Available' ? 'bg-emerald-50 border-emerald-100 text-emerald-500' : 'bg-rose-50 border-rose-100 text-rose-500') ?> w-10 h-10 rounded-xl border flex items-center justify-center shrink-0 group-hover:scale-110 group-hover:bg-<?= ($availability === 'Available' ? 'emerald' : 'rose') ?>-100 transition-all duration-300">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                    <?php if ($availability === 'Available'): ?>
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    <?php else: ?>
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                                    <?php endif; ?>
                                </svg>
                            </div>
                            <div class="text-right">
                                <p class="text-[10px] font-semibold text-slate-400 uppercase tracking-widest">Availability</p>
                                <p class="text-2xl sm:text-3xl font-extrabold tracking-tight mt-0.5 <?= ($availability === 'Available' ? 'text-emerald-600' : 'text-rose-600') ?>"><?= htmlspecialchars($availability); ?></p>
                            </div>
                        </div>
                        <div class="pt-2 border-t border-slate-50 space-y-1">
                            <p class="text-xs text-slate-500 leading-tight"><?= htmlspecialchars($availability_message); ?></p>
                            <?php if (!empty($next_eligible_date)):
                                $remaining = floor((strtotime($next_eligible_date) - time()) / 86400);
                            ?>
                                <div class="flex items-center justify-between pt-1">
                                    <span class="text-[11px] font-semibold text-slate-400">Next eligible</span>
                                    <span class="text-[11px] font-bold text-amber-600 bg-amber-50 border border-amber-100 px-2 py-0.5 rounded-md"><?= max(0, $remaining) ?>d rem.</span>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Total Donations Card -->
                <a href="/BloodConnect/public/donor/history" class="group relative bg-white rounded-2xl shadow-sm border border-slate-100 hover:border-indigo-200 hover:shadow-lg hover:-translate-y-0.5 transition-all duration-300 ease-out overflow-hidden cursor-pointer">
                    <div class="absolute inset-x-0 top-0 h-0.5 bg-gradient-to-r from-indigo-400 to-indigo-500 opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
                    <div class="p-5 sm:p-6">
                        <div class="flex items-start justify-between mb-3">
                            <div class="w-10 h-10 rounded-xl bg-indigo-50 border border-indigo-100 flex items-center justify-center text-indigo-500 shrink-0 group-hover:scale-110 group-hover:bg-indigo-100 transition-all duration-300">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 7.5v3m0 0v3m0-3h3m-3 0h-3m-2.25-4.125a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zM4 19.235v-.11a6.375 6.375 0 0112.75 0v.109A12.318 12.318 0 0110.374 21c-2.331 0-4.512-.645-6.374-1.766z" />
                                </svg>
                            </div>
                            <div class="text-right">
                                <p class="text-[10px] font-semibold text-slate-400 uppercase tracking-widest">Donations</p>
                                <p class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight mt-0.5">
                                    <?= (int)($total_donations ?? 0) ?>
                                </p>
                            </div>
                        </div>
                        <div class="flex items-center justify-between pt-2 border-t border-slate-50">
                            <span class="text-xs font-medium text-slate-400">Total Contributions</span>
                            <span class="text-xs font-bold text-indigo-600">View History &rarr;</span>
                        </div>
                    </div>
                </a>

                <!-- Last Donation Card -->
                <div class="group relative bg-white rounded-2xl shadow-sm border border-slate-100 hover:border-amber-200 hover:shadow-lg hover:-translate-y-0.5 transition-all duration-300 ease-out overflow-hidden">
                    <div class="absolute inset-x-0 top-0 h-0.5 bg-gradient-to-r from-amber-400 to-amber-500 opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
                    <div class="p-5 sm:p-6">
                        <div class="flex items-start justify-between mb-3">
                            <div class="w-10 h-10 rounded-xl bg-amber-50 border border-amber-100 flex items-center justify-center text-amber-500 shrink-0 group-hover:scale-110 group-hover:bg-amber-100 transition-all duration-300">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5" />
                                </svg>
                            </div>
                            <div class="text-right">
                                <p class="text-[10px] font-semibold text-slate-400 uppercase tracking-widest">Last Donation</p>
                                <p class="text-lg sm:text-xl font-extrabold text-slate-900 tracking-tight mt-0.5 leading-tight"><?= htmlspecialchars($last_donation_date ?: 'None Record'); ?></p>
                            </div>
                        </div>
                        <div class="flex items-center justify-between pt-2 border-t border-slate-50">
                            <span class="text-xs font-medium text-slate-400">Location</span>
                            <span class="text-xs font-semibold text-slate-600 text-right max-w-[60%] truncate"><?= htmlspecialchars($last_donation_location ?: 'No location saved'); ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Matching Blood Requests Section -->
            <div class="space-y-4">
                <div class="flex items-center justify-between gap-4">
                    <div class="flex items-center gap-3">
                        <h3 class="text-base sm:text-xl font-extrabold text-slate-900 tracking-tight uppercase">Matching Blood Requests</h3>
                        <?php if (!empty($blood_requests)): ?>
                            <span class="inline-flex items-center justify-center min-w-6 h-6 px-1.5 rounded-full bg-rose-100 text-rose-600 text-[11px] font-black">
                                <?= count($blood_requests) ?>
                            </span>
                        <?php endif; ?>
                    </div>
                    <a href="/BloodConnect/public/donor/blood-requests"
                        class="inline-flex items-center gap-1 text-xs font-bold text-red-600 hover:text-red-700 hover:gap-2 transition-all duration-200 shrink-0">
                        View All <span aria-hidden="true">&rarr;</span>
                    </a>
                </div>

                <?php if (empty($blood_requests)): ?>
                    <!-- Empty state -->
                    <div class="bg-white border border-slate-100 rounded-2xl shadow-sm p-10 sm:p-12 text-center">
                        <div class="w-16 h-16 mx-auto rounded-full bg-rose-50 border border-rose-100 flex items-center justify-center text-3xl">
                            🩸
                        </div>
                        <h4 class="mt-4 text-base font-extrabold text-slate-800 tracking-tight">No matching requests</h4>
                        <p class="mt-1 text-sm text-slate-500">There are currently no blood requests matching your blood group.</p>
                        <p class="text-sm text-slate-500">We'll notify you when your blood type is needed.</p>
                        <a href="/BloodConnect/public/donor/blood-requests"
                            class="mt-5 inline-flex items-center justify-center gap-2 px-6 py-2.5 rounded-xl bg-rose-600 hover:bg-rose-700 active:scale-[0.98] text-white text-xs font-bold shadow-sm transition-all duration-200">
                            View All Requests
                        </a>
                    </div>
                <?php else: ?>
                    <div class="space-y-3">
                        <?php foreach ($blood_requests as $request): ?>
                            <?php
                            $urgency = strtolower((string)($request['urgency'] ?? ''));
                            $statusValue = strtolower((string)($request['status_name'] ?? $request['status'] ?? 'pending'));
                            $isAccepted = str_contains($statusValue, 'accepted') || (int)($request['status'] ?? 0) === 8;
                            $isCritical = str_contains($urgency, 'critical');
                            $isUrgent = str_contains($urgency, 'urgent');

                            $badgeBg = $isCritical
                                ? 'bg-red-600 text-white'
                                : ($isUrgent
                                    ? 'bg-orange-500 text-white'
                                    : 'bg-blue-600 text-white');

                            $daysToExpiry = !empty($request['expiry_date'])
                                ? floor((strtotime($request['expiry_date']) - time()) / 86400)
                                : null;
                            $timing = $daysToExpiry === 0
                                ? 'Needed Today'
                                : ($daysToExpiry !== null && $daysToExpiry <= 3
                                    ? 'Needed Soon'
                                    : 'Open Request');
                            $unitCount = max(1, (int)($request['unit'] ?? 1));
                            ?>
                            <div class="bg-white border border-slate-100 rounded-2xl shadow-sm p-5 transition-all duration-200 hover:shadow-md flex flex-col sm:flex-row sm:items-center justify-between gap-4 <?= $isCritical ? 'border-l-4 border-l-red-600' : ($isUrgent ? 'border-l-4 border-l-orange-500' : 'border-l-4 border-l-blue-600') ?>">
                                <div class="flex items-start gap-4 min-w-0">
                                    <div class="w-12 h-12 rounded-full bg-rose-50 text-rose-600 font-black flex items-center justify-center text-sm shrink-0 shadow-sm border border-rose-100">
                                        <?= htmlspecialchars($request['blood_group_needed'] ?? 'N/A') ?>
                                    </div>
                                    <div class="min-w-0 space-y-1">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <span class="text-[10px] font-black uppercase tracking-widest px-2.5 py-1 rounded-full <?= $badgeBg ?>">
                                                <?= htmlspecialchars(strtoupper((string)($request['urgency'] ?? 'Normal'))) ?>
                                            </span>
                                            <h4 class="font-bold text-slate-900 text-sm sm:text-base">
                                                <?= htmlspecialchars($request['blood_group_needed'] ?? '') ?> Blood Required
                                            </h4>
                                        </div>
                                        <p class="text-sm text-slate-500 flex items-center gap-1.5">
                                            <span class="inline-block">🏥</span>
                                            <?= htmlspecialchars($request['hospital_name'] ?? 'Not specified') ?>
                                        </p>
                                        <p class="text-xs font-semibold text-slate-400">
                                            <?= $unitCount ?> bag<?= $unitCount > 1 ? 's' : '' ?> required
                                            <span class="text-slate-300 mx-1">&bull;</span>
                                            <?= htmlspecialchars($timing) ?>
                                        </p>
                                    </div>
                                </div>

                                <div class="flex items-center gap-2.5 shrink-0">
                                    <a href="/BloodConnect/public/donor/blood-request/<?= (int)$request['request_id'] ?>"
                                        class="inline-flex items-center justify-center px-4 py-2 rounded-xl border border-slate-200 text-slate-700 text-xs font-bold hover:bg-slate-50 hover:border-slate-300 transition-all duration-200 active:scale-[0.98]">
                                        View
                                    </a>
                                    <?php if ($isAccepted): ?>
                                        <span class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-emerald-50 text-emerald-700 text-xs font-bold border border-emerald-100">
                                            ✓ Processed
                                        </span>
                                    <?php else: ?>
                                        <form method="POST" action="/BloodConnect/public/donor/request/accept" class="inline">
                                            <input type="hidden" name="request_id" value="<?= (int)$request['request_id'] ?>">
                                            <button type="submit"
                                                class="inline-flex items-center justify-center px-4 py-2 rounded-xl bg-rose-600 hover:bg-rose-700 text-white text-xs font-bold shadow-sm transition-all duration-200 active:scale-[0.98]">
                                                Respond
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- My Impact -->
            <div class="relative bg-white border border-slate-100 rounded-2xl shadow-sm overflow-hidden">
                <div class="absolute inset-x-0 top-0 h-0.5 bg-gradient-to-r from-[#E63946] to-[#ce2424] opacity-100"></div>
                <div class="relative p-6 sm:p-8">
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                        <h3 class="text-base sm:text-xl font-extrabold text-slate-900 tracking-tight uppercase">My Impact</h3>
                        <p class="text-xs sm:text-sm font-semibold <?= $impact_donations > 0 ? 'text-[#E63946]' : 'text-slate-500' ?>">
                            <?= $impact_donations > 0
                                ? 'Thank you for helping save lives! ❤️'
                                : 'Start your journey as a blood donor.' ?>
                        </p>
                    </div>

                    <div class="mt-6 grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <div class="bg-slate-50/80 border border-slate-100 rounded-2xl p-5 text-center">
                            <p class="mono text-3xl sm:text-4xl font-black tracking-tight text-slate-900"><?= number_format($impact_donations) ?></p>
                            <p class="mt-1 text-[10px] font-bold text-slate-400 uppercase tracking-widest">
                                <?= $impact_donations === 1 ? 'Donation' : 'Donations' ?>
                            </p>
                        </div>
                        <div class="bg-slate-50/80 border border-slate-100 rounded-2xl p-5 text-center">
                            <p class="mono text-3xl sm:text-4xl font-black tracking-tight text-slate-900"><?= number_format($impact_bags) ?></p>
                            <p class="mt-1 text-[10px] font-bold text-slate-400 uppercase tracking-widest">
                                <?= $impact_bags === 1 ? 'Blood Bag' : 'Blood Bags' ?>
                            </p>
                        </div>
                        <div class="bg-slate-50/80 border border-slate-100 rounded-2xl p-5 text-center">
                            <p class="mono text-3xl sm:text-4xl font-black tracking-tight text-slate-900"><?= number_format($impact_requests_supported) ?></p>
                            <p class="mt-1 text-[10px] font-bold text-slate-400 uppercase tracking-widest">
                                <?= $impact_requests_supported === 1 ? 'Request Supported' : 'Requests Supported' ?>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</body>

</html>