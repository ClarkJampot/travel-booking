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
  // Check if requesting single item by ID
  $id = isset($_GET['id']) ? (int)$_GET['id'] : null;
  if ($id) {
    $stmt = $pdo->prepare('SELECT * FROM activities WHERE id = ?');
    $stmt->execute([$id]);
    $activity = $stmt->fetch();
    if (!$activity) {
      json_error('Activity not found', 404);
      exit;
    }
    json_ok(['results' => [$activity]]);
    exit;
  }

  // Otherwise, handle list query
  $city = $_GET['city'] ?? null;
  $date = $_GET['date'] ?? null;
  $createdBy = isset($_GET['createdBy']) ? (int)$_GET['createdBy'] : null;
  $page = max(1, (int)($_GET['page'] ?? 1));
  $limit = min(50, max(1, (int)($_GET['limit'] ?? 10)));
  $offset = ($page - 1) * $limit;

  $where = [];
  $params = [];
  if ($city) { $where[] = 'city = :city'; $params[':city'] = $city; }
  if ($date) { $where[] = 'date >= :date'; $params[':date'] = $date; }
  if ($createdBy !== null) { $where[] = 'created_by = :createdBy'; $params[':createdBy'] = $createdBy; }
  $whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

  $sql = "SELECT * FROM activities $whereSql ORDER BY date ASC, price ASC, id ASC OFFSET $offset ROWS FETCH NEXT $limit ROWS ONLY";
  $stmt = $pdo->prepare($sql);
  foreach ($params as $k => $v) { $stmt->bindValue($k, $v); }
  $stmt->execute();
  $rows = $stmt->fetchAll();
  json_ok(['page' => $page, 'limit' => $limit, 'results' => $rows]);
  exit;
}

json_error('Method not allowed', 405);
