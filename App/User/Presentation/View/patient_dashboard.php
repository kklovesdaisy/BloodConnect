<?php

use App\Shared\Helpers\Session;

Session::start();
$username = $username ?? 'Patient';
$patientName = $_SESSION['username'] ?? 'Patient';

$metrics = $metrics ?? [
    'total_requests' => 0,
    'pending_requests' => 0,
    'accepted_requests' => 0,
    'completed_requests' => 0,
    'cancelled_requests' => 0,
];
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Patient Dashboard | BloodConnect</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        .custom-scroll::-webkit-scrollbar { width: 5px; }
        .custom-scroll::-webkit-scrollbar-track { background: transparent; }
        .custom-scroll::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 20px; }
        .custom-scroll::-webkit-scrollbar-thumb:hover { background: #94a3b8; }
        body {
            background-color: #FFF9F9;
        }

        .animate-fade-in {
            animation: fadeIn 0.6s cubic-bezier(0.16, 1, 0.3, 1) forwards;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(12px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
    </style>
</head>

<body class="min-h-screen text-slate-800 font-sans antialiased">

    <main class="w-full max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-8">

        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 animate-fade-in">
            <div>
                <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight">
                    Welcome, <span class="text-[#E63946]"><?= htmlspecialchars($username) ?></span>
                    <span class="inline-block origin-bottom animate-[wave_2s_infinite]">👋</span>
                </h1>
                <p class="text-sm text-slate-500 mt-0.5">Manage your blood requests status dynamically.</p>
            </div>
            <!-- <div>
                <button onclick="openModal()" class="w-full sm:w-auto inline-flex items-center justify-center gap-2 bg-[#E63946] hover:bg-[#D62839] text-white font-semibold px-5 py-3 rounded-xl shadow-sm transition-all duration-300 transform hover:-translate-y-0.5 active:translate-y-0">
                    <i class="fa-solid fa-plus text-xs"></i>
                    <span>New Blood Request</span>
                </button>
            </div> -->
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 md:gap-6 animate-fade-in" style="animation-delay: 100ms;">

            <a href="/BloodConnect/public/patient/my-requests" class="bg-white/80 backdrop-blur-md p-6 rounded-2xl shadow-sm border border-slate-100 flex items-center gap-4 transition-all duration-300 hover:shadow-sm hover:-translate-y-0.5">
                <div class="w-12 h-12 rounded-xl bg-red-50 text-[#E63946] flex items-center justify-center text-xl shrink-0">
                    <i class="fa-solid fa-droplet animate-pulse"></i>
                </div>
                <div>
                    <p class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Blood Group / Total</p>
                    <p class="text-xl font-black text-slate-900 mt-0.5"><?php echo $metrics['total_requests']; ?> <span class="text-sm font-medium text-slate-400">Requests</span></p>
                </div>
            </a>

            <a href="/BloodConnect/public/patient/my-requests?filter=pending" class="bg-white/80 backdrop-blur-md p-6 rounded-2xl shadow-sm border border-slate-100 flex items-center gap-4 transition-all duration-300 hover:shadow-sm hover:-translate-y-0.5">
                <div class="w-12 h-12 rounded-xl bg-amber-50 text-amber-500 flex items-center justify-center text-xl shrink-0">
                    <i class="fa-solid fa-clock-rotate-left"></i>
                </div>
                <div>
                    <p class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Availability Status</p>
                    <div class="flex items-center gap-2 mt-0.5">
                        <span class="text-xl font-black text-slate-900"><?php echo $metrics['pending_requests']; ?> Pending</span>
                        <span class="w-2 h-2 rounded-full bg-amber-500 animate-ping"></span>
                    </div>
                </div>
            </a>

            <a href="/BloodConnect/public/patient/my-requests?filter=accepted" class="bg-white/80 backdrop-blur-md p-6 rounded-2xl shadow-sm border border-slate-100 flex items-center gap-4 transition-all duration-300 hover:shadow-sm hover:-translate-y-0.5">
                <div class="w-12 h-12 rounded-xl bg-emerald-50 text-emerald-500 flex items-center justify-center text-xl shrink-0">
                    <i class="fa-solid fa-circle-check"></i>
                </div>
                <div>
                    <p class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Accepted Requests</p>
                    <p class="text-xl font-black text-emerald-600 mt-0.5"><?php echo $metrics['accepted_requests']; ?> Approved</p>
                </div>
            </a>

            <a href="/BloodConnect/public/patient/my-requests?filter=cancelled" class="bg-white/80 backdrop-blur-md p-6 rounded-2xl shadow-sm border border-slate-100 flex items-center gap-4 transition-all duration-300 hover:shadow-sm hover:-translate-y-0.5">
                <div class="w-12 h-12 rounded-xl bg-rose-50 text-rose-500 flex items-center justify-center text-xl shrink-0">
                    <i class="fa-solid fa-ban"></i>
                </div>
                <div>
                    <p class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Cancelled Requests</p>
                    <p class="text-xl font-black text-rose-600 mt-0.5"><?php echo $metrics['cancelled_requests']; ?> Cancelled</p>
                </div>
            </a>

        </div>

        <div class="space-y-4 animate-fade-in" style="animation-delay: 200ms;">
            <div class="flex items-center gap-2">
                <h2 class="text-lg font-bold text-slate-900">Recent Blood Requests</h2>
                <span class="bg-[#E63946] text-white text-xs font-bold px-2 py-0.5 rounded-full"><?= !empty($requests) ? count($requests) : 0 ?></span>
            </div>

            <div class="hidden lg:block bg-white rounded-2xl border border-slate-100 max-h-[400px] overflow-y-auto custom-scroll">
                <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-slate-50/70 border-b border-slate-100 text-[11px] font-bold uppercase tracking-wider text-slate-400">
                                <th class="py-4 px-6">Request ID</th>
                                <th class="py-4 px-6">Patient Name</th>
                                <th class="py-4 px-6 text-center">Urgency Level</th>
                                <th class="py-4 px-6 text-center">Blood</th>
                                <th class="py-4 px-6 text-center">Unit</th>
                                <th class="py-4 px-6">Hospital / Location</th>
                                <th class="py-4 px-6">Contact Phone</th>
                                <th class="py-4 px-6 text-center">Status</th>
                                <th class="py-4 px-6 text-right">Date</th>
                                <th class="py-4 px-6 text-right">Expires</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-sm font-medium text-slate-600">
                            <?php if (!empty($requests)): ?>
                                <?php foreach ($requests as $request): ?>
                                    <tr class="hover:bg-slate-50/50 transition-colors duration-200">
                                        <td class="py-4 px-6 font-semibold text-slate-900"><?= htmlspecialchars($request['request_code']) ?></td>
                                        <td class="py-4 px-6"><?= htmlspecialchars($request['patient_name']) ?></td>
                                        <td class="py-4 px-6 text-center">
                                            <?php
                                            $urgency = strtolower(trim($request['urgency'] ?? ''));
                                            if (in_array($urgency, ['critical (immediate)', 'critical'])) {
                                                echo '<span class="inline-block text-xs font-bold uppercase tracking-wide px-2.5 py-1 rounded bg-red-50 text-red-600">Urgent Urgency</span>';
                                            } elseif ($urgency == 'urgent') {
                                                echo '<span class="inline-block text-xs font-bold uppercase tracking-wide px-2.5 py-1 rounded bg-orange-50 text-orange-600">Urgent</span>';
                                            } else {
                                                echo '<span class="inline-block text-xs font-bold uppercase tracking-wide px-2.5 py-1 rounded bg-slate-100 text-slate-600">Standard</span>';
                                            }
                                            ?>
                                        </td>
                                        <td class="py-4 px-6 text-center">
                                            <span class="inline-block bg-red-50 text-[#E63946] text-xs font-bold px-2.5 py-1 rounded-lg border border-red-100">
                                                <?= htmlspecialchars($request['blood_group_needed']) ?>
                                            </span>
                                        </td>
                                        <td class="py-4 px-6 text-center"><?= htmlspecialchars($request['unit']) ?> Units</td>
                                        <td class="py-4 px-6">
                                            <div class="flex items-center gap-1.5 text-slate-700">
                                                <i class="fa-solid fa-hospital text-slate-400 text-xs"></i>
                                                <span><?= htmlspecialchars($request['hospital_name']) ?></span>
                                            </div>
                                        </td>
                                        <td class="py-4 px-6"><?= htmlspecialchars($request['contact_phone']) ?></td>
                                        <td class="py-4 px-6 text-center">
                                            <?php
                                            $statusKey = strtolower($request['status']);
                                            if (in_array($statusKey, ['pending', 'assigned', 'declined'])) {
                                                echo '<span class="inline-flex px-3 py-1 rounded-full text-xs font-bold bg-amber-50 text-amber-600">Pending</span>';
                                            } else {
                                                switch ($statusKey) {
                                                    case 'accepted':
                                                        echo '<span class="inline-flex px-3 py-1 rounded-full text-xs font-bold bg-emerald-50 text-emerald-600">Accepted</span>';
                                                        break;
                                                    case 'completed':
                                                        echo '<span class="inline-flex px-3 py-1 rounded-full text-xs font-bold bg-blue-50 text-blue-600">Completed</span>';
                                                        break;
                                                    case 'expired':
                                                        echo '<span class="inline-flex px-3 py-1 rounded-full text-xs font-bold bg-slate-200 text-slate-600">Expired</span>';
                                                        break;
                                                    default:
                                                        echo '<span class="inline-flex px-3 py-1 rounded-full text-xs font-bold bg-red-50 text-red-600">Cancelled</span>';
                                                }
                                            }
                                            ?>
                                        </td>
                                        <td class="py-4 px-6 text-right text-slate-400 text-xs"><?= date('d M Y', strtotime($request['created_at'])) ?></td>
                                        <td class="py-4 px-6 text-right">
                                            <?php
                                            $expiry = $request['expiry_date'] ?? '';
                                            $showExpiry = in_array($statusKey, ['pending', 'expired']);
                                            if ($showExpiry && $expiry !== ''):
                                                $expiryTs = strtotime($expiry);
                                                $diff = $expiryTs - time();
                                            ?>
                                                <span class="text-xs font-semibold text-slate-600 block"><?= date('d M Y', $expiryTs) ?></span>
                                                <?php if ($diff > 0):
                                                    $days = floor($diff / 86400);
                                                    $hours = floor(($diff % 86400) / 3600);
                                                ?>
                                                    <span class="text-[10px] text-amber-500 block mt-0.5"><?= $days > 0 ? $days . 'd ' . $hours . 'h' : $hours . 'h' ?> left</span>
                                                <?php else: ?>
                                                    <span class="text-[10px] font-bold text-slate-500 block mt-0.5">Expired</span>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                <span class="text-xs text-slate-300">—</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="10" class="py-12 text-center text-slate-400 font-normal">No active blood requests found.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
            </div>

            <div class="lg:hidden max-h-[400px] overflow-y-auto custom-scroll space-y-4 pr-1">
                <?php if (!empty($requests)): ?>
                    <?php foreach ($requests as $request): ?>
                        <div class="bg-white rounded-2xl border-l-4 border-[#E63946] border-y border-r border-slate-100 p-5 flex flex-col sm:flex-row sm:items-center justify-between gap-4 relative transition-all duration-200 active:scale-[0.99]">
                            <div class="flex items-start gap-4">
                                <div class="w-12 h-12 rounded-full bg-red-50 border border-red-100 text-[#E63946] flex items-center justify-center font-black text-sm shrink-0">
                                    <?= htmlspecialchars($request['blood_group_needed']) ?>
                                </div>
                                <div class="space-y-1">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <span class="font-bold text-slate-900 text-base">Request from: <?= htmlspecialchars($request['patient_name']) ?></span>
                                        <?php if (in_array(strtolower(trim($request['urgency'] ?? '')), ['critical (immediate)', 'critical', 'urgent'])): ?>
                                            <span class="bg-orange-50 text-orange-600 text-[10px] uppercase font-extrabold tracking-wider px-2 py-0.5 rounded">Urgent Urgency</span>
                                        <?php endif; ?>
                                    </div>
                                    <p class="text-sm text-slate-500 flex items-center gap-1.5">
                                        <i class="fa-solid fa-hospital text-slate-400 text-xs"></i>
                                        <?= htmlspecialchars($request['hospital_name']) ?>
                                    </p>
                                    <p class="text-xs text-slate-400">
                                        <?= htmlspecialchars($request['unit']) ?> Units &bull; Requested on <?= date('d M Y', strtotime($request['created_at'])) ?>
                                    </p>
                                    <?php
                                    $mobileExpiry = $request['expiry_date'] ?? '';
                                    $mobileStatusKey2 = strtolower($request['status']);
                                    if (in_array($mobileStatusKey2, ['pending', 'expired']) && $mobileExpiry !== ''):
                                        $mobileExpiryTs = strtotime($mobileExpiry);
                                        $mobileDiff = $mobileExpiryTs - time();
                                    ?>
                                        <p class="text-xs text-slate-400 pt-0.5">
                                            <i class="fa-regular fa-clock text-slate-400 text-xs mr-1"></i>
                                            Expires <?= date('d M Y', $mobileExpiryTs) ?>
                                            <?php if ($mobileDiff > 0):
                                                $mobileDays = floor($mobileDiff / 86400);
                                                $mobileHours = floor(($mobileDiff % 86400) / 3600);
                                                echo '&bull; <span class="text-amber-600 font-semibold">' . ($mobileDays > 0 ? $mobileDays . 'd ' . $mobileHours . 'h' : $mobileHours . 'h') . ' left</span>';
                                            endif; ?>
                                        </p>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="sm:text-right flex sm:flex-col items-center sm:items-end justify-between sm:justify-center border-t sm:border-t-0 pt-3 sm:pt-0 border-slate-50">
                                <span class="text-xs text-slate-400 font-medium sm:hidden">Current Status:</span>
                                <?php
                                $mobileStatusKey = strtolower($request['status']);
                                $mobileStatusDisplay = in_array($mobileStatusKey, ['pending', 'assigned', 'declined']) ? 'Pending' : ucfirst($mobileStatusKey);
                                ?>
                                <span class="<?= $mobileStatusKey === 'expired' ? 'bg-slate-200 text-slate-600' : 'bg-amber-50 text-amber-700' ?> px-4 py-1.5 text-xs font-bold rounded-xl">
                                    <?= htmlspecialchars($mobileStatusDisplay) ?>
                                </span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="bg-white rounded-2xl p-8 border border-slate-100 text-center text-slate-400">
                        No active requests found.
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <div id="modalBackdrop" class="fixed inset-0 bg-slate-900/40 backdrop-blur-sm z-50 flex items-center justify-center hidden opacity-0 transition-opacity duration-300 px-4">
        <div id="modalBox" class="bg-white w-full max-w-md p-6 rounded-2xl shadow-xl border border-slate-50 scale-95 transform transition-transform duration-300">
            <div class="flex items-center justify-between border-b border-slate-100 pb-4 mb-4">
                <h3 class="text-lg font-bold text-slate-900">Create New Request</h3>
                <button onclick="closeModal()" class="text-slate-400 hover:text-slate-600 transition-colors w-8 h-8 flex items-center justify-center rounded-full hover:bg-slate-50">
                    <i class="fa-solid fa-xmark text-base"></i>
                </button>
            </div>
            <form action="" method="POST" class="space-y-4">
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1.5">Blood Type</label>
                    <select name="blood_type" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2.5 text-sm focus:outline-none focus:border-[#E63946] focus:bg-white transition-all">
                        <option value="A+">A+</option>
                        <option value="A-">A-</option>
                        <option value="B+">B+</option>
                        <option value="B-">B-</option>
                        <option value="O+">O+</option>
                        <option value="O-">O-</option>
                        <option value="AB+">AB+</option>
                        <option value="AB-">AB-</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1.5">Hospital / Target Location</label>
                    <input type="text" name="location" placeholder="e.g. Grand Hospital, Yangon" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2.5 text-sm focus:outline-none focus:border-[#E63946] focus:bg-white transition-all" required>
                </div>
                <button type="submit" class="w-full bg-[#E63946] hover:bg-[#D62839] text-white font-semibold py-3 rounded-xl transition-colors shadow-sm mt-2">
                    Submit Request
                </button>
            </form>
        </div>
    </div>

    <script>
        const backdrop = document.getElementById('modalBackdrop');
        const modalBox = document.getElementById('modalBox');

        function openModal() {
            backdrop.classList.remove('hidden');
            setTimeout(() => {
                backdrop.classList.remove('opacity-0');
                modalBox.classList.remove('scale-95');
                modalBox.classList.add('scale-100');
            }, 20);
        }

        function closeModal() {
            backdrop.classList.add('opacity-0');
            modalBox.classList.remove('scale-100');
            modalBox.classList.add('scale-95');
            setTimeout(() => {
                backdrop.classList.add('hidden');
            }, 300);
        }

        backdrop.addEventListener('click', (e) => {
            if (e.target === backdrop) closeModal();
        });
    </script>
</body>

</html>