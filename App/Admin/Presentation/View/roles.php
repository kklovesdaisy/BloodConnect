<?php
$roles = isset($roles) && is_array($roles) ? $roles : [];
$maxPerms = max(1, max(array_map(fn($r) => (int)($r['total_permissions'] ?? 0), $roles) ?: [1]));
?>

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">

<style>
    body {
        font-family: 'Plus Jakarta Sans', sans-serif;
        background-color: #faf8f8;
    }

    .mono {
        font-family: 'JetBrains Mono', monospace;
    }

    @keyframes springReveal {
        0% {
            opacity: 0;
            transform: translateY(20px) scale(0.99);
        }

        100% {
            opacity: 1;
            transform: translateY(0) scale(1);
        }
    }

    .animate-spring-in {
        opacity: 0;
        animation: springReveal 0.5s cubic-bezier(0.16, 1, 0.3, 1) forwards;
    }

    .role-card-premium {
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }

    .role-card-premium:hover {
        transform: translateY(-5px);
        box-shadow: 0 18px 40px -8px rgba(15, 23, 42, 0.10), 0 6px 18px -6px rgba(239, 68, 68, 0.06);
    }

    .bar-fill {
        transition: width 0.8s cubic-bezier(0.16, 1, 0.3, 1);
    }
</style>

