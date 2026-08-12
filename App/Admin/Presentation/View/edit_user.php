<?php
$user = $user ?? ($data['user'] ?? [
    'user_id' => '',
    'username' => '',
    'email' => '',
    'user_type_id' => 2,
    'is_active' => 1,
]);
?>

<!-- 
  MAIN CONTENT WRAPPER:
  Matches the layout container properties of your User Management view.
-->
<div class="bg-[#faf8f8] opacity-0 translate-y-1 transition-all duration-300 ease-out" id="editUserCardWrapper">

    <!-- HEADER BLOCK -->
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-slate-900">Edit User Profile</h1>
        <p class="text-sm text-slate-500">Modify account permissions, credentials, and access states</p>
    </div>

    <!-- 
      EXACT MATCH TO YOUR TABLE CONTAINER:
      Uses the identical rounding ('rounded-xl'), fine border color, and crisp, 
      low-offset shadow from 'Screenshot 2026-07-06 010649.png'.
    -->
    <div class="bg-white rounded-xl shadow border border-slate-200/80 p-6 sm:p-10 lg:p-12">

        <form method="POST" action="/BloodConnect/public/admin/user/update" id="editUserForm" class="space-y-8">
            <input type="hidden" name="user_id" value="<?= $user['user_id'] ?>">

            <!-- Form Fields Grid Structure -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-x-8 gap-y-6">

                <!-- FULL NAME -->
                <div class="space-y-2 group">
                    <label class="block text-[11px] font-bold text-slate-400 group-focus-within:text-rose-500 uppercase tracking-wider transition-colors duration-200">
                        Full Name
                    </label>
                    <input type="text"
                        name="username"
                        required
                        value="<?= htmlspecialchars($user['username']) ?>"
                        class="w-full px-5 py-3.5 bg-slate-50/40 border border-slate-200/80 rounded-xl outline-none focus:border-rose-500 focus:bg-white focus:ring-4 focus:ring-rose-500/5 transition-all duration-200 text-slate-700 font-medium text-sm">
                </div>

                <!-- EMAIL ADDRESS -->
                <div class="space-y-2 group">
                    <label class="block text-[11px] font-bold text-slate-400 group-focus-within:text-rose-500 uppercase tracking-wider transition-colors duration-200">
                        Email Address
                    </label>
                    <input type="email"
                        name="email"
                        required
                        value="<?= htmlspecialchars($user['email']) ?>"
                        class="w-full px-5 py-3.5 bg-slate-50/40 border border-slate-200/80 rounded-xl outline-none focus:border-rose-500 focus:bg-white focus:ring-4 focus:ring-rose-500/5 transition-all duration-200 text-slate-700 font-medium text-sm">
                </div>

                <!-- SYSTEM ROLE -->
                <div class="space-y-2 group">
                    <label class="block text-[11px] font-bold text-slate-400 group-focus-within:text-rose-500 uppercase tracking-wider transition-colors duration-200">
                        System Role
                    </label>
                    <div class="relative flex items-center">
                        <select name="user_type_id" class="w-full px-5 py-3.5 bg-slate-50/40 border border-slate-200/80 rounded-xl outline-none focus:border-rose-500 focus:bg-white focus:ring-4 focus:ring-rose-500/5 transition-all duration-200 text-slate-700 font-medium text-sm appearance-none cursor-pointer">
                            <option value="1" <?= $user['user_type_id'] == 1 ? 'selected' : '' ?>>Admin</option>
                            <option value="2" <?= $user['user_type_id'] == 2 ? 'selected' : '' ?>>Donor</option>
                            <option value="3" <?= $user['user_type_id'] == 3 ? 'selected' : '' ?>>Patient</option>
                        </select>
                        <span class="absolute right-5 pointer-events-none text-slate-400 group-focus-within:text-rose-500 transition-colors duration-200">
                            <i class="fa-solid fa-chevron-down text-xs"></i>
                        </span>
                    </div>
                </div>

                <!-- ACCOUNT STATUS -->
                <div class="space-y-2 group">
                    <label class="block text-[11px] font-bold text-slate-400 group-focus-within:text-rose-500 uppercase tracking-wider transition-colors duration-200">
                        Account Status
                    </label>
                    <div class="relative flex items-center">
                        <select name="is_active" class="w-full px-5 py-3.5 bg-slate-50/40 border border-slate-200/80 rounded-xl outline-none focus:border-rose-500 focus:bg-white focus:ring-4 focus:ring-rose-500/5 transition-all duration-200 text-slate-700 font-medium text-sm appearance-none cursor-pointer">
                            <option value="1" <?= $user['is_active'] == 1 ? 'selected' : '' ?>>Active</option>
                            <option value="0" <?= $user['is_active'] == 0 ? 'selected' : '' ?>>Disabled</option>
                        </select>
                        <span class="absolute right-5 pointer-events-none text-slate-400 group-focus-within:text-rose-500 transition-colors duration-200">
                            <i class="fa-solid fa-chevron-down text-xs"></i>
                        </span>
                    </div>
                </div>

            </div>

            <!-- ACTION FOOTER BUTTONS -->
            <div class="pt-6 border-t border-slate-100 flex flex-col-reverse sm:flex-row items-center justify-end gap-4">
                <a href="/BloodConnect/public/admin/user" class="w-full sm:w-auto text-sm font-semibold text-slate-400 hover:text-slate-600 transition-colors duration-200 text-center px-4 py-2 rounded-lg">
                    Cancel
                </a>
                <button type="submit" class="w-full sm:w-auto px-10 py-3.5 bg-rose-600 hover:bg-rose-700 text-white text-sm font-semibold rounded-xl transition-all duration-200 active:scale-[0.99] shadow-md shadow-rose-600/10">
                    Save Changes
                </button>
            </div>
        </form>

    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const wrapper = document.getElementById('editUserCardWrapper');
        if (wrapper) {
            wrapper.classList.remove('opacity-0', 'translate-y-1');
        }

        const form = document.getElementById('editUserForm');
        if (form) {
            form.addEventListener('submit', function() {
                const submitBtn = form.querySelector('button[type="submit"]');
                submitBtn.disabled = true;
                submitBtn.innerHTML = 'Saving Changes...';
                submitBtn.classList.add('opacity-80', 'cursor-not-allowed');
            });
        }
    });
</script>