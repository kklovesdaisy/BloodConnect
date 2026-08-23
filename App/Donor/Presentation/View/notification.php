<?php
?>

<style>
    @keyframes smoothFeedUp {
        from {
            opacity: 0;
            transform: translateY(16px) scale(0.99);
        }

        to {
            opacity: 1;
            transform: translateY(0) scale(1);
        }
    }

    .animate-feed-reveal {
        animation: smoothFeedUp 0.45s cubic-bezier(0.16, 1, 0.3, 1) forwards;
    }

    .feed-scrollbar::-webkit-scrollbar {
        width: 6px;
    }

    .feed-scrollbar::-webkit-scrollbar-track {
        background: transparent;
    }

    .feed-scrollbar::-webkit-scrollbar-thumb {
        background-color: #e2e8f0;
        border-radius: 20px;
    }

    .feed-scrollbar::-webkit-scrollbar-thumb:hover {
        background-color: #cbd5e1;
    }
</style>

<div class="max-w-7xl mx-auto w-full px-4 sm:px-8 lg:px-12 py-6 lg:py-10 space-y-6 select-none flex flex-col justify-center">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 border-b border-slate-100 pb-6">
        <div>
            <div class="flex items-center gap-2 mb-1">
                <span class="block text-xs font-black text-red-600 uppercase tracking-widest">
                    Donor Dashboard
                </span>
                <span class="w-1.5 h-1.5 rounded-full bg-red-500 animate-pulse"></span>
            </div>
            <h1 class="text-2xl font-black text-slate-900 tracking-tight sm:text-3xl lg:text-4xl">
                Notifications Hub
            </h1>
            <p class="text-xs sm:text-sm font-medium text-slate-500 mt-0.5">
                Stay informed about donor activity, blood requests, and important updates.
            </p>
        </div>

        <button id="markAllReadBtn"
            class="inline-flex items-center justify-center gap-2 px-5 py-2.5 rounded-xl border border-slate-200 text-xs font-bold text-slate-700 bg-white hover:bg-slate-50 hover:text-red-600 hover:border-red-200 shadow-sm transition-all duration-200 active:scale-[0.98] self-start sm:self-center group flex-shrink-0">
            <svg class="w-3.5 h-3.5 text-slate-400 group-hover:text-red-500 transition-colors" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
            </svg>
            Mark all as read
        </button>
    </div>

    <div class="bg-white border border-slate-200 rounded-2xl shadow-sm overflow-hidden w-full">
        <div class="p-4 sm:p-6 max-h-[750px] overflow-y-auto space-y-4 feed-scrollbar bg-slate-50/30" id="notificationsWrapper">
            <?php if (!empty($notifications)): ?>
                <?php foreach ($notifications as $index => $notification): ?>
                    <?php
                    $isUnread = empty($notification['is_read']);
                    $notifType = htmlspecialchars(strtolower($notification['type'] ?? 'info'));

                    $cardClass = $isUnread
                        ? 'bg-white border-slate-200 shadow-[0_4px_12px_-6px_rgba(0,0,0,0.05)] hover:border-slate-300 hover:shadow-md'
                        : 'bg-white/60 border-slate-100 opacity-70 hover:opacity-100';

                    switch ($notifType) {
                        case 'request':
                            $iconBg = 'bg-rose-50 text-rose-600 border-rose-100';
                            $iconPath = 'M12 2.5C12 2.5 5 9.5 5 14.5C5 18.37 8.13 21.5 12 21.5C15.87 21.5 19 18.37 19 14.5C19 9.5 12 2.5 12 2.5Z';
                            break;
                        case 'approval':
                            $iconBg = 'bg-blue-50 text-blue-600 border-blue-100';
                            $iconPath = 'M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0z M4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z';
                            break;
                        case 'reminder':
                            $iconBg = 'bg-amber-50 text-amber-600 border-amber-100';
                            $iconPath = 'M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z';
                            break;
                        default:
                            $iconBg = 'bg-emerald-50 text-emerald-600 border-emerald-100';
                            $iconPath = 'M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z';
                            break;
                    }
                    ?>

                    <div class="notification-card group opacity-0 animate-feed-reveal flex items-start justify-between gap-5 py-5 px-6 rounded-2xl border transition-all duration-300 cursor-pointer active:scale-[0.995] <?= $cardClass ?>"
                        style="animation-delay: <?= $index * 30 ?>ms"
                        data-id="<?= $notification['notification_id'] ?>">
                        <div class="flex items-start gap-5 flex-1 min-w-0">
                            <div class="w-12 h-12 rounded-xl <?= $iconBg ?> border flex items-center justify-center flex-shrink-0 shadow-sm">
                                <svg class="w-5 h-5" viewBox="0 0 24 24" fill="currentColor">
                                    <path d="<?= $iconPath ?>"></path>
                                </svg>
                            </div>
                            <div class="space-y-1 flex-1 min-w-0 mt-0.5">
                                <h3 class="font-bold text-slate-900 tracking-tight text-sm sm:text-base truncate">
                                    <?= htmlspecialchars($notification['title']) ?>
                                </h3>
                                <p class="text-xs sm:text-sm text-slate-500 leading-relaxed font-medium break-words">
                                    <?= $notification['message'] ?>
                                </p>
                            </div>
                        </div>

                        <div class="flex flex-col items-end justify-between self-stretch flex-shrink-0 pl-2">
                            <span class="text-[10px] sm:text-xs font-bold text-slate-400 tracking-wide whitespace-nowrap">
                                <?= htmlspecialchars($notification['time_ago'] ?? ($notification['created_at'] ?? '')) ?>
                            </span>
                            <div class="flex items-center gap-2 mt-1">
                                <button class="delete-notif-btn p-1 rounded-lg text-slate-300 hover:text-red-500 hover:bg-red-50 transition-all duration-200 opacity-0 group-hover:opacity-100"
                                    onclick="event.stopPropagation(); deleteNotification(this, <?= (int)$notification['notification_id'] ?>)"
                                    title="Delete notification">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" />
                                    </svg>
                                </button>
                                <span class="status-indicator w-2.5 h-2.5 rounded-full transition-all duration-300 shadow-sm mb-0.5
                                    <?= $isUnread ? 'bg-red-500 ring-4 ring-red-500/20' : 'bg-slate-300/70' ?>">
                                </span>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="text-center py-24 bg-white border border-dashed border-slate-200 rounded-xl p-6">
                    <span class="text-3xl block mb-2">🎉</span>
                    <h3 class="text-base font-bold text-slate-900">All feeds clear!</h3>
                    <p class="mt-1 text-xs sm:text-sm font-medium text-slate-400 max-w-xs mx-auto">
                        There are no active alerts requiring your attention right now.
                    </p>
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

        fetch("/BloodConnect/public/notification/unread-count")
            .then(res => res.json())
            .then(data => refreshBadges(data.count))
            .catch(() => {});

        cards.forEach(card => {
            card.addEventListener("click", async () => {
                if (card.classList.contains("notification-read")) return;
                const id = card.dataset.id;

                try {
                    const response = await fetch("/BloodConnect/public/notification/mark-read", {
                        method: "POST",
                        headers: {
                            "Content-Type": "application/x-www-form-urlencoded"
                        },
                        body: "notification_id=" + encodeURIComponent(id)
                    });

                    const result = await response.json();
                    if (result.success) {
                        transformCardToRead(card);
                        card.classList.add("notification-read");
                        refreshBadges(result.count);
                    }
                } catch (err) {
                    console.error(err);
                }
            });
        });

        if (markAllBtn) {
            markAllBtn.addEventListener("click", async () => {
                try {
                    const response = await fetch("/BloodConnect/public/notification/mark-all-read", {
                        method: "POST"
                    });

                    const result = await response.json();
                    if (result.success) {
                        cards.forEach(card => {
                            transformCardToRead(card);
                            card.classList.add("notification-read");
                        });
                        refreshBadges(result.count);
                    }
                } catch (err) {
                    console.error(err);
                }
            });
        }
    });

    function refreshBadges(count) {
        const safeCount = Number(count || 0);

        if (window.syncNotificationBadges) {
            window.syncNotificationBadges(safeCount);
            return;
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
                refreshBadges(result.count);
            }
        } catch (err) {
            console.error(err);
        }
    }
</script>