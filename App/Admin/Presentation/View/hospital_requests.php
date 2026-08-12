<?php

use App\Shared\Helpers\AdminFilters;

$db = \App\Shared\Infrastructure\Database\Database::getConnection();

$q = AdminFilters::strParam('q');
$statusFilter = AdminFilters::strParam('status');
$bloodGroup = AdminFilters::strParam('blood_group');
$dateFrom = AdminFilters::strParam('from');
$dateTo = AdminFilters::strParam('to');

$conditions = [];
$params = [];

if ($statusFilter !== '') {
    $conditions[] = 'r.status = ?';
    $params[] = $statusFilter;
}
if ($bloodGroup !== '') {
    $conditions[] = 'r.blood_group = ?';
    $params[] = $bloodGroup;
}
if ($q !== '') {
    $conditions[] = '(r.hospital_name LIKE ? OR r.request_code LIKE ? OR h.phone LIKE ? OR h.address LIKE ?)';
    $like = '%' . $q . '%';
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
}
if ($dateFrom !== '') {
    $conditions[] = 'DATE(r.created_at) >= ?';
    $params[] = $dateFrom;
}
if ($dateTo !== '') {
    $conditions[] = 'DATE(r.created_at) <= ?';
    $params[] = $dateTo;
}

$where = !empty($conditions) ? 'WHERE ' . implode(' AND ', $conditions) : '';

