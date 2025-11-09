<?php
// Flights controller
declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../middleware/auth.php';

try {
  $pdo = db_pdo();
} catch (Throwable $e) {
  json_error('Database connection failed', 500);
}

$uri = $GLOBALS['API_URI'] ?? $_SERVER['REQUEST_URI'];

// GET /api/flights
if ($_SERVER['REQUEST_METHOD'] === 'GET' && preg_match('#^/flights/?$#', $uri)) {
  $id = isset($_GET['id']) ? (int)$_GET['id'] : null;
  
  // Get single flight
  if ($id) {
    $stmt = $pdo->prepare('SELECT * FROM flights WHERE id = ?');
    $stmt->execute([$id]);
    $flight = $stmt->fetch();
    if (!$flight) {
      json_error('Flight not found', 404);
    }
    json_ok(['flight' => $flight]);
  }
  
  // List flights
  $origin = $_GET['origin'] ?? null;
  $destination = $_GET['destination'] ?? null;
  $date = $_GET['date'] ?? null;
  $createdBy = isset($_GET['createdBy']) ? (int)$_GET['createdBy'] : null;
  $page = max(1, (int)($_GET['page'] ?? 1));
  $limit = min(50, max(1, (int)($_GET['limit'] ?? 10)));
  $offset = ($page - 1) * $limit;
  
  $where = [];
  $params = [];
  
  if ($origin) {
    $where[] = 'origin = ?';
    $params[] = $origin;
  }
  if ($destination) {
    $where[] = 'destination = ?';
    $params[] = $destination;
  }
  if ($date) {
    $where[] = 'depart_date >= ?';
    $params[] = $date;
  }
  if ($createdBy !== null) {
    $where[] = 'created_by = ?';
    $params[] = $createdBy;
  }
  
  $whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';
  // SQL Server OFFSET/FETCH syntax - must use integers directly, not parameters
  $offsetInt = (int)$offset;
  $limitInt = (int)$limit;
  $sql = "SELECT * FROM flights $whereSql ORDER BY depart_date ASC, price ASC, id ASC OFFSET $offsetInt ROWS FETCH NEXT $limitInt ROWS ONLY";
  
  $stmt = $pdo->prepare($sql);
  $stmt->execute($params);
  $flights = $stmt->fetchAll();
  
  json_ok(['page' => $page, 'limit' => $limit, 'results' => $flights]);
}

// POST /api/flights (agency/admin only)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && preg_match('#^/flights/?$#', $uri)) {
  requireRole(['agency', 'admin']);
  
  $user = get_authenticated_user();
  $input = json_decode(file_get_contents('php://input'), true);
  
  $airline = trim($input['airline'] ?? '');
  $origin = trim($input['origin'] ?? '');
  $destination = trim($input['destination'] ?? '');
  $depart_date = $input['depart_date'] ?? '';
  $price = isset($input['price']) ? (float)$input['price'] : null;
  $description = trim($input['description'] ?? '');
  $image_url = trim($input['image_url'] ?? '');
  
  if (!$airline || !$origin || !$destination || !$depart_date || $price === null) {
    json_error('Missing required fields: airline, origin, destination, depart_date, price', 400);
  }
  
  if ($price < 0) {
    json_error('Price must be positive', 400);
  }
  
  $createdBy = $user['role'] === 'admin' && isset($input['created_by']) ? (int)$input['created_by'] : $user['id'];
  
  $stmt = $pdo->prepare('INSERT INTO flights (airline, origin, destination, depart_date, price, description, image_url, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
  $stmt->execute([$airline, $origin, $destination, $depart_date, $price, $description, $image_url, $createdBy]);
  $flightId = (int)$pdo->lastInsertId();
  
  $stmt = $pdo->prepare('SELECT * FROM flights WHERE id = ?');
  $stmt->execute([$flightId]);
  $flight = $stmt->fetch();
  
  json_ok(['flight' => $flight], 201);
}

// PUT /api/flights/:id (agency/admin only)
if ($_SERVER['REQUEST_METHOD'] === 'PUT' && preg_match('#^/flights/(\d+)/?$#', $uri, $matches)) {
  requireRole(['agency', 'admin']);
  
  $id = (int)$matches[1];
  $user = get_authenticated_user();
  $input = json_decode(file_get_contents('php://input'), true);
  
  // Check if flight exists
  $stmt = $pdo->prepare('SELECT created_by FROM flights WHERE id = ?');
  $stmt->execute([$id]);
  $flight = $stmt->fetch();
  if (!$flight) {
    json_error('Flight not found', 404);
  }
  
  // Check permission
  if ($user['role'] !== 'admin' && $flight['created_by'] != $user['id']) {
    json_error('Forbidden', 403);
  }
  
  $updates = [];
  $params = [];
  
  if (isset($input['airline'])) {
    $updates[] = 'airline = ?';
    $params[] = trim($input['airline']);
  }
  if (isset($input['origin'])) {
    $updates[] = 'origin = ?';
    $params[] = trim($input['origin']);
  }
  if (isset($input['destination'])) {
    $updates[] = 'destination = ?';
    $params[] = trim($input['destination']);
  }
  if (isset($input['depart_date'])) {
    $updates[] = 'depart_date = ?';
    $params[] = $input['depart_date'];
  }
  if (isset($input['price'])) {
    $updates[] = 'price = ?';
    $params[] = (float)$input['price'];
  }
  if (isset($input['description'])) {
    $updates[] = 'description = ?';
    $params[] = trim($input['description']);
  }
  if (isset($input['image_url'])) {
    $updates[] = 'image_url = ?';
    $params[] = trim($input['image_url']);
  }
  
  if (empty($updates)) {
    json_error('No fields to update', 400);
  }
  
  $params[] = $id;
  $sql = 'UPDATE flights SET ' . implode(', ', $updates) . ' WHERE id = ?';
  $stmt = $pdo->prepare($sql);
  $stmt->execute($params);
  
  $stmt = $pdo->prepare('SELECT * FROM flights WHERE id = ?');
  $stmt->execute([$id]);
  $flight = $stmt->fetch();
  
  json_ok(['flight' => $flight]);
}

// DELETE /api/flights/:id (agency/admin only)
if ($_SERVER['REQUEST_METHOD'] === 'DELETE' && preg_match('#^/flights/(\d+)/?$#', $uri, $matches)) {
  requireRole(['agency', 'admin']);
  
  $id = (int)$matches[1];
  $user = get_authenticated_user();
  
  // Check if flight exists
  $stmt = $pdo->prepare('SELECT created_by FROM flights WHERE id = ?');
  $stmt->execute([$id]);
  $flight = $stmt->fetch();
  if (!$flight) {
    json_error('Flight not found', 404);
  }
  
  // Check permission
  if ($user['role'] !== 'admin' && $flight['created_by'] != $user['id']) {
    json_error('Forbidden', 403);
  }
  
  $stmt = $pdo->prepare('DELETE FROM flights WHERE id = ?');
  $stmt->execute([$id]);
  
  json_ok(['message' => 'Flight deleted successfully']);
}

json_error('Not found', 404);
