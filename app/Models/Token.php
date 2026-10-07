<?php

declare(strict_types=1);

namespace App\Models;

use PDO;

final class Token
{
    public static function save(PDO $pdo, int $userId, string $provider, string $accessToken, string $refreshToken): void
    {
        $stmt = $pdo->prepare(
            'INSERT INTO provider_tokens (user_id, provider, access_token, refresh_token, created_at) VALUES (:user_id, :provider, :access_token, :refresh_token, :created_at)'
        );
        $stmt->execute([
            ':user_id' => $userId,
            ':provider' => $provider,
            ':access_token' => $accessToken,
            ':refresh_token' => $refreshToken,
            ':created_at' => gmdate('c'),
        ]);
    }

    public static function getLatest(PDO $pdo, int $userId, string $provider): ?array
    {
        $stmt = $pdo->prepare(
            'SELECT * FROM provider_tokens WHERE user_id = :user_id AND provider = :provider ORDER BY id DESC LIMIT 1'
        );
        $stmt->execute([':user_id' => $userId, ':provider' => $provider]);
        $row = $stmt->fetch();
        return $row ?: null;
    }
}
