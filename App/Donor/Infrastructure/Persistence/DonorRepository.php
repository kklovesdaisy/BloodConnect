<?php

namespace App\Donor\Infrastructure\Persistence;

use App\Donor\Domain\Repository\DonorRepositoryInterface;
use App\Donor\Domain\Service\DonorEligibilityService;
use App\Shared\Infrastructure\Database\Database;
use PDO;

class DonorRepository implements DonorRepositoryInterface
{
    private PDO $db;

    public function __construct(private DonorEligibilityService $eligibilityService)
    {
        $this->db = Database::getConnection();
    }

    public function findById(int $id)
    {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE user_id = ?");
        $stmt->execute([$id]);

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function search(array $criteria)
    {
        $sql = "SELECT * FROM users WHERE 1=1";
        $params = [];

        if (!empty($criteria['username'])) {
            $sql .= " AND username LIKE ?";
            $params[] = '%' . $criteria['username'] . '%';
        }

        if (!empty($criteria['blood_group'])) {
            $sql .= " AND blood_group = ?";
            $params[] = $criteria['blood_group'];
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function syncAvailabilityStatus(int $userId): array
    {
        $stmt = $this->db->prepare("
            SELECT u.available, u.next_available_date, d.date_of_birth, d.weight, d.is_pregnant
            FROM users u
            LEFT JOIN donors d ON d.user_id = u.user_id
            WHERE u.user_id = ?
        ");
        $stmt->execute([$userId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            return [
                'available' => true,
                'next_available_date' => null
            ];
        }

        // Donor must meet ALL requirements to be available:
        // age 18-65, weight >= 50kg, not pregnant. Missing DOB/weight
        // means requirements cannot be verified -> unavailable.
        $profileEligible = false;
        if (!empty($row['date_of_birth']) && !empty($row['weight'])) {
            $eligibility = $this->eligibilityService->evaluate(
                (string)($row['date_of_birth'] ?? ''),
                (string)($row['weight'] ?? ''),
                (bool)($row['is_pregnant'] ?? 0)
            );
            $profileEligible = $eligibility['eligible'];
        }

        if (!$profileEligible) {
            $update = $this->db->prepare("UPDATE users SET available = 0 WHERE user_id = ?");
            $update->execute([$userId]);
            return [
                'available' => false,
                'next_available_date' => null
            ];
        }

        $available = (int)($row['available'] ?? 1) === 1;
        $nextAvailableDate = trim((string)($row['next_available_date'] ?? ''));

        // Already has a future unavailability window (e.g. post-donation wait):
        // only re-enable once that date has passed.
        if (!$available && $nextAvailableDate !== '') {
            $timezone = new \DateTimeZone('Asia/Yangon');
            $now = new \DateTime('now', $timezone);
            $nextDate = \DateTime::createFromFormat('Y-m-d H:i:s', $nextAvailableDate, $timezone);

            if ($nextDate === false) {
                $nextDate = new \DateTime($nextAvailableDate, $timezone);
            }

            if ($nextDate !== false && $now >= $nextDate) {
                $update = $this->db->prepare("UPDATE users SET available = 1, next_available_date = NULL WHERE user_id = ?");
                $update->execute([$userId]);

                return [
                    'available' => true,
                    'next_available_date' => null
                ];
            }

            return [
                'available' => false,
                'next_available_date' => $nextAvailableDate
            ];
        }

        // Profile is eligible and there is no future unavailability window:
        // the donor should be Available. Flip them back if they were marked
        // unavailable (e.g. due to a previously ineligible weight/age).
        if ($profileEligible && !$available) {
            $update = $this->db->prepare("UPDATE users SET available = 1 WHERE user_id = ?");
            $update->execute([$userId]);
            $available = true;
        }

        return [
            'available' => $available,
            'next_available_date' => $nextAvailableDate !== '' ? $nextAvailableDate : null
        ];
    }

    public function saveNextAvailableDate(int $userId, string $nextAvailableDate): bool
    {
        $stmt = $this->db->prepare("UPDATE users SET available = 0, next_available_date = ? WHERE user_id = ?");
        return $stmt->execute([$nextAvailableDate, $userId]);
    }

    public function updateProfile(int $id, array $data): bool
    {
        $setClauses = [
            'username = ?',
            'email = ?',
            'phone = ?',
            'address = ?',
            'updated_at = NOW()'
        ];

        $params = [
            trim((string)($data['username'] ?? '')),
            trim((string)($data['email'] ?? '')),
            trim((string)($data['phone'] ?? '')),
            trim((string)($data['address'] ?? '')),
        ];

        if (!empty($data['password'])) {
            $setClauses[] = 'password = ?';
            $params[] = password_hash($data['password'], PASSWORD_BCRYPT);
        }

        $params[] = $id;

        $sql = 'UPDATE users SET ' . implode(', ', $setClauses) . ' WHERE user_id = ?';
        $stmt = $this->db->prepare($sql);

        return $stmt->execute($params);
    }

    public function createDonorProfile(int $userId): bool
    {
        $stmt = $this->db->prepare("INSERT INTO donors (user_id) VALUES (?)");
        return $stmt->execute([$userId]);
    }

    public function saveLocation(int $userId, string $stateRegion, string $township): bool
    {
                $stmt = $this->db->prepare("UPDATE donors SET state_region = ?, township = ? WHERE user_id = ?");
        return $stmt->execute([$stateRegion, $township, $userId]);
    }

    public function getDonorDetails(int $userId): ?array
    {
                        $stmt = $this->db->prepare("
            SELECT d.*, u.username, u.email, u.phone, u.blood_group, u.address
            FROM donors d
            JOIN users u ON u.user_id = d.user_id
            WHERE d.user_id = ?
        ");
        $stmt->execute([$userId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    public function saveDonorDetails(int $userId, array $data): bool
    {
                        $fields = [];
        $params = [];

        if (isset($data['date_of_birth'])) {
            $fields[] = 'date_of_birth = ?';
            $params[] = $data['date_of_birth'];
        }
        if (isset($data['weight'])) {
            $fields[] = 'weight = ?';
            $params[] = $data['weight'];
        }
        if (isset($data['state_region'])) {
            $fields[] = 'state_region = ?';
            $params[] = $data['state_region'];
        }
        if (isset($data['township'])) {
            $fields[] = 'township = ?';
            $params[] = $data['township'];
        }
        if (isset($data['is_pregnant'])) {
            $fields[] = 'is_pregnant = ?';
            $params[] = (int)(bool)$data['is_pregnant'];
        }

        if (empty($fields)) {
            return true;
        }

        $params[] = $userId;
        $sql = 'UPDATE donors SET ' . implode(', ', $fields) . ' WHERE user_id = ?';
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }

    public function isProfileComplete(int $userId): bool
    {
                $stmt = $this->db->prepare("SELECT date_of_birth, weight FROM donors WHERE user_id = ?");
        $stmt->execute([$userId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            return false;
        }

        return !empty($row['date_of_birth']) && !empty($row['weight']);
    }

    public function updateWeight(int $userId, string $weight): bool
    {
        $stmt = $this->db->prepare("UPDATE donors SET weight = ? WHERE user_id = ?");
        return $stmt->execute([$weight, $userId]);
    }
}

