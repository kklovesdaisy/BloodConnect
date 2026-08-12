<?php

if (!isset($data) || !is_array($data)) {
    $data = [
        'adminName' => 'Admin',
        'totalDonors' => 0,
        'totalPatients' => 0,
        'pendingRequests' => 0,
        'acceptedRequests' => 0,
        'requestsChartData' => [],
        'donorsByBloodGroup' => [],
        'latestRequests' => [],
    ];
}

$adminName = $data['adminName'] ?? 'Admin';

$basePath = '/BloodConnect/public';

$stats = [
    ['label' => 'Total Donors', 'value' => $data['totalDonors'], 'icon' => '💧', 'bg' => 'bg-emerald-500/10 text-emerald-600 border-emerald-500/20', 'link' => "$basePath/admin/donor-management?filter=donors"],
    ['label' => 'Total Patients', 'value' => $data['totalPatients'], 'icon' => '🏥', 'bg' => 'bg-sky-500/10 text-sky-600 border-sky-500/20', 'link' => "$basePath/admin/user-management?filter=patients"],
    ['label' => 'Pending Requests', 'value' => $data['pendingRequests'], 'icon' => '⏳', 'bg' => 'bg-amber-500/10 text-amber-600 border-amber-500/20', 'link' => "$basePath/admin/blood-requests?filter=pending"],
    ['label' => 'Accepted Requests', 'value' => $data['acceptedRequests'] ?? 0, 'icon' => '📋', 'bg' => 'bg-indigo-500/10 text-indigo-600 border-indigo-500/20', 'link' => "$basePath/admin/blood-requests?filter=accepted"],
];

// ✅ RECENT ACTIVITY (provided by the controller via ActivityLogger)
$activities = $data['activities'] ?? [];

/**
 * TIME AGO FUNCTION (Optimized short versions for better mobile fitting)
 */
function timeAgo($datetime)
{
    $time = strtotime($datetime);
    $diff = time() - $time;

    if ($diff < 60) return $diff . "s ago";

    $diff = floor($diff / 60);
    if ($diff < 60) return $diff . "m ago";

    $diff = floor($diff / 60);
    if ($diff < 24) return $diff . "h ago";

    $diff = floor($diff / 24);
    if ($diff < 7) return $diff . "d ago";

    return date("d M", $time);
}
?>

