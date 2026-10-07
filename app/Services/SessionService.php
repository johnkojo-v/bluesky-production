<?php

declare(strict_types=1);

namespace App\Services;

final class SessionService
{
    public function __construct(private string $sessionName = 'bluesky_prod_session') {}

    public function start(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_name($this->sessionName);
            session_start();
        }
    }

    public function setUser(array $user): void
    {
        $this->start();
        $_SESSION['user'] = [
            'id' => (int) ($user['id'] ?? 0),
            'email' => (string) ($user['email'] ?? ''),
            'role' => (string) ($user['role'] ?? 'user'),
        ];
    }

    public function user(): ?array
    {
        $this->start();
        return $_SESSION['user'] ?? null;
    }

    public function destroy(): void
    {
        $this->start();
        $_SESSION = [];
        session_destroy();
    }
}
