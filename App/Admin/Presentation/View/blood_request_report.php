<?php

use App\Admin\Application\UseCase\ReportDataProvider;
use App\Shared\Helpers\AdminFilters;

$db = \App\Shared\Infrastructure\Database\Database::getConnection();

$columns = ['Request ID', 'Patient', 'Blood Group', 'Hospital', 'Urgency', 'Status'];
$pillColumns = [4, 5];

$reportFilterConfig = [
    'action' => '/BloodConnect/public/admin/reports/blood-requests',
    'search_placeholder' => 'Search request code, patient, hospital or phone…',
    'fields' => [
        [
            'type' => 'select',
            'name' => 'status',
            'label' => 'Status',
            'options' => ReportDataProvider::statusOptions(),
        ],
        [
            'type' => 'select',
            'name' => 'urgency',
            'label' => 'Urgency',
            'options' => ['Standard' => 'Standard', 'Urgent' => 'Urgent', 'Critical' => 'Critical'],
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
            <div class="w-12 h-12 bg-gradient-to-br from-amber-500 to-orange-600 text-white rounded-2xl flex items-center justify-center shadow-lg shadow-amber-600/10 transform hover:scale-105 transition-transform duration-300">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="w-6 h-6">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25z" />
                </svg>
            </div>
            <div>
                <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight leading-none"><?= htmlspecialchars($reportTitle) ?></h1>
                <p class="text-xs sm:text-sm text-slate-400 font-medium mt-1.5">Every blood request with urgency, hospital and current status.</p>
            </div>
        </div>

        <div class="flex items-center gap-4 bg-slate-900 text-white px-5 py-3 rounded-2xl shadow-lg border border-slate-800 self-start md:self-auto">
            <div class="flex flex-col">
                <span class="text-[9px] text-slate-400 font-bold uppercase tracking-widest">Requests</span>
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
