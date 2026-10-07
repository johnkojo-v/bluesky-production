<?php

declare(strict_types=1);

namespace App\Http;

use App\Controllers\AuthController;
use App\Controllers\CampaignController;
use App\Controllers\JobController;
use App\Services\AuthGuard;
use App\Services\PermissionService;
use App\Services\SessionService;
use PDO;

final class Router
{
    public static function dispatch(string $uri, string $method, PDO $pdo): void
    {
        $session = new SessionService();
        $session->start();

        if ($uri === '/health') {
            header('Content-Type: application/json');
            echo json_encode(['status' => 'ok', 'service' => 'bluesky-production', 'timestamp' => gmdate('c')]);
            return;
        }

        if ($method === 'POST' && $uri === '/api/auth/login') {
            $payload = json_decode(file_get_contents('php://input'), true) ?? $_POST;
            try {
                $controller = new AuthController($pdo);
                $user = $controller->login($payload);
                $session->setUser($user);
                header('Content-Type: application/json');
                echo json_encode(['status' => 'success', 'user' => $user]);
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
                $guard = new AuthGuard($pdo, $session);
                $guard->requirePermission('campaign.create');
                $controller = new CampaignController($pdo);
                header('Content-Type: application/json');
                echo json_encode($controller->create($payload));
                return;
            } catch (\Throwable $e) {
                http_response_code(403);
                echo json_encode(['error' => $e->getMessage()]);
                return;
            }
        }

        if ($method === 'POST' && $uri === '/api/jobs') {
            $payload = json_decode(file_get_contents('php://input'), true) ?? $_POST;
            try {
                $guard = new AuthGuard($pdo, $session);
                $guard->requirePermission('job.queue');
                $controller = new JobController($pdo);
                header('Content-Type: application/json');
                echo json_encode($controller->create($payload));
                return;
            } catch (\Throwable $e) {
                http_response_code(403);
                echo json_encode(['error' => $e->getMessage()]);
                return;
            }
        }

        if ($method === 'GET' && $uri === '/api/campaigns') {
            $guard = new AuthGuard($pdo, $session);
            $guard->requirePermission('campaign.view');
            $controller = new CampaignController($pdo);
            header('Content-Type: application/json');
            echo json_encode($controller->list());
            return;
        }

        if ($method === 'GET' && $uri === '/api/jobs') {
            $guard = new AuthGuard($pdo, $session);
            $guard->requirePermission('job.view');
            $controller = new JobController($pdo);
            header('Content-Type: application/json');
            echo json_encode($controller->list());
            return;
        }

        throw new \RuntimeException('Route not found: ' . $uri);
    }
}
