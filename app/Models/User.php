<?php

declare(strict_types=1);

namespace App\Models;

use PDO;

final class User
{
    public static function findByEmail(PDO $pdo, string $email): ?array
    {
        $stmt = $pdo->prepare('SELECT * FROM users WHERE email = :email LIMIT 1');
        $stmt->execute([':email' => trim($email)]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    public static function create(PDO $pdo, string $email, string $passwordHash, string $role = 'user'): array
    {
        $stmt = $pdo->prepare('INSERT INTO users (email, password_hash, role) VALUES (:email, :password_hash, :role)');
        $stmt->execute([
            ':email' => trim($email),
            ':password_hash' => $passwordHash,
            ':role' => $role,
        ]);

        $id = (int) $pdo->lastInsertId();
        return [
            'id' => $id,
            'email' => trim($email),
            'role' => $role,
        ];
    }
}
