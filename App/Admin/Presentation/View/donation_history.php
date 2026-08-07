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
    $conditions[] = 'br.status = ?';
    $params[] = (int)$statusFilter;
}
if ($bloodGroup !== '') {
    $conditions[] = 'br.blood_group_needed = ?';
    $params[] = $bloodGroup;
}
if ($q !== '') {
    $conditions[] = '(donor.username LIKE ? OR br.patient_name LIKE ? OR br.hospital_name LIKE ? OR br.request_code LIKE ?)';
    $like = '%' . $q . '%';
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
}
if ($dateFrom !== '') {
    $conditions[] = 'DATE(br.created_at) >= ?';
    $params[] = $dateFrom;
}
if ($dateTo !== '') {
    $conditions[] = 'DATE(br.created_at) <= ?';
    $params[] = $dateTo;
}

// Donation history == requests currently in ACCEPTED / COMPLETED state
$conditions[] = "br.status IN (
    SELECT id FROM master_data
    WHERE category = 'REQUEST_STATUS' AND code IN ('ACCEPTED', 'COMPLETED')
)";

$where = !empty($conditions) ? 'WHERE ' . implode(' AND ', $conditions) : '';

$stmt = $db->prepare("
    SELECT COUNT(*)
    FROM blood_requests br
    LEFT JOIN users donor ON donor.user_id = br.donor_id
    {$where}
");
$stmt->execute($params);
$totalDonations = (int)$stmt->fetchColumn();

$adminPagination = AdminFilters::paginate($totalDonations, 20);

$stmt = $db->prepare("
    SELECT
        br.request_id AS donation_id,
        br.created_at AS donation_date,
        br.status AS status_id,
        COALESCE(md.label, '—') AS status_label,
        br.request_id,
        br.request_code,
        br.patient_name,
        br.hospital_name,
        br.blood_group_needed AS blood_type,
        donor.user_id AS donor_user_id,
        donor.username AS donor_name,
        donor.phone AS donor_phone
    FROM blood_requests br
    LEFT JOIN users donor ON donor.user_id = br.donor_id
    LEFT JOIN master_data md ON md.id = br.status
    {$where}
    ORDER BY br.created_at DESC, br.request_id DESC
    LIMIT {$adminPagination['perPage']} OFFSET {$adminPagination['offset']}
");
$stmt->execute($params);
$donations = $stmt->fetchAll();

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

<div class="max-w-7xl mx-auto p-4 sm:p-6 lg:p-8 min-h-screen transition-all opacity-0 translate-y-6 ease-out duration-700" id="donationHistoryContainer">

    <div class="relative bg-white border border-slate-200/60 rounded-2xl p-6 mb-8 shadow-xs flex flex-col md:flex-row md:items-center md:justify-between gap-6">
        <div class="flex items-center gap-4">
            <div class="w-12 h-12 bg-[#ce2424] text-white rounded-2xl flex items-center justify-center shadow-lg shadow-red-600/10 transform hover:scale-105 transition-transform duration-300">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-6 h-6 text-white">
                    <path d="M12 2.69l5.66 5.66a8 8 0 1 1-11.31 0z" />
                </svg>
            </div>
            <div>
                <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight leading-none">Donation History</h1>
                <p class="text-xs sm:text-sm text-slate-400 font-medium mt-1.5">Complete audit ledger of completed donor contributions.</p>
            </div>
        </div>

        <div class="flex items-center gap-4 bg-slate-900 text-white px-5 py-3 rounded-2xl shadow-lg border border-slate-800 self-start md:self-auto">
            <div class="flex flex-col">
                <span class="text-[9px] text-slate-400 font-bold uppercase tracking-widest">Ledger Entries</span>
                <span class="text-lg font-extrabold tracking-tight mt-0.5 mono">
                    <?= sprintf('%02d', $adminPagination['total']) ?> <span class="text-xs text-slate-500 font-normal">records</span>
                </span>
            </div>
            <div class="w-2.5 h-2.5 rounded-full bg-red-500 shadow-[0_0_10px_rgba(239,68,68,0.6)] animate-pulse"></div>
        </div>
    </div>

    <?php
    $statusOptions = [
        '8' => 'Accepted',
        '9' => 'Completed',
    ];
    $adminFilterConfig = [
        'action' => '/BloodConnect/public/admin/donation-history',
        'search_name' => 'q',
        'search_placeholder' => 'Search donor, patient, hospital or request code…',
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
                'options' => AdminFilters::bloodGroups($db),
            ],
            ['type' => 'date', 'name' => 'from', 'label' => 'From'],
            ['type' => 'date', 'name' => 'to', 'label' => 'To'],
        ],
    ];
    require __DIR__ . '/../../../Shared/Presentation/View/partials/admin_filter_bar.php';
    ?>

    <div class="space-y-3">

        <?php if (empty($donations)): ?>
            <div class="bg-white border border-slate-200/70 rounded-2xl p-12 text-center text-slate-400 font-medium animate-spring-in">
                No donation records match the current ledger view.
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
                            <div class="truncate">
                                <span class="text-[10px] font-bold uppercase text-slate-400 tracking-wider mono block">DNT_<?= str_pad($d['donation_id'], 4, '0', STR_PAD_LEFT) ?></span>
                                <span class="font-bold text-slate-800 text-sm tracking-tight truncate block mt-0.5"><?= htmlspecialchars($d['donor_name'] ?? 'Unknown') ?></span>
                            </div>
                        </div>

                        <div class="col-span-3 min-w-0">
                            <span class="text-slate-700 font-semibold tracking-tight text-sm block truncate"><?= htmlspecialchars($d['patient_name'] ?? '—') ?></span>
                            <span class="text-xs text-slate-400 font-medium block mt-0.5 mono truncate"><?= htmlspecialchars($d['hospital_name'] ?? '—') ?></span>
                        </div>

                        <div class="col-span-1 flex justify-center">
                            <span class="inline-flex items-center justify-center px-2.5 py-1 rounded-lg bg-red-500/5 text-red-500 border border-red-500/10 font-black text-xs shadow-3xs font-mono">
                                <?= htmlspecialchars($d['blood_type'] ?? 'N/A') ?>
                            </span>
                        </div>

                        <div class="col-span-2 flex justify-start pl-4">
                            <span class="inline-block px-3 py-1 text-[9px] font-extrabold rounded-lg uppercase tracking-widest border shadow-3xs <?= donationStatusClass($d['status_label']) ?>">
                                <?= htmlspecialchars($d['status_label']) ?>
                            </span>
                        </div>

                        <div class="col-span-2 text-right text-slate-500 pr-2">
                            <span class="text-xs font-semibold block"><?= date('M d, Y', strtotime($d['donation_date'])) ?></span>
                            <span class="text-[10px] text-slate-400 block mt-0.5 mono"><?= htmlspecialchars($d['request_code'] ?? '') ?></span>
                        </div>

                        <div class="col-span-1 flex items-center justify-end pr-2">
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
                        </div>

                        <div class="flex items-start justify-between gap-4">
                            <div class="space-y-1 truncate">
                                <h4 class="font-extrabold text-slate-900 text-base tracking-tight truncate leading-tight"><?= htmlspecialchars($d['donor_name'] ?? 'Unknown') ?></h4>
                                <p class="text-xs font-medium text-slate-600 truncate">Patient: <?= htmlspecialchars($d['patient_name'] ?? '—') ?></p>
                                <p class="text-xs text-slate-400 mono pt-0.5 truncate"><?= htmlspecialchars($d['hospital_name'] ?? '—') ?></p>
                            </div>
                            <div class="w-11 h-11 rounded-xl bg-red-500/5 text-red-500 border border-red-500/10 font-black text-sm flex items-center justify-center font-mono shrink-0">
                                <?= htmlspecialchars($d['blood_type'] ?? 'N/A') ?>
                            </div>
                        </div>

                        <div class="flex items-center gap-2 pt-1">
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
