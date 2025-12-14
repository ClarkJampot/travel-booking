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

// GET /api/transfers/routes/:id or /api/transfers/routes?id=X
if ($_SERVER['REQUEST_METHOD'] === 'GET' && preg_match('#^/transfers/routes(?:/(\d+))?/?$#', $uri, $matches)) {
  $id = isset($matches[1]) ? (int)$matches[1] : (isset($_GET['id']) ? (int)$_GET['id'] : null);
  $origin_city_id = isset($_GET['origin_city_id']) ? (int)$_GET['origin_city_id'] : null;
  $destination_city_id = isset($_GET['destination_city_id']) ? (int)$_GET['destination_city_id'] : null;
  $q = isset($_GET['q']) ? trim($_GET['q']) : null;
  $createdBy = isset($_GET['createdBy']) ? (int)$_GET['createdBy'] : null;
  
  // Get single route
  if ($id) {
    $stmt = $pdo->prepare('
      SELECT tr.*,
        oc.name as origin_city_name, oc.province_id as origin_province_id,
        op.name as origin_province_name,
        dc.name as destination_city_name, dc.province_id as destination_province_id,
        dp.name as destination_province_name,
        ts.departure_time
      FROM transfer_routes tr
      INNER JOIN cities oc ON tr.origin_city_id = oc.id
      INNER JOIN cities dc ON tr.destination_city_id = dc.id
      LEFT JOIN provinces op ON oc.province_id = op.id
      LEFT JOIN provinces dp ON dc.province_id = dp.id
      LEFT JOIN transfer_schedules ts ON ts.route_id = tr.id AND ts.is_active = 1
      WHERE tr.id = ?
    ');
    $stmt->execute([$id]);
    $route = $stmt->fetch();
    if (!$route) {
      json_error('Route not found', 404);
    }
    
    // Transfers don't have images
    $route['images'] = [];
    $route['image_url'] = null;
    
    // Format departure_time if present (TIME to HH:MM format)
    if (isset($route['departure_time']) && $route['departure_time']) {
      $timeStr = (string)$route['departure_time'];
      // SQL Server TIME type returns as "HH:MM:SS" or "HH:MM:SS.mmm"
      // Extract just HH:MM
      if (preg_match('/^(\d{1,2}):(\d{2})/', $timeStr, $matches)) {
        $route['departure_time'] = str_pad($matches[1], 2, '0', STR_PAD_LEFT) . ':' . $matches[2];
      } else {
        // Fallback: try to extract first 5 characters
        $route['departure_time'] = substr($timeStr, 0, 5);
      }
    }
    
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
      oc.name as origin_city_name, dc.name as destination_city_name
    FROM transfer_routes tr
    INNER JOIN cities oc ON tr.origin_city_id = oc.id
    INNER JOIN cities dc ON tr.destination_city_id = dc.id
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
  
  require_once __DIR__ . '/../helpers/ValidationHelper.php';
  
  $user = get_authenticated_user();
  $input = json_decode(file_get_contents('php://input'), true);
  
  try {
    // Validate required integer fields
    $origin_city_id = ValidationHelper::validateInt($input['origin_city_id'] ?? null, 'origin_city_id', true, 1);
    $destination_city_id = ValidationHelper::validateInt($input['destination_city_id'] ?? null, 'destination_city_id', true, 1);
    
    // Validate prices
    $base_price = ValidationHelper::validateDecimal($input['base_price'] ?? null, 'base_price', true, 0.01);
    $discount_percent = ValidationHelper::validateDecimal($input['discount_percent'] ?? 0, 'discount_percent', false, 0, 100);
    
    // Validate strings
    $origin_specific = ValidationHelper::validateString($input['origin_specific'] ?? null, 'origin_specific', false, 255);
    $destination_specific = ValidationHelper::validateString($input['destination_specific'] ?? null, 'destination_specific', false, 255);
    $description = ValidationHelper::validateString($input['description'] ?? null, 'description', false);
    $departure_time = ValidationHelper::validateTime($input['departure_time'] ?? null, 'departure_time', true);
    $days_of_week = '0,1,2,3,4,5,6'; // Daily by default
    
    $ad = isset($input['ad']) && ($input['ad'] === true || $input['ad'] === 1 || $input['ad'] === '1');
    
    // Validate that origin and destination are different (unless specific locations are provided)
    if ($origin_city_id === $destination_city_id && (!$origin_specific || !$destination_specific)) {
      throw new InvalidArgumentException('Origin and destination cities cannot be the same without specific locations');
    }
  } catch (InvalidArgumentException $e) {
    json_error($e->getMessage(), 400);
  } catch (Exception $e) {
    error_log('Validation error: ' . $e->getMessage());
    json_error('Validation error: ' . $e->getMessage(), 400);
  }
  
  $createdBy = $user['role'] === 'admin' && isset($input['created_by']) ? (int)$input['created_by'] : $user['id'];
  
  $stmt = $pdo->prepare('
    INSERT INTO transfer_routes (origin_city_id, destination_city_id, origin_specific, destination_specific, base_price, description, ad, discount_percent, created_by)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
  ');
  $stmt->execute([
    $origin_city_id, $destination_city_id, $origin_specific, $destination_specific,
    $base_price,
    $description, $ad ? 1 : 0, $discount_percent, $createdBy
  ]);
  $routeId = (int)$pdo->lastInsertId();
  
  // Create schedule for this route
  $scheduleStmt = $pdo->prepare('
    INSERT INTO transfer_schedules (route_id, departure_time, days_of_week, is_active)
    VALUES (?, ?, ?, 1)
  ');
  $scheduleStmt->execute([$routeId, $departure_time, $days_of_week]);
  $scheduleId = (int)$pdo->lastInsertId();
  
  // Generate instances for the next 30 days
  // Default: 3 vehicles, 12 seats per vehicle = 36 total seats
  $vehiclesTotal = 3;
  $seatsTotal = $vehiclesTotal * 12;
  
  $instanceStmt = $pdo->prepare('
    INSERT INTO transfer_instances (schedule_id, departure_date, departure_datetime, price, vehicles_total, vehicles_available, seats_available, status)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?)
  ');
  
  $today = new DateTime();
  $today->setTime(0, 0, 0);
  
  for ($day = 0; $day < 30; $day++) {
    $departureDate = clone $today;
    $departureDate->modify("+{$day} days");
    
    // Parse days_of_week (e.g., "1,2,3,4,5" for Mon-Fri)
    $daysArray = array_map('trim', explode(',', $days_of_week));
    $dayOfWeek = (int)$departureDate->format('w'); // 0 = Sunday, 1 = Monday, etc.
    
    // Check if this day matches the schedule
    if (in_array((string)$dayOfWeek, $daysArray)) {
      // Combine date and time
      $timeParts = explode(':', $departure_time);
      $departureDateTime = clone $departureDate;
      $departureDateTime->setTime((int)$timeParts[0], (int)$timeParts[1], 0);
      
      $instanceStmt->execute([
        $scheduleId,
        $departureDate->format('Y-m-d'),
        $departureDateTime->format('Y-m-d H:i:s'),
        $base_price,
        $vehiclesTotal,
        $vehiclesTotal,
        $seatsTotal,
        'scheduled'
      ]);
    }
  }
  
  // Fetch created route
  $stmt = $pdo->prepare('
    SELECT tr.*,
      oc.name as origin_city_name, dc.name as destination_city_name
    FROM transfer_routes tr
    INNER JOIN cities oc ON tr.origin_city_id = oc.id
    INNER JOIN cities dc ON tr.destination_city_id = dc.id
    WHERE tr.id = ?
  ');
  $stmt->execute([$routeId]);
  $route = $stmt->fetch();
  
  // Transfers don't have images
  $route['images'] = [];
  $route['image_url'] = null;
  
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
  if (isset($input['base_price'])) {
    $updates[] = 'base_price = ?';
    $params[] = (float)$input['base_price'];
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
      oc.name as origin_city_name, dc.name as destination_city_name
    FROM transfer_routes tr
    INNER JOIN cities oc ON tr.origin_city_id = oc.id
    INNER JOIN cities dc ON tr.destination_city_id = dc.id
    WHERE tr.id = ?
  ');
  $stmt->execute([$id]);
  $route = $stmt->fetch();
  
  // Transfers don't have images
  $route['images'] = [];
  $route['image_url'] = null;
  
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








