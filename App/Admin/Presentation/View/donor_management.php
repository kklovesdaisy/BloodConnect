<?php

use App\Shared\Infrastructure\Database\Database;
use App\Shared\Helpers\DonorAvailabilityHelper;

$db = Database::getConnection(); ?>
<style>
.custom-scroll::-webkit-scrollbar { width: 5px; }
.custom-scroll::-webkit-scrollbar-track { background: transparent; }
.custom-scroll::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 20px; }
.custom-scroll::-webkit-scrollbar-thumb:hover { background: #94a3b8; }
</style>
<?php

use App\Shared\Helpers\AdminFilters;

$filter = $_GET['filter'] ?? 'donors';
$highlightId = isset($_GET['highlight']) ? (int)$_GET['highlight'] : 0;

/*
|--------------------------------------------------------------------------
| SEARCH & FILTER PARAMETERS
|--------------------------------------------------------------------------
*/
$q = AdminFilters::strParam('q');
$availability = AdminFilters::strParam('availability');
$bloodGroup = AdminFilters::strParam('blood_group');
$statusFilter = AdminFilters::strParam('status');
$location = AdminFilters::strParam('location');

/*
|--------------------------------------------------------------------------
| FETCH DONORS FROM DATABASE
|--------------------------------------------------------------------------
*/
$where = 'u.user_type_id = 2 AND u.deleted_at IS NULL';
$params = [];

if ($filter === 'available' && $availability === '') {
    $availability = 'available';
}
if ($availability === 'available') {
    $where .= ' AND u.available = 1 AND u.is_active = 1';
} elseif ($availability === 'unavailable') {
    $where .= ' AND u.available = 0 AND u.is_active = 1';
} elseif ($availability === 'inactive') {
    $where .= ' AND u.is_active != 1';
}
if ($statusFilter !== '') {
    $where .= ' AND u.is_active = ?';
    $params[] = $statusFilter === '1' ? 1 : 0;
}
if ($location !== '') {
    $where .= ' AND d.state_region = ?';
    $params[] = $location;
}
if ($bloodGroup !== '') {
    $where .= ' AND (u.blood_group = ? OR md.label = ?)';
    $params[] = $bloodGroup;
    $params[] = $bloodGroup;
}
if ($q !== '') {
    $where .= ' AND (u.username LIKE ? OR u.email LIKE ? OR u.phone LIKE ? OR u.blood_group LIKE ? OR md.label LIKE ?)';
    $like = '%' . $q . '%';
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
}

$stmt = $db->prepare("
    SELECT COUNT(*)
    FROM users u
    LEFT JOIN donors d ON d.user_id = u.user_id
    LEFT JOIN master_data md
        ON md.category = 'BLOOD_GROUP'
        AND md.code = u.blood_group
    WHERE {$where}
");
$stmt->execute($params);
$totalDonors = (int)$stmt->fetchColumn();

$adminPagination = AdminFilters::paginate($totalDonors, 20);

$stmt = $db->prepare("
    SELECT 
        u.user_id,
        u.username,
        u.email,
        u.phone,
        u.is_active,
        u.blood_group,
        u.next_available_date,
        d.date_of_birth,
        d.weight,
        CONCAT('DNR-', u.user_id + 4200) AS donor_id,
        CASE
            WHEN u.is_active != 1 THEN 'INACTIVE'
            WHEN u.available = 1 OR u.next_available_date IS NULL THEN 'AVAILABLE'
            ELSE 'UNAVAILABLE'
        END AS availability_status
    FROM users u
    LEFT JOIN donors d ON d.user_id = u.user_id
    LEFT JOIN master_data md
        ON md.category = 'BLOOD_GROUP'
        AND md.code = u.blood_group
    WHERE {$where}
    ORDER BY u.user_id DESC
    LIMIT {$adminPagination['perPage']} OFFSET {$adminPagination['offset']}
");

$stmt->execute($params);
$donors = $stmt->fetchAll();

// Override availability status with eligibility check (age 18-65, weight >= 50kg)
foreach ($donors as &$donor) {
    $donor['availability_status'] = DonorAvailabilityHelper::withEligibilityOverride(
        $donor['availability_status'],
        $donor['date_of_birth'] ?? null,
        $donor['weight'] ?? null
    );
    $donor['eligibility_reasons'] = DonorAvailabilityHelper::eligibilityReasons(
        $donor['date_of_birth'] ?? null,
        $donor['weight'] ?? null
    );
}
unset($donor);

$pageTitle = match ($filter) {
    'available' => 'Available Donors',
    default => 'Donor Management',
};
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

<div class="max-w-7xl mx-auto p-4 sm:p-6 lg:p-8 min-h-screen transition-all opacity-0 translate-y-6 ease-out duration-700" id="donorManagementContainer">

    <div class="relative bg-white border border-slate-200/60 rounded-2xl p-6 mb-8 shadow-xs flex flex-col md:flex-row md:items-center md:justify-between gap-6">
        <div class="flex items-center gap-4">
            <div class="w-12 h-12 bg-[#ce2424] text-white rounded-2xl flex items-center justify-center shadow-lg shadow-red-600/10 transform hover:scale-105 transition-transform duration-300">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-6 h-6 text-white">
                    <path d="M12 2.69l5.66 5.66a8 8 0 1 1-11.31 0z" />
                </svg>
            </div>
            <div>
                <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight leading-none"><?= htmlspecialchars($pageTitle) ?></h1>
                <p class="text-xs sm:text-sm text-slate-400 font-medium mt-1.5">Direct system registry controls for live donor entities.</p>
            </div>
        </div>

        <div class="flex items-center gap-4 bg-slate-900 text-white px-5 py-3 rounded-2xl shadow-lg border border-slate-800 self-start md:self-auto">
            <div class="flex flex-col">
                <span class="text-[9px] text-slate-400 font-bold uppercase tracking-widest">Active Pool</span>
                <span class="text-lg font-extrabold tracking-tight mt-0.5 mono">
                    <?= sprintf('%02d', $adminPagination['total']) ?> <span class="text-xs text-slate-500 font-normal">nodes</span>
                </span>
            </div>
            <div class="w-2.5 h-2.5 rounded-full bg-emerald-400 shadow-[0_0_10px_rgba(52,211,153,0.6)] animate-pulse"></div>
        </div>
    </div>

    <?php
    $locationStmt = $db->prepare("SELECT DISTINCT state_region FROM donors WHERE state_region IS NOT NULL AND state_region <> '' ORDER BY state_region");
    $locationStmt->execute();
    $donorLocations = [];
    foreach ($locationStmt->fetchAll(\PDO::FETCH_COLUMN) as $loc) {
        $donorLocations[(string)$loc] = (string)$loc;
    }

    $adminFilterConfig = [
        'action' => '/BloodConnect/public/admin/donor-management',
        'search_name' => 'q',
        'search_placeholder' => 'Search name, email, phone or blood group…',
        'fields' => [
            [
                'type' => 'select',
                'name' => 'availability',
                'label' => 'Availability',
                'options' => [
                    'available' => 'Available',
                    'unavailable' => 'Unavailable',
                    'inactive' => 'Inactive',
                ],
            ],
            [
                'type' => 'select',
                'name' => 'status',
                'label' => 'Status',
                'options' => ['1' => 'Active', '0' => 'Inactive'],
            ],
            [
                'type' => 'select',
                'name' => 'blood_group',
                'label' => 'Blood Group',
                'options' => AdminFilters::bloodGroups($db),
            ],
            [
                'type' => 'select',
                'name' => 'location',
                'label' => 'Location',
                'options' => $donorLocations,
            ],
        ],
    ];
    require __DIR__ . '/../../../Shared/Presentation/View/partials/admin_filter_bar.php';
    ?>

    <div class="space-y-3">

        <div class="hidden lg:block max-h-[500px] overflow-y-auto pr-1 custom-scroll space-y-3">
            <?php foreach ($donors as $index => $donor): ?>
                <?php $isHighlighted = $highlightId > 0 && (int)$donor['user_id'] === $highlightId; ?>
                <div class="glow-row bg-white border rounded-2xl p-4 grid grid-cols-12 items-center gap-4 transition-all duration-300 transform animate-spring-in shadow-2xs <?= $isHighlighted ? 'border-[#ce2424] ring-2 ring-[#ce2424]/20' : 'border-slate-200/70' ?>"
                    style="animation-delay: <?= $index * 0.03 ?>s;">

                    <div class="col-span-3 flex items-center gap-4 min-w-0">
                        <div class="relative shrink-0">
                            <div class="w-11 h-11 rounded-xl bg-emerald-50 border border-emerald-200/60 text-emerald-600 flex items-center justify-center font-bold text-xs uppercase tracking-wider">
                                <?= mb_substr($donor['username'], 0, 2) ?>
                            </div>
                            <span class="absolute -bottom-0.5 -right-0.5 w-3 h-3 rounded-full border-2 border-white <?= $donor['is_active'] == 1 ? 'bg-emerald-500' : 'bg-slate-300' ?>"></span>
                        </div>
                        <div class="truncate">
                            <span class="text-[10px] font-bold uppercase text-slate-400 tracking-wider mono block">ID: <?= htmlspecialchars($donor['donor_id']) ?></span>
                            <span class="font-bold text-slate-800 text-sm tracking-tight truncate block mt-0.5"><?= htmlspecialchars($donor['username']) ?></span>
                        </div>
                    </div>

                    <div class="col-span-3 min-w-0">
                        <span class="text-slate-700 font-semibold tracking-tight text-sm block truncate"><?= htmlspecialchars($donor['email']) ?></span>
                        <span class="text-xs text-slate-400 font-medium block mt-0.5 mono truncate">Mob: <?= htmlspecialchars($donor['phone'] ?? '—') ?></span>
                    </div>

                    <div class="col-span-2 flex flex-col items-start justify-center pl-4 gap-1">
                        <?php if ($donor['availability_status'] === 'AVAILABLE'): ?>
                            <span class="inline-block px-3 py-1 text-[9px] font-extrabold rounded-lg uppercase tracking-widest border shadow-3xs bg-emerald-500 text-white border-emerald-600 <?= $isHighlighted ? 'ring-2 ring-emerald-300 ring-offset-1' : '' ?>">
                                AVAILABLE
                            </span>
                        <?php elseif ($donor['availability_status'] === 'UNAVAILABLE'): ?>
                            <span class="inline-block px-3 py-1 text-[9px] font-extrabold rounded-lg uppercase tracking-widest border shadow-3xs bg-red-500 text-white border-red-600 <?= $isHighlighted ? 'ring-2 ring-red-300 ring-offset-1' : '' ?>"
                                  title="<?= !empty($donor['eligibility_reasons']) ? 'Eligibility: ' . implode(', ', $donor['eligibility_reasons']) : '' ?>">
                                UNAVAILABLE
                            </span>
                            <?php if (!empty($donor['eligibility_reasons'])): ?>
                                <span class="text-[10px] text-red-500 font-medium mono whitespace-nowrap">
                                    <?= htmlspecialchars(implode(', ', $donor['eligibility_reasons'])) ?>
                                </span>
                            <?php endif; ?>
                            <?php if (!empty($donor['next_available_date'])): 
                                $remaining = floor((strtotime($donor['next_available_date']) - time()) / 86400);
                            ?>
                                <span class="text-[10px] text-slate-500 font-medium mono whitespace-nowrap">
                                    Eligible: <?= date('M d, Y', strtotime($donor['next_available_date'])) ?>
                                </span>
                                <span class="text-[10px] font-bold text-amber-600 mono whitespace-nowrap ml-1">
                                    (<?= max(0, $remaining) ?>d rem.)
                                </span>
                            <?php endif; ?>
                        <?php else: ?>
                            <span class="inline-block px-3 py-1 text-[9px] font-extrabold rounded-lg uppercase tracking-widest border shadow-3xs bg-rose-50 text-rose-600 border-rose-200/50 <?= $isHighlighted ? 'ring-2 ring-rose-300 ring-offset-1' : '' ?>">
                                INACTIVE
                            </span>
                        <?php endif; ?>
                    </div>

                    <div class="col-span-1 flex justify-center">
                        <span class="inline-flex items-center justify-center px-2.5 py-1 rounded-lg bg-red-500/5 text-red-500 border border-red-500/10 font-black text-xs shadow-3xs font-mono">
                            <?= htmlspecialchars($donor['blood_group']) ?>
                        </span>
                    </div>

                    <div class="col-span-2 text-right text-slate-500 pr-2">
                        <span class="text-xs font-semibold block">Jul 08, 2026</span>
                        <span class="text-[10px] text-slate-400 block mt-0.5 mono">13:43 UTC</span>
                    </div>

                    <div class="col-span-1 flex items-center justify-end gap-1.5 pr-2">
                        <a href="/BloodConnect/public/admin/user/view?id=<?= $donor['user_id'] ?>&from=donor"
                            title="View Profile"
                            class="p-2 text-slate-400 hover:text-slate-700 hover:bg-slate-50 border border-transparent hover:border-slate-200 rounded-xl transition-all active:scale-90">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.3" stroke="currentColor" class="w-4 h-4">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 003 8.25v10.5A2.25 2.25 0 005.25 21h10.5A2.25 2.25 0 0018 18.75V10.5M10.5 13.5L20.25 3.75M20.25 3.75H15.75M20.25 3.75v4.5" />
                            </svg>
                        </a>
                        <a href="/BloodConnect/public/admin/user/delete?id=<?= $donor['user_id'] ?>"
                            onclick="return confirm('Are you sure you want to remove this record?')"
                            title="Delete Account"
                            class="p-2 text-slate-400 hover:text-red-500 hover:bg-rose-50/50 border border-transparent hover:border-rose-100 rounded-xl transition-all active:scale-90">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.3" stroke="currentColor" class="w-4 h-4">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79" />
                            </svg>
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="block lg:hidden max-h-[500px] overflow-y-auto pr-1 custom-scroll space-y-3.5">
            <?php foreach ($donors as $index => $donor): ?>
                <div class="bg-white border border-slate-200 rounded-2xl p-5 space-y-4 shadow-3xs transform transition-all duration-300 hover:shadow-2xs active:scale-[0.99] animate-spring-in"
                    style="animation-delay: <?= $index * 0.03 ?>s;">

                    <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                        <div class="flex items-center gap-2.5">
                            <span class="px-2 py-0.5 text-[9px] font-extrabold rounded-md uppercase tracking-widest border bg-emerald-50 text-emerald-600 border-emerald-200/50">
                                DONOR
                            </span>
                            <span class="inline-flex items-center gap-1 text-[10px] font-bold text-slate-400 mono">
                                ID: <?= htmlspecialchars($donor['donor_id']) ?>
                            </span>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="text-[10px] text-slate-400 mono">Jul 08, 2026</span>
                            <span class="w-1.5 h-1.5 rounded-full <?= $donor['is_active'] == 1 ? 'bg-emerald-500' : 'bg-slate-300' ?>"></span>
                        </div>
                    </div>

                    <div class="flex items-start justify-between gap-4">
                        <div class="space-y-1 truncate">
                            <h4 class="font-extrabold text-slate-900 text-base tracking-tight truncate leading-tight"><?= htmlspecialchars($donor['username']) ?></h4>
                            <p class="text-xs font-medium text-slate-600 truncate"><?= htmlspecialchars($donor['email']) ?></p>
                            <p class="text-xs text-slate-400 mono pt-0.5">Mob: <?= htmlspecialchars($donor['phone'] ?? '—') ?></p>
                        </div>
                        <div class="w-11 h-11 rounded-xl bg-red-50/5 text-red-500 border border-red-500/10 font-black text-sm flex items-center justify-center font-mono shrink-0">
                            <?= htmlspecialchars($donor['blood_group']) ?>
                        </div>
                    </div>

                    <?php if ($donor['availability_status'] === 'UNAVAILABLE'): ?>
                        <?php if (!empty($donor['eligibility_reasons'])): ?>
                            <div class="flex items-center gap-2 px-3 py-2 bg-red-50 border border-red-100 rounded-xl">
                                <span class="text-[10px] text-red-600 font-semibold">
                                    <?= htmlspecialchars(implode(', ', $donor['eligibility_reasons'])) ?>
                                </span>
                            </div>
                        <?php endif; ?>
                        <?php if (!empty($donor['next_available_date'])):
                            $remaining = floor((strtotime($donor['next_available_date']) - time()) / 86400);
                        ?>
                            <div class="flex items-center gap-2 px-3 py-2 bg-amber-50 border border-amber-100 rounded-xl">
                                <span class="text-[10px] text-amber-700 font-semibold">
                                    Next eligible: <?= date('M d, Y', strtotime($donor['next_available_date'])) ?>
                                </span>
                                <span class="text-[10px] font-bold text-amber-700">
                                    (<?= max(0, $remaining) ?>d rem.)
                                </span>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>

                    <div class="flex items-center gap-2 pt-1">
                        <a href="/BloodConnect/public/admin/user/view?id=<?= $donor['user_id'] ?>&from=donor"
                            class="flex-1 flex items-center justify-center gap-2 py-2.5 px-4 bg-slate-50 border border-slate-200 text-slate-700 rounded-xl font-bold text-xs transition-all active:scale-95">
                            Inspect Profile
                        </a>
                        <a href="/BloodConnect/public/admin/user/delete?id=<?= $donor['user_id'] ?>"
                            onclick="return confirm('Are you sure you want to remove this record?')"
                            class="py-2.5 px-3.5 bg-rose-50 border border-rose-100 text-rose-500 rounded-xl transition-all active:scale-95 flex items-center justify-center">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.3" stroke="currentColor" class="w-3.5 h-3.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79" />
                            </svg>
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <?php require __DIR__ . '/../../../Shared/Presentation/View/partials/admin_pagination.php'; ?>
</div>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const viewport = document.getElementById('donorManagementContainer');
        if (viewport) {
            requestAnimationFrame(() => {
                viewport.classList.remove('opacity-0', 'translate-y-6');
            });
        }
    });
</script>