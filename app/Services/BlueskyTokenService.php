<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Token;
use PDO;

final class BlueskyTokenService
{
    public function __construct(private PDO $pdo) {}

    public function saveToken(int $userId, string $provider, string $accessToken, string $refreshToken): void
    {
        Token::save($this->pdo, $userId, $provider, $accessToken, $refreshToken);
    }

    public function latest(int $userId, string $provider): ?array
    {
        return Token::getLatest($this->pdo, $userId, $provider);
    }
}
