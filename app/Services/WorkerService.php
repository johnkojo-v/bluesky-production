<?php

declare(strict_types=1);

namespace App\Services;

use PDO;

final class WorkerService
{
    public function __construct(private PDO $pdo) {}

    public function processNext(): ?array
    {
        $job = $this->pdo->query(
            'SELECT * FROM jobs WHERE status = "queued" ORDER BY id ASC LIMIT 1'
        )->fetch();

        if (!$job) {
            return null;
        }

        $this->pdo->prepare('UPDATE jobs SET status = :status, result = :result WHERE id = :id')->execute([
            ':status' => 'processing',
            ':result' => json_encode(['status' => 'processing', 'started_at' => gmdate('c')]),
            ':id' => (int) $job['id'],
        ]);

        $result = [
            'job_id' => (int) $job['id'],
            'action' => (string) $job['action'],
            'status' => 'processed',
            'processed_at' => gmdate('c'),
        ];

        $this->pdo->prepare('UPDATE jobs SET status = :status, result = :result WHERE id = :id')->execute([
            ':status' => 'done',
            ':result' => json_encode($result),
            ':id' => (int) $job['id'],
        ]);

        return $result;
    }

    public function runBatch(int $limit = 10): array
    {
        $results = [];
        $count = 0;

        while ($count < $limit) {
            $item = $this->processNext();
            if ($item === null) {
                break;
            }
            $results[] = $item;
            $count++;
        }

        return $results;
    }
}