<div class="max-w-7xl mx-auto p-4 sm:p-6 lg:p-8 min-h-screen transition-all opacity-0 translate-y-6 ease-out duration-700" id="rolesContainer">

    <!-- GLASS CORE HEADER -->
    <div class="relative bg-white border border-slate-200/60 rounded-2xl p-6 mb-8 shadow-xs flex flex-col md:flex-row md:items-center md:justify-between gap-6">
        <div class="flex items-center gap-4">
            <div class="w-12 h-12 bg-[#ce2424] text-white rounded-2xl flex items-center justify-center shadow-lg shadow-red-600/10 transform hover:scale-105 transition-transform duration-300">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-6 h-6 text-white">
                    <path fill-rule="evenodd" d="M12 1.5L5.5 4.05c-1.4.5-2.5 1.85-2.5 3.5v4.9c0 4.1 2.75 7.9 6.5 8.95L12 22l2.5-.6c3.75-1.05 6.5-4.85 6.5-8.95V7.55c0-1.65-1.1-3-2.5-3.5L12 1.5zm0 2.1l5.5 2.2v4.65c0 3.35-2.2 6.5-5.5 7.45-3.3-.95-5.5-4.1-5.5-7.45V5.8l5.5-2.2zM11.25 16h1.5v-1.5h-1.5V16zm0-7h1.5v4.5h-1.5V9z" clip-rule="evenodd" />
                </svg>
            </div>
            <div>
                <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight leading-none">Roles &amp; Permissions</h1>
                <p class="text-xs sm:text-sm text-slate-400 font-medium mt-1.5">Leveled administrative clearance tiers and functional node privileges.</p>
            </div>
        </div>

        <!-- LIVE TIER COUNTER -->
        <div class="flex items-center gap-4 bg-slate-900 text-white px-5 py-3 rounded-2xl shadow-lg border border-slate-800 self-start md:self-auto">
            <div class="flex flex-col">
                <span class="text-[9px] text-slate-400 font-bold uppercase tracking-widest">Active Tiers</span>
                <span class="text-lg font-extrabold tracking-tight mt-0.5 mono">
                    <?= sprintf('%02d', count($roles)) ?> <span class="text-xs text-slate-500 font-normal">tiers</span>
                </span>
            </div>
            <div class="w-2.5 h-2.5 rounded-full bg-emerald-400 shadow-[0_0_10px_rgba(52,211,153,0.6)] animate-pulse"></div>
        </div>
    </div>

    <?php if (empty($roles)): ?>
        <div class="bg-white border border-slate-200/70 rounded-2xl p-12 text-center text-slate-400 font-medium animate-spring-in">
            No authorization tiers registered in the system ledger.
        </div>
    <?php else: ?>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <?php foreach ($roles as $index => $role): ?>
                <?php
                $roleName = strtolower($role['name'] ?? '');
                $accent = match ($roleName) {
                    'donor' => ['emerald', '#10b981', 'rgba(16,185,129,0.08)', 'rgba(16,185,129,0.18)'],
                    'patient' => ['blue', '#3b82f6', 'rgba(59,130,246,0.08)', 'rgba(59,130,246,0.18)'],
                    'admin' => ['purple', '#8b5cf6', 'rgba(139,92,246,0.08)', 'rgba(139,92,246,0.18)'],
                    default => ['red', '#dc2626', 'rgba(220,38,38,0.06)', 'rgba(220,38,38,0.15)'],
                };
                [$accentName, $accentHex, $accentBg, $accentBar] = $accent;
                $userCount = (int)($role['total_users'] ?? 0);
                $permCount = (int)($role['total_permissions'] ?? 0);
                $coverage = (int)round(($permCount / $maxPerms) * 100);
                ?>
                <div class="role-card-premium animate-spring-in relative bg-white border border-slate-200/70 rounded-2xl overflow-hidden shadow-2xs"
                    style="animation-delay: <?= $index * 80 ?>ms;">

                    <!-- Accent gradient strip -->
                    <div class="h-1.5 w-full" style="background: linear-gradient(90deg, <?= $accentHex ?>, transparent);"></div>

                    <div class="p-7 sm:p-8 space-y-7">
                        <!-- Identity row -->
                        <div class="flex items-start justify-between gap-4">
                            <div class="flex items-center gap-4">
                                <div class="w-12 h-12 sm:w-14 sm:h-14 rounded-xl flex items-center justify-center text-xl flex-shrink-0"
                                    style="background: <?= $accentBg ?>; color: <?= $accentHex ?>; border: 1px solid <?= $accentBar ?>;">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-6 h-6">
                                        <path fill-rule="evenodd" d="M12 1.5L5.5 4.05c-1.4.5-2.5 1.85-2.5 3.5v4.9c0 4.1 2.75 7.9 6.5 8.95L12 22l2.5-.6c3.75-1.05 6.5-4.85 6.5-8.95V7.55c0-1.65-1.1-3-2.5-3.5L12 1.5zm0 2.1l5.5 2.2v4.65c0 3.35-2.2 6.5-5.5 7.45-3.3-.95-5.5-4.1-5.5-7.45V5.8l5.5-2.2z" clip-rule="evenodd" />
                                    </svg>
                                </div>
                                <div class="space-y-1 min-w-0">
                                    <h3 class="font-extrabold text-slate-900 text-base sm:text-lg tracking-tight truncate capitalize">
                                        <?= htmlspecialchars($role['name']) ?>
                                    </h3>
                                    <span class="inline-flex items-center gap-1.5 text-[10px] font-bold text-slate-400 uppercase tracking-widest">
                                        <span class="w-1.5 h-1.5 rounded-full" style="background: <?= $accentHex ?>;"></span>
                                        System Authorization
                                    </span>
                                </div>
                            </div>
                            <span class="mono text-[10px] font-bold text-slate-300 bg-slate-50 border border-slate-100 px-2 py-1 rounded-lg">
                                #0<?= (int)$role['id'] ?>
                            </span>
                        </div>

                        <!-- Stat panels -->
                        <div class="grid grid-cols-2 gap-4">
                            <div class="bg-slate-50/80 border border-slate-100 rounded-xl p-4 space-y-1">
                                <span class="block text-[9px] font-extrabold text-slate-400 uppercase tracking-widest">Personnel</span>
                                <div class="flex items-baseline gap-1.5">
                                    <span class="mono text-3xl font-black text-slate-800"><?= number_format($userCount) ?></span>
                                    <span class="text-[11px] font-semibold text-slate-500">users</span>
                                </div>
                            </div>
                            <div class="rounded-xl p-4 space-y-1" style="background: <?= $accentBg ?>; border: 1px solid <?= $accentBar ?>;">
                                <span class="block text-[9px] font-extrabold uppercase tracking-widest" style="color: <?= $accentHex ?>;">Scopes</span>
                                <div class="flex items-baseline gap-1.5">
                                    <span class="mono text-3xl font-black" style="color: <?= $accentHex ?>;"><?= number_format($permCount) ?></span>
                                    <span class="text-[11px] font-semibold" style="color: <?= $accentHex ?>;">rules</span>
                                </div>
                            </div>
                        </div>

                        <!-- Permission coverage -->
                        <div class="space-y-2">
                            <div class="flex items-center justify-between">
                                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Clearance Coverage</span>
                                <span class="mono text-[10px] font-bold text-slate-600"><?= $coverage ?>%</span>
                            </div>
                            <div class="h-2.5 rounded-full bg-slate-100 overflow-hidden">
                                <div class="bar-fill h-full rounded-full"
                                    style="width: <?= $coverage ?>%; background: linear-gradient(90deg, <?= $accentHex ?>, <?= $accentHex ?>cc);"></div>
                            </div>
                        </div>

                        <!-- Footer actions -->
                        <div class="pt-4 border-t border-slate-100 flex items-center justify-between gap-3">
                            <span class="text-[11px] font-medium text-slate-400 italic">Authorization matrix</span>
                            <a href="/BloodConnect/public/admin/roles/<?= (int)$role['id'] ?>"
                                class="inline-flex items-center justify-center gap-2 px-5 py-2.5 rounded-xl text-white text-xs font-bold shadow-sm hover:shadow-md transition-all duration-300 group active:scale-[0.98]"
                                style="background: <?= $accentHex ?>;">
                                <span>Configure</span>
                                <svg class="w-3.5 h-3.5 transform group-hover:translate-x-0.5 transition-transform" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" />
                                </svg>
                            </a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

    <?php endif; ?>
</div>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const viewport = document.getElementById('rolesContainer');
        if (viewport) {
            requestAnimationFrame(() => {
                viewport.classList.remove('opacity-0', 'translate-y-6');
                document.querySelectorAll('.bar-fill').forEach((bar, i) => {
                    const target = bar.style.width;
                    bar.style.width = '0%';
                    setTimeout(() => {
                        bar.style.width = target;
                    }, 150 + i * 120);
                });
            });
        }
    });
</script>
