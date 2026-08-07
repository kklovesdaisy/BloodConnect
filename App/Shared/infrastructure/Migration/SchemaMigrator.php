<?php

namespace App\Shared\Infrastructure\Migration;

use App\Shared\Infrastructure\Database\Database;
use PDO;

/**
 * Idempotent schema migration that ensures the application's required columns
 * exist. Previously this DDL was executed ad-hoc inside repository methods on
 * the request hot path (a Single-Responsibility violation). It now runs once
 * per process at bootstrap via index.php.
 *
 * Safe to call repeatedly: every change is guarded by a SHOW COLUMNS check.
 */
class SchemaMigrator
{
    private bool $migrated = false;

    public function migrate(): void
    {
        if ($this->migrated) {
            return;
        }

        $db = Database::getConnection();

        $this->ensureColumn($db, 'blood_requests', 'state_region', 'VARCHAR(100) DEFAULT NULL AFTER hospital_name');
        $this->ensureColumn($db, 'blood_requests', 'township', 'VARCHAR(100) DEFAULT NULL AFTER state_region');
        $this->ensureColumn($db, 'blood_requests', 'hospital_address', 'TEXT DEFAULT NULL AFTER township');
        $this->ensureColumn($db, 'donors', 'date_of_birth', 'DATE DEFAULT NULL AFTER weight');
        $this->ensureColumn($db, 'donors', 'state_region', 'VARCHAR(100) DEFAULT NULL AFTER date_of_birth');
        $this->ensureColumn($db, 'donors', 'township', 'VARCHAR(100) DEFAULT NULL AFTER state_region');
        $this->ensureColumn($db, 'users', 'next_available_date', 'DATETIME DEFAULT NULL');

        // Blood stock inventory (one row per blood group, keyed by label e.g. 'A+')
        $this->ensureTable($db, 'blood_stock', "
            CREATE TABLE IF NOT EXISTS blood_stock (
                blood_group VARCHAR(10) NOT NULL PRIMARY KEY,
                available_bags INT NOT NULL DEFAULT 0,
                last_updated TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");

        // Manual stock adjustment audit trail
        $this->ensureTable($db, 'blood_stock_adjustments', "
            CREATE TABLE IF NOT EXISTS blood_stock_adjustments (
                adjustment_id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
                blood_group VARCHAR(10) NOT NULL,
                delta INT NOT NULL,
                reason VARCHAR(255) DEFAULT NULL,
                created_by INT DEFAULT NULL,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                KEY idx_blood_group (blood_group)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");

        // Seed the 8 standard blood groups so the stock page always shows them
        $count = (int)$db->query("SELECT COUNT(*) FROM blood_stock")->fetchColumn();
        if ($count === 0) {
            $db->exec("INSERT INTO blood_stock (blood_group, available_bags) VALUES
                ('O+', 0), ('O-', 0), ('A+', 0), ('A-', 0),
                ('B+', 0), ('B-', 0), ('AB+', 0), ('AB-', 0)");
        }

        $this->migrated = true;
    }

    private function ensureTable(PDO $db, string $table, string $ddl): void
    {
        $stmt = $db->query(
            "SELECT COUNT(*) FROM information_schema.tables
             WHERE table_schema = DATABASE() AND table_name = '{$table}'"
        );

        if ((int)$stmt->fetchColumn() === 0) {
            $db->exec($ddl);
        }
    }

    private function ensureColumn(PDO $db, string $table, string $column, string $definition): void
    {
        $stmt = $db->query("SHOW COLUMNS FROM {$table} LIKE '{$column}'");

        if ($stmt->rowCount() === 0) {
            $db->exec("ALTER TABLE {$table} ADD COLUMN {$column} {$definition}");
        }
    }
}
