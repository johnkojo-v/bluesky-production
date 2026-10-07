<?php

declare(strict_types=1);

require_once __DIR__ . '/../app/Config.php';
require_once __DIR__ . '/../app/Database.php';
require_once __DIR__ . '/../app/Bootstrap.php';
require_once __DIR__ . '/../app/Bootstrap/SchemaBoot.php';
require_once __DIR__ . '/../app/Models/User.php';
require_once __DIR__ . '/../app/Models/Campaign.php';
require_once __DIR__ . '/../app/Models/Job.php';
require_once __DIR__ . '/../app/Models/Role.php';
require_once __DIR__ . '/../app/Models/Token.php';
require_once __DIR__ . '/../app/Services/AuthService.php';
require_once __DIR__ . '/../app/Services/CampaignService.php';
require_once __DIR__ . '/../app/Services/JobService.php';
require_once __DIR__ . '/../app/Services/SessionService.php';
require_once __DIR__ . '/../app/Services/WorkerService.php';
require_once __DIR__ . '/../app/Services/BlueskyTokenService.php';
require_once __DIR__ . '/../app/Services/RoleService.php';
require_once __DIR__ . '/../app/Services/EnvironmentValidator.php';
require_once __DIR__ . '/../app/Controllers/AuthController.php';
require_once __DIR__ . '/../app/Controllers/CampaignController.php';
require_once __DIR__ . '/../app/Controllers/JobController.php';
require_once __DIR__ . '/../app/Http/Router.php';

use App\Bootstrap;
use App\Bootstrap\SchemaBoot;
use App\Config;
use App\Database;
use App\Http\Router;
use App\Services\RoleService;
use App\Services\SessionService;
use App\Services\WorkerService;

$config = Config::load(__DIR__ . '/..');
$db = new Database($config);
$pdo = $db->connection();
Bootstrap::ensureSchema($pdo);
SchemaBoot::ensureRoleSchema($pdo);
$roleService = new RoleService($pdo);
$roleService->ensure();
$session = new SessionService();
$session->start();

$worker = new WorkerService($pdo);
$worker->runBatch(5);

$uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/';
$method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');

try {
    if ($uri === '/' || $uri === '/index.php') {
        $campaigns = $pdo->query('SELECT * FROM campaigns ORDER BY id DESC LIMIT 10')->fetchAll();
        $jobs = $pdo->query('SELECT * FROM jobs ORDER BY id DESC LIMIT 10')->fetchAll();
        $user = $session->user();

        $campaignRows = '';
        foreach ($campaigns as $c) {
            $campaignRows .= sprintf(
                '<tr><td>%s</td><td>%s</td><td>%s</td></tr>',
                htmlspecialchars((string) ($c['id'] ?? '')),
                htmlspecialchars((string) ($c['name'] ?? '')),
                htmlspecialchars((string) ($c['status'] ?? 'draft'))
            );
        }

        if ($campaignRows === '') {
            $campaignRows = '<tr><td colspan="3">No campaigns yet.</td></tr>';
        }

        $jobRows = '';
        foreach ($jobs as $j) {
            $jobRows .= sprintf(
                '<tr><td>%s</td><td>%s</td><td>%s</td></tr>',
                htmlspecialchars((string) ($j['id'] ?? '')),
                htmlspecialchars((string) ($j['action'] ?? '')),
                htmlspecialchars((string) ($j['status'] ?? 'queued'))
            );
        }

        if ($jobRows === '') {
            $jobRows = '<tr><td colspan="3">No jobs yet.</td></tr>';
        }

        $sessionStatus = $user ? 'Authenticated: ' . htmlspecialchars((string) $user['email']) : 'Not authenticated';

        echo <<<HTML
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
    input, button, select { width: 100%; padding: 12px 14px; border-radius: 10px; border: 1px solid #475569; background: #0f172a; color: #f8fafc; margin-bottom: 12px; }
    button { background: #2563eb; border: none; cursor: pointer; font-weight: 700; }
    label { display: block; margin-bottom: 8px; color: #cbd5e1; }
    table { width: 100%; border-collapse: collapse; margin-top: 10px; }
    th, td { padding: 10px 12px; border-bottom: 1px solid #334155; text-align: left; }
    th { color: #cbd5e1; }
    .status { color: #a7f3d0; font-weight: bold; }
  </style>
</head>
<body>
  <div class="wrap">
    <div class="card">
      <span class="badge">Production foundation</span>
      <h1>Bluesky Production Engine</h1>
      <p class="status">{$sessionStatus}</p>
      <p>Role system enabled with admin/editor/viewer permissions.</p>
    </div>

    <div class="grid">
      <div class="card">
        <h2>Login / register</h2>
        <form action="/api/auth/login" method="post">
          <label>Email</label>
          <input type="email" name="email" placeholder="user@example.com" required />
          <label>Password</label>
          <input type="password" name="password" placeholder="Password" required />
          <button type="submit">Login / Create account</button>
        </form>
      </div>

      <div class="card">
        <h2>Create campaign</h2>
        <form action="/api/campaigns" method="post">
          <label>Campaign name</label>
          <input type="text" name="name" placeholder="Spring launch" required />
          <label>Topic</label>
          <input type="text" name="topic" placeholder="AI growth" />
          <button type="submit">Create campaign</button>
        </form>
      </div>

      <div class="card">
        <h2>Queue job</h2>
        <form action="/api/jobs" method="post">
          <label>Campaign ID</label>
          <input type="number" name="campaign_id" placeholder="1" min="1" required />
          <label>Action</label>
          <select name="action">
            <option value="publish_post">Publish Post</option>
            <option value="like_post">Like Post</option>
            <option value="follow_account">Follow Account</option>
            <option value="repost_content">Repost Content</option>
          </select>
          <button type="submit">Queue job</button>
        </form>
      </div>
    </div>

    <div class="card">
      <h2>Campaigns</h2>
      <table>
        <thead>
          <tr>
            <th>ID</th>
            <th>Name</th>
            <th>Status</th>
          </tr>
        </thead>
        <tbody>
          {$campaignRows}
        </tbody>
      </table>
    </div>

    <div class="card">
      <h2>Jobs</h2>
      <table>
        <thead>
          <tr>
            <th>ID</th>
            <th>Action</th>
            <th>Status</th>
          </tr>
        </thead>
        <tbody>
          {$jobRows}
        </tbody>
      </table>
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
