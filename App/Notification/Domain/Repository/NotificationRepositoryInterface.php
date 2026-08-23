<?php

namespace App\Notification\Domain\Repository;

interface NotificationRepositoryInterface
{
    public function create(int $userId, string $title, string $message, string $type): bool;
    public function findByUserId(int $userId): array;
    public function markAsRead(int $notificationId): bool;
    public function markAllAsRead(int $userId): bool;
    public function getUnreadCount(int $userId): int;
    public function deleteNotification(int $notificationId, int $userId): bool;
    public function getAdmins(): array;

    /**
     * Send a notification to every admin user, optionally excluding one user.
     */
    public function notifyAdmins(string $title, string $message, string $type, ?int $exceptUserId = null): void;
}
