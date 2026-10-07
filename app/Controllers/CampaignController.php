<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\Campaign;
use PDO;

final class CampaignController
{
    public function __construct(private PDO $pdo) {}

    public function create(array $payload): array
    {
        $name = trim((string) ($payload['name'] ?? ''));
        $topic = trim((string) ($payload['topic'] ?? ''));

        if ($name === '') {
            throw new \InvalidArgumentException('Campaign name is required.');
        }

        $campaign = Campaign::create($this->pdo, 1, $name, $topic);
        return ['status' => 'created', 'campaign' => $campaign];
    }

    public function list(): array
    {
        return ['campaigns' => Campaign::listRecent($this->pdo, 20)];
    }
}
