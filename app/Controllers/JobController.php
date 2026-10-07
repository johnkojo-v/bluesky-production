<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\Job;
use PDO;

final class JobController
{
    public function __construct(private PDO $pdo) {}

    public function create(array $payload): array
    {
        $campaignId = (int) ($payload['campaign_id'] ?? 0);
        $action = trim((string) ($payload['action'] ?? ''));

        if ($campaignId <= 0 || $action === '') {
            throw new \InvalidArgumentException('Campaign and action are required.');
        }

        $job = Job::create($this->pdo, $campaignId, $action, $payload['payload'] ?? []);
        return ['status' => 'queued', 'job' => $job];
    }

    public function list(): array
    {
        return ['jobs' => Job::listRecent($this->pdo, 20)];
    }
}
