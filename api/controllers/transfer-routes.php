<?php
// Transfer Routes controller
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

// GET /api/transfers/routes
if ($_SERVER['REQUEST_METHOD'] === 'GET' && preg_match('#^/transfers/routes/?$#', $uri)) {
  $id = isset($_GET['id']) ? (int)$_GET['id'] : null;
  $origin_city_id = isset($_GET['origin_city_id']) ? (int)$_GET['origin_city_id'] : null;
  $destination_city_id = isset($_GET['destination_city_id']) ? (int)$_GET['destination_city_id'] : null;
  $transfer_type_id = isset($_GET['transfer_type_id']) ? (int)$_GET['transfer_type_id'] : null;
  $q = isset($_GET['q']) ? trim($_GET['q']) : null;
  $createdBy = isset($_GET['createdBy']) ? (int)$_GET['createdBy'] : null;
  
  // Get single route
  if ($id) {
    $stmt = $pdo->prepare('
      SELECT tr.*,
        oc.name as origin_city_name, op.name as origin_province_name,
        dc.name as destination_city_name, dp.name as destination_province_name,
        tt.name as transfer_type_name, tt.icon as transfer_type_icon
      FROM transfer_routes tr
      INNER JOIN cities oc ON tr.origin_city_id = oc.id
      INNER JOIN cities dc ON tr.destination_city_id = dc.id
      LEFT JOIN provinces op ON oc.province_id = op.id
      LEFT JOIN provinces dp ON dc.province_id = dp.id
      INNER JOIN transfer_types tt ON tr.transfer_type_id = tt.id
      WHERE tr.id = ?
    ');
    $stmt->execute([$id]);
    $route = $stmt->fetch();
    if (!$route) {
      json_error('Route not found', 404);
    }
    
    // Get images
    $images = get_entity_images($pdo, 'transfer_route', $id);
    $route['images'] = $images;
    $route['image_url'] = $images[0] ?? null;
    
    json_ok(['route' => $route]);
  }
  
  // List routes
  $where = [];
  $params = [];
  
  if ($origin_city_id !== null) {
    $where[] = 'tr.origin_city_id = ?';
    $params[] = $origin_city_id;
  }
  if ($destination_city_id !== null) {
    $where[] = 'tr.destination_city_id = ?';
    $params[] = $destination_city_id;
  }
  if ($transfer_type_id !== null) {
    $where[] = 'tr.transfer_type_id = ?';
    $params[] = $transfer_type_id;
  }
  if ($q) {
    $searchTerm = str_replace(['%', '_', '[', ']'], ['[%]', '[_]', '[[]', '[]]'], $q);
    $where[] = "(tr.origin_specific COLLATE SQL_Latin1_General_CP1_CI_AI LIKE ? OR tr.destination_specific COLLATE SQL_Latin1_General_CP1_CI_AI LIKE ? OR oc.name COLLATE SQL_Latin1_General_CP1_CI_AI LIKE ? OR dc.name COLLATE SQL_Latin1_General_CP1_CI_AI LIKE ? OR tr.description COLLATE SQL_Latin1_General_CP1_CI_AI LIKE ?)";
    $searchPattern = '%' . $searchTerm . '%';
    $params[] = $searchPattern;
    $params[] = $searchPattern;
    $params[] = $searchPattern;
    $params[] = $searchPattern;
    $params[] = $searchPattern;
  }
  if ($createdBy !== null) {
    $where[] = 'tr.created_by = ?';
    $params[] = $createdBy;
  }
  
  $whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';
  
  $sql = "SELECT tr.*,
      oc.name as origin_city_name, dc.name as destination_city_name,
      tt.name as transfer_type_name
    FROM transfer_routes tr
    INNER JOIN cities oc ON tr.origin_city_id = oc.id
    INNER JOIN cities dc ON tr.destination_city_id = dc.id
    INNER JOIN transfer_types tt ON tr.transfer_type_id = tt.id
    $whereSql
    ORDER BY tr.created_at DESC";
  
  $stmt = $pdo->prepare($sql);
  $stmt->execute($params);
  $routes = $stmt->fetchAll();
  
  json_ok(['results' => $routes]);
}

// POST /api/transfers/routes (agency/admin only)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && preg_match('#^/transfers/routes/?$#', $uri)) {
  requireRole(['agency', 'admin']);
  
  $user = get_authenticated_user();
  $input = json_decode(file_get_contents('php://input'), true);
  
  $origin_city_id = isset($input['origin_city_id']) ? (int)$input['origin_city_id'] : null;
  $destination_city_id = isset($input['destination_city_id']) ? (int)$input['destination_city_id'] : null;
  $origin_specific = trim($input['origin_specific'] ?? '');
  $destination_specific = trim($input['destination_specific'] ?? '');
  $transfer_type_id = isset($input['transfer_type_id']) ? (int)$input['transfer_type_id'] : null;
  $base_price = isset($input['base_price']) ? (float)$input['base_price'] : null;
  $duration_minutes = isset($input['duration_minutes']) ? (int)$input['duration_minutes'] : null;
  $distance_km = isset($input['distance_km']) ? (float)$input['distance_km'] : null;
  $capacity = isset($input['capacity']) ? (int)$input['capacity'] : null;
  $description = trim($input['description'] ?? '');
  $ad = isset($input['ad']) ? (bool)$input['ad'] : false;
  $discount_percent = isset($input['discount_percent']) ? (float)$input['discount_percent'] : 0;
  $images = $input['images'] ?? [];
  
  if (!$origin_city_id || !$destination_city_id || !$transfer_type_id || $base_price === null || $duration_minutes === null || $capacity === null) {
    json_error('Missing required fields: origin_city_id, destination_city_id, transfer_type_id, base_price, duration_minutes, capacity', 400);
  }
  
  if ($origin_city_id === $destination_city_id && !$origin_specific && !$destination_specific) {
    json_error('Origin and destination cities cannot be the same without specific locations', 400);
  }
  
  if ($base_price < 0) {
    json_error('Base price must be positive', 400);
  }
  
  $createdBy = $user['role'] === 'admin' && isset($input['created_by']) ? (int)$input['created_by'] : $user['id'];
  
  $stmt = $pdo->prepare('
    INSERT INTO transfer_routes (origin_city_id, destination_city_id, origin_specific, destination_specific, transfer_type_id, base_price, duration_minutes, distance_km, capacity, description, ad, discount_percent, created_by)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
  ');
  $stmt->execute([
    $origin_city_id, $destination_city_id, $origin_specific, $destination_specific,
    $transfer_type_id, $base_price, $duration_minutes, $distance_km, $capacity,
    $description, $ad ? 1 : 0, $discount_percent, $createdBy
  ]);
  $routeId = (int)$pdo->lastInsertId();
  
  // Insert images
  if (!empty($images) && is_array($images)) {
    $imgStmt = $pdo->prepare('INSERT INTO entity_images (entity_type, entity_id, image_url, display_order) VALUES (?, ?, ?, ?)');
    foreach ($images as $index => $imageUrl) {
      if (!empty($imageUrl)) {
        $imgStmt->execute(['transfer_route', $routeId, trim($imageUrl), $index + 1]);
      }
    }
  }
  
  // Fetch created route
  $stmt = $pdo->prepare('
    SELECT tr.*,
      oc.name as origin_city_name, dc.name as destination_city_name,
      tt.name as transfer_type_name
    FROM transfer_routes tr
    INNER JOIN cities oc ON tr.origin_city_id = oc.id
    INNER JOIN cities dc ON tr.destination_city_id = dc.id
    INNER JOIN transfer_types tt ON tr.transfer_type_id = tt.id
    WHERE tr.id = ?
  ');
  $stmt->execute([$routeId]);
  $route = $stmt->fetch();
  
  $images = get_entity_images($pdo, 'transfer_route', $routeId);
  $route['images'] = $images;
  $route['image_url'] = $images[0] ?? null;
  
  json_ok(['route' => $route], 201);
}

// PUT /api/transfers/routes/:id (agency/admin only)
if ($_SERVER['REQUEST_METHOD'] === 'PUT' && preg_match('#^/transfers/routes/(\d+)/?$#', $uri, $matches)) {
  requireRole(['agency', 'admin']);
  
  $id = (int)$matches[1];
  $user = get_authenticated_user();
  $input = json_decode(file_get_contents('php://input'), true);
  
  $stmt = $pdo->prepare('SELECT created_by FROM transfer_routes WHERE id = ?');
  $stmt->execute([$id]);
  $route = $stmt->fetch();
  if (!$route) {
    json_error('Route not found', 404);
  }
  
  if ($user['role'] !== 'admin' && $route['created_by'] != $user['id']) {
    json_error('Forbidden', 403);
  }
  
  $updates = [];
  $params = [];
  
  if (isset($input['origin_city_id'])) {
    $updates[] = 'origin_city_id = ?';
    $params[] = (int)$input['origin_city_id'];
  }
  if (isset($input['destination_city_id'])) {
    $updates[] = 'destination_city_id = ?';
    $params[] = (int)$input['destination_city_id'];
  }
  if (isset($input['origin_specific'])) {
    $updates[] = 'origin_specific = ?';
    $params[] = trim($input['origin_specific']);
  }
  if (isset($input['destination_specific'])) {
    $updates[] = 'destination_specific = ?';
    $params[] = trim($input['destination_specific']);
  }
  if (isset($input['transfer_type_id'])) {
    $updates[] = 'transfer_type_id = ?';
    $params[] = (int)$input['transfer_type_id'];
  }
  if (isset($input['base_price'])) {
    $updates[] = 'base_price = ?';
    $params[] = (float)$input['base_price'];
  }
  if (isset($input['duration_minutes'])) {
    $updates[] = 'duration_minutes = ?';
    $params[] = (int)$input['duration_minutes'];
  }
  if (isset($input['distance_km'])) {
    $updates[] = 'distance_km = ?';
    $params[] = (float)$input['distance_km'];
  }
  if (isset($input['capacity'])) {
    $updates[] = 'capacity = ?';
    $params[] = (int)$input['capacity'];
  }
  if (isset($input['description'])) {
    $updates[] = 'description = ?';
    $params[] = trim($input['description']);
  }
  if (isset($input['ad'])) {
    $updates[] = 'ad = ?';
    $params[] = $input['ad'] ? 1 : 0;
  }
  if (isset($input['discount_percent'])) {
    $updates[] = 'discount_percent = ?';
    $params[] = (float)$input['discount_percent'];
  }
  
  $images = $input['images'] ?? null;
  
  if (empty($updates) && $images === null) {
    json_error('No fields to update', 400);
  }
  
  if (!empty($updates)) {
    $params[] = $id;
    $sql = 'UPDATE transfer_routes SET ' . implode(', ', $updates) . ' WHERE id = ?';
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
  }
  
  // Update images if provided
  if ($images !== null && is_array($images)) {
    $delStmt = $pdo->prepare('DELETE FROM entity_images WHERE entity_type = ? AND entity_id = ?');
    $delStmt->execute(['transfer_route', $id]);
    
    if (!empty($images)) {
      $imgStmt = $pdo->prepare('INSERT INTO entity_images (entity_type, entity_id, image_url, display_order) VALUES (?, ?, ?, ?)');
      foreach ($images as $index => $imageUrl) {
        if (!empty($imageUrl)) {
          $imgStmt->execute(['transfer_route', $id, trim($imageUrl), $index + 1]);
        }
      }
    }
  }
  
  $stmt = $pdo->prepare('
    SELECT tr.*,
      oc.name as origin_city_name, dc.name as destination_city_name,
      tt.name as transfer_type_name
    FROM transfer_routes tr
    INNER JOIN cities oc ON tr.origin_city_id = oc.id
    INNER JOIN cities dc ON tr.destination_city_id = dc.id
    INNER JOIN transfer_types tt ON tr.transfer_type_id = tt.id
    WHERE tr.id = ?
  ');
  $stmt->execute([$id]);
  $route = $stmt->fetch();
  
  $images = get_entity_images($pdo, 'transfer_route', $id);
  $route['images'] = $images;
  $route['image_url'] = $images[0] ?? null;
  
  json_ok(['route' => $route]);
}

// DELETE /api/transfers/routes/:id (agency/admin only)
if ($_SERVER['REQUEST_METHOD'] === 'DELETE' && preg_match('#^/transfers/routes/(\d+)/?$#', $uri, $matches)) {
  requireRole(['agency', 'admin']);
  
  $id = (int)$matches[1];
  $user = get_authenticated_user();
  
  $stmt = $pdo->prepare('SELECT created_by FROM transfer_routes WHERE id = ?');
  $stmt->execute([$id]);
  $route = $stmt->fetch();
  if (!$route) {
    json_error('Route not found', 404);
  }
  
  if ($user['role'] !== 'admin' && $route['created_by'] != $user['id']) {
    json_error('Forbidden', 403);
  }
  
  // Check if route has schedules/instances
  $checkStmt = $pdo->prepare('SELECT COUNT(*) as count FROM transfer_schedules WHERE route_id = ?');
  $checkStmt->execute([$id]);
  $result = $checkStmt->fetch();
  if ($result['count'] > 0) {
    json_error('Cannot delete route: it has associated schedules', 400);
  }
  
  $stmt = $pdo->prepare('DELETE FROM transfer_routes WHERE id = ?');
  $stmt->execute([$id]);
  
  json_ok(['message' => 'Route deleted successfully']);
}

json_error('Not found', 404);







