<?php
$requests = $requests ?? [];
$user = $user ?? [];
$bloodGroup = $blood_group ?? '';

// Severity / Urgency Mapping Class
function getSeverityStyle($urgency)
{
    return match (strtoupper((string)$urgency)) {
        'CRITICAL' => 'bg-rose-50 text-rose-600 border-rose-200/50',
        'URGENT'   => 'bg-orange-50 text-orange-600 border-orange-200/50',
        default    => 'bg-slate-50 text-slate-500 border-slate-200/50',
    };
}

// Fulfillment Status Class
function getFulfillmentStyle($status, $statusCode = 0)
{
    $status = strtolower((string)$status);
    if (str_contains($status, 'accepted') || (int)$statusCode === 8) {
        return 'bg-emerald-50 text-emerald-700 border-emerald-200/50';
    } elseif (str_contains($status, 'assigned') || (int)$statusCode === 42) {
        return 'bg-violet-50 text-violet-700 border-violet-200/50';
    } elseif (str_contains($status, 'declined') || (int)$statusCode === 47) {
        return 'bg-rose-50 text-rose-700 border-rose-200/50';
    } elseif (str_contains($status, 'completed') || (int)$statusCode === 9) {
        return 'bg-blue-50 text-blue-700 border-blue-200/50';
    } elseif (str_contains($status, 'expired') || (int)$statusCode === 48) {
        return 'bg-slate-100 text-slate-500 border-slate-200/50';
    }
    return 'bg-amber-50 text-amber-700 border-amber-200/50';
}

// Blood Type Class Matcher
function getBloodGroupStyle($type)
{
    $type = strtolower((string)$type);
    if (str_contains($type, 'negative') || str_contains($type, '-')) {
        return 'text-rose-600 bg-rose-50 border-rose-200/40';
    }
    return 'text-emerald-600 bg-emerald-50 border-emerald-200/40';
}

// Safely count open requests
$activeCount = count($requests);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Blood Requests | BloodConnect</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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
</head>

