<div class="max-w-7xl mx-auto p-4 sm:p-6 lg:p-8 min-h-screen" id="patientRequestsContainer">

    <?php
    $requests = $requests ?? [];
    $message = $message ?? '';
    $status = $status ?? '';
    $filter = $filter ?? '';
    $pageTitle = $pageTitle ?? 'My Requests';
    $emptyMessage = $emptyMessage ?? 'You have no requests recorded.';

    function getSeverityClass($level)
    {
        return match (strtoupper((string) $level)) {
            'CRITICAL' => 'bg-red-50 text-red-600 border-red-200/50',
            'URGENT' => 'bg-orange-50 text-orange-600 border-orange-200/50',
            default => 'bg-slate-100 text-slate-600 border-slate-200/50',
        };
    }

    function getFulfillmentClass($status)
    {
        return match (strtolower((string) $status)) {
            'pending' => 'bg-amber-50 text-amber-600 border-amber-200/50',
            'accepted' => 'bg-emerald-50 text-emerald-600 border-emerald-200/50',
            'completed' => 'bg-blue-50 text-blue-600 border-blue-200/50',
            'cancelled', 'canceled' => 'bg-red-50 text-red-600 border-red-200/50',
            'declined' => 'bg-rose-50 text-rose-600 border-rose-200/50',
            'expired' => 'bg-slate-100 text-slate-500 border-slate-200/50',
            default => 'bg-slate-50 text-slate-600 border-slate-200/50',
        };
    }

    function getBloodTypeClass($type)
    {
        $type = strtolower((string) $type);
        if (strpos($type, 'negative') !== false || strpos($type, '-') !== false) {
            return 'text-rose-600 bg-rose-50 border-rose-200/40';
        }
        return 'text-emerald-600 bg-emerald-50 border-emerald-200/40';
    }

    function patientStatusLabel($status)
    {
        $s = strtolower(trim((string) $status));
        if (in_array($s, ['pending', 'assigned', 'declined'])) {
            return 'Pending';
        }
        return $status;
    }

    // Safely calculate active/pending request count for badge display
    $activeCount = 0;
    if (!empty($requests)) {
        foreach ($requests as $req) {
            $status = strtolower($req['status'] ?? $req['fulfillment_status'] ?? 'pending');
            if ($status === 'pending' || $status === 'in progress') {
                $activeCount++;
            }
        }
    }
    ?>

    <div class="relative bg-white border border-slate-200/60 rounded-2xl p-6 mb-8 shadow-xs flex flex-col sm:flex-row sm:items-center sm:justify-between gap-6">
        <div class="flex items-center gap-4">
            <div class="w-12 h-12 bg-[#ce2424] text-white rounded-2xl flex items-center justify-center shadow-lg shadow-red-600/10 shrink-0">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-6 h-6 text-white">
                    <path d="M12 2.69l5.66 5.66a8 8 0 1 1-11.31 0z" />
                </svg>
            </div>
            <div>
                <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight leading-none"><?= htmlspecialchars($pageTitle) ?></h1>
                <p class="text-xs sm:text-sm text-slate-400 font-medium mt-1.5">
                    <?= $filter === 'cancelled' ? 'Requests that were cancelled by you.' : 'Direct system registry controls for live incoming hospital requests.' ?>
                </p>
            </div>
        </div>

        <div class="bg-[#111625] rounded-xl px-4 py-2.5 flex items-center justify-between gap-6 self-start sm:self-auto shadow-md">
            <div>
                <span class="text-[9px] uppercase tracking-wider font-bold text-slate-400 block">Visible Requests</span>
                <div class="flex items-baseline gap-1 mt-0.5">
                    <span class="text-white font-black text-lg font-mono leading-none"><?= count($requests) ?></span>
                    <span class="text-[10px] text-slate-500 font-medium">shown</span>
                </div>
            </div>
            <div class="w-2 h-2 rounded-full bg-rose-500 shadow-xs shadow-rose-500/50 animate-pulse shrink-0"></div>
        </div>
    </div>

    <?php if (!empty($message)): ?>
        <div class="mb-5 p-3.5 rounded-xl flex items-center gap-3 border transition-all duration-300 <?= $status === 'success' ? 'bg-emerald-50 border-emerald-200 text-emerald-700' : 'bg-rose-50 border-rose-200 text-rose-700' ?>">
            <i class="fa-solid <?= $status === 'success' ? 'fa-circle-check' : 'fa-circle-exclamation' ?> text-base"></i>
            <span class="text-xs font-semibold"><?= htmlspecialchars($message) ?></span>
        </div>
    <?php endif; ?>

    <div class="space-y-3">
        <?php if (empty($requests)): ?>
            <div class="bg-white border border-slate-200/70 rounded-2xl p-12 text-center text-slate-400 font-medium">
                <?= htmlspecialchars($emptyMessage) ?>
            </div>
        <?php else: ?>

            <div class="hidden lg:block space-y-3">
                <?php foreach ($requests as $index => $req):
                    $rid = $req['request_id'] ?? $req['id'] ?? $req['request_code'] ?? 0;
                    $hospital = $req['hospital_name'] ?? $req['location'] ?? '-';
                    $patient = $req['patient_name'] ?? $req['requester'] ?? '-';
                    $blood = $req['blood_group_needed'] ?? $req['blood_type'] ?? '-';
                    $units = $req['unit'] ?? $req['units'] ?? $req['units_requested'] ?? '-';
                    $severity = $req['urgency'] ?? $req['priority'] ?? 'Routine';
                    $status = $req['status'] ?? $req['fulfillment_status'] ?? 'pending';
                    $required_date = $req['created_at'] ?? $req['required_date'] ?? $req['deadline'] ?? date('Y-m-d');
                ?>
                    <div class="bg-white border border-slate-200/70 rounded-2xl p-4 grid grid-cols-12 items-center gap-4 transition-all duration-300 transform shadow-2xs" style="animation-delay: <?= $index * 0.03 ?>s;">

                        <div class="col-span-3 flex items-center gap-4 min-w-0">
                            <div class="relative shrink-0">
                                <div class="w-11 h-11 rounded-xl bg-red-50 border border-red-100 text-red-500 flex items-center justify-center">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-5 h-5">
                                        <path d="M12 2.69l5.66 5.66a8 8 0 1 1-11.31 0z" />
                                    </svg>
                                </div>
                            </div>
                            <div class="truncate">
                                <span class="text-[10px] font-bold uppercase text-slate-400 tracking-wider mono block">REQ___<?= str_pad($rid, 4, '0', STR_PAD_LEFT) ?></span>
                                <span class="font-bold text-slate-800 text-sm tracking-tight truncate block mt-0.5"><?= htmlspecialchars($hospital) ?></span>
                                <span class="text-xs text-slate-400 font-medium block truncate">Pt: <?= htmlspecialchars($patient) ?></span>
                                <?php
                                $loc = array_filter([$req['township'] ?? '', $req['state_region'] ?? '']);
                                if (!empty($loc)): ?>
                                    <span class="text-[10px] text-slate-400 font-medium block truncate mt-0.5"><?= htmlspecialchars(implode(', ', $loc)) ?></span>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="col-span-2 min-w-0 pl-2">
                            <span class="text-slate-800 font-extrabold tracking-tight text-sm block truncate"><?= htmlspecialchars($units) ?> Units</span>
                            <span class="text-[10px] text-slate-400 font-bold tracking-wider uppercase block mt-0.5">Volume Demand</span>
                        </div>

                        <div class="col-span-2 flex justify-start pl-2">
                            <span class="inline-block px-3 py-1 text-[9px] font-extrabold rounded-lg uppercase tracking-widest border shadow-3xs <?= getSeverityClass($severity) ?>">
                                <?= htmlspecialchars(strtoupper((string)$severity)) ?>
                            </span>
                        </div>

                        <div class="col-span-2 flex flex-col items-start gap-1 pl-2">
                            <span class="inline-block px-3 py-1 text-[9px] font-extrabold rounded-lg uppercase tracking-widest border shadow-3xs <?= getFulfillmentClass(patientStatusLabel($status)) ?>">
                                <?= htmlspecialchars(ucwords((string)patientStatusLabel($status))) ?>
                            </span>
                            <?php
                            $expiryDate = $req['expiry_date'] ?? '';
                            $isPendingRow = strtolower((string)$status) === 'pending';
                            $isExpiredRow = strtolower((string)$status) === 'expired';
                            if (($isPendingRow || $isExpiredRow) && $expiryDate !== ''):
                                $expiryTs = strtotime($expiryDate);
                                $expiryDiff = $expiryTs - time();
                                if ($isExpiredRow || $expiryDiff <= 0):
                            ?>
                                <span class="text-[10px] font-bold text-slate-500">Expired <?= date('M d', $expiryTs) ?></span>
                            <?php else:
                                $edays = floor($expiryDiff / 86400);
                                $ehours = floor(($expiryDiff % 86400) / 3600);
                            ?>
                                <span class="text-[10px] font-medium text-amber-500"><i class="fa-regular fa-clock"></i> Expires in <?= $edays > 0 ? $edays . 'd ' . $ehours . 'h' : $ehours . 'h' ?></span>
                            <?php endif; endif; ?>
                        </div>

                        <div class="col-span-1 flex justify-center">
                            <span class="inline-flex items-center justify-center px-2.5 py-1 rounded-lg border font-black text-xs shadow-3xs font-mono <?= getBloodTypeClass($blood) ?>">
                                <?= htmlspecialchars($blood) ?>
                            </span>
                        </div>

                        <div class="col-span-1 text-right text-slate-500 pr-2">
                            <span class="text-xs font-semibold block truncate"><?= date('M d, Y', strtotime($required_date)) ?></span>
                            <span class="text-[10px] text-slate-400 block mt-0.5 mono"><?= date('H:i', strtotime($required_date)) ?></span>
                        </div>

                        <div class="col-span-1 flex items-center justify-end pr-2">
                            <a href="/BloodConnect/public/patient/my-request/view?id=<?= (int)$rid ?>"
                                title="Inspect Ticket"
                                class="p-2 text-red-500 hover:text-white hover:bg-red-600 border border-transparent hover:border-red-600 rounded-xl transition-all active:scale-90 font-semibold">
                                View
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="block lg:hidden space-y-3.5">
                <?php foreach ($requests as $index => $req):
                    $rid = $req['request_id'] ?? $req['id'] ?? $req['request_code'] ?? 0;
                    $hospital = $req['hospital_name'] ?? $req['location'] ?? '-';
                    $patient = $req['patient_name'] ?? $req['requester'] ?? '-';
                    $blood = $req['blood_group_needed'] ?? $req['blood_type'] ?? '-';
                    $units = $req['unit'] ?? $req['units'] ?? $req['units_requested'] ?? '-';
                    $severity = $req['urgency'] ?? $req['priority'] ?? 'Routine';
                    $status = $req['status'] ?? $req['fulfillment_status'] ?? 'pending';
                    $required_date = $req['created_at'] ?? $req['required_date'] ?? $req['deadline'] ?? date('Y-m-d');
                ?>
                    <div class="bg-white border border-slate-200 rounded-2xl p-5 space-y-4 shadow-3xs transform transition-all duration-300 hover:shadow-2xs active:scale-[0.99]" style="animation-delay: <?= $index * 0.03 ?>s;">

                        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                            <div class="flex items-center gap-2.5">
                                <span class="px-2 py-0.5 text-[9px] font-extrabold rounded-md uppercase tracking-wider border <?= getSeverityClass($severity) ?>">
                                    <?= htmlspecialchars(strtoupper((string)$severity)) ?>
                                </span>
                                <span class="inline-flex items-center gap-1 text-[10px] font-bold text-slate-400 mono">
                                    REQ_ID: <?= htmlspecialchars($rid) ?>
                                </span>
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="text-[10px] text-slate-400 mono"><?= date('Y-m-d', strtotime($required_date)) ?></span>
                            </div>
                        </div>

                        <div class="flex items-start justify-between gap-4">
                            <div class="space-y-1 truncate">
                                <h4 class="font-extrabold text-slate-900 text-base tracking-tight truncate leading-tight"><?= htmlspecialchars($hospital) ?></h4>
                                <p class="text-xs font-medium text-slate-666 truncate">Patient: <?= htmlspecialchars($patient) ?></p>
                                <?php
                                $loc = array_filter([$req['township'] ?? '', $req['state_region'] ?? '']);
                                if (!empty($loc)): ?>
                                    <p class="text-[11px] text-slate-400 font-medium truncate"><?= htmlspecialchars(implode(', ', $loc)) ?></p>
                                <?php endif; ?>
                                <p class="text-xs text-slate-400 font-bold pt-1"><?= htmlspecialchars($units) ?> Units Required</p>
                            </div>
                            <div class="w-11 h-11 rounded-xl border font-black text-sm flex items-center justify-center font-mono shrink-0 <?= getBloodTypeClass($blood) ?>">
                                <?= htmlspecialchars($blood) ?>
                            </div>
                        </div>

                        <div class="flex items-center justify-between gap-2 pt-1">
                            <div class="flex flex-col gap-1">
                                <span class="inline-block px-2.5 py-1 text-[9px] font-extrabold rounded-md uppercase tracking-widest border <?= getFulfillmentClass(patientStatusLabel($status)) ?>">
                                    <?= htmlspecialchars(ucwords((string)patientStatusLabel($status))) ?>
                                </span>
                                <?php
                                $mobileExpiry = $req['expiry_date'] ?? '';
                                $mobileExpired = strtolower((string)$status) === 'expired';
                                $mobilePending = strtolower((string)$status) === 'pending';
                                if (($mobilePending || $mobileExpired) && $mobileExpiry !== ''):
                                    $mobileExpiryTs = strtotime($mobileExpiry);
                                    $mobileExpiryDiff = $mobileExpiryTs - time();
                                    if ($mobileExpired || $mobileExpiryDiff <= 0):
                                ?>
                                    <span class="text-[10px] font-bold text-slate-500">Expired <?= date('M d', $mobileExpiryTs) ?></span>
                                <?php else:
                                    $mDays = floor($mobileExpiryDiff / 86400);
                                    $mHours = floor(($mobileExpiryDiff % 86400) / 3600);
                                ?>
                                    <span class="text-[10px] font-medium text-amber-500">Expires in <?= $mDays > 0 ? $mDays . 'd ' . $mHours . 'h' : $mHours . 'h' ?></span>
                                <?php endif; endif; ?>
                            </div>
                            <a href="/BloodConnect/public/patient/my-request/view?id=<?= (int)$rid ?>"
                                class="flex items-center justify-center gap-2 py-2 px-4 bg-red-600 border border-red-600 text-white rounded-xl font-bold text-xs transition-all active:scale-95 hover:bg-red-700">
                                Inspect Request
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

        <?php endif; ?>
    </div>

</div>