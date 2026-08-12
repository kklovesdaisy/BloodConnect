<?php

use App\Shared\Helpers\Session;

Session::start();

$username = Session::get('username', 'Hospital');

$unreadCount = $unreadCount ?? 0;

?>
<header class="bg-white border-b border-slate-100 px-3 sm:px-6 lg:px-8 py-3 sm:py-4 flex items-center justify-between sticky top-0 z-30">

    <div class="flex items-center gap-2 sm:gap-3">
        <button id="mobileSidebarToggle"
            class="p-2 rounded-xl text-[#0b1325] hover:bg-red-50/70 hover:text-[#ce2424] transition-all flex items-center justify-center focus:outline-none">
            <span class="text-xl leading-none">☰</span>
        </button>

        <h2 class="text-sm sm:text-base lg:text-lg font-black text-[#0b1325] tracking-tight leading-none whitespace-nowrap">
            Hospital Dashboard
        </h2>
    </div>

    <div class="flex items-center gap-2 sm:gap-5">
        <div class="relative">
            <a href="/BloodConnect/public/hospital/notifications"
                class="relative p-2 text-slate-400 hover:bg-red-50/70 hover:text-[#ce2424] rounded-xl transition-all inline-flex items-center justify-center">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75v-.7V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0" />
                </svg>
                <span id="topbarNotificationBadge"
                    class="absolute top-1 right-1 w-4 h-4 rounded-full bg-[#ce2424] text-[9px] font-black text-white flex items-center justify-center ring-2 ring-white <?= ($unreadCount > 0) ? '' : 'hidden' ?>">
                    <?= $unreadCount ?>
                </span>
            </a>
        </div>

        <div class="relative group">
            <button class="flex items-center gap-1.5 sm:gap-2.5 bg-slate-50 p-1.5 sm:pl-2 sm:pr-3 sm:py-1.5 rounded-xl border border-slate-100 transition-all hover:bg-red-50/70 hover:text-[#ce2424] cursor-pointer outline-none">
                <div class="w-7 h-7 rounded-full bg-slate-200 group-hover:bg-red-200/50 flex items-center justify-center font-bold text-[#0b1325] group-hover:text-[#ce2424] text-xs transition-colors shrink-0">
                    🏥
                </div>
                <div class="text-left hidden sm:block">
                    <span class="block text-sm font-bold text-[#0b1325] group-hover:text-[#ce2424] leading-none transition-colors max-w-[120px] truncate">
                        <?= htmlspecialchars($username) ?>
                    </span>
                    <span class="block text-[9px] text-slate-400 font-bold uppercase tracking-wider mt-0.5">
                        Hospital
                    </span>
                </div>
                <span class="text-[10px] text-slate-400 group-hover:text-[#ce2424] transition-transform group-hover:rotate-180 ml-0.5 sm:ml-0">▼</span>
            </button>

            <div class="absolute right-0 mt-2 w-56 bg-white border border-slate-100 rounded-2xl shadow-xl opacity-0 invisible translate-y-2 group-hover:opacity-100 group-hover:visible group-hover:translate-y-0 transition-all duration-200 z-50 overflow-hidden">
                <div class="px-4 py-3 bg-slate-50/50 border-b border-slate-100">
                    <span class="block text-[10px] font-bold text-slate-400 uppercase tracking-wide">Signed in as</span>
                    <span class="block text-base font-black text-[#0b1325] truncate mt-0.5"><?= htmlspecialchars($username) ?></span>
                </div>
                <div class="p-1.5 space-y-0.5">
                    <a href="/BloodConnect/public/hospital/profile"
                        class="flex items-center gap-2.5 px-3 py-2 rounded-xl text-sm font-bold text-slate-600 hover:bg-red-50/70 hover:text-[#ce2424] transition-colors">
                        <span class="text-base text-slate-400">🏥</span>
                        Hospital Profile
                    </a>
                </div>
                <div class="p-1.5 border-t border-slate-100 bg-slate-50/30">
                    <a href="/BloodConnect/public/logout"
                        class="flex items-center gap-2.5 px-3 py-2 rounded-xl text-sm font-bold text-[#ce2424] hover:bg-red-100/60 transition-colors">
                        <span class="text-base">🚪</span>
                        Logout System
                    </a>
                </div>
            </div>
        </div>
    </div>
</header>

<script>
    function syncNotificationBadges(count) {
        const topbarBadge = document.getElementById("topbarNotificationBadge");
        const safeCount = Number(count || 0);

        if (topbarBadge) {
            if (safeCount > 0) {
                topbarBadge.textContent = safeCount;
                topbarBadge.classList.remove("hidden");
            } else {
                topbarBadge.textContent = "";
                topbarBadge.classList.add("hidden");
            }
        }

        document.querySelectorAll(".notification-badge").forEach(badge => {
            if (safeCount > 0) {
                badge.textContent = safeCount;
                badge.style.display = "flex";
            } else {
                badge.textContent = "";
                badge.style.display = "none";
            }
        });
    }

    async function updateNotificationBadges() {
        try {
            const res = await fetch("/BloodConnect/public/notification/unread-count");
            const data = await res.json();
            syncNotificationBadges(data.count || 0);
        } catch (error) {
            console.log("Notification update failed", error);
        }
    }

    window.syncNotificationBadges = syncNotificationBadges;
    window.updateNotificationBadges = updateNotificationBadges;

    updateNotificationBadges();
    setInterval(updateNotificationBadges, 10000);

    document.addEventListener("visibilitychange", () => {
        if (!document.hidden) {
            updateNotificationBadges();
        }
    });
</script>
