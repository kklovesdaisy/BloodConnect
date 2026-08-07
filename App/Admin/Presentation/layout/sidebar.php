<?php

use App\Shared\Helpers\Permission;
use App\Shared\Infrastructure\Database\Database;

$pendingBloodRequests = 0;
try {
    $db = Database::getConnection();
    $masterRepo = new \App\Shared\Infrastructure\Persistence\MasterDataRepository();
    $pendingStatus = $masterRepo->getId('REQUEST_STATUS', 'PENDING') ?? 7;

    $stmt = $db->prepare("SELECT COUNT(*) FROM blood_requests WHERE status = ?");
    $stmt->execute([$pendingStatus]);
    $totalPending = (int)$stmt->fetchColumn();

    $viewedIds = $_SESSION['admin_viewed_requests'] ?? [];
    $viewedIds = is_array($viewedIds) ? array_map('intval', $viewedIds) : [];

    $stmt2 = $db->prepare("SELECT request_id FROM blood_requests WHERE status = ?");
    $stmt2->execute([$pendingStatus]);
    $allPendingIds = array_map('intval', $stmt2->fetchAll(\PDO::FETCH_COLUMN) ?: []);
    $viewedCount = count(array_intersect($allPendingIds, $viewedIds));

    $pendingBloodRequests = max(0, $totalPending - $viewedCount);
} catch (Throwable $e) {
    $pendingBloodRequests = 0;
}
?>

<div id="sidebarBackdrop" class="fixed inset-0 bg-slate-900/40 backdrop-blur-sm z-40 hidden opacity-0 transition-opacity duration-300 lg:hidden"></div>

