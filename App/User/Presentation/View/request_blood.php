<?php
$message = $message ?? '';
$status = $status ?? '';
$user = \App\Shared\Helpers\Session::get('user', []);
?>

<div class="max-w-3xl mx-auto animate-fade-in space-y-4">

    <!-- Header -->
    <div>
        <p class="text-xs font-bold uppercase tracking-wider text-rose-600 mb-1">Blood Request</p>
        <h1 class="text-2xl md:text-3xl font-extrabold tracking-tight text-slate-950">Create a Blood Request</h1>
        <p class="text-sm text-slate-500 mt-1">Fill out the form below to alert compatible donors in your area.</p>
    </div>

    <?php if (!empty($message)): ?>
        <div class="p-4 rounded-xl flex items-center gap-3 border shadow-sm <?= $status === 'success' ? 'bg-emerald-50 border-emerald-200 text-emerald-700' : 'bg-rose-50 border-rose-200 text-rose-700' ?>">
            <div class="w-8 h-8 rounded-lg flex items-center justify-center shrink-0 <?= $status === 'success' ? 'bg-emerald-100 text-emerald-600' : 'bg-rose-100 text-rose-600' ?>">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-4 h-4">
                    <?php if ($status === 'success'): ?>
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                    <?php else: ?>
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                    <?php endif; ?>
                </svg>
            </div>
            <span class="text-sm font-semibold"><?= htmlspecialchars($message) ?></span>
        </div>
    <?php endif; ?>

    <!-- Form Card -->
    <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
        <div class="h-0.5 bg-gradient-to-r from-rose-400 to-rose-500"></div>

        <form action="" method="POST" id="bloodRequestForm" class="p-4 sm:p-5 space-y-3.5">

            <!-- Row 1: Patient Name + Units -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                <div class="space-y-1.5">
                    <label class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Patient Name</label>
                    <div class="relative">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400 pointer-events-none">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z" />
                        </svg>
                        <input type="text" name="patient_name" required placeholder="e.g. John Doe"
                            value="<?= htmlspecialchars($user['username'] ?? '') ?>"
                            class="w-full h-9 bg-slate-50 border border-slate-200 rounded-xl pl-10 pr-3.5 text-sm text-slate-800 placeholder-slate-400 font-medium outline-none transition-all duration-200 focus:bg-white focus:border-rose-400 focus:ring-2 focus:ring-rose-500/10">
                    </div>
                </div>

                <div class="space-y-1.5">
                    <label class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Units Required</label>
                    <div class="relative">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400 pointer-events-none">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5m8.25 3v6.75m0 0l-3-3m3 3l3-3M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z" />
                        </svg>
                        <input type="number" name="unit" min="1" max="2" required placeholder="2"
                            class="w-full h-9 bg-slate-50 border border-slate-200 rounded-xl pl-10 pr-3.5 text-sm text-slate-800 placeholder-slate-400 font-medium outline-none transition-all duration-200 focus:bg-white focus:border-rose-400 focus:ring-2 focus:ring-rose-500/10">
                    </div>
                </div>
            </div>

            <!-- Row 2: Blood Group -->
            <div class="space-y-2">
                <label class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Required Blood Group</label>
                <input type="hidden" name="blood_group_needed" id="selectedBloodGroup" required>
                <div class="grid grid-cols-4 gap-2.5">
                    <?php
                    $blood_types = ['A+', 'A-', 'B+', 'B-', 'O+', 'O-', 'AB+', 'AB-'];
                    foreach ($blood_types as $type):
                        $isPositive = str_contains($type, '+');
                    ?>
                        <button type="button" onclick="selectBloodGroup('<?= $type ?>')" data-type="<?= $type ?>"
                            class="blood-btn py-2.5 rounded-xl border-2 border-slate-200 bg-slate-50 text-sm font-bold tracking-wide text-slate-600 transition-all duration-200 hover:border-rose-300 hover:bg-rose-50/50 active:scale-95 flex flex-col items-center gap-0.5">
                            <span><?= $type ?></span>
                            <span class="text-[9px] font-semibold uppercase <?= $isPositive ? 'text-emerald-500' : 'text-rose-400' ?>"><?= $isPositive ? 'Pos' : 'Neg' ?></span>
                        </button>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Row 3: Hospital Name -->
            <div class="space-y-1.5">
                <label class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Hospital Name</label>
                <div class="relative">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400 pointer-events-none">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 21h19.5m-18-18v18m10.5-18v18m6-13.5V21M6.75 6.75h.75m-.75 3h.75m-.75 3h.75m3-6h.75m-.75 3h.75m-.75 3h.75M6.75 21v-3.375c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21M3 3h12m-.75 4.5H21m-3.75 3.75h.008v.008h-.008v-.008zm0 3h.008v.008h-.008v-.008zm0 3h.008v.008h-.008v-.008z" />
                    </svg>
                    <input type="text" name="hospital_name" required placeholder="e.g. City Hospital, Emergency Wing"
                        class="w-full h-9 bg-slate-50 border border-slate-200 rounded-xl pl-10 pr-3.5 text-sm text-slate-800 placeholder-slate-400 font-medium outline-none transition-all duration-200 focus:bg-white focus:border-rose-400 focus:ring-2 focus:ring-rose-500/10">
                </div>
            </div>

            <!-- Row 3b: State/Region + Township -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                <div class="space-y-1.5">
                    <label class="text-xs font-semibold text-slate-400 uppercase tracking-wider">State / Region</label>
                    <div class="relative">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400 pointer-events-none">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z" />
                        </svg>
                        <input type="text" name="state_region" required placeholder="e.g. Yangon Region"
                            class="w-full h-9 bg-slate-50 border border-slate-200 rounded-xl pl-10 pr-3.5 text-sm text-slate-800 placeholder-slate-400 font-medium outline-none transition-all duration-200 focus:bg-white focus:border-rose-400 focus:ring-2 focus:ring-rose-500/10">
                    </div>
                </div>

                <div class="space-y-1.5">
                    <label class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Township</label>
                    <div class="relative">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400 pointer-events-none">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 6.75V15m6-6v8.25m.503 3.498l4.875-2.437c.381-.19.622-.58.622-1.006V4.82c0-.836-.88-1.38-1.628-1.006l-3.869 1.934c-.317.159-.69.159-1.006 0L9.503 3.252a1.125 1.125 0 00-1.006 0L3.622 5.689C3.24 5.88 3 6.27 3 6.695V19.18c0 .836.88 1.38 1.628 1.006l3.869-1.934c.317-.159.69-.159 1.006 0l4.994 2.497c.317.158.69.158 1.006 0z" />
                        </svg>
                        <input type="text" name="township" required placeholder="e.g. Hlaingthaya"
                            class="w-full h-9 bg-slate-50 border border-slate-200 rounded-xl pl-10 pr-3.5 text-sm text-slate-800 placeholder-slate-400 font-medium outline-none transition-all duration-200 focus:bg-white focus:border-rose-400 focus:ring-2 focus:ring-rose-500/10">
                    </div>
                </div>
            </div>

            <!-- Row 3c: Hospital Address -->
            <div class="space-y-1.5">
                <label class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Hospital Address</label>
                <div class="relative">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="absolute left-3.5 top-3 w-4 h-4 text-slate-400 pointer-events-none">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-3-2.818l.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <textarea name="hospital_address" required rows="2" placeholder="Street address, building, landmark"
                        class="w-full bg-slate-50 border border-slate-200 rounded-xl pl-10 pr-3.5 py-2.5 text-sm text-slate-800 placeholder-slate-400 font-medium outline-none transition-all duration-200 focus:bg-white focus:border-rose-400 focus:ring-2 focus:ring-rose-500/10 resize-none"></textarea>
                </div>
            </div>

            <!-- Row 4: Phone + Urgency -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                <div class="space-y-1.5">
                    <label class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Contact Phone</label>
                    <div class="relative">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400 pointer-events-none">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 002.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-2.824-1.802-5.14-4.117-6.942-6.942l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 00-1.091-.852H4.5A2.25 2.25 0 002.25 4.5v2.25z" />
                        </svg>
                        <input type="text" name="contact_phone" required placeholder="e.g. 09xxxxxxxxx"
                            class="w-full h-9 bg-slate-50 border border-slate-200 rounded-xl pl-10 pr-3.5 text-sm text-slate-800 placeholder-slate-400 font-medium outline-none transition-all duration-200 focus:bg-white focus:border-rose-400 focus:ring-2 focus:ring-rose-500/10">
                    </div>
                </div>

                <div class="space-y-1.5">
                    <label class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Urgency Level</label>
                    <div class="grid grid-cols-3 gap-2 h-9">
                        <label class="relative flex items-center justify-center gap-1.5 rounded-xl border-2 border-slate-200 bg-slate-50 cursor-pointer transition-all duration-200 hover:border-amber-300 has-[:checked]:border-amber-400 has-[:checked]:bg-amber-50">
                            <input type="radio" name="urgency" value="Standard" checked class="sr-only">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-3.5 h-3.5 text-amber-500">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <span class="text-xs font-bold text-slate-700">Standard</span>
                        </label>
                        <label class="relative flex items-center justify-center gap-1.5 rounded-xl border-2 border-slate-200 bg-slate-50 cursor-pointer transition-all duration-200 hover:border-orange-300 has-[:checked]:border-orange-400 has-[:checked]:bg-orange-50">
                            <input type="radio" name="urgency" value="Urgent" class="sr-only">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-3.5 h-3.5 text-orange-500">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                            </svg>
                            <span class="text-xs font-bold text-slate-700">Urgent</span>
                        </label>
                        <label class="relative flex items-center justify-center gap-1.5 rounded-xl border-2 border-slate-200 bg-slate-50 cursor-pointer transition-all duration-200 hover:border-rose-300 has-[:checked]:border-rose-400 has-[:checked]:bg-rose-50">
                            <input type="radio" name="urgency" value="Critical" class="sr-only">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-3.5 h-3.5 text-rose-500">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09z" />
                            </svg>
                            <span class="text-xs font-bold text-slate-700">Critical</span>
                        </label>
                    </div>
                </div>
            </div>

            <!-- Submit -->
            <div class="pt-1">
                <button type="submit" id="submitBtn"
                    class="w-full h-10 bg-[#ce2424] hover:bg-[#b81e1e] text-white font-bold rounded-xl text-sm shadow-sm hover:shadow-md transition-all duration-200 hover:-translate-y-0.5 active:translate-y-0 flex items-center justify-center gap-2.5 relative overflow-hidden group">
                    <span class="absolute inset-0 w-full h-full bg-gradient-to-r from-white/10 to-transparent -skew-x-12 -translate-x-full group-hover:translate-x-full transition-transform duration-700"></span>
                    <span id="btnText" class="relative z-10">Broadcast Request</span>
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-4 h-4 relative z-10 transition-transform duration-300 group-hover:translate-x-0.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 12L3.269 3.126A59.768 59.768 0 0121.485 12 59.77 59.77 0 013.27 20.876L5.999 12zm0 0h7.5" />
                    </svg>
                </button>
            </div>

        </form>
    </div>
</div>

<script>
    function selectBloodGroup(type) {
        document.getElementById('selectedBloodGroup').value = type;

        document.querySelectorAll('.blood-btn').forEach(btn => {
            btn.classList.remove('border-rose-300', 'bg-rose-50/50', 'shadow-sm');
            btn.classList.add('border-slate-200', 'bg-slate-50');
        });

        const btn = document.querySelector(`[data-type="${type}"]`);
        btn.classList.remove('border-slate-200', 'bg-slate-50');
        btn.classList.add('border-rose-300', 'bg-rose-50/50', 'shadow-sm');
    }

    document.getElementById('bloodRequestForm').addEventListener('submit', function() {
        if (this.checkValidity()) {
            const btn = document.getElementById('submitBtn');
            btn.disabled = true;
            btn.classList.add('opacity-80', 'cursor-not-allowed');
            document.getElementById('btnText').textContent = 'Broadcasting...';
        }
    });
</script>
