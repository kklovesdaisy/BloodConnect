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

    public function recordBags(string $bloodGroup, int $bags, string $collectionDate, ?int $donationId = null): bool
    {
        $bags = max(1, $bags);
        $group = BloodStock::normalize($bloodGroup);
        $collectionDate = $collectionDate !== '' ? $collectionDate : date('Y-m-d');
        $expiryDate = date('Y-m-d', strtotime($collectionDate . ' + ' . BloodStock::SHELF_LIFE_DAYS . ' days'));

        $inTx = $this->db->inTransaction();
        if (!$inTx) {
            $this->db->beginTransaction();
        }

        try {
            $stmt = $this->db->prepare(
                "INSERT INTO blood_bags (blood_group, donation_id, collection_date, expiry_date, status)
                 VALUES (?, ?, ?, ?, 'AVAILABLE')"
            );

            for ($i = 0; $i < $bags; $i++) {
                $stmt->execute([$group, $donationId, $collectionDate, $expiryDate]);
            }

            $upd = $this->db->prepare(
                "INSERT INTO blood_stock (blood_group, available_bags)
                 VALUES (?, ?)
                 ON DUPLICATE KEY UPDATE available_bags = available_bags + VALUES(available_bags)"
            );
            $upd->execute([$group, $bags]);

            $this->logAdjustment($group, $bags, 'DONATION');

            if (!$inTx) {
                $this->db->commit();
            }

            return true;
        } catch (\Throwable $e) {
            if (!$inTx && $this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }
    }

    public function decreaseIfAvailable(string $bloodGroup, int $bags, ?int $requestId = null): bool
    {
        $bags = max(1, $bags);
        $group = BloodStock::normalize($bloodGroup);

        $inTx = $this->db->inTransaction();
        if (!$inTx) {
            $this->db->beginTransaction();
        }

        try {
            // FEFO: pick the oldest (soonest-expiring) available bags first.
            $stmt = $this->db->prepare(
                "SELECT bag_id
                 FROM blood_bags
                 WHERE blood_group = ? AND status = 'AVAILABLE'
                 ORDER BY expiry_date ASC, bag_id ASC
                 LIMIT ?"
            );
            $stmt->bindValue(1, $group, PDO::PARAM_STR);
            $stmt->bindValue(2, $bags, PDO::PARAM_INT);
            $stmt->execute();
            $bagIds = array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));

            if (count($bagIds) < $bags) {
                if (!$inTx) {
                    $this->db->rollBack();
                }
                return false;
            }

            // Mark the selected bags as allocated. Placeholder order matters:
            // allocated_to_request_id comes first in the SQL, so it must also
            // come first in the bound params.
            $placeholders = implode(',', array_fill(0, count($bagIds), '?'));
            $params = $bagIds;
            $requestSet = '';
            if ($requestId !== null) {
                $requestSet = ', allocated_to_request_id = ?';
                array_unshift($params, $requestId);
            }

            $upd = $this->db->prepare(
                "UPDATE blood_bags
                 SET status = 'ALLOCATED', allocated_at = NOW(){$requestSet}
                 WHERE bag_id IN ({$placeholders}) AND status = 'AVAILABLE'"
            );
            $upd->execute($params);

            if ($upd->rowCount() !== count($bagIds)) {
                if (!$inTx) {
                    $this->db->rollBack();
                }
                return false;
            }

            $dec = $this->db->prepare(
                "UPDATE blood_stock
                 SET available_bags = available_bags - ?
                 WHERE blood_group = ? AND available_bags >= ?"
            );
            $dec->execute([$bags, $group, $bags]);

            if ($dec->rowCount() !== 1) {
                if (!$inTx) {
                    $this->db->rollBack();
                }
                return false;
            }

            $this->logAdjustment($group, -$bags, 'ALLOCATION');

            if (!$inTx) {
                $this->db->commit();
            }

            return true;
        } catch (\Throwable $e) {
            if (!$inTx && $this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }
    }

    public function expireDueBags(?int $createdBy = null): int
    {
        $inTx = $this->db->inTransaction();
        if (!$inTx) {
            $this->db->beginTransaction();
        }

        try {
            // Find per-group counts of still-available bags past their expiry.
            $stmt = $this->db->query(
                "SELECT blood_group, COUNT(*) AS expired_count
                 FROM blood_bags
                 WHERE status = 'AVAILABLE' AND expiry_date < CURDATE()
                 GROUP BY blood_group"
            );
            $groups = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $total = 0;

            foreach ($groups as $group) {
                $bloodGroup = $group['blood_group'];
                $count = (int)$group['expired_count'];

                $mark = $this->db->prepare(
                    "UPDATE blood_bags
                     SET status = 'EXPIRED'
                     WHERE blood_group = ? AND status = 'AVAILABLE' AND expiry_date < CURDATE()"
                );
                $mark->execute([$bloodGroup]);

                $dec = $this->db->prepare(
                    "UPDATE blood_stock
                     SET available_bags = available_bags - ?
                     WHERE blood_group = ?"
                );
                $dec->execute([$count, $bloodGroup]);

                $log = $this->db->prepare(
                    "INSERT INTO blood_stock_adjustments (blood_group, delta, reason, created_by)
                     VALUES (?, ?, 'EXPIRED', ?)"
                );
                $log->execute([$bloodGroup, -$count, $createdBy]);

                $total += $count;
            }

            if (!$inTx) {
                $this->db->commit();
            }

            return $total;
        } catch (\Throwable $e) {
            if (!$inTx && $this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }
    }

    public function disposeExpiredBags(?int $createdBy = null): int
    {
        $this->expireDueBags($createdBy);

        $inTx = $this->db->inTransaction();
        if (!$inTx) {
            $this->db->beginTransaction();
        }

        try {
            $stmt = $this->db->prepare(
                "DELETE FROM blood_bags WHERE status = 'EXPIRED'"
            );
            $stmt->execute();

            if (!$inTx) {
                $this->db->commit();
            }

            return $stmt->rowCount();
        } catch (\Throwable $e) {
            if (!$inTx && $this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }
    }

    public function findExpiryOverview(): array
    {
        $stmt = $this->db->prepare(
            "SELECT
                bb.blood_group,
                SUM(CASE WHEN bb.status = 'AVAILABLE' AND bb.expiry_date >= CURDATE() AND bb.expiry_date <= DATE_ADD(CURDATE(), INTERVAL ? DAY) THEN 1 ELSE 0 END) AS expiring_soon,
                SUM(CASE WHEN bb.status = 'EXPIRED' THEN 1 ELSE 0 END) AS expired,
                MIN(CASE WHEN bb.status = 'AVAILABLE' THEN bb.expiry_date END) AS oldest_expiry
             FROM blood_bags bb
             GROUP BY bb.blood_group"
        );
        $stmt->bindValue(1, BloodStock::EXPIRY_WARNING_DAYS, PDO::PARAM_INT);
        $stmt->execute();

        $overview = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $overview[$row['blood_group']] = [
                'expiring_soon' => (int)$row['expiring_soon'],
                'expired'       => (int)$row['expired'],
                'oldest_expiry' => $row['oldest_expiry'],
            ];
        }

        return $overview;
    }

    public function findReservedOverview(): array
    {
        $stmt = $this->db->query(
            "SELECT blood_group, COUNT(*) AS reserved
             FROM blood_bags
             WHERE status = 'ALLOCATED'
             GROUP BY blood_group"
        );

        $map = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $map[$row['blood_group']] = (int)$row['reserved'];
        }

        return $map;
    }

    public function findRecentAdjustments(int $limit = 6): array
    {
        $stmt = $this->db->prepare(
            "SELECT blood_group, delta, reason, created_at
             FROM blood_stock_adjustments
             ORDER BY adjustment_id DESC
             LIMIT ?"
        );
        $stmt->bindValue(1, max(1, $limit), PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Append an inventory change to the stock adjustment ledger. Intended to be
     * called from inside an existing transaction (e.g. recordBags /
     * decreaseIfAvailable / expireDueBags) so the audit trail stays consistent.
     */
    private function logAdjustment(string $bloodGroup, int $delta, string $reason, ?int $createdBy = null): void
    {
        $stmt = $this->db->prepare(
            "INSERT INTO blood_stock_adjustments (blood_group, delta, reason, created_by)
             VALUES (?, ?, ?, ?)"
        );
        $stmt->execute([$bloodGroup, $delta, $reason, $createdBy]);
    }
}
