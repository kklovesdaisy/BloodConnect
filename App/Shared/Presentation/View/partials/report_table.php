<?php
/**
 * Shared report data table + totals + meta footer.
 * Expects: $reportData (rows, totals), $columns, $pillColumns (0-based indexes
 * rendered as colored pills).
 */
$reportRows = $reportData['rows'] ?? [];
$reportTotals = $reportData['totals'] ?? [];
$reportFilters = $reportData['filters'] ?? [];
$pillColumns = $pillColumns ?? [];

function reportPillClass(string $value): string
{
    return match (strtolower($value)) {
        'pending' => 'bg-amber-50 text-amber-600 border-amber-200/60',
        'partial' => 'bg-amber-50 text-amber-600 border-amber-200/60',
        'assigned' => 'bg-indigo-50 text-indigo-600 border-indigo-200/60',
        'accepted' => 'bg-emerald-50 text-emerald-600 border-emerald-200/60',
        'completed' => 'bg-blue-50 text-blue-600 border-blue-200/60',
        'cancelled', 'canceled' => 'bg-red-50 text-red-600 border-red-200/60',
        'declined' => 'bg-rose-50 text-rose-600 border-rose-200/60',
        'expired' => 'bg-slate-100 text-slate-500 border-slate-200/60',
        'active' => 'bg-emerald-50 text-emerald-600 border-emerald-200/60',
        'inactive' => 'bg-slate-100 text-slate-500 border-slate-200/60',
        'available' => 'bg-green-50 text-green-700 border-green-200/60',
        'unavailable' => 'bg-slate-100 text-slate-500 border-slate-200/60',
        'critical' => 'bg-red-50 text-red-600 border-red-200/60',
        'urgent' => 'bg-amber-50 text-amber-600 border-amber-200/60',
        'standard' => 'bg-slate-100 text-slate-600 border-slate-200/60',
        default => 'bg-slate-50 text-slate-600 border-slate-200/60',
    };
}
?>

<div class="bg-white border border-slate-200/60 rounded-2xl shadow-xs overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-sm min-w-[720px]">
            <thead>
                <tr class="bg-[#ce2424] text-white">
                    <?php foreach ($columns as $i => $column): ?>
                        <th class="text-left px-5 py-3.5 text-[10px] font-extrabold uppercase tracking-widest whitespace-nowrap"><?= htmlspecialchars($column) ?></th>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                <?php if (empty($reportRows)): ?>
                    <tr>
                        <td colspan="<?= count($columns) ?>" class="px-5 py-14 text-center text-slate-400 font-medium">
                            No records match the current filters.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($reportRows as $row): ?>
                        <tr class="hover:bg-red-50/40 transition-colors">
                            <?php foreach ($columns as $i => $column): ?>
                                <?php $value = (string)(is_array($row) ? ($row[$i] ?? '') : $row); ?>
                                <td class="px-5 py-3 text-slate-700 font-medium whitespace-nowrap">
                                    <?php if (in_array($i, $pillColumns, true)): ?>
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-lg border text-[10px] font-extrabold uppercase tracking-widest <?= reportPillClass($value) ?>">
                                            <?= htmlspecialchars($value) ?>
                                        </span>
                                    <?php else: ?>
                                        <?= htmlspecialchars($value) ?>
                                    <?php endif; ?>
                                </td>
                            <?php endforeach; ?>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php if (!empty($reportTotals)): ?>
    <div class="mt-6 grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-3">
        <?php foreach ($reportTotals as $label => $value): ?>
            <div class="bg-white border border-slate-200/60 rounded-2xl px-4 py-3.5 shadow-xs flex items-center justify-between gap-2">
                <span class="text-[10px] font-bold uppercase tracking-widest text-slate-400"><?= htmlspecialchars($label) ?></span>
                <span class="text-lg font-extrabold text-slate-900 mono"><?= htmlspecialchars((string)$value) ?></span>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<div class="mt-6 flex flex-wrap items-center justify-between gap-4 bg-[#fdf6f6] text-slate-700 rounded-2xl px-5 py-4 shadow-xs border border-red-100">
    <div class="flex items-center gap-3">
        <span class="w-2 h-2 rounded-full bg-red-500 shadow-[0_0_10px_rgba(239,68,68,0.4)] animate-pulse"></span>
        <span class="text-xs font-bold text-slate-600">
            <?php
            $from = $reportFilters['from'] ?? '';
            $to = $reportFilters['to'] ?? '';
            if ($from !== '' && $to !== '') {
                echo 'Report Period: ' . date('d-M-Y', strtotime($from)) . ' to ' . date('d-M-Y', strtotime($to));
            } elseif ($from !== '') {
                echo 'Report Period: from ' . date('d-M-Y', strtotime($from));
            } elseif ($to !== '') {
                echo 'Report Period: up to ' . date('d-M-Y', strtotime($to));
            } else {
                echo 'Report Period: All records';
            }
            ?>
        </span>
    </div>
    <div class="flex items-center gap-4 text-[11px] font-semibold text-slate-500">
        <span>Generated By: <span class="text-slate-900 font-bold">Administrator</span></span>
        <span>Date: <span class="text-slate-900 font-bold"><?= date('d-M-Y') ?></span></span>
        <span>Time: <span class="text-slate-900 font-bold"><?= date('h:i A') ?></span></span>
    </div>
</div>
