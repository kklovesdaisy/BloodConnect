<?php
?>
<style>
    @keyframes smoothFeedUp { from { opacity: 0; transform: translateY(16px) scale(0.99); } to { opacity: 1; transform: translateY(0) scale(1); } }
    .animate-feed-reveal { animation: smoothFeedUp .45s cubic-bezier(.16,1,.3,1) forwards; }
</style>

<div class="max-w-7xl mx-auto w-full px-4 sm:px-8 lg:px-12 py-6 lg:py-10 space-y-6 select-none">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 border-b border-slate-100 pb-6">
        <div>
            <div class="flex items-center gap-2 mb-1">
                <span class="block text-xs font-black text-red-600 uppercase tracking-widest">Hospital Portal</span>
                <span class="w-1.5 h-1.5 rounded-full bg-red-500 animate-pulse"></span>
            </div>
            <h1 class="text-2xl font-black text-slate-900 tracking-tight sm:text-3xl">Notifications Hub</h1>
            <p class="text-xs sm:text-sm font-medium text-slate-500 mt-0.5">Stock request updates and important notices.</p>
        </div>

        <button id="markAllReadBtn"
            class="inline-flex items-center justify-center gap-2 px-5 py-2.5 rounded-xl border border-slate-200 text-xs font-bold text-slate-700 bg-white hover:bg-slate-50 hover:text-red-600 hover:border-red-200 shadow-sm transition-all duration-200 active:scale-[0.98] self-start sm:self-center group flex-shrink-0">
            Mark all as read
        </button>
    </div>

    <div class="bg-white border border-slate-200 rounded-2xl shadow-sm overflow-hidden w-full">
        <div class="p-4 sm:p-6 space-y-4 bg-slate-50/30" id="notificationsWrapper">
            <?php if (!empty($notifications)): ?>
                <?php foreach ($notifications as $index => $notification): ?>
                    <?php
                    $isUnread = empty($notification['is_read']);
                    $cardClass = $isUnread
                        ? 'bg-white border-slate-200 shadow-[0_4px_12px_-6px_rgba(0,0,0,0.05)] hover:border-slate-300 hover:shadow-md'
                        : 'bg-white/60 border-slate-100 opacity-70 hover:opacity-100';
                    ?>
                    <div class="notification-card group opacity-0 animate-feed-reveal flex items-start justify-between gap-5 py-5 px-6 rounded-2xl border transition-all duration-300 cursor-pointer active:scale-[0.995] <?= $cardClass ?>"
                        style="animation-delay: <?= $index * 30 ?>ms"
                        data-id="<?= htmlspecialchars((string)($notification['notification_id'] ?? '')) ?>">
                        <div class="flex items-start gap-5 flex-1 min-w-0">
                            <div class="w-12 h-12 rounded-xl bg-red-50 text-red-600 border border-red-100 flex items-center justify-center flex-shrink-0 shadow-sm">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-5 h-5"><path d="M12 2.5C12 2.5 5 9.5 5 14.5C5 18.37 8.13 21.5 12 21.5C15.87 21.5 19 18.37 19 14.5C19 9.5 12 2.5 12 2.5Z" /></svg>
                            </div>
                            <div class="space-y-1 flex-1 min-w-0 mt-0.5">
                                <h3 class="font-bold text-slate-900 tracking-tight text-sm sm:text-base truncate"><?= htmlspecialchars((string)($notification['title'] ?? '')) ?></h3>
                                <p class="text-xs sm:text-sm text-slate-500 leading-relaxed font-medium break-words"><?= htmlspecialchars((string)($notification['message'] ?? '')) ?></p>
                            </div>
                        </div>
                        <div class="flex flex-col items-end justify-between self-stretch flex-shrink-0 pl-2">
                            <span class="text-[10px] sm:text-xs font-bold text-slate-400 tracking-wide whitespace-nowrap"><?= htmlspecialchars((string)($notification['time_ago'] ?? ($notification['created_at'] ?? ''))) ?></span>
                            <div class="flex items-center gap-2 mt-1">
                                <button class="delete-notif-btn p-1 rounded-lg text-slate-300 hover:text-red-500 hover:bg-red-50 transition-all duration-200 opacity-0 group-hover:opacity-100"
                                    onclick="event.stopPropagation(); deleteNotification(this, <?= (int)$notification['notification_id'] ?>)"
                                    title="Delete notification">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" />
                                    </svg>
                                </button>
                                <span class="status-indicator w-2.5 h-2.5 rounded-full transition-all duration-300 shadow-sm mb-0.5 <?= $isUnread ? 'bg-red-500 ring-4 ring-red-500/20' : 'bg-slate-300/70' ?>"></span>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="text-center py-24 bg-white border border-dashed border-slate-200 rounded-xl p-6">
                    <span class="text-3xl block mb-2">🎉</span>
                    <h3 class="text-base font-bold text-slate-900">All feeds clear!</h3>
                    <p class="mt-1 text-xs sm:text-sm font-medium text-slate-400 max-w-xs mx-auto">There are no active alerts requiring your attention right now.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
    document.addEventListener("DOMContentLoaded", () => {
        const markAllBtn = document.getElementById("markAllReadBtn");
        const cards = document.querySelectorAll(".notification-card");

        const transformCardToRead = (card) => {
            const indicator = card.querySelector(".status-indicator");
            if (indicator) {
                indicator.classList.remove("bg-red-500", "ring-4", "ring-red-500/20");
                indicator.classList.add("bg-slate-300/70");
            }
            card.classList.remove("bg-white", "border-slate-200/80", "shadow-[0_4px_12px_-6px_rgba(0,0,0,0.05)]", "hover:border-slate-300", "hover:shadow-md");
            card.classList.add("bg-white/60", "border-slate-100", "opacity-70", "hover:opacity-100");
        };

        cards.forEach(card => {
            card.addEventListener("click", async () => {
                if (card.classList.contains("notification-read")) return;
                const id = card.dataset.id;

                try {
                    const response = await fetch("/BloodConnect/public/notification/mark-read", {
                        method: "POST",
                        headers: { "Content-Type": "application/x-www-form-urlencoded" },
                        body: "notification_id=" + encodeURIComponent(id)
                    });
                    const result = await response.json();
                    if (result.success) {
                        transformCardToRead(card);
                        card.classList.add("notification-read");
                        if (window.syncNotificationBadges) window.syncNotificationBadges(result.count);
                    }
                } catch (err) {
                    console.error(err);
                }
            });
        });

        if (markAllBtn) {
            markAllBtn.addEventListener("click", async () => {
                try {
                    const response = await fetch("/BloodConnect/public/notification/mark-all-read", { method: "POST" });
                    const result = await response.json();
                    if (result.success) {
                        cards.forEach(card => {
                            transformCardToRead(card);
                            card.classList.add("notification-read");
                        });
                        if (window.syncNotificationBadges) window.syncNotificationBadges(result.count);
                    }
                } catch (err) {
                    console.error(err);
                }
            });
        }
    });

    async function deleteNotification(btn, id) {
        try {
            const response = await fetch("/BloodConnect/public/notification/delete", {
                method: "POST",
                headers: { "Content-Type": "application/x-www-form-urlencoded" },
                body: "notification_id=" + encodeURIComponent(id)
            });
            const result = await response.json();
            if (result.success) {
                const card = btn.closest(".notification-card");
                if (card) {
                    card.style.transition = "opacity 0.3s, transform 0.3s";
                    card.style.opacity = "0";
                    card.style.transform = "translateX(20px) scale(0.97)";
                    setTimeout(() => card.remove(), 300);
                }
                if (window.syncNotificationBadges) window.syncNotificationBadges(result.count);
            }
        } catch (err) {
            console.error(err);
        }
    }
</script>
