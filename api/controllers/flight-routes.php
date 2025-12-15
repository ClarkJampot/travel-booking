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


// GET /api/flights/routes/:id or /api/flights/routes?id=X
if ($_SERVER['REQUEST_METHOD'] === 'GET' && preg_match('#^/flights/routes(?:/(\d+))?/?$#', $uri, $matches)) {
  $id = isset($matches[1]) ? (int)$matches[1] : (isset($_GET['id']) ? (int)$_GET['id'] : null);
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
        oc.name as origin_city_name, oc.province_id as origin_province_id,
        op.name as origin_province_name,
        da.code as destination_code, da.name as destination_name, da.city_id as destination_city_id,
        dc.name as destination_city_name, dc.province_id as destination_province_id,
        dp.name as destination_province_name,
        fs.departure_time
      FROM flight_routes fr
      INNER JOIN airports oa ON fr.origin_airport_id = oa.id
      INNER JOIN airports da ON fr.destination_airport_id = da.id
      LEFT JOIN cities oc ON oa.city_id = oc.id
      LEFT JOIN provinces op ON oc.province_id = op.id
      LEFT JOIN cities dc ON da.city_id = dc.id
      LEFT JOIN provinces dp ON dc.province_id = dp.id
      LEFT JOIN flight_schedules fs ON fs.route_id = fr.id AND fs.is_active = 1
      WHERE fr.id = ?
    ');
    $stmt->execute([$id]);
    $route = $stmt->fetch();
    if (!$route) {
      ResponseHelper::error('Route not found', 404);
    }
    
    // Flights don't have images
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
    
    ResponseHelper::successSimple(['route' => $route]);
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
  
  ResponseHelper::successSimple(['results' => $routes]);
}

