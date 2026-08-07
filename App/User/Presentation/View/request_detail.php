<?php
$request = $request ?? [];
$rawStatus = strtolower(trim((string)($request['status_name'] ?? $request['status'] ?? 'pending')));
$statusLabel = htmlspecialchars(in_array($rawStatus, ['pending', 'assigned', 'declined']) ? 'Pending' : ucfirst($rawStatus));
$donorName = htmlspecialchars((string)($request['donor_name'] ?? ''));
$donorBlood = htmlspecialchars((string)($request['donor_blood_group'] ?? ''));
$donorPhone = htmlspecialchars((string)($request['donor_phone'] ?? ''));
$donorEmail = htmlspecialchars((string)($request['donor_email'] ?? ''));
$donorAddress = htmlspecialchars((string)($request['donor_address'] ?? ''));
$donorLocation = htmlspecialchars((string)($request['donor_township'] ?? '')) . (($request['donor_township'] ?? '') && ($request['donor_state_region'] ?? '') ? ', ' : '') . htmlspecialchars((string)($request['donor_state_region'] ?? ''));
$hasDonor = !empty($donorName);
$isAccepted = (stripos((string)($request['status_name'] ?? $request['status'] ?? 'Pending'), 'accepted') !== false)
    || (int)($request['status'] ?? 0) === 8;
$isDeclined = (stripos((string)($request['status_name'] ?? $request['status'] ?? ''), 'declined') !== false)
    || (int)($request['status'] ?? 0) === 47;
$isExpired = (stripos((string)($request['status_name'] ?? $request['status'] ?? ''), 'expired') !== false)
    || (int)($request['status'] ?? 0) === 48;
$isPending = $rawStatus === 'pending' || (int)($request['status'] ?? 0) === 7;
$showPendingAssignment = $hasDonor && !$isAccepted && !$isDeclined;

$statusBadgeClass = $isExpired ? 'bg-slate-100 text-slate-500' : ($isAccepted ? 'bg-emerald-50 text-emerald-600' : ($isDeclined ? 'bg-rose-50 text-rose-600' : 'bg-amber-50 text-amber-600'));

$expiryDate = (string)($request['expiry_date'] ?? '');
$expiryRemaining = '';
if ($expiryDate !== '') {
    $expiryDiff = strtotime($expiryDate) - time();
    if ($expiryDiff > 0) {
        $expiryDays = floor($expiryDiff / 86400);
        $expiryHours = floor(($expiryDiff % 86400) / 3600);
        $expiryRemaining = ($expiryDays > 0 ? $expiryDays . 'd ' : '') . $expiryHours . 'h left';
    }
}
?>

