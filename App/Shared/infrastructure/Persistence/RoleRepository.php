<?php

namespace App\Shared\Infrastructure\Persistence;

use App\Shared\Infrastructure\Database\Database;
use PDO;

class RoleRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    public function getAllRoles(): array
    {
        $stmt = $this->db->query("
        SELECT
            ut.id,
            ut.name,
            COUNT(DISTINCT u.user_id) AS total_users,
            COUNT(DISTINCT utp.permission_id) AS total_permissions
        FROM user_types ut

        LEFT JOIN users u
            ON u.user_type_id = ut.id

        LEFT JOIN user_type_permissions utp
            ON ut.id = utp.user_type_id

        WHERE ut.id <> 1

        GROUP BY ut.id, ut.name

        ORDER BY ut.id
    ");

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare("
        SELECT *
        FROM user_types
        WHERE id = ?
        LIMIT 1
    ");

        $stmt->execute([$id]);

        $role = $stmt->fetch(PDO::FETCH_ASSOC);

        return $role ?: null;
    }

    public function getAllPermissions(): array
    {
        $stmt = $this->db->query("
        SELECT
            permission_id,
            permission_name,
            permission_key,
            description
        FROM permissions
        ORDER BY permission_name
    ");

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getRolePermissionIds(int $roleId): array
    {
        $stmt = $this->db->prepare("
        SELECT permission_id
        FROM user_type_permissions
        WHERE user_type_id = ?
    ");

        $stmt->execute([$roleId]);

        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    public function updateRolePermissions($roleId, $permissions)
    {
        // Remove old permissions
        $stmt = $this->db->prepare(
            "DELETE FROM user_type_permissions
         WHERE user_type_id = ?"
        );

        $stmt->execute([$roleId]);


        // Add new permissions
        $stmt = $this->db->prepare(
            "INSERT INTO user_type_permissions
        (user_type_id, permission_id)
        VALUES (?, ?)"
        );


        foreach ($permissions as $permissionId) {

            $stmt->execute([
                $roleId,
                $permissionId
            ]);
        }
    }

}