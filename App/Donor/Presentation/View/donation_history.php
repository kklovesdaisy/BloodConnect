<?php
$requests = $requests ?? [];

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

$historyCount = count($requests);
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
                <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight leading-none">Accepted History</h1>
                <p class="text-xs sm:text-sm text-slate-400 font-medium mt-1.5">Review the requests you accepted and view the patient details.</p>
            </div>
        </div>

        <div class="bg-[#111625] rounded-xl px-4 py-2.5 flex items-center justify-between gap-6 self-start sm:self-auto shadow-md">
            <div>
                <span class="text-[9px] uppercase tracking-wider font-bold text-slate-400 block">Total Logs</span>
                <div class="flex items-baseline gap-1 mt-0.5">
                    <span class="text-white font-black text-lg font-mono leading-none"><?= $historyCount ?></span>
                    <span class="text-[10px] text-slate-500 font-medium">records</span>
                </div>
            </div>
            <div class="w-2 h-2 rounded-full bg-emerald-500 shadow-xs shadow-emerald-500/50 animate-pulse shrink-0"></div>
        </div>
    </div>

    <div class="space-y-3">
        <?php if (empty($requests)): ?>
            <div class="bg-white border border-slate-200/70 rounded-2xl p-12 text-center text-slate-400 font-medium animate-fade-in-up">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-8 h-8 text-slate-300 mx-auto mb-2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 13.5h3.86a2.25 2.25 0 0 1 2.008 1.24l.885 1.77a2.25 2.25 0 0 0 2.007 1.24h1.98a2.25 2.25 0 0 0 2.007-1.24l.885-1.77a2.25 2.25 0 0 1 2.007-1.24h3.86m-18 0h18" />
                </svg>
                No accepted requests found yet.
            </div>
        <?php else: ?>

            <div class="hidden lg:block space-y-3">
                <?php foreach ($requests as $index => $request):
                    $rid = $request['request_id'] ?? 0;
                    $code = $request['request_code'] ?? 'N/A';
                    $created_at = $request['created_at'] ?? date('Y-m-d H:i');
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
                                <span class="text-[10px] font-bold uppercase text-slate-400 tracking-wider font-mono block"><?= htmlspecialchars((string)$code) ?></span>
                                <span class="font-bold text-slate-800 text-sm tracking-tight truncate block mt-0.5"><?= htmlspecialchars((string)($request['hospital_name'] ?? '-')) ?></span>
                                <span class="text-xs text-slate-400 font-medium block truncate">Pt: <?= htmlspecialchars((string)($request['patient_name'] ?? 'Patient')) ?></span>
                            </div>
                        </div>

                        <div class="col-span-3 min-w-0 pl-2">
                            <span class="text-slate-500 text-xs font-semibold block truncate">Hospital Location Details</span>
                            <span class="text-[10px] text-slate-400 font-bold tracking-wider uppercase block mt-0.5">Assigned Facility</span>
                        </div>

                        <div class="col-span-3 text-slate-500 pl-2">
                            <span class="text-xs font-semibold block text-slate-700 truncate"><?= date('M d, Y', strtotime($created_at)) ?></span>
                            <span class="text-[10px] text-slate-400 block mt-0.5 font-mono"><?= date('H:i', strtotime($created_at)) ?></span>
                        </div>

                        <div class="col-span-1 flex justify-center">
                            <span class="inline-flex items-center justify-center px-2.5 py-1 rounded-lg border font-black text-xs shadow-3xs font-mono <?= getHistoryBloodGroupStyle($request['blood_group_needed'] ?? '-') ?>">
                                <?= htmlspecialchars((string)($request['blood_group_needed'] ?? '-')) ?>
                            </span>
                        </div>

                        <div class="col-span-2 flex items-center justify-end gap-2 pr-2">
                            <a href="/BloodConnect/public/donor/donation/certificate/view?id=<?= (int)$rid ?>"
                                class="px-4 py-2 text-xs font-bold border border-red-200 text-red-600 hover:bg-red-50 rounded-xl transition-all active:scale-95 shadow-3xs">
                                Certificate
                            </a>
                            <a href="/BloodConnect/public/donor/history/view?id=<?= (int)$rid ?>"
                                class="px-4 py-2 text-xs font-bold bg-red-600 border border-red-600 text-white hover:bg-red-700 rounded-xl transition-all active:scale-95 shadow-3xs">
                                Inspect Logs
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="block lg:hidden space-y-3.5">
                <?php foreach ($requests as $index => $request):
                    $rid = $request['request_id'] ?? 0;
                    $code = $request['request_code'] ?? 'N/A';
                    $created_at = $request['created_at'] ?? date('Y-m-d H:i');
                ?>
                    <div class="bg-white border border-slate-200 rounded-2xl p-5 space-y-4 shadow-3xs opacity-0 animate-fade-in-up transform transition-all duration-200 active:scale-[0.99]" style="animation-delay: <?= $index * 0.04 ?>s;">

                        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                            <div class="flex items-center gap-2">
                                <span class="px-2 py-0.5 text-[9px] font-extrabold rounded-md uppercase tracking-wider bg-slate-100 text-slate-600 border border-slate-200 font-mono">
                                    <?= htmlspecialchars((string)$code) ?>
                                </span>
                            </div>
                            <span class="text-[10px] text-slate-400 font-mono font-bold"><?= date('Y-m-d H:i', strtotime($created_at)) ?></span>
                        </div>

                        <div class="flex items-start justify-between gap-4">
                            <div class="space-y-1 truncate">
                                <h4 class="font-extrabold text-slate-900 text-base tracking-tight truncate leading-tight"><?= htmlspecialchars((string)($request['hospital_name'] ?? '-')) ?></h4>
                                <p class="text-xs font-medium text-slate-600 truncate">Patient: <?= htmlspecialchars((string)($request['patient_name'] ?? 'Patient')) ?></p>
                                <p class="text-[10px] text-slate-400 font-bold uppercase tracking-wider pt-1">Accepted Entry Record</p>
                            </div>
                            <div class="w-11 h-11 rounded-xl border font-black text-sm flex items-center justify-center font-mono shrink-0 <?= getHistoryBloodGroupStyle($request['blood_group_needed'] ?? '-') ?>">
                                <?= htmlspecialchars((string)($request['blood_group_needed'] ?? '-')) ?>
                            </div>
                        </div>

                        <div class="pt-1 flex flex-col gap-2">
                            <a href="/BloodConnect/public/donor/donation/certificate/view?id=<?= (int)$rid ?>"
                                class="flex items-center justify-center gap-2 py-2.5 px-4 bg-white border border-red-200 text-red-600 hover:bg-red-50 rounded-xl font-bold text-xs transition-all active:scale-95 shadow-3xs w-full">
                                <i class="fa-solid fa-award"></i> View Certificate
                            </a>
                            <a href="/BloodConnect/public/donor/history/view?id=<?= (int)$rid ?>"
                                class="flex items-center justify-center gap-2 py-2.5 px-4 bg-red-600 border border-red-600 text-white hover:bg-red-700 rounded-xl font-bold text-xs transition-all active:scale-95 shadow-3xs w-full">
                                Inspect Full Ticket History
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

        <?php endif; ?>
    </div>
</div>