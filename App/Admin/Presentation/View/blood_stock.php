<?php

/** @var array $stock */

$totalBags = 0;
$totalExpiringSoon = 0;
$totalExpired = 0;

foreach ($stock as $row) {
    $totalBags += (int)$row['available_bags'];
    $totalExpiringSoon += (int)($row['expiring_soon'] ?? 0);
    $totalExpired += (int)($row['expired'] ?? 0);
}

function stockStatusPill(int $bags): string
{
    if ($bags === 0) {
        return '<span class="inline-block px-3 py-1 text-[9px] font-extrabold rounded-lg uppercase tracking-widest border shadow-3xs bg-rose-50 text-rose-600 border-rose-200/60">Out of Stock</span>';
    }
    if ($bags <= 2) {
        return '<span class="inline-block px-3 py-1 text-[9px] font-extrabold rounded-lg uppercase tracking-widest border shadow-3xs bg-amber-50 text-amber-600 border-amber-200/60">Low</span>';
    }
    return '<span class="inline-block px-3 py-1 text-[9px] font-extrabold rounded-lg uppercase tracking-widest border shadow-3xs bg-emerald-50 text-emerald-600 border-emerald-200/60">In Stock</span>';
}

function expiryPill(string $kind, int $count): string
{
    if ($count === 0) {
        return '<span class="inline-block px-2.5 py-1 text-[9px] font-extrabold rounded-lg uppercase tracking-widest border shadow-3xs bg-slate-50 text-slate-400 border-slate-200/60">None</span>';
    }

    if ($kind === 'expired') {
        return '<span class="inline-block px-2.5 py-1 text-[9px] font-extrabold rounded-lg uppercase tracking-widest border shadow-3xs bg-rose-50 text-rose-600 border-rose-200/60">' . $count . ' Expired</span>';
    }

    return '<span class="inline-block px-2.5 py-1 text-[9px] font-extrabold rounded-lg uppercase tracking-widest border shadow-3xs bg-amber-50 text-amber-600 border-amber-200/60">' . $count . ' Expiring</span>';
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

    .custom-scroll::-webkit-scrollbar { width: 5px; }
    .custom-scroll::-webkit-scrollbar-track { background: transparent; }
    .custom-scroll::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 20px; }
    .custom-scroll::-webkit-scrollbar-thumb:hover { background: #94a3b8; }
</style>

<div class="min-h-screen transition-all opacity-0 translate-y-6 ease-out duration-700" id="bloodStockContainer">

    <?php if (!empty($_SESSION['flash_message'])): ?>
        <div class="mb-6 px-5 py-4 rounded-2xl border text-sm font-bold <?= ($_SESSION['flash_status'] ?? 'error') === 'error' ? 'bg-rose-50 border-rose-200 text-rose-700' : 'bg-emerald-50 border-emerald-200 text-emerald-700' ?>">
            <?= htmlspecialchars($_SESSION['flash_message']) ?>
        </div>
        <?php unset($_SESSION['flash_message'], $_SESSION['flash_status']); ?>
    <?php endif; ?>

    <div class="relative bg-white border border-slate-200/60 rounded-2xl p-6 mb-8 shadow-xs flex flex-col md:flex-row md:items-center md:justify-between gap-6">
        <div class="flex items-center gap-4">
            <div class="w-12 h-12 bg-[#ce2424] text-white rounded-2xl flex items-center justify-center shadow-lg shadow-red-600/10 transform hover:scale-105 transition-transform duration-300">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-6 h-6 text-white">
                    <path d="M12 2.69l5.66 5.66a8 8 0 1 1-11.31 0z" />
                </svg>
            </div>
            <div>
                <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight leading-none">Blood Stock</h1>
                <p class="text-xs sm:text-sm text-slate-400 font-medium mt-1.5">Bags donated by donors, allocated to hospitals, and available now — per blood group.</p>
            </div>
        </div>

        <div class="flex items-center gap-4 bg-slate-900 text-white px-5 py-3 rounded-2xl shadow-lg border border-slate-800 self-start md:self-auto">
            <div class="flex flex-col">
                <span class="text-[9px] text-slate-400 font-bold uppercase tracking-widest">Total Bags</span>
                <span class="text-lg font-extrabold tracking-tight mt-0.5 mono">
                    <?= $totalBags ?> <span class="text-xs text-slate-500 font-normal">bags</span>
                </span>
            </div>
            <div class="w-2.5 h-2.5 rounded-full bg-red-500 shadow-[0_0_10px_rgba(239,68,68,0.6)] animate-pulse"></div>
        </div>
    </div>

    <?php if ($totalExpired > 0 || $totalExpiringSoon > 0): ?>
        <div class="mb-8 p-5 rounded-2xl border <?= $totalExpired > 0 ? 'bg-rose-50 border-rose-200' : 'bg-amber-50 border-amber-200' ?> flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div class="flex items-start gap-3">
                <div class="w-10 h-10 rounded-xl <?= $totalExpired > 0 ? 'bg-rose-500/10 text-rose-600' : 'bg-amber-500/10 text-amber-600' ?> flex items-center justify-center flex-shrink-0">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="w-5 h-5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                    </svg>
                </div>
                <div>
                    <p class="font-extrabold text-slate-900 tracking-tight">
                        <?php if ($totalExpired > 0): ?>
                            <?= $totalExpired ?> expired bag(s) past their 42-day shelf life
                        <?php else: ?>
                            <?= $totalExpiringSoon ?> bag(s) expiring within 7 days
                        <?php endif; ?>
                    </p>
                    <p class="text-xs text-slate-500 font-medium mt-0.5">
                        Blood bags are safe to store for up to 42 days after collection. Expired bags cannot be issued to hospitals or patients.
                    </p>
                </div>
            </div>
            <?php if ($totalExpired > 0): ?>
                <form method="POST" action="/BloodConnect/public/admin/blood-stock/dispose-expired" class="flex-shrink-0">
                    <button type="submit"
                        class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-rose-600 text-white text-xs font-bold hover:bg-rose-700 shadow-sm shadow-rose-600/20 transition-all active:scale-95">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-4 h-4">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" />
                        </svg>
                        Dispose Expired
                    </button>
                </form>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <div class="mb-8">
        <div class="bg-white border border-slate-200/70 rounded-2xl shadow-2xs overflow-hidden">
                <div class="flex items-center justify-between px-6 py-4 border-b border-slate-100">
                    <h2 class="font-extrabold text-slate-900 tracking-tight">Inventory by Blood Group</h2>
                    <span class="text-[10px] font-bold uppercase tracking-widest text-slate-400">Donations · Hospital Allocations · Expiry</span>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="text-left text-[10px] font-extrabold uppercase tracking-widest text-slate-400 border-b border-slate-100">
                                <th class="px-6 py-3">Blood Group</th>
                                <th class="px-6 py-3">Donated</th>
                                <th class="px-6 py-3">Available Bags</th>
                                <th class="px-6 py-3">Expiry Status</th>
                                <th class="px-6 py-3">Oldest Expiry</th>
                                <th class="px-6 py-3">Status</th>
                                <th class="px-6 py-3 text-right">Last Updated</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($stock as $row): ?>
                                <?php $bags = (int)$row['available_bags']; ?>
                                <tr class="border-b border-slate-50 hover:bg-red-50/20 transition-colors">
                                    <td class="px-6 py-4">
                                        <div class="flex items-center gap-3">
                                            <div class="w-9 h-9 rounded-lg bg-red-500/5 text-red-500 border border-red-500/10 font-black text-xs flex items-center justify-center font-mono">
                                                <?= htmlspecialchars($row['blood_group']) ?>
                                            </div>
                                            <span class="font-bold text-slate-800 tracking-tight"><?= htmlspecialchars($row['blood_group']) ?> blood</span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <span class="text-lg font-extrabold text-slate-900 tracking-tight mono"><?= (int)($row['donated_bags'] ?? 0) ?></span>
                                        <span class="text-xs text-slate-400 font-semibold"> bags</span>
                                    </td>
                                    <td class="px-6 py-4">
                                        <span class="text-xl font-extrabold text-slate-900 tracking-tight mono"><?= $bags ?></span>
                                        <span class="text-xs text-slate-400 font-semibold"> bags</span>
                                    </td>
                                    <td class="px-6 py-4">
                                        <?php if ((int)($row['expired'] ?? 0) > 0): ?>
                                            <?= expiryPill('expired', (int)$row['expired']) ?>
                                        <?php else: ?>
                                            <?= expiryPill('soon', (int)($row['expiring_soon'] ?? 0)) ?>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-6 py-4 text-xs text-slate-500 font-medium mono whitespace-nowrap">
                                        <?= !empty($row['oldest_expiry']) ? date('M d, Y', strtotime((string)$row['oldest_expiry'])) : '—' ?>
                                    </td>
                                    <td class="px-6 py-4"><?= stockStatusPill($bags) ?></td>
                                    <td class="px-6 py-4 text-right text-xs text-slate-400 font-medium mono whitespace-nowrap">
                                        <?= !empty($row['last_updated']) ? date('M d, Y h:i A', strtotime($row['last_updated'])) : '—' ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const viewport = document.getElementById('bloodStockContainer');
        if (viewport) {
            requestAnimationFrame(() => {
                viewport.classList.remove('opacity-0', 'translate-y-6');
            });
        }
    });
</script>
