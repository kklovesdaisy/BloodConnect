<?php
$profile = $profile ?? [];
?>
<style>
    @keyframes fadeInUp { from { opacity: 0; transform: translateY(12px); } to { opacity: 1; transform: translateY(0); } }
    .animate-fade-in-up { animation: fadeInUp .4s cubic-bezier(.16,1,.3,1) forwards; }
</style>

<div class="max-w-2xl mx-auto">
    <div class="bg-white border border-slate-200/60 rounded-2xl p-6 mb-6 shadow-xs animate-fade-in-up">
        <div class="flex items-center gap-4">
            <div class="w-12 h-12 bg-[#ce2424] text-white rounded-2xl flex items-center justify-center shadow-lg shadow-red-600/10 shrink-0">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6"><path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10" /></svg>
            </div>
            <div>
                <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight leading-none">Update Hospital Profile</h1>
                <p class="text-xs sm:text-sm text-slate-400 font-medium mt-1.5">Keep your contact and location details up to date.</p>
            </div>
        </div>
    </div>

    <form method="POST" action="/BloodConnect/public/hospital/profile/update"
        class="bg-white border border-slate-200/60 rounded-2xl p-6 sm:p-8 shadow-xs animate-fade-in-up" style="animation-delay:.05s">

        <div class="space-y-5">
            <div>
                <label class="block text-sm font-bold text-slate-700 mb-1.5">Hospital Name</label>
                <input type="text" name="hospital_name" required value="<?= htmlspecialchars((string)($profile['hospital_name'] ?? '')) ?>"
                    class="w-full border border-slate-200 rounded-xl px-4 py-3 text-sm font-semibold text-slate-800 focus:outline-none focus:ring-2 focus:ring-red-200 focus:border-[#ce2424]">
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                <div>
                    <label class="block text-sm font-bold text-slate-700 mb-1.5">License No</label>
                    <input type="text" name="license_no" value="<?= htmlspecialchars((string)($profile['license_no'] ?? '')) ?>"
                        class="w-full border border-slate-200 rounded-xl px-4 py-3 text-sm font-semibold text-slate-800 focus:outline-none focus:ring-2 focus:ring-red-200 focus:border-[#ce2424]">
                </div>
                <div>
                    <label class="block text-sm font-bold text-slate-700 mb-1.5">Phone</label>
                    <input type="text" name="phone" value="<?= htmlspecialchars((string)($profile['phone'] ?? '')) ?>"
                        class="w-full border border-slate-200 rounded-xl px-4 py-3 text-sm font-semibold text-slate-800 focus:outline-none focus:ring-2 focus:ring-red-200 focus:border-[#ce2424]">
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                <div>
                    <label class="block text-sm font-bold text-slate-700 mb-1.5">State / Region</label>
                    <input type="text" name="state_region" value="<?= htmlspecialchars((string)($profile['state_region'] ?? '')) ?>"
                        class="w-full border border-slate-200 rounded-xl px-4 py-3 text-sm font-semibold text-slate-800 focus:outline-none focus:ring-2 focus:ring-red-200 focus:border-[#ce2424]">
                </div>
                <div>
                    <label class="block text-sm font-bold text-slate-700 mb-1.5">Township</label>
                    <input type="text" name="township" value="<?= htmlspecialchars((string)($profile['township'] ?? '')) ?>"
                        class="w-full border border-slate-200 rounded-xl px-4 py-3 text-sm font-semibold text-slate-800 focus:outline-none focus:ring-2 focus:ring-red-200 focus:border-[#ce2424]">
                </div>
            </div>

            <div>
                <label class="block text-sm font-bold text-slate-700 mb-1.5">Address</label>
                <textarea name="address" rows="3"
                    class="w-full border border-slate-200 rounded-xl px-4 py-3 text-sm font-semibold text-slate-800 focus:outline-none focus:ring-2 focus:ring-red-200 focus:border-[#ce2424]"><?= htmlspecialchars((string)($profile['address'] ?? '')) ?></textarea>
            </div>
        </div>

        <div class="flex gap-3 mt-7">
            <a href="/BloodConnect/public/hospital/profile"
                class="px-5 py-3 border border-slate-200 rounded-xl text-sm font-bold text-slate-600 hover:bg-slate-50 transition-all">Cancel</a>
            <button type="submit"
                class="flex-1 px-5 py-3 bg-[#ce2424] text-white text-sm font-bold rounded-xl hover:bg-[#b81f1f] transition-all active:scale-95 shadow-md shadow-red-600/20">
                Save Changes
            </button>
        </div>
    </form>
</div>
