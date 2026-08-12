<?php
$assignedPermissions = $assignedPermissions ?? $rolePermissions ?? [];
$permissions = $permissions ?? [];
$role = $role ?? null;
$roleName = isset($role['name']) && $role['name'] !== '' ? $role['name'] : 'Unknown Role';
$roleId = is_array($role) && isset($role['id']) ? $role['id'] : '';

// Swapped out font icons for ultra-reliable premium emoji metrics to match roles.php perfectly
$moduleIcons = [
    'User Management' => '👥',
    'Blood Requests'  => '🩸',
    'Notifications'   => '🔔',
    'Profile'         => '👤',
    'Dashboard'       => '📊',
    'Other'           => '⚙️'
];
?>

<style>
    @keyframes modernFadeInUp {
        from { opacity: 0; transform: translateY(24px); }
        to { opacity: 1; transform: translateY(0); }
    }
    .animate-matrix-reveal {
        animation: modernFadeInUp 0.5s cubic-bezier(0.16, 1, 0.3, 1) forwards;
    }
    
    .permission-card-wrapper {
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }
    .permission-card-wrapper:hover {
        transform: translateY(-4px);
        box-shadow: 0 16px 36px -4px rgba(0, 0, 0, 0.04);
    }
    
    .custom-matrix-checkbox:checked {
        animation: checkboxScalePop 0.2s cubic-bezier(0.175, 0.885, 0.32, 1.275);
    }
    @keyframes checkboxScalePop {
        0% { transform: scale(1); }
        50% { transform: scale(1.15); }
        100% { transform: scale(1); }
    }
</style>

<main class="flex-grow space-y-8 w-full min-h-screen select-none selection:bg-red-500 selection:text-white">

    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-6 border-b border-slate-100 pb-6">
        <div class="space-y-1">
            <div class="flex items-center gap-2">
                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-[10px] font-black text-red-600 bg-red-50 uppercase tracking-widest border border-red-100">
                    🛡️ Authorization Matrix
                </span>
            </div>
            <h1 class="text-2xl font-black text-slate-900 tracking-tight sm:text-3xl lg:text-4xl">
                Edit Role Matrix: <span class="text-red-600"><?= htmlspecialchars($roleName) ?></span>
            </h1>
            <p class="text-xs sm:text-sm text-slate-500 font-medium max-w-2xl">
                Configure administrative operational privileges and fine-tune localized structural framework access tokens across the deployment landscape.
            </p>
        </div>

        <button onclick="window.history.back();"
            class="group border border-slate-200 bg-white hover:bg-slate-50 active:scale-[0.98] text-slate-700 px-5 py-2.5 rounded-xl font-bold text-xs shadow-sm hover:shadow transition-all duration-200 flex items-center justify-center gap-2 self-start sm:self-auto flex-shrink-0">
            <span class="transform group-hover:-translate-x-0.5 transition-transform">&larr;</span> Cancel & Go Back
        </button>
    </div>

    <form method="POST" action="/BloodConnect/public/admin/roles/update-permissions" class="space-y-8">
        <input type="hidden" name="role_id" value="<?= htmlspecialchars($roleId) ?>">

        <div class="bg-white border border-slate-200 rounded-2xl p-6 shadow-sm flex flex-col md:flex-row md:items-center justify-between gap-6">
            <label class="flex items-start gap-4 cursor-pointer group max-w-xl">
                <input type="checkbox" id="selectAll" class="custom-matrix-checkbox w-5 h-5 rounded-md border-slate-300 text-red-600 focus:ring-red-500/20 mt-0.5 transition-all cursor-pointer">
                <div class="space-y-0.5">
                    <span class="text-sm font-black text-slate-800 group-hover:text-slate-900 transition-colors">
                        Grant Global Domain Control
                    </span>
                    <p class="text-xs text-slate-400 font-medium leading-normal">
                        Toggle and bind all available core operational parameters simultaneously across the network nodes.
                    </p>
                </div>
            </label>

            <button type="submit" class="w-full md:w-auto px-6 py-3.5 bg-slate-900 hover:bg-red-600 active:scale-[0.99] text-white font-bold text-xs sm:text-sm rounded-xl shadow-sm hover:shadow transition-all duration-300 flex items-center justify-center gap-2.5 flex-shrink-0 group">
                🛡️ <span class="ml-1">Save Matrix Permissions</span>
            </button>
        </div>

        <?php
        $groups = [
            'User Management' => [],
            'Blood Requests'  => [],
            'Notifications'   => [],
            'Profile'         => [],
            'Dashboard'       => [],
            'Other'           => []
        ];

        foreach ($permissions as $permission) {
            $key = strtolower($permission['permission_key'] ?? '');
            if (str_contains($key, 'user')) {
                $groups['User Management'][] = $permission;
            } elseif (str_contains($key, 'blood_request') || str_contains($key, 'blood')) {
                $groups['Blood Requests'][] = $permission;
            } elseif (str_contains($key, 'notification')) {
                $groups['Notifications'][] = $permission;
            } elseif (str_contains($key, 'profile')) {
                $groups['Profile'][] = $permission;
            } elseif (str_contains($key, 'dashboard')) {
                $groups['Dashboard'][] = $permission;
            } else {
                $groups['Other'][] = $permission;
            }
        }
        ?>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 xl:gap-8 w-full">
            <?php foreach ($groups as $title => $items): ?>
                <?php if (!empty($items)): ?>
                    
                    <div class="permission-card-wrapper opacity-0 animate-matrix-reveal bg-white border border-slate-200/90 rounded-2xl p-6 flex flex-col h-full overflow-hidden">

                        <div class="flex items-center justify-between border-b border-slate-100 pb-4 mb-5 shrink-0 gap-3">
                            <div class="flex items-center gap-3 min-w-0">
                                <span class="w-9 h-9 rounded-xl bg-red-50 border border-red-100/60 flex items-center justify-center text-base shrink-0 shadow-sm">
                                    <?= htmlspecialchars($moduleIcons[$title] ?? '⚙️') ?>
                                </span>
                                <h2 class="font-black text-slate-900 tracking-tight text-sm sm:text-base truncate">
                                    <?= htmlspecialchars($title) ?>
                                </h2>
                            </div>

                            <label class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-slate-50 hover:bg-slate-100 border border-slate-200/60 text-[11px] font-bold text-slate-700 transition-colors cursor-pointer shrink-0 select-none">
                                <input type="checkbox" class="group-select custom-matrix-checkbox w-3.5 h-3.5 rounded border-slate-300 text-red-600 focus:ring-0 transition-all cursor-pointer">
                                <span>All</span>
                            </label>
                        </div>

                        <div class="space-y-3.5 flex-grow">
                            <?php foreach ($items as $permission):
                                $isChecked = in_array($permission['permission_id'], $assignedPermissions) || in_array($permission['permission_key'], $assignedPermissions);
                            ?>
                                <label class="flex items-start gap-4 p-4 border border-slate-100 rounded-xl bg-slate-50/40 hover:bg-slate-50/90 hover:border-slate-200 transition-all duration-150 cursor-pointer group">
                                    <input type="checkbox"
                                        class="permission-checkbox custom-matrix-checkbox w-4 h-4 rounded border-slate-300 text-red-600 focus:ring-red-500/10 mt-0.5 transition-all cursor-pointer"
                                        name="permissions[]"
                                        value="<?= htmlspecialchars($permission['permission_id']) ?>"
                                        <?= $isChecked ? 'checked' : '' ?>>
                                    
                                    <div class="min-w-0 space-y-0.5">
                                        <span class="text-xs sm:text-sm font-bold text-slate-800 group-hover:text-slate-900 transition-colors block leading-tight">
                                            <?= htmlspecialchars($permission['permission_name']) ?>
                                        </span>
                                        <?php if (!empty($permission['permission_key'])): ?>
                                            <span class="text-[10px] font-mono font-bold text-slate-400 tracking-tight block truncate">
                                                <?= htmlspecialchars($permission['permission_key']) ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </label>
                            <?php endforeach; ?>
                        </div>

                    </div>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>

    </form>
</main>

<script>
    document.addEventListener("DOMContentLoaded", () => {
        const mainSelectAll = document.getElementById('selectAll');
        const allPermissions = document.querySelectorAll('.permission-checkbox');
        const groupSelects = document.querySelectorAll('.group-select');

        updateMainSelectAll();
        updateModuleSelectAllStates();

        if (mainSelectAll) {
            mainSelectAll.addEventListener('change', function() {
                const isTargetChecked = this.checked;
                allPermissions.forEach(permission => {
                    permission.checked = isTargetChecked;
                });
                groupSelects.forEach(group => {
                    group.checked = isTargetChecked;
                });
            });
        }

        groupSelects.forEach(groupSelect => {
            groupSelect.addEventListener('change', function() {
                const container = this.closest('.permission-card-wrapper');
                const permissions = container.querySelectorAll('.permission-checkbox');

                permissions.forEach(permission => {
                    permission.checked = this.checked;
                });
                updateMainSelectAll();
            });
        });

        allPermissions.forEach(permission => {
            permission.addEventListener('change', function() {
                const container = this.closest('.permission-card-wrapper');
                const groupCheckbox = container.querySelector('.group-select');
                const groupPermissions = container.querySelectorAll('.permission-checkbox');
                const checkedCount = container.querySelectorAll('.permission-checkbox:checked').length;

                if (groupCheckbox) {
                    groupCheckbox.checked = checkedCount === groupPermissions.length;
                }
                updateMainSelectAll();
            });
        });

        function updateMainSelectAll() {
            if (!mainSelectAll || allPermissions.length === 0) return;
            const totalChecked = document.querySelectorAll('.permission-checkbox:checked').length;
            mainSelectAll.checked = (totalChecked === allPermissions.length);
        }

        function updateModuleSelectAllStates() {
            groupSelects.forEach(groupSelect => {
                const container = groupSelect.closest('.permission-card-wrapper');
                const totalPerms = container.querySelectorAll('.permission-checkbox');
                const totalChecked = container.querySelectorAll('.permission-checkbox:checked');
                groupSelect.checked = (totalPerms.length === totalChecked.length) && totalPerms.length > 0;
            });
        }
    });
</script>