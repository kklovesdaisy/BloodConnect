<?php

namespace App\Hospital\Infrastructure\Persistence;

use App\Hospital\Domain\Repository\HospitalRepositoryInterface;
use App\Shared\Infrastructure\Database\Database;
use PDO;

class HospitalRepository implements HospitalRepositoryInterface
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    public function findByUserId(int $userId): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM hospitals WHERE user_id = ? LIMIT 1");
        $stmt->execute([$userId]);

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    public function createProfile(int $userId, array $data): bool
    {
        $stmt = $this->db->prepare(
            "INSERT INTO hospitals (user_id, hospital_name, license_no, state_region, township, address, phone)
             VALUES (?, ?, ?, ?, ?, ?, ?)"
        );

        return $stmt->execute([
            $userId,
            trim((string)($data['hospital_name'] ?? '')),
            trim((string)($data['license_no'] ?? '')),
            trim((string)($data['state_region'] ?? '')),
            trim((string)($data['township'] ?? '')),
            trim((string)($data['address'] ?? '')),
            trim((string)($data['phone'] ?? '')),
        ]);
    }

    public function updateProfile(int $userId, array $data): bool
    {
        $fields = [];
        $params = [];

        foreach (['hospital_name', 'license_no', 'state_region', 'township', 'address', 'phone'] as $column) {
            if (array_key_exists($column, $data)) {
                $fields[] = "$column = ?";
                $params[] = trim((string)$data[$column]);
            }
        }

        if (empty($fields)) {
            return true;
        }

        $params[] = $userId;
        $sql = "UPDATE hospitals SET " . implode(', ', $fields) . " WHERE user_id = ?";

        return $this->db->prepare($sql)->execute($params);
    }

    public function createAccount(array $data, string $passwordHash, int $statusId): int
    {
        $username = trim((string)($data['username'] ?? ''));
        $email = trim((string)($data['email'] ?? ''));

        if ($username === '' || $email === '') {
            return 0;
        }

        try {
            $this->db->beginTransaction();

            $userStmt = $this->db->prepare(
                "INSERT INTO users (
                    username, email, phone, password, address, user_type_id, status_id,
                    is_verified, is_active, available, auth_provider, created_at, updated_at
                 ) VALUES (?, ?, ?, ?, ?, 4, ?, 1, 1, 1, 'local', NOW(), NOW())"
            );

            $userStmt->execute([
                $username,
                $email,
                trim((string)($data['phone'] ?? '')),
                $passwordHash,
                trim((string)($data['address'] ?? '')),
                $statusId,
            ]);

            $userId = (int)$this->db->lastInsertId();

            $ok = $this->createProfile($userId, $data);
            if (!$ok) {
                throw new \RuntimeException('Could not create hospital profile.');
            }

            $this->db->commit();

            return $userId;
        } catch (\Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }

            error_log('Hospital account creation failed: ' . $e->getMessage());

            return 0;
        }
    }

    public function findAll(): array
    {
        $stmt = $this->db->prepare(
            "SELECT h.*, u.username, u.email, u.phone AS user_phone, u.is_active, u.status_id
             FROM hospitals h
             INNER JOIN users u ON u.user_id = h.user_id
             ORDER BY h.hospital_id DESC"
        );
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
