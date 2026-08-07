<?php

namespace App\Shared\Infrastructure\Persistence;

use App\Shared\Infrastructure\Database\Database;
use PDO;

class MasterDataRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    public function getId(string $category, string $code): ?int
    {
        $stmt = $this->db->prepare("
            SELECT id
            FROM master_data
            WHERE category = ?
              AND code = ?
            LIMIT 1
        ");

        $stmt->execute([$category, strtoupper($code)]);

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ? (int)$row['id'] : null;
    }

    public function getLabel(string $category, string $code): ?string
    {
        $stmt = $this->db->prepare("
            SELECT label
            FROM master_data
            WHERE category = ?
              AND code = ?
            LIMIT 1
        ");

        $stmt->execute([$category, strtoupper($code)]);

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ? (string)$row['label'] : null;
    }
}
