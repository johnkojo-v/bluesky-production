<?php

declare(strict_types=1);

require_once __DIR__ . '/../app/Config.php';
require_once __DIR__ . '/../app/Database.php';
require_once __DIR__ . '/../app/Bootstrap.php';
require_once __DIR__ . '/../app/Models/User.php';
require_once __DIR__ . '/../app/Models/Campaign.php';
require_once __DIR__ . '/../app/Services/AuthService.php';
require_once __DIR__ . '/../app/Services/CampaignService.php';
require_once __DIR__ . '/../app/Controllers/AuthController.php';
require_once __DIR__ . '/../app/Controllers/CampaignController.php';
require_once __DIR__ . '/../app/Http/Router.php';

use App\Bootstrap;
use App\Config;
use App\Database;
use App\Http\Router;

$config = Config::load(__DIR__ . '/..');
$db = new Database($config);
$pdo = $db->connection();
Bootstrap::ensureSchema($pdo);

$uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/';
$method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');

try {
    if ($uri === '/' || $uri === '/index.php') {
        echo <<<'HTML'
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Bluesky Production Engine</title>
  <style>
    body { margin: 0; font-family: Arial, sans-serif; background: #020817; color: #e2e8f0; }
    .wrap { max-width: 1100px; margin: 0 auto; padding: 40px 20px; }
    .card { background: #111827; border: 1px solid #334155; border-radius: 16px; padding: 24px; margin-bottom: 24px; }
    .badge { display: inline-block; background: #16a34a; color: white; padding: 6px 12px; border-radius: 999px; font-size: 12px; font-weight: bold; }
    .grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 20px; }
    input, button { width: 100%; padding: 12px 14px; border-radius: 10px; border: 1px solid #475569; background: #0f172a; color: #f8fafc; margin-bottom: 12px; }
    button { background: #2563eb; border: none; cursor: pointer; font-weight: 700; }
    label { display: block; margin-bottom: 8px; color: #cbd5e1; }
    ul { color: #cbd5e1; line-height: 1.8; }
  </style>
</head>
<body>
  <div class="wrap">
    <div class="card">
      <span class="badge">Production foundation</span>
      <h1>Bluesky Production Engine</h1>
      <p>We are converting the mock system into a production-ready web app.</p>
    </div>

    <div class="grid">
      <div class="card">
        <h2>Login demo</h2>
        <form action="/api/auth/login" method="post">
          <label>Email</label>
          <input type="email" name="email" placeholder="user@example.com" required />
          <label>Password</label>
          <input type="password" name="password" placeholder="Password" required />
          <button type="submit">Login / Create</button>
        </form>
      </div>

      <div class="card">
        <h2>Campaign demo</h2>
        <form action="/api/campaigns" method="post">
          <label>Campaign name</label>
          <input type="text" name="name" placeholder="Spring launch" required />
          <label>Topic</label>
          <input type="text" name="topic" placeholder="AI growth" />
          <button type="submit">Create campaign</button>
        </form>
      </div>
    </div>

    <div class="card">
      <h2>Next roadmap</h2>
      <ul>
        <li>Database-backed campaign management</li>
        <li>Bluesky authentication service</li>
        <li>Queued post and follow actions</li>
        <li>Admin dashboard and user roles</li>
        <li>HTTPS + deployment hardening</li>
      </ul>
    </div>
  </div>
</body>
</html>
HTML;
        exit;
    }

    Router::dispatch($uri, $method, $pdo);
} catch (\Throwable $e) {
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode([
        'error' => 'internal_server_error',
        'message' => $e->getMessage(),
    ]);
}
