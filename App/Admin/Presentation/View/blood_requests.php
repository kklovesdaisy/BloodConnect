<?php

use App\Shared\Infrastructure\Database\Database;

$db = Database::getConnection(); ?>
<style>
.custom-scroll::-webkit-scrollbar { width: 5px; }
.custom-scroll::-webkit-scrollbar-track { background: transparent; }
.custom-scroll::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 20px; }
.custom-scroll::-webkit-scrollbar-thumb:hover { background: #94a3b8; }
</style>
<?php

use App\Shared\Helpers\AdminFilters;

$filter = $_GET['filter'] ?? '';
$q = AdminFilters::strParam('q');
$reqStatus = AdminFilters::strParam('req_status');
$bloodGroup = AdminFilters::strParam('blood_group');
$urgency = AdminFilters::strParam('urgency');
$dateFrom = AdminFilters::strParam('from');
$dateTo = AdminFilters::strParam('to');

/* Fetch blood requests from the database */
$requests = [];

try {
    $conditions = [];
    $params = [];

    if ($reqStatus !== '') {
        $conditions[] = 'r.status = ?';
        $params[] = (int)$reqStatus;
    } elseif ($filter === 'pending') {
        $conditions[] = 'r.status = 7';
    } elseif ($filter === 'assigned') {
        $conditions[] = 'r.status = 42';
    } elseif ($filter === 'accepted') {
        $conditions[] = 'r.status IN (8, 9)';
    } elseif ($filter === 'completed') {
        $conditions[] = 'r.status = 9';
    } elseif ($filter === 'declined') {
        $conditions[] = 'r.status = 47';
    } elseif ($filter === 'expired') {
        $conditions[] = 'r.status = 48';
    }

    if ($bloodGroup !== '') {
        $conditions[] = 'r.blood_group_needed = ?';
        $params[] = $bloodGroup;
    }

    if ($urgency !== '') {
        $conditions[] = 'UPPER(r.urgency) = ?';
        $params[] = strtoupper($urgency);
    }

    if ($q !== '') {
        $conditions[] = '(r.hospital_name LIKE ? OR r.patient_name LIKE ? OR r.request_code LIKE ? OR r.contact_phone LIKE ? OR donor.username LIKE ?)';
        $like = '%' . $q . '%';
        $params[] = $like;
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
        SELECT COUNT(*)
        FROM blood_requests r
        LEFT JOIN users donor ON donor.user_id = r.donor_id
        {$where}
    ");
    $stmt->execute($params);
    $totalRequests = (int)$stmt->fetchColumn();

    $adminPagination = AdminFilters::paginate($totalRequests, 20);

    $stmt = $db->prepare("
        SELECT
            r.request_id,
            r.request_code,
            r.hospital_name,
            r.patient_name,
            r.blood_group_needed AS blood_type,
            r.unit AS units_requested,
            COALESCE(UPPER(r.urgency), 'ROUTINE') AS severity_level,
            COALESCE(md.label, 'Pending') AS fulfillment_status,
            r.status AS status_id,
            COALESCE(r.created_at, CURRENT_TIMESTAMP) AS required_date,
            r.expiry_date,
            r.donor_id,
            donor.username AS donor_name
        FROM blood_requests r
        LEFT JOIN master_data md ON md.id = r.status
        LEFT JOIN users donor ON donor.user_id = r.donor_id
        {$where}
        ORDER BY r.created_at DESC
        LIMIT {$adminPagination['perPage']} OFFSET {$adminPagination['offset']}
    ");
    $stmt->execute($params);
    $requests = $stmt->fetchAll();
} catch (Throwable $e) {
    $requests = [];
    $adminPagination = AdminFilters::paginate(0, 10);
}

$pageTitle = match ($filter) {
    'pending' => 'Pending Blood Requests',
    'assigned' => 'Assigned Blood Requests',
    'accepted' => 'Accepted Blood Requests',
    'completed' => 'Completed Blood Requests',
    'declined' => 'Declined Blood Requests',
    'expired' => 'Expired Blood Requests',
    default => 'Blood Requests',
};

function getSeverityClass($level)
{
    return match (strtoupper((string) $level)) {
        'CRITICAL' => 'bg-rose-50 text-rose-600 border-rose-200/50',
        'URGENT' => 'bg-orange-50 text-orange-600 border-orange-200/50',
        default => 'bg-slate-50 text-slate-500 border-slate-200/50',
    };
}

function getFulfillmentClass($status)
{
    return match (strtolower((string) $status)) {
        'pending' => 'bg-amber-50 text-amber-600 border-amber-200/50',
        'accepted' => 'bg-emerald-50 text-emerald-600 border-emerald-200/50',
        'completed' => 'bg-blue-50 text-blue-600 border-blue-200/50',
        'cancelled', 'canceled' => 'bg-red-50 text-red-600 border-red-200/50',
        'declined' => 'bg-rose-50 text-rose-600 border-rose-200/50',
        'expired' => 'bg-slate-100 text-slate-500 border-slate-200/50',
        default => 'bg-slate-50 text-slate-600 border-slate-200/50',
    };
}

function getBloodTypeClass($type)
{
    $type = strtolower((string) $type);

    if (strpos($type, 'negative') !== false || strpos($type, '-') !== false) {
        return 'text-rose-600 bg-rose-50 border-rose-200/40';
    }
    return 'text-emerald-600 bg-emerald-50 border-emerald-200/40';
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

    /* Premium Smooth Entrance Physics */
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

    /* Interactive Hover Glow Ring effect */
    .glow-row:hover {
        box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.02), 0 8px 16px -6px rgba(0, 0, 0, 0.01);
        border-color: rgba(220, 38, 38, 0.12);
    }
</style>

<div class="min-h-screen transition-all opacity-0 translate-y-6 ease-out duration-700" id="bloodRequestsContainer">

    <div class="relative bg-white border border-slate-200/60 rounded-2xl p-6 mb-8 shadow-xs flex flex-col md:flex-row md:items-center md:justify-between gap-6">
        <div class="flex items-center gap-4">
            <div class="w-12 h-12 bg-[#ce2424] text-white rounded-2xl flex items-center justify-center shadow-lg shadow-red-600/10 transform hover:scale-105 transition-transform duration-300">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-6 h-6 text-white">
                    <path d="M12 2.69l5.66 5.66a8 8 0 1 1-11.31 0z" />
                </svg>
            </div>
            <div>
                <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight leading-none"><?= htmlspecialchars($pageTitle) ?></h1>
                <p class="text-xs sm:text-sm text-slate-400 font-medium mt-1.5">Direct system registry controls for live incoming hospital requests.</p>
            </div>
        </div>

        <div class="flex items-center gap-4 bg-slate-900 text-white px-5 py-3 rounded-2xl shadow-lg border border-slate-800 self-start md:self-auto">
            <div class="flex flex-col">
                <span class="text-[9px] text-slate-400 font-bold uppercase tracking-widest">Active Tickets</span>
                <span class="text-lg font-extrabold tracking-tight mt-0.5 mono">
                    <?= sprintf('%02d', $adminPagination['total']) ?> <span class="text-xs text-slate-500 font-normal">nodes</span>
                </span>
            </div>
            <div class="w-2.5 h-2.5 rounded-full bg-red-500 shadow-[0_0_10px_rgba(239,68,68,0.6)] animate-pulse"></div>
        </div>
    </div>

    <?php
    $adminFilterConfig = [
        'action' => '/BloodConnect/public/admin/blood-requests',
        'search_name' => 'q',
        'search_placeholder' => 'Search hospital, patient, request code or donor…',
        'fields' => [
            [
                'type' => 'select',
                'name' => 'req_status',
                'label' => 'Request Status',
                'options' => AdminFilters::requestStatuses($db),
            ],
            [
                'type' => 'select',
                'name' => 'urgency',
                'label' => 'Urgency',
                'options' => [
                    'Standard' => 'Standard',
                    'Urgent' => 'Urgent',
                    'Critical' => 'Critical',
                ],
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
    require __DIR__ . '/../../../Shared/Presentation/View/partials/admin_filter_bar.php';
    ?>

    <div class="space-y-3">

        <?php if (empty($requests)): ?>
            <div class="bg-white border border-slate-200/70 rounded-2xl p-12 text-center text-slate-400 font-medium animate-spring-in">
                No active blood requests found in the live ledger pool.
            </div>
        <?php else: ?>

            <div class="hidden lg:block max-h-[500px] overflow-y-auto pr-1 custom-scroll space-y-3">
                <?php foreach ($requests as $index => $req): ?>
                    <div class="glow-row bg-white border border-slate-200/70 rounded-2xl p-4 grid grid-cols-12 items-center gap-4 transition-all duration-300 transform animate-spring-in shadow-2xs"
                        style="animation-delay: <?= $index * 0.03 ?>s;">

                        <div class="col-span-3 flex items-center gap-4 min-w-0">
                            <div class="relative shrink-0">
                                <div class="w-11 h-11 rounded-xl bg-red-50 border border-red-100 text-red-500 flex items-center justify-center">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-5 h-5">
                                        <path d="M12 2.69l5.66 5.66a8 8 0 1 1-11.31 0z" />
                                    </svg>
                                </div>
                            </div>
                            <div class="truncate">
                                <span class="text-[10px] font-bold uppercase text-slate-400 tracking-wider mono block">REQ___<?= str_pad($req['request_id'], 4, '0', STR_PAD_LEFT) ?></span>
                                <span class="font-bold text-slate-800 text-sm tracking-tight truncate block mt-0.5"><?= htmlspecialchars($req['hospital_name']) ?></span>
                                <span class="text-xs text-slate-400 font-medium block truncate">Pt: <?= htmlspecialchars($req['patient_name']) ?></span>
                            </div>
                        </div>

                        <div class="col-span-2 min-w-0 pl-2">
                            <span class="text-slate-800 font-extrabold tracking-tight text-sm block truncate"><?= htmlspecialchars($req['units_requested']) ?> Units</span>
                            <span class="text-[10px] text-slate-400 font-bold tracking-wider uppercase block mt-0.5">Volume Demand</span>
                        </div>

                        <div class="col-span-2 flex justify-start pl-2">
                            <span class="inline-block px-3 py-1 text-[9px] font-extrabold rounded-lg uppercase tracking-widest border shadow-3xs <?= getSeverityClass($req['severity_level']) ?>">
                                <?= htmlspecialchars($req['severity_level']) ?>
                            </span>
                        </div>

                        <div class="col-span-2 flex flex-col items-start gap-1 pl-2">
                            <span class="inline-block px-3 py-1 text-[9px] font-extrabold rounded-lg uppercase tracking-widest border shadow-3xs <?= getFulfillmentClass($req['fulfillment_status']) ?>">
                                <?= htmlspecialchars($req['fulfillment_status']) ?>
                            </span>
                            <?php if ((int)$req['status_id'] === 7 && !empty($req['expiry_date'])):
                                $admExpiryDiff = strtotime($req['expiry_date']) - time();
                                if ($admExpiryDiff > 0):
                                    $admDays = floor($admExpiryDiff / 86400);
                                    $admHours = floor(($admExpiryDiff % 86400) / 3600);
                            ?>
                                <span class="text-[10px] font-medium text-amber-500">Expires in <?= $admDays > 0 ? $admDays . 'd ' . $admHours . 'h' : $admHours . 'h' ?></span>
                            <?php endif; endif; ?>
                            <?php if (!empty($req['donor_name']) && (int)$req['status_id'] !== 7): ?>
                                <span class="text-[10px] font-medium text-slate-500 truncate max-w-full">
                                    <?php if ((int)$req['status_id'] === 42): ?>
                                        Assigned to: <span class="font-bold text-slate-700"><?= htmlspecialchars($req['donor_name']) ?></span>
                                    <?php elseif ((int)$req['status_id'] === 47): ?>
                                        Declined by: <span class="font-bold text-slate-700"><?= htmlspecialchars($req['donor_name']) ?></span>
                                    <?php else: ?>
                                        Accepted by: <span class="font-bold text-slate-700"><?= htmlspecialchars($req['donor_name']) ?></span>
                                    <?php endif; ?>
                                </span>
                            <?php endif; ?>
                        </div>

                        <div class="col-span-1 flex justify-center">
                            <span class="inline-flex items-center justify-center px-2.5 py-1 rounded-lg border font-black text-xs shadow-3xs font-mono <?= getBloodTypeClass($req['blood_type']) ?>">
                                <?= htmlspecialchars($req['blood_type']) ?>
                            </span>
                        </div>

                        <div class="col-span-1 text-right text-slate-500 pr-2">
                            <span class="text-xs font-semibold block truncate"><?= date('M d, Y', strtotime($req['required_date'])) ?></span>
                            <span class="text-[10px] text-slate-400 block mt-0.5 mono"><?= date('H:i', strtotime($req['required_date'])) ?></span>
                        </div>

                        <div class="col-span-1 flex items-center justify-end pr-2">
                            <a href="/BloodConnect/public/admin/blood-request/view?id=<?= (int)$req['request_id'] ?>"
                                title="Inspect Ticket Node"
                                class="p-2 text-slate-400 hover:text-slate-700 hover:bg-slate-50 border border-transparent hover:border-slate-200 rounded-xl transition-all active:scale-90">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.3" stroke="currentColor" class="w-4 h-4">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 003 8.25v10.5A2.25 2.25 0 005.25 21h10.5A2.25 2.25 0 0018 18.75V10.5M10.5 13.5L20.25 3.75M20.25 3.75H15.75M20.25 3.75v4.5" />
                                </svg>
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="block lg:hidden max-h-[500px] overflow-y-auto pr-1 custom-scroll space-y-3.5">
                <?php foreach ($requests as $index => $req): ?>
                    <div class="bg-white border border-slate-200 rounded-2xl p-5 space-y-4 shadow-3xs transform transition-all duration-300 hover:shadow-2xs active:scale-[0.99] animate-spring-in"
                        style="animation-delay: <?= $index * 0.03 ?>s;">

                        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                            <div class="flex items-center gap-2.5">
                                <span class="px-2 py-0.5 text-[9px] font-extrabold rounded-md uppercase tracking-widest border <?= getSeverityClass($req['severity_level']) ?>">
                                    <?= htmlspecialchars($req['severity_level']) ?>
                                </span>
                                <span class="inline-flex items-center gap-1 text-[10px] font-bold text-slate-400 mono">
                                    REQ_ID: <?= htmlspecialchars($req['request_id']) ?>
                                </span>
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="text-[10px] text-slate-400 mono"><?= date('Y-m-d', strtotime($req['required_date'])) ?></span>
                            </div>
                        </div>

                        <div class="flex items-start justify-between gap-4">
                            <div class="space-y-1 truncate">
                                <h4 class="font-extrabold text-slate-900 text-base tracking-tight truncate leading-tight"><?= htmlspecialchars($req['hospital_name']) ?></h4>
                                <p class="text-xs font-medium text-slate-600 truncate">Patient: <?= htmlspecialchars($req['patient_name']) ?></p>
                                <p class="text-xs text-slate-400 font-bold pt-1"><?= htmlspecialchars($req['units_requested']) ?> Units Required</p>
                            </div>
                            <div class="w-11 h-11 rounded-xl border font-black text-sm flex items-center justify-center font-mono shrink-0 <?= getBloodTypeClass($req['blood_type']) ?>">
                                <?= htmlspecialchars($req['blood_type']) ?>
                            </div>
                        </div>

                        <div class="flex items-center justify-between gap-2 pt-1">
                            <div class="flex flex-col gap-1">
                                <span class="inline-block px-2.5 py-1 text-[9px] font-extrabold rounded-md uppercase tracking-widest border <?= getFulfillmentClass($req['fulfillment_status']) ?>">
                                    <?= htmlspecialchars($req['fulfillment_status']) ?>
                                </span>
                                <?php if (!empty($req['donor_name']) && (int)$req['status_id'] !== 7): ?>
                                    <span class="text-[10px] text-slate-500 font-medium">
                                        <?php if ((int)$req['status_id'] === 42): ?>
                                            Assigned to <span class="font-bold text-slate-700"><?= htmlspecialchars($req['donor_name']) ?></span>
                                        <?php elseif ((int)$req['status_id'] === 47): ?>
                                            Declined by <span class="font-bold text-slate-700"><?= htmlspecialchars($req['donor_name']) ?></span>
                                        <?php else: ?>
                                            Accepted by <span class="font-bold text-slate-700"><?= htmlspecialchars($req['donor_name']) ?></span>
                                        <?php endif; ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                            <a href="/BloodConnect/public/admin/blood-request/view?id=<?= (int)$req['request_id'] ?>"
                                class="flex items-center justify-center gap-2 py-2 px-4 bg-slate-50 border border-slate-200 text-slate-700 rounded-xl font-bold text-xs transition-all active:scale-95">
                                Inspect Request
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <?php require __DIR__ . '/../../../Shared/Presentation/View/partials/admin_pagination.php'; ?>
</div>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const viewport = document.getElementById('bloodRequestsContainer');
        if (viewport) {
            requestAnimationFrame(() => {
                viewport.classList.remove('opacity-0', 'translate-y-6');
            });
        }
    });
</script>