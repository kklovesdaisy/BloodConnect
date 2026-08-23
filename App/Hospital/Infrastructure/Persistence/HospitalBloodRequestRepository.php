<?php

namespace App\Hospital\Infrastructure\Persistence;

use App\Hospital\Domain\Repository\HospitalBloodRequestRepositoryInterface;
use App\Shared\Infrastructure\Database\Database;
use PDO;

class HospitalBloodRequestRepository implements HospitalBloodRequestRepositoryInterface
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    public function create(array $data): int
    {
        $stmt = $this->db->prepare(
            "INSERT INTO hospital_blood_requests
                (request_code, hospital_user_id, hospital_name, blood_group,
                 quantity_requested, quantity_allocated, quantity_pending, status)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
        );

        $stmt->execute([
            trim((string)$data['request_code']),
            (int)$data['hospital_user_id'],
            $data['hospital_name'] !== null ? trim((string)$data['hospital_name']) : null,
            trim((string)$data['blood_group']),
            (int)$data['quantity_requested'],
            (int)$data['quantity_allocated'],
            (int)$data['quantity_pending'],
            trim((string)$data['status']),
        ]);

        return (int)$this->db->lastInsertId();
    }

    public function findByHospital(int $hospitalUserId): array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM hospital_blood_requests
             WHERE hospital_user_id = ?
             ORDER BY request_id DESC"
        );
        $stmt->execute([$hospitalUserId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findById(int $requestId): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM hospital_blood_requests
             WHERE request_id = ?"
        );
        $stmt->execute([$requestId]);

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    public function findOpenByBloodGroup(string $bloodGroup): array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM hospital_blood_requests
             WHERE blood_group = ?
               AND status IN ('PENDING', 'PARTIAL')
               AND quantity_pending > 0
             ORDER BY request_id ASC"
        );
        $stmt->execute([strtoupper(trim($bloodGroup))]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function countOpenByHospital(int $hospitalUserId): int
    {
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) FROM hospital_blood_requests
             WHERE hospital_user_id = ?
               AND status IN ('PENDING', 'PARTIAL')
               AND quantity_pending > 0"
        );
        $stmt->execute([$hospitalUserId]);

        return (int)$stmt->fetchColumn();
    }

    public function sumPendingByHospital(int $hospitalUserId): int
    {
        $stmt = $this->db->prepare(
            "SELECT COALESCE(SUM(quantity_pending), 0) FROM hospital_blood_requests
             WHERE hospital_user_id = ?
               AND status IN ('PENDING', 'PARTIAL')
               AND quantity_pending > 0"
        );
        $stmt->execute([$hospitalUserId]);

        return (int)$stmt->fetchColumn();
    }

    public function countCompletedRequests(): int
    {
        $stmt = $this->db->query(
            "SELECT COUNT(*) FROM hospital_blood_requests WHERE status = 'COMPLETED'"
        );

        return (int)$stmt->fetchColumn();
    }

    public function allocateBags(int $requestId, int $bags): bool
    {
        $stmt = $this->db->prepare(
            "UPDATE hospital_blood_requests
             SET quantity_allocated = quantity_allocated + ?,
                 quantity_pending = quantity_pending - ?
             WHERE request_id = ? AND quantity_pending >= ?"
        );

        return $stmt->execute([$bags, $bags, $requestId, $bags]);
    }

    public function markCompleted(int $requestId): bool
    {
        $stmt = $this->db->prepare(
            "UPDATE hospital_blood_requests
             SET status = 'COMPLETED', quantity_pending = 0
             WHERE request_id = ?"
        );

        return $stmt->execute([$requestId]);
    }

    public function accept(int $requestId, int $bags, int $newPending, string $status): bool
    {
        $stmt = $this->db->prepare(
            "UPDATE hospital_blood_requests
             SET quantity_allocated = quantity_allocated + ?,
                 quantity_pending = ?,
                 status = ?
             WHERE request_id = ? AND status = 'PENDING'"
        );

        return $stmt->execute([$bags, $newPending, $status, $requestId]);
    }

    public function decline(int $requestId): bool
    {
        $stmt = $this->db->prepare(
            "UPDATE hospital_blood_requests
             SET status = 'DECLINED', quantity_pending = 0
             WHERE request_id = ? AND status = 'PENDING'"
        );

        return $stmt->execute([$requestId]);
    }
}
