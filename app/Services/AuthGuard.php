<?php

declare(strict_types=1);

namespace App\Services;

use PDO;

final class AuthGuard
{
    public function __construct(private PDO $pdo, private SessionService $session) {}

    public function requirePermission(string $permission): void
    {
        $user = $this->session->user();
        if (!$user) {
            throw new \RuntimeException('Authentication required.');
        }

        $svc = new PermissionService($this->pdo);
        if (!$svc->userHasPermission((int) $user['id'], $permission)) {
            throw new \RuntimeException('Permission denied.');
        }
    }
}
