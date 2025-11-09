<?php
// Activities controller
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

// GET /api/activities
if ($_SERVER['REQUEST_METHOD'] === 'GET' && preg_match('#^/activities/?$#', $uri)) {
  $id = isset($_GET['id']) ? (int)$_GET['id'] : null;
  
  // Get single activity
  if ($id) {
    $stmt = $pdo->prepare('SELECT * FROM activities WHERE id = ?');
    $stmt->execute([$id]);
    $activity = $stmt->fetch();
    if (!$activity) {
      json_error('Activity not found', 404);
    }
    json_ok(['activity' => $activity]);
  }
  
  // List activities
  $city = $_GET['city'] ?? null;
  $destination_id = isset($_GET['destination_id']) ? (int)$_GET['destination_id'] : null;
  $date = $_GET['date'] ?? null;
  $createdBy = isset($_GET['createdBy']) ? (int)$_GET['createdBy'] : null;
  $page = max(1, (int)($_GET['page'] ?? 1));
  $limit = min(50, max(1, (int)($_GET['limit'] ?? 10)));
  $offset = ($page - 1) * $limit;
  
  $where = [];
  $params = [];
  
  if ($city) {
    $where[] = 'city = ?';
    $params[] = $city;
  }
  if ($destination_id !== null) {
    $where[] = 'destination_id = ?';
    $params[] = $destination_id;
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
  $sql = "SELECT * FROM activities $whereSql ORDER BY date ASC, price ASC, id ASC OFFSET $offsetInt ROWS FETCH NEXT $limitInt ROWS ONLY";
  
  $stmt = $pdo->prepare($sql);
  $stmt->execute($params);
  $activities = $stmt->fetchAll();
  
  json_ok(['page' => $page, 'limit' => $limit, 'results' => $activities]);
}

// POST /api/activities (agency/admin only)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && preg_match('#^/activities/?$#', $uri)) {
  requireRole(['agency', 'admin']);
  
  $user = get_authenticated_user();
  $input = json_decode(file_get_contents('php://input'), true);
  
  $title = trim($input['title'] ?? '');
  $destination_id = isset($input['destination_id']) ? (int)$input['destination_id'] : null;
  $city = trim($input['city'] ?? '');
  $date = $input['date'] ?? '';
  $price = isset($input['price']) ? (float)$input['price'] : null;
  $description = trim($input['description'] ?? '');
  $image_url = trim($input['image_url'] ?? '');
  
  if (!$title || !$city || !$date || $price === null) {
    json_error('Missing required fields: title, city, date, price', 400);
  }
  
  if ($price < 0) {
    json_error('Price must be positive', 400);
  }
  
  $createdBy = $user['role'] === 'admin' && isset($input['created_by']) ? (int)$input['created_by'] : $user['id'];
  
  $stmt = $pdo->prepare('INSERT INTO activities (title, destination_id, city, date, price, description, image_url, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
  $stmt->execute([$title, $destination_id, $city, $date, $price, $description, $image_url, $createdBy]);
  $activityId = (int)$pdo->lastInsertId();
  
  $stmt = $pdo->prepare('SELECT * FROM activities WHERE id = ?');
  $stmt->execute([$activityId]);
  $activity = $stmt->fetch();
  
  json_ok(['activity' => $activity], 201);
}

// PUT /api/activities/:id (agency/admin only)
if ($_SERVER['REQUEST_METHOD'] === 'PUT' && preg_match('#^/activities/(\d+)/?$#', $uri, $matches)) {
  requireRole(['agency', 'admin']);
  
  $id = (int)$matches[1];
  $user = get_authenticated_user();
  $input = json_decode(file_get_contents('php://input'), true);
  
  // Check if activity exists
  $stmt = $pdo->prepare('SELECT created_by FROM activities WHERE id = ?');
  $stmt->execute([$id]);
  $activity = $stmt->fetch();
  if (!$activity) {
    json_error('Activity not found', 404);
  }
  
  // Check permission
  if ($user['role'] !== 'admin' && $activity['created_by'] != $user['id']) {
    json_error('Forbidden', 403);
  }
  
  $updates = [];
  $params = [];
  
  if (isset($input['title'])) {
    $updates[] = 'title = ?';
    $params[] = trim($input['title']);
  }
  if (isset($input['destination_id'])) {
    $updates[] = 'destination_id = ?';
    $params[] = (int)$input['destination_id'];
  }
  if (isset($input['city'])) {
    $updates[] = 'city = ?';
    $params[] = trim($input['city']);
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
  $sql = 'UPDATE activities SET ' . implode(', ', $updates) . ' WHERE id = ?';
  $stmt = $pdo->prepare($sql);
  $stmt->execute($params);
  
  $stmt = $pdo->prepare('SELECT * FROM activities WHERE id = ?');
  $stmt->execute([$id]);
  $activity = $stmt->fetch();
  
  json_ok(['activity' => $activity]);
}

// DELETE /api/activities/:id (agency/admin only)
if ($_SERVER['REQUEST_METHOD'] === 'DELETE' && preg_match('#^/activities/(\d+)/?$#', $uri, $matches)) {
  requireRole(['agency', 'admin']);
  
  $id = (int)$matches[1];
  $user = get_authenticated_user();
  
  // Check if activity exists
  $stmt = $pdo->prepare('SELECT created_by FROM activities WHERE id = ?');
  $stmt->execute([$id]);
  $activity = $stmt->fetch();
  if (!$activity) {
    json_error('Activity not found', 404);
  }
  
  // Check permission
  if ($user['role'] !== 'admin' && $activity['created_by'] != $user['id']) {
    json_error('Forbidden', 403);
  }
  
  $stmt = $pdo->prepare('DELETE FROM activities WHERE id = ?');
  $stmt->execute([$id]);
  
  json_ok(['message' => 'Activity deleted successfully']);
}

json_error('Not found', 404);
