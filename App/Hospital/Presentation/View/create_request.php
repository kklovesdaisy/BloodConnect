<?php
$stock = $stock ?? [];
$stockByGroup = [];
foreach ($stock as $row) {
    $stockByGroup[$row['blood_group']] = (int)($row['available_bags'] ?? 0);
}
?>
<style>
    @keyframes fadeInUp { from { opacity: 0; transform: translateY(12px); } to { opacity: 1; transform: translateY(0); } }
    .animate-fade-in-up { animation: fadeInUp .4s cubic-bezier(.16,1,.3,1) forwards; }
</style>

<div class="max-w-3xl mx-auto">
    <div class="bg-white border border-slate-200/60 rounded-2xl p-6 mb-6 shadow-xs animate-fade-in-up">
        <div class="flex items-center gap-4">
            <div class="w-12 h-12 bg-[#ce2424] text-white rounded-2xl flex items-center justify-center shadow-lg shadow-red-600/10 shrink-0">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v6m3-3H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
            </div>
            <div>
                <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight leading-none">Request Blood Stock</h1>
                <p class="text-xs sm:text-sm text-slate-400 font-medium mt-1.5">Your request will be reviewed by the administrator. Once approved, available bags are allocated immediately and any remainder is provided automatically as stock arrives.</p>
            </div>
        </div>
    </div>

    <form method="POST" action="/BloodConnect/public/hospital/requests/create"
        class="bg-white border border-slate-200/60 rounded-2xl p-6 sm:p-8 shadow-xs animate-fade-in-up" style="animation-delay:.05s">

        <div class="space-y-5">
            <div>
                <label class="block text-sm font-bold text-slate-700 mb-1.5">Blood Group</label>
                <div class="grid grid-cols-4 gap-2.5">
                    <?php foreach (['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'] as $group): ?>
                        <label class="cursor-pointer">
                            <input type="radio" name="blood_group" value="<?= $group ?>" class="peer sr-only" <?= ($group === 'O+') ? 'checked' : '' ?>>
                            <span class="block text-center border border-slate-200 rounded-xl py-3 font-black font-mono text-sm text-slate-700 peer-checked:bg-[#ce2424] peer-checked:text-white peer-checked:border-[#ce2424] hover:border-red-200 transition-all">
                                <?= $group ?>
                            </span>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <div>
                <label for="quantity" class="block text-sm font-bold text-slate-700 mb-1.5">Number of Bags (max 100)</label>
                <input type="number" id="quantity" name="quantity" min="1" max="100" required value="1"
                    class="w-full border border-slate-200 rounded-xl px-4 py-3 text-sm font-semibold text-slate-800 focus:outline-none focus:ring-2 focus:ring-red-200 focus:border-[#ce2424]">
                <p class="text-xs text-slate-400 font-medium mt-1.5">One bag is equivalent to one unit of blood.</p>
            </div>

            <div class="bg-slate-50 border border-slate-100 rounded-xl p-4">
                <span class="block text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-2">Available in Central Stock</span>
                <div class="flex flex-wrap gap-2">
                    <?php foreach ($stockByGroup as $group => $bags): ?>
                        <span class="px-2.5 py-1 rounded-lg border text-[11px] font-black font-mono <?= $bags <= 0 ? 'bg-rose-50 text-rose-600 border-rose-200' : ($bags <= 2 ? 'bg-amber-50 text-amber-600 border-amber-200' : 'bg-emerald-50 text-emerald-600 border-emerald-200') ?>">
                            <?= htmlspecialchars((string)$group) ?> · <?= (int)$bags ?>
                        </span>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <button type="submit"
            class="mt-7 w-full inline-flex items-center justify-center gap-2 px-5 py-3 bg-[#ce2424] text-white text-sm font-bold rounded-xl hover:bg-[#b81f1f] transition-all active:scale-95 shadow-md shadow-red-600/20">
            Submit Stock Request
        </button>
    </form>
</div>
