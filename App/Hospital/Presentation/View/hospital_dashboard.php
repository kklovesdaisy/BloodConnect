<?php
$dashboard = $dashboard ?? [];
$profile = $dashboard['profile'] ?? [];
$recent = $dashboard['recent_requests'] ?? [];
$alerts = $dashboard['alerts'] ?? [];
$notifications = $dashboard['recent_notifications'] ?? [];
$hospitalName = $dashboard['hospital_name'] ?? 'Hospital';

function hospitalStatusBadge(string $status): string
{
    return match ($status) {
        'COMPLETED' => 'bg-emerald-50 text-emerald-600 border-emerald-200',
        'PARTIAL'   => 'bg-amber-50 text-amber-600 border-amber-200',
        'PENDING'   => 'bg-red-50 text-red-600 border-red-200',
        'DECLINED'  => 'bg-slate-100 text-slate-500 border-slate-200',
        default     => 'bg-slate-100 text-slate-600 border-slate-200',
    };
}

function hospitalStatusLabel(string $status): string
{
    return match ($status) {
        'COMPLETED' => 'Fulfilled',
        'PARTIAL'   => 'Partially Fulfilled',
        'PENDING'   => 'Pending',
        'DECLINED'  => 'Declined',
        default     => $status,
    };
}
?>
<style>
    @keyframes fadeInUp { from { opacity: 0; transform: translateY(12px); } to { opacity: 1; transform: translateY(0); } }
    .animate-fade-in-up { animation: fadeInUp .4s cubic-bezier(.16,1,.3,1) forwards; }
</style>

