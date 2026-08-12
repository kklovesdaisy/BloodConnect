<?php
$requests = $requests ?? [];
$message = $message ?? '';
$status = $status ?? '';

if (!function_exists('hospitalRequestStatusStyle')) {
    function hospitalRequestStatusStyle(string $status): string
    {
        return match ($status) {
            'COMPLETED' => 'bg-emerald-50 text-emerald-600 border-emerald-200',
            'PARTIAL'   => 'bg-amber-50 text-amber-600 border-amber-200',
            'PENDING'   => 'bg-red-50 text-red-600 border-red-200',
            'DECLINED'  => 'bg-slate-100 text-slate-500 border-slate-200',
            default     => 'bg-slate-100 text-slate-600 border-slate-200',
        };
    }
}
?>
<style>
    @keyframes fadeInUp { from { opacity: 0; transform: translateY(12px); } to { opacity: 1; transform: translateY(0); } }
    .animate-fade-in-up { animation: fadeInUp .4s cubic-bezier(.16,1,.3,1) forwards; }
</style>

<div class="max-w-7xl mx-auto space-y-6">

    <?php if ($message !== ''): ?>
        <div class="rounded-2xl px-5 py-4 text-sm font-bold border <?= $status === 'error' ? 'bg-rose-50 text-rose-600 border-rose-200' : 'bg-emerald-50 text-emerald-600 border-emerald-200' ?> animate-fade-in-up">
            <?= htmlspecialchars($message) ?>
        </div>
    <?php endif; ?>

    <div class="bg-white border border-slate-200/60 rounded-2xl p-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 shadow-xs">
        <div class="flex items-center gap-4">
            <div class="w-12 h-12 bg-[#ce2424] text-white rounded-2xl flex items-center justify-center shadow-lg shadow-red-600/10 shrink-0">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25zM6.75 12h.008v.008H6.75V12zm0 3h.008v.008H6.75V15zm0 3h.008v.008H6.75V18z" /></svg>
            </div>
            <div>
                <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight leading-none">My Stock Requests</h1>
                <p class="text-xs sm:text-sm text-slate-400 font-medium mt-1.5">Track every bag requested, allocated and still owed.</p>
            </div>
        </div>
        <a href="/BloodConnect/public/hospital/requests/create"
            class="inline-flex items-center gap-2 px-5 py-2.5 bg-[#ce2424] text-white text-sm font-bold rounded-xl hover:bg-[#b81f1f] transition-all active:scale-95 shadow-md shadow-red-600/20 self-start sm:self-auto">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-4 h-4"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v6m3-3H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
            Request Stock
        </a>
    </div>

    <?php if (empty($requests)): ?>
        <div class="bg-white border border-slate-200/60 rounded-2xl p-12 text-center text-slate-400 font-medium animate-fade-in-up">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-8 h-8 text-slate-300 mx-auto mb-2"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 13.5h3.86a2.25 2.25 0 012.008 1.24l.885 1.77a2.25 2.25 0 002.007 1.24h1.98a2.25 2.25 0 002.007-1.24l.885-1.77a2.25 2.25 0 012.007-1.24h3.86m-18 0h18" /></svg>
            No stock requests yet.
        </div>
    <?php else: ?>
        <div class="bg-white border border-slate-200/60 rounded-2xl p-6 shadow-xs animate-fade-in-up" style="animation-delay:.05s">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-left text-[10px] uppercase tracking-wider text-slate-400 font-bold border-b border-slate-100">
                            <th class="py-2.5 pr-4">Code</th>
                            <th class="py-2.5 pr-4">Blood</th>
                            <th class="py-2.5 pr-4">Requested</th>
                            <th class="py-2.5 pr-4">Allocated</th>
                            <th class="py-2.5 pr-4">Pending</th>
                            <th class="py-2.5 pr-4">Status</th>
                            <th class="py-2.5">Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($requests as $request):
                            $statusValue = $request['status'] ?? '';
                        ?>
                            <tr class="border-b border-slate-50 hover:bg-slate-50/50">
                                <td class="py-3.5 pr-4 font-mono text-xs font-bold text-slate-700"><?= htmlspecialchars((string)($request['request_code'] ?? '-')) ?></td>
                                <td class="py-3.5 pr-4 font-mono font-black text-slate-800"><?= htmlspecialchars((string)($request['blood_group'] ?? '-')) ?></td>
                                <td class="py-3.5 pr-4"><?= (int)($request['quantity_requested'] ?? 0) ?></td>
                                <td class="py-3.5 pr-4 text-emerald-600 font-bold"><?= (int)($request['quantity_allocated'] ?? 0) ?></td>
                                <td class="py-3.5 pr-4">
                                    <?php if ($statusValue === 'PENDING'): ?>
                                        <span class="text-[11px] font-bold text-red-500">Awaiting approval</span>
                                    <?php elseif ($statusValue === 'DECLINED'): ?>
                                        <span class="text-[11px] font-semibold text-slate-400">—</span>
                                    <?php else: ?>
                                        <span class="<?= ((int)($request['quantity_pending'] ?? 0) > 0) ? 'text-[#ce2424] font-bold' : 'text-slate-400' ?>"><?= (int)($request['quantity_pending'] ?? 0) ?></span>
                                    <?php endif; ?>
                                </td>
                                <td class="py-3.5 pr-4">
                                    <span class="px-2.5 py-1 rounded-lg border text-[10px] font-black uppercase tracking-wider <?= hospitalRequestStatusStyle((string)$statusValue) ?>">
                                        <?= htmlspecialchars((string)$statusValue) ?>
                                    </span>
                                </td>
                                <td class="py-3.5 text-xs text-slate-400 font-medium"><?= date('d M Y', strtotime($request['created_at'] ?? 'now')) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>
</div>
