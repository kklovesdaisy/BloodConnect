<?php

/** @var array $stock */
/** @var array $adjustments */

$totalBags = 0;
$stockedGroups = 0;
$lowGroups = 0;
$outGroups = 0;

foreach ($stock as $row) {
    $bags = (int)$row['available_bags'];
    $totalBags += $bags;
    if ($bags > 0) {
        $stockedGroups++;
    }
    if ($bags === 0) {
        $outGroups++;
    } elseif ($bags <= 2) {
        $lowGroups++;
    }
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

<div class="max-w-7xl mx-auto p-4 sm:p-6 lg:p-8 min-h-screen transition-all opacity-0 translate-y-6 ease-out duration-700" id="bloodStockContainer">

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
                <p class="text-xs sm:text-sm text-slate-400 font-medium mt-1.5">Donor blood bag storage inventory across all blood groups.</p>
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

    <!-- Summary Tiles -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
        <div class="bg-white border border-slate-200/70 rounded-2xl p-5 flex items-center gap-4 animate-spring-in">
            <div class="w-11 h-11 rounded-xl bg-emerald-50 border border-emerald-200/60 text-emerald-600 flex items-center justify-center shrink-0">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-5 h-5"><path d="M12 2.5C12 2.5 5 9.5 5 14.5C5 18.37 8.13 21.5 12 21.5C15.87 21.5 19 18.37 19 14.5C19 9.5 12 2.5 12 2.5Z" /></svg>
            </div>
            <div>
                <span class="text-[10px] font-bold uppercase text-slate-400 tracking-wider block">Groups Stocked</span>
                <span class="text-xl font-extrabold text-slate-900 tracking-tight mono"><?= $stockedGroups ?><span class="text-xs text-slate-400 font-normal"> / 8</span></span>
            </div>
        </div>
        <div class="bg-white border border-slate-200/70 rounded-2xl p-5 flex items-center gap-4 animate-spring-in" style="animation-delay: .05s">
            <div class="w-11 h-11 rounded-xl bg-amber-50 border border-amber-200/60 text-amber-600 flex items-center justify-center shrink-0">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-5 h-5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" /></svg>
            </div>
            <div>
                <span class="text-[10px] font-bold uppercase text-slate-400 tracking-wider block">Groups Low</span>
                <span class="text-xl font-extrabold text-slate-900 tracking-tight mono"><?= $lowGroups ?></span>
            </div>
        </div>
        <div class="bg-white border border-slate-200/70 rounded-2xl p-5 flex items-center gap-4 animate-spring-in" style="animation-delay: .1s">
            <div class="w-11 h-11 rounded-xl bg-rose-50 border border-rose-200/60 text-rose-600 flex items-center justify-center shrink-0">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-5 h-5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m0 3.75h.008v.008H12v-.008zM9 12.75h6m-3 3v-6" /></svg>
            </div>
            <div>
                <span class="text-[10px] font-bold uppercase text-slate-400 tracking-wider block">Groups Out</span>
                <span class="text-xl font-extrabold text-slate-900 tracking-tight mono"><?= $outGroups ?></span>
            </div>
        </div>
        <div class="bg-white border border-slate-200/70 rounded-2xl p-5 flex items-center gap-4 animate-spring-in" style="animation-delay: .15s">
            <div class="w-11 h-11 rounded-xl bg-slate-100 border border-slate-200/60 text-slate-600 flex items-center justify-center shrink-0">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-5 h-5"><path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625z" /></svg>
            </div>
            <div>
                <span class="text-[10px] font-bold uppercase text-slate-400 tracking-wider block">Avg per Group</span>
                <span class="text-xl font-extrabold text-slate-900 tracking-tight mono"><?= $totalBags > 0 ? round($totalBags / max(1, $stockedGroups), 1) : 0 ?></span>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
        <!-- Stock Table -->
        <div class="lg:col-span-2">
            <div class="bg-white border border-slate-200/70 rounded-2xl shadow-2xs overflow-hidden">
                <div class="flex items-center justify-between px-6 py-4 border-b border-slate-100">
                    <h2 class="font-extrabold text-slate-900 tracking-tight">Inventory by Blood Group</h2>
                    <span class="text-[10px] font-bold uppercase tracking-widest text-slate-400">Auto + Manual Updates</span>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="text-left text-[10px] font-extrabold uppercase tracking-widest text-slate-400 border-b border-slate-100">
                                <th class="px-6 py-3">Blood Group</th>
                                <th class="px-6 py-3">Available Bags</th>
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
                                        <span class="text-xl font-extrabold text-slate-900 tracking-tight mono"><?= $bags ?></span>
                                        <span class="text-xs text-slate-400 font-semibold"> bags</span>
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

        <!-- Adjust Form -->
        <div class="lg:col-span-1">
            <div class="bg-white border border-slate-200/70 rounded-2xl shadow-2xs p-6 sticky top-6">
                <div class="flex items-center gap-3 mb-5">
                    <div class="w-10 h-10 rounded-xl bg-[#ce2424] text-white flex items-center justify-center shadow-lg shadow-red-600/10">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="w-5 h-5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                    </div>
                    <div>
                        <h2 class="font-extrabold text-slate-900 tracking-tight">Manual Adjustment</h2>
                        <p class="text-[11px] text-slate-400 font-medium">Seed stock or correct discrepancies.</p>
                    </div>
                </div>

                <form method="POST" action="/BloodConnect/public/admin/blood-stock/adjust" class="space-y-4">
                    <div>
                        <label class="block text-[10px] font-extrabold uppercase tracking-widest text-slate-400 mb-1.5">Blood Group</label>
                        <select name="blood_group" required
                            class="w-full px-4 py-2.5 rounded-xl border border-slate-200 bg-white text-sm font-semibold text-slate-700 focus:outline-none focus:ring-2 focus:ring-red-500/20 focus:border-red-300 transition-all">
                            <option value="">Select blood group…</option>
                            <?php foreach ($stock as $row): ?>
                                <option value="<?= htmlspecialchars($row['blood_group']) ?>"><?= htmlspecialchars($row['blood_group']) ?> (<?= (int)$row['available_bags'] ?> bags)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-[10px] font-extrabold uppercase tracking-widest text-slate-400 mb-1.5">Quantity</label>
                            <input type="number" name="quantity" min="1" max="100" value="1" required
                                class="w-full px-4 py-2.5 rounded-xl border border-slate-200 bg-white text-sm font-bold text-slate-800 focus:outline-none focus:ring-2 focus:ring-red-500/20 focus:border-red-300 transition-all">
                        </div>
                        <div>
                            <label class="block text-[10px] font-extrabold uppercase tracking-widest text-slate-400 mb-1.5">Operation</label>
                            <select name="operation" required
                                class="w-full px-4 py-2.5 rounded-xl border border-slate-200 bg-white text-sm font-semibold text-slate-700 focus:outline-none focus:ring-2 focus:ring-red-500/20 focus:border-red-300 transition-all">
                                <option value="add">Add</option>
                                <option value="deduct">Deduct</option>
                            </select>
                        </div>
                    </div>

                    <div>
                        <label class="block text-[10px] font-extrabold uppercase tracking-widest text-slate-400 mb-1.5">Reason (optional)</label>
                        <input type="text" name="reason" maxlength="255" placeholder="e.g. New batch received, damaged unit…"
                            class="w-full px-4 py-2.5 rounded-xl border border-slate-200 bg-white text-sm font-semibold text-slate-700 placeholder:text-slate-300 focus:outline-none focus:ring-2 focus:ring-red-500/20 focus:border-red-300 transition-all">
                    </div>

                    <button type="submit"
                        class="w-full py-3 px-4 rounded-xl bg-[#ce2424] text-white text-sm font-extrabold shadow-lg shadow-red-600/20 hover:bg-red-700 active:scale-[0.98] transition-all duration-200">
                        Apply Adjustment
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Recent Adjustments Log -->
    <div class="bg-white border border-slate-200/70 rounded-2xl shadow-2xs overflow-hidden">
        <div class="flex items-center justify-between px-6 py-4 border-b border-slate-100">
            <h2 class="font-extrabold text-slate-900 tracking-tight">Recent Adjustments</h2>
            <span class="text-[10px] font-bold uppercase tracking-widest text-slate-400">Audit Trail</span>
        </div>

        <?php if (empty($adjustments)): ?>
            <div class="p-12 text-center text-slate-400 font-medium">
                No manual adjustments recorded yet.
            </div>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-left text-[10px] font-extrabold uppercase tracking-widest text-slate-400 border-b border-slate-100">
                            <th class="px-6 py-3">Blood Group</th>
                            <th class="px-6 py-3">Change</th>
                            <th class="px-6 py-3">Reason</th>
                            <th class="px-6 py-3">By</th>
                            <th class="px-6 py-3 text-right">When</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($adjustments as $a): ?>
                            <?php $delta = (int)$a['delta']; ?>
                            <tr class="border-b border-slate-50 hover:bg-red-50/20 transition-colors">
                                <td class="px-6 py-3.5">
                                    <span class="inline-flex items-center justify-center px-2.5 py-1 rounded-lg bg-red-500/5 text-red-500 border border-red-500/10 font-black text-xs font-mono">
                                        <?= htmlspecialchars($a['blood_group']) ?>
                                    </span>
                                </td>
                                <td class="px-6 py-3.5">
                                    <span class="font-extrabold mono <?= $delta >= 0 ? 'text-emerald-600' : 'text-rose-600' ?>">
                                        <?= $delta >= 0 ? '+' : '' ?><?= $delta ?>
                                    </span>
                                </td>
                                <td class="px-6 py-3.5 text-slate-600 font-medium"><?= htmlspecialchars($a['reason'] ?? '—') ?></td>
                                <td class="px-6 py-3.5 text-slate-500 font-semibold"><?= htmlspecialchars($a['actor'] ?? 'System') ?></td>
                                <td class="px-6 py-3.5 text-right text-xs text-slate-400 font-medium mono whitespace-nowrap">
                                    <?= date('M d, Y h:i A', strtotime($a['created_at'])) ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
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
