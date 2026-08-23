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
        $this->ensureColumn($db, 'donors', 'is_pregnant', 'TINYINT(1) NOT NULL DEFAULT 0 AFTER township');
        $this->ensureColumn($db, 'users', 'next_available_date', 'DATETIME DEFAULT NULL');

        // Blood stock inventory (one row per blood group, keyed by label e.g. 'A+')
        $this->ensureTable($db, 'blood_stock', "
            CREATE TABLE IF NOT EXISTS blood_stock (
                blood_group VARCHAR(10) NOT NULL PRIMARY KEY,
                available_bags INT NOT NULL DEFAULT 0,
                last_updated TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");

        // Per-bag blood inventory with 42-day shelf-life expiry tracking.
        // One row per physical bag: status AVAILABLE -> ALLOCATED / EXPIRED.
        $this->ensureTable($db, 'blood_bags', "
            CREATE TABLE IF NOT EXISTS blood_bags (
                bag_id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
                blood_group VARCHAR(10) NOT NULL,
                donation_id INT DEFAULT NULL,
                collection_date DATE NOT NULL,
                expiry_date DATE NOT NULL,
                status VARCHAR(12) NOT NULL DEFAULT 'AVAILABLE',
                allocated_to_request_id INT DEFAULT NULL,
                allocated_at DATETIME DEFAULT NULL,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                KEY idx_blood_group_status (blood_group, status),
                KEY idx_expiry (expiry_date),
                KEY idx_donation (donation_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");

        // Backfill bag rows for any pre-existing summary counts so the bag
        // ledger and blood_stock totals stay consistent (idempotent: only runs
        // when the bag table is empty).
        $bagCount = (int)$db->query("SELECT COUNT(*) FROM blood_bags")->fetchColumn();
        if ($bagCount === 0) {
            $rows = $db->query(
                "SELECT blood_group, available_bags, DATE(last_updated) AS d
                 FROM blood_stock WHERE available_bags > 0"
            )->fetchAll(PDO::FETCH_ASSOC);
            if (!empty($rows)) {
                $ins = $db->prepare(
                    "INSERT INTO blood_bags (blood_group, collection_date, expiry_date, status)
                     VALUES (?, ?, DATE_ADD(?, INTERVAL 42 DAY), 'AVAILABLE')"
                );
                foreach ($rows as $r) {
                    $d = $r['d'] ?: date('Y-m-d');
                    for ($i = 0; $i < (int)$r['available_bags']; $i++) {
                        $ins->execute([$r['blood_group'], $d, $d]);
                    }
                }
            }
        }

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

        // =====================================================
        // Hospital role (user_type_id = 4) + hospital profile
        // =====================================================

        // Hospital profile (one row per hospital user)
        $this->ensureTable($db, 'hospitals', "
            CREATE TABLE IF NOT EXISTS hospitals (
                hospital_id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                hospital_name VARCHAR(150) DEFAULT NULL,
                license_no VARCHAR(50) DEFAULT NULL,
                state_region VARCHAR(100) DEFAULT NULL,
                township VARCHAR(100) DEFAULT NULL,
                address TEXT DEFAULT NULL,
                phone VARCHAR(20) DEFAULT NULL,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                KEY idx_user_id (user_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");

        // Hospital -> blood stock requests (fulfilled + outstanding shortfall)
        $this->ensureTable($db, 'hospital_blood_requests', "
            CREATE TABLE IF NOT EXISTS hospital_blood_requests (
                request_id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
                request_code VARCHAR(30) NOT NULL,
                hospital_user_id INT NOT NULL,
                hospital_name VARCHAR(150) DEFAULT NULL,
                blood_group VARCHAR(10) NOT NULL,
                quantity_requested INT NOT NULL DEFAULT 0,
                quantity_allocated INT NOT NULL DEFAULT 0,
                quantity_pending INT NOT NULL DEFAULT 0,
                status VARCHAR(20) NOT NULL DEFAULT 'PENDING',
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                KEY idx_hospital (hospital_user_id),
                KEY idx_status_group (status, blood_group)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");

        // Standalone donor donations: request_id is now nullable (a donation is
        // not necessarily linked to a patient blood request), and each row
        // records how many bags the donor gave (max 2 per donation).
        $this->ensureColumnNullable($db, 'donation_history', 'request_id');
        $this->ensureColumn($db, 'donation_history', 'bags', 'INT NOT NULL DEFAULT 1 AFTER status');

        // Seed the hospital user type (id 4)
        $utStmt = $db->prepare("SELECT COUNT(*) FROM user_types WHERE id = 4");
        $utStmt->execute();
        if ((int)$utStmt->fetchColumn() === 0) {
            $db->exec("INSERT INTO user_types (id, name, permissions, created_at) VALUES
                (4, 'hospital', 'dashboard,blood_stock,stock_requests,profile,notifications', NOW())");
        }

        // Seed new permissions required by the hospital role and donor donations.
        // Any permission inserted here is tracked so the role grants below only
        // run on first setup — otherwise every request would silently re-grant
        // permissions that an admin deliberately revoked via Roles & Permissions.
        $perms = [
            ['permission_id' => 38, 'permission_name' => 'View Blood Stock', 'permission_key' => 'blood_stock.view', 'description' => 'View blood stock levels'],
            ['permission_id' => 35, 'permission_name' => 'Create Stock Request', 'permission_key' => 'hospital_request.create', 'description' => 'Request blood bags from stock'],
            ['permission_id' => 36, 'permission_name' => 'View Stock Requests', 'permission_key' => 'hospital_request.view', 'description' => 'View own stock requests'],
            ['permission_id' => 37, 'permission_name' => 'Donate Blood', 'permission_key' => 'donation.create', 'description' => 'Record a standalone blood donation'],
        ];
        $newlyCreatedPerms = [];
        foreach ($perms as $perm) {
            $pStmt = $db->prepare("SELECT COUNT(*) FROM permissions WHERE permission_id = ?");
            $pStmt->execute([$perm['permission_id']]);
            if ((int)$pStmt->fetchColumn() === 0) {
                $ins = $db->prepare("INSERT INTO permissions (permission_id, permission_name, permission_key, description, created_at) VALUES (?, ?, ?, ?, NOW())");
                $ins->execute([$perm['permission_id'], $perm['permission_name'], $perm['permission_key'], $perm['description']]);
                $newlyCreatedPerms[] = $perm['permission_id'];
            }
        }

        // First-time grant for the hospital role (only when its permissions were
        // just created in this run). Skipped afterwards so admin revocations stick.
        $isHospitalFirstRun = count(array_intersect([35, 36, 38], $newlyCreatedPerms)) > 0;
        if ($isHospitalFirstRun) {
            // Note: permission id 34 is taken by report.view, so blood_stock.view uses id 38.
            $hospitalPerms = [13, 14, 15, 23, 35, 36, 38];
            foreach ($hospitalPerms as $permId) {
                $hpStmt = $db->prepare("SELECT COUNT(*) FROM user_type_permissions WHERE user_type_id = 4 AND permission_id = ?");
                $hpStmt->execute([$permId]);
                if ((int)$hpStmt->fetchColumn() === 0) {
                    $db->prepare("INSERT INTO user_type_permissions (user_type_id, permission_id) VALUES (4, ?)")->execute([$permId]);
                }
            }

            // Correction: hospitals were briefly granted permission 34 (report.view)
            // before blood_stock.view moved to id 38. Remove that stale grant once.
            $db->prepare("DELETE FROM user_type_permissions WHERE user_type_id = 4 AND permission_id = 34")->execute();
        }

        // First-time grant: donors may record standalone donations
        if (in_array(37, $newlyCreatedPerms, true)) {
            $donorPermStmt = $db->prepare("SELECT COUNT(*) FROM user_type_permissions WHERE user_type_id = 2 AND permission_id = 37");
            $donorPermStmt->execute();
            if ((int)$donorPermStmt->fetchColumn() === 0) {
                $db->prepare("INSERT INTO user_type_permissions (user_type_id, permission_id) VALUES (2, 37)")->execute();
            }
        }

        // Notification type for hospital stock requests (used with type 'STOCK_REQUEST')
        $ntStmt = $db->prepare("SELECT COUNT(*) FROM master_data WHERE category = 'NOTIFICATION_TYPE' AND code = 'STOCK_REQUEST'");
        $ntStmt->execute();
        if ((int)$ntStmt->fetchColumn() === 0) {
            $db->prepare("INSERT INTO master_data (category, code, label, created_at) VALUES ('NOTIFICATION_TYPE', 'STOCK_REQUEST', 'Stock Request', NOW())")->execute();
        }

        // First-time grant: admin access to the hospital stock request overview
        if (in_array(36, $newlyCreatedPerms, true)) {
            $adminPermStmt = $db->prepare("SELECT COUNT(*) FROM user_type_permissions WHERE user_type_id = 1 AND permission_id = 36");
            $adminPermStmt->execute();
            if ((int)$adminPermStmt->fetchColumn() === 0) {
                $db->prepare("INSERT INTO user_type_permissions (user_type_id, permission_id) VALUES (1, 36)")->execute();
            }
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

    private function ensureColumnNullable(PDO $db, string $table, string $column): void
    {
        $stmt = $db->query("SHOW COLUMNS FROM {$table} LIKE '{$column}'");

        if ($stmt->rowCount() === 0) {
            return;
        }

        $col = $stmt->fetch(PDO::FETCH_ASSOC);
        if (isset($col['Null']) && $col['Null'] === 'YES') {
            return;
        }

        $db->exec("ALTER TABLE {$table} MODIFY {$column} INT NULL");
    }
}
