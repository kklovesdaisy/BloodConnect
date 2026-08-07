<?php
/**
 * Reusable admin search + filter bar.
 * Expects $adminFilterConfig:
 *   [
 *     'action' => string,
 *     'search_name' => string (default 'q'),
 *     'search_placeholder' => string,
 *     'fields' => [
 *        ['type'=>'select','name'=>'...','label'=>'...','options'=>[value=>label]],
 *        ['type'=>'date','name'=>'from','label'=>'From'],
 *        ['type'=>'date','name'=>'to','label'=>'To'],
 *     ],
 *   ]
 */
use App\Shared\Helpers\AdminFilters;

$action = $adminFilterConfig['action'] ?? '/BloodConnect/public/admin';
$searchName = $adminFilterConfig['search_name'] ?? 'q';
$searchPlaceholder = $adminFilterConfig['search_placeholder'] ?? 'Search…';
$fields = $adminFilterConfig['fields'] ?? [];

$formNames = array_merge([$searchName], array_column($fields, 'name'));
?>

<div class="bg-white border border-slate-200/60 rounded-2xl p-4 sm:p-5 mb-6 shadow-xs">
    <form method="GET" action="<?= htmlspecialchars($action) ?>" class="space-y-4" id="adminFilterForm">
        <?php foreach ($_GET as $gk => $gv): ?>
            <?php if (in_array($gk, $formNames, true) || $gk === 'page' || is_array($gv)): continue; endif; ?>
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
                    class="h-11 inline-flex items-center gap-2 px-4 rounded-xl bg-red-600 text-white text-xs font-bold hover:bg-red-700 shadow-sm shadow-red-600/20 transition-all active:scale-95">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.2" stroke="currentColor" class="w-3.5 h-3.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 3c2.755 0 5.455.232 8.083.678.533.09.917.556.917 1.096v1.044a2.25 2.25 0 01-.659 1.591l-5.432 5.432a2.25 2.25 0 00-.659 1.591v2.927a2.25 2.25 0 01-1.244 2.013L9.75 21v-6.568a2.25 2.25 0 00-.659-1.591L3.659 7.409A2.25 2.25 0 013 5.818V4.774c0-.54.384-1.006.917-1.096A48.32 48.32 0 0112 3z" />
                    </svg>
                    Apply
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
    </form>
</div>
