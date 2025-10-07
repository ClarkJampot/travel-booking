<?php
declare(strict_types=1);
require_once __DIR__ . '/../bootstrap.php';

// Load DB
if (!file_exists(__DIR__ . '/../db.php')) {
  json_error('Missing api/db.php', 500);
  exit;
}
require_once __DIR__ . '/../db.php';

try {
  $pdo = db_pdo();
} catch (Throwable $e) {
  json_error('DB connection failed: ' . $e->getMessage(), 500);
  exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
  $placement = $_GET['placement'] ?? null;
  $page = max(1, (int)($_GET['page'] ?? 1));
  $limit = min(50, max(1, (int)($_GET['limit'] ?? 10)));
  $offset = ($page - 1) * $limit;

  $where = [];
  $params = [];
  if ($placement) { $where[] = 'placement = :placement'; $params[':placement'] = $placement; }
  $whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

  $sql = "SELECT * FROM ads $whereSql ORDER BY id ASC OFFSET $offset ROWS FETCH NEXT $limit ROWS ONLY";
  $stmt = $pdo->prepare($sql);
  foreach ($params as $k => $v) { $stmt->bindValue($k, $v); }
  $stmt->execute();
  $rows = $stmt->fetchAll();
  json_ok(['page' => $page, 'limit' => $limit, 'results' => $rows]);
  exit;
}

json_error('Method not allowed', 405);