// POST /api/flights/routes (agency/admin only)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && preg_match('#^/flights/routes/?$#', $uri)) {
  requireRole(['agency', 'admin']);
  
  require_once __DIR__ . '/../helpers/ValidationHelper.php';
  
  $user = get_authenticated_user();
  $input = json_decode(file_get_contents('php://input'), true);
  
  try {
    // Validate required integer fields
    $origin_province_id = ValidationHelper::validateInt($input['origin_province_id'] ?? null, 'origin_province_id', true, 1);
    $origin_city_id = ValidationHelper::validateInt($input['origin_city_id'] ?? null, 'origin_city_id', true, 1);
    $destination_province_id = ValidationHelper::validateInt($input['destination_province_id'] ?? null, 'destination_province_id', true, 1);
    $destination_city_id = ValidationHelper::validateInt($input['destination_city_id'] ?? null, 'destination_city_id', true, 1);
    
    // Validate prices
    $base_price_economy = ValidationHelper::validateDecimal($input['base_price_economy'] ?? null, 'base_price_economy', true, 0.01);
    $base_price_business = ValidationHelper::validateDecimal($input['base_price_business'] ?? null, 'base_price_business', false, 0.01);
    $base_price_first = ValidationHelper::validateDecimal($input['base_price_first'] ?? null, 'base_price_first', false, 0.01);
    
    // Validate other fields
    $aircraft_type = ValidationHelper::validateString($input['aircraft_type'] ?? null, 'aircraft_type', false, 100);
    $departure_time = ValidationHelper::validateTime($input['departure_time'] ?? null, 'departure_time', true);
    $days_of_week = '0,1,2,3,4,5,6'; // Daily by default
    $ad = isset($input['ad']) && ($input['ad'] === true || $input['ad'] === 1 || $input['ad'] === '1');
    $discount_percent = ValidationHelper::validateDecimal($input['discount_percent'] ?? 0, 'discount_percent', false, 0, 100);
    
    // Validate that origin and destination are different
    if ($origin_city_id === $destination_city_id && $origin_province_id === $destination_province_id) {
      throw new InvalidArgumentException('Origin and destination cannot be the same');
    }
  } catch (InvalidArgumentException $e) {
    ResponseHelper::error($e->getMessage(), 400);
  } catch (Exception $e) {
    error_log('Validation error: ' . $e->getMessage());
    ResponseHelper::error('Validation error: ' . $e->getMessage(), 400);
  }
  
  // Find or create airports based on city-province pairs
  // Get origin province code
  $originProvinceStmt = $pdo->prepare('SELECT code, name FROM provinces WHERE id = ?');
  $originProvinceStmt->execute([$origin_province_id]);
  $originProvince = $originProvinceStmt->fetch();
  $originAirportCode = $originProvince['code'] ?? 'ORG';
  $originAirportName = ($originProvince['name'] ?? 'Origin') . ' Airport';
  
  // Get destination province code
  $destProvinceStmt = $pdo->prepare('SELECT code, name FROM provinces WHERE id = ?');
  $destProvinceStmt->execute([$destination_province_id]);
  $destProvince = $destProvinceStmt->fetch();
  $destAirportCode = $destProvince['code'] ?? 'DST';
  $destAirportName = ($destProvince['name'] ?? 'Destination') . ' Airport';
  
  // Find or create origin airport
  $airportStmt = $pdo->prepare('SELECT id FROM airports WHERE code = ?');
  $airportStmt->execute([$originAirportCode]);
  $originAirport = $airportStmt->fetch();
  if (!$originAirport) {
    $createAirportStmt = $pdo->prepare('INSERT INTO airports (code, name, city_id, is_international) VALUES (?, ?, ?, 0)');
    $createAirportStmt->execute([$originAirportCode, $originAirportName, $origin_city_id]);
    $origin_airport_id = (int)$pdo->lastInsertId();
  } else {
    $origin_airport_id = (int)$originAirport['id'];
  }
  
  // Find or create destination airport
  $airportStmt->execute([$destAirportCode]);
  $destAirport = $airportStmt->fetch();
  if (!$destAirport) {
    $createAirportStmt = $pdo->prepare('INSERT INTO airports (code, name, city_id, is_international) VALUES (?, ?, ?, 0)');
    $createAirportStmt->execute([$destAirportCode, $destAirportName, $destination_city_id]);
    $destination_airport_id = (int)$pdo->lastInsertId();
  } else {
    $destination_airport_id = (int)$destAirport['id'];
  }
  
  if ($origin_airport_id === $destination_airport_id) {
    ResponseHelper::error('Origin and destination airports cannot be the same', 400);
  }
  
  $createdBy = $user['role'] === 'admin' && isset($input['created_by']) ? ValidationHelper::validateInt($input['created_by'], 'created_by', false, 1) : $user['id'];
  
  try {
    $stmt = $pdo->prepare('
      INSERT INTO flight_routes (origin_airport_id, destination_airport_id, airline, base_price_economy, base_price_business, base_price_first, aircraft_type, ad, discount_percent, created_by)
      VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ');
    $stmt->execute([
      $origin_airport_id, $destination_airport_id, $airline,
      $base_price_economy, $base_price_business, $base_price_first,
      $aircraft_type,
      $ad ? 1 : 0, $discount_percent, $createdBy
    ]);
    $routeId = (int)$pdo->lastInsertId();
  } catch (PDOException $e) {
    error_log('Flight route creation error: ' . $e->getMessage());
    if (strpos($e->getMessage(), 'UQ_flight_routes_airline_route') !== false) {
      ResponseHelper::error('Route already exists for this airline', 400);
    }
    ResponseHelper::error('Database error: ' . $e->getMessage(), 500);
  } catch (Exception $e) {
    error_log('Flight route creation error: ' . $e->getMessage());
    ResponseHelper::error('Error creating flight route: ' . $e->getMessage(), 500);
  }
  
  // Create schedule for this route
  try {
    $scheduleStmt = $pdo->prepare('
      INSERT INTO flight_schedules (route_id, departure_time, days_of_week, is_active)
      VALUES (?, ?, ?, 1)
    ');
    $scheduleStmt->execute([$routeId, $departure_time, $days_of_week]);
    $scheduleId = (int)$pdo->lastInsertId();
  } catch (PDOException $e) {
    error_log('Flight schedule creation error: ' . $e->getMessage());
    ResponseHelper::error('Error creating flight schedule: ' . $e->getMessage(), 500);
  }
  
  // Generate instances for the next 30 days
  try {
    $instanceStmt = $pdo->prepare('
      INSERT INTO flight_instances (schedule_id, departure_date, departure_datetime, price_economy, price_business, price_first, seats_economy_total, seats_economy_available, seats_business_total, seats_business_available, seats_first_total, seats_first_available, status)
      VALUES (?, ?, ?, ?, ?, ?, 180, 180, 30, 30, 12, 12, ?)
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
        if (count($timeParts) < 2) {
          error_log("Invalid departure_time format: {$departure_time}");
          continue;
        }
        $departureDateTime = clone $departureDate;
        $departureDateTime->setTime((int)$timeParts[0], (int)$timeParts[1], 0);
        
        $instanceStmt->execute([
          $scheduleId,
          $departureDate->format('Y-m-d'),
          $departureDateTime->format('Y-m-d H:i:s'),
          $base_price_economy,
          $base_price_business,
          $base_price_first,
          'scheduled'
        ]);
      }
    }
  } catch (PDOException $e) {
    error_log('Flight instance creation error: ' . $e->getMessage());
    ResponseHelper::error('Error creating flight instances: ' . $e->getMessage(), 500);
  } catch (Exception $e) {
    error_log('Flight instance creation error: ' . $e->getMessage());
    ResponseHelper::error('Error creating flight instances: ' . $e->getMessage(), 500);
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
  
  // Flights don't have images
  $route['images'] = [];
  $route['image_url'] = null;
  
  ResponseHelper::successSimple(['route' => $route], 201);
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
    ResponseHelper::error('Route not found', 404);
  }
  
  if ($user['role'] !== 'admin' && $route['created_by'] != $user['id']) {
    ResponseHelper::error('Forbidden', 403);
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
    ResponseHelper::error('No fields to update', 400);
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
  
  // Flights don't have images
  $route['images'] = [];
  $route['image_url'] = null;
  
  ResponseHelper::successSimple(['route' => $route]);
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
    ResponseHelper::error('Route not found', 404);
  }
  
  if ($user['role'] !== 'admin' && $route['created_by'] != $user['id']) {
    ResponseHelper::error('Forbidden', 403);
  }
  
  // Check if route has schedules/instances
  $checkStmt = $pdo->prepare('SELECT COUNT(*) as count FROM flight_schedules WHERE route_id = ?');
  $checkStmt->execute([$id]);
  $result = $checkStmt->fetch();
  if ($result['count'] > 0) {
    ResponseHelper::error('Cannot delete route: it has associated schedules', 400);
  }
  
  $stmt = $pdo->prepare('DELETE FROM flight_routes WHERE id = ?');
  $stmt->execute([$id]);
  
  ResponseHelper::successSimple(['message' => 'Route deleted successfully']);
}

ResponseHelper::error('Not found', 404);








