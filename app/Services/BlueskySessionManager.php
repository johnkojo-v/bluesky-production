<?php

declare(strict_types=1);

namespace App\Services;

final class BlueskySessionManager
{
    public function __construct(
        private string $pdsUrl,
        private string $apiUrl,
    ) {}

    public function authenticate(string $handle, string $password): array
    {
        $response = $this->makeRequest(
            'POST',
            '/xrpc/com.atproto.server.createSession',
            [
                'identifier' => $handle,
                'password' => $password,
            ],
            false
        );

        if ($response['http_code'] !== 200) {
            throw new \RuntimeException('Bluesky authentication failed: ' . ($response['payload']['message'] ?? 'Unknown error'));
        }

        return [
            'did' => $response['payload']['did'] ?? '',
            'handle' => $response['payload']['handle'] ?? $handle,
            'access_jwt' => $response['payload']['accessJwt'] ?? '',
            'refresh_jwt' => $response['payload']['refreshJwt'] ?? '',
            'email' => $response['payload']['email'] ?? '',
        ];
    }

    public function refreshSession(string $refreshToken): array
    {
        $response = $this->makeRequest(
            'POST',
            '/xrpc/com.atproto.server.refreshSession',
            [],
            true,
            $refreshToken
        );

        if ($response['http_code'] !== 200) {
            throw new \RuntimeException('Session refresh failed.');
        }

        return [
            'access_jwt' => $response['payload']['accessJwt'] ?? '',
            'refresh_jwt' => $response['payload']['refreshJwt'] ?? '',
        ];
    }

    public function getSession(string $accessToken): array
    {
        $response = $this->makeRequest(
            'GET',
            '/xrpc/com.atproto.server.getSession',
            [],
            true,
            $accessToken
        );

        if ($response['http_code'] !== 200) {
            throw new \RuntimeException('Failed to get session.');
        }

        return $response['payload'];
    }

    private function makeRequest(
        string $method,
        string $path,
        array $payload = [],
        bool $auth = false,
        ?string $token = null
    ): array {
        $url = rtrim($this->pdsUrl, '/') . $path;
        $ch = curl_init();

        $headers = [
            'Content-Type: application/json',
            'Accept: application/json',
        ];

        if ($auth && $token) {
            $headers[] = 'Authorization: Bearer ' . $token;
        }

        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);

        if ($method === 'POST' && !empty($payload)) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        }

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($response === false) {
            throw new \RuntimeException('Bluesky API error: ' . $error);
        }

        $decoded = json_decode($response, true);
        if (!is_array($decoded)) {
            $decoded = ['error' => 'Invalid response'];
        }

        return [
            'http_code' => $httpCode,
            'payload' => $decoded,
        ];
    }
}
