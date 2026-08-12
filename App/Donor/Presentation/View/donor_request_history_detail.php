<?php
$request = $request ?? [];
$donationId = (int)($donation_id ?? 0);
$donorDetails = $donorDetails ?? null;
$patientName = htmlspecialchars((string)($request['patient_username'] ?? $request['patient_name'] ?? 'Patient'));
$patientEmail = htmlspecialchars((string)($request['patient_email'] ?? '-'));
$patientPhone = htmlspecialchars((string)($request['patient_phone'] ?? '-'));
$patientAddress = htmlspecialchars((string)($request['patient_address'] ?? '-'));
$requestCode = htmlspecialchars((string)($request['request_code'] ?? 'N/A'));
$bloodGroup = htmlspecialchars((string)($request['blood_group_needed'] ?? ''));
$units = (int)($request['unit'] ?? 0);
$hospitalName = htmlspecialchars((string)($request['hospital_name'] ?? '-'));
$contactPhone = htmlspecialchars((string)($request['contact_phone'] ?? '-'));
$createdAt = htmlspecialchars((string)($request['created_at'] ?? '-'));
$statusName = htmlspecialchars((string)($request['status_name'] ?? $request['status'] ?? 'Accepted'));
$reqLocation = htmlspecialchars((string)($request['township'] ?? '—')) . ' / ' . htmlspecialchars((string)($request['state_region'] ?? '—'));
$donorLoc = $donorDetails ? array_filter([$donorDetails['township'] ?? '', $donorDetails['state_region'] ?? '']) : [];
$donorLocation = !empty($donorLoc) ? htmlspecialchars(implode(', ', $donorLoc)) : '';
?>

