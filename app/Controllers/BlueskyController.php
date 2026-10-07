<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\BlueskySessionManager;
use App\Services\SessionService;
use App\Services\TokenEncryption;
use PDO;

final class BlueskyController
{
    public function __construct(
        private PDO $pdo,
        private SessionService $session,
        private BlueskySessionManager $bluesky,
        private string $jwtSecret,
    ) {}

    public function authenticate(array $payload): array
    {
        $handle = trim((string) ($payload['handle'] ?? ''));
        $password = (string) ($payload['password'] ?? '');

        if ($handle === '' || $password === '') {
            throw new \InvalidArgumentException('Handle and password are required.');
        }

        $auth = $this->bluesky->authenticate($handle, $password);

        $stmt = $this->pdo->prepare(
            'INSERT OR REPLACE INTO provider_tokens (user_id, provider, access_token, refresh_token, created_at) VALUES (:user_id, :provider, :access_token, :refresh_token, :created_at)'
        );

        $accessEncrypted = TokenEncryption::encrypt($auth['access_jwt'], $this->jwtSecret);
        $refreshEncrypted = TokenEncryption::encrypt($auth['refresh_jwt'], $this->jwtSecret);

        $stmt->execute([
            ':user_id' => 1,
            ':provider' => 'bluesky',
            ':access_token' => $accessEncrypted,
            ':refresh_token' => $refreshEncrypted,
            ':created_at' => gmdate('c'),
        ]);

        return [
            'status' => 'authenticated',
            'did' => $auth['did'],
            'handle' => $auth['handle'],
            'email' => $auth['email'] ?? null,
        ];
    }
}
