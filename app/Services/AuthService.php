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

        $permissions = [
            'campaign.create',
            'campaign.view',
            'job.queue',
            'job.view',
            'admin.access',
        ];

        foreach ($permissions as $permission) {
            $this->pdo->prepare('INSERT OR IGNORE INTO permissions (name) VALUES (:name)')->execute([':name' => $permission]);
        }

        $rolePerms = [
            'admin' => ['campaign.create', 'campaign.view', 'job.queue', 'job.view', 'admin.access'],
            'editor' => ['campaign.create', 'campaign.view', 'job.queue', 'job.view'],
            'viewer' => ['campaign.view', 'job.view'],
        ];

        foreach ($rolePerms as $roleName => $perms) {
            $roleId = $this->pdo->prepare('SELECT id FROM roles WHERE name = :name LIMIT 1');
            $roleId->execute([':name' => $roleName]);
            $roleRow = $roleId->fetch();
            if (!$roleRow) {
                continue;
            }

            foreach ($perms as $permName) {
                $permId = $this->pdo->prepare('SELECT id FROM permissions WHERE name = :name LIMIT 1');
                $permId->execute([':name' => $permName]);
                $permRow = $permId->fetch();
                if (!$permRow) {
                    continue;
                }

                $this->pdo->prepare(
                    'INSERT OR IGNORE INTO role_permissions (role_id, permission_id) VALUES (:role_id, :permission_id)'
                )->execute([
                    ':role_id' => (int) $roleRow['id'],
                    ':permission_id' => (int) $permRow['id'],
                ]);
            }
        }
    }

    public function assignRolePermission(string $roleName, string $permissionName): void
    {
        $role = $this->pdo->prepare('SELECT id FROM roles WHERE name = :role_name LIMIT 1');
        $role->execute([':role_name' => $roleName]);
        $roleRow = $role->fetch();

        $permission = $this->pdo->prepare('SELECT id FROM permissions WHERE name = :permission_name LIMIT 1');
        $permission->execute([':permission_name' => $permissionName]);
        $permRow = $permission->fetch();

        if (!$roleRow || !$permRow) {
            return;
        }

        $this->pdo->prepare(
            'INSERT OR IGNORE INTO role_permissions (role_id, permission_id) VALUES (:role_id, :permission_id)'
        )->execute([
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
