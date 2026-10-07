<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\Campaign;
use App\Services\AuthService;
use PDO;

final class AuthController
{
    public function __construct(private PDO $pdo) {}

    public function login(array $payload): array
    {
        $email = trim((string) ($payload['email'] ?? ''));
        $password = (string) ($payload['password'] ?? '');

        $service = new AuthService($this->pdo);
        return $service->loginOrRegister($email, $password);
    }
}
