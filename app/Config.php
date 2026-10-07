<?php

declare(strict_types=1);

namespace App\Services;

final class AuthService
{
    public function __construct(private \PDO $pdo) {}

    public function loginOrRegister(string $email, string $password): array
    {
        $email = trim($email);
        $password = (string) $password;

        if ($email === '' || $password === '') {
            throw new \InvalidArgumentException('Email and password are required.');
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException('A valid email address is required.');
        }

        $user = \App\Models\User::findByEmail($this->pdo, $email);

        if (!$user) {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $user = \App\Models\User::create($this->pdo, $email, $hash, 'user');
            RoleManager::setRole($this->pdo, (int) $user['id'], RoleManager::resolveDefaultRole($email));
        }

        $resolvedRole = (string) ($user['role'] ?? RoleManager::resolveDefaultRole($email));
        if ($resolvedRole === 'user') {
            RoleManager::setRole($this->pdo, (int) ($user['id'] ?? 0), RoleManager::resolveDefaultRole($email));
            $resolvedRole = RoleManager::resolveDefaultRole($email);
        }

        if (!password_verify($password, (string) ($user['password_hash'] ?? '')) && isset($user['password_hash'])) {
            throw new \InvalidArgumentException('Invalid credentials.');
        }

        return [
            'id' => (int) ($user['id'] ?? 0),
            'email' => (string) ($user['email'] ?? $email),
            'role' => $resolvedRole,
        ];
    }
}
