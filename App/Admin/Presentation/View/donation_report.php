<?php

use App\Admin\Application\UseCase\ReportDataProvider;
use App\Shared\Helpers\AdminFilters;

$db = \App\Shared\Infrastructure\Database\Database::getConnection();

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
            'options' => AdminFilters::bloodGroups($db),
        ],
        ['type' => 'date', 'name' => 'from', 'label' => 'From'],
        ['type' => 'date', 'name' => 'to', 'label' => 'To'],
    ],
];
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

<div class="max-w-7xl mx-auto p-4 sm:p-6 lg:p-8 min-h-screen transition-all opacity-0 translate-y-6 ease-out duration-700" id="reportContainer">

    <div class="relative bg-white border border-slate-200/60 rounded-2xl p-6 mb-6 shadow-xs flex flex-col md:flex-row md:items-center md:justify-between gap-6">
        <div class="flex items-center gap-4">
            <div class="w-12 h-12 bg-gradient-to-br from-emerald-500 to-teal-600 text-white rounded-2xl flex items-center justify-center shadow-lg shadow-emerald-600/10 transform hover:scale-105 transition-transform duration-300">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="w-6 h-6">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12z" />
                </svg>
            </div>
            <div>
                <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight leading-none"><?= htmlspecialchars($reportTitle) ?></h1>
                <p class="text-xs sm:text-sm text-slate-400 font-medium mt-1.5">Completed donations ledger with donor, patient and hospital.</p>
            </div>
        </div>

        <div class="flex items-center gap-4 bg-slate-900 text-white px-5 py-3 rounded-2xl shadow-lg border border-slate-800 self-start md:self-auto">
            <div class="flex flex-col">
                <span class="text-[9px] text-slate-400 font-bold uppercase tracking-widest">Donations</span>
                <span class="text-lg font-extrabold tracking-tight mt-0.5 mono"><?= sprintf('%02d', count($reportData['rows'])) ?> <span class="text-xs text-slate-500 font-normal">rows</span></span>
            </div>
            <div class="w-2.5 h-2.5 rounded-full bg-red-500 shadow-[0_0_10px_rgba(239,68,68,0.6)] animate-pulse"></div>
        </div>
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
