<?php

namespace App\Hospital\Infrastructure\Persistence;

use App\BloodStock\Domain\Entity\BloodStock;
use App\Hospital\Domain\Repository\HospitalBloodStockRepositoryInterface;
use App\Shared\Infrastructure\Database\Database;
use PDO;

/**
 * Per-hospital blood inventory, derived from the central bag ledger.
 *
 * A bag belongs to a hospital from the moment it is allocated against one of
 * that hospital's stock requests (blood_bags.status = 'ALLOCATED' with an
 * allocated_to_request_id pointing at a hospital_blood_requests row owned by
 * the hospital). There is no separate "hospital received it" step: allocation
 * and ownership are the same event, so every ALLOCATED bag linked to one of
 * the hospital's requests counts as inventory on hand until it expires.
 */
class HospitalBloodStockRepository implements HospitalBloodStockRepositoryInterface
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    public function findAllByHospital(int $hospitalUserId): array
    {
        $stmt = $this->db->prepare(
            "SELECT bb.blood_group,
                    COUNT(*) AS available_bags,
                    MAX(bb.allocated_at) AS last_updated
             FROM blood_bags bb
             INNER JOIN hospital_blood_requests r ON r.request_id = bb.allocated_to_request_id
             WHERE r.hospital_user_id = ?
               AND bb.status = 'ALLOCATED'
               AND bb.expiry_date >= CURDATE()
             GROUP BY bb.blood_group
             ORDER BY bb.blood_group"
        );
        $stmt->execute([$hospitalUserId]);

        return array_map(
            fn(array $row): array => [
                'blood_group'    => (string)$row['blood_group'],
                'available_bags' => (int)$row['available_bags'],
                'last_updated'   => (string)($row['last_updated'] ?? ''),
            ],
            $stmt->fetchAll(PDO::FETCH_ASSOC)
        );
    }

    public function findExpiryOverviewByHospital(int $hospitalUserId): array
    {
        $stmt = $this->db->prepare(
            "SELECT bb.blood_group,
                    SUM(CASE WHEN bb.expiry_date >= CURDATE()
                              AND bb.expiry_date <= DATE_ADD(CURDATE(), INTERVAL ? DAY)
                        THEN 1 ELSE 0 END) AS expiring_soon,
                    SUM(CASE WHEN bb.expiry_date < CURDATE() THEN 1 ELSE 0 END) AS expired,
                    MIN(CASE WHEN bb.expiry_date >= CURDATE() THEN bb.expiry_date END) AS oldest_expiry
             FROM blood_bags bb
             INNER JOIN hospital_blood_requests r ON r.request_id = bb.allocated_to_request_id
             WHERE r.hospital_user_id = ?
               AND bb.status = 'ALLOCATED'
             GROUP BY bb.blood_group"
        );
        $stmt->bindValue(1, BloodStock::EXPIRY_WARNING_DAYS, PDO::PARAM_INT);
        $stmt->bindValue(2, $hospitalUserId, PDO::PARAM_INT);
        $stmt->execute();

        $overview = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $overview[(string)$row['blood_group']] = [
                'expiring_soon' => (int)$row['expiring_soon'],
                'expired'       => (int)$row['expired'],
                'oldest_expiry' => (string)($row['oldest_expiry'] ?? ''),
            ];
        }

        return $overview;
    }
}
