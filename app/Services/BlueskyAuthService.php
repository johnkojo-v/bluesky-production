<?php

declare(strict_types=1);

namespace App\Services;

use PDO;

final class BlueskyAuthService
{
    public function __construct(private PDO $pdo) {}

    public function storeTokens(int $userId, string $accessToken, string $refreshToken): void
    {
        $this->pdo->prepare(
            'INSERT INTO provider_tokens (user_id, provider, access_token, refresh_token, created_at) VALUES (:user_id, :provider, :access_token, :refresh_token, :created_at)'
        )->execute([
            ':user_id' => $userId,
            ':provider' => 'bluesky',
            ':access_token' => $accessToken,
            ':refresh_token' => $refreshToken,
            ':created_at' => gmdate('c'),
        ]);
    }

    public function getLatestToken(int $userId): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM provider_tokens WHERE user_id = :user_id AND provider = :provider ORDER BY id DESC LIMIT 1'
        );
        $stmt->execute([':user_id' => $userId, ':provider' => 'bluesky']);
        $row = $stmt->fetch();
        return $row ?: null;
    }
}