<style>
    @keyframes fadeInUp {
        from {
            opacity: 0;
            transform: translateY(15px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .animate-fade-in-up {
        animation: fadeInUp 0.5s ease-out forwards;
    }

    .animation-delay-100 {
        animation-delay: 100ms;
    }

    .animation-delay-200 {
        animation-delay: 200ms;
    }

    .animation-delay-300 {
        animation-delay: 300ms;
    }

    /* Custom elegant minimalist scrollbar */
    .custom-scrollbar::-webkit-scrollbar {
        width: 6px;
    }

    .custom-scrollbar::-webkit-scrollbar-track {
        background: transparent;
    }

    .custom-scrollbar::-webkit-scrollbar-thumb {
        background-color: #cbd5e1;
        /* slate-300 */
        border-radius: 20px;
    }

    .custom-scrollbar::-webkit-scrollbar-thumb:hover {
        background-color: #94a3b8;
        /* slate-400 */
    }
</style>

<div class="space-y-6 md:space-y-8 py-6">

    <div class="animate-fade-in-up opacity-0">
        <p class="text-xs font-bold uppercase tracking-wider text-indigo-600 mb-1">Dashboard Overview</p>
        <h1 class="text-2xl md:text-3xl font-extrabold tracking-tight text-slate-950 flex items-center gap-2">
            Welcome back, <span class="bg-gradient-to-r align-middle from-rose-600 to-orange-500 bg-clip-text text-transparent"><?= htmlspecialchars($adminName) ?></span>! 👋
        </h1>
    </div>

    <section class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4 md:gap-6">
        <?php foreach ($stats as $index => $stat): ?>
            <?php
            $delayClass = match ($index) {
                1 => 'animation-delay-100',
                2 => 'animation-delay-200',
                3 => 'animation-delay-300',
                default => '',
            };
            ?>
            <a href="<?= $stat['link'] ?>" class="animate-fade-in-up opacity-0 <?= $delayClass ?> group relative bg-white/80 backdrop-blur-md rounded-2xl p-6 shadow-sm border border-slate-100 hover:border-slate-200 hover:shadow-xl hover:-translate-y-1 transition-all duration-300 ease-out cursor-pointer">

                <div class="absolute inset-0 bg-gradient-to-br from-slate-50/0 to-slate-50 rounded-2xl opacity-0 group-hover:opacity-100 transition-opacity duration-300 pointer-events-none"></div>

                <div class="relative flex items-center justify-between">
                    <div class="space-y-2">
                        <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider">
                            <?= htmlspecialchars($stat['label']) ?>
                        </p>
                        <p class="text-2xl md:text-3xl font-extrabold text-slate-900 tracking-tight stat-counter" data-target="<?= htmlspecialchars((string)$stat['value']) ?>">
                            0
                        </p>
                    </div>

                    <div class="<?= $stat['bg'] ?> border w-14 h-14 rounded-2xl flex items-center justify-center text-2xl shadow-inner transform group-hover:scale-110 transition-transform duration-300 ease-out">
                        <?= $stat['icon'] ?>
                    </div>
                </div>
            </a>
        <?php endforeach; ?>
    </section>

    <?php
    // SVG icon map per activity type
    $typeIcons = [
        'success' => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-4 h-4"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>',
        'warning' => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-4 h-4"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" /></svg>',
        'error'   => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-4 h-4"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" /></svg>',
        'info'    => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-4 h-4"><path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z" /></svg>',
    ];

    $typeStyles = [
        'success' => ['ring' => 'ring-emerald-500/20', 'bg' => 'bg-emerald-50', 'icon' => 'text-emerald-600', 'border' => 'border-l-emerald-500'],
        'warning' => ['ring' => 'ring-amber-500/20', 'bg' => 'bg-amber-50', 'icon' => 'text-amber-600', 'border' => 'border-l-amber-500'],
        'error'   => ['ring' => 'ring-rose-500/20', 'bg' => 'bg-rose-50', 'icon' => 'text-rose-600', 'border' => 'border-l-rose-500'],
        'info'    => ['ring' => 'ring-sky-500/20', 'bg' => 'bg-sky-50', 'icon' => 'text-sky-600', 'border' => 'border-l-sky-500'],
    ];
    ?>

    <section class="grid grid-cols-1 lg:grid-cols-2 gap-4 md:gap-6">

        <!-- Blood Requests Overview -->
        <div class="animate-fade-in-up opacity-0 animation-delay-100 bg-white/80 backdrop-blur-md rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
            <div class="px-5 sm:px-6 py-4 sm:py-5 border-b border-slate-100 flex items-center justify-between gap-3">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-xl bg-rose-50 border border-rose-100 flex items-center justify-center text-rose-600 shrink-0">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4.5 h-4.5" style="width:18px;height:18px;">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="font-bold text-slate-900 tracking-tight text-sm sm:text-base leading-tight">Blood Requests Overview</h3>
                        <p class="text-[11px] text-slate-400 font-medium leading-none mt-0.5">Request distribution and trends</p>
                    </div>
                </div>
                <select id="requestsMonthFilter" class="text-xs font-semibold text-slate-600 bg-slate-50 border border-slate-200 rounded-lg px-3 py-1.5 focus:outline-none focus:ring-2 focus:ring-rose-500/20 focus:border-rose-400 cursor-pointer transition-all duration-200 hover:bg-slate-100">
                    <option value="7">Last 7 days</option>
                    <option value="30" selected>Last 30 days</option>
                    <option value="90">Last 90 days</option>
                    <option value="365">This year</option>
                </select>
            </div>
            <div class="p-5 sm:p-6">
                <div style="position:relative;height:240px;">
                    <canvas id="requestsOverviewChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Donors by Blood Group -->
        <div class="animate-fade-in-up opacity-0 animation-delay-200 bg-white/80 backdrop-blur-md rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
            <div class="px-5 sm:px-6 py-4 sm:py-5 border-b border-slate-100 flex items-center gap-3">
                <div class="w-9 h-9 rounded-xl bg-emerald-50 border border-emerald-100 flex items-center justify-center text-emerald-600 shrink-0">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4.5 h-4.5" style="width:18px;height:18px;">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z" />
                    </svg>
                </div>
                <div>
                    <h3 class="font-bold text-slate-900 tracking-tight text-sm sm:text-base leading-tight">Donors by Blood Group</h3>
                    <p class="text-[11px] text-slate-400 font-medium leading-none mt-0.5">Blood type distribution of donors</p>
                </div>
            </div>
            <div class="p-5 sm:p-6 flex flex-col sm:flex-row items-center gap-6 sm:gap-8">
                <div style="position:relative;width:180px;height:180px;" class="shrink-0">
                    <canvas id="donorsBloodGroupChart"></canvas>
                </div>
                <div id="donorsLegend" class="flex-1 w-full space-y-2.5"></div>
            </div>
        </div>

        <!-- Recent Blood Requests -->
        <div class="animate-fade-in-up opacity-0 animation-delay-300 bg-white/80 backdrop-blur-md rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
            <div class="px-5 sm:px-6 py-4 sm:py-5 border-b border-slate-100 flex items-center justify-between gap-3">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-xl bg-amber-50 border border-amber-100 flex items-center justify-center text-amber-600 shrink-0">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4.5 h-4.5" style="width:18px;height:18px;">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="font-bold text-slate-900 tracking-tight text-sm sm:text-base leading-tight">Recent Blood Requests</h3>
                        <p class="text-[11px] text-slate-400 font-medium leading-none mt-0.5">Latest incoming donation requests</p>
                    </div>
                </div>
                <a href="<?= $basePath ?>/admin/blood-requests" class="text-xs font-semibold text-rose-600 hover:text-rose-700 bg-rose-50 hover:bg-rose-100 border border-rose-200/60 px-3 py-1.5 rounded-lg transition-all duration-200 shrink-0">
                    View All
                </a>
            </div>

            <div class="divide-y divide-slate-50">
                <?php
                $latestRequests = $data['latestRequests'] ?? [];
                if (!empty($latestRequests)):
                    foreach ($latestRequests as $req):
                        $bgGroup = $req['blood_group_needed'] ?? '';
                        $bgColors = match(strtoupper($bgGroup)) {
                            'O-',  'O+'  => ['dot' => 'bg-rose-500',    'bg' => 'bg-rose-50',  'text' => 'text-rose-700',  'border' => 'border-rose-200'],
                            'A-',  'A+'  => ['dot' => 'bg-amber-500',   'bg' => 'bg-amber-50', 'text' => 'text-amber-700', 'border' => 'border-amber-200'],
                            'B-',  'B+'  => ['dot' => 'bg-emerald-500', 'bg' => 'bg-emerald-50','text' => 'text-emerald-700','border' => 'border-emerald-200'],
                            'AB-', 'AB+' => ['dot' => 'bg-indigo-500',  'bg' => 'bg-indigo-50','text' => 'text-indigo-700','border' => 'border-indigo-200'],
                            default => ['dot' => 'bg-slate-500', 'bg' => 'bg-slate-50', 'text' => 'text-slate-700', 'border' => 'border-slate-200'],
                        };
                ?>
                <div class="px-5 sm:px-6 py-3.5 flex items-center gap-3.5 hover:bg-slate-50/60 transition-colors">
                    <div class="w-10 h-10 rounded-xl <?= $bgColors['bg'] ?> border <?= $bgColors['border'] ?> flex items-center justify-center shrink-0">
                        <span class="text-xs font-extrabold <?= $bgColors['text'] ?> leading-none tracking-tight"><?= htmlspecialchars(strtoupper($bgGroup)) ?></span>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-bold text-slate-800"><?= htmlspecialchars($bgGroup) ?></p>
                        <p class="text-xs text-slate-500 font-medium truncate mt-0.5"><?= htmlspecialchars($req['hospital_name'] ?? '') ?></p>
                    </div>
                    <span class="text-[11px] font-semibold text-slate-400 whitespace-nowrap shrink-0 bg-slate-50 border border-transparent hover:border-slate-100 px-2 py-1 rounded-md">
                        <?= date('d M Y', strtotime($req['created_at'])) ?>
                    </span>
                </div>
                <?php endforeach; ?>
                <?php else: ?>
                <div class="px-6 py-16 text-center flex flex-col items-center justify-center">
                    <div class="w-16 h-16 rounded-2xl bg-slate-50 border border-slate-100 flex items-center justify-center mb-4">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1" stroke="currentColor" class="w-8 h-8 text-slate-300">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <p class="text-sm font-semibold text-slate-500">No recent requests</p>
                    <p class="text-xs text-slate-400 mt-1">Blood requests will appear here as they arrive.</p>
                </div>
                <?php endif; ?>
            </div>

        </div>

        <!-- Recent System Activity -->
        <div class="animate-fade-in-up opacity-0 animation-delay-300 bg-white/80 backdrop-blur-md rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
            <div class="px-5 sm:px-6 py-4 sm:py-5 border-b border-slate-100 flex items-center justify-between gap-3">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-xl bg-indigo-50 border border-indigo-100 flex items-center justify-center text-indigo-600 shrink-0">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4.5 h-4.5" style="width:18px;height:18px;">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 4.5h14.25M3 9h9.75M3 13.5h5.25m5.25-.75H17.25m-5.25 0h5.25m-5.25 0h5.25M3 18h3.75m3 0h3.75M17.25 18h.75" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="font-bold text-slate-900 tracking-tight text-sm sm:text-base leading-tight">Recent System Activity</h3>
                        <p class="text-[11px] text-slate-400 font-medium leading-none mt-0.5">Live feed of platform events</p>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <span class="relative flex h-2 w-2">
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                        <span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-500"></span>
                    </span>
                    <span class="text-[11px] font-semibold text-emerald-700 bg-emerald-50 border border-emerald-200/60 px-2 py-0.5 rounded-md leading-none">LIVE</span>
                </div>
            </div>

            <div class="divide-y divide-slate-50 max-h-[420px] overflow-y-auto custom-scrollbar">
                <?php if (!empty($activities)): ?>
                    <?php foreach ($activities as $act): ?>
                        <?php
                        $type = strtolower($act['type'] ?? 'info');
                        $style = $typeStyles[$type] ?? $typeStyles['info'];
                        $icon = $typeIcons[$type] ?? $typeIcons['info'];
                        $action = htmlspecialchars($act['action'] ?? '');
                        $userName = htmlspecialchars($act['user_name'] ?? '');
                        ?>

                        <div class="relative px-5 sm:px-6 py-3.5 flex items-start gap-3.5 hover:bg-slate-50/60 transition-colors group border-l-2 border-transparent hover:<?= $style['border'] ?>">

                            <div class="w-8 h-8 rounded-lg <?= $style['bg'] ?> flex items-center justify-center <?= $style['icon'] ?> shrink-0 mt-0.5 ring-1 <?= $style['ring'] ?> group-hover:scale-105 transition-transform duration-200">
                                <?= $icon ?>
                            </div>

                            <div class="flex-1 min-w-0 pt-0.5">
                                <p class="text-sm text-slate-700 leading-snug group-hover:text-slate-900 transition-colors">
                                    <?php if ($userName): ?>
                                        <span class="font-semibold text-slate-800"><?= $userName ?></span>
                                    <?php endif; ?>
                                    <?= htmlspecialchars($act['message']) ?>
                                </p>
                                <?php if ($action): ?>
                                    <span class="inline-block mt-1 text-[11px] font-medium text-slate-400 bg-slate-100/80 px-2 py-0.5 rounded leading-none">
                                        <?= $action ?>
                                    </span>
                                <?php endif; ?>
                            </div>

                            <span class="text-[11px] font-semibold text-slate-400 whitespace-nowrap shrink-0 mt-1 bg-slate-50 group-hover:bg-white group-hover:text-slate-600 border border-transparent group-hover:border-slate-100 px-2 py-1 rounded-md transition-all duration-200">
                                <?= timeAgo($act['created_at']) ?>
                            </span>

                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="px-5 sm:px-6 py-16 text-center flex flex-col items-center justify-center">
                        <div class="w-16 h-16 rounded-2xl bg-slate-50 border border-slate-100 flex items-center justify-center mb-4">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1" stroke="currentColor" class="w-8 h-8 text-slate-300">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 4.5h14.25M3 9h9.75M3 13.5h5.25m5.25-.75H17.25m-5.25 0h5.25m-5.25 0h5.25M3 18h3.75m3 0h3.75M17.25 18h.75" />
                            </svg>
                        </div>
                        <p class="text-sm font-semibold text-slate-500">No recent activity</p>
                        <p class="text-xs text-slate-400 mt-1">System events will appear here in real time.</p>
                    </div>
                <?php endif; ?>
            </div>

        </div>

    </section>

</div>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const counters = document.querySelectorAll('.stat-counter');

        counters.forEach(counter => {
            const target = parseInt(counter.getAttribute('data-target'), 10) || 0;
            if (target === 0) {
                counter.innerText = '0';
                return;
            }

            const duration = 1200; // Total millisecond runtime
            const startTime = performance.now();

            const updateCounter = (currentTime) => {
                const elapsedTime = currentTime - startTime;
                const progress = Math.min(elapsedTime / duration, 1);

                // Out-Quart easing formulation for organic deceleration momentum
                const easeProgress = 1 - Math.pow(1 - progress, 4);
                const currentValue = Math.floor(easeProgress * target);

                counter.innerText = currentValue.toLocaleString();

                if (progress < 1) {
                    requestAnimationFrame(updateCounter);
                } else {
                    counter.innerText = target.toLocaleString();
                }
            };

            requestAnimationFrame(updateCounter);
        });
    });
</script>

<script src="<?= $basePath ?>/js/chart.umd.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const ctx = document.getElementById('requestsOverviewChart');
        if (!ctx) return;

        const chartData = <?= json_encode($data['requestsChartData'] ?? [], JSON_HEX_TAG) ?>;

        const labels = chartData.map(r => {
            const d = new Date(r.date + 'T00:00:00');
            return d.toLocaleDateString('en', { month: 'short', day: 'numeric' });
        });
        const pendingData = chartData.map(r => r.pending);
        const acceptedData = chartData.map(r => r.accepted);
        const cancelledData = chartData.map(r => r.cancelled);

        const requestsChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [
                    {
                        label: 'Pending',
                        data: pendingData,
                        borderColor: '#f59e0b',
                        backgroundColor: 'rgba(245, 158, 11, 0.08)',
                        borderWidth: 2.5,
                        pointBackgroundColor: '#f59e0b',
                        pointBorderColor: '#fff',
                        pointBorderWidth: 2,
                        pointRadius: 4,
                        pointHoverRadius: 6,
                        tension: 0.4,
                        fill: true,
                    },
                    {
                        label: 'Accepted',
                        data: acceptedData,
                        borderColor: '#6366f1',
                        backgroundColor: 'rgba(99, 102, 241, 0.08)',
                        borderWidth: 2.5,
                        pointBackgroundColor: '#6366f1',
                        pointBorderColor: '#fff',
                        pointBorderWidth: 2,
                        pointRadius: 4,
                        pointHoverRadius: 6,
                        tension: 0.4,
                        fill: true,
                    },
                    {
                        label: 'Cancelled',
                        data: cancelledData,
                        borderColor: '#ef4444',
                        backgroundColor: 'rgba(239, 68, 68, 0.08)',
                        borderWidth: 2.5,
                        pointBackgroundColor: '#ef4444',
                        pointBorderColor: '#fff',
                        pointBorderWidth: 2,
                        pointRadius: 4,
                        pointHoverRadius: 6,
                        tension: 0.4,
                        fill: true,
                    },
                ],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: {
                    mode: 'index',
                    intersect: false,
                },
                plugins: {
                    legend: {
                        position: 'top',
                        align: 'end',
                        labels: {
                            usePointStyle: true,
                            pointStyle: 'circle',
                            boxWidth: 6,
                            boxHeight: 6,
                            padding: 16,
                            font: { size: 12, weight: '600', family: 'Inter' },
                            color: '#64748b',
                        },
                    },
                    tooltip: {
                        backgroundColor: '#1e293b',
                        titleFont: { size: 13, weight: '600', family: 'Inter' },
                        bodyFont: { size: 12, weight: '500', family: 'Inter' },
                        padding: { x: 12, y: 8 },
                        cornerRadius: 10,
                        displayColors: true,
                        boxWidth: 8,
                        boxHeight: 8,
                        boxPadding: 4,
                    },
                },
                scales: {
                    x: {
                        grid: { display: false },
                        ticks: {
                            font: { size: 11, weight: '500', family: 'Inter' },
                            color: '#94a3b8',
                        },
                        border: { display: false },
                    },
                    y: {
                        beginAtZero: true,
                        grid: { color: 'rgba(148, 163, 184, 0.08)' },
                        ticks: {
                            font: { size: 11, weight: '500', family: 'Inter' },
                            color: '#94a3b8',
                            stepSize: 5,
                        },
                        border: { display: false },
                    },
                },
            },
        });

        document.getElementById('requestsMonthFilter')?.addEventListener('change', function () {
            const days = parseInt(this.value, 10);
            fetch('<?= $basePath ?>/admin/dashboard/chart-data?days=' + days)
                .then(res => res.json())
                .then(data => {
                    requestsChart.data.labels = data.map(r => {
                        const d = new Date(r.date + 'T00:00:00');
                        return d.toLocaleDateString('en', { month: 'short', day: 'numeric' });
                    });
                    requestsChart.data.datasets[0].data = data.map(r => r.pending);
                    requestsChart.data.datasets[1].data = data.map(r => r.accepted);
                    requestsChart.data.datasets[2].data = data.map(r => r.cancelled);
                    requestsChart.update('active');
                });
        });
    });
