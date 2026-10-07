<?php

declare(strict_types=1);

require_once __DIR__ . '/../app/Config.php';
require_once __DIR__ . '/../app/Database.php';
require_once __DIR__ . '/../app/Bootstrap.php';
require_once __DIR__ . '/../app/Models/User.php';
require_once __DIR__ . '/../app/Models/Campaign.php';
require_once __DIR__ . '/../app/Services/AuthService.php';

use App\Bootstrap;
use App\Config;
use App\Database;
use App\Models\Campaign;
use App\Services\AuthService;

$config = Config::load(__DIR__ . '/..');
$db = new Database($config);
$pdo = $db->connection();
Bootstrap::ensureSchema($pdo);

$uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/';
$method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');

if ($uri === '/health') {
    header('Content-Type: application/json');
    echo json_encode([
        'status' => 'ok',
        'app' => $config['app_name'],
        'env' => $config['app_env'],
        'bluesky_enabled' => $config['bluesky_enabled'],
        'timestamp' => gmdate('c'),
    ]);
    exit;
}

if ($method === 'POST' && $uri === '/api/auth/login') {
    $payload = json_decode(file_get_contents('php://input'), true) ?? $_POST;
    $email = trim((string) ($payload['email'] ?? ''));
    $password = (string) ($payload['password'] ?? '');

    try {
        $auth = new AuthService($pdo);
        $user = $auth->loginOrRegister($email, $password);

        header('Content-Type: application/json');
        echo json_encode(['status' => 'success', 'user' => $user]);
        exit;
    } catch (\InvalidArgumentException $e) {
        http_response_code(422);
        echo json_encode(['error' => $e->getMessage()]);
        exit;
    }
}

if ($method === 'POST' && $uri === '/api/campaigns') {
    $payload = json_decode(file_get_contents('php://input'), true) ?? $_POST;
    $name = trim((string) ($payload['name'] ?? ''));
    $topic = trim((string) ($payload['topic'] ?? ''));

    try {
        $campaign = Campaign::create($pdo, 1, $name, $topic);

        header('Content-Type: application/json');
        echo json_encode(['status' => 'created', 'campaign' => $campaign]);
        exit;
    } catch (\Throwable $e) {
        http_response_code(422);
        echo json_encode(['error' => $e->getMessage()]);
        exit;
    }
}

if ($method === 'GET' && $uri === '/api/campaigns') {
    header('Content-Type: application/json');
    echo json_encode(['campaigns' => Campaign::listRecent($pdo, 20)]);
    exit;
}

$html = <<<'HTML'
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Bluesky Production Engine</title>
  <style>
    body {
      margin: 0;
      font-family: Arial, sans-serif;
      background: #020817;
      color: #e2e8f0;
      padding: 32px 18px;
    }
    .container { max-width: 1000px; margin: 0 auto; }
    .card {
      background: #111827;
      border: 1px solid #334155;
      border-radius: 16px;
      padding: 24px;
      margin-bottom: 20px;
    }
    .badge {
      display: inline-block;
      padding: 5px 12px;
      border-radius: 999px;
      background: #16a34a;
      color: white;
      font-size: 12px;
      font-weight: bold;
      letter-spacing: 0.05em;
      text-transform: uppercase;
    }
    .grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 20px; }
    label { display: block; margin-bottom: 8px; color: #cbd5e1; }
    input, button {
      width: 100%;
      padding: 12px 14px;
      border-radius: 10px;
      border: 1px solid #475569;
      background: #0f172a;
      color: #f8fafc;
      margin-bottom: 12px;
    }
    button {
      background: #2563eb;
      border: none;
      cursor: pointer;
      font-weight: 700;
    }
    ul { color: #cbd5e1; line-height: 1.8; }
  </style>
</head>
<body>
  <div class="container">
    <div class="card">
      <span class="badge">Production foundation</span>
      <h1>Bluesky Production Engine</h1>
      <p>Secure configuration + database-backed campaign foundation for the mock system.</p>
    </div>

    <div class="grid">
      <div class="card">
        <h2>Login API</h2>
        <form action="/api/auth/login" method="post">
          <label>Email</label>
          <input type="email" name="email" placeholder="user@example.com" required />
          <label>Password</label>
          <input type="password" name="password" placeholder="Password" required />
          <button type="submit">Login / Create user</button>
        </form>
      </div>

      <div class="card">
        <h2>Create Campaign</h2>
        <form action="/api/campaigns" method="post">
          <label>Campaign name</label>
          <input type="text" name="name" placeholder="Summer launch" required />
          <label>Topic</label>
          <input type="text" name="topic" placeholder="AI tools" />
          <button type="submit">Create campaign</button>
        </form>
      </div>
    </div>

    <div class="card">
      <h2>Production checklist</h2>
      <ul>
        <li>Environment-based configuration is enabled</li>
        <li>SQLite-ready schema has been created</li>
        <li>Campaigns are now stored in a database</li>
        <li>Auth layer supports login and auto-registration</li>
        <li>Bluesky API service is scaffolded for real integration</li>
      </ul>
    </div>
  </div>
</body>
</html>
HTML;

echo $html;
