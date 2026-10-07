<?php

declare(strict_types=1);

require_once __DIR__ . '/../app/Config.php';
require_once __DIR__ . '/../app/Database.php';
require_once __DIR__ . '/../app/Bootstrap.php';

use App\Bootstrap;
use App\Config;
use App\Database;

$config = Config::load(__DIR__ . '/..');
$db = new Database($config);
$pdo = $db->connection();
Bootstrap::ensureSchema($pdo);

$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/';
$method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');

if ($path === '/health') {
    header('Content-Type: application/json');
    echo json_encode([
        'status' => 'ok',
        'app' => $config['app_name'],
        'env' => $config['app_env'],
        'db_connection' => $config['db_connection'],
        'bluesky_enabled' => $config['bluesky_enabled'],
        'timestamp' => gmdate('c'),
    ]);
    exit;
}

if ($method === 'POST' && $path === '/api/auth/login') {
    $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
    $email = trim((string) ($input['email'] ?? ''));
    $password = (string) ($input['password'] ?? '');

    if ($email === '' || $password === '') {
        http_response_code(422);
        echo json_encode(['error' => 'email and password are required']);
        exit;
    }

    $stmt = $pdo->prepare('SELECT * FROM users WHERE email = :email LIMIT 1');
    $stmt->execute([':email' => $email]);
    $user = $stmt->fetch();

    if (!$user) {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $insert = $pdo->prepare('INSERT INTO users (email, password_hash, role) VALUES (:email, :password_hash, :role)');
        $insert->execute([
            ':email' => $email,
            ':password_hash' => $hash,
            ':role' => 'user',
        ]);

        $stmt = $pdo->prepare('SELECT * FROM users WHERE email = :email LIMIT 1');
        $stmt->execute([':email' => $email]);
        $user = $stmt->fetch();
    }

    if (!password_verify($password, (string) ($user['password_hash'] ?? ''))) {
        http_response_code(401);
        echo json_encode(['error' => 'invalid credentials']);
        exit;
    }

    header('Content-Type: application/json');
    echo json_encode([
        'status' => 'success',
        'user' => [
            'id' => (int) $user['id'],
            'email' => $user['email'],
            'role' => $user['role'],
        ],
    ]);
    exit;
}

if ($method === 'POST' && $path === '/api/campaigns') {
    $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
    $name = trim((string) ($input['name'] ?? ''));
    $topic = trim((string) ($input['topic'] ?? ''));

    if ($name === '') {
        http_response_code(422);
        echo json_encode(['error' => 'campaign name is required']);
        exit;
    }

    $stmt = $pdo->prepare('INSERT INTO campaigns (user_id, name, topic, status, metrics) VALUES (:user_id, :name, :topic, :status, :metrics)');
    $stmt->execute([
        ':user_id' => 1,
        ':name' => $name,
        ':topic' => $topic,
        ':status' => 'draft',
        ':metrics' => json_encode(['engagement' => 0, 'reach' => 0]),
    ]);

    header('Content-Type: application/json');
    echo json_encode([
        'status' => 'created',
        'campaign' => ['name' => $name, 'topic' => $topic],
    ]);
    exit;
}

if ($method === 'GET' && $path === '/api/campaigns') {
    header('Content-Type: application/json');
    $rows = $pdo->query('SELECT * FROM campaigns ORDER BY id DESC LIMIT 20')->fetchAll();
    echo json_encode(['campaigns' => $rows]);
    exit;
}

$html = <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Bluesky Production Engine</title>
  <style>
    :root {
      --bg: #0f172a;
      --panel: #111827;
      --panel-2: #1f2937;
      --line: #374151;
      --text: #e5e7eb;
      --muted: #94a3b8;
      --primary: #2563eb;
      --success: #16a34a;
    }
    * { box-sizing: border-box; }
    body {
      margin: 0;
      background: linear-gradient(135deg, #020817, #0f172a 60%, #111827);
      color: var(--text);
      font-family: Arial, sans-serif;
      padding: 48px 20px;
    }
    .container {
      max-width: 980px;
      margin: 0 auto;
    }
    .card {
      background: rgba(17, 24, 39, 0.9);
      border: 1px solid var(--line);
      border-radius: 16px;
      padding: 24px;
      margin-bottom: 24px;
      box-shadow: 0 10px 30px rgba(0,0,0,0.2);
    }
    .badge {
      display: inline-block;
      background: var(--success);
      color: white;
      border-radius: 999px;
      padding: 6px 12px;
      font-weight: 700;
      font-size: 12px;
      letter-spacing: 0.04em;
      text-transform: uppercase;
    }
    h1, h2 { margin-top: 0; }
    .grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
      gap: 20px;
    }
    form {
      display: grid;
      gap: 12px;
    }
    input, button {
      width: 100%;
      padding: 12px 14px;
      border-radius: 10px;
      border: 1px solid var(--line);
      font-size: 14px;
    }
    input {
      background: rgba(15, 23, 42, 0.9);
      color: var(--text);
    }
    button {
      background: var(--primary);
      color: white;
      border: none;
      font-weight: 700;
      cursor: pointer;
    }
    ul {
      margin: 0;
      padding-left: 18px;
      color: var(--muted);
      line-height: 1.8;
    }
    .small {
      color: var(--muted);
    }
  </style>
</head>
<body>
  <div class="container">
    <div class="card">
      <span class="badge">Production-ready foundation</span>
      <h1>Bluesky Production Engine</h1>
      <p class="small">App: <strong>{$config['app_name']}</strong> | Environment: <strong>{$config['app_env']}</strong> | Bluesky: <strong>{$config['bluesky_enabled'] ? 'enabled' : 'disabled'}</strong></p>
    </div>

    <div class="grid">
      <div class="card">
        <h2>Login demo</h2>
        <form action="/api/auth/login" method="post">
          <input type="email" name="email" placeholder="user@example.com" required />
          <input type="password" name="password" placeholder="Password" required />
          <button type="submit">Create or login user</button>
        </form>
      </div>

      <div class="card">
        <h2>Campaign demo</h2>
        <form action="/api/campaigns" method="post">
          <input type="text" name="name" placeholder="Campaign name" required />
          <input type="text" name="topic" placeholder="Campaign topic" />
          <button type="submit">Create campaign</button>
        </form>
      </div>
    </div>

    <div class="card">
      <h2>Production checklist</h2>
      <ul>
        <li>Use a real database instead of JSON state</li>
        <li>Store credentials and tokens in environment variables</li>
        <li>Add real Bluesky authentication and post actions</li>
        <li>Introduce queue workers for scheduled campaigns</li>
        <li>Use HTTPS, rate limiting, and audit logging in deployment</li>
      </ul>
    </div>
  </div>
</body>
</html>
HTML;

echo $html;
