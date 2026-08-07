<?php
/**
 * Reusable admin pagination bar.
 * Expects $adminPagination array from AdminFilters::paginate().
 */
use App\Shared\Helpers\AdminFilters;

if (empty($adminPagination)) {
    return;
}

$nav = AdminFilters::paginationHtml($adminPagination);
if ($nav === '' && $adminPagination['total'] === 0) {
    return;
}
?>

<div class="mt-5 px-1">
    <?= $nav === '' ? '<div class="flex items-center justify-between"><p class="text-xs font-semibold text-slate-400">Showing <span class="text-slate-700 font-bold">' . $adminPagination['start'] . '–' . $adminPagination['end'] . '</span> of <span class="text-slate-700 font-bold">' . $adminPagination['total'] . '</span> records</p></div>' : $nav ?>
</div>
