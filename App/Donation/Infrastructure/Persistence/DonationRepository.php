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
            "SELECT dh.*, br.request_code, br.hospital_name, br.blood_group_needed
             FROM donation_history dh
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

    public function findDonationForCertificate(int $requestId, int $donorId): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT
                dh.donation_id,
                dh.request_id,
                dh.donor_id,
                dh.donation_date,
                dh.status,
                dh.remarks,
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
             WHERE dh.request_id = ?
               AND dh.donor_id = ?
             ORDER BY dh.donation_id DESC
             LIMIT 1"
        );

        $stmt->execute([$requestId, $donorId]);

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }
}