</script>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const dCtx = document.getElementById('donorsBloodGroupChart');
        if (!dCtx) return;

        const dbRows = <?= json_encode($data['donorsByBloodGroup'] ?? [], JSON_HEX_TAG) ?>;

        const bloodGroups = dbRows.map(r => r.blood_group);
        const donorCounts = dbRows.map(r => parseInt(r.total, 10));
        const total = donorCounts.reduce((a, b) => a + b, 0);

        if (total === 0) {
            dCtx.parentElement.innerHTML = '<p class="text-sm font-medium text-slate-400">No donor data available</p>';
            return;
        }

        const colorMap = {
            'O+': '#ef4444', 'O-': '#f87171',
            'A+': '#f59e0b', 'A-': '#fbbf24',
            'B+': '#22c55e', 'B-': '#4ade80',
            'AB+': '#6366f1', 'AB-': '#818cf8',
        };
        const fallback = ['#ef4444','#f97316','#eab308','#22c55e','#06b6d4','#6366f1','#a855f7','#ec4899'];
        const colors = bloodGroups.map((g, i) => colorMap[g] || fallback[i % fallback.length]);

        new Chart(dCtx, {
            type: 'doughnut',
            data: {
                labels: bloodGroups,
                datasets: [{
                    data: donorCounts,
                    backgroundColor: colors,
                    borderColor: '#fff',
                    borderWidth: 2.5,
                    hoverOffset: 6,
                }],
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                cutout: '65%',
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: '#1e293b',
                        titleFont: { size: 13, weight: '600', family: 'Inter' },
                        bodyFont: { size: 12, weight: '500', family: 'Inter' },
                        padding: { x: 12, y: 8 },
                        cornerRadius: 10,
                        callbacks: {
                            label: (ctx) => {
                                const pct = ((ctx.parsed / total) * 100).toFixed(1);
                                return ` ${ctx.label}: ${ctx.parsed} donors (${pct}%)`;
                            },
                        },
                    },
                },
            },
        });

        const legendEl = document.getElementById('donorsLegend');
        if (!legendEl) return;

        legendEl.innerHTML = bloodGroups.map((group, i) => {
            const pct = ((donorCounts[i] / total) * 100).toFixed(1);
            return `
                <div class="flex items-center justify-between gap-3 group">
                    <div class="flex items-center gap-2.5 min-w-0">
                        <span class="w-2.5 h-2.5 rounded-full shrink-0 ring-2 ring-white shadow-sm" style="background-color:${colors[i]}"></span>
                        <span class="text-sm font-semibold text-slate-700 truncate">${group}</span>
                    </div>
                    <div class="flex items-center gap-3 shrink-0">
                        <span class="text-sm font-bold text-slate-900 tabular-nums">${donorCounts[i]}</span>
                        <span class="text-[11px] font-semibold text-slate-400 bg-slate-100 px-1.5 py-0.5 rounded tabular-nums w-[48px] text-right">${pct}%</span>
                    </div>
                </div>`;
        }).join('');
    });
</script>