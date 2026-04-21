<?php
$db = getenv('POSTGRES_DB') ?: 'labdb';
$user = getenv('POSTGRES_USER') ?: 'labuser';
$pass = getenv('POSTGRES_PASSWORD') ?: 'change_this_for_local_lab_use';
$dsn = "pgsql:host=postgres;port=5432;dbname={$db}";
$rows = [];
$status = 'Not connected';
$error = null;

try {
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    $status = 'Connected successfully to PostgreSQL through the application path.';
    $stmt = $pdo->query('SELECT id, title, detail FROM notes ORDER BY id');
    $rows = $stmt->fetchAll();
} catch (Throwable $e) {
    $error = $e->getMessage();
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Lab 2 App to DB Demo</title>
  <style>
    body { font-family: Arial, sans-serif; margin: 2rem; line-height: 1.5; }
    h1, h2 { margin-top: 1.5rem; }
    code { background: #f3f3f3; padding: 0.15rem 0.3rem; }
    table { border-collapse: collapse; width: 100%; margin-top: 1rem; }
    th, td { border: 1px solid #cccccc; padding: 0.5rem; text-align: left; }
    .info { background: #f8f8f8; border-left: 4px solid #555555; padding: 1rem; }
    .ok { color: #0a6c2f; font-weight: bold; }
    .warn { color: #8a1f11; font-weight: bold; }
  </style>
</head>
<body>
  <h1>Nginx Application Server Behind Traefik</h1>

  <div class="info">
    <p><strong>Important:</strong> Traefik is the reverse proxy in this lab.</p>
    <p>This Nginx container is only the web server for this application.</p>
    <p>The database is reached through the application path and is not published directly to the Docker host.</p>
  </div>

  <h2>Database connection status</h2>
  <?php if ($error === null): ?>
    <p class="ok"><?php echo htmlspecialchars($status, ENT_QUOTES, 'UTF-8'); ?></p>
  <?php else: ?>
    <p class="warn">Database connection failed.</p>
    <pre><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></pre>
  <?php endif; ?>

  <h2>Rows from the notes table</h2>
  <?php if (count($rows) > 0): ?>
    <table>
      <thead>
        <tr><th>ID</th><th>Title</th><th>Detail</th></tr>
      </thead>
      <tbody>
      <?php foreach ($rows as $row): ?>
        <tr>
          <td><?php echo htmlspecialchars((string)$row['id'], ENT_QUOTES, 'UTF-8'); ?></td>
          <td><?php echo htmlspecialchars($row['title'], ENT_QUOTES, 'UTF-8'); ?></td>
          <td><?php echo htmlspecialchars($row['detail'], ENT_QUOTES, 'UTF-8'); ?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  <?php else: ?>
    <p>No rows are present yet. Create the table and insert the sample rows from the tutorial.</p>
  <?php endif; ?>

  <h2>Path summary</h2>
  <ol>
    <li>Browser -> Traefik at <code>https://localhost:8443/app</code></li>
    <li>Traefik -> Nginx application server</li>
    <li>Nginx -> PHP-FPM</li>
    <li>PHP-FPM -> PostgreSQL on <code>backend_net</code></li>
  </ol>
</body>
</html>
