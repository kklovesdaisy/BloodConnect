<?php
/**
 * Reusable report filter + export bar.
 * Expects $reportFilterConfig:
 *   [
 *     'action' => string,
 *     'search_name' => string (default 'q'),
 *     'search_placeholder' => string,
 *     'fields' => [
 *        ['type'=>'select','name'=>'...','label'=>'...','options'=>[value=>label]],
 *        ['type'=>'date','name'=>'from','label'=>'From'],
 *     ],
 *   ]
 */
use App\Shared\Helpers\AdminFilters;

$action = $reportFilterConfig['action'] ?? '/BloodConnect/public/admin/reports';
$searchName = $reportFilterConfig['search_name'] ?? 'q';
$searchPlaceholder = $reportFilterConfig['search_placeholder'] ?? 'Search…';
$fields = $reportFilterConfig['fields'] ?? [];

$formNames = array_merge([$searchName], array_column($fields, 'name'));
$pdfUrl = AdminFilters::preserve(['export' => 'pdf', 'page' => null]);
$excelUrl = AdminFilters::preserve(['export' => 'excel', 'page' => null]);
?>

<div class="bg-white border border-slate-200/60 rounded-2xl p-4 sm:p-5 mb-6 shadow-xs">
    <form method="GET" action="<?= htmlspecialchars($action) ?>" class="space-y-4">
        <?php foreach ($_GET as $gk => $gv): ?>
            <?php if (in_array($gk, $formNames, true) || $gk === 'page' || $gk === 'export' || is_array($gv)): continue; endif; ?>
            <input type="hidden" name="<?= htmlspecialchars($gk) ?>" value="<?= htmlspecialchars((string)$gv) ?>">
        <?php endforeach; ?>

        <div class="flex flex-col lg:flex-row lg:items-center gap-3">
            <div class="relative flex-1">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-4 h-4 absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" />
                </svg>
                <input type="text" name="<?= htmlspecialchars($searchName) ?>"
                    value="<?= htmlspecialchars(AdminFilters::strParam($searchName)) ?>"
                    placeholder="<?= htmlspecialchars($searchPlaceholder) ?>"
                    class="w-full h-11 pl-10 pr-4 rounded-xl border border-slate-200 bg-slate-50/50 text-sm font-medium text-slate-700 placeholder:text-slate-400 focus:outline-none focus:ring-2 focus:ring-red-500/30 focus:border-red-300 transition-all">
            </div>

            <div class="flex flex-wrap items-end gap-2.5">
                <?php foreach ($fields as $field): ?>
                    <?php if ($field['type'] === 'select'): ?>
                        <div class="flex flex-col gap-1">
                            <label class="text-[10px] font-bold uppercase tracking-widest text-slate-400"><?= htmlspecialchars($field['label']) ?></label>
                            <select name="<?= htmlspecialchars($field['name']) ?>"
                                class="h-11 rounded-xl border border-slate-200 bg-slate-50/50 text-sm font-semibold text-slate-700 px-3 pr-8 focus:outline-none focus:ring-2 focus:ring-red-500/30 focus:border-red-300 transition-all appearance-none">
                                <option value="">All</option>
                                <?php foreach ($field['options'] as $value => $label): ?>
                                    <option value="<?= htmlspecialchars((string)$value) ?>"
                                        <?= AdminFilters::strParam($field['name']) === (string)$value ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($label) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    <?php elseif ($field['type'] === 'date'): ?>
                        <div class="flex flex-col gap-1">
                            <label class="text-[10px] font-bold uppercase tracking-widest text-slate-400"><?= htmlspecialchars($field['label']) ?></label>
                            <input type="date" name="<?= htmlspecialchars($field['name']) ?>"
                                value="<?= htmlspecialchars(AdminFilters::strParam($field['name'])) ?>"
                                class="h-11 rounded-xl border border-slate-200 bg-slate-50/50 text-sm font-semibold text-slate-700 px-3 focus:outline-none focus:ring-2 focus:ring-red-500/30 focus:border-red-300 transition-all">
                        </div>
                    <?php endif; ?>
                <?php endforeach; ?>

                <button type="submit"
                    class="h-11 inline-flex items-center gap-2 px-4 rounded-xl bg-slate-900 text-white text-xs font-bold hover:bg-slate-800 shadow-sm transition-all active:scale-95">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.2" stroke="currentColor" class="w-3.5 h-3.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 3c2.755 0 5.455.232 8.083.678.533.09.917.556.917 1.096v1.044a2.25 2.25 0 01-.659 1.591l-5.432 5.432a2.25 2.25 0 00-.659 1.591v2.927a2.25 2.25 0 01-1.244 2.013L9.75 21v-6.568a2.25 2.25 0 00-.659-1.591L3.659 7.409A2.25 2.25 0 013 5.818V4.774c0-.54.384-1.006.917-1.096A48.32 48.32 0 0112 3z" />
                    </svg>
                    Search
                </button>
                <a href="<?= htmlspecialchars($action) ?>"
                    class="h-11 inline-flex items-center gap-2 px-4 rounded-xl border border-slate-200 bg-white text-slate-600 text-xs font-bold hover:bg-slate-50 transition-all active:scale-95">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.2" stroke="currentColor" class="w-3.5 h-3.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                    Clear
                </a>
            </div>
        </div>

        <div class="flex flex-wrap items-center justify-between gap-3 border-t border-slate-100 pt-4">
            <p class="text-xs font-semibold text-slate-400">Export the current filtered result set below.</p>
            <div class="flex items-center gap-2.5">
                <a href="<?= htmlspecialchars($pdfUrl) ?>"
                    class="h-10 inline-flex items-center gap-2 px-4 rounded-xl bg-red-600 text-white text-xs font-bold hover:bg-red-700 shadow-sm shadow-red-600/20 transition-all active:scale-95">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-4 h-4">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m2.25 12l3 3m0 0l3-3m-3 3v-6m-1.5-9H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
                    </svg>
                    Export PDF
                </a>
                <a href="<?= htmlspecialchars($excelUrl) ?>"
                    class="h-10 inline-flex items-center gap-2 px-4 rounded-xl bg-emerald-600 text-white text-xs font-bold hover:bg-emerald-700 shadow-sm shadow-emerald-600/20 transition-all active:scale-95">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-4 h-4">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3" />
                    </svg>
                    Export Excel
                </a>
            </div>
        </div>
    </form>
</div>
