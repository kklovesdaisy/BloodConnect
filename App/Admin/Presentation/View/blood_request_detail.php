<?php
$request = $request ?? [];
$donors = $donors ?? [];
$isAccepted = $isAccepted ?? false;
$acceptedDonor = $acceptedDonor ?? null;
$assignedDonor = $assignedDonor ?? null;
$assignedDonors = $assignedDonors ?? [];
$matchingTier = $matchingTier ?? 'none';
$competingRequests = $competingRequests ?? [];
$isCancelledRequest = $isCancelledRequest ?? false;
$isExpired = $isExpired ?? false;
$hasAssignedDonor = !empty($request['donor_id']) || !empty($assignedDonor);
$isPendingAssignment = $hasAssignedDonor && !$isAccepted;

$donorResponseMap = $donorResponseMap ?? [];

$bloodGroup = trim((string)($request['blood_group_needed'] ?? ''));
$patientName = htmlspecialchars((string)($request['patient_name'] ?? 'Patient'));
$requestCode = htmlspecialchars((string)($request['request_code'] ?? 'N/A'));
$hospitalName = htmlspecialchars((string)($request['hospital_name'] ?? 'N/A'));
$urgency = htmlspecialchars((string)($request['urgency'] ?? 'Routine'));
$units = (int)($request['unit'] ?? $request['units'] ?? 0);
$contactPhone = htmlspecialchars((string)($request['contact_phone'] ?? '-'));
$createdAt = htmlspecialchars((string)($request['created_at'] ?? '-'));

// Severity / Urgency Mapping Class
if (!function_exists('getAdminUrgencyStyle')) {
    function getAdminUrgencyStyle($urgency)
    {
        return match (strtoupper((string)$urgency)) {
            'CRITICAL' => 'bg-rose-50 text-rose-600 border-rose-200/50',
            'URGENT'   => 'bg-orange-50 text-orange-600 border-orange-200/50',
            default    => 'bg-slate-50 text-slate-500 border-slate-200/50',
        };
    }
}

