<?php

namespace App\Donation\Infrastructure\Persistence;

use App\Donation\Domain\Repository\DonationRepositoryInterface;
use App\Shared\Infrastructure\Database\Database;
use PDO;

class DonationRepository implements DonationRepositoryInterface
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    public function create(array $data): bool
    {
        $requestId = (int)($data['request_id'] ?? 0);
        $donorId = (int)($data['donor_id'] ?? 0);
        $donationDate = trim((string)($data['donation_date'] ?? ''));
        $status = (int)($data['status'] ?? 0);
        $remarks = trim((string)($data['remarks'] ?? ''));

        if ($requestId <= 0 || $donorId <= 0 || $donationDate === '' || $status <= 0) {
            return false;
        }

        // Ensure referenced request and donor exist to avoid FK violations
        if (!$this->requestExists($requestId) || !$this->userExists($donorId)) {
            error_log("Skipping donation_history insert: missing request_id={$requestId} or donor_id={$donorId}");
            return false;
        }

        $exists = $this->db->prepare("SELECT 1 FROM donation_history WHERE request_id = ? LIMIT 1");
        $exists->execute([$requestId]);

        if ($exists->fetchColumn()) {
            return true;
        }

        try {
            $stmt = $this->db->prepare(
                "INSERT INTO donation_history (request_id, donor_id, donation_date, status, remarks) VALUES (?, ?, ?, ?, ?)"
            );

            return $stmt->execute([$requestId, $donorId, $donationDate, $status, $remarks]);
        } catch (\PDOException $e) {
            if (str_contains($e->getMessage(), 'foreign key constraint')) {
                error_log("Failed inserting donation_history for request_id={$requestId}, donor_id={$donorId}: {$e->getMessage()}");
                return false;
            }
            throw $e;
        }
    }

    public function createStandalone(array $data): ?int
    {
        $donorId = (int)($data['donor_id'] ?? 0);
        $donationDate = trim((string)($data['donation_date'] ?? ''));
        $status = (int)($data['status'] ?? 0);
        $remarks = trim((string)($data['remarks'] ?? ''));
        $bags = max(1, (int)($data['bags'] ?? 1));

        if ($donorId <= 0 || $donationDate === '' || $status <= 0) {
            return null;
        }

        if (!$this->userExists($donorId)) {
            error_log("Skipping standalone donation insert: missing donor_id={$donorId}");
            return null;
        }

        try {
            $stmt = $this->db->prepare(
                "INSERT INTO donation_history (request_id, donor_id, donation_date, status, remarks, bags)
                 VALUES (NULL, ?, ?, ?, ?, ?)"
            );

            $ok = $stmt->execute([$donorId, $donationDate, $status, $remarks, $bags]);

            return $ok ? (int)$this->db->lastInsertId() : null;
        } catch (\PDOException $e) {
            if (str_contains($e->getMessage(), 'foreign key constraint')) {
                error_log("Failed inserting standalone donation for donor_id={$donorId}: {$e->getMessage()}");
                return null;
            }
            throw $e;
        }
    }

    public function findByRequestId(int $requestId): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT donation_id, request_id, donor_id, donation_date, status, bags
             FROM donation_history
             WHERE request_id = ?
             ORDER BY donation_id DESC
             LIMIT 1"
        );
        $stmt->execute([$requestId]);

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    private function requestExists(int $requestId): bool
    {
        $stmt = $this->db->prepare("SELECT 1 FROM blood_requests WHERE request_id = ? LIMIT 1");
        $stmt->execute([$requestId]);

        return (bool)$stmt->fetchColumn();
    }

    private function userExists(int $userId): bool
    {
        $stmt = $this->db->prepare("SELECT 1 FROM users WHERE user_id = ? LIMIT 1");
        $stmt->execute([$userId]);

        return (bool)$stmt->fetchColumn();
    }

    public function findByDonor(int $donorId): array
    {
        $stmt = $this->db->prepare(
            "SELECT dh.*,
                    md.label AS status_label,
                    br.request_code, br.hospital_name, br.blood_group_needed,
                    (SELECT b.blood_group FROM blood_bags b WHERE b.donation_id = dh.donation_id LIMIT 1) AS blood_group
             FROM donation_history dh
             LEFT JOIN master_data md ON md.id = dh.status
             LEFT JOIN blood_requests br ON br.request_id = dh.request_id
             WHERE dh.donor_id = ?
             ORDER BY dh.donation_date DESC, dh.created_at DESC"
        );

        $stmt->execute([$donorId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function updateStatusByRequestId(int $requestId, int $status): bool
    {
        $stmt = $this->db->prepare("UPDATE donation_history SET status = ? WHERE request_id = ?");
        return $stmt->execute([$status, $requestId]);
    }

    public function countSuccessfulDonations(?int $statusId = null): int
    {
        $status = $statusId ?? null;

        if ($status !== null && $status > 0) {
            $stmt = $this->db->prepare("SELECT COUNT(*) FROM donation_history WHERE status = ?");
            $stmt->execute([$status]);
            return (int)$stmt->fetchColumn();
        }

        $stmt = $this->db->query("SELECT COUNT(*) FROM donation_history");
        return (int)$stmt->fetchColumn();
    }

    public function sumDonatedBagsByBloodGroup(int $completedStatus): array
    {
        $stmt = $this->db->prepare(
            "SELECT d.blood_group, COALESCE(SUM(dh.bags), 0) AS donated
             FROM donation_history dh
             JOIN users d ON d.user_id = dh.donor_id
             WHERE dh.status = ?
               AND d.blood_group IS NOT NULL
               AND TRIM(d.blood_group) <> ''
             GROUP BY d.blood_group"
        );
        $stmt->execute([$completedStatus]);

        $map = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $map[$row['blood_group']] = (int)$row['donated'];
        }

        return $map;
    }

    public function findDonationForCertificate(int $donationId, int $donorId, ?int $completedStatus = null): ?array
    {
        $sql = "SELECT
                dh.donation_id,
                dh.request_id,
                dh.donor_id,
                dh.donation_date,
                dh.status,
                dh.remarks,
                dh.bags,
                dh.created_at,
                br.request_code,
                br.hospital_name,
                br.blood_group_needed,
                donor.username AS donor_name,
                donor.email AS donor_email,
                donor.blood_group AS donor_blood_group
             FROM donation_history dh
             LEFT JOIN blood_requests br ON br.request_id = dh.request_id
             LEFT JOIN users donor ON donor.user_id = dh.donor_id
             WHERE dh.donation_id = ?
               AND dh.donor_id = ?";

        $params = [$donationId, $donorId];

        if ($completedStatus !== null && $completedStatus > 0) {
            $sql .= " AND dh.status = ?";
            $params[] = $completedStatus;
        }

        $sql .= " ORDER BY dh.donation_id DESC LIMIT 1";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }
}