<body class="bg-slate-50 min-h-screen text-slate-800 tracking-tight antialiased">
    <div class="max-w-7xl mx-auto p-4 sm:p-6 lg:p-8 min-h-screen">

        <div class="relative bg-white border border-slate-200/60 rounded-2xl p-6 mb-8 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-6 shadow-xs">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 bg-[#ce2424] text-white rounded-2xl flex items-center justify-center shadow-lg shadow-red-600/10 shrink-0">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-6 h-6 text-white">
                        <path d="M12 2.69l5.66 5.66a8 8 0 1 1-11.31 0z" />
                    </svg>
                </div>
                <div>
                    <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight leading-none">Blood Requests</h1>
                    <p class="text-xs sm:text-sm text-slate-400 font-medium mt-1.5">
                        Matching requests for group <span class="font-bold text-red-600 bg-red-50/80 px-1.5 py-0.5 rounded-md border border-red-100"><?= htmlspecialchars($bloodGroup) ?></span>.
                    </p>
                </div>
            </div>

            <div class="flex flex-wrap items-center gap-4 self-start sm:self-auto">
                <div class="bg-[#111625] rounded-xl px-4 py-2.5 flex items-center justify-between gap-6 shadow-md">
                    <div>
                        <span class="text-[9px] uppercase tracking-wider font-bold text-slate-400 block">Active Registry</span>
                        <div class="flex items-baseline gap-1 mt-0.5">
                            <span class="text-white font-black text-lg font-mono leading-none"><?= $activeCount ?></span>
                            <span class="text-[10px] text-slate-500 font-medium">nodes</span>
                        </div>
                    </div>
                    <div class="w-2 h-2 rounded-full bg-rose-500 shadow-xs shadow-rose-500/50 animate-pulse shrink-0"></div>
                </div>

                <a href="/BloodConnect/public/donor/dashboard" class="inline-flex items-center gap-2 rounded-xl border border-slate-200/80 bg-white px-4 py-2.5 text-xs font-bold text-slate-600 hover:bg-slate-50 shadow-3xs transition-all active:scale-95">
                    <i class="fa-solid fa-arrow-left"></i> Dashboard
                </a>
            </div>
        </div>

        <?php if (!empty($message)): ?>
            <div class="mb-5 p-3.5 rounded-xl flex items-center gap-3 border transition-all duration-300 animate-fade-in-up <?= $status === 'success' ? 'bg-emerald-50 border-emerald-200 text-emerald-700' : 'bg-rose-50 border-rose-200 text-rose-700' ?>">
                <i class="fa-solid <?= $status === 'success' ? 'fa-circle-check' : 'fa-circle-exclamation' ?> text-base"></i>
                <span class="text-xs font-semibold"><?= htmlspecialchars($message) ?></span>
            </div>
        <?php endif; ?>

        <div class="space-y-3">
            <?php if (empty($requests)): ?>
                <div class="bg-white border border-slate-200/70 rounded-2xl p-12 text-center text-slate-400 font-medium animate-fade-in-up">
                    <i class="fa-solid fa-folder-open text-2xl block text-slate-300 mb-2"></i>
                    No matching blood requests found at this moment.
                </div>
            <?php else: ?>

                <div class="hidden lg:block space-y-3">
                    <?php foreach ($requests as $index => $request):
                        $rid = $request['request_id'] ?? $request['id'] ?? 0;
                        $statusName = $request['status_name'] ?? $request['status'] ?? 'Pending';
                        $statusCode = $request['status'] ?? 0;
                    ?>
                        <div class="bg-white border border-slate-200/70 rounded-2xl p-4 grid grid-cols-12 items-center gap-4 opacity-0 animate-fade-in-up shadow-2xs transition-all duration-200 hover:border-slate-300" style="animation-delay: <?= $index * 0.04 ?>s;">

                            <div class="col-span-3 flex items-center gap-4 min-w-0">
                                <div class="relative shrink-0">
                                    <div class="w-11 h-11 rounded-xl bg-red-50 border border-red-100 text-red-500 flex items-center justify-center">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-5 h-5">
                                            <path d="M12 2.69l5.66 5.66a8 8 0 1 1-11.31 0z" />
                                        </svg>
                                    </div>
                                </div>
                                <div class="truncate">
                                    <span class="text-[10px] font-bold uppercase text-slate-400 tracking-wider font-mono block">REQ___<?= str_pad($rid, 4, '0', STR_PAD_LEFT) ?></span>
                                    <span class="font-bold text-slate-800 text-sm tracking-tight truncate block mt-0.5"><?= htmlspecialchars($request['hospital_name'] ?? '-') ?></span>
                                    <span class="text-xs text-slate-400 font-medium block truncate">Pt: <?= htmlspecialchars($request['patient_name'] ?? 'Unknown') ?></span>
                                </div>
                            </div>

                            <div class="col-span-2 min-w-0 pl-2">
                                <span class="text-slate-800 font-extrabold tracking-tight text-sm block truncate"><?= (int)($request['unit'] ?? 0) ?> Units</span>
                                <span class="text-[10px] text-slate-400 font-bold tracking-wider uppercase block mt-0.5">Volume Demand</span>
                            </div>

                            <div class="col-span-2 flex justify-start pl-2">
                                <span class="inline-block px-3 py-1 text-[9px] font-extrabold rounded-lg uppercase tracking-widest border shadow-3xs <?= getSeverityStyle($request['urgency'] ?? 'Routine') ?>">
                                    <?= htmlspecialchars(strtoupper((string)($request['urgency'] ?? 'Routine'))) ?>
                                </span>
                            </div>

                            <div class="col-span-2 flex flex-col items-start gap-1 pl-2">
                                <span class="inline-block px-3 py-1 text-[9px] font-extrabold rounded-lg uppercase tracking-widest border shadow-3xs <?= getFulfillmentStyle($statusName, $statusCode) ?>">
                                    <?= htmlspecialchars(ucwords((string)$statusName)) ?>
                                </span>
                                <?php if ((int)$statusCode === 7 && !empty($request['expiry_date'])):
                                    $donExpiryDiff = strtotime($request['expiry_date']) - time();
                                    if ($donExpiryDiff > 0):
                                        $donDays = floor($donExpiryDiff / 86400);
                                        $donHours = floor(($donExpiryDiff % 86400) / 3600);
                                ?>
                                    <span class="text-[10px] font-medium text-amber-500"><i class="fa-regular fa-clock"></i> Expires in <?= $donDays > 0 ? $donDays . 'd ' . $donHours . 'h' : $donHours . 'h' ?></span>
                                <?php endif; endif; ?>
                            </div>

                            <div class="col-span-1 flex justify-center">
                                <span class="inline-flex items-center justify-center px-2.5 py-1 rounded-lg border font-black text-xs shadow-3xs font-mono <?= getBloodGroupStyle($request['blood_group_needed'] ?? '-') ?>">
                                    <?= htmlspecialchars($request['blood_group_needed'] ?? '-') ?>
                                </span>
                            </div>

                            <div class="col-span-2 flex items-center justify-end pr-2">
                                <a href="/BloodConnect/public/donor/blood-request/<?= (int)$rid ?>"
                                    class="px-4 py-2 text-xs font-bold bg-red-600 border border-red-600 text-white hover:bg-red-700 rounded-xl transition-all active:scale-95 shadow-3xs">
                                    <i class="fa-solid fa-eye mr-1"></i> Inspect
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="block lg:hidden space-y-3.5">
                    <?php foreach ($requests as $index => $request):
                        $rid = $request['request_id'] ?? $request['id'] ?? 0;
                        $statusName = $request['status_name'] ?? $request['status'] ?? 'Pending';
                        $statusCode = $request['status'] ?? 0;
                    ?>
                        <div class="bg-white border border-slate-200 rounded-2xl p-5 space-y-4 shadow-3xs opacity-0 animate-fade-in-up transform transition-all duration-200 active:scale-[0.99]" style="animation-delay: <?= $index * 0.04 ?>s;">

                            <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                                <div class="flex items-center gap-2.5">
                                    <span class="px-2 py-0.5 text-[9px] font-extrabold rounded-md uppercase tracking-wider border <?= getSeverityStyle($request['urgency'] ?? 'Routine') ?>">
                                        <?= htmlspecialchars(strtoupper((string)($request['urgency'] ?? 'Routine'))) ?>
                                    </span>
                                    <span class="inline-flex items-center gap-1 text-[10px] font-bold text-slate-400 font-mono">
                                        REG_ID: <?= htmlspecialchars($rid) ?>
                                    </span>
                                </div>
                                <span class="inline-block px-2.5 py-1 text-[9px] font-extrabold rounded-md uppercase tracking-widest border <?= getFulfillmentStyle($statusName, $statusCode) ?>">
                                    <?= htmlspecialchars(ucwords((string)$statusName)) ?>
                                </span>
                            </div>

                            <div class="flex items-start justify-between gap-4">
                                <div class="space-y-1 truncate">
                                    <h4 class="font-extrabold text-slate-900 text-base tracking-tight truncate leading-tight"><?= htmlspecialchars($request['hospital_name'] ?? '-') ?></h4>
                                    <p class="text-xs font-medium text-slate-600 truncate">Patient: <?= htmlspecialchars($request['patient_name'] ?? 'Unknown') ?></p>
                                    <p class="text-xs text-slate-400 font-bold pt-1"><i class="fa-solid fa-droplet text-red-500 mr-1"></i><?= (int)($request['unit'] ?? 0) ?> Units Required</p>
                                    <?php if ((int)$statusCode === 7 && !empty($request['expiry_date'])):
                                        $donMobileExpiry = strtotime($request['expiry_date']) - time();
                                        if ($donMobileExpiry > 0):
                                            $donMobileDays = floor($donMobileExpiry / 86400);
                                            $donMobileHours = floor(($donMobileExpiry % 86400) / 3600);
                                    ?>
                                        <p class="text-xs text-amber-500 font-semibold"><i class="fa-regular fa-clock mr-1"></i>Expires in <?= $donMobileDays > 0 ? $donMobileDays . 'd ' . $donMobileHours . 'h' : $donMobileHours . 'h' ?></p>
                                    <?php endif; endif; ?>
                                </div>
                                <div class="w-11 h-11 rounded-xl border font-black text-sm flex items-center justify-center font-mono shrink-0 <?= getBloodGroupStyle($request['blood_group_needed'] ?? '-') ?>">
                                    <?= htmlspecialchars($request['blood_group_needed'] ?? '-') ?>
                                </div>
                            </div>

                            <div class="pt-1">
                                <a href="/BloodConnect/public/donor/blood-request/<?= (int)$rid ?>"
                                    class="flex items-center justify-center gap-2 py-2.5 px-4 bg-red-600 border border-red-600 text-white hover:bg-red-700 rounded-xl font-bold text-xs transition-all active:scale-95 shadow-3xs w-full">
                                    <i class="fa-solid fa-eye text-white"></i> Inspect Ticket Requirements
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

            <?php endif; ?>
        </div>
    </div>
</body>

</html>