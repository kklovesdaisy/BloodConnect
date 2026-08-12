<?php
$requests = $requests ?? [];
$pendingTotal = (int)($pending_total ?? 0);
?>
<style>
    @keyframes fadeInUp { from { opacity: 0; transform: translateY(12px); } to { opacity: 1; transform: translateY(0); } }
    .animate-fade-in-up { animation: fadeInUp .4s cubic-bezier(.16,1,.3,1) forwards; }
</style>

<div class="max-w-7xl mx-auto space-y-6">

    <div class="bg-white border border-slate-200/60 rounded-2xl p-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 shadow-xs">
        <div class="flex items-center gap-4">
            <div class="w-12 h-12 bg-[#ce2424] text-white rounded-2xl flex items-center justify-center shadow-lg shadow-red-600/10 shrink-0">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
            </div>
            <div>
                <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight leading-none">More to Be Provided</h1>
                <p class="text-xs sm:text-sm text-slate-400 font-medium mt-1.5">These shortfalls are auto-fulfilled oldest-first as new blood arrives.</p>
            </div>
        </div>
        <div class="bg-[#111625] rounded-xl px-4 py-2.5 flex items-center gap-4 self-start sm:self-auto shadow-md">
            <span class="text-white font-black text-xl font-mono leading-none"><?= $pendingTotal ?></span>
            <span class="text-[9px] uppercase tracking-wider font-bold text-slate-400 block leading-tight">bag(s)<br>still owed</span>
        </div>
    </div>

    <?php if (empty($requests)): ?>
        <div class="bg-white border border-slate-200/60 rounded-2xl p-12 text-center text-slate-400 font-medium animate-fade-in-up">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-8 h-8 text-slate-300 mx-auto mb-2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
            Great news — you have no outstanding shortfalls. All your requests are fully fulfilled.
        </div>
    <?php else: ?>
        <div class="space-y-3">
            <?php foreach ($requests as $index => $request):
                $shortfall = (int)($request['quantity_pending'] ?? 0);
                $total = (int)($request['quantity_requested'] ?? 1);
                $allocated = (int)($request['quantity_allocated'] ?? 0);
                $pct = $total > 0 ? min(100, (int)round(($allocated / $total) * 100)) : 0;
            ?>
                <div class="bg-white border border-slate-200/70 rounded-2xl p-5 animate-fade-in-up shadow-2xs" style="animation-delay: <?= $index * 0.04 ?>s;">
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                        <div class="flex items-center gap-4 min-w-0">
                            <div class="w-11 h-11 rounded-xl bg-red-50 border border-red-100 text-red-500 flex items-center justify-center shrink-0">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-5 h-5"><path d="M12 2.5C12 2.5 5 9.5 5 14.5C5 18.37 8.13 21.5 12 21.5C15.87 21.5 19 18.37 19 14.5C19 9.5 12 2.5 12 2.5Z" /></svg>
                            </div>
                            <div class="truncate">
                                <span class="text-[10px] font-bold uppercase text-slate-400 tracking-wider font-mono block"><?= htmlspecialchars((string)($request['request_code'] ?? '-')) ?></span>
                                <span class="font-bold text-slate-800 text-sm tracking-tight truncate block mt-0.5">
                                    <?= htmlspecialchars((string)($request['blood_group'] ?? '-')) ?> · <?= $shortfall ?> bag(s) still owed
                                </span>
                            </div>
                        </div>

                        <div class="flex items-center gap-4 sm:min-w-[280px]">
                            <div class="flex-1 h-2 rounded-full bg-slate-100 overflow-hidden">
                                <div class="h-full rounded-full bg-emerald-500 transition-all" style="width: <?= $pct ?>%"></div>
                            </div>
                            <span class="text-xs font-bold text-slate-500 whitespace-nowrap"><?= $pct ?>% fulfilled</span>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
