<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Job;
use PDO;

final class JobService
{
    public function __construct(private PDO $pdo) {}

    public function create(int $campaignId, string $action, array $payload = []): array
    {
        return Job::create($this->pdo, $campaignId, $action, $payload);
    }

    public function listRecent(int $limit = 20): array
    {
        return Job::listRecent($this->pdo, $limit);
    }
}
