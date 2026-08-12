<?php

use App\Shared\Helpers\Permission;

if (!isset($pending_count)) {
    $pending_count = 0;
}

$pendingRequestsCount = $pending_count;

?>
<div id="sidebarBackdrop" class="fixed inset-0 bg-slate-900/40 backdrop-blur-sm z-40 hidden opacity-0 transition-opacity duration-300 lg:hidden"></div>

<aside id="sidebarDrawer" class="fixed inset-y-0 left-0 w-72 bg-white border-r border-slate-100 p-5 flex flex-col justify-between z-50 -translate-x-full lg:translate-x-0 lg:sticky lg:top-0 h-screen transition-transform duration-300 ease-in-out shrink-0">
    <div class="space-y-7">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-3 px-2">
                <div class="w-11 h-11 flex items-center justify-center shrink-0">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="w-11 h-11">
                        <path d="M12 2.5C12 2.5 5 9.5 5 14.5C5 18.37 8.13 21.5 12 21.5C15.87 21.5 19 18.37 19 14.5C19 9.5 12 2.5 12 2.5Z" fill="#ce2424" />
                        <path d="M12 17.15l-.38-.35C10.27 15.44 9 14.28 9 12.85c0-1.17.91-2.08 2.08-2.08.66 0 1.29.31 1.7.79.41-.48 1.04-.79 1.7-.79 1.17 0 2.08.91 2.08 2.08 0 1.43-1.27 2.59-2.62 3.96l-.38.34z" fill="#ffffff" />
                    </svg>
                </div>
                <div>
                    <span class="block font-black text-[#0b1325] text-lg tracking-tight leading-tight">BloodConnect</span>
                    <span class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mt-0.5">Hospital Portal</span>
                </div>
            </div>
            <button id="closeSidebarBtn" class="lg:hidden p-2 rounded-xl hover:bg-red-50 text-slate-400 hover:text-red-600 transition-colors">✕</button>
        </div>

        <nav class="space-y-1.5" id="sidebarNav">

            <?php if (Permission::can('dashboard.view')): ?>
                <a href="/BloodConnect/public/hospital/dashboard"
                    class="nav-link flex items-center gap-3.5 px-4 py-3 rounded-xl font-bold text-base text-slate-600 hover:bg-red-50 hover:text-red-600 transition-all duration-200">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6 shrink-0">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 016 3.75h2.25A2.25 2.25 0 0110.5 6v2.25a2.25 2.25 0 01-2.25 2.25H6a2.25 2.25 0 01-2.25-2.25V6zM3.75 15.75A2.25 2.25 0 016 13.5h2.25a2.25 2.25 0 012.25 2.25V18a2.25 2.25 0 01-2.25 2.25H6A2.25 2.25 0 013.75 18v-2.25zM13.5 6a2.25 2.25 0 012.25-2.25H18A2.25 2.25 0 0120.25 6v2.25A2.25 2.25 0 0118 10.5h-2.25a2.25 2.25 0 01-2.25-2.25V6zM13.5 15.75a2.25 2.25 0 012.25-2.25H18a2.25 2.25 0 012.25 2.25V18A2.25 2.25 0 0118 20.25h-2.25A2.25 2.25 0 0113.5 18v-2.25z" />
                    </svg>
                    Dashboard
                </a>
            <?php endif; ?>

            <div class="pt-4 pb-1 px-4">
                <span class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider">Stock Management</span>
            </div>

            <?php if (Permission::can('blood_stock.view')): ?>
                <a href="/BloodConnect/public/hospital/stock"
                    data-section="stock"
                    class="nav-link flex items-center gap-3.5 px-4 py-3 rounded-xl font-bold text-base text-slate-600 hover:bg-red-50 hover:text-red-600 transition-all duration-200">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-6 h-6 shrink-0">
                        <path d="M19.5 10.5h-4.5l-1.5-3.5-2.25 6-1.5-2.5H4.5A1.5 1.5 0 003 12v4.5A1.5 1.5 0 004.5 18h15a1.5 1.5 0 001.5-1.5v-4.5a1.5 1.5 0 00-1.5-1.5zM4.5 6h15a.75.75 0 000-1.5h-15a.75.75 0 000 1.5z" />
                    </svg>
                    Blood Stock
                </a>
            <?php endif; ?>

            <?php if (Permission::can('hospital_request.create')): ?>
                <a href="/BloodConnect/public/hospital/requests/create"
                    data-section="create-request"
                    class="nav-link flex items-center gap-3.5 px-4 py-3 rounded-xl font-bold text-base text-slate-600 hover:bg-red-50 hover:text-red-600 transition-all duration-200">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6 shrink-0">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v6m3-3H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    Request Stock
                </a>
            <?php endif; ?>

            <?php if (Permission::can('hospital_request.view')): ?>
                <a href="/BloodConnect/public/hospital/requests"
                    data-section="requests"
                    class="nav-link flex items-center justify-between px-4 py-3 rounded-xl font-bold text-base text-slate-600 hover:bg-red-50 hover:text-red-600 transition-all duration-200">
                    <div class="flex items-center gap-3.5">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6 shrink-0">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25zM6.75 12h.008v.008H6.75V12zm0 3h.008v.008H6.75V15zm0 3h.008v.008H6.75V18z" />
                        </svg>
                        My Requests
                    </div>
                    <?php if ($pendingRequestsCount > 0): ?>
                        <span class="pending-badge w-5 h-5 rounded-full bg-[#ce2424] text-white text-[10px] font-black flex items-center justify-center"><?= $pendingRequestsCount ?></span>
                    <?php else: ?>
                        <span class="pending-badge w-5 h-5 rounded-full bg-[#ce2424] text-white text-[10px] font-black hidden"></span>
                    <?php endif; ?>
                </a>
            <?php endif; ?>

            <?php if (Permission::can('profile.view')): ?>
                <div class="pt-4 pb-1 px-4">
                    <span class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider">Account</span>
                </div>

                <a href="/BloodConnect/public/hospital/profile"
                    data-section="profile"
                    class="nav-link flex items-center gap-3.5 px-4 py-3 rounded-xl font-bold text-base text-slate-600 hover:bg-red-50 hover:text-red-600 transition-all duration-200">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6 shrink-0">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z" />
                    </svg>
                    Hospital Profile
                </a>

                <?php if (Permission::can('notification.view')): ?>
                    <a href="/BloodConnect/public/hospital/notifications"
                        class="nav-link flex items-center justify-between px-4 py-3 rounded-xl font-bold text-base text-slate-600 hover:bg-red-50 hover:text-red-600 transition-all duration-200">
                        <div class="flex items-center gap-3.5">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6 shrink-0">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75v-.7V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0" />
                            </svg>
                            Notifications
                        </div>
                        <span class="notification-badge inline-flex items-center justify-center min-w-[20px] h-5 px-1 rounded-full bg-[#ce2424] text-white text-[10px] font-black leading-none shadow-sm hidden">0</span>
                    </a>
                <?php endif; ?>
            <?php endif; ?>

        </nav>
    </div>
