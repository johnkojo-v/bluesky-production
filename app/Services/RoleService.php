<?php

declare(strict_types=1);

namespace App\Services;

use PDO;

final class RoleService
{
    public function __construct(private PDO $pdo) {}

    public function ensure(): void
    {
        $this->pdo->exec(
            "CREATE TABLE IF NOT EXISTS user_roles (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER NOT NULL,
                role_id INTEGER NOT NULL,
                created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id),
                FOREIGN KEY (role_id) REFERENCES roles(id)
            );"
        );

        $this->pdo->exec(
            "CREATE TABLE IF NOT EXISTS permissions (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT NOT NULL UNIQUE,
                created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
            );"
        );

        $this->pdo->exec(
            "CREATE TABLE IF NOT EXISTS role_permissions (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                role_id INTEGER NOT NULL,
                permission_id INTEGER NOT NULL,
                created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (role_id) REFERENCES roles(id),
                FOREIGN KEY (permission_id) REFERENCES permissions(id)
            );"
        );
    }

    public function can(int $userId, string $permission): bool
    {
        $stmt = $this->pdo->prepare(
            "SELECT COUNT(*) as total FROM role_permissions rp
             JOIN user_roles ur ON ur.role_id = rp.role_id
             JOIN permissions p ON p.id = rp.permission_id
             WHERE ur.user_id = :user_id AND p.name = :permission"
        );
        $stmt->execute([':user_id' => $userId, ':permission' => $permission]);
        $row = $stmt->fetch();
        return ((int) ($row['total'] ?? 0)) > 0;
    }
}
