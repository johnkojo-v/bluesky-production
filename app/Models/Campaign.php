<?php

declare(strict_types=1);

namespace App\Models;

use PDO;

final class Campaign
{
    public static function create(PDO $pdo, int $userId, string $name, string $topic): array
    {
        $name = trim($name);
        $topic = trim($topic);

        if ($name === '') {
            throw new \InvalidArgumentException('Campaign name is required.');
        }

        $stmt = $pdo->prepare(
            'INSERT INTO campaigns (user_id, name, topic, status, metrics) VALUES (:user_id, :name, :topic, :status, :metrics)'
        );
        $stmt->execute([
            ':user_id' => $userId,
            ':name' => $name,
            ':topic' => $topic,
            ':status' => 'draft',
            ':metrics' => json_encode(['engagement' => 0, 'reach' => 0]),
        ]);

        $campaignId = (int) $pdo->lastInsertId();

        $fetch = $pdo->prepare('SELECT * FROM campaigns WHERE id = :id LIMIT 1');
        $fetch->execute([':id' => $campaignId]);
        $row = $fetch->fetch();

        return $row ?: ['id' => $campaignId, 'name' => $name, 'topic' => $topic, 'status' => 'draft'];
    }

    public static function listRecent(PDO $pdo, int $limit = 20): array
    {
        $stmt = $pdo->query('SELECT * FROM campaigns ORDER BY id DESC LIMIT ' . (int) $limit);
        return $stmt->fetchAll();
    }
}