</aside>

<script>
    document.addEventListener("DOMContentLoaded", () => {
        const toggleBtn = document.getElementById("mobileSidebarToggle");
        const closeBtn = document.getElementById("closeSidebarBtn");
        const drawer = document.getElementById("sidebarDrawer");
        const backdrop = document.getElementById("sidebarBackdrop");

        function openSidebar() {
            backdrop.classList.remove("hidden");
            setTimeout(() => {
                backdrop.classList.add("opacity-100");
                drawer.classList.remove("-translate-x-full");
            }, 20);
        }

        function closeSidebar() {
            backdrop.classList.remove("opacity-100");
            drawer.classList.add("-translate-x-full");
            setTimeout(() => {
                backdrop.classList.add("hidden");
            }, 300);
        }

        if (toggleBtn) toggleBtn.addEventListener("click", openSidebar);
        if (closeBtn) closeBtn.addEventListener("click", closeSidebar);
        if (backdrop) backdrop.addEventListener("click", closeSidebar);

        const navLinks = document.querySelectorAll('.nav-link');

        function highlightNavLink(link) {
            if (!link) return;
            link.classList.remove('text-slate-600', 'hover:bg-red-50', 'hover:text-red-600');
            link.classList.add('bg-[#ce2424]', 'text-white');

            const badge = link.querySelector('.notification-badge');
            if (badge) {
                badge.classList.remove('bg-[#ce2424]', 'text-white');
                badge.classList.add('bg-white', 'text-[#ce2424]');
            }
        }

        function clearNavActive() {
            navLinks.forEach(link => {
                link.classList.remove('bg-[#ce2424]', 'text-white');

                const badge = link.querySelector('.notification-badge');
                if (badge) {
                    badge.classList.add('bg-[#ce2424]', 'text-white');
                    badge.classList.remove('bg-white', 'text-[#ce2424]');
                }
            });
        }

        function updateActiveNav() {
            clearNavActive();

            const currentPath = window.location.pathname;

            navLinks.forEach(link => {
                const linkHref = link.getAttribute('href');
                if (!linkHref) return;

                const plainHref = linkHref.split('#')[0];

                if (linkHref.includes('#')) {
                    return;
                }

                if (currentPath === plainHref || currentPath.endsWith(plainHref)) {
                    highlightNavLink(link);
                }
            });

            if (currentPath.includes('/hospital/requests') && !currentPath.includes('/hospital/requests/create')) {
                highlightNavLink(document.querySelector('.nav-link[data-section="requests"]'));
            }
        }

        updateActiveNav();
        window.addEventListener('hashchange', updateActiveNav);
    });

    async function updateNotificationBadges() {
        try {
            const res = await fetch("/BloodConnect/public/notification/unread-count");
            const data = await res.json();
            const badges = document.querySelectorAll(".notification-badge");

            badges.forEach(badge => {
                if (data.count > 0) {
                    badge.style.display = "inline-flex";
                    badge.textContent = data.count;
                } else {
                    badge.style.display = "none";
                }
            });
        } catch (error) {
            console.log("Notification update failed", error);
        }
    }

    updateNotificationBadges();
    setInterval(updateNotificationBadges, 10000);
</script>
