<?php

declare(strict_types=1);

namespace App\Services;

use PDO;

final class PermissionService
{
    public function __construct(private PDO $pdo) {}

    public function installDefaults(): void
    {
        $this->pdo->exec("CREATE TABLE IF NOT EXISTS permissions (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT NOT NULL UNIQUE, created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP);");
        $this->pdo->exec("CREATE TABLE IF NOT EXISTS role_permissions (id INTEGER PRIMARY KEY AUTOINCREMENT, role_id INTEGER NOT NULL, permission_id INTEGER NOT NULL, created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP, FOREIGN KEY (role_id) REFERENCES roles(id), FOREIGN KEY (permission_id) REFERENCES permissions(id));");

        $existing = $this->pdo->query("SELECT COUNT(*) as total FROM permissions")->fetch();
        if ((int) ($existing['total'] ?? 0) === 0) {
            $this->pdo->exec("INSERT INTO permissions (name) VALUES ('campaign.create'), ('campaign.view'), ('job.queue'), ('job.view'), ('admin.access');");
        }
    }

    public function assignRolePermission(string $roleName, string $permissionName): void
    {
        $roleSql = 'SELECT id FROM roles WHERE name = :role_name LIMIT 1';
        $permSql = 'SELECT id FROM permissions WHERE name = :permission_name LIMIT 1';

        $role = $this->pdo->prepare($roleSql);
        $role->execute([':role_name' => $roleName]);
        $roleRow = $role->fetch();

        $perm = $this->pdo->prepare($permSql);
        $perm->execute([':permission_name' => $permissionName]);
        $permRow = $perm->fetch();

        if (!$roleRow || !$permRow) {
            return;
        }

        $insert = $this->pdo->prepare(
            'INSERT OR IGNORE INTO role_permissions (role_id, permission_id) VALUES (:role_id, :permission_id)'
        );
        $insert->execute([
            ':role_id' => (int) $roleRow['id'],
            ':permission_id' => (int) $permRow['id'],
        ]);
    }

    public function userHasPermission(int $userId, string $permissionName): bool
    {
        $stmt = $this->pdo->prepare(
            "SELECT COUNT(*) as total FROM user_roles ur
             JOIN roles r ON r.id = ur.role_id
             JOIN role_permissions rp ON rp.role_id = r.id
             JOIN permissions p ON p.id = rp.permission_id
             WHERE ur.user_id = :user_id AND p.name = :permission_name"
        );
        $stmt->execute([':user_id' => $userId, ':permission_name' => $permissionName]);
        $row = $stmt->fetch();

        return ((int) ($row['total'] ?? 0)) > 0;
    }
}
