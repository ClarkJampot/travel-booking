<?php
// Flight Routes controller
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

// GET /api/flights/routes
if ($_SERVER['REQUEST_METHOD'] === 'GET' && preg_match('#^/flights/routes/?$#', $uri)) {
  $id = isset($_GET['id']) ? (int)$_GET['id'] : null;
  $origin_airport_id = isset($_GET['origin_airport_id']) ? (int)$_GET['origin_airport_id'] : null;
  $destination_airport_id = isset($_GET['destination_airport_id']) ? (int)$_GET['destination_airport_id'] : null;
  $airline = isset($_GET['airline']) ? trim($_GET['airline']) : null;
  $q = isset($_GET['q']) ? trim($_GET['q']) : null;
  $createdBy = isset($_GET['createdBy']) ? (int)$_GET['createdBy'] : null;
  
  // Get single route
  if ($id) {
    $stmt = $pdo->prepare('
      SELECT fr.*,
        oa.code as origin_code, oa.name as origin_name, oa.city_id as origin_city_id,
        oc.name as origin_city_name, op.name as origin_province_name,
        da.code as destination_code, da.name as destination_name, da.city_id as destination_city_id,
        dc.name as destination_city_name, dp.name as destination_province_name
      FROM flight_routes fr
      INNER JOIN airports oa ON fr.origin_airport_id = oa.id
      INNER JOIN airports da ON fr.destination_airport_id = da.id
      LEFT JOIN cities oc ON oa.city_id = oc.id
      LEFT JOIN provinces op ON oc.province_id = op.id
      LEFT JOIN cities dc ON da.city_id = dc.id
      LEFT JOIN provinces dp ON dc.province_id = dp.id
      WHERE fr.id = ?
    ');
    $stmt->execute([$id]);
    $route = $stmt->fetch();
    if (!$route) {
      json_error('Route not found', 404);
    }
    
    // Get images
    $images = get_entity_images($pdo, 'flight_route', $id);
    $route['images'] = $images;
    $route['image_url'] = $images[0] ?? null;
    
    json_ok(['route' => $route]);
  }
  
  // List routes
  $where = [];
  $params = [];
  
  if ($origin_airport_id !== null) {
    $where[] = 'fr.origin_airport_id = ?';
    $params[] = $origin_airport_id;
  }
  if ($destination_airport_id !== null) {
    $where[] = 'fr.destination_airport_id = ?';
    $params[] = $destination_airport_id;
  }
  if ($airline) {
    $where[] = 'fr.airline = ?';
    $params[] = $airline;
  }
  if ($q) {
    $searchTerm = str_replace(['%', '_', '[', ']'], ['[%]', '[_]', '[[]', '[]]'], $q);
    $where[] = "(fr.airline COLLATE SQL_Latin1_General_CP1_CI_AI LIKE ? OR oa.name COLLATE SQL_Latin1_General_CP1_CI_AI LIKE ? OR da.name COLLATE SQL_Latin1_General_CP1_CI_AI LIKE ?)";
    $searchPattern = '%' . $searchTerm . '%';
    $params[] = $searchPattern;
    $params[] = $searchPattern;
    $params[] = $searchPattern;
  }
  if ($createdBy !== null) {
    $where[] = 'fr.created_by = ?';
    $params[] = $createdBy;
  }
  
  $whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';
  
  $sql = "SELECT fr.*,
      oa.code as origin_code, oa.name as origin_name,
      da.code as destination_code, da.name as destination_name,
      oc.name as origin_city_name, dc.name as destination_city_name
    FROM flight_routes fr
    INNER JOIN airports oa ON fr.origin_airport_id = oa.id
    INNER JOIN airports da ON fr.destination_airport_id = da.id
    LEFT JOIN cities oc ON oa.city_id = oc.id
    LEFT JOIN cities dc ON da.city_id = dc.id
    $whereSql
    ORDER BY fr.created_at DESC";
  
  $stmt = $pdo->prepare($sql);
  $stmt->execute($params);
  $routes = $stmt->fetchAll();
  
  json_ok(['results' => $routes]);
}

// POST /api/flights/routes (agency/admin only)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && preg_match('#^/flights/routes/?$#', $uri)) {
  requireRole(['agency', 'admin']);
  
  $user = get_authenticated_user();
  $input = json_decode(file_get_contents('php://input'), true);
  
  $origin_airport_id = isset($input['origin_airport_id']) ? (int)$input['origin_airport_id'] : null;
  $destination_airport_id = isset($input['destination_airport_id']) ? (int)$input['destination_airport_id'] : null;
  $airline = trim($input['airline'] ?? '');
  $base_price_economy = isset($input['base_price_economy']) ? (float)$input['base_price_economy'] : null;
  $base_price_business = isset($input['base_price_business']) ? (float)$input['base_price_business'] : null;
  $base_price_first = isset($input['base_price_first']) ? (float)$input['base_price_first'] : null;
  $duration_minutes = isset($input['duration_minutes']) ? (int)$input['duration_minutes'] : null;
  $aircraft_type = trim($input['aircraft_type'] ?? '');
  $description = trim($input['description'] ?? '');
  $ad = isset($input['ad']) ? (bool)$input['ad'] : false;
  $discount_percent = isset($input['discount_percent']) ? (float)$input['discount_percent'] : 0;
  $images = $input['images'] ?? [];
  
  if (!$origin_airport_id || !$destination_airport_id || !$airline || $base_price_economy === null || $duration_minutes === null) {
    json_error('Missing required fields: origin_airport_id, destination_airport_id, airline, base_price_economy, duration_minutes', 400);
  }
  
  if ($origin_airport_id === $destination_airport_id) {
    json_error('Origin and destination airports cannot be the same', 400);
  }
  
  if ($base_price_economy < 0) {
    json_error('Base price must be positive', 400);
  }
  
  $createdBy = $user['role'] === 'admin' && isset($input['created_by']) ? (int)$input['created_by'] : $user['id'];
  
  try {
    $stmt = $pdo->prepare('
      INSERT INTO flight_routes (origin_airport_id, destination_airport_id, airline, base_price_economy, base_price_business, base_price_first, duration_minutes, aircraft_type, description, ad, discount_percent, created_by)
      VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ');
    $stmt->execute([
      $origin_airport_id, $destination_airport_id, $airline,
      $base_price_economy, $base_price_business, $base_price_first,
      $duration_minutes, $aircraft_type, $description,
      $ad ? 1 : 0, $discount_percent, $createdBy
    ]);
    $routeId = (int)$pdo->lastInsertId();
  } catch (PDOException $e) {
    if (strpos($e->getMessage(), 'UQ_flight_routes_airline_route') !== false) {
      json_error('Route already exists for this airline', 400);
    }
    throw $e;
  }
  
  // Insert images
  if (!empty($images) && is_array($images)) {
    $imgStmt = $pdo->prepare('INSERT INTO entity_images (entity_type, entity_id, image_url, display_order) VALUES (?, ?, ?, ?)');
    foreach ($images as $index => $imageUrl) {
      if (!empty($imageUrl)) {
        $imgStmt->execute(['flight_route', $routeId, trim($imageUrl), $index + 1]);
      }
    }
  }
  
  // Fetch created route
  $stmt = $pdo->prepare('
    SELECT fr.*,
      oa.code as origin_code, oa.name as origin_name,
      da.code as destination_code, da.name as destination_name,
      oc.name as origin_city_name, dc.name as destination_city_name
    FROM flight_routes fr
    INNER JOIN airports oa ON fr.origin_airport_id = oa.id
    INNER JOIN airports da ON fr.destination_airport_id = da.id
    LEFT JOIN cities oc ON oa.city_id = oc.id
    LEFT JOIN cities dc ON da.city_id = dc.id
    WHERE fr.id = ?
  ');
  $stmt->execute([$routeId]);
  $route = $stmt->fetch();
  
  $images = get_entity_images($pdo, 'flight_route', $routeId);
  $route['images'] = $images;
  $route['image_url'] = $images[0] ?? null;
  
  json_ok(['route' => $route], 201);
}

// PUT /api/flights/routes/:id (agency/admin only)
if ($_SERVER['REQUEST_METHOD'] === 'PUT' && preg_match('#^/flights/routes/(\d+)/?$#', $uri, $matches)) {
  requireRole(['agency', 'admin']);
  
  $id = (int)$matches[1];
  $user = get_authenticated_user();
  $input = json_decode(file_get_contents('php://input'), true);
  
  $stmt = $pdo->prepare('SELECT created_by FROM flight_routes WHERE id = ?');
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
  
  if (isset($input['origin_airport_id'])) {
    $updates[] = 'origin_airport_id = ?';
    $params[] = (int)$input['origin_airport_id'];
  }
  if (isset($input['destination_airport_id'])) {
    $updates[] = 'destination_airport_id = ?';
    $params[] = (int)$input['destination_airport_id'];
  }
  if (isset($input['airline'])) {
    $updates[] = 'airline = ?';
    $params[] = trim($input['airline']);
  }
  if (isset($input['base_price_economy'])) {
    $updates[] = 'base_price_economy = ?';
    $params[] = (float)$input['base_price_economy'];
  }
  if (isset($input['base_price_business'])) {
    $updates[] = 'base_price_business = ?';
    $params[] = (float)$input['base_price_business'];
  }
  if (isset($input['base_price_first'])) {
    $updates[] = 'base_price_first = ?';
    $params[] = (float)$input['base_price_first'];
  }
  if (isset($input['duration_minutes'])) {
    $updates[] = 'duration_minutes = ?';
    $params[] = (int)$input['duration_minutes'];
  }
  if (isset($input['aircraft_type'])) {
    $updates[] = 'aircraft_type = ?';
    $params[] = trim($input['aircraft_type']);
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
    $sql = 'UPDATE flight_routes SET ' . implode(', ', $updates) . ' WHERE id = ?';
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
  }
  
  // Update images if provided
  if ($images !== null && is_array($images)) {
    $delStmt = $pdo->prepare('DELETE FROM entity_images WHERE entity_type = ? AND entity_id = ?');
    $delStmt->execute(['flight_route', $id]);
    
    if (!empty($images)) {
      $imgStmt = $pdo->prepare('INSERT INTO entity_images (entity_type, entity_id, image_url, display_order) VALUES (?, ?, ?, ?)');
      foreach ($images as $index => $imageUrl) {
        if (!empty($imageUrl)) {
          $imgStmt->execute(['flight_route', $id, trim($imageUrl), $index + 1]);
        }
      }
    }
  }
  
  $stmt = $pdo->prepare('
    SELECT fr.*,
      oa.code as origin_code, oa.name as origin_name,
      da.code as destination_code, da.name as destination_name,
      oc.name as origin_city_name, dc.name as destination_city_name
    FROM flight_routes fr
    INNER JOIN airports oa ON fr.origin_airport_id = oa.id
    INNER JOIN airports da ON fr.destination_airport_id = da.id
    LEFT JOIN cities oc ON oa.city_id = oc.id
    LEFT JOIN cities dc ON da.city_id = dc.id
    WHERE fr.id = ?
  ');
  $stmt->execute([$id]);
  $route = $stmt->fetch();
  
  $images = get_entity_images($pdo, 'flight_route', $id);
  $route['images'] = $images;
  $route['image_url'] = $images[0] ?? null;
  
  json_ok(['route' => $route]);
}

// DELETE /api/flights/routes/:id (agency/admin only)
if ($_SERVER['REQUEST_METHOD'] === 'DELETE' && preg_match('#^/flights/routes/(\d+)/?$#', $uri, $matches)) {
  requireRole(['agency', 'admin']);
  
  $id = (int)$matches[1];
  $user = get_authenticated_user();
  
  $stmt = $pdo->prepare('SELECT created_by FROM flight_routes WHERE id = ?');
  $stmt->execute([$id]);
  $route = $stmt->fetch();
  if (!$route) {
    json_error('Route not found', 404);
  }
  
  if ($user['role'] !== 'admin' && $route['created_by'] != $user['id']) {
    json_error('Forbidden', 403);
  }
  
  // Check if route has schedules/instances
  $checkStmt = $pdo->prepare('SELECT COUNT(*) as count FROM flight_schedules WHERE route_id = ?');
  $checkStmt->execute([$id]);
  $result = $checkStmt->fetch();
  if ($result['count'] > 0) {
    json_error('Cannot delete route: it has associated schedules', 400);
  }
  
  $stmt = $pdo->prepare('DELETE FROM flight_routes WHERE id = ?');
  $stmt->execute([$id]);
  
  json_ok(['message' => 'Route deleted successfully']);
}

json_error('Not found', 404);