<div class="max-w-7xl mx-auto space-y-6">

    <!-- Header -->
    <div class="bg-white border border-slate-200/60 rounded-2xl p-6 flex flex-col lg:flex-row lg:items-center lg:justify-between gap-5 shadow-xs">
        <div class="flex items-center gap-4">
            <div class="w-12 h-12 bg-[#ce2424] text-white rounded-2xl flex items-center justify-center shadow-lg shadow-red-600/10 shrink-0">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-6 h-6"><path d="M11.47 3.84a.75.75 0 011.06 0l8.69 8.69a.75.75 0 101.06-1.06l-8.689-8.69a2.25 2.25 0 00-3.182 0l-8.69 8.69a.75.75 0 001.061 1.06l8.69-8.69zM12 5.432l8.159 8.159c.03.03.06.058.091.086v6.198c0 1.035-.84 1.875-1.875 1.875H15a.75.75 0 01-.75-.75v-4.5a.75.75 0 00-.75-.75h-3a.75.75 0 00-.75.75V21a.75.75 0 01-.75.75H5.625a1.875 1.875 0 01-1.875-1.875v-6.198c.03-.028.058-.056.086-.086L12 5.432z" /></svg>
            </div>
            <div>
                <div class="flex items-center gap-2">
                    <h1 class="text-xl sm:text-2xl font-extrabold text-slate-900 tracking-tight leading-none">Welcome, <?= htmlspecialchars($hospitalName) ?></h1>
                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md bg-emerald-50 border border-emerald-200 text-emerald-700 text-[10px] font-bold">
                        ✓ Verified
                    </span>
                </div>
                <div class="mt-2 flex flex-wrap items-center gap-x-4 gap-y-1 text-xs text-slate-500 font-medium">
                    <?php if (!empty($profile['state_region']) || !empty($profile['township'])): ?>
                        <span class="inline-flex items-center gap-1">
                            <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z" /></svg>
                            <?= htmlspecialchars(trim(($profile['state_region'] ?? '') . ' ' . ($profile['township'] ?? ''))) ?>
                        </span>
                    <?php endif; ?>
                    <?php if (!empty($profile['phone'])): ?>
                        <span class="inline-flex items-center gap-1">
                            <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 002.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 01-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 00-1.091-.852H4.5A2.25 2.25 0 002.25 4.5v2.25z" /></svg>
                            <?= htmlspecialchars((string)($profile['phone'] ?? '')) ?>
                        </span>
                    <?php endif; ?>
                    <span class="text-[10px] text-slate-400 font-bold uppercase tracking-wider">Request blood bags from central stock &amp; track fulfilment.</span>
                </div>
            </div>
        </div>

        <a href="/BloodConnect/public/hospital/requests/create"
            class="inline-flex items-center gap-2 px-5 py-2.5 bg-[#ce2424] text-white text-sm font-bold rounded-xl hover:bg-[#b81f1f] transition-all active:scale-95 shadow-md shadow-red-600/20 self-start lg:self-auto">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-4 h-4"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v6m3-3H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
            Request Stock
        </a>
    </div>

    <!-- Stat cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white border border-slate-200/60 rounded-2xl p-5 shadow-xs animate-fade-in-up">
            <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Total Requests</span>
            <div class="mt-2 text-3xl font-black text-slate-900"><?= (int)($dashboard['total_requests'] ?? 0) ?></div>
            <span class="text-xs text-slate-400 font-medium">all stock requests</span>
        </div>
        <div class="bg-white border border-slate-200/60 rounded-2xl p-5 shadow-xs animate-fade-in-up" style="animation-delay:.05s">
            <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Open (Pending)</span>
            <div class="mt-2 text-3xl font-black text-amber-500"><?= (int)($dashboard['open_requests'] ?? 0) ?></div>
            <span class="text-xs text-slate-400 font-medium">awaiting more bags</span>
        </div>
        <div class="bg-white border border-slate-200/60 rounded-2xl p-5 shadow-xs animate-fade-in-up" style="animation-delay:.1s">
            <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Allocated</span>
            <div class="mt-2 text-3xl font-black text-emerald-600"><?= (int)($dashboard['total_allocated'] ?? 0) ?></div>
            <span class="text-xs text-slate-400 font-medium">bag(s) received</span>
        </div>
        <div class="bg-white border border-slate-200/60 rounded-2xl p-5 shadow-xs animate-fade-in-up" style="animation-delay:.15s">
            <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Still Owed</span>
            <div class="mt-2 text-3xl font-black text-[#ce2424]"><?= (int)($dashboard['total_pending'] ?? 0) ?></div>
            <span class="text-xs text-slate-400 font-medium">bag(s) to be provided</span>
        </div>
    </div>

    <!-- Stock Alerts -->
    <?php if (!empty($alerts)): ?>
        <div class="space-y-3">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-lg font-extrabold text-slate-900 tracking-tight">Stock Alerts</h2>
                    <p class="text-xs text-slate-400 font-medium mt-0.5">Things that need your attention</p>
                </div>
                <span class="px-3 py-1 rounded-lg bg-rose-50 text-rose-600 text-[10px] font-bold uppercase tracking-wider border border-rose-100"><?= count($alerts) ?> active</span>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <?php foreach ($alerts as $alert):
                    $severity = $alert['severity'] ?? 'warning';
                    $kind = $alert['kind'] ?? 'stock';
                    $group = $alert['blood_group'] ?? '';
                    $bags = (int)($alert['bags'] ?? 0);
                    $code = $alert['code'] ?? '';

                    $headline = match (true) {
                        $severity === 'critical' && $kind === 'stock' => 'Out of stock',
                        $severity === 'warning' && $kind === 'stock' => 'Running low',
                        $severity === 'warning' && $kind === 'request' => 'Waiting for blood',
                        $severity === 'success'                      => 'Ready to send',
                        default => $alert['title'] ?? 'Alert',
                    };

                    $sentence = match (true) {
                        $severity === 'critical' && $kind === 'stock' => "You have no {$group} blood left. Please order more.",
                        $severity === 'warning' && $kind === 'stock'  => "You have only {$bags} bag" . ($bags > 1 ? 's' : '') . " of {$group} blood left. Order more soon.",
                        $severity === 'warning' && $kind === 'request' => "You still need {$bags} bag" . ($bags > 1 ? 's' : '') . " of {$group} blood for request {$code}.",
                        $severity === 'success'                      => "You have enough {$group} blood. Request {$code} can be fulfilled now.",
                        default => $alert['message'] ?? '',
                    };

                    $cardStyles = match ($severity) {
                        'critical' => 'border-rose-200 bg-rose-50',
                        'success'  => 'border-emerald-200 bg-emerald-50',
                        default    => 'border-amber-200 bg-amber-50',
                    };
                    $tileStyles = match ($severity) {
                        'critical' => 'bg-rose-100 text-rose-700 border-rose-200',
                        'success'  => 'bg-emerald-100 text-emerald-700 border-emerald-200',
                        default    => 'bg-amber-100 text-amber-700 border-amber-200',
                    };
                    $dotColor = match ($severity) {
                        'critical' => 'bg-rose-500',
                        'success'  => 'bg-emerald-500',
                        default    => 'bg-amber-500',
                    };
                ?>
                    <div class="rounded-2xl border p-4 flex items-center gap-4 <?= $cardStyles ?>">
                        <div class="w-14 h-14 rounded-xl border-2 flex items-center justify-center shrink-0 <?= $tileStyles ?>">
                            <span class="font-black text-lg leading-none"><?= htmlspecialchars($group) ?></span>
                        </div>
                        <div class="min-w-0 flex-1">
                            <div class="flex items-center gap-1.5">
                                <span class="w-1.5 h-1.5 rounded-full <?= $dotColor ?>"></span>
                                <p class="text-sm font-extrabold text-slate-900 leading-tight"><?= htmlspecialchars($headline) ?></p>
                            </div>
                            <p class="text-xs font-medium text-slate-600 mt-1 leading-snug"><?= htmlspecialchars($sentence) ?></p>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- Recent Requests -->
    <div class="bg-white border border-slate-200/60 rounded-2xl p-6 shadow-xs">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h2 class="text-lg font-extrabold text-slate-900 tracking-tight">Recent Requests</h2>
                    <p class="text-xs text-slate-400 font-medium mt-0.5">Fulfilment progress for your latest stock requests</p>
                </div>
                <a href="/BloodConnect/public/hospital/requests" class="text-xs font-bold text-[#ce2424] hover:text-[#b81f1f]">View all →</a>
            </div>

            <?php if (empty($recent)): ?>
                <div class="text-center text-slate-400 font-medium py-10">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-8 h-8 text-slate-300 mx-auto mb-2"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 13.5h3.86a2.25 2.25 0 012.008 1.24l.885 1.77a2.25 2.25 0 002.007 1.24h1.98a2.25 2.25 0 002.007-1.24l.885-1.77a2.25 2.25 0 012.007-1.24h3.86m-18 0h18" /></svg>
                    No stock requests yet. Request your first bags now.
                </div>
            <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="text-left text-[10px] uppercase tracking-wider text-slate-400 font-bold border-b border-slate-100">
                                <th class="py-2.5 pr-4">Code</th>
                                <th class="py-2.5 pr-4">Blood</th>
                                <th class="py-2.5 pr-4">Progress</th>
                                <th class="py-2.5 pr-4">Status</th>
                                <th class="py-2.5">Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recent as $request):
                                $status = (string)($request['status'] ?? '');
                                $requested = (int)($request['quantity_requested'] ?? 0);
                                $allocated = (int)($request['quantity_allocated'] ?? 0);
                                $pct = $requested > 0 ? (int)round(($allocated / $requested) * 100) : 0;
                                $barColor = $pct >= 100 ? 'bg-emerald-500' : ($pct > 0 ? 'bg-amber-500' : 'bg-slate-200');
                            ?>
                                <tr class="border-b border-slate-50 hover:bg-slate-50/50">
                                    <td class="py-3 pr-4 font-mono text-xs font-bold text-slate-700"><?= htmlspecialchars($request['request_code'] ?? '-') ?></td>
                                    <td class="py-3 pr-4 font-mono font-black text-slate-800"><?= htmlspecialchars((string)($request['blood_group'] ?? '-')) ?></td>
                                    <td class="py-3 pr-4 min-w-[150px]">
                                        <div class="flex items-center gap-2">
                                            <div class="flex-1 h-2 rounded-full bg-slate-100 overflow-hidden">
                                                <div class="h-full rounded-full <?= $barColor ?>" style="width: <?= $pct ?>%"></div>
                                            </div>
                                            <span class="text-[11px] font-bold text-slate-600 whitespace-nowrap"><?= $allocated ?>/<?= $requested ?></span>
                                        </div>
                                        <?php if ($pct > 0 && $pct < 100): ?>
                                            <span class="text-[10px] text-amber-600 font-semibold"><?= (int)($request['quantity_pending'] ?? 0) ?> bag(s) still owed</span>
                                        <?php elseif ($pct === 0): ?>
                                            <span class="text-[10px] text-red-500 font-semibold">Awaiting allocation</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="py-3 pr-4">
                                        <span class="px-2.5 py-1 rounded-lg border text-[10px] font-black uppercase tracking-wider whitespace-nowrap <?= hospitalStatusBadge($status) ?>">
                                            <?= htmlspecialchars(hospitalStatusLabel($status)) ?>
                                        </span>
                                    </td>
                                    <td class="py-3 text-xs text-slate-400 font-medium whitespace-nowrap"><?= date('d M Y', strtotime($request['created_at'] ?? 'now')) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
    </div>

    <!-- Recent Notifications -->
    <div class="bg-white border border-slate-200/60 rounded-2xl p-6 shadow-xs">
        <div class="flex items-center justify-between mb-4">
            <div>
                <h2 class="text-lg font-extrabold text-slate-900 tracking-tight">Recent Notifications</h2>
                <p class="text-xs text-slate-400 font-medium mt-0.5">Stock &amp; request updates for your hospital</p>
            </div>
            <a href="/BloodConnect/public/hospital/notifications" class="text-xs font-bold text-[#ce2424] hover:text-[#b81f1f]">View all →</a>
        </div>

        <?php if (empty($notifications)): ?>
            <div class="text-center py-10">
                <div class="w-14 h-14 mx-auto rounded-full bg-emerald-50 border border-emerald-100 flex items-center justify-center text-2xl">✓</div>
                <p class="mt-3 text-sm font-extrabold text-slate-800">You're all caught up</p>
                <p class="mt-1 text-xs text-slate-500">You don't have any new notifications.</p>
            </div>
        <?php else: ?>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                <?php foreach ($notifications as $notification):
                    $nt = strtolower((string)($notification['type'] ?? 'info'));
                    if (str_contains($nt, 'stock') || str_contains($nt, 'request')) {
                        $iconBg = 'bg-rose-50 text-rose-600 border-rose-100'; $icon = '🔴';
                    } elseif (str_contains($nt, 'approv') || str_contains($nt, 'confirm')) {
                        $iconBg = 'bg-emerald-50 text-emerald-600 border-emerald-100'; $icon = '✓';
                    } elseif (str_contains($nt, 'eligib') || str_contains($nt, 'reminder')) {
                        $iconBg = 'bg-amber-50 text-amber-600 border-amber-100'; $icon = 'ℹ';
                    } else {
                        $iconBg = 'bg-blue-50 text-blue-600 border-blue-100'; $icon = '🔔';
                    }
                ?>
                    <div class="flex items-start gap-3 p-4 rounded-xl border border-slate-100 bg-slate-50/40">
                        <span class="w-9 h-9 rounded-xl border flex items-center justify-center text-sm shrink-0 <?= $iconBg ?>"><?= $icon ?></span>
                        <div class="min-w-0">
                            <p class="text-xs font-bold text-slate-800 leading-snug"><?= htmlspecialchars($notification['title'] ?? 'Update') ?></p>
                            <p class="text-[11px] text-slate-500 mt-0.5 leading-snug"><?= htmlspecialchars($notification['message'] ?? '') ?></p>
                            <p class="text-[10px] text-slate-300 font-semibold mt-1.5"><?= !empty($notification['created_at']) ? date('d M Y', strtotime($notification['created_at'])) : '' ?></p>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>
