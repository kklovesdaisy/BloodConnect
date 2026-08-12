<?php

use App\Admin\Application\UseCase\ReportDataProvider;
use App\Shared\Helpers\AdminFilters;

$db = \App\Shared\Infrastructure\Database\Database::getConnection();

$isHospitalTab = (($reportTab ?? 'donor') === 'hospital');

$bloodGroupOptions = [];
foreach (AdminFilters::bloodGroups($db) as $code => $label) {
    $bloodGroupOptions[$label] = $label;
}

if ($isHospitalTab) {
    $columns = ['Request Code', 'Hospital', 'Location', 'Blood Group', 'Requested', 'Allocated', 'Pending', 'Status', 'Date'];
    $pillColumns = [7];
    $statusOptions = [
        'PENDING' => 'Pending',
        'PARTIAL' => 'Partial',
        'COMPLETED' => 'Completed',
        'DECLINED' => 'Declined',
    ];
    $reportFilterConfig = [
        'action' => '/BloodConnect/public/admin/reports/donations',
        'search_placeholder' => 'Search hospital, request code, phone, email or address…',
        'fields' => [
            [
                'type' => 'select',
                'name' => 'status',
                'label' => 'Status',
                'options' => $statusOptions,
            ],
            [
                'type' => 'select',
                'name' => 'blood_group',
                'label' => 'Blood Group',
                'options' => $bloodGroupOptions,
            ],
            ['type' => 'date', 'name' => 'from', 'label' => 'From'],
            ['type' => 'date', 'name' => 'to', 'label' => 'To'],
        ],
    ];
} else {
    $columns = ['Donation ID', 'Donor', 'Patient', 'Date', 'Hospital', 'Status'];
    $pillColumns = [5];
    $reportFilterConfig = [
        'action' => '/BloodConnect/public/admin/reports/donations',
        'search_placeholder' => 'Search donor, patient, hospital or request code…',
        'fields' => [
            [
                'type' => 'select',
                'name' => 'status',
                'label' => 'Status',
                'options' => ReportDataProvider::statusOptions(),
            ],
            [
                'type' => 'select',
                'name' => 'blood_group',
                'label' => 'Blood Group',
                'options' => $bloodGroupOptions,
            ],
            ['type' => 'date', 'name' => 'from', 'label' => 'From'],
            ['type' => 'date', 'name' => 'to', 'label' => 'To'],
        ],
    ];
}
?>

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">

<style>
    body { font-family: 'Plus Jakarta Sans', sans-serif; background-color: #faf8f8; }
    .mono { font-family: 'JetBrains Mono', monospace; }
    @keyframes springReveal {
        0% { opacity: 0; transform: translateY(20px) scale(0.99); }
        100% { opacity: 1; transform: translateY(0) scale(1); }
    }
    .animate-spring-in { opacity: 0; animation: springReveal 0.5s cubic-bezier(0.16, 1, 0.3, 1) forwards; }
</style>

<div class="min-h-screen transition-all opacity-0 translate-y-6 ease-out duration-700" id="reportContainer">

    <div class="relative bg-white border border-slate-200/60 rounded-2xl p-6 mb-6 shadow-xs flex flex-col md:flex-row md:items-center md:justify-between gap-6">
        <div class="flex items-center gap-4">
            <div class="w-12 h-12 bg-gradient-to-br from-emerald-500 to-teal-600 text-white rounded-2xl flex items-center justify-center shadow-lg shadow-emerald-600/10 transform hover:scale-105 transition-transform duration-300">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="w-6 h-6">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12z" />
                </svg>
            </div>
            <div>
                <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight leading-none"><?= htmlspecialchars($reportTitle) ?></h1>
                <p class="text-xs sm:text-sm text-slate-400 font-medium mt-1.5"><?= $isHospitalTab ? 'Blood bags allocated from central stock to hospitals, with full bag and hospital details.' : 'Completed donations ledger with donor, patient and hospital.' ?></p>
            </div>
        </div>

        <div class="flex items-center gap-4 bg-slate-900 text-white px-5 py-3 rounded-2xl shadow-lg border border-slate-800 self-start md:self-auto">
            <div class="flex flex-col">
                <span class="text-[9px] text-slate-400 font-bold uppercase tracking-widest"><?= $isHospitalTab ? 'Allocations' : 'Donations' ?></span>
                <span class="text-lg font-extrabold tracking-tight mt-0.5 mono"><?= sprintf('%02d', count($reportData['rows'])) ?> <span class="text-xs text-slate-500 font-normal">rows</span></span>
            </div>
            <div class="w-2.5 h-2.5 rounded-full bg-red-500 shadow-[0_0_10px_rgba(239,68,68,0.6)] animate-pulse"></div>
        </div>
    </div>

    <div class="flex items-center gap-2 mb-6" role="tablist">
        <a href="<?= htmlspecialchars('/BloodConnect/public/admin/reports/donations' . AdminFilters::preserve(['tab' => 'donor', 'page' => null])) ?>"
            class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl font-bold text-sm transition-all active:scale-95 <?= !$isHospitalTab ? 'bg-[#ce2424] text-white shadow-md shadow-red-600/20' : 'bg-white border border-slate-200 text-slate-500 hover:text-slate-800 hover:border-slate-300' ?>">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-4 h-4"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z" /></svg>
            Donor Donations
        </a>
        <a href="<?= htmlspecialchars('/BloodConnect/public/admin/reports/donations' . AdminFilters::preserve(['tab' => 'hospital', 'page' => null])) ?>"
            class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl font-bold text-sm transition-all active:scale-95 <?= $isHospitalTab ? 'bg-[#ce2424] text-white shadow-md shadow-red-600/20' : 'bg-white border border-slate-200 text-slate-500 hover:text-slate-800 hover:border-slate-300' ?>">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-4 h-4"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 21h19.5m-18-18v18m10.5-18v18m6-13.5V21M6.75 6.75h.75m-.75 3h.75m-.75 3h.75m3-6h.75m-.75 3h.75m-.75 3h.75M6.75 21v-3.375c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21M3 3h12m-.75 4.5H21m-3.75 3.75h.008v.008h-.008v-.008zm0 3h.008v.008h-.008v-.008zm0 3h.008v.008h-.008v-.008z" /></svg>
            System to Hospital
        </a>
    </div>

    <?php require __DIR__ . '/../../../Shared/Presentation/View/partials/report_filter_bar.php'; ?>

    <?php require __DIR__ . '/../../../Shared/Presentation/View/partials/report_table.php'; ?>

</div>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const viewport = document.getElementById('reportContainer');
        if (viewport) {
            requestAnimationFrame(() => {
                viewport.classList.remove('opacity-0', 'translate-y-6');
            });
        }
    });
</script>