$stmt = $db->prepare("
    SELECT COUNT(*), COALESCE(SUM(r.quantity_requested), 0), COALESCE(SUM(r.quantity_allocated), 0), COALESCE(SUM(r.quantity_pending), 0)
    FROM hospital_blood_requests r
    LEFT JOIN hospitals h ON h.user_id = r.hospital_user_id
    {$where}
");
$stmt->execute($params);
[$totalRequests, $sumRequested, $sumAllocated, $sumPending] = $stmt->fetch(\PDO::FETCH_NUM);
$totalRequests = (int)$totalRequests;
$sumRequested = (int)$sumRequested;
$sumAllocated = (int)$sumAllocated;
$sumPending = (int)$sumPending;

// Open shortfall counters (independent of filters)
$openStmt = $db->query("
    SELECT COUNT(*) FROM hospital_blood_requests
    WHERE status IN ('PENDING', 'PARTIAL') AND quantity_pending > 0
");
$openCount = (int)$openStmt->fetchColumn();

$adminPagination = AdminFilters::paginate($totalRequests, 20);

$stmt = $db->prepare("
    SELECT
        r.request_id,
        r.request_code,
        r.hospital_user_id,
        r.hospital_name,
        r.blood_group,
        r.quantity_requested,
        r.quantity_allocated,
        r.quantity_pending,
        r.status,
        r.created_at,
        h.phone AS hospital_phone,
        h.state_region,
        h.township,
        h.address
    FROM hospital_blood_requests r
    LEFT JOIN hospitals h ON h.user_id = r.hospital_user_id
    {$where}
    ORDER BY r.created_at DESC, r.request_id DESC
    LIMIT {$adminPagination['perPage']} OFFSET {$adminPagination['offset']}
");
$stmt->execute($params);
$requests = $stmt->fetchAll();

function hospitalReqStatusStyle(string $status): string
{
    return match (strtoupper($status)) {
        'COMPLETED' => 'bg-emerald-50 text-emerald-600 border-emerald-200/50',
        'PARTIAL'   => 'bg-amber-50 text-amber-600 border-amber-200/50',
        'PENDING'   => 'bg-red-50 text-red-600 border-red-200/50',
        'DECLINED'  => 'bg-slate-100 text-slate-500 border-slate-200/50',
        default     => 'bg-slate-50 text-slate-600 border-slate-200/50',
    };
}

$bloodGroupOptions = [];
foreach (AdminFilters::bloodGroups($db) as $code => $label) {
    $bloodGroupOptions[$label] = $label;
}
?>

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">

<style>
    body {
        font-family: 'Plus Jakarta Sans', sans-serif;
        background-color: #faf8f8;
    }

    .mono {
        font-family: 'JetBrains Mono', monospace;
    }

    @keyframes springReveal {
        0% {
            opacity: 0;
            transform: translateY(20px) scale(0.99);
        }

        100% {
            opacity: 1;
            transform: translateY(0) scale(1);
        }
    }

    .animate-spring-in {
        opacity: 0;
        animation: springReveal 0.5s cubic-bezier(0.16, 1, 0.3, 1) forwards;
    }

    .glow-row:hover {
        box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.02), 0 8px 16px -6px rgba(0, 0, 0, 0.01);
        border-color: rgba(220, 38, 38, 0.12);
    }
</style>

<div class="min-h-screen transition-all opacity-0 translate-y-6 ease-out duration-700" id="hospitalRequestsContainer">

    <div class="relative bg-white border border-slate-200/60 rounded-2xl p-6 mb-6 shadow-xs flex flex-col md:flex-row md:items-center md:justify-between gap-6">
        <div class="flex items-center gap-4">
            <div class="w-12 h-12 bg-[#ce2424] text-white rounded-2xl flex items-center justify-center shadow-lg shadow-red-600/10 transform hover:scale-105 transition-transform duration-300">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6 text-white">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 6.375c0 2.278-3.694 4.125-8.25 4.125S3.75 8.653 3.75 6.375m16.5 0c0-2.278-3.694-4.125-8.25-4.125S3.75 4.097 3.75 6.375m16.5 0v11.25c0 2.278-3.694 4.125-8.25 4.125s-8.25-1.847-8.25-4.125V6.375m16.5 0v1.5m0-1.5v6.75c0 2.278-3.694 4.125-8.25 4.125s-8.25-1.847-8.25-4.125v-6.75" />
                </svg>
            </div>
            <div>
                <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight leading-none">Hospital Stock Requests</h1>
                <p class="text-xs sm:text-sm text-slate-400 font-medium mt-1.5">Every blood bag requested by a hospital and how the system settles it.</p>
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-4 bg-slate-900 text-white px-5 py-3 rounded-2xl shadow-lg border border-slate-800 self-start md:self-auto">
            <div class="flex flex-col">
                <span class="text-[9px] text-slate-400 font-bold uppercase tracking-widest">Bags Requested</span>
                <span class="text-lg font-extrabold tracking-tight mt-0.5 mono"><?= sprintf('%02d', $sumRequested) ?></span>
            </div>
            <div class="w-px h-10 bg-slate-700/60"></div>
            <div class="flex flex-col">
                <span class="text-[9px] text-slate-400 font-bold uppercase tracking-widest">Allocated</span>
                <span class="text-lg font-extrabold tracking-tight mt-0.5 mono text-emerald-400"><?= sprintf('%02d', $sumAllocated) ?></span>
            </div>
            <div class="w-px h-10 bg-slate-700/60"></div>
            <div class="flex flex-col">
                <span class="text-[9px] text-slate-400 font-bold uppercase tracking-widest">Pending</span>
                <span class="text-lg font-extrabold tracking-tight mt-0.5 mono text-amber-400"><?= sprintf('%02d', $sumPending) ?></span>
            </div>
            <div class="w-px h-10 bg-slate-700/60"></div>
            <div class="flex flex-col">
                <span class="text-[9px] text-slate-400 font-bold uppercase tracking-widest">Open Shortfalls</span>
                <span class="text-lg font-extrabold tracking-tight mt-0.5 mono text-red-400"><?= sprintf('%02d', $openCount) ?></span>
            </div>
        </div>
    </div>

    <?php if (!empty($flash_message)): ?>
        <div class="mb-6 rounded-2xl px-5 py-4 text-sm font-bold border animate-spring-in <?= ($flash_status ?? '') === 'error' ? 'bg-rose-50 text-rose-600 border-rose-200' : 'bg-emerald-50 text-emerald-600 border-emerald-200' ?>">
            <?= htmlspecialchars($flash_message) ?>
        </div>
    <?php endif; ?>

    <?php
    $statusOptions = [
        'PENDING' => 'Pending',
        'PARTIAL' => 'Partial',
        'COMPLETED' => 'Completed',
        'DECLINED' => 'Declined',
    ];
    $adminFilterConfig = [
        'action' => '/BloodConnect/public/admin/hospital-requests',
        'search_name' => 'q',
        'search_placeholder' => 'Search hospital, request code, phone or address…',
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
    require __DIR__ . '/../../../Shared/Presentation/View/partials/admin_filter_bar.php';
    ?>

    <div class="space-y-3">

        <?php if (empty($requests)): ?>
            <div class="bg-white border border-slate-200/70 rounded-2xl p-12 text-center text-slate-400 font-medium animate-spring-in">
                No hospital stock requests match the current view.
            </div>
        <?php else: ?>

            <div class="hidden lg:block max-h-[600px] overflow-y-auto pr-1 custom-scroll space-y-3">
                <?php foreach ($requests as $index => $r):
                    $requested = (int)($r['quantity_requested'] ?? 0);
                    $allocated = (int)($r['quantity_allocated'] ?? 0);
                    $pending = (int)($r['quantity_pending'] ?? 0);
                    $pct = $requested > 0 ? (int)round(($allocated / $requested) * 100) : 0;
                ?>
                    <div class="glow-row bg-white border border-slate-200/70 rounded-2xl p-4 grid grid-cols-12 items-center gap-4 transition-all duration-300 transform animate-spring-in shadow-2xs"
                        style="animation-delay: <?= $index * 0.03 ?>s;">

                        <div class="col-span-3 flex items-center gap-4 min-w-0">
                            <div class="relative shrink-0">
                                <div class="w-11 h-11 rounded-xl bg-red-50 border border-red-200/60 text-[#ce2424] flex items-center justify-center font-black text-xs uppercase tracking-wider">
                                    <?= htmlspecialchars(mb_substr($r['hospital_name'] ?? '?', 0, 2)) ?>
                                </div>
                            </div>
                            <div class="truncate min-w-0">
                                <span class="font-bold text-slate-800 text-sm tracking-tight truncate block"><?= htmlspecialchars($r['hospital_name'] ?? 'Unknown') ?></span>
                                <span class="text-xs text-slate-400 font-medium block mt-0.5 mono truncate"><?= htmlspecialchars($r['request_code'] ?? '') ?></span>
                            </div>
                        </div>

                        <div class="col-span-2 min-w-0">
                            <span class="text-slate-600 font-semibold tracking-tight text-sm block truncate"><?= htmlspecialchars($r['hospital_phone'] ?? '—') ?></span>
                            <span class="text-xs text-slate-400 font-medium block mt-0.5 truncate">
                                <?= htmlspecialchars(trim(($r['township'] ?? '') . ($r['state_region'] ? ', ' . $r['state_region'] : '')) ?: '—') ?>
                            </span>
                        </div>

                        <div class="col-span-1 flex justify-center">
                            <span class="inline-flex items-center justify-center px-2.5 py-1 rounded-lg bg-red-500/5 text-red-500 border border-red-500/10 font-black text-xs shadow-3xs font-mono">
                                <?= htmlspecialchars($r['blood_group'] ?? 'N/A') ?>
                            </span>
                        </div>

                        <div class="col-span-3 min-w-0">
                            <div class="flex items-center justify-between text-xs mb-1.5">
                                <span class="text-slate-500 font-semibold"><?= $allocated ?>/<?= $requested ?> bags</span>
                                <span class="text-slate-400 mono font-semibold"><?= $pct ?>%</span>
                            </div>
                            <div class="w-full h-2 rounded-full bg-slate-100 overflow-hidden">
                                <div class="h-full rounded-full transition-all duration-500 <?= $pending > 0 ? 'bg-amber-500' : 'bg-emerald-500' ?>" style="width: <?= $pct ?>%"></div>
                            </div>
                            <div class="flex items-center gap-3 mt-1.5 text-[10px] font-bold">
                                <span class="text-emerald-600"><?= $allocated ?> given</span>
                                <?php if ($pending > 0): ?>
                                    <span class="text-amber-600"><?= $pending ?> owed</span>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="col-span-1 flex justify-start pl-4">
                            <span class="inline-block px-3 py-1 text-[9px] font-extrabold rounded-lg uppercase tracking-widest border shadow-3xs <?= hospitalReqStatusStyle((string)($r['status'] ?? '')) ?>">
                                <?= htmlspecialchars((string)($r['status'] ?? '')) ?>
                            </span>
                        </div>

                        <?php if (($r['status'] ?? '') === 'PENDING'): ?>
                            <div class="col-span-1 flex flex-col gap-1.5">
                                <form method="POST" action="/BloodConnect/public/admin/hospital-requests/accept">
                                    <input type="hidden" name="request_id" value="<?= (int)$r['request_id'] ?>">
                                    <button type="submit" class="w-full inline-flex items-center justify-center gap-1 px-2 py-1 rounded-lg bg-emerald-500 text-white text-[10px] font-extrabold uppercase tracking-wider hover:bg-emerald-600 transition-all active:scale-95 shadow-sm">
                                        Accept
                                    </button>
                                </form>
                                <form method="POST" action="/BloodConnect/public/admin/hospital-requests/decline" onsubmit="return confirm('Decline this stock request?');">
                                    <input type="hidden" name="request_id" value="<?= (int)$r['request_id'] ?>">
                                    <button type="submit" class="w-full inline-flex items-center justify-center gap-1 px-2 py-1 rounded-lg bg-rose-50 text-rose-600 border border-rose-200 text-[10px] font-extrabold uppercase tracking-wider hover:bg-rose-100 transition-all active:scale-95">
                                        Decline
                                    </button>
                                </form>
                            </div>
                        <?php elseif (($r['status'] ?? '') === 'PARTIAL'): ?>
                            <div class="col-span-1 flex flex-col gap-1.5">
                                <form method="POST" action="/BloodConnect/public/admin/hospital-requests/provide-more">
                                    <input type="hidden" name="request_id" value="<?= (int)$r['request_id'] ?>">
                                    <button type="submit" class="w-full inline-flex items-center justify-center gap-1 px-2 py-1 rounded-lg bg-amber-500 text-white text-[10px] font-extrabold uppercase tracking-wider hover:bg-amber-600 transition-all active:scale-95 shadow-sm">
                                        Provide More
                                    </button>
                                </form>
                            </div>
                        <?php else: ?>
                            <div class="col-span-1"></div>
                        <?php endif; ?>

                        <div class="col-span-1 text-right text-slate-500 pr-2">
                            <span class="text-xs font-semibold block"><?= date('M d, Y', strtotime($r['created_at'])) ?></span>
                            <span class="text-[10px] text-slate-400 block mt-0.5 mono"><?= date('H:i', strtotime($r['created_at'])) ?></span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="block lg:hidden max-h-[600px] overflow-y-auto pr-1 custom-scroll space-y-3.5">
                <?php foreach ($requests as $index => $r):
                    $requested = (int)($r['quantity_requested'] ?? 0);
                    $allocated = (int)($r['quantity_allocated'] ?? 0);
                    $pending = (int)($r['quantity_pending'] ?? 0);
                ?>
                    <div class="bg-white border border-slate-200 rounded-2xl p-5 space-y-4 shadow-3xs transform transition-all duration-300 hover:shadow-2xs active:scale-[0.99] animate-spring-in"
                        style="animation-delay: <?= $index * 0.03 ?>s;">

                        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                            <div class="flex items-center gap-2.5">
                                <span class="inline-block px-2.5 py-1 text-[9px] font-extrabold rounded-md uppercase tracking-widest border <?= hospitalReqStatusStyle((string)($r['status'] ?? '')) ?>">
                                    <?= htmlspecialchars((string)($r['status'] ?? '')) ?>
                                </span>
                                <span class="text-[10px] text-slate-400 mono"><?= date('Y-m-d', strtotime($r['created_at'])) ?></span>
                            </div>
                            <span class="inline-flex items-center justify-center px-2.5 py-1 rounded-lg bg-red-500/5 text-red-500 border border-red-500/10 font-black text-xs font-mono">
                                <?= htmlspecialchars($r['blood_group'] ?? 'N/A') ?>
                            </span>
                        </div>

                        <div class="space-y-1 truncate min-w-0">
                            <h4 class="font-extrabold text-slate-900 text-base tracking-tight truncate leading-tight"><?= htmlspecialchars($r['hospital_name'] ?? 'Unknown') ?></h4>
                            <p class="text-xs text-slate-400 mono truncate"><?= htmlspecialchars($r['request_code'] ?? '') ?></p>
                            <p class="text-xs text-slate-400 truncate"><?= htmlspecialchars($r['hospital_phone'] ?? '—') ?></p>
                            <p class="text-xs text-slate-400 truncate"><?= htmlspecialchars(trim(($r['township'] ?? '') . ($r['state_region'] ? ', ' . $r['state_region'] : '')) ?: '—') ?></p>
                        </div>

                        <div class="flex items-center gap-2 text-xs font-bold">
                            <span class="px-2.5 py-1 rounded-lg bg-emerald-50 text-emerald-600 border border-emerald-200/60"><?= $allocated ?>/<?= $requested ?> bags given</span>
                            <?php if ($pending > 0): ?>
                                <span class="px-2.5 py-1 rounded-lg bg-amber-50 text-amber-600 border border-amber-200/60"><?= $pending ?> owed</span>
                            <?php endif; ?>
                        </div>

                        <?php if (($r['status'] ?? '') === 'PENDING'): ?>
                            <div class="flex items-center gap-2 pt-1">
                                <form method="POST" action="/BloodConnect/public/admin/hospital-requests/accept" class="flex-1">
                                    <input type="hidden" name="request_id" value="<?= (int)$r['request_id'] ?>">
                                    <button type="submit" class="w-full px-3 py-2 rounded-xl bg-emerald-500 text-white text-xs font-extrabold uppercase tracking-wider hover:bg-emerald-600 transition-all active:scale-95">Accept</button>
                                </form>
                                <form method="POST" action="/BloodConnect/public/admin/hospital-requests/decline" class="flex-1" onsubmit="return confirm('Decline this stock request?');">
                                    <input type="hidden" name="request_id" value="<?= (int)$r['request_id'] ?>">
                                    <button type="submit" class="w-full px-3 py-2 rounded-xl bg-rose-50 text-rose-600 border border-rose-200 text-xs font-extrabold uppercase tracking-wider hover:bg-rose-100 transition-all active:scale-95">Decline</button>
                                </form>
                            </div>
                        <?php elseif (($r['status'] ?? '') === 'PARTIAL'): ?>
                            <div class="pt-1">
                                <form method="POST" action="/BloodConnect/public/admin/hospital-requests/provide-more">
                                    <input type="hidden" name="request_id" value="<?= (int)$r['request_id'] ?>">
                                    <button type="submit" class="w-full px-3 py-2 rounded-xl bg-amber-500 text-white text-xs font-extrabold uppercase tracking-wider hover:bg-amber-600 transition-all active:scale-95">Provide More</button>
                                </form>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>

        <?php endif; ?>
    </div>

    <?php require __DIR__ . '/../../../Shared/Presentation/View/partials/admin_pagination.php'; ?>
</div>

<style>
    .custom-scroll::-webkit-scrollbar { width: 5px; }
    .custom-scroll::-webkit-scrollbar-track { background: transparent; }
    .custom-scroll::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 20px; }
    .custom-scroll::-webkit-scrollbar-thumb:hover { background: #94a3b8; }
</style>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const viewport = document.getElementById('hospitalRequestsContainer');
        if (viewport) {
            requestAnimationFrame(() => {
                viewport.classList.remove('opacity-0', 'translate-y-6');
            });
        }
    });
</script>
