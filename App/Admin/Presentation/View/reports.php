<?php

use App\Shared\Infrastructure\Database\Database;

$db = Database::getConnection();
$counts = [];
$counts['donors'] = (int)$db->query("SELECT COUNT(*) FROM users WHERE user_type_id = 2 AND deleted_at IS NULL")->fetchColumn();
$counts['patients'] = (int)$db->query("SELECT COUNT(*) FROM users WHERE user_type_id = 3 AND deleted_at IS NULL")->fetchColumn();
$counts['requests'] = (int)$db->query("SELECT COUNT(*) FROM blood_requests")->fetchColumn();
$counts['donations'] = (int)$db->query(
    "SELECT COUNT(*) FROM blood_requests
     WHERE status IN (
         SELECT id FROM master_data
         WHERE category = 'REQUEST_STATUS' AND code IN ('ACCEPTED', 'COMPLETED')
     )"
)->fetchColumn();

$reportCards = [
    [
        'title' => 'Donor Report',
        'description' => 'All registered donors with blood group, contact and status.',
        'count' => $counts['donors'],
        'countLabel' => 'Donors',
        'href' => '/BloodConnect/public/admin/reports/donors',
        'icon' => 'drop',
        'accent' => 'from-red-500 to-rose-600',
    ],
    [
        'title' => 'Patient Report',
        'description' => 'Registered patients seeking blood, with their profile details.',
        'count' => $counts['patients'],
        'countLabel' => 'Patients',
        'href' => '/BloodConnect/public/admin/reports/patients',
        'icon' => 'users',
        'accent' => 'from-blue-500 to-indigo-600',
    ],
    [
        'title' => 'Blood Request Report',
        'description' => 'Every blood request with urgency, hospital and current status.',
        'count' => $counts['requests'],
        'countLabel' => 'Requests',
        'href' => '/BloodConnect/public/admin/reports/blood-requests',
        'icon' => 'clipboard',
        'accent' => 'from-amber-500 to-orange-600',
    ],
    [
        'title' => 'Donation Report',
        'description' => 'Completed donations ledger with donor, patient and hospital.',
        'count' => $counts['donations'],
        'countLabel' => 'Donations',
        'href' => '/BloodConnect/public/admin/reports/donations',
        'icon' => 'heart',
        'accent' => 'from-emerald-500 to-teal-600',
    ],
];

