<?php
// Destinations controller
declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../middleware/auth.php';

try {
  $pdo = db_pdo();
} catch (Throwable $e) {
  error_log('Database error: ' . $e->getMessage());
  json_error('Database connection failed', 500);
}

// GET /api/destinations
$uri = $GLOBALS['API_URI'] ?? $_SERVER['REQUEST_URI'];
if ($_SERVER['REQUEST_METHOD'] === 'GET' && preg_match('#^/destinations/?$#', $uri)) {
  $id = isset($_GET['id']) ? (int)$_GET['id'] : null;
  
  // Get single destination
  if ($id) {
    $stmt = $pdo->prepare('SELECT * FROM destinations WHERE id = ?');
    $stmt->execute([$id]);
    $destination = $stmt->fetch();
    if (!$destination) {
      json_error('Destination not found', 404);
    }
    json_ok(['destination' => $destination]);
  }
  
  // List destinations
  $country = $_GET['country'] ?? null;
  $featured = isset($_GET['featured']) ? (bool)$_GET['featured'] : null;
  $page = max(1, (int)($_GET['page'] ?? 1));
  $limit = min(50, max(1, (int)($_GET['limit'] ?? 20)));
  $offset = ($page - 1) * $limit;
  
  $where = [];
  $params = [];
  
  if ($country) {
    $where[] = 'country = ?';
    $params[] = $country;
  }
  if ($featured !== null) {
    $where[] = 'featured = ?';
    $params[] = $featured ? 1 : 0;
  }
  
  $whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';
  
  // SQL Server OFFSET/FETCH syntax - must use integers directly, not parameters
  $offsetInt = (int)$offset;
  $limitInt = (int)$limit;
  $sql = "SELECT * FROM destinations $whereSql ORDER BY featured DESC, name ASC OFFSET $offsetInt ROWS FETCH NEXT $limitInt ROWS ONLY";
  
  try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $destinations = $stmt->fetchAll();
    
    json_ok(['page' => $page, 'limit' => $limit, 'results' => $destinations]);
  } catch (PDOException $e) {
    error_log('SQL Error: ' . $e->getMessage() . ' | SQL: ' . $sql);
    json_error('Database query failed', 500);
  }
}

// POST /api/destinations (admin only)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && preg_match('#^/destinations/?$#', $uri)) {
  requireRole(['admin']);
  
  $input = json_decode(file_get_contents('php://input'), true);
  
  $name = trim($input['name'] ?? '');
  $country = trim($input['country'] ?? '');
  $description = trim($input['description'] ?? '');
  $image_url = trim($input['image_url'] ?? '');
  $featured = isset($input['featured']) ? (bool)$input['featured'] : false;
  
  if (!$name || !$country) {
    json_error('Missing required fields: name, country', 400);
  }
  
  $stmt = $pdo->prepare('INSERT INTO destinations (name, country, description, image_url, featured) VALUES (?, ?, ?, ?, ?)');
  $stmt->execute([$name, $country, $description, $image_url, $featured ? 1 : 0]);
  $destinationId = (int)$pdo->lastInsertId();
  
  $stmt = $pdo->prepare('SELECT * FROM destinations WHERE id = ?');
  $stmt->execute([$destinationId]);
  $destination = $stmt->fetch();
  
  json_ok(['destination' => $destination], 201);
}

// PUT /api/destinations/:id (admin only)
if ($_SERVER['REQUEST_METHOD'] === 'PUT' && preg_match('#^/destinations/(\d+)/?$#', $uri, $matches)) {
  requireRole(['admin']);
  
  $id = (int)$matches[1];
  $input = json_decode(file_get_contents('php://input'), true);
  
  // Check if destination exists
  $stmt = $pdo->prepare('SELECT id FROM destinations WHERE id = ?');
  $stmt->execute([$id]);
  if (!$stmt->fetch()) {
    json_error('Destination not found', 404);
  }
  
  $updates = [];
  $params = [];
  
  if (isset($input['name'])) {
    $updates[] = 'name = ?';
    $params[] = trim($input['name']);
  }
  if (isset($input['country'])) {
    $updates[] = 'country = ?';
    $params[] = trim($input['country']);
  }
  if (isset($input['description'])) {
    $updates[] = 'description = ?';
    $params[] = trim($input['description']);
  }
  if (isset($input['image_url'])) {
    $updates[] = 'image_url = ?';
    $params[] = trim($input['image_url']);
  }
  if (isset($input['featured'])) {
    $updates[] = 'featured = ?';
    $params[] = (bool)$input['featured'] ? 1 : 0;
  }
  
  if (empty($updates)) {
    json_error('No fields to update', 400);
  }
  
  $params[] = $id;
  $sql = 'UPDATE destinations SET ' . implode(', ', $updates) . ' WHERE id = ?';
  $stmt = $pdo->prepare($sql);
  $stmt->execute($params);
  
  $stmt = $pdo->prepare('SELECT * FROM destinations WHERE id = ?');
  $stmt->execute([$id]);
  $destination = $stmt->fetch();
  
  json_ok(['destination' => $destination]);
}

// DELETE /api/destinations/:id (admin only)
if ($_SERVER['REQUEST_METHOD'] === 'DELETE' && preg_match('#^/destinations/(\d+)/?$#', $uri, $matches)) {
  requireRole(['admin']);
  
  $id = (int)$matches[1];
  
  // Check if destination exists
  $stmt = $pdo->prepare('SELECT id FROM destinations WHERE id = ?');
  $stmt->execute([$id]);
  if (!$stmt->fetch()) {
    json_error('Destination not found', 404);
  }
  
  // Check if destination is used
  $stmt = $pdo->prepare('SELECT COUNT(*) as count FROM hotels WHERE destination_id = ?');
  $stmt->execute([$id]);
  $hotelCount = $stmt->fetch()['count'];
  
  $stmt = $pdo->prepare('SELECT COUNT(*) as count FROM activities WHERE destination_id = ?');
  $stmt->execute([$id]);
  $activityCount = $stmt->fetch()['count'];
  
  if ($hotelCount > 0 || $activityCount > 0) {
    json_error('Cannot delete destination that is in use', 400);
  }
  
  $stmt = $pdo->prepare('DELETE FROM destinations WHERE id = ?');
  $stmt->execute([$id]);
  
  json_ok(['message' => 'Destination deleted successfully']);
}

json_error('Not found', 404);

