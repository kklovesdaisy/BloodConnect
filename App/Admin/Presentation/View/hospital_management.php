<?php
use App\Shared\Helpers\Permission;

$hospitals = $hospitals ?? [];
$flashMessage = $flash_message ?? '';
$flashStatus = $flash_status ?? '';
$canCreateHospital = Permission::can('hospital.create');
$totalHospitals = count($hospitals);
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

    .custom-scroll::-webkit-scrollbar { width: 5px; }
    .custom-scroll::-webkit-scrollbar-track { background: transparent; }
    .custom-scroll::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 20px; }
    .custom-scroll::-webkit-scrollbar-thumb:hover { background: #94a3b8; }

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

<div class="min-h-screen transition-all opacity-0 translate-y-6 ease-out duration-700" id="hospitalManagementContainer">

    <div class="relative bg-white border border-slate-200/60 rounded-2xl p-6 mb-8 shadow-xs flex flex-col md:flex-row md:items-center md:justify-between gap-6">
        <div class="flex items-center gap-4">
            <div class="w-12 h-12 bg-[#ce2424] text-white rounded-2xl flex items-center justify-center shadow-lg shadow-red-600/10 transform hover:scale-105 transition-transform duration-300">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-6 h-6 text-white">
                    <path d="M3 4.5h18v15H3zM7 9h4V7h2v2h4v2h-4v2h-2v-2H7z" />
                </svg>
            </div>
            <div>
                <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight leading-none">Hospital Management</h1>
                <p class="text-xs sm:text-sm text-slate-400 font-medium mt-1.5">Accounts that request blood bags from the central stock.</p>
            </div>
        </div>

        <div class="flex items-center gap-4 self-start md:self-auto">
            <div class="flex items-center gap-4 bg-slate-900 text-white px-5 py-3 rounded-2xl shadow-lg border border-slate-800">
                <div class="flex flex-col">
                    <span class="text-[9px] text-slate-400 font-bold uppercase tracking-widest">Active Pool</span>
                    <span class="text-lg font-extrabold tracking-tight mt-0.5 mono">
                        <?= sprintf('%02d', $totalHospitals) ?> <span class="text-xs text-slate-500 font-normal">nodes</span>
                    </span>
                </div>
                <div class="w-2.5 h-2.5 rounded-full bg-emerald-400 shadow-[0_0_10px_rgba(52,211,153,0.6)] animate-pulse"></div>
            </div>

            <?php if ($canCreateHospital): ?>
                <a href="/BloodConnect/public/admin/hospitals/create"
                    class="inline-flex items-center gap-2 px-4 py-3 rounded-xl text-sm font-bold text-white bg-[#ce2424] hover:bg-red-700 transition-colors shadow-sm">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                    </svg>
                    Create Hospital
                </a>
            <?php endif; ?>
        </div>
    </div>

    <?php if ($flashMessage !== ''): ?>
        <div class="mb-6 px-4 py-3 rounded-xl text-sm font-semibold <?= $flashStatus === 'error' ? 'bg-red-50 border border-red-200 text-red-700' : 'bg-emerald-50 border border-emerald-200 text-emerald-700' ?>">
            <?= htmlspecialchars($flashMessage) ?>
        </div>
    <?php endif; ?>

    <div class="space-y-3">
        <?php if (empty($hospitals)): ?>
            <div class="bg-white border border-slate-200/70 rounded-2xl p-10 text-center animate-spring-in">
                <p class="text-slate-400 font-semibold">No hospital accounts yet.</p>
                <?php if ($canCreateHospital): ?>
                    <a href="/BloodConnect/public/admin/hospitals/create" class="inline-block mt-3 text-sm font-bold text-[#ce2424] hover:underline">
                        Create the first hospital account
                    </a>
                <?php endif; ?>
            </div>
        <?php else: ?>

            <!-- DESKTOP CONTINUOUS LIST (STRICT GRID ALIGNMENT ARCHITECTURE) -->
            <div class="hidden lg:block max-h-[500px] overflow-y-auto pr-1 custom-scroll space-y-3">
                <?php foreach ($hospitals as $index => $hospital): ?>
                    <div class="glow-row bg-white border border-slate-200/70 rounded-2xl p-4 grid grid-cols-12 items-center gap-4 transition-all duration-300 transform animate-spring-in shadow-2xs"
                        style="animation-delay: <?= $index * 0.03 ?>s;">

                        <!-- 1. IDENTITY BLOCK (col-span-3) -->
                        <div class="col-span-3 flex items-center gap-4 min-w-0">
                            <div class="relative shrink-0">
                                <div class="w-11 h-11 rounded-xl bg-red-50 border border-red-200/60 text-red-500 flex items-center justify-center font-bold text-xs uppercase tracking-wider">
                                    <?= mb_substr($hospital['hospital_name'] ?? 'H', 0, 2) ?>
                                </div>
                                <span class="absolute -bottom-0.5 -right-0.5 w-3 h-3 rounded-full border-2 border-white <?= (int)($hospital['is_active'] ?? 1) === 1 ? 'bg-emerald-500' : 'bg-slate-300' ?>"></span>
                            </div>
                            <div class="truncate">
                                <span class="text-[10px] font-bold uppercase text-slate-400 tracking-wider mono block">HSP_<?= str_pad((string)($hospital['hospital_id'] ?? ''), 4, '0', STR_PAD_LEFT) ?></span>
                                <span class="font-bold text-slate-800 text-sm tracking-tight truncate block mt-0.5"><?= htmlspecialchars($hospital['hospital_name'] ?? '—') ?></span>
                            </div>
                        </div>

                        <!-- 2. CONTACT NODE (col-span-3) -->
                        <div class="col-span-3 min-w-0">
                            <span class="text-slate-700 font-semibold tracking-tight text-sm block truncate"><?= htmlspecialchars($hospital['email'] ?? '—') ?></span>
                            <span class="text-xs text-slate-400 font-medium block mt-0.5 mono truncate">Mob: <?= htmlspecialchars($hospital['phone'] ?? $hospital['user_phone'] ?? '—') ?></span>
                        </div>

                        <!-- 3. LOCATION NODE (col-span-2) -->
                        <div class="col-span-2 min-w-0">
                            <span class="text-slate-700 font-semibold tracking-tight text-sm block truncate"><?= htmlspecialchars(trim(($hospital['township'] ?? '') . ', ' . ($hospital['state_region'] ?? ''), ', ')) ?: '—' ?></span>
                            <?php if (!empty($hospital['license_no'])): ?>
                                <span class="text-[10px] text-slate-400 font-medium mono block mt-0.5 truncate">LIC: <?= htmlspecialchars($hospital['license_no']) ?></span>
                            <?php endif; ?>
                        </div>

                        <!-- 4. STATUS NODE (col-span-1) -->
                        <div class="col-span-1 flex justify-center">
                            <?php if ((int)($hospital['is_active'] ?? 1) === 1): ?>
                                <span class="inline-block px-3 py-1 text-[9px] font-extrabold rounded-lg uppercase tracking-widest border shadow-3xs bg-emerald-50 text-emerald-600 border-emerald-200/50">
                                    Active
                                </span>
                            <?php else: ?>
                                <span class="inline-block px-3 py-1 text-[9px] font-extrabold rounded-lg uppercase tracking-widest border shadow-3xs bg-rose-50 text-rose-600 border-rose-200/50">
                                    Inactive
                                </span>
                            <?php endif; ?>
                        </div>

                        <!-- 5. USERNAME STRUCT (col-span-2) -->
                        <div class="col-span-2 text-right text-slate-500 pr-2">
                            <span class="text-xs font-semibold block truncate"><?= htmlspecialchars($hospital['username'] ?? '—') ?></span>
                            <span class="text-[10px] text-slate-400 block mt-0.5 mono">Account</span>
                        </div>

                        <!-- 6. INTERACTIVE ACTIONS DRAWER (col-span-1) -->
                        <div class="col-span-1 flex items-center justify-end gap-1.5 pr-2">
                            <a href="/BloodConnect/public/admin/user/view?id=<?= (int)$hospital['user_id'] ?>&from=hospital"
                                title="Inspect Node"
                                class="p-2 text-slate-400 hover:text-slate-700 hover:bg-slate-50 border border-transparent hover:border-slate-200 rounded-xl transition-all active:scale-90">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.3" stroke="currentColor" class="w-4 h-4">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 003 8.25v10.5A2.25 2.25 0 005.25 21h10.5A2.25 2.25 0 0018 18.75V10.5M10.5 13.5L20.25 3.75M20.25 3.75H15.75M20.25 3.75v4.5" />
                                </svg>
                            </a>
                            <a href="/BloodConnect/public/admin/user/delete?id=<?= (int)$hospital['user_id'] ?>"
                                onclick="return confirm('Are you sure you want to remove this record?')"
                                title="Terminate Entry"
                                class="p-2 text-slate-400 hover:text-red-500 hover:bg-rose-50/50 border border-transparent hover:border-rose-100 rounded-xl transition-all active:scale-90">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.3" stroke="currentColor" class="w-4 h-4">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79" />
                                </svg>
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- MOBILE / TABLET DISPLAY (FLUID DASHBOARD GRIDS) -->
            <div class="block lg:hidden max-h-[500px] overflow-y-auto pr-1 custom-scroll space-y-3.5">
                <?php foreach ($hospitals as $index => $hospital): ?>
                    <div class="bg-white border border-slate-200 rounded-2xl p-5 space-y-4 shadow-3xs transform transition-all duration-300 hover:shadow-2xs active:scale-[0.99] animate-spring-in"
                        style="animation-delay: <?= $index * 0.03 ?>s;">

                        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                            <div class="flex items-center gap-2.5">
                                <span class="px-2 py-0.5 text-[9px] font-extrabold rounded-md uppercase tracking-widest border bg-red-50 text-red-500 border-red-200/50">
                                    HOSPITAL
                                </span>
                                <span class="inline-flex items-center gap-1 text-[10px] font-bold text-slate-400 mono">
                                    HSP_<?= str_pad((string)($hospital['hospital_id'] ?? ''), 4, '0', STR_PAD_LEFT) ?>
                                </span>
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="text-[10px] text-slate-400 mono"><?= htmlspecialchars($hospital['username'] ?? '') ?></span>
                                <span class="w-1.5 h-1.5 rounded-full <?= (int)($hospital['is_active'] ?? 1) === 1 ? 'bg-emerald-500' : 'bg-slate-300' ?>"></span>
                            </div>
                        </div>

                        <div class="flex items-start justify-between gap-4">
                            <div class="space-y-1 truncate">
                                <h4 class="font-extrabold text-slate-900 text-base tracking-tight truncate leading-tight"><?= htmlspecialchars($hospital['hospital_name'] ?? '—') ?></h4>
                                <p class="text-xs font-medium text-slate-600 truncate"><?= htmlspecialchars($hospital['email'] ?? '—') ?></p>
                                <p class="text-xs text-slate-400 mono pt-0.5">Mob: <?= htmlspecialchars($hospital['phone'] ?? $hospital['user_phone'] ?? '—') ?></p>
                                <p class="text-xs text-slate-400 pt-0.5"><?= htmlspecialchars(trim(($hospital['township'] ?? '') . ', ' . ($hospital['state_region'] ?? ''), ', ')) ?: '—' ?></p>
                            </div>
                            <div class="w-11 h-11 rounded-xl bg-red-500/5 text-red-500 border border-red-500/10 font-black text-[10px] flex items-center justify-center font-mono shrink-0">
                                <?= (int)($hospital['is_active'] ?? 1) === 1 ? 'ON' : 'OFF' ?>
                            </div>
                        </div>

                        <div class="flex items-center gap-2 pt-1">
                            <a href="/BloodConnect/public/admin/user/view?id=<?= (int)$hospital['user_id'] ?>&from=hospital"
                                class="flex-1 flex items-center justify-center gap-2 py-2.5 px-4 bg-slate-50 border border-slate-200 text-slate-700 rounded-xl font-bold text-xs transition-all active:scale-95">
                                Inspect Profile
                            </a>
                            <a href="/BloodConnect/public/admin/user/delete?id=<?= (int)$hospital['user_id'] ?>"
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
        <?php endif; ?>
    </div>
</div>

<!-- PAGE STRUCTURAL MOUNT ANIMATION SCRIPT -->
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const viewport = document.getElementById('hospitalManagementContainer');
        if (viewport) {
            requestAnimationFrame(() => {
                viewport.classList.remove('opacity-0', 'translate-y-6');
            });
        }
    });
</script>
