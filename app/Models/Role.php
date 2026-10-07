<?php

declare(strict_types=1);

namespace App\Models;

use PDO;

final class Role
{
    public static function ensure(PDO $pdo): void
    {
        $pdo->exec(
            "CREATE TABLE IF NOT EXISTS roles (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT NOT NULL UNIQUE,
                created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
            );"
        );

        $existing = $pdo->query("SELECT COUNT(*) as total FROM roles")->fetch();
        if ((int) ($existing['total'] ?? 0) === 0) {
            $pdo->exec("INSERT INTO roles (name) VALUES ('admin'), ('editor'), ('viewer');");
        }
    }

    public static function assign(PDO $pdo, int $userId, string $role): void
    {
        $role = trim($role);
        $pdo->exec("DELETE FROM user_roles WHERE user_id = " . (int) $userId);
        $stmt = $pdo->prepare('INSERT INTO user_roles (user_id, role_id) VALUES (:user_id, (SELECT id FROM roles WHERE name = :role))');
        $stmt->execute([':user_id' => $userId, ':role' => $role]);
    }
}
