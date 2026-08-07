<?php

namespace App\BloodStock\Infrastructure\Persistence;

use App\BloodStock\Domain\Entity\BloodStock;
use App\BloodStock\Domain\Repository\BloodStockRepositoryInterface;
use App\Shared\Infrastructure\Database\Database;
use PDO;

class BloodStockRepository implements BloodStockRepositoryInterface
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    public function findAll(): array
    {
        $stmt = $this->db->query(
            "SELECT blood_group, available_bags, last_updated
             FROM blood_stock
             ORDER BY blood_group"
        );

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function find(string $bloodGroup): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT blood_group, available_bags, last_updated
             FROM blood_stock
             WHERE blood_group = ?"
        );
        $stmt->execute([BloodStock::normalize($bloodGroup)]);

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    public function increase(string $bloodGroup, int $bags): bool
    {
        $bags = max(1, $bags);

        $stmt = $this->db->prepare(
            "INSERT INTO blood_stock (blood_group, available_bags)
             VALUES (?, ?)
             ON DUPLICATE KEY UPDATE available_bags = available_bags + VALUES(available_bags)"
        );

        return $stmt->execute([BloodStock::normalize($bloodGroup), $bags]);
    }

    public function decreaseIfAvailable(string $bloodGroup, int $bags): bool
    {
        $bags = max(1, $bags);

        $stmt = $this->db->prepare(
            "UPDATE blood_stock
             SET available_bags = available_bags - ?
             WHERE blood_group = ? AND available_bags >= ?"
        );
        $stmt->execute([$bags, BloodStock::normalize($bloodGroup), $bags]);

        return $stmt->rowCount() === 1;
    }

    public function adjust(string $bloodGroup, int $delta, string $reason, int $actorId): bool
    {
        $group = BloodStock::normalize($bloodGroup);

        if ($delta > 0) {
            $ok = $this->increase($group, $delta);
        } else {
            $ok = $this->decreaseIfAvailable($group, abs($delta));
        }

        if (!$ok) {
            return false;
        }

        if (trim($reason) !== '') {
            $stmt = $this->db->prepare(
                "INSERT INTO blood_stock_adjustments (blood_group, delta, reason, created_by)
                 VALUES (?, ?, ?, ?)"
            );
            $stmt->execute([$group, $delta, trim($reason), $actorId]);
        }

        return true;
    }

    public function recentAdjustments(int $limit = 20): array
    {
        $stmt = $this->db->prepare(
            "SELECT a.adjustment_id, a.blood_group, a.delta, a.reason, a.created_at,
                    COALESCE(u.username, 'System') AS actor
             FROM blood_stock_adjustments a
             LEFT JOIN users u ON u.user_id = a.created_by
             ORDER BY a.adjustment_id DESC
             LIMIT ?"
        );
        $stmt->execute([max(1, $limit)]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
