<?php

declare(strict_types=1);

namespace App\Http;

use App\Controllers\AuthController;
use App\Controllers\CampaignController;
use PDO;

final class Router
{
    public static function dispatch(string $uri, string $method, PDO $pdo): void
    {
        if ($uri === '/health') {
            header('Content-Type: application/json');
            echo json_encode([
                'status' => 'ok',
                'service' => 'bluesky-production',
                'timestamp' => gmdate('c'),
            ]);
            return;
        }

        if ($method === 'POST' && $uri === '/api/auth/login') {
            $payload = json_decode(file_get_contents('php://input'), true) ?? $_POST;
            try {
                $controller = new AuthController($pdo);
                header('Content-Type: application/json');
                echo json_encode(['status' => 'success', 'user' => $controller->login($payload)]);
                return;
            } catch (\Throwable $e) {
                http_response_code(422);
                echo json_encode(['error' => $e->getMessage()]);
                return;
            }
        }

        if ($method === 'POST' && $uri === '/api/campaigns') {
            $payload = json_decode(file_get_contents('php://input'), true) ?? $_POST;
            try {
                $controller = new CampaignController($pdo);
                header('Content-Type: application/json');
                echo json_encode($controller->create($payload));
                return;
            } catch (\Throwable $e) {
                http_response_code(422);
                echo json_encode(['error' => $e->getMessage()]);
                return;
            }
        }

        if ($method === 'GET' && $uri === '/api/campaigns') {
            $controller = new CampaignController($pdo);
            header('Content-Type: application/json');
            echo json_encode($controller->list());
            return;
        }

        throw new \RuntimeException('Route not found: ' . $uri);
    }
}
