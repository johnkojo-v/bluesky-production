<?php

declare(strict_types=1);

namespace App\Models;

use PDO;

final class Job
{
    public static function create(PDO $pdo, int $campaignId, string $action, array $payload = []): array
    {
        $campaignId = (int) $campaignId;
        $action = trim($action);

        if ($campaignId <= 0 || $action === '') {
            throw new \InvalidArgumentException('A valid campaign and action are required.');
        }

        $stmt = $pdo->prepare(
            'INSERT INTO jobs (campaign_id, action, payload, status, result) VALUES (:campaign_id, :action, :payload, :status, :result)'
        );
        $stmt->execute([
            ':campaign_id' => $campaignId,
            ':action' => $action,
            ':payload' => json_encode($payload),
            ':status' => 'queued',
            ':result' => json_encode(['queued' => true]),
        ]);

        $jobId = (int) $pdo->lastInsertId();
        $fetch = $pdo->prepare('SELECT * FROM jobs WHERE id = :id LIMIT 1');
        $fetch->execute([':id' => $jobId]);
        $row = $fetch->fetch();

        return $row ?: ['id' => $jobId, 'campaign_id' => $campaignId, 'action' => $action, 'status' => 'queued'];
    }

    public static function listRecent(PDO $pdo, int $limit = 20): array
    {
        $stmt = $pdo->query('SELECT * FROM jobs ORDER BY id DESC LIMIT ' . (int) $limit);
        return $stmt->fetchAll();
    }
}