<aside id="sidebarDrawer" class="fixed inset-y-0 left-0 w-72 bg-white border-r border-slate-100 p-5 flex flex-col justify-between z-50 -translate-x-full lg:translate-x-0 lg:sticky lg:top-0 h-screen transition-transform duration-300 ease-in-out shrink-0">
    <div class="space-y-7">
        <!-- Brand Header -->
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-3 px-2">
                <!-- Custom SVG Logo -->
                <div class="w-11 h-11 flex items-center justify-center shrink-0">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="w-11 h-11">
                        <!-- Red Blood Drop Shape -->
                        <path d="M12 2.5C12 2.5 5 9.5 5 14.5C5 18.37 8.13 21.5 12 21.5C15.87 21.5 19 18.37 19 14.5C19 9.5 12 2.5 12 2.5Z" fill="#ce2424" />
                        <!-- White Heart Cutout perfectly centered inside -->
                        <path d="M12 17.15l-.38-.35C10.27 15.44 9 14.28 9 12.85c0-1.17.91-2.08 2.08-2.08.66 0 1.29.31 1.7.79.41-.48 1.04-.79 1.7-.79 1.17 0 2.08.91 2.08 2.08 0 1.43-1.27 2.59-2.62 3.96l-.38.34z" fill="#ffffff" />
                    </svg>
                </div>
                <div>
                    <span class="block font-black text-[#0b1325] text-lg tracking-tight leading-tight">BloodConnect</span>
                    <span class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mt-0.5">Admin Dashboard</span>
                </div>
            </div>
            <button id="closeSidebarBtn" class="lg:hidden p-2 rounded-xl hover:bg-rose-50/30 text-slate-400 hover:text-red-600 transition-colors">✕</button>
        </div>

        <!-- Navigation Links -->
        <nav class="space-y-1.5" id="sidebarNav">

            <a href="/BloodConnect/public/admin/dashboard"
                class="nav-link flex items-center gap-3.5 px-4 py-3 rounded-xl font-bold text-base text-slate-600 hover:bg-red-50 hover:text-red-600 transition-all duration-200">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6 shrink-0">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 016 3.75h2.25A2.25 2.25 0 0110.5 6v2.25a2.25 2.25 0 01-2.25 2.25H6a2.25 2.25 0 01-2.25-2.25V6zM3.75 15.75A2.25 2.25 0 016 13.5h2.25a2.25 2.25 0 012.25 2.25V18a2.25 2.25 0 01-2.25 2.25H6A2.25 2.25 0 013.75 18v-2.25zM13.5 6a2.25 2.25 0 012.25-2.25H18A2.25 2.25 0 0120.25 6v2.25A2.25 2.25 0 0118 10.5h-2.25a2.25 2.25 0 01-2.25-2.25V6zM13.5 15.75a2.25 2.25 0 012.25-2.25H18a2.25 2.25 0 012.25 2.25V18A2.25 2.25 0 0118 20.25h-2.25A2.25 2.25 0 0113.5 18v-2.25z" />
                </svg>
                Dashboard
            </a>

            <!-- Management Subheading Header Reference from image_678b1d.png -->
            <div class="pt-4 pb-1 px-4">
                <span class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider">Management</span>
            </div>

            <a href="/BloodConnect/public/admin/user-management"
                data-section="user-management"
                class="nav-link flex items-center gap-3.5 px-4 py-3 rounded-xl font-bold text-base text-slate-600 hover:bg-red-50 hover:text-red-600 transition-all duration-200">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6 shrink-0">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z" />
                </svg>
                User Management
            </a>

            <a href="/BloodConnect/public/admin/donor-management"
                data-section="donor-management"
                class="nav-link flex items-center gap-3.5 px-4 py-3 rounded-xl font-bold text-base text-slate-600 hover:bg-red-50 hover:text-red-600 transition-all duration-200">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-6 h-6 shrink-0">
                    <path d="M12 2.5C12 2.5 5 9.5 5 14.5C5 18.37 8.13 21.5 12 21.5C15.87 21.5 19 18.37 19 14.5C19 9.5 12 2.5 12 2.5Z" />
                </svg>
                Donor Management
            </a>

            <a href="/BloodConnect/public/admin/blood-requests"
                data-section="blood-requests"
                class="nav-link flex items-center justify-between px-4 py-3 rounded-xl font-bold text-base text-slate-600 hover:bg-red-50 hover:text-red-600 transition-all duration-200">
                <div class="flex items-center gap-3.5">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6 shrink-0">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25zM6.75 12h.008v.008H6.75V12zm0 3h.008v.008H6.75V15zm0 3h.008v.008H6.75V18z" />
                    </svg>
                    Blood Requests
                </div>
                <?php if ($pendingBloodRequests > 0): ?>
                    <span class="pending-badge w-5 h-5 rounded-full bg-[#ce2424] text-white text-[10px] font-black flex items-center justify-center"><?= $pendingBloodRequests ?></span>
                <?php else: ?>
                    <span class="pending-badge w-5 h-5 rounded-full bg-[#ce2424] text-white text-[10px] font-black hidden"></span>
                <?php endif; ?>
            </a>

            <a href="/BloodConnect/public/admin/donor-management?filter=available"
                class="nav-link flex items-center gap-3.5 px-4 py-3 rounded-xl font-bold text-base text-slate-600 hover:bg-red-50 hover:text-red-600 transition-all duration-200 ">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6 shrink-0">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                Available Donors
            </a>

            <a href="/BloodConnect/public/admin/donation-history"
                data-section="donation-history"
                class="nav-link flex items-center gap-3.5 px-4 py-3 rounded-xl font-bold text-base text-slate-600 hover:bg-red-50 hover:text-red-600 transition-all duration-200">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6 shrink-0">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                Donation History
            </a>

            <a href="/BloodConnect/public/admin/blood-stock"
                data-section="blood-stock"
                class="nav-link flex items-center gap-3.5 px-4 py-3 rounded-xl font-bold text-base text-slate-600 hover:bg-red-50 hover:text-red-600 transition-all duration-200">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-6 h-6 shrink-0">
                    <path d="M19.5 10.5h-4.5l-1.5-3.5-2.25 6-1.5-2.5H4.5A1.5 1.5 0 003 12v4.5A1.5 1.5 0 004.5 18h15a1.5 1.5 0 001.5-1.5v-4.5a1.5 1.5 0 00-1.5-1.5zM4.5 6h15a.75.75 0 000-1.5h-15a.75.75 0 000 1.5z" />
                </svg>
                Blood Stock
            </a>

            <a href="/BloodConnect/public/admin/reports"
                data-section="reports"
                class="nav-link flex items-center gap-3.5 px-4 py-3 rounded-xl font-bold text-base text-slate-600 hover:bg-red-50 hover:text-red-600 transition-all duration-200">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6 shrink-0">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z" />
                </svg>
                Reports
            </a>

            <!-- Communication Subheading Header Reference from image_678b1d.png -->
            <div class="pt-4 pb-1 px-4">
                <span class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider">Communication</span>
            </div>

            <a href="/BloodConnect/public/admin/notifications"
                class="nav-link flex items-center justify-between px-4 py-3 rounded-xl font-bold text-base text-slate-600 hover:bg-red-50 hover:text-red-600 transition-all duration-200">

                <div class="flex items-center gap-3.5">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6 shrink-0">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75v-.7V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0" />
                    </svg>
                    Notifications
                </div>



                <?php if (!empty($unreadCount) && $unreadCount > 0): ?>
                    <span class="notification-badge inline-flex items-center justify-center min-w-[20px] h-5 px-1 rounded-full bg-[#ce2424] text-white text-[10px] font-black leading-none shadow-sm"><?= $unreadCount ?></span>
                <?php else: ?>
                    <span class="notification-badge inline-flex items-center justify-center min-w-[20px] h-5 px-1 rounded-full bg-[#ce2424] text-white text-[10px] font-black leading-none shadow-sm hidden"></span>
                <?php endif; ?>
            </a>

            <a href="/BloodConnect/public/admin/roles"
                data-section="roles"
                class="nav-link flex items-center justify-between px-4 py-3 rounded-xl font-bold text-base text-slate-600 hover:bg-red-50 hover:text-red-600 transition-all duration-200">
                <div class="flex items-center gap-3.5">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6 shrink-0">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.324.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 011.37.49l1.296 2.247a1.125 1.125 0 01-.26 1.431l-1.003.827c-.293.24-.438.613-.431.992a6.759 6.759 0 010 .255c-.007.378.138.75.43.99l1.005.828c.424.35.534.954.26 1.43l-1.298 2.247a1.125 1.125 0 01-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.57 6.57 0 01-.22.128c-.331.183-.581.495-.644.869l-.213 1.28c-.09.543-.56.941-1.11.941h-2.594c-.55 0-1.02-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 01-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 01-1.369-.49l-1.297-2.247a1.125 1.125 0 01.26-1.431l1.004-.827c.292-.24.437-.613.43-.992a6.932 6.932 0 010-.255c.007-.378-.138-.75-.43-.99l-1.004-.828a1.125 1.125 0 01-.26-1.43l1.297-2.247a1.125 1.125 0 011.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.087.22-.128.332-.183.582-.495.644-.869l.214-1.281z" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                    Settings
                </div>
            </a>

        </nav>
    </div>

    <!-- Footer Actions -->
    <!-- <div class="border-t border-slate-100 pt-4">
        <a href="/BloodConnect/public/logout" class="block">
            <button class="w-full flex items-center gap-3.5 px-4 py-3 rounded-xl font-bold text-base text-slate-600 hover:bg-red-50 hover:text-red-600 transition-all duration-200">
                <span class="text-xl">🚪</span>
                Logout
            </button>
        </a>
    </div> -->
