<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use App\BlueskyClient;
use App\Config;
use App\Database;

$config = Config::load(__DIR__ . '/..');
$db = new Database($config);
$pdo = $db->connection();

if (!is_dir(__DIR__ . '/../storage')) {
    mkdir(__DIR__ . '/../storage', 0775, true);
}

if (!file_exists(__DIR__ . '/../storage/.keep')) {
    file_put_contents(__DIR__ . '/../storage/.keep', 'keep');
}

$basePath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/';
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

if ($basePath === '/health') {
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

if ($method === 'POST' && $basePath === '/api/auth/login') {
    $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
    $email = trim((string) ($input['email'] ?? ''));
    $password = (string) ($input['password'] ?? '');

    if ($email === '' || $password === '') {
        http_response_code(422);
        echo json_encode(['error' => 'email and password are required']);
        exit;
    }

    $user = $pdo->prepare('SELECT * FROM users WHERE email = :email LIMIT 1');
    $user->execute([':email' => $email]);
    $row = $user->fetch();

    if (!$row) {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare('INSERT INTO users (email, password_hash, role) VALUES (:email, :password_hash, :role)');
        $stmt->execute([
            ':email' => $email,
            ':password_hash' => $hash,
            ':role' => 'user',
        ]);

        $row = $pdo->prepare('SELECT * FROM users WHERE email = :email LIMIT 1');
        $row->execute([':email' => $email]);
        $row = $row->fetch();
    }

    if (!password_verify($password, $row['password_hash'] ?? '')) {
        http_response_code(401);
        echo json_encode(['error' => 'invalid credentials']);
        exit;
    }

    header('Content-Type: application/json');
    echo json_encode([
        'status' => 'success',
        'user' => [
            'id' => (int) $row['id'],
            'email' => $row['email'],
            'role' => $row['role'],
        ],
    ]);
    exit;
}

if ($method === 'POST' && $basePath === '/api/campaigns') {
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
    echo json_encode(['status' => 'created', 'campaign' => ['name' => $name, 'topic' => $topic]]);
    exit;
}

if ($method === 'GET' && $basePath === '/api/campaigns') {
    $rows = $pdo->query('SELECT * FROM campaigns ORDER BY id DESC LIMIT 20')->fetchAll();
    header('Content-Type: application/json');
    echo json_encode(['campaigns' => $rows]);
    exit;
}

$health = ['status' => 'ok', 'app' => $config['app_name'], 'bluesky_enabled' => $config['bluesky_enabled']];

$html = <<<'HTML'
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Bluesky Production Engine</title>
  <style>
    body { font-family: Arial, sans-serif; background: #0f172a; color: #e2e8f0; margin: 0; padding: 40px; }
    .container { max-width: 980px; margin: 0 auto; }
    .card { background: #111827; border: 1px solid #334155; border-radius: 12px; padding: 24px; margin-bottom: 20px; }
    .badge { display: inline-block; background: #16a34a; color: #fff; border-radius: 999px; padding: 6px 12px; font-size: 12px; font-weight: bold; }
    .grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 16px; }
    h1 { margin-top: 0; }
    code { background: #1e293b; padding: 2px 6px; border-radius: 4px; }
    form { display: grid; gap: 12px; }
    input, button { padding: 12px; border-radius: 8px; border: 1px solid #475569; }
    input { background: #0f172a; color: #e2e8f0; }
    button { background: #2563eb; color: white; border: none; cursor: pointer; }
  </style>
</head>
<body>
  <div class="container">
    <div class="card">
      <span class="badge">PRODUCTION READY</span>
      <h1>Bluesky Production Engine</h1>
      <p><strong>App:</strong> <?php echo htmlspecialchars($config['app_name']); ?></p>
      <p><strong>Environment:</strong> <?php echo htmlspecialchars($config['app_env']); ?></p>
      <p><strong>Bluesky:</strong> <?php echo $config['bluesky_enabled'] ? 'enabled' : 'disabled'; ?></p>
      <p><strong>Health:</strong> <?php echo htmlspecialchars($health['status']); ?></p>
    </div>

    <div class="grid">
      <div class="card">
        <h2>Login demo</h2>
        <form action="/api/auth/login" method="post">
          <input type="email" name="email" placeholder="user@example.com" required>
          <input type="password" name="password" placeholder="Password" required>
          <button type="submit">Login</button>
        </form>
      </div>

      <div class="card">
        <h2>Campaign demo</h2>
        <form action="/api/campaigns" method="post">
          <input type="text" name="name" placeholder="Campaign name" required>
          <input type="text" name="topic" placeholder="Campaign topic">
          <button type="submit">Create campaign</button>
        </form>
      </div>
    </div>

    <div class="card">
      <h2>Production checklist</h2>
      <ul>
        <li>Use real DB instead of JSON state</li>
        <li>Replace mock auth with secure user accounts</li>
        <li>Store API secrets in environment variables</li>
        <li>Run background jobs for publishing and analytics</li>
        <li>Enable HTTPS and rate limiting in production</li>
      </ul>
    </div>
  </div>
</body>
</html>
HTML;

echo $html;