<div class="max-w-7xl mx-auto p-4 sm:p-6 lg:p-8">

    <div class="relative bg-white border border-slate-200/60 rounded-2xl p-6 mb-8 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-6 shadow-xs">
        <div class="flex items-center gap-4">
            <div class="w-12 h-12 bg-[#ce2424] text-white rounded-2xl flex items-center justify-center shadow-lg shadow-red-600/10 shrink-0">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-6 h-6 text-white">
                    <path d="M12 2.69l5.66 5.66a8 8 0 1 1-11.31 0z" />
                </svg>
            </div>
            <div>
                <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight leading-none">Accepted Request Details</h1>
                <p class="text-xs sm:text-sm text-slate-400 font-medium mt-1.5">View the blood request details and your donor profile.</p>
            </div>
        </div>

        <div class="flex items-center gap-2 self-start sm:self-auto">
            <?php if (!empty($donationId)): ?>
            <a href="/BloodConnect/public/donor/donation/certificate/view?id=<?= (int)$donationId ?>"
                class="inline-flex items-center gap-2 rounded-xl bg-red-600 px-4 py-2.5 text-xs font-bold text-white hover:bg-red-700 shadow-sm transition-all active:scale-95">
                <i class="fa-solid fa-award"></i> View Certificate
            </a>
            <?php endif; ?>
            <a href="/BloodConnect/public/donor/history" class="inline-flex items-center gap-2 rounded-xl border border-slate-200/80 bg-white px-4 py-2.5 text-xs font-bold text-slate-600 hover:bg-slate-50 shadow-3xs transition-all active:scale-95">
                <i class="fa-solid fa-arrow-left"></i> Back to History
            </a>
        </div>
    </div>

    <div class="grid gap-6 lg:grid-cols-[1.15fr_0.85fr]">

        <div class="bg-white border border-slate-200/70 rounded-3xl p-6 shadow-2xs space-y-6">
            <div class="flex items-center justify-between gap-3 border-b border-slate-100 pb-5">
                <div>
                    <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 font-mono block">Ticket Reference Code</span>
                    <h2 class="mt-1 text-xl font-black text-slate-900 tracking-tight"><?= $requestCode ?></h2>
                </div>
                <span class="inline-block px-3 py-1 text-[9px] font-extrabold rounded-lg uppercase tracking-widest border bg-emerald-50 text-emerald-600 border-emerald-200/50 shadow-3xs">
                    <?= $statusName ?>
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
                        <span class="inline-flex items-center justify-center px-2 py-0.5 rounded-md border font-black text-xs font-mono text-emerald-600 bg-emerald-50 border-emerald-200/40">
                            <?= $bloodGroup ?>
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
                    <p class="text-sm font-bold text-slate-800"><?= $reqLocation ?></p>
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
                    <p class="font-bold text-slate-700">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-3.5 h-3.5 text-slate-400 inline mr-1.5 -mt-0.5">
                            <path fill-rule="evenodd" d="M1.5 4.5a3 3 0 013-3h1.372c.86 0 1.61.586 1.819 1.42l1.105 4.423a1.875 1.875 0 01-.694 1.955l-1.293.97c-.135.101-.164.249-.126.352a11.285 11.285 0 006.697 6.697c.103.038.25.009.352-.126l.97-1.293a1.875 1.875 0 011.955-.694l4.423 1.105c.834.209 1.42.959 1.42 1.82V19.5a3 3 0 01-3 3h-2.25C8.552 22.5 1.5 15.448 1.5 6.75V4.5z" clip-rule="evenodd" />
                        </svg>
                        <?= $contactPhone ?>
                    </p>
                    <p class="font-semibold text-slate-400 font-mono">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-3.5 h-3.5 text-slate-300 inline mr-1.5 -mt-0.5">
                            <path fill-rule="evenodd" d="M6.75 2.25A.75.75 0 017.5 3v1.5h9V3A.75.75 0 0118 3v1.5h.75a3 3 0 013 3v11.25a3 3 0 01-3 3H5.25a3 3 0 01-3-3V7.5a3 3 0 013-3H6V3a.75.75 0 01.75-.75zm13.5 9a1.5 1.5 0 00-1.5-1.5H5.25a1.5 1.5 0 00-1.5 1.5v7.5a1.5 1.5 0 001.5 1.5h13.5a1.5 1.5 0 001.5-1.5v-7.5z" clip-rule="evenodd" />
                        </svg>
                        <?= $createdAt ?>
                    </p>
                </div>
            </div>
        </div>

        <div class="bg-white border border-slate-200/70 rounded-3xl p-6 shadow-2xs space-y-6">
            <div class="rounded-2xl border border-emerald-200 bg-emerald-50/50 p-5 space-y-4">
                <div class="flex items-center justify-between gap-3">
                    <div>
                        <span class="text-[9px] font-extrabold uppercase tracking-widest text-emerald-600 block">Fulfillment Active</span>
                        <h4 class="mt-1 text-base font-black text-slate-900 tracking-tight">
                            <?= htmlspecialchars((string)($user['username'] ?? 'Donor')) ?>
                        </h4>
                    </div>
                    <span class="rounded-full bg-emerald-600 px-3 py-1 text-[10px] font-extrabold uppercase tracking-wider text-white shadow-xs shadow-emerald-600/10">Accepted</span>
                </div>

                <div class="mt-4 grid gap-3">
                    <div class="rounded-lg bg-white/80 p-3">
                        <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Blood Type</span>
                        <p class="mt-1 text-sm font-semibold text-slate-800"><?= htmlspecialchars((string)($user['blood_group'] ?? '-')) ?></p>
                    </div>
                    <div class="rounded-lg bg-white/80 p-3">
                        <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Direct Line</span>
                        <p class="mt-1 text-sm font-semibold text-slate-800"><?= htmlspecialchars((string)($user['phone'] ?? '-')) ?></p>
                    </div>
                    <div class="rounded-lg bg-white/80 p-3">
                        <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Email</span>
                        <p class="mt-1 text-sm font-semibold text-slate-800 truncate"><?= htmlspecialchars((string)($user['email'] ?? '-')) ?></p>
                    </div>
                    <div class="rounded-lg bg-white/80 p-3">
                        <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Address</span>
                        <p class="mt-1 text-sm font-semibold text-slate-800"><?= htmlspecialchars((string)($user['address'] ?? '-')) ?></p>
                    </div>
                    <?php if (!empty($donorLocation)): ?>
                    <div class="rounded-lg bg-white/80 p-3">
                        <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Location</span>
                        <p class="mt-1 text-sm font-semibold text-slate-800"><?= $donorLocation ?></p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