$iconSvg = [
    'drop' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-6 h-6 text-white"><path d="M12 2.69l5.66 5.66a8 8 0 1 1-11.31 0z" /></svg>',
    'users' => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="w-6 h-6 text-white"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z" /></svg>',
    'clipboard' => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="w-6 h-6 text-white"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25z" /></svg>',
    'heart' => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="w-6 h-6 text-white"><path stroke-linecap="round" stroke-linejoin="round" d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12z" /></svg>',
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
    .report-card:hover { box-shadow: 0 20px 40px -12px rgba(206, 36, 36, 0.12); transform: translateY(-3px); }
</style>

<div class="max-w-7xl mx-auto p-4 sm:p-6 lg:p-8 min-h-screen transition-all opacity-0 translate-y-6 ease-out duration-700" id="reportsContainer">

    <div class="relative bg-white border border-slate-200/60 rounded-2xl p-6 mb-8 shadow-xs flex flex-col md:flex-row md:items-center md:justify-between gap-6 overflow-hidden">
        <div class="flex items-center gap-4">
            <div class="w-12 h-12 bg-gradient-to-br from-[#ce2424] to-rose-600 text-white rounded-2xl flex items-center justify-center shadow-lg shadow-red-600/10 transform hover:scale-105 transition-transform duration-300">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="w-6 h-6">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z" />
                </svg>
            </div>
            <div>
                <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight leading-none">Export Reports</h1>
                <p class="text-xs sm:text-sm text-slate-400 font-medium mt-1.5">Download system data as printable PDF or editable Excel files.</p>
            </div>
        </div>

        <div class="flex items-center gap-3">
            <span class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-emerald-50 border border-emerald-200/60 text-emerald-700 text-xs font-bold">
                <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span> Excel Export Ready
            </span>
            <span class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-red-50 border border-red-200/60 text-red-700 text-xs font-bold">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-4 h-4">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m2.25 12l3 3m0 0l3-3m-3 3v-6" />
                </svg>
                PDF Export Ready
            </span>
        </div>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-5">
        <?php foreach ($reportCards as $index => $card): ?>
            <a href="<?= htmlspecialchars($card['href']) ?>"
                class="report-card group bg-white border border-slate-200/60 rounded-2xl p-6 shadow-xs transition-all duration-300 animate-spring-in"
                style="animation-delay: <?= $index * 0.06 ?>s;">
                <div class="flex items-start justify-between mb-5">
                    <div class="w-12 h-12 bg-gradient-to-br <?= htmlspecialchars($card['accent']) ?> rounded-2xl flex items-center justify-center shadow-lg shadow-slate-200 group-hover:scale-110 transition-transform duration-300">
                        <?= $iconSvg[$card['icon']] ?>
                    </div>
                    <span class="text-[10px] font-extrabold uppercase tracking-widest text-slate-400 mono">Report</span>
                </div>

                <h2 class="text-lg font-extrabold text-slate-900 tracking-tight group-hover:text-[#ce2424] transition-colors"><?= htmlspecialchars($card['title']) ?></h2>
                <p class="text-xs text-slate-500 font-medium mt-1.5 leading-relaxed"><?= htmlspecialchars($card['description']) ?></p>

                <div class="mt-5 pt-4 border-t border-slate-100 flex items-center justify-between">
                    <div>
                        <span class="block text-[10px] font-bold uppercase tracking-widest text-slate-400"><?= htmlspecialchars($card['countLabel']) ?></span>
                        <span class="block text-2xl font-extrabold text-slate-900 mono mt-0.5"><?= sprintf('%02d', $card['count']) ?></span>
                    </div>
                    <span class="w-9 h-9 rounded-xl bg-slate-50 border border-slate-200 flex items-center justify-center text-slate-400 group-hover:bg-red-50 group-hover:text-[#ce2424] group-hover:border-red-200 transition-all">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.2" stroke="currentColor" class="w-4 h-4">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 003 8.25v10.5A2.25 2.25 0 005.25 21h10.5A2.25 2.25 0 0018 18.75V10.5M10.5 13.5L20.25 3.75M20.25 3.75H15.75M20.25 3.75v4.5" />
                        </svg>
                    </span>
                </div>
            </a>
        <?php endforeach; ?>
    </div>

    <div class="mt-8 bg-white border border-slate-200/60 rounded-2xl p-6 shadow-xs">
        <h3 class="text-sm font-extrabold text-slate-900 tracking-tight uppercase">What can I do?</h3>
        <div class="mt-4 grid grid-cols-1 md:grid-cols-3 gap-5 text-xs text-slate-500 font-medium leading-relaxed">
            <div class="flex gap-3">
                <span class="w-8 h-8 shrink-0 rounded-lg bg-red-50 border border-red-100 text-[#ce2424] flex items-center justify-center font-extrabold">1</span>
                <p>Open any report and narrow the result set with search, date range and status filters.</p>
            </div>
            <div class="flex gap-3">
                <span class="w-8 h-8 shrink-0 rounded-lg bg-red-50 border border-red-100 text-[#ce2424] flex items-center justify-center font-extrabold">2</span>
                <p>Click <strong>Export PDF</strong> to save a print-ready copy for sharing or records.</p>
            </div>
            <div class="flex gap-3">
                <span class="w-8 h-8 shrink-0 rounded-lg bg-red-50 border border-red-100 text-[#ce2424] flex items-center justify-center font-extrabold">3</span>
                <p>Click <strong>Export Excel</strong> to download a .xlsx you can sort, filter and edit later.</p>
            </div>
        </div>
    </div>

</div>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const viewport = document.getElementById('reportsContainer');
        if (viewport) {
            requestAnimationFrame(() => {
                viewport.classList.remove('opacity-0', 'translate-y-6');
            });
        }
    });
</script>
