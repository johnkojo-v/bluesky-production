<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Token;
use PDO;

final class BlueskyRealAuthService
{
    public function __construct(
        private PDO $pdo,
        private string $jwtSecret,
        private string $baseUrl,
    ) {}

    public function saveEncryptedToken(int $userId, string $accessToken, string $refreshToken): void
    {
        $access = TokenEncryption::encrypt($accessToken, $this->jwtSecret);
        $refresh = TokenEncryption::encrypt($refreshToken, $this->jwtSecret);
        Token::save($this->pdo, $userId, 'bluesky', $access, $refresh);
    }

    public function latestToken(int $userId): ?array
    {
        $row = Token::getLatest($this->pdo, $userId, 'bluesky');
        if (!$row) {
            return null;
        }

        try {
            $row['access_token'] = TokenEncryption::decrypt((string) $row['access_token'], $this->jwtSecret);
            $row['refresh_token'] = TokenEncryption::decrypt((string) $row['refresh_token'], $this->jwtSecret);
        } catch (\Throwable) {
            return null;
        }

        return $row;
    }

    public function requestSession(string $handle, string $password): array
    {
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => rtrim($this->baseUrl, '/') . '/xrpc/com.atproto.server.createSession',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode([
                'identifier' => $handle,
                'password' => $password,
            ]),
            CURLOPT_HTTPHEADER => ['Content-Type: application/json', 'Accept: application/json'],
        ]);

        $response = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($response === false) {
            throw new \RuntimeException('Bluesky auth session failed.');
        }

        $data = json_decode($response, true);
        if (!is_array($data)) {
            throw new \RuntimeException('Invalid auth response from Bluesky.');
        }

        return ['http_code' => $code, 'payload' => $data];
    }
}