// Blood Type Class Matcher
if (!function_exists('getAdminBloodGroupStyle')) {
    function getAdminBloodGroupStyle($type)
    {
        $type = strtolower((string)$type);
        if (str_contains($type, 'negative') || str_contains($type, '-')) {
            return 'text-rose-600 bg-rose-50 border-rose-200/40';
        }
        return 'text-emerald-600 bg-emerald-50 border-emerald-200/40';
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Blood Request Details | BloodConnect</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
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

        [x-cloak] {
            display: none !important;
        }
    </style>
</head>

<body class="bg-slate-50 min-h-screen text-slate-800 tracking-tight antialiased">
    <div class="max-w-7xl mx-auto p-4 sm:p-6 lg:p-8">

        <div class="relative bg-white border border-slate-200/60 rounded-2xl p-6 mb-8 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-6 shadow-xs animate-fade-in-up">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 bg-[#ce2424] text-white rounded-2xl flex items-center justify-center shadow-lg shadow-red-600/10 shrink-0">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-6 h-6 text-white">
                        <path d="M12 2.69l5.66 5.66a8 8 0 1 1-11.31 0z" />
                    </svg>
                </div>
                <div>
                    <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight leading-none">Request Control Hub</h1>
                    <p class="text-xs sm:text-sm text-slate-400 font-medium mt-1.5">Review current ticket metrics and assign compatible donor clusters.</p>
                </div>
            </div>

            <div class="flex items-center gap-2 self-start sm:self-auto">
                <form action="/BloodConnect/public/admin/blood-request/delete" method="POST" onsubmit="return confirm('Delete this blood request and all related records?');">
                    <input type="hidden" name="request_id" value="<?= (int)($request['request_id'] ?? 0) ?>">
                    <button type="submit" class="inline-flex items-center gap-2 rounded-xl border border-rose-200 bg-rose-50 px-4 py-2.5 text-xs font-bold text-rose-600 hover:bg-rose-100 shadow-3xs transition-all active:scale-95">
                        <i class="fa-solid fa-trash"></i> Delete Request
                    </button>
                </form>
                <a href="/BloodConnect/public/admin/blood-requests" class="inline-flex items-center gap-2 rounded-xl border border-slate-200/80 bg-white px-4 py-2.5 text-xs font-bold text-slate-600 hover:bg-slate-50 shadow-3xs transition-all active:scale-95">
                    <i class="fa-solid fa-arrow-left"></i> Back to Requests
                </a>
            </div>
        </div>

        <?php if (!empty($_SESSION['flash_message'])): ?>
        <div class="mb-6 px-5 py-4 rounded-2xl border text-sm font-bold <?= ($_SESSION['flash_status'] ?? 'error') === 'error' ? 'bg-rose-50 border-rose-200 text-rose-700' : 'bg-emerald-50 border-emerald-200 text-emerald-700' ?> animate-fade-in-up">
            <?= htmlspecialchars($_SESSION['flash_message']) ?>
            <button onclick="this.parentElement.remove()" class="float-right text-slate-400 hover:text-slate-600">&times;</button>
        </div>
        <?php unset($_SESSION['flash_message'], $_SESSION['flash_status']); ?>
        <?php endif; ?>

        <div class="grid gap-6 lg:grid-cols-[1.15fr_0.85fr]">

            <div class="bg-white border border-slate-200/70 rounded-3xl p-6 shadow-2xs space-y-6 opacity-0 animate-fade-in-up" style="animation-delay: 0.05s;">
                <div class="flex items-center justify-between gap-3 border-b border-slate-100 pb-5">
                    <div>
                        <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 font-mono block">Ticket Reference Code</span>
                        <h2 class="mt-1 text-xl font-black text-slate-900 tracking-tight"><?= $requestCode ?></h2>
                    </div>
                    <span class="inline-block px-3 py-1 text-[9px] font-extrabold rounded-lg uppercase tracking-widest border shadow-3xs <?= getAdminUrgencyStyle($urgency) ?>">
                        <?= htmlspecialchars(strtoupper((string)$urgency)) ?>
                    </span>
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="rounded-2xl border border-slate-200/60 bg-slate-50/50 p-4 space-y-1">
                        <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 block">Patient Name</span>
                        <p class="text-sm font-bold text-slate-800"><?= $patientName ?></p>
                    </div>

                    <div class="rounded-2xl border border-slate-200/60 bg-slate-50/50 p-4 space-y-1">
                        <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 block">Required Classification</span>
                        <div class="pt-0.5">
                            <span class="inline-flex items-center justify-center px-2 py-0.5 rounded-md border font-black text-xs font-mono <?= getAdminBloodGroupStyle($bloodGroup) ?>">
                                <?= htmlspecialchars($bloodGroup) ?>
                            </span>
                        </div>
                    </div>

                    <div class="rounded-2xl border border-slate-200/60 bg-slate-50/50 p-4 space-y-1">
                        <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 block">Units Requested</span>
                        <p class="text-sm font-extrabold text-slate-800"><?= $units ?> Bags</p>
                    </div>

                    <div class="rounded-2xl border border-slate-200/60 bg-slate-50/50 p-4 space-y-1">
                        <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 block">Target Facility</span>
                        <p class="text-sm font-bold text-slate-800 truncate"><?= $hospitalName ?></p>
                    </div>

                    <div class="rounded-2xl border border-slate-200/60 bg-slate-50/50 p-4 space-y-1">
                        <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 block">Location</span>
                        <p class="text-sm font-bold text-slate-800">
                            <?= htmlspecialchars((string)($request['township'] ?? '—')) ?> / <?= htmlspecialchars((string)($request['state_region'] ?? '—')) ?>
                        </p>
                    </div>

                    <?php if (!empty($request['hospital_address'])): ?>
                    <div class="rounded-2xl border border-slate-200/60 bg-slate-50/50 p-4 space-y-1 sm:col-span-2">
                        <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 block">Hospital Address</span>
                        <p class="text-sm font-semibold text-slate-700"><?= nl2br(htmlspecialchars((string)($request['hospital_address']))) ?></p>
                    </div>
                    <?php endif; ?>
                </div>

                <div class="rounded-2xl border border-slate-200/60 bg-slate-50/50 p-4 space-y-2">
                    <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 block">Communication & Registry Metadata</span>
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 text-xs">
                        <p class="font-bold text-slate-700"><i class="fa-solid fa-phone text-slate-400 mr-2"></i><?= $contactPhone ?></p>
                        <p class="font-semibold text-slate-400 font-mono"><i class="fa-solid fa-calendar text-slate-300 mr-2"></i><?= $createdAt ?></p>
                    </div>
                </div>
            </div>

            <div class="bg-white border border-slate-200/70 rounded-3xl p-6 shadow-2xs space-y-6 opacity-0 animate-fade-in-up" style="animation-delay: 0.1s;">
                <?php if ($isExpired): ?>
                    <div class="rounded-2xl border border-slate-300 bg-slate-100/70 p-6 text-center">
                        <div class="w-12 h-12 mx-auto mb-3 rounded-2xl bg-slate-200 text-slate-500 flex items-center justify-center">
                            <i class="fa-solid fa-hourglass-end text-xl"></i>
                        </div>
                        <h3 class="text-lg font-black text-slate-900 tracking-tight">Request Expired</h3>
                        <p class="mt-2 text-sm font-medium text-slate-500">This request expired before a donor accepted it. Donor matching is disabled for expired requests.</p>
                    </div>
                <?php elseif ($isCancelledRequest): ?>
                    <div class="rounded-2xl border border-slate-200 bg-slate-50/70 p-6 text-center">
                        <div class="w-12 h-12 mx-auto mb-3 rounded-2xl bg-slate-100 text-slate-500 flex items-center justify-center">
                            <i class="fa-solid fa-ban text-xl"></i>
                        </div>
                        <h3 class="text-lg font-black text-slate-900 tracking-tight">Matchmaking Disabled</h3>
                        <p class="mt-2 text-sm font-medium text-slate-500">This request was cancelled by the patient, so donor matching is no longer required.</p>
                    </div>
                <?php elseif ($isDeclined): ?>
                    <div class="rounded-2xl border border-rose-200 bg-rose-50/70 p-6 text-center mb-8">
                        <div class="w-12 h-12 mx-auto mb-3 rounded-2xl bg-rose-100 text-rose-500 flex items-center justify-center">
                            <i class="fa-solid fa-circle-xmark text-xl"></i>
                        </div>
                        <h3 class="text-lg font-black text-slate-900 tracking-tight">Donor Declined</h3>
                        <p class="mt-2 text-sm font-medium text-slate-500">The assigned donor has declined this request. You may assign a new donor from the matching list below, or reset the request to Pending.</p>
                        <form method="POST" action="/BloodConnect/public/admin/blood-request/reset-to-pending" class="mt-4" onsubmit="return confirm('Reset this request to Pending? The current donor assignment will be cleared.')">
                            <input type="hidden" name="request_id" value="<?= (int)($request['request_id'] ?? 0) ?>">
                            <button type="submit" class="rounded-lg border border-rose-300 bg-rose-600 px-4 py-2 text-sm font-semibold text-white hover:bg-rose-700 transition-all">
                                <i class="fa-solid fa-rotate-right mr-1"></i> Reset to Pending
                            </button>
                        </form>
                    </div>

                    <?php if (!empty($assignedDonors)): ?>
                    <div class="space-y-4 mb-8">
                        <div class="flex items-center justify-between gap-3 border-b border-slate-100 pb-4">
                            <div>
                                <h3 class="text-lg font-black text-slate-900 tracking-tight">Assigned Donors</h3>
                                <p class="text-xs text-slate-400 font-medium mt-0.5">Donor responses to this request.</p>
                            </div>
                            <span class="inline-block px-2.5 py-1 text-[9px] font-extrabold rounded-lg uppercase tracking-wider bg-slate-100 text-slate-600 border border-slate-200/60">
                                <?= count($assignedDonors) ?> Total
                            </span>
                        </div>
                        <?php
                        $responseLabels = [
                            11 => ['label' => 'Pending', 'class' => 'text-amber-600'],
                            12 => ['label' => 'Accepted', 'class' => 'text-emerald-600'],
                            13 => ['label' => 'Declined', 'class' => 'text-rose-600'],
                        ];
                        $responseBorders = [
                            11 => 'border-amber-200 bg-amber-50/30',
                            12 => 'border-emerald-200 bg-emerald-50/30',
                            13 => 'border-rose-200 bg-rose-50/30',
                        ];
                        ?>
                        <?php foreach ($assignedDonors as $ad):
                            $rsid = (int)($ad['response_status_id'] ?? 11);
                            $rlabel = $responseLabels[$rsid] ?? $responseLabels[11];
                            $rborder = $responseBorders[$rsid] ?? $responseBorders[11];
                        ?>
                            <div class="rounded-2xl border p-4 space-y-2 <?= $rborder ?>">
                                <div class="flex items-center justify-between gap-3">
                                    <h4 class="text-sm font-black text-slate-900"><?= htmlspecialchars((string)($ad['username'] ?? 'Donor')) ?></h4>
                                    <span class="text-[9px] font-bold uppercase tracking-wider <?= $rlabel['class'] ?>"><?= $rlabel['label'] ?></span>
                                </div>
                                <div class="flex gap-3 text-xs text-slate-600">
                                    <span class="font-mono font-bold"><?= htmlspecialchars((string)($ad['blood_group'] ?? '-')) ?></span>
                                    <span>&middot;</span>
                                    <span><?= htmlspecialchars((string)($ad['phone'] ?? '-')) ?></span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>

                    <?php if ($matchingTier !== 'none'): ?>
                    <div class="space-y-4">
                        <div class="flex items-center justify-between gap-3 border-b border-slate-100 pb-4">
                            <div>
                                <h3 class="text-lg font-black text-slate-900 tracking-tight">Matching Donors</h3>
                                <p class="text-xs text-slate-400 font-medium mt-0.5">Auto-filtered by location proximity.</p>
                            </div>
                            <span class="inline-block px-2.5 py-1 text-[9px] font-extrabold rounded-lg uppercase tracking-wider bg-slate-100 text-slate-600 border border-slate-200/60">
                                <?= count($donors) ?> Matches
                            </span>
                        </div>

                        <?php
                        $tierLabels = [
                            'township' => ['label' => 'Same Township', 'class' => 'bg-emerald-50 text-emerald-600 border-emerald-200'],
                            'region' => ['label' => 'Same State/Region', 'class' => 'bg-blue-50 text-blue-600 border-blue-200'],
                            'all' => ['label' => 'All Donors (broadcast)', 'class' => 'bg-amber-50 text-amber-600 border-amber-200'],
                        ];
                        $tierInfo = $tierLabels[$matchingTier] ?? ['label' => 'Unknown', 'class' => 'bg-slate-50 text-slate-600 border-slate-200'];
                        ?>

                        <div class="rounded-xl border p-3 <?= $tierInfo['class'] ?> flex items-center justify-between">
                            <span class="text-xs font-extrabold uppercase tracking-wider"><?= $tierInfo['label'] ?></span>
                            <span class="text-[10px] font-bold"><?= count($donors) ?> donor<?= count($donors) !== 1 ? 's' : '' ?></span>
                        </div>

                        <form action="/BloodConnect/public/admin/blood-request/assign-donors" method="POST" class="space-y-4">
                            <input type="hidden" name="request_id" value="<?= (int)($request['request_id'] ?? 0) ?>">

                             <div class="space-y-2 max-h-80 overflow-y-auto pr-1">
                                <?php
                                $allReserved = !empty($donors);
                                foreach ($donors as $donor):
                                    $did = (int)($donor['user_id'] ?? 0);
                                    $dloc = array_filter([$donor['township'] ?? '', $donor['state_region'] ?? '']);
                                    $reserved = $donor['reserved_for'] ?? null;
                                    if ($reserved === null) {
                                        $allReserved = false;
                                    }
                                ?>
                                    <?php $alreadyResponded = isset($donorResponseMap[$did]) && $donorResponseMap[$did] !== 11; ?>
                                    <label class="flex items-start gap-3 p-3 rounded-xl border transition-all <?= ($reserved !== null || $alreadyResponded) ? 'border-slate-200 bg-slate-50/40 opacity-70 cursor-not-allowed' : 'border-slate-200/70 bg-white hover:bg-slate-50/50 cursor-pointer has-[:checked]:border-rose-300 has-[:checked]:bg-rose-50/30' ?>">
                                        <input type="checkbox" name="donor_ids[]" value="<?= $did ?>" <?= ($reserved !== null || $alreadyResponded) ? 'disabled' : '' ?>
                                            class="mt-0.5 w-4 h-4 rounded accent-[#ce24224]">
                                        <div class="flex-1 min-w-0">
                                            <div class="flex items-center justify-between gap-2">
                                                <span class="text-sm font-bold text-slate-800 truncate"><?= htmlspecialchars((string)($donor['username'] ?? 'Donor')) ?></span>
                                                <span class="text-[10px] font-bold font-mono px-1.5 py-0.5 rounded border <?= getAdminBloodGroupStyle($donor['blood_group'] ?? '') ?>">
                                                    <?= htmlspecialchars((string)($donor['blood_group'] ?? '-')) ?>
                                                </span>
                                            </div>
                                            <div class="flex flex-wrap gap-x-3 gap-y-0.5 mt-1 text-[11px] text-slate-500 font-medium">
                                                <span><?= htmlspecialchars((string)($donor['phone'] ?? '-')) ?></span>
                                                <?php if (!empty($dloc)): ?>
                                                    <span>&middot;</span>
                                                    <span class="truncate"><?= htmlspecialchars(implode(', ', $dloc)) ?></span>
                                                <?php endif; ?>
                                                <?php
                                                $tierBadge = [
                                                    'township' => ['label' => 'Same Township', 'class' => 'bg-emerald-100 text-emerald-700 border-emerald-200'],
                                                    'region'   => ['label' => 'Same Region', 'class' => 'bg-blue-100 text-blue-700 border-blue-200'],
                                                    'all'      => ['label' => 'Other Region', 'class' => 'bg-amber-100 text-amber-700 border-amber-200'],
                                                ];
                                                $dt = $donor['match_tier'] ?? 'all';
                                                $dbadge = $tierBadge[$dt] ?? $tierBadge['all'];
                                                ?>
                                                <span class="inline-flex items-center gap-1 text-[10px] font-extrabold uppercase tracking-wider <?= $dbadge['class'] ?> border rounded px-1.5 py-0.5">
                                                    <?= htmlspecialchars($dbadge['label']) ?>
                                                </span>
                                                <?php if (isset($donorResponseMap[$did]) && $donorResponseMap[$did] === 13): ?>
                                                    <span class="inline-flex items-center gap-1 text-[10px] font-extrabold uppercase tracking-wider text-rose-700 bg-rose-100 border border-rose-200 rounded px-1.5 py-0.5">
                                                        Declined
                                                    </span>
                                                <?php elseif (isset($donorResponseMap[$did]) && $donorResponseMap[$did] === 12): ?>
                                                    <span class="inline-flex items-center gap-1 text-[10px] font-extrabold uppercase tracking-wider text-emerald-700 bg-emerald-100 border border-emerald-200 rounded px-1.5 py-0.5">
                                                        Accepted
                                                    </span>
                                                <?php endif; ?>
                                                <?php if ($reserved !== null): ?>
                                                    <span class="inline-flex items-center gap-1 mt-1 text-[10px] font-extrabold uppercase tracking-wider text-amber-700 bg-amber-100 border border-amber-200 rounded px-1.5 py-0.5">
                                                        Reserved &rarr; <?= htmlspecialchars($reserved['request_code']) ?> (<?= htmlspecialchars($reserved['urgency']) ?>)
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </label>
                                <?php endforeach; ?>
                            </div>

                            <?php if ($allReserved): ?>
                                <div class="rounded-xl border border-amber-200 bg-amber-50/60 p-3 text-[11px] font-semibold text-amber-700">
                                    All matching donors are reserved for a higher-priority request. Assign donors to the top-priority request first.
                                </div>
                            <?php endif; ?>

                            <?php if (!empty($donors)): ?>
                                <div class="flex items-center gap-2">
                                    <button type="button" onclick="toggleAllCheckboxes()" class="text-xs font-bold text-slate-500 hover:text-slate-700 transition-colors">
                                        Toggle All
                                    </button>
                                    <span class="text-slate-300">|</span>
                                    <span id="selectedCount" class="text-xs font-semibold text-slate-400">0 selected</span>
                                </div>

                                <button type="submit" class="w-full inline-flex items-center justify-center gap-2 rounded-xl bg-[#ce2424] px-4 py-3 text-xs font-bold text-white hover:bg-[#a61c1c] transition-all active:scale-95 shadow-md shadow-red-600/10">
                                    <i class="fa-solid fa-truck-medical"></i> Assign Selected Donors
                                </button>

                                <button type="submit" formaction="/BloodConnect/public/admin/blood-request/notify-donors" class="w-full inline-flex items-center justify-center gap-2 rounded-xl border border-[#ce2424] bg-white px-4 py-3 text-xs font-bold text-[#ce2424] hover:bg-rose-50 transition-all active:scale-95">
                                    <i class="fa-solid fa-envelope"></i> Send Email Alert to Selected Donors
                                </button>
                            <?php endif; ?>
                        </form>

                        <script>
                            function toggleAllCheckboxes() {
                                const checkboxes = document.querySelectorAll('input[name="donor_ids[]"]');
                                const allChecked = Array.from(checkboxes).every(cb => cb.checked);
                                checkboxes.forEach(cb => cb.checked = !allChecked);
                                updateSelectedCount();
                            }

                            function updateSelectedCount() {
                                const checked = document.querySelectorAll('input[name="donor_ids[]"]:checked').length;
                                const el = document.getElementById('selectedCount');
                                if (el) el.textContent = checked + ' selected';
                            }

                            document.addEventListener('change', function(e) {
                                if (e.target.matches('input[name="donor_ids[]"]')) {
                                    updateSelectedCount();
                                }
                            });

                            document.addEventListener('DOMContentLoaded', updateSelectedCount);
                        </script>
                    </div>
                    <?php else: ?>
                        <div class="rounded-2xl border border-dashed border-slate-200 bg-slate-50/50 p-8 text-center text-xs font-medium text-slate-400">
                            <i class="fa-solid fa-users-slash text-xl block text-slate-300 mb-2"></i>
                            No active verified network donors match this blood group registry.
                        </div>
                    <?php endif; ?>

                <?php elseif ($isAccepted && $acceptedDonor): ?>
                    <div class="rounded-2xl border border-emerald-200 bg-emerald-50/50 p-5 space-y-4">
                        <div class="flex items-center justify-between gap-3">
                            <div>
                                <span class="text-[9px] font-extrabold uppercase tracking-widest text-emerald-600 block">Fulfillment Active</span>
                                <h4 class="mt-1 text-base font-black text-slate-900 tracking-tight">
                                    <?= htmlspecialchars((string)($acceptedDonor['username'] ?? 'Donor')) ?>
                                </h4>
                            </div>
                            <span class="rounded-full bg-emerald-600 px-3 py-1 text-[10px] font-extrabold uppercase tracking-wider text-white shadow-xs shadow-emerald-600/10">Accepted</span>
                        </div>
                        <div class="mt-4 grid gap-3">
                            <div class="rounded-lg bg-white/80 p-3">
                                <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Blood Type</span>
                                <p class="mt-1 text-sm font-semibold text-slate-800"><?= htmlspecialchars((string)($acceptedDonor['blood_group'] ?? '-')) ?></p>
                            </div>
                            <div class="rounded-lg bg-white/80 p-3">
                                <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Direct Line</span>
                                <p class="mt-1 text-sm font-semibold text-slate-800"><?= htmlspecialchars((string)($acceptedDonor['phone'] ?? '-')) ?></p>
                            </div>
                            <div class="rounded-lg bg-white/80 p-3">
                                <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Email</span>
                                <p class="mt-1 text-sm font-semibold text-slate-800 truncate"><?= htmlspecialchars((string)($acceptedDonor['email'] ?? '-')) ?></p>
                            </div>
                            <div class="rounded-lg bg-white/80 p-3">
                                <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Address</span>
                                <p class="mt-1 text-sm font-semibold text-slate-800"><?= htmlspecialchars((string)($acceptedDonor['address'] ?? '-')) ?></p>
                            </div>
                            <div class="rounded-lg bg-white/80 p-3">
                                <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Location</span>
                                <p class="mt-1 text-sm font-semibold text-slate-800">
                                    <?php
                                    $loc = array_filter([$acceptedDonor['township'] ?? '', $acceptedDonor['state_region'] ?? '']);
                                    echo !empty($loc) ? htmlspecialchars(implode(', ', $loc)) : '—';
                                    ?>
                                </p>
                            </div>
                        </div>
                    </div>

                <?php else: ?>

                    <?php if (!empty($assignedDonors)): ?>
                    <div class="space-y-4 mb-8">
                        <div class="flex items-center justify-between gap-3 border-b border-slate-100 pb-4">
                            <div>
                                <h3 class="text-lg font-black text-slate-900 tracking-tight">Assigned Donors</h3>
                                <p class="text-xs text-slate-400 font-medium mt-0.5">Donor responses to this request.</p>
                            </div>
                            <span class="inline-block px-2.5 py-1 text-[9px] font-extrabold rounded-lg uppercase tracking-wider bg-slate-100 text-slate-600 border border-slate-200/60">
                                <?= count($assignedDonors) ?> Total
                            </span>
                        </div>
                        <?php
                        $responseLabels = [
                            11 => ['label' => 'Pending', 'class' => 'text-amber-600'],
                            12 => ['label' => 'Accepted', 'class' => 'text-emerald-600'],
                            13 => ['label' => 'Declined', 'class' => 'text-rose-600'],
                        ];
                        $responseBorders = [
                            11 => 'border-amber-200 bg-amber-50/30',
                            12 => 'border-emerald-200 bg-emerald-50/30',
                            13 => 'border-rose-200 bg-rose-50/30',
                        ];
                        ?>
                        <?php foreach ($assignedDonors as $ad):
                            $rsid = (int)($ad['response_status_id'] ?? 11);
                            $rlabel = $responseLabels[$rsid] ?? $responseLabels[11];
                            $rborder = $responseBorders[$rsid] ?? $responseBorders[11];
                        ?>
                            <div class="rounded-2xl border p-4 space-y-2 <?= $rborder ?>">
                                <div class="flex items-center justify-between gap-3">
                                    <h4 class="text-sm font-black text-slate-900"><?= htmlspecialchars((string)($ad['username'] ?? 'Donor')) ?></h4>
                                    <span class="text-[9px] font-bold uppercase tracking-wider <?= $rlabel['class'] ?>"><?= $rlabel['label'] ?></span>
                                </div>
                                <div class="flex gap-3 text-xs text-slate-600">
                                    <span class="font-mono font-bold"><?= htmlspecialchars((string)($ad['blood_group'] ?? '-')) ?></span>
                                    <span>&middot;</span>
                                    <span><?= htmlspecialchars((string)($ad['phone'] ?? '-')) ?></span>
                                </div>
                            </div>
                        <?php endforeach; ?>

                        <?php
                        $notifiableAssigned = array_filter($assignedDonors, fn($ad) => (int)($ad['response_status_id'] ?? 11) !== 13);
                        ?>
                        <?php if (!empty($notifiableAssigned)): ?>
                        <form action="/BloodConnect/public/admin/blood-request/notify-donors" method="POST" class="pt-2">
                            <input type="hidden" name="request_id" value="<?= (int)($request['request_id'] ?? 0) ?>">
                            <?php foreach ($notifiableAssigned as $ad): ?>
                                <input type="hidden" name="donor_ids[]" value="<?= (int)($ad['donor_id'] ?? 0) ?>">
                            <?php endforeach; ?>
                            <button type="submit" class="w-full inline-flex items-center justify-center gap-2 rounded-xl border border-[#ce2424] bg-white px-4 py-3 text-xs font-bold text-[#ce2424] hover:bg-rose-50 transition-all active:scale-95">
                                <i class="fa-solid fa-envelope"></i> Send Email Alert to Assigned Donors
                            </button>
                        </form>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>

                    <?php if ($matchingTier !== 'none'): ?>
                    <div class="space-y-4">
                        <div class="flex items-center justify-between gap-3 border-b border-slate-100 pb-4">
                            <div>
                                <h3 class="text-lg font-black text-slate-900 tracking-tight">Matching Donors</h3>
                                <p class="text-xs text-slate-400 font-medium mt-0.5">Auto-filtered by location proximity.</p>
                            </div>
                            <span class="inline-block px-2.5 py-1 text-[9px] font-extrabold rounded-lg uppercase tracking-wider bg-slate-100 text-slate-600 border border-slate-200/60">
                                <?= count($donors) ?> Matches
                            </span>
                        </div>

                        <?php
                        $tierLabels = [
                            'township' => ['label' => 'Same Township', 'class' => 'bg-emerald-50 text-emerald-600 border-emerald-200'],
                            'region' => ['label' => 'Same State/Region', 'class' => 'bg-blue-50 text-blue-600 border-blue-200'],
                            'all' => ['label' => 'All Donors (broadcast)', 'class' => 'bg-amber-50 text-amber-600 border-amber-200'],
                        ];
                        $tierInfo = $tierLabels[$matchingTier] ?? ['label' => 'Unknown', 'class' => 'bg-slate-50 text-slate-600 border-slate-200'];
                        ?>

                        <div class="rounded-xl border p-3 <?= $tierInfo['class'] ?> flex items-center justify-between">
                            <span class="text-xs font-extrabold uppercase tracking-wider"><?= $tierInfo['label'] ?></span>
                            <span class="text-[10px] font-bold"><?= count($donors) ?> donor<?= count($donors) !== 1 ? 's' : '' ?></span>
                        </div>

                        <form action="/BloodConnect/public/admin/blood-request/assign-donors" method="POST" class="space-y-4">
                            <input type="hidden" name="request_id" value="<?= (int)($request['request_id'] ?? 0) ?>">

                             <div class="space-y-2 max-h-80 overflow-y-auto pr-1">
                                <?php
                                $allReserved = !empty($donors);
                                foreach ($donors as $donor):
                                    $did = (int)($donor['user_id'] ?? 0);
                                    $dloc = array_filter([$donor['township'] ?? '', $donor['state_region'] ?? '']);
                                    $reserved = $donor['reserved_for'] ?? null;
                                    if ($reserved === null) {
                                        $allReserved = false;
                                    }
                                ?>
                                    <?php $alreadyResponded = isset($donorResponseMap[$did]) && $donorResponseMap[$did] !== 11; ?>
                                    <label class="flex items-start gap-3 p-3 rounded-xl border transition-all <?= ($reserved !== null || $alreadyResponded) ? 'border-slate-200 bg-slate-50/40 opacity-70 cursor-not-allowed' : 'border-slate-200/70 bg-white hover:bg-slate-50/50 cursor-pointer has-[:checked]:border-rose-300 has-[:checked]:bg-rose-50/30' ?>">
                                        <input type="checkbox" name="donor_ids[]" value="<?= $did ?>" <?= ($reserved !== null || $alreadyResponded) ? 'disabled' : '' ?>
                                            class="mt-0.5 w-4 h-4 rounded accent-[#ce24224]">
                                        <div class="flex-1 min-w-0">
                                            <div class="flex items-center justify-between gap-2">
                                                <span class="text-sm font-bold text-slate-800 truncate"><?= htmlspecialchars((string)($donor['username'] ?? 'Donor')) ?></span>
                                                <span class="text-[10px] font-bold font-mono px-1.5 py-0.5 rounded border <?= getAdminBloodGroupStyle($donor['blood_group'] ?? '') ?>">
                                                    <?= htmlspecialchars((string)($donor['blood_group'] ?? '-')) ?>
                                                </span>
                                            </div>
                                            <div class="flex flex-wrap gap-x-3 gap-y-0.5 mt-1 text-[11px] text-slate-500 font-medium">
                                                <span><?= htmlspecialchars((string)($donor['phone'] ?? '-')) ?></span>
                                                <?php if (!empty($dloc)): ?>
                                                    <span>&middot;</span>
                                                    <span class="truncate"><?= htmlspecialchars(implode(', ', $dloc)) ?></span>
                                                <?php endif; ?>
                                                <?php
                                                $tierBadge = [
                                                    'township' => ['label' => 'Same Township', 'class' => 'bg-emerald-100 text-emerald-700 border-emerald-200'],
                                                    'region'   => ['label' => 'Same Region', 'class' => 'bg-blue-100 text-blue-700 border-blue-200'],
                                                    'all'      => ['label' => 'Other Region', 'class' => 'bg-amber-100 text-amber-700 border-amber-200'],
                                                ];
                                                $dt = $donor['match_tier'] ?? 'all';
                                                $dbadge = $tierBadge[$dt] ?? $tierBadge['all'];
                                                ?>
                                                <span class="inline-flex items-center gap-1 text-[10px] font-extrabold uppercase tracking-wider <?= $dbadge['class'] ?> border rounded px-1.5 py-0.5">
                                                    <?= htmlspecialchars($dbadge['label']) ?>
                                                </span>
                                                <?php if (isset($donorResponseMap[$did]) && $donorResponseMap[$did] === 13): ?>
                                                    <span class="inline-flex items-center gap-1 text-[10px] font-extrabold uppercase tracking-wider text-rose-700 bg-rose-100 border border-rose-200 rounded px-1.5 py-0.5">
                                                        Declined
                                                    </span>
                                                <?php elseif (isset($donorResponseMap[$did]) && $donorResponseMap[$did] === 12): ?>
                                                    <span class="inline-flex items-center gap-1 text-[10px] font-extrabold uppercase tracking-wider text-emerald-700 bg-emerald-100 border border-emerald-200 rounded px-1.5 py-0.5">
                                                        Accepted
                                                    </span>
                                                <?php endif; ?>
                                                <?php if ($reserved !== null): ?>
                                                    <span class="inline-flex items-center gap-1 mt-1 text-[10px] font-extrabold uppercase tracking-wider text-amber-700 bg-amber-100 border border-amber-200 rounded px-1.5 py-0.5">
                                                        Reserved &rarr; <?= htmlspecialchars($reserved['request_code']) ?> (<?= htmlspecialchars($reserved['urgency']) ?>)
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </label>
                                <?php endforeach; ?>
                            </div>

                            <?php if ($allReserved): ?>
                                <div class="rounded-xl border border-amber-200 bg-amber-50/60 p-3 text-[11px] font-semibold text-amber-700">
                                    All matching donors are reserved for a higher-priority request. Assign donors to the top-priority request first.
                                </div>
                            <?php endif; ?>

                            <?php if (!empty($donors)): ?>
                                <div class="flex items-center gap-2">
                                    <button type="button" onclick="toggleAllCheckboxes()" class="text-xs font-bold text-slate-500 hover:text-slate-700 transition-colors">
                                        Toggle All
                                    </button>
                                    <span class="text-slate-300">|</span>
                                    <span id="selectedCount" class="text-xs font-semibold text-slate-400">0 selected</span>
                                </div>

                                <button type="submit" class="w-full inline-flex items-center justify-center gap-2 rounded-xl bg-[#ce2424] px-4 py-3 text-xs font-bold text-white hover:bg-[#a61c1c] transition-all active:scale-95 shadow-md shadow-red-600/10">
                                    <i class="fa-solid fa-truck-medical"></i> Assign Selected Donors
                                </button>

                                <button type="submit" formaction="/BloodConnect/public/admin/blood-request/notify-donors" class="w-full inline-flex items-center justify-center gap-2 rounded-xl border border-[#ce2424] bg-white px-4 py-3 text-xs font-bold text-[#ce2424] hover:bg-rose-50 transition-all active:scale-95">
                                    <i class="fa-solid fa-envelope"></i> Send Email Alert to Selected Donors
                                </button>
                            <?php endif; ?>
                        </form>

                        <script>
                            function toggleAllCheckboxes() {
                                const checkboxes = document.querySelectorAll('input[name="donor_ids[]"]');
                                const allChecked = Array.from(checkboxes).every(cb => cb.checked);
                                checkboxes.forEach(cb => cb.checked = !allChecked);
                                updateSelectedCount();
                            }

                            function updateSelectedCount() {
                                const checked = document.querySelectorAll('input[name="donor_ids[]"]:checked').length;
                                const el = document.getElementById('selectedCount');
                                if (el) el.textContent = checked + ' selected';
                            }

                            document.addEventListener('change', function(e) {
                                if (e.target.matches('input[name="donor_ids[]"]')) {
                                    updateSelectedCount();
                                }
                            });

                            document.addEventListener('DOMContentLoaded', updateSelectedCount);
                        </script>
                    </div>
                <?php else: ?>
                    <div class="rounded-2xl border border-dashed border-slate-200 bg-slate-50/50 p-8 text-center text-xs font-medium text-slate-400">
                        <i class="fa-solid fa-users-slash text-xl block text-slate-300 mb-2"></i>
                        No active verified network donors match this blood group registry.
                    </div>
                <?php endif; ?>
                <?php endif; ?>
            </div>

        </div>

        <?php if (!empty($competingRequests)): ?>
        <div class="mt-6 bg-white border border-slate-200/70 rounded-3xl p-6 shadow-2xs opacity-0 animate-fade-in-up" style="animation-delay: 0.15s;">
            <div class="flex items-center justify-between gap-3 border-b border-slate-100 pb-4 mb-4">
                <div>
                    <h3 class="text-lg font-black text-slate-900 tracking-tight">Competing Requests</h3>
                    <p class="text-xs text-slate-400 font-medium mt-0.5">
                        <?= count($competingRequests) ?> other pending request<?= count($competingRequests) !== 1 ? 's' : '' ?>
                        with same blood type and location — ordered by urgency (Critical &rarr; Standard).
                        Assign donors to the top-priority request first.
                    </p>
                </div>
                <span class="inline-block px-2.5 py-1 text-[9px] font-extrabold rounded-lg uppercase tracking-wider bg-slate-100 text-slate-600 border border-slate-200/60">
                    <?= count($competingRequests) ?> pending
                </span>
            </div>
            <div class="space-y-2.5">
                <?php foreach ($competingRequests as $ci => $cr):
                    $crUrgency = htmlspecialchars((string)($cr['urgency'] ?? 'ROUTINE'));
                    $crLoc = array_filter([$cr['township'] ?? '', $cr['state_region'] ?? '']);
                    $isTopPriority = $ci === 0;
                ?>
                <a href="/BloodConnect/public/admin/blood-request/view?id=<?= (int)$cr['request_id'] ?>"
                   class="block rounded-2xl border p-4 transition-all active:scale-[0.99] <?= $isTopPriority ? 'border-rose-300 bg-rose-50/40 hover:bg-rose-50 hover:border-rose-400' : 'border-slate-200/70 bg-slate-50/30 hover:bg-slate-50 hover:border-slate-300' ?>">
                    <div class="flex items-center justify-between gap-4">
                        <div class="flex items-center gap-3 min-w-0">
                            <?php if ($isTopPriority): ?>
                            <span class="shrink-0 text-[9px] font-extrabold uppercase tracking-wider rounded-md bg-rose-600 text-white px-1.5 py-0.5">Top</span>
                            <?php endif; ?>
                            <span class="shrink-0 text-[10px] font-bold text-slate-400 font-mono">#<?= (int)$cr['request_id'] ?></span>
                            <div class="min-w-0">
                                <span class="text-sm font-bold text-slate-800 truncate block"><?= htmlspecialchars($cr['patient_name']) ?></span>
                                <span class="text-[11px] text-slate-500 font-medium block truncate"><?= htmlspecialchars($cr['hospital_name']) ?></span>
                            </div>
                        </div>
                        <div class="flex items-center gap-3 shrink-0">
                            <span class="text-xs font-bold text-slate-600"><?= (int)$cr['unit'] ?> units</span>
                            <span class="inline-block px-2.5 py-1 text-[9px] font-extrabold rounded-lg uppercase tracking-widest border <?= getAdminUrgencyStyle($crUrgency) ?>">
                                <?= strtoupper($crUrgency) ?>
                            </span>
                            <span class="inline-flex items-center justify-center px-2 py-0.5 rounded-md border font-black text-xs font-mono <?= getAdminBloodGroupStyle($cr['blood_group_needed'] ?? '') ?>">
                                <?= htmlspecialchars($cr['blood_group_needed']) ?>
                            </span>
                            <?php if (!empty($crLoc)): ?>
                            <span class="text-[11px] text-slate-400 font-medium hidden sm:inline"><?= htmlspecialchars(implode(', ', $crLoc)) ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                </a>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

    </div>
</body>

</html>
