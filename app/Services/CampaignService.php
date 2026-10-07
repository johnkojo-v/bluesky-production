<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Campaign;
use PDO;

final class CampaignService
{
    public function __construct(private PDO $pdo) {}

    public function create(string $name, string $topic): array
    {
        $name = trim($name);
        $topic = trim($topic);

        if ($name === '') {
            throw new \InvalidArgumentException('Campaign name is required.');
        }

        return Campaign::create($this->pdo, 1, $name, $topic);
    }

    public function listRecent(int $limit = 20): array
    {
        return Campaign::listRecent($this->pdo, $limit);
    }
}
