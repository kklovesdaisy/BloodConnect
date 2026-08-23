<?php

use App\Shared\Helpers\AdminFilters;

$db = \App\Shared\Infrastructure\Database\Database::getConnection();

$q = AdminFilters::strParam('q');
$statusFilter = AdminFilters::strParam('status');
$bloodGroup = AdminFilters::strParam('blood_group');
$tab = AdminFilters::strParam('tab');
$isHospitalTab = ($tab === 'hospital');

$conditions = [];
$params = [];

if ($isHospitalTab) {
    if ($statusFilter !== '') {
        $conditions[] = 'r.status = ?';
        $params[] = $statusFilter;
    }
    if ($bloodGroup !== '') {
        $conditions[] = 'r.blood_group = ?';
        $params[] = $bloodGroup;
    }
    if ($q !== '') {
        $conditions[] = '(r.hospital_name LIKE ? OR r.request_code LIKE ? OR h.phone LIKE ? OR u.email LIKE ? OR h.address LIKE ?)';
        $like = '%' . $q . '%';
        $params[] = $like;
        $params[] = $like;
        $params[] = $like;
        $params[] = $like;
        $params[] = $like;
    }

    $where = !empty($conditions) ? 'WHERE ' . implode(' AND ', $conditions) : '';

    $stmt = $db->prepare("
        SELECT COUNT(*), COALESCE(SUM(r.quantity_allocated), 0)
        FROM hospital_blood_requests r
        LEFT JOIN hospitals h ON h.user_id = r.hospital_user_id
        LEFT JOIN users u ON u.user_id = r.hospital_user_id
        {$where}
    ");
    $stmt->execute($params);
    [$totalAllocations, $totalAllocatedBags] = $stmt->fetch(\PDO::FETCH_NUM);
    $totalAllocations = (int)$totalAllocations;
    $totalAllocatedBags = (int)$totalAllocatedBags;

    $adminPagination = AdminFilters::paginate($totalAllocations, 20);

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
            h.address,
            u.email AS hospital_email
        FROM hospital_blood_requests r
        LEFT JOIN hospitals h ON h.user_id = r.hospital_user_id
        LEFT JOIN users u ON u.user_id = r.hospital_user_id
        {$where}
        ORDER BY r.created_at DESC, r.request_id DESC
        LIMIT {$adminPagination['perPage']} OFFSET {$adminPagination['offset']}
    ");
    $stmt->execute($params);
    $allocations = $stmt->fetchAll();

    $totalDonations = 0;
    $totalBags = 0;
    $donations = [];
} else {
    if ($statusFilter !== '') {
        $conditions[] = 'dh.status = ?';
        $params[] = (int)$statusFilter;
    }
    if ($bloodGroup !== '') {
        $conditions[] = 'COALESCE(br.blood_group_needed, d.blood_group) = ?';
        $params[] = $bloodGroup;
    }
    if ($q !== '') {
        $conditions[] = '(d.username LIKE ? OR d.email LIKE ? OR d.phone LIKE ? OR br.request_code LIKE ? OR br.hospital_name LIKE ?)';
        $like = '%' . $q . '%';
        $params[] = $like;
        $params[] = $like;
        $params[] = $like;
        $params[] = $like;
        $params[] = $like;
    }

    $where = !empty($conditions) ? 'WHERE ' . implode(' AND ', $conditions) : '';

    $stmt = $db->prepare("
        SELECT COUNT(*), COALESCE(SUM(dh.bags), 0)
        FROM donation_history dh
        LEFT JOIN users d ON d.user_id = dh.donor_id
        LEFT JOIN blood_requests br ON br.request_id = dh.request_id
        {$where}
    ");
    $stmt->execute($params);
    [$totalDonations, $totalBags] = $stmt->fetch(\PDO::FETCH_NUM);
    $totalDonations = (int)$totalDonations;
    $totalBags = (int)$totalBags;

    $adminPagination = AdminFilters::paginate($totalDonations, 20);

    $stmt = $db->prepare("
        SELECT
            dh.donation_id,
            dh.request_id,
            dh.donor_id,
            dh.donation_date,
            dh.bags,
            dh.status AS status_id,
            COALESCE(md.label, '—') AS status_label,
            dh.created_at,
            br.request_code,
            br.hospital_name,
            d.username AS donor_name,
            d.email AS donor_email,
            d.phone AS donor_phone,
            COALESCE(br.blood_group_needed, d.blood_group) AS blood_group,
            dp.state_region,
            dp.township
        FROM donation_history dh
        LEFT JOIN users d ON d.user_id = dh.donor_id
        LEFT JOIN donors dp ON dp.user_id = dh.donor_id
        LEFT JOIN blood_requests br ON br.request_id = dh.request_id
        LEFT JOIN master_data md ON md.id = dh.status
        {$where}
        ORDER BY dh.donation_date DESC, dh.donation_id DESC
        LIMIT {$adminPagination['perPage']} OFFSET {$adminPagination['offset']}
    ");
    $stmt->execute($params);
    $donations = $stmt->fetchAll();
}

function donationStatusClass(string $status): string
{
    return match (strtolower($status)) {
        'accepted' => 'bg-emerald-50 text-emerald-600 border-emerald-200/50',
        'completed' => 'bg-blue-50 text-blue-600 border-blue-200/50',
        'cancelled', 'canceled' => 'bg-red-50 text-red-600 border-red-200/50',
        'declined' => 'bg-rose-50 text-rose-600 border-rose-200/50',
        'pending' => 'bg-amber-50 text-amber-600 border-amber-200/50',
        default => 'bg-slate-50 text-slate-600 border-slate-200/50',
    };
}

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

<div class="min-h-screen transition-all opacity-0 translate-y-6 ease-out duration-700" id="donationHistoryContainer">

    <div class="relative bg-white border border-slate-200/60 rounded-2xl p-6 mb-8 shadow-xs flex flex-col md:flex-row md:items-center md:justify-between gap-6">
        <div class="flex items-center gap-4">
            <div class="w-12 h-12 bg-[#ce2424] text-white rounded-2xl flex items-center justify-center shadow-lg shadow-red-600/10 transform hover:scale-105 transition-transform duration-300">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-6 h-6 text-white">
                    <path d="M12 2.69l5.66 5.66a8 8 0 1 1-11.31 0z" />
                </svg>
            </div>
            <div>
                <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight leading-none">Donation History</h1>
                <p class="text-xs sm:text-sm text-slate-400 font-medium mt-1.5"><?= $isHospitalTab ? 'Blood bags allocated from central stock to hospitals, with full bag and hospital details.' : 'Every blood bag donated by a donor, with full donor details.' ?></p>
            </div>
        </div>

        <div class="flex items-center gap-4 bg-slate-900 text-white px-5 py-3 rounded-2xl shadow-lg border border-slate-800 self-start md:self-auto">
            <div class="flex flex-col">
                <span class="text-[9px] text-slate-400 font-bold uppercase tracking-widest"><?= $isHospitalTab ? 'Bags Allocated' : 'Total Bags' ?></span>
                <span class="text-lg font-extrabold tracking-tight mt-0.5 mono text-red-400">
                    <?= sprintf('%02d', $isHospitalTab ? $totalAllocatedBags : $totalBags) ?> <span class="text-xs text-slate-500 font-normal">bags</span>
                </span>
            </div>
            <div class="w-2.5 h-2.5 rounded-full bg-red-500 shadow-[0_0_10px_rgba(239,68,68,0.6)] animate-pulse"></div>
        </div>
    </div>

    <div class="flex items-center gap-2 mb-6" role="tablist">
        <a href="<?= htmlspecialchars('/BloodConnect/public/admin/donation-history' . AdminFilters::preserve(['tab' => 'donor', 'page' => null])) ?>"
            class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl font-bold text-sm transition-all active:scale-95 <?= !$isHospitalTab ? 'bg-[#ce2424] text-white shadow-md shadow-red-600/20' : 'bg-white border border-slate-200 text-slate-500 hover:text-slate-800 hover:border-slate-300' ?>">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-4 h-4"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z" /></svg>
            Donor Donations
        </a>
        <a href="<?= htmlspecialchars('/BloodConnect/public/admin/donation-history' . AdminFilters::preserve(['tab' => 'hospital', 'page' => null])) ?>"
            class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl font-bold text-sm transition-all active:scale-95 <?= $isHospitalTab ? 'bg-[#ce2424] text-white shadow-md shadow-red-600/20' : 'bg-white border border-slate-200 text-slate-500 hover:text-slate-800 hover:border-slate-300' ?>">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-4 h-4"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 21h19.5m-18-18v18m10.5-18v18m6-13.5V21M6.75 6.75h.75m-.75 3h.75m-.75 3h.75m3-6h.75m-.75 3h.75m-.75 3h.75M6.75 21v-3.375c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21M3 3h12m-.75 4.5H21m-3.75 3.75h.008v.008h-.008v-.008zm0 3h.008v.008h-.008v-.008zm0 3h.008v.008h-.008v-.008z" /></svg>
            System to Hospital
        </a>
    </div>

    <?php
    if ($isHospitalTab) {
        $statusOptions = [
            'PENDING' => 'Pending',
            'PARTIAL' => 'Partial',
            'COMPLETED' => 'Completed',
            'DECLINED' => 'Declined',
        ];
    } else {
        $statusOptions = [
            '8' => 'Accepted',
            '9' => 'Completed',
        ];
    }
    $adminFilterConfig = [
        'action' => '/BloodConnect/public/admin/donation-history',
        'search_name' => 'q',
        'search_placeholder' => $isHospitalTab ? 'Search hospital name, request code, phone, email or address…' : 'Search donor name, email, phone, request code or hospital…',
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
        ],
    ];
    require __DIR__ . '/../../../Shared/Presentation/View/partials/admin_filter_bar.php';
    ?>

    <div class="space-y-3">

        <?php if ($isHospitalTab): ?>

            <?php if (empty($allocations)): ?>
                <div class="bg-white border border-slate-200/70 rounded-2xl p-12 text-center text-slate-400 font-medium animate-spring-in">
                    No blood bags have been allocated to hospitals yet.
                </div>
            <?php else: ?>

                <div class="hidden lg:block max-h-[600px] overflow-y-auto pr-1 custom-scroll space-y-3">
                    <?php foreach ($allocations as $index => $a):
                        $requested = (int)($a['quantity_requested'] ?? 0);
                        $allocated = (int)($a['quantity_allocated'] ?? 0);
                        $pending = (int)($a['quantity_pending'] ?? 0);
                        $pct = $requested > 0 ? (int)round(($allocated / $requested) * 100) : 0;
                    ?>
                        <div class="glow-row bg-white border border-slate-200/70 rounded-2xl p-4 grid grid-cols-12 items-center gap-4 transition-all duration-300 transform animate-spring-in shadow-2xs"
                            style="animation-delay: <?= $index * 0.03 ?>s;">

                            <div class="col-span-3 flex items-center gap-4 min-w-0">
                                <div class="relative shrink-0">
                                    <div class="w-11 h-11 rounded-xl bg-red-50 border border-red-200/60 text-[#ce2424] flex items-center justify-center font-black text-xs uppercase tracking-wider">
                                        <?= htmlspecialchars(mb_substr($a['hospital_name'] ?? '?', 0, 2)) ?>
                                    </div>
                                </div>
                                <div class="truncate min-w-0">
                                    <span class="font-bold text-slate-800 text-sm tracking-tight truncate block"><?= htmlspecialchars($a['hospital_name'] ?? 'Unknown') ?></span>
                                    <span class="text-xs text-slate-400 font-medium block mt-0.5 mono truncate"><?= htmlspecialchars($a['request_code'] ?? '') ?></span>
                                </div>
                            </div>

                            <div class="col-span-2 min-w-0">
                                <span class="text-slate-600 font-semibold tracking-tight text-sm block truncate"><?= htmlspecialchars($a['hospital_phone'] ?? '—') ?></span>
                                <span class="text-xs text-slate-400 font-medium block mt-0.5 truncate">
                                    <?= htmlspecialchars(trim(($a['township'] ?? '') . ($a['state_region'] ? ', ' . $a['state_region'] : '')) ?: '—') ?>
                                </span>
                            </div>

                            <div class="col-span-1 flex justify-center">
                                <span class="inline-flex items-center justify-center px-2.5 py-1 rounded-lg bg-red-500/5 text-red-500 border border-red-500/10 font-black text-xs shadow-3xs font-mono">
                                    <?= htmlspecialchars($a['blood_group'] ?? 'N/A') ?>
                                </span>
                            </div>

                            <div class="col-span-1 flex justify-center">
                                <span class="inline-flex items-center justify-center px-2.5 py-1 rounded-lg bg-rose-600 text-white font-black text-xs shadow-sm shadow-rose-600/20">
                                    <?= $allocated ?> bag<?= $allocated === 1 ? '' : 's' ?>
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
                                    <span class="text-emerald-600"><?= $allocated ?> allocated</span>
                                    <?php if ($pending > 0): ?>
                                        <span class="text-amber-600"><?= $pending ?> owed</span>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <div class="col-span-1 flex justify-start pl-4">
                                <span class="inline-block px-2.5 py-1 text-[9px] font-extrabold rounded-lg uppercase tracking-widest border shadow-3xs <?= hospitalReqStatusStyle((string)($a['status'] ?? '')) ?>">
                                    <?= htmlspecialchars((string)($a['status'] ?? '')) ?>
                                </span>
                            </div>

                            <div class="col-span-1 text-right text-slate-500 pr-2">
                                <span class="text-xs font-semibold block"><?= date('M d, Y', strtotime($a['created_at'])) ?></span>
                                <span class="text-[10px] text-slate-400 block mt-0.5 mono"><?= date('H:i', strtotime($a['created_at'])) ?></span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="block lg:hidden max-h-[600px] overflow-y-auto pr-1 custom-scroll space-y-3.5">
                    <?php foreach ($allocations as $index => $a):
                        $requested = (int)($a['quantity_requested'] ?? 0);
                        $allocated = (int)($a['quantity_allocated'] ?? 0);
                        $pending = (int)($a['quantity_pending'] ?? 0);
                    ?>
                        <div class="bg-white border border-slate-200 rounded-2xl p-5 space-y-4 shadow-3xs transform transition-all duration-300 hover:shadow-2xs active:scale-[0.99] animate-spring-in"
                            style="animation-delay: <?= $index * 0.03 ?>s;">

                            <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                                <div class="flex items-center gap-2.5">
                                    <span class="inline-block px-2.5 py-1 text-[9px] font-extrabold rounded-md uppercase tracking-widest border <?= hospitalReqStatusStyle((string)($a['status'] ?? '')) ?>">
                                        <?= htmlspecialchars((string)($a['status'] ?? '')) ?>
                                    </span>
                                    <span class="text-[10px] text-slate-400 mono"><?= date('Y-m-d', strtotime($a['created_at'])) ?></span>
                                </div>
                                <span class="inline-flex items-center justify-center px-2.5 py-1 rounded-lg bg-rose-600 text-white font-black text-xs shadow-sm">
                                    <?= $allocated ?> bag<?= $allocated === 1 ? '' : 's' ?>
                                </span>
                            </div>

                            <div class="flex items-start justify-between gap-4">
                                <div class="space-y-1 truncate min-w-0">
                                    <h4 class="font-extrabold text-slate-900 text-base tracking-tight truncate leading-tight"><?= htmlspecialchars($a['hospital_name'] ?? 'Unknown') ?></h4>
                                    <p class="text-xs text-slate-400 mono truncate"><?= htmlspecialchars($a['request_code'] ?? '') ?></p>
                                    <p class="text-xs text-slate-400 truncate"><?= htmlspecialchars($a['hospital_phone'] ?? '—') ?></p>
                                    <p class="text-xs text-slate-400 truncate"><?= htmlspecialchars(trim(($a['township'] ?? '') . ($a['state_region'] ? ', ' . $a['state_region'] : '')) ?: '—') ?></p>
                                </div>
                                <div class="w-11 h-11 rounded-xl bg-red-500/5 text-red-500 border border-red-500/10 font-black text-sm flex items-center justify-center font-mono shrink-0">
                                    <?= htmlspecialchars($a['blood_group'] ?? 'N/A') ?>
                                </div>
                            </div>

                            <div class="flex items-center gap-2 text-xs font-bold">
                                <span class="px-2.5 py-1 rounded-lg bg-emerald-50 text-emerald-600 border border-emerald-200/60"><?= $allocated ?>/<?= $requested ?> bags allocated</span>
                                <?php if ($pending > 0): ?>
                                    <span class="px-2.5 py-1 rounded-lg bg-amber-50 text-amber-600 border border-amber-200/60"><?= $pending ?> owed</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

            <?php endif; ?>

        <?php elseif (empty($donations)): ?>
            <div class="bg-white border border-slate-200/70 rounded-2xl p-12 text-center text-slate-400 font-medium animate-spring-in">
                No donated blood bags match the current view.
            </div>
        <?php else: ?>

            <div class="hidden lg:block max-h-[600px] overflow-y-auto pr-1 custom-scroll space-y-3">
                <?php foreach ($donations as $index => $d): ?>
                    <div class="glow-row bg-white border border-slate-200/70 rounded-2xl p-4 grid grid-cols-12 items-center gap-4 transition-all duration-300 transform animate-spring-in shadow-2xs"
                        style="animation-delay: <?= $index * 0.03 ?>s;">

                        <div class="col-span-3 flex items-center gap-4 min-w-0">
                            <div class="relative shrink-0">
                                <div class="w-11 h-11 rounded-xl bg-emerald-50 border border-emerald-200/60 text-emerald-600 flex items-center justify-center font-bold text-xs uppercase tracking-wider">
                                    <?= htmlspecialchars(mb_substr($d['donor_name'] ?? '?', 0, 2)) ?>
                                </div>
                            </div>
                            <div class="truncate min-w-0">
                                <span class="font-bold text-slate-800 text-sm tracking-tight truncate block"><?= htmlspecialchars($d['donor_name'] ?? 'Unknown') ?></span>
                                <span class="text-xs text-slate-400 font-medium block mt-0.5 truncate mono"><?= htmlspecialchars($d['donor_email'] ?? '—') ?></span>
                            </div>
                        </div>

                        <div class="col-span-2 min-w-0">
                            <span class="text-slate-600 font-semibold tracking-tight text-sm block truncate"><?= htmlspecialchars($d['donor_phone'] ?? '—') ?></span>
                            <span class="text-xs text-slate-400 font-medium block mt-0.5 truncate">
                                <?= htmlspecialchars(trim(($d['township'] ?? '') . ($d['state_region'] ? ', ' . $d['state_region'] : '')) ?: '—') ?>
                            </span>
                        </div>

                        <div class="col-span-1 flex justify-center">
                            <span class="inline-flex items-center justify-center px-2.5 py-1 rounded-lg bg-red-500/5 text-red-500 border border-red-500/10 font-black text-xs shadow-3xs font-mono">
                                <?= htmlspecialchars($d['blood_group'] ?? 'N/A') ?>
                            </span>
                        </div>

                        <div class="col-span-1 flex justify-center">
                            <span class="inline-flex items-center justify-center px-2.5 py-1 rounded-lg bg-rose-600 text-white font-black text-xs shadow-sm shadow-rose-600/20">
                                <?= (int)($d['bags'] ?? 1) ?> bag<?= (int)($d['bags'] ?? 1) === 1 ? '' : 's' ?>
                            </span>
                        </div>

                        <div class="col-span-2 flex justify-start pl-4">
                            <?php if (!empty($d['request_id'])): ?>
                                <span class="inline-block px-3 py-1 text-[9px] font-extrabold rounded-lg uppercase tracking-widest border bg-amber-50 text-amber-600 border-amber-200/50 shadow-3xs" title="Donated against a hospital request">Request</span>
                            <?php else: ?>
                                <span class="inline-block px-3 py-1 text-[9px] font-extrabold rounded-lg uppercase tracking-widest border bg-sky-50 text-sky-600 border-sky-200/50 shadow-3xs" title="Donated directly to central stock">Standalone</span>
                            <?php endif; ?>
                        </div>

                        <div class="col-span-2 text-right text-slate-500 pr-2">
                            <span class="text-xs font-semibold block"><?= date('M d, Y', strtotime($d['donation_date'])) ?></span>
                            <span class="text-[10px] text-slate-400 block mt-0.5 mono truncate">
                                <?= !empty($d['hospital_name']) ? htmlspecialchars($d['hospital_name']) : htmlspecialchars($d['request_code'] ?? '') ?>
                            </span>
                        </div>

                        <div class="col-span-1 flex items-center justify-end pr-2 gap-1">
                            <span class="inline-block px-2.5 py-1 text-[9px] font-extrabold rounded-lg uppercase tracking-widest border shadow-3xs <?= donationStatusClass($d['status_label']) ?>">
                                <?= htmlspecialchars($d['status_label']) ?>
                            </span>
                            <?php if (!empty($d['request_id'])): ?>
                                <a href="/BloodConnect/public/admin/blood-request/view?id=<?= (int)$d['request_id'] ?>"
                                    title="Inspect Request"
                                    class="p-2 text-slate-400 hover:text-slate-700 hover:bg-slate-50 border border-transparent hover:border-slate-200 rounded-xl transition-all active:scale-90">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.3" stroke="currentColor" class="w-4 h-4">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 003 8.25v10.5A2.25 2.25 0 005.25 21h10.5A2.25 2.25 0 0018 18.75V10.5M10.5 13.5L20.25 3.75M20.25 3.75H15.75M20.25 3.75v4.5" />
                                    </svg>
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="block lg:hidden max-h-[600px] overflow-y-auto pr-1 custom-scroll space-y-3.5">
                <?php foreach ($donations as $index => $d): ?>
                    <div class="bg-white border border-slate-200 rounded-2xl p-5 space-y-4 shadow-3xs transform transition-all duration-300 hover:shadow-2xs active:scale-[0.99] animate-spring-in"
                        style="animation-delay: <?= $index * 0.03 ?>s;">

                        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                            <div class="flex items-center gap-2.5">
                                <span class="inline-block px-2.5 py-1 text-[9px] font-extrabold rounded-md uppercase tracking-widest border <?= donationStatusClass($d['status_label']) ?>">
                                    <?= htmlspecialchars($d['status_label']) ?>
                                </span>
                                <span class="text-[10px] text-slate-400 mono"><?= date('Y-m-d', strtotime($d['donation_date'])) ?></span>
                            </div>
                            <span class="inline-flex items-center justify-center px-2.5 py-1 rounded-lg bg-rose-600 text-white font-black text-xs shadow-sm">
                                <?= (int)($d['bags'] ?? 1) ?> bag<?= (int)($d['bags'] ?? 1) === 1 ? '' : 's' ?>
                            </span>
                        </div>

                        <div class="flex items-start justify-between gap-4">
                            <div class="space-y-1 truncate min-w-0">
                                <h4 class="font-extrabold text-slate-900 text-base tracking-tight truncate leading-tight"><?= htmlspecialchars($d['donor_name'] ?? 'Unknown') ?></h4>
                                <p class="text-xs font-medium text-slate-600 truncate"><?= htmlspecialchars($d['donor_email'] ?? '—') ?></p>
                                <p class="text-xs text-slate-400 truncate"><?= htmlspecialchars($d['donor_phone'] ?? '—') ?></p>
                                <p class="text-xs text-slate-400 truncate"><?= htmlspecialchars(trim(($d['township'] ?? '') . ($d['state_region'] ? ', ' . $d['state_region'] : '')) ?: '—') ?></p>
                            </div>
                            <div class="w-11 h-11 rounded-xl bg-red-500/5 text-red-500 border border-red-500/10 font-black text-sm flex items-center justify-center font-mono shrink-0">
                                <?= htmlspecialchars($d['blood_group'] ?? 'N/A') ?>
                            </div>
                        </div>

                        <div class="flex items-center gap-2 pt-1">
                            <span class="inline-block px-2.5 py-1 text-[9px] font-extrabold rounded-lg uppercase tracking-widest border <?= !empty($d['request_id']) ? 'bg-amber-50 text-amber-600 border-amber-200/50' : 'bg-sky-50 text-sky-600 border-sky-200/50' ?>">
                                <?= !empty($d['request_id']) ? 'Request' : 'Standalone' ?>
                            </span>
                            <?php if (!empty($d['request_id'])): ?>
                                <a href="/BloodConnect/public/admin/blood-request/view?id=<?= (int)$d['request_id'] ?>"
                                    class="flex-1 flex items-center justify-center gap-2 py-2.5 px-4 bg-slate-50 border border-slate-200 text-slate-700 rounded-xl font-bold text-xs transition-all active:scale-95">
                                    Inspect Request
                                </a>
                            <?php endif; ?>
                        </div>
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
        const viewport = document.getElementById('donationHistoryContainer');
        if (viewport) {
            requestAnimationFrame(() => {
                viewport.classList.remove('opacity-0', 'translate-y-6');
            });
        }
    });
</script>
