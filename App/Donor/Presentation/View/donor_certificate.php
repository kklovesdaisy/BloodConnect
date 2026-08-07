<?php
$donation = $donation ?? [];
$requestId = (int)($request_id ?? 0);

$donorName = htmlspecialchars((string)($donation['donor_name'] ?? ''));
$donorEmail = htmlspecialchars((string)($donation['donor_email'] ?? ''));
$bloodGroup = htmlspecialchars((string)($donation['donor_blood_group'] ?? ($donation['blood_group_needed'] ?? '')));
$donationDate = !empty($donation['donation_date'])
    ? htmlspecialchars(date('d-M-Y', strtotime((string)$donation['donation_date'])))
    : '&mdash;';
$hospital = htmlspecialchars((string)($donation['hospital_name'] ?? ''));
$requestCode = htmlspecialchars((string)($donation['request_code'] ?? ''));
$certNumber = htmlspecialchars((string)($donation['certificate_number'] ?? 'BC-000000'));
$logoUrl = '/BloodConnect/public/images/bloodconnect-logo.png';
?>

<div class="max-w-5xl mx-auto p-4 sm:p-6 lg:p-8">

    <div class="relative bg-white border border-slate-200/60 rounded-2xl p-6 mb-8 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-6 shadow-xs">
        <div class="flex items-center gap-4">
            <div class="w-12 h-12 bg-[#ce2424] text-white rounded-2xl flex items-center justify-center shadow-lg shadow-red-600/10 shrink-0">
                <i class="fa-solid fa-award"></i>
            </div>
            <div>
                <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight leading-none">Donation Certificate</h1>
                <p class="text-xs sm:text-sm text-slate-400 font-medium mt-1.5">View and download your official BloodConnect donation certificate.</p>
            </div>
        </div>

        <div class="flex items-center gap-2 self-start sm:self-auto">
            <a href="/BloodConnect/public/donor/donation/certificate?id=<?= $requestId ?>"
                class="inline-flex items-center gap-2 rounded-xl bg-red-600 px-4 py-2.5 text-xs font-bold text-white hover:bg-red-700 shadow-sm transition-all active:scale-95">
                <i class="fa-solid fa-download"></i> Download PDF
            </a>
            <a href="/BloodConnect/public/donor/history/view?id=<?= $requestId ?>" class="inline-flex items-center gap-2 rounded-xl border border-slate-200/80 bg-white px-4 py-2.5 text-xs font-bold text-slate-600 hover:bg-slate-50 shadow-3xs transition-all active:scale-95">
                <i class="fa-solid fa-arrow-left"></i> Back
            </a>
        </div>
    </div>

    <!-- Certificate Preview -->
    <div class="bg-white border border-slate-200/70 rounded-3xl shadow-2xs p-4 sm:p-6">
        <div class="border-4 border-[#ce2424] rounded-2xl p-2">
            <div class="border border-[#ce2424] rounded-xl px-4 sm:px-10 py-8 sm:py-10 text-center bg-gradient-to-b from-white to-red-50/40">

                <div class="flex items-center justify-center gap-3 pb-5 mb-5 border-b border-slate-100">
                    <img src="<?= $logoUrl ?>" alt="BloodConnect logo" class="w-12 h-12 object-contain">
                    <div class="text-left">
                        <p class="font-black text-xl text-[#ce2424] tracking-tight">BloodConnect <span class="text-slate-900">&bull; Blood Donation Network</span></p>
                        <p class="text-[11px] text-slate-400 font-medium">Connecting donors with patients to save lives.</p>
                    </div>
                </div>

                <h2 class="text-2xl sm:text-3xl font-black uppercase tracking-[0.2em] text-slate-900">Certificate of Donation</h2>
                <p class="text-sm text-slate-400 font-medium mt-1.5">Acknowledging a voluntary, life-saving blood donation</p>

                <div class="mt-5">
                    <span class="inline-block border border-dashed border-slate-300 bg-slate-50 px-5 py-1.5 rounded-full text-xs font-bold text-slate-500">
                        Certificate No. <span class="text-slate-800"><?= $certNumber ?></span>
                    </span>
                </div>

                <div class="mt-6">
                    <p class="text-sm text-slate-500 font-medium">This is to proudly certify that</p>
                    <p class="text-3xl sm:text-4xl font-black text-slate-900 tracking-tight my-2"><?= $donorName !== '' ? $donorName : '&mdash;' ?></p>
                    <p class="text-sm text-slate-500 font-medium">has made a blood donation through the BloodConnect network on the date stated below.</p>
                </div>

                <div class="mt-7 grid grid-cols-1 sm:grid-cols-2 gap-3 max-w-2xl mx-auto">
                    <div class="rounded-xl border border-slate-200/80 bg-white/80 p-3.5 flex items-center justify-between gap-2">
                        <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Blood Group</span>
                        <span class="font-black text-xl text-[#ce2424] tracking-wider"><?= $bloodGroup !== '' ? $bloodGroup : '&mdash;' ?></span>
                    </div>
                    <div class="rounded-xl border border-slate-200/80 bg-white/80 p-3.5 flex items-center justify-between gap-2">
                        <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Donation Date</span>
                        <span class="font-bold text-slate-800"><?= $donationDate ?></span>
                    </div>
                    <div class="rounded-xl border border-slate-200/80 bg-white/80 p-3.5 flex items-center justify-between gap-2 sm:col-span-2">
                        <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Donation Hospital</span>
                        <span class="font-bold text-slate-800 text-right"><?= $hospital !== '' ? $hospital : '&mdash;' ?></span>
                    </div>
                    <?php if ($requestCode !== ''): ?>
                    <div class="rounded-xl border border-slate-200/80 bg-white/80 p-3.5 flex items-center justify-between gap-2 sm:col-span-2">
                        <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Request Reference</span>
                        <span class="font-bold text-slate-800 font-mono"><?= $requestCode ?></span>
                    </div>
                    <?php endif; ?>
                    <?php if ($donorEmail !== ''): ?>
                    <div class="rounded-xl border border-slate-200/80 bg-white/80 p-3.5 flex items-center justify-between gap-2 sm:col-span-2">
                        <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Donor Email</span>
                        <span class="font-bold text-slate-800 truncate"><?= $donorEmail ?></span>
                    </div>
                    <?php endif; ?>
                </div>

                <div class="mt-7">
                    <span class="inline-block border-[3px] border-[#ce2424] text-[#ce2424] font-black uppercase tracking-widest text-sm px-5 py-1.5 rounded-lg -rotate-6 opacity-85">
                        Verified by BloodConnect
                    </span>
                </div>
            </div>
        </div>
    </div>
</div>
