<?php
$stock = $stock ?? [];

function hospitalStockBadge(int $bags): string
{
    if ($bags <= 0) return 'text-rose-600 bg-rose-50 border-rose-200';
    if ($bags <= 2) return 'text-amber-600 bg-amber-50 border-amber-200';
    return 'text-emerald-600 bg-emerald-50 border-emerald-200';
}

function hospitalBarColor(int $bags): string
{
    if ($bags <= 0) return 'bg-rose-500';
    if ($bags <= 2) return 'bg-amber-500';
    return 'bg-emerald-500';
}
?>
<style>
    @keyframes fadeInUp { from { opacity: 0; transform: translateY(12px); } to { opacity: 1; transform: translateY(0); } }
    .animate-fade-in-up { animation: fadeInUp .4s cubic-bezier(.16,1,.3,1) forwards; }
</style>

<div class="max-w-7xl mx-auto space-y-6">
    <div class="bg-white border border-slate-200/60 rounded-2xl p-6 flex flex-col lg:flex-row lg:items-center lg:justify-between gap-5 shadow-xs animate-fade-in-up">
        <div class="flex items-center gap-4">
            <div class="w-12 h-12 bg-[#ce2424] text-white rounded-2xl flex items-center justify-center shadow-lg shadow-red-600/10 shrink-0">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6"><path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5m8.25 3v6.75m0 0l-3-3m3 3l3-3M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z" /></svg>
            </div>
            <div>
                <h1 class="text-xl sm:text-2xl font-extrabold text-slate-900 tracking-tight leading-none">My Blood Stock</h1>
                <p class="text-xs sm:text-sm text-slate-400 font-medium mt-1.5">Bags allocated to your hospital from your stock requests</p>
            </div>
        </div>
        <a href="/BloodConnect/public/hospital/requests/create"
            class="inline-flex items-center gap-2 px-5 py-2.5 bg-[#ce2424] text-white text-sm font-bold rounded-xl hover:bg-[#b81f1f] transition-all active:scale-95 shadow-md shadow-red-600/20 self-start lg:self-auto">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-4 h-4"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v6m3-3H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
            Request Stock
        </a>
    </div>

    <div class="bg-white border border-slate-200/60 rounded-2xl p-6 shadow-xs animate-fade-in-up" style="animation-delay:.05s">
        <div class="flex items-center justify-between mb-5">
            <div>
                <h2 class="text-lg font-extrabold text-slate-900 tracking-tight">Inventory by Group</h2>
                <p class="text-xs text-slate-400 font-medium mt-0.5">Available and expiring bags per blood group</p>
            </div>
            <span class="px-3 py-1 rounded-lg bg-emerald-50 border border-emerald-200 text-emerald-600 text-[10px] font-bold uppercase tracking-wider">Live</span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-left text-[10px] uppercase tracking-wider text-slate-400 font-bold border-b border-slate-100">
                        <th class="py-3 pr-4">Blood Group</th>
                        <th class="py-3 pr-4">Status</th>
                        <th class="py-3 px-4 w-56">Available</th>
                        <th class="py-3 px-4 text-center">Expiring Soon</th>
                        <th class="py-3 px-4">Oldest Expiry</th>
                        <th class="py-3 pl-4 text-right">Last Updated</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($stock as $row):
                        $available = (int)($row['available_bags'] ?? 0);
                        $expiringSoon = (int)($row['expiring_soon'] ?? 0);
                        $status = $available <= 0 ? 'Out of stock' : ($available <= 2 ? 'Low stock' : 'In stock');
                        $statusStyle = match ($status) {
                            'Out of stock' => 'bg-rose-50 text-rose-600 border-rose-200',
                            'Low stock'    => 'bg-amber-50 text-amber-600 border-amber-200',
                            default        => 'bg-emerald-50 text-emerald-600 border-emerald-200',
                        };
                    ?>
                        <tr class="border-b border-slate-50 last:border-0 hover:bg-slate-50/50 transition-colors">
                            <td class="py-4 pr-4">
                                <span class="w-9 h-9 rounded-xl <?= hospitalStockBadge($available) ?> border font-black text-xs font-mono items-center justify-center inline-flex">
                                    <?= htmlspecialchars((string)($row['blood_group'] ?? '-')) ?>
                                </span>
                            </td>
                            <td class="py-4 pr-4">
                                <span class="px-2.5 py-1 rounded-lg border text-[10px] font-black uppercase tracking-wider whitespace-nowrap <?= $statusStyle ?>"><?= $status ?></span>
                            </td>
                            <td class="py-4 px-4">
                                <div class="flex items-center gap-3">
                                    <div class="flex-1 max-w-[140px] h-2 rounded-full bg-slate-100 overflow-hidden">
                                        <div class="h-full rounded-full transition-all duration-500 <?= hospitalBarColor($available) ?>" style="width: <?= min(100, max(4, (int)($row['stock_pct'] ?? 0))) ?>%"></div>
                                    </div>
                                    <span class="text-base font-black text-slate-900 tabular-nums min-w-[1.5rem] text-right"><?= $available ?></span>
                                </div>
                            </td>
                            <td class="py-4 px-4 text-center">
                                <span class="inline-flex items-center justify-center min-w-[2rem] px-2 py-0.5 rounded-lg font-black tabular-nums <?= $expiringSoon > 0 ? 'bg-amber-50 text-amber-600 border border-amber-200' : 'text-slate-400' ?>"><?= $expiringSoon ?></span>
                            </td>
                            <td class="py-4 px-4 font-semibold text-slate-600 whitespace-nowrap"><?= !empty($row['oldest_expiry']) ? htmlspecialchars(date('d M Y', strtotime($row['oldest_expiry']))) : '—' ?></td>
                            <td class="py-4 pl-4 text-xs text-slate-400 font-medium whitespace-nowrap text-right"><?= htmlspecialchars((string)($row['last_updated_label'] ?? '—')) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
