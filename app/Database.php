<?php

declare(strict_types=1);

namespace App;

use PDO;
use PDOException;

final class Database
{
    private PDO $pdo;

    public function __construct(array $config)
    {
        $driver = $config['db_connection'] ?? 'sqlite';

        try {
            if ($driver === 'sqlite') {
                $path = $config['db_path'] ?? ':memory:';
                $this->pdo = new PDO('sqlite:' . $path);
            } else {
                $dsn = sprintf(
                    '%s:host=%s;port=%s;dbname=%s;charset=utf8mb4',
                    $driver,
                    $config['db_host'] ?? 'localhost',
                    $config['db_port'] ?? '5432',
                    $config['db_database'] ?? 'app',
                );
                $this->pdo = new PDO($dsn, $config['db_username'] ?? '', $config['db_password'] ?? '');
            }

            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Database connection failed', 'message' => $e->getMessage()]);
            exit;
        }
    }

    public function connection(): PDO
    {
        return $this->pdo;
    }
}
