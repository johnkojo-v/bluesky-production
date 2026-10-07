<?php

declare(strict_types=1);

namespace App\Services;

final class BlueskyService
{
    public function __construct(
        private string $baseUrl,
        private ?string $token = null,
    ) {}

    public function createSession(string $identifier, string $password): array
    {
        return $this->request(
            'POST',
            '/xrpc/com.atproto.server.createSession',
            ['identifier' => $identifier, 'password' => $password],
            false,
        );
    }

    public function createPost(string $text): array
    {
        $record = [
            '$type' => 'app.bsky.feed.post',
            'text' => $text,
            'createdAt' => gmdate('c'),
        ];

        return $this->request(
            'POST',
            '/xrpc/com.atproto.repo.createRecord',
            [
                'repo' => 'self',
                'collection' => 'app.bsky.feed.post',
                'record' => $record,
            ],
            true,
        );
    }

    private function request(string $method, string $path, array $payload = [], bool $auth = true): array
    {
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, rtrim($this->baseUrl, '/') . $path);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_HTTPHEADER, array_filter([
            'Content-Type: application/json',
            'Accept: application/json',
            $auth && $this->token ? 'Authorization: Bearer ' . $this->token : null,
        ]));

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($response === false) {
            throw new \RuntimeException('Bluesky API error: ' . $error);
        }

        $decoded = json_decode($response, true);
        if (!is_array($decoded)) {
            $decoded = ['raw' => $response];
        }

        return ['http_code' => $httpCode, 'payload' => $decoded];
    }
}
