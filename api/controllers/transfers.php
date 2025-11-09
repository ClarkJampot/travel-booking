<?php
// Transfers controller
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

// GET /api/transfers
if ($_SERVER['REQUEST_METHOD'] === 'GET' && preg_match('#^/transfers/?$#', $uri)) {
  $id = isset($_GET['id']) ? (int)$_GET['id'] : null;
  
  // Get single transfer
  if ($id) {
    $stmt = $pdo->prepare('SELECT * FROM transfers WHERE id = ?');
    $stmt->execute([$id]);
    $transfer = $stmt->fetch();
    if (!$transfer) {
      json_error('Transfer not found', 404);
    }
    json_ok(['transfer' => $transfer]);
  }
  
  // List transfers
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
    $where[] = 'date >= ?';
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
  $sql = "SELECT * FROM transfers $whereSql ORDER BY date ASC, price ASC, id ASC OFFSET $offsetInt ROWS FETCH NEXT $limitInt ROWS ONLY";
  
  $stmt = $pdo->prepare($sql);
  $stmt->execute($params);
  $transfers = $stmt->fetchAll();
  
  json_ok(['page' => $page, 'limit' => $limit, 'results' => $transfers]);
}

// POST /api/transfers (agency/admin only)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && preg_match('#^/transfers/?$#', $uri)) {
  requireRole(['agency', 'admin']);
  
  $user = get_authenticated_user();
  $input = json_decode(file_get_contents('php://input'), true);
  
  $service = trim($input['service'] ?? '');
  $origin = trim($input['origin'] ?? '');
  $destination = trim($input['destination'] ?? '');
  $date = $input['date'] ?? '';
  $price = isset($input['price']) ? (float)$input['price'] : null;
  $description = trim($input['description'] ?? '');
  $image_url = trim($input['image_url'] ?? '');
  
  if (!$service || !$origin || !$destination || !$date || $price === null) {
    json_error('Missing required fields: service, origin, destination, date, price', 400);
  }
  
  if ($price < 0) {
    json_error('Price must be positive', 400);
  }
  
  $createdBy = $user['role'] === 'admin' && isset($input['created_by']) ? (int)$input['created_by'] : $user['id'];
  
  $stmt = $pdo->prepare('INSERT INTO transfers (service, origin, destination, date, price, description, image_url, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
  $stmt->execute([$service, $origin, $destination, $date, $price, $description, $image_url, $createdBy]);
  $transferId = (int)$pdo->lastInsertId();
  
  $stmt = $pdo->prepare('SELECT * FROM transfers WHERE id = ?');
  $stmt->execute([$transferId]);
  $transfer = $stmt->fetch();
  
  json_ok(['transfer' => $transfer], 201);
}

// PUT /api/transfers/:id (agency/admin only)
if ($_SERVER['REQUEST_METHOD'] === 'PUT' && preg_match('#^/transfers/(\d+)/?$#', $uri, $matches)) {
  requireRole(['agency', 'admin']);
  
  $id = (int)$matches[1];
  $user = get_authenticated_user();
  $input = json_decode(file_get_contents('php://input'), true);
  
  // Check if transfer exists
  $stmt = $pdo->prepare('SELECT created_by FROM transfers WHERE id = ?');
  $stmt->execute([$id]);
  $transfer = $stmt->fetch();
  if (!$transfer) {
    json_error('Transfer not found', 404);
  }
  
  // Check permission
  if ($user['role'] !== 'admin' && $transfer['created_by'] != $user['id']) {
    json_error('Forbidden', 403);
  }
  
  $updates = [];
  $params = [];
  
  if (isset($input['service'])) {
    $updates[] = 'service = ?';
    $params[] = trim($input['service']);
  }
  if (isset($input['origin'])) {
    $updates[] = 'origin = ?';
    $params[] = trim($input['origin']);
  }
  if (isset($input['destination'])) {
    $updates[] = 'destination = ?';
    $params[] = trim($input['destination']);
  }
  if (isset($input['date'])) {
    $updates[] = 'date = ?';
    $params[] = $input['date'];
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
  $sql = 'UPDATE transfers SET ' . implode(', ', $updates) . ' WHERE id = ?';
  $stmt = $pdo->prepare($sql);
  $stmt->execute($params);
  
  $stmt = $pdo->prepare('SELECT * FROM transfers WHERE id = ?');
  $stmt->execute([$id]);
  $transfer = $stmt->fetch();
  
  json_ok(['transfer' => $transfer]);
}

// DELETE /api/transfers/:id (agency/admin only)
if ($_SERVER['REQUEST_METHOD'] === 'DELETE' && preg_match('#^/transfers/(\d+)/?$#', $uri, $matches)) {
  requireRole(['agency', 'admin']);
  
  $id = (int)$matches[1];
  $user = get_authenticated_user();
  
  // Check if transfer exists
  $stmt = $pdo->prepare('SELECT created_by FROM transfers WHERE id = ?');
  $stmt->execute([$id]);
  $transfer = $stmt->fetch();
  if (!$transfer) {
    json_error('Transfer not found', 404);
  }
  
  // Check permission
  if ($user['role'] !== 'admin' && $transfer['created_by'] != $user['id']) {
    json_error('Forbidden', 403);
  }
  
  $stmt = $pdo->prepare('DELETE FROM transfers WHERE id = ?');
  $stmt->execute([$id]);
  
  json_ok(['message' => 'Transfer deleted successfully']);
}

json_error('Not found', 404);
