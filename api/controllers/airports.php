<?php
declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../middleware/auth.php';
require_once __DIR__ . '/../helpers/ResponseHelper.php';

try {
  $pdo = db_pdo();
} catch (Throwable $e) {
  ResponseHelper::error('Database connection failed', 500);
}


// GET /api/airports
if ($_SERVER['REQUEST_METHOD'] === 'GET' && preg_match('#^/airports/?$#', $uri)) {
  $id = isset($_GET['id']) ? (int)$_GET['id'] : null;
  $city_id = isset($_GET['city_id']) ? (int)$_GET['city_id'] : null;
  $q = isset($_GET['q']) ? trim($_GET['q']) : null;
  
  // Get single airport
  if ($id) {
    $stmt = $pdo->prepare('
      SELECT a.*, c.name as city_name, p.name as province_name
      FROM airports a
      LEFT JOIN cities c ON a.city_id = c.id
      LEFT JOIN provinces p ON c.province_id = p.id
      WHERE a.id = ?
    ');
    $stmt->execute([$id]);
    $airport = $stmt->fetch();
    if (!$airport) {
      ResponseHelper::error('Airport not found', 404);
    }
    ResponseHelper::successSimple(['airport' => $airport]);
  }
  
  // List airports
  $where = [];
  $params = [];
  
  if ($city_id !== null) {
    $where[] = 'a.city_id = ?';
    $params[] = $city_id;
  }
  
  if ($q) {
    $searchTerm = str_replace(['%', '_', '[', ']'], ['[%]', '[_]', '[[]', '[]]'], $q);
    $where[] = "(a.code COLLATE SQL_Latin1_General_CP1_CI_AI LIKE ? OR a.name COLLATE SQL_Latin1_General_CP1_CI_AI LIKE ?)";
    $searchPattern = '%' . $searchTerm . '%';
    $params[] = $searchPattern;
    $params[] = $searchPattern;
  }
  
  $whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';
  
  $sql = "SELECT a.*, c.name as city_name, p.name as province_name
    FROM airports a
    LEFT JOIN cities c ON a.city_id = c.id
    LEFT JOIN provinces p ON c.province_id = p.id
    $whereSql
    ORDER BY a.name ASC";
  
  $stmt = $pdo->prepare($sql);
  $stmt->execute($params);
  $airports = $stmt->fetchAll();
  
  ResponseHelper::successSimple(['results' => $airports]);
}

// POST /api/airports (admin only)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && preg_match('#^/airports/?$#', $uri)) {
  requireRole(['admin']);
  
  $input = json_decode(file_get_contents('php://input'), true);
  
  $code = trim($input['code'] ?? '');
  $name = trim($input['name'] ?? '');
  $city_id = isset($input['city_id']) ? (int)$input['city_id'] : null;
  $is_international = isset($input['is_international']) ? (bool)$input['is_international'] : false;
  
  if (!$code || !$name || !$city_id) {
    ResponseHelper::error('Missing required fields: code, name, city_id', 400);
  }
  
  $checkStmt = $pdo->prepare('SELECT id FROM airports WHERE code = ?');
  $checkStmt->execute([$code]);
  if ($checkStmt->fetch()) {
    ResponseHelper::error('Airport code already exists', 400);
  }
  
  $stmt = $pdo->prepare('INSERT INTO airports (code, name, city_id, is_international) VALUES (?, ?, ?, ?)');
  $stmt->execute([$code, $name, $city_id, $is_international ? 1 : 0]);
  $airportId = (int)$pdo->lastInsertId();
  
  $stmt = $pdo->prepare('
    SELECT a.*, c.name as city_name, p.name as province_name
    FROM airports a
    LEFT JOIN cities c ON a.city_id = c.id
    LEFT JOIN provinces p ON c.province_id = p.id
    WHERE a.id = ?
  ');
  $stmt->execute([$airportId]);
  $airport = $stmt->fetch();
  
  ResponseHelper::successSimple(['airport' => $airport], 201);
}

// PUT /api/airports/:id (admin only)
if ($_SERVER['REQUEST_METHOD'] === 'PUT' && preg_match('#^/airports/(\d+)/?$#', $uri, $matches)) {
  requireRole(['admin']);
  
  $id = (int)$matches[1];
  $input = json_decode(file_get_contents('php://input'), true);
  
  $stmt = $pdo->prepare('SELECT id FROM airports WHERE id = ?');
  $stmt->execute([$id]);
  if (!$stmt->fetch()) {
    ResponseHelper::error('Airport not found', 404);
  }
  
  $updates = [];
  $params = [];
  
  if (isset($input['code'])) {
    $updates[] = 'code = ?';
    $params[] = trim($input['code']);
  }
  if (isset($input['name'])) {
    $updates[] = 'name = ?';
    $params[] = trim($input['name']);
  }
  if (isset($input['city_id'])) {
    $updates[] = 'city_id = ?';
    $params[] = (int)$input['city_id'];
  }
  if (isset($input['is_international'])) {
    $updates[] = 'is_international = ?';
    $params[] = $input['is_international'] ? 1 : 0;
  }
  
  if (empty($updates)) {
    ResponseHelper::error('No fields to update', 400);
  }
  
  $params[] = $id;
  $sql = 'UPDATE airports SET ' . implode(', ', $updates) . ' WHERE id = ?';
  $stmt = $pdo->prepare($sql);
  $stmt->execute($params);
  
  $stmt = $pdo->prepare('
    SELECT a.*, c.name as city_name, p.name as province_name
    FROM airports a
    LEFT JOIN cities c ON a.city_id = c.id
    LEFT JOIN provinces p ON c.province_id = p.id
    WHERE a.id = ?
  ');
  $stmt->execute([$id]);
  $airport = $stmt->fetch();
  
  ResponseHelper::successSimple(['airport' => $airport]);
}

if ($_SERVER['REQUEST_METHOD'] === 'DELETE' && preg_match('#^/airports/(\d+)/?$#', $uri, $matches)) {
  requireRole(['admin']);
  
  $id = (int)$matches[1];
  
  $stmt = $pdo->prepare('SELECT id FROM airports WHERE id = ?');
  $stmt->execute([$id]);
  if (!$stmt->fetch()) {
    ResponseHelper::error('Airport not found', 404);
  }
  
  $checkStmt = $pdo->prepare('SELECT COUNT(*) as count FROM flight_routes WHERE origin_airport_id = ? OR destination_airport_id = ?');
  $checkStmt->execute([$id, $id]);
  $result = $checkStmt->fetch();
  if ($result['count'] > 0) {
    ResponseHelper::error('Cannot delete airport: it is used in flight routes', 400);
  }
  
  $stmt = $pdo->prepare('DELETE FROM airports WHERE id = ?');
  $stmt->execute([$id]);
  
  ResponseHelper::successSimple(['message' => 'Airport deleted successfully']);
}

ResponseHelper::error('Not found', 404);










