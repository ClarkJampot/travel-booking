<?php
declare(strict_types=1);
require_once __DIR__ . '/../bootstrap.php';

// Load DB (expects api/db.php present locally with creds)
if (!file_exists(__DIR__ . '/../db.php')) {
  json_error('Missing api/db.php. Copy api/db.sample.php to api/db.php and fill credentials.', 500);
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
    $stmt = $pdo->prepare('SELECT * FROM hotels WHERE id = ?');
    $stmt->execute([$id]);
    $hotel = $stmt->fetch();
    if (!$hotel) {
      json_error('Hotel not found', 404);
      exit;
    }
    json_ok(['results' => [$hotel]]);
    exit;
  }

  // Otherwise, handle list query
  $city = $_GET['city'] ?? null;
  $country = $_GET['country'] ?? null;
  $minPrice = isset($_GET['minPrice']) ? (float)$_GET['minPrice'] : null;
  $maxPrice = isset($_GET['maxPrice']) ? (float)$_GET['maxPrice'] : null;
  $ratingMin = isset($_GET['ratingMin']) ? (float)$_GET['ratingMin'] : null;
  $createdBy = isset($_GET['createdBy']) ? (int)$_GET['createdBy'] : null;
  $page = max(1, (int)($_GET['page'] ?? 1));
  $limit = min(50, max(1, (int)($_GET['limit'] ?? 10)));
  $offset = ($page - 1) * $limit;

  $where = [];
  $params = [];
  if ($city) { $where[] = 'city = :city'; $params[':city'] = $city; }
  if ($country) { $where[] = 'country = :country'; $params[':country'] = $country; }
  if ($minPrice !== null) { $where[] = 'price_per_night >= :minPrice'; $params[':minPrice'] = $minPrice; }
  if ($maxPrice !== null) { $where[] = 'price_per_night <= :maxPrice'; $params[':maxPrice'] = $maxPrice; }
  if ($ratingMin !== null) { $where[] = 'rating >= :ratingMin'; $params[':ratingMin'] = $ratingMin; }
  if ($createdBy !== null) { $where[] = 'created_by = :createdBy'; $params[':createdBy'] = $createdBy; }
  $whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

  $sql = "SELECT * FROM hotels $whereSql ORDER BY rating DESC, price_per_night ASC, id ASC OFFSET $offset ROWS FETCH NEXT $limit ROWS ONLY";
  $stmt = $pdo->prepare($sql);
  foreach ($params as $k => $v) { $stmt->bindValue($k, $v); }
  $stmt->execute();
  $rows = $stmt->fetchAll();
  json_ok(['page' => $page, 'limit' => $limit, 'results' => $rows]);
  exit;
}

json_error('Method not allowed', 405);