</aside>

<!-- Active Route & Sidebar Toggle JavaScript -->
<script>
    document.addEventListener("DOMContentLoaded", () => {
        // --- 1. SIDEBAR MOBILE TOGGLE INTERACTION LOGIC ---
        const toggleBtn = document.getElementById("mobileSidebarToggle"); // Target menu icon from topbar
        const closeBtn = document.getElementById("closeSidebarBtn");
        const drawer = document.getElementById("sidebarDrawer");
        const backdrop = document.getElementById("sidebarBackdrop");

        function openSidebar() {
            backdrop.classList.remove("hidden");
            // Allow layout engine to parse block removal before animating opacity
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
            }, 300); // Wait for transition duration to end
        }

        // Event Listeners for drawer actions
        if (toggleBtn) toggleBtn.addEventListener("click", openSidebar);
        if (closeBtn) closeBtn.addEventListener("click", closeSidebar);
        if (backdrop) backdrop.addEventListener("click", closeSidebar);


        // --- 2. ACTIVE ROUTE CHECKER ---
        const currentUrl = new URL(window.location.href);
        const currentPath = currentUrl.pathname;
        const currentSearch = currentUrl.search;
        const navLinks = document.querySelectorAll('.nav-link');

        navLinks.forEach(link => {
            const linkHref = link.getAttribute('href');
            if (!linkHref) return;

            const linkUrl = new URL(linkHref, window.location.origin);
            const samePath = currentPath === linkUrl.pathname || currentPath.endsWith(linkUrl.pathname);
            const hasAvailableFilter = currentSearch.includes('filter=available');
            const isAvailableLink = linkUrl.searchParams.get('filter') === 'available';
            const shouldActivate = samePath && (isAvailableLink ? hasAvailableFilter : !hasAvailableFilter);

            if (shouldActivate) {
                link.classList.remove('text-slate-600', 'hover:bg-red-50', 'hover:text-red-600');
                link.classList.add('bg-[#ce2424]', 'text-white');

                const badge = link.querySelector('.notification-badge, .pending-badge');
                if (badge) {
                    badge.classList.remove('bg-[#ce2424]', 'text-white');
                    badge.classList.add('bg-white', 'text-[#ce2424]');
                }
            }
        });

        // --- 2b. SECTION OWNERSHIP (shared view/edit pages belong to a source section) ---
        const fromParam = currentUrl.searchParams.get('from');
        let ownedSection = null;
        if (currentPath.endsWith('/admin/user/view') || currentPath.endsWith('/admin/user/edit') ||
            currentPath.endsWith('/admin/user/delete') || currentPath.endsWith('/admin/user/update')) {
            ownedSection = fromParam === 'donor' ? 'donor-management' : 'user-management';
        } else if (currentPath.endsWith('/admin/blood-request/view') || currentPath.endsWith('/admin/blood-request/edit')) {
            ownedSection = 'blood-requests';
        } else if (currentPath.startsWith('/BloodConnect/public/admin/reports')) {
            ownedSection = 'reports';
        } else if (currentPath.startsWith('/BloodConnect/public/admin/roles')) {
            ownedSection = 'roles';
        }

        if (ownedSection) {
            const ownerLink = document.querySelector('.nav-link[data-section="' + ownedSection + '"]');
            if (ownerLink) {
                ownerLink.classList.remove('text-slate-600', 'hover:bg-red-50', 'hover:text-red-600');
                ownerLink.classList.add('bg-[#ce2424]', 'text-white');
            }
        }
    });

    // --- 3. LIVE NOTIFICATION BADGE CONTROLLER ---
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

    // --- 4. PENDING BLOOD REQUESTS POLLING ---
    async function updatePendingBloodRequests() {
        try {
            const res = await fetch("/BloodConnect/public/admin/pending-blood-requests-count");
            const data = await res.json();
            const badges = document.querySelectorAll(".pending-badge");
            badges.forEach(badge => {
                if (data.count > 0) {
                    badge.classList.remove('hidden');
                    badge.textContent = data.count;
                } else {
                    badge.classList.add('hidden');
                }
            });
        } catch (error) {
            console.log("Pending count update failed", error);
        }
    }

    updatePendingBloodRequests();
    setInterval(updatePendingBloodRequests, 15000);
</script>