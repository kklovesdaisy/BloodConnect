<?php

namespace App\Notification\Infrastructure\Persistence;

use App\Shared\Infrastructure\Database\Database;
use PDO;
use PDOException;
use Exception;

class NotificationRepository implements \App\Notification\Domain\Repository\NotificationRepositoryInterface
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    /**
     * Create notification
     */
    public function create(
        int $userId,
        string $title,
        string $message,
        string $type
    ): bool {

        if ($userId <= 0) {
            return false;
        }

        if (!$this->userExists($userId)) {
            error_log("Skipping notification for missing user_id={$userId}");
            return false;
        }

        $typeId = $this->getTypeId($type);

        try {
            $stmt = $this->db->prepare("
                INSERT INTO notifications
                (
                    user_id,
                    notification_type_id,
                    title,
                    message,
                    type
                )
                VALUES
                (
                    :user_id,
                    :notification_type_id,
                    :title,
                    :message,
                    :type
                )
            ");

            return $stmt->execute([
                ':user_id' => $userId,
                ':notification_type_id' => $typeId,
                ':title' => $title,
                ':message' => $message,
                ':type' => strtolower($type)
            ]);
        } catch (PDOException $e) {
            if (str_contains($e->getMessage(), 'foreign key constraint fails')) {
                error_log("Skipping notification for invalid user_id={$userId}: {$e->getMessage()}");
                return false;
            }

            throw $e;
        }
    }

    private function userExists(int $userId): bool
    {
        $stmt = $this->db->prepare("SELECT 1 FROM users WHERE user_id = ? LIMIT 1");
        $stmt->execute([$userId]);

        return (bool)$stmt->fetchColumn();
    }

    /**
     * Convert notification code to master_data ID
     */
    private function getTypeId(string $type): int
    {
        $stmt = $this->db->prepare("
            SELECT id
            FROM master_data
            WHERE category = 'NOTIFICATION_TYPE'
              AND code = ?
            LIMIT 1
        ");

        $stmt->execute([$type]);

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            throw new Exception("Invalid notification type: {$type}");
        }

        return (int)$row['id'];
    }

    /**
     * Get notifications for a user
     */
    public function findByUserId(int $userId): array
    {
        $stmt = $this->db->prepare("
            SELECT
                n.*,
                md.code AS notification_code,
                md.label AS notification_label
            FROM notifications n
            LEFT JOIN master_data md
                ON md.id = n.notification_type_id
            WHERE n.user_id = ?
            ORDER BY n.created_at DESC
        ");

        $stmt->execute([$userId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Mark notification as read
     */
    public function markAsRead(int $notificationId): bool
    {
        $stmt = $this->db->prepare("
            UPDATE notifications
            SET is_read = 1
            WHERE notification_id = ?
        ");

        return $stmt->execute([$notificationId]);
    }


    public function markAllAsRead(int $userId): bool
    {
        $stmt = $this->db->prepare("
        UPDATE notifications
        SET is_read = 1
        WHERE user_id = ?
    ");

        return $stmt->execute([$userId]);
    }

    public function getUnreadCount(int $userId): int
    {
        $stmt = $this->db->prepare("
        SELECT COUNT(*) as count
        FROM notifications
        WHERE user_id = ?
        AND is_read = 0
    ");

        $stmt->execute([$userId]);

        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        return (int)($result['count'] ?? 0);
    }

    public function deleteNotification(int $notificationId, int $userId): bool
    {
        if ($notificationId <= 0 || $userId <= 0) {
            return false;
        }

        $stmt = $this->db->prepare("
            DELETE FROM notifications
            WHERE notification_id = ?
              AND user_id = ?
        ");

        return $stmt->execute([$notificationId, $userId]);
    }

    public function getAdmins(): array
    {
        $stmt = $this->db->prepare("
        SELECT user_id, username
        FROM users
        WHERE user_type_id = 1
    ");

        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function notifyAdmins(string $title, string $message, string $type, ?int $exceptUserId = null): void
    {
        foreach ($this->getAdmins() as $admin) {
            if ($exceptUserId !== null && (int)$admin['user_id'] === $exceptUserId) {
                continue;
            }

            $this->create((int)$admin['user_id'], $title, $message, $type);
        }
    }
}