<div class="max-w-7xl mx-auto p-6">
    <div class="mb-6 flex items-center justify-between">
        <div>
            <h2 class="text-2xl font-bold text-slate-900">Request Details</h2>
            <p class="text-sm text-slate-500">View full request information and assigned donor details.</p>
        </div>
        <div class="flex items-center gap-2">
            <?php if (strtolower($statusLabel) === 'pending'): ?>
                <form method="POST" action="/BloodConnect/public/patient/request/cancel" class="inline" onsubmit="return confirm('Are you sure you want to cancel this request?')">
                    <input type="hidden" name="request_id" value="<?= (int)($request['request_id'] ?? 0) ?>">
                    <button type="submit" class="rounded-lg border border-red-200 bg-red-50 px-4 py-2 text-sm font-semibold text-red-600 hover:bg-red-100">
                        Cancel Request
                    </button>
                </form>
            <?php endif; ?>
            <a href="/BloodConnect/public/patient/my-request/receipt?id=<?= (int)($request['request_id'] ?? 0) ?>"
                class="inline-flex items-center gap-1.5 rounded-lg bg-red-600 px-4 py-2 text-sm font-semibold text-white hover:bg-red-700">
                <i class="fa-solid fa-file-pdf"></i> Download PDF
            </a>
            <a href="/BloodConnect/public/patient/my-requests" class="rounded-lg border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-600 hover:bg-slate-50">
                ← Back to My Requests
            </a>
        </div>
    </div>

    <div class="grid gap-6 lg:grid-cols-[1.1fr_0.9fr]">
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <div class="flex items-center justify-between gap-3">
                <div>
                    <p class="text-xs font-bold uppercase tracking-wider text-slate-400">Request Code</p>
                    <h3 class="mt-1 text-xl font-black text-slate-900"><?= htmlspecialchars((string)($request['request_code'] ?? 'N/A')) ?></h3>
                </div>
                <span class="rounded-full <?= $statusBadgeClass ?> px-3 py-1 text-sm font-semibold"><?= $statusLabel ?></span>
            </div>

            <div class="mt-6 grid gap-4 sm:grid-cols-2">
                <div class="rounded-xl border border-slate-100 bg-slate-50 p-4">
                    <p class="text-xs font-bold uppercase tracking-wider text-slate-400">Patient</p>
                    <p class="mt-2 text-base font-semibold text-slate-800"><?= htmlspecialchars((string)($request['patient_name'] ?? 'Patient')) ?></p>
                </div>
                <div class="rounded-xl border border-slate-100 bg-slate-50 p-4">
                    <p class="text-xs font-bold uppercase tracking-wider text-slate-400">Blood Group Needed</p>
                    <p class="mt-2 text-base font-semibold text-slate-800"><?= htmlspecialchars((string)($request['blood_group_needed'] ?? '-')) ?></p>
                </div>
                <div class="rounded-xl border border-slate-100 bg-slate-50 p-4">
                    <p class="text-xs font-bold uppercase tracking-wider text-slate-400">Units Needed</p>
                    <p class="mt-2 text-base font-semibold text-slate-800"><?= (int)($request['unit'] ?? 0) ?></p>
                </div>
                <div class="rounded-xl border border-slate-100 bg-slate-50 p-4">
                    <p class="text-xs font-bold uppercase tracking-wider text-slate-400">Hospital</p>
                    <p class="mt-2 text-base font-semibold text-slate-800"><?= htmlspecialchars((string)($request['hospital_name'] ?? '-')) ?></p>
                </div>
                <div class="rounded-xl border border-slate-100 bg-slate-50 p-4">
                    <p class="text-xs font-bold uppercase tracking-wider text-slate-400">Location</p>
                    <p class="mt-2 text-base font-semibold text-slate-800">
                        <?= htmlspecialchars((string)($request['township'] ?? '—')) ?>, <?= htmlspecialchars((string)($request['state_region'] ?? '—')) ?>
                    </p>
                </div>
            </div>

            <?php if (!empty($request['hospital_address'])): ?>
                <div class="mt-6 rounded-xl border border-slate-100 bg-slate-50 p-4">
                    <p class="text-xs font-bold uppercase tracking-wider text-slate-400">Hospital Address</p>
                    <p class="mt-2 text-base font-semibold text-slate-800"><?= nl2br(htmlspecialchars((string)($request['hospital_address']))) ?></p>
                </div>
            <?php endif; ?>

            <div class="mt-6 rounded-xl border border-slate-100 bg-slate-50 p-4">
                <p class="text-xs font-bold uppercase tracking-wider text-slate-400">Contact</p>
                <p class="mt-2 text-base font-semibold text-slate-800">Phone: <?= htmlspecialchars((string)($request['contact_phone'] ?? '-')) ?></p>
                <p class="mt-1 text-sm text-slate-500">Created: <?= htmlspecialchars((string)($request['created_at'] ?? '-')) ?></p>
                <?php if ($expiryDate !== '' && ($isPending || $isExpired)): ?>
                    <div class="mt-3 rounded-lg border <?= $isExpired ? 'border-slate-200 bg-slate-100' : 'border-amber-200 bg-amber-50' ?> p-3">
                        <p class="text-xs font-bold uppercase tracking-wider <?= $isExpired ? 'text-slate-500' : 'text-amber-600' ?>">Request Expiry</p>
                        <p class="mt-1 text-sm font-semibold <?= $isExpired ? 'text-slate-600' : 'text-slate-800' ?>">
                            <?= $isExpired
                                ? '<i class="fa-solid fa-hourglass-end mr-1"></i> Expired on ' . htmlspecialchars(date('d M Y H:i', strtotime($expiryDate)))
                                : '<i class="fa-regular fa-clock mr-1"></i> Expires on ' . htmlspecialchars(date('d M Y H:i', strtotime($expiryDate))) . ' <span class="font-bold text-amber-600">(' . htmlspecialchars($expiryRemaining) . ')</span>' ?>
                        </p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h3 class="text-lg font-black text-slate-900">Matched Donor</h3>
            <p class="mt-1 text-sm text-slate-500">This section appears when a donor is assigned to your request.</p>

            <?php if ($isAccepted && $hasDonor): ?>
                <div class="mt-6 rounded-xl border border-emerald-200 bg-emerald-50 p-5">
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <p class="text-xs font-bold uppercase tracking-wider text-emerald-600">Assigned Donor</p>
                            <h4 class="mt-1 text-lg font-black text-slate-900"><?= $donorName ?></h4>
                        </div>
                        <span class="rounded-full bg-white px-3 py-1 text-sm font-semibold text-emerald-600">Accepted</span>
                    </div>

                    <div class="mt-4 grid gap-3">
                        <div class="rounded-lg bg-white/80 p-3">
                            <p class="text-xs font-bold uppercase tracking-wider text-slate-400">Blood Group</p>
                            <p class="mt-1 text-sm font-semibold text-slate-800"><?= $donorBlood ?></p>
                        </div>
                        <div class="rounded-lg bg-white/80 p-3">
                            <p class="text-xs font-bold uppercase tracking-wider text-slate-400">Phone</p>
                            <p class="mt-1 text-sm font-semibold text-slate-800"><?= $donorPhone ?></p>
                        </div>
                        <div class="rounded-lg bg-white/80 p-3">
                            <p class="text-xs font-bold uppercase tracking-wider text-slate-400">Email</p>
                            <p class="mt-1 text-sm font-semibold text-slate-800"><?= $donorEmail ?></p>
                        </div>
                        <div class="rounded-lg bg-white/80 p-3">
                            <p class="text-xs font-bold uppercase tracking-wider text-slate-400">Address</p>
                            <p class="mt-1 text-sm font-semibold text-slate-800"><?= $donorAddress ?></p>
                        </div>
                        <?php if (!empty($donorLocation)): ?>
                        <div class="rounded-lg bg-white/80 p-3">
                            <p class="text-xs font-bold uppercase tracking-wider text-slate-400">Location</p>
                            <p class="mt-1 text-sm font-semibold text-slate-800"><?= $donorLocation ?></p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php elseif ($showPendingAssignment): ?>
                <div class="mt-6 rounded-xl border border-amber-200 bg-amber-50 p-5">
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <p class="text-xs font-bold uppercase tracking-wider text-amber-600">Assigned Donor</p>
                            <h4 class="mt-1 text-lg font-black text-slate-900"><?= $donorName ?></h4>
                        </div>
                        <span class="rounded-full bg-white px-3 py-1 text-sm font-semibold text-amber-600"><?= $statusLabel ?></span>
                    </div>
                    <p class="mt-3 text-sm text-amber-700">This donor has been assigned but has not accepted the request yet.</p>

                    <div class="mt-4 grid gap-3">
                        <div class="rounded-lg bg-white/80 p-3">
                            <p class="text-xs font-bold uppercase tracking-wider text-slate-400">Blood Group</p>
                            <p class="mt-1 text-sm font-semibold text-slate-800"><?= $donorBlood ?></p>
                        </div>
                        <div class="rounded-lg bg-white/80 p-3">
                            <p class="text-xs font-bold uppercase tracking-wider text-slate-400">Phone</p>
                            <p class="mt-1 text-sm font-semibold text-slate-800"><?= $donorPhone ?></p>
                        </div>
                        <div class="rounded-lg bg-white/80 p-3">
                            <p class="text-xs font-bold uppercase tracking-wider text-slate-400">Email</p>
                            <p class="mt-1 text-sm font-semibold text-slate-800"><?= $donorEmail ?></p>
                        </div>
                        <div class="rounded-lg bg-white/80 p-3">
                            <p class="text-xs font-bold uppercase tracking-wider text-slate-400">Address</p>
                            <p class="mt-1 text-sm font-semibold text-slate-800"><?= $donorAddress ?></p>
                        </div>
                        <?php if (!empty($donorLocation)): ?>
                        <div class="rounded-lg bg-white/80 p-3">
                            <p class="text-xs font-bold uppercase tracking-wider text-slate-400">Location</p>
                            <p class="mt-1 text-sm font-semibold text-slate-800"><?= $donorLocation ?></p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php elseif ($isDeclined): ?>
                <div class="mt-6 rounded-xl border border-rose-200 bg-rose-50 p-5">
                    <div class="flex items-center gap-3">
                        <i class="fa-solid fa-circle-xmark text-rose-500 text-lg"></i>
                        <div>
                            <p class="text-xs font-bold uppercase tracking-wider text-rose-600">Donor Declined</p>
                            <p class="mt-1 text-sm text-rose-700">The assigned donor has declined this request. It is no longer active.</p>
                        </div>
                    </div>
                </div>
            <?php elseif ($isExpired): ?>
                <div class="mt-6 rounded-xl border border-slate-200 bg-slate-100 p-5">
                    <div class="flex items-center gap-3">
                        <i class="fa-solid fa-hourglass-end text-slate-500 text-lg"></i>
                        <div>
                            <p class="text-xs font-bold uppercase tracking-wider text-slate-500">Request Expired</p>
                            <p class="mt-1 text-sm text-slate-600">This request expired before a donor accepted it. Please create a new request.</p>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <div class="mt-6 rounded-xl border border-dashed border-slate-200 bg-slate-50 p-6 text-center text-sm text-slate-500">
                    No donor has been assigned to this request yet.
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>