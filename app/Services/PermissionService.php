<?php

declare(strict_types=1);

namespace App\Services;

use PDO;

final class RoleManager
{
    public static function setRole(PDO $pdo, int $userId, string $role): void
    {
        $normalized = strtolower(trim($role));
        if (!in_array($normalized, ['admin', 'editor', 'viewer'], true)) {
            $normalized = 'viewer';
        }

        $pdo->prepare('DELETE FROM user_roles WHERE user_id = :user_id')->execute([':user_id' => $userId]);

        $stmt = $pdo->prepare(
            'INSERT INTO user_roles (user_id, role_id) VALUES (:user_id, (SELECT id FROM roles WHERE name = :role_name LIMIT 1))'
        );
        $stmt->execute([':user_id' => $userId, ':role_name' => $normalized]);
    }

    public static function resolveDefaultRole(string $email): string
    {
        $normalized = strtolower(trim($email));
        if (str_contains($normalized, 'admin')) {
            return 'admin';
        }

        return 'viewer';
    }
}
