<?php

use App\Shared\Helpers\AdminFilters;

$db = \App\Shared\Infrastructure\Database\Database::getConnection();

$columns = ['Patient ID', 'Name', 'Blood Group', 'Phone', 'Status'];
$pillColumns = [4];

$reportFilterConfig = [
    'action' => '/BloodConnect/public/admin/reports/patients',
    'search_placeholder' => 'Search name, phone or blood group…',
    'fields' => [
        [
            'type' => 'select',
            'name' => 'status',
            'label' => 'Status',
            'options' => ['active' => 'Active', 'inactive' => 'Inactive'],
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

<div class="min-h-screen transition-all opacity-0 translate-y-6 ease-out duration-700" id="reportContainer">

    <div class="relative bg-white border border-slate-200/60 rounded-2xl p-6 mb-6 shadow-xs flex flex-col md:flex-row md:items-center md:justify-between gap-6">
        <div class="flex items-center gap-4">
            <div class="w-12 h-12 bg-gradient-to-br from-blue-500 to-indigo-600 text-white rounded-2xl flex items-center justify-center shadow-lg shadow-blue-600/10 transform hover:scale-105 transition-transform duration-300">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="w-6 h-6">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z" />
                </svg>
            </div>
            <div>
                <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight leading-none"><?= htmlspecialchars($reportTitle) ?></h1>
                <p class="text-xs sm:text-sm text-slate-400 font-medium mt-1.5">Registered patients seeking blood, with their profile details.</p>
            </div>
        </div>

        <div class="flex items-center gap-4 bg-slate-900 text-white px-5 py-3 rounded-2xl shadow-lg border border-slate-800 self-start md:self-auto">
            <div class="flex flex-col">
                <span class="text-[9px] text-slate-400 font-bold uppercase tracking-widest">Patients</span>
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
