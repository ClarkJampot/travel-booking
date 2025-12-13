<?php
// Transfer Instances controller
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

// GET /api/transfer-instances?transfer_id=X&date=Y&passenger_count=Z
if ($_SERVER['REQUEST_METHOD'] === 'GET' && preg_match('#^/transfer-instances/?$#', $uri)) {
  $transferId = isset($_GET['transfer_id']) ? (int)$_GET['transfer_id'] : null;
  $date = $_GET['date'] ?? null;
  $passengerCount = isset($_GET['passenger_count']) ? (int)$_GET['passenger_count'] : 1;
  
  if (!$transferId || !$date) {
    json_error('transfer_id and date are required', 400);
  }
  
  // Get instances for this transfer route on the specified date
  // Use DATE comparison that accounts for timezone - compare date parts only
  $stmt = $pdo->prepare('
    SELECT ti.*,
      ts.route_id, ts.departure_time,
      tr.base_price, tr.discount_percent, tr.capacity,
      tr.origin_city_id, tr.destination_city_id,
      oc.name as origin_city_name, dc.name as destination_city_name
    FROM transfer_instances ti
    INNER JOIN transfer_schedules ts ON ti.schedule_id = ts.id
    INNER JOIN transfer_routes tr ON ts.route_id = tr.id
    INNER JOIN cities oc ON tr.origin_city_id = oc.id
    INNER JOIN cities dc ON tr.destination_city_id = dc.id
    WHERE tr.id = ?
      AND CAST(ti.departure_datetime AS DATE) = CAST(? AS DATE)
      AND ti.status = ?
      AND ti.seats_available >= ?
    ORDER BY ti.departure_datetime ASC
  ');
  $stmt->execute([$transferId, $date, 'scheduled', $passengerCount]);
  $instances = $stmt->fetchAll();
  
  // Calculate prices and discounted prices
  foreach ($instances as &$instance) {
    $price = $instance['price'] ?? $instance['base_price'];
    $instance['price'] = $price;
    if ($instance['discount_percent'] > 0) {
      $instance['discounted_price'] = round($price * (1 - $instance['discount_percent'] / 100), 2);
    }
  }
  
  json_ok(['instances' => $instances]);
  exit;
}

// GET /api/transfers/availability?transfer_id=X
if ($_SERVER['REQUEST_METHOD'] === 'GET' && preg_match('#^/transfers/availability/?$#', $uri)) {
  $transferId = isset($_GET['transfer_id']) ? (int)$_GET['transfer_id'] : null;
  
  if (!$transferId) {
    json_error('transfer_id is required', 400);
  }
  
  // Get all available dates for this transfer route
  // Query transfer_instances that are scheduled and have available seats
  $stmt = $pdo->prepare('
    SELECT DISTINCT CAST(ti.departure_date AS DATE) as available_date
    FROM transfer_instances ti
    INNER JOIN transfer_schedules ts ON ti.schedule_id = ts.id
    INNER JOIN transfer_routes tr ON ts.route_id = tr.id
    WHERE tr.id = ?
      AND ti.status = ?
      AND ti.departure_date >= CAST(GETDATE() AS DATE)
      AND ti.seats_available > 0
    ORDER BY available_date ASC
  ');
  $stmt->execute([$transferId, 'scheduled']);
  $results = $stmt->fetchAll();
  
  $availableDates = array_map(function($row) {
    return $row['available_date'];
  }, $results);
  
  json_ok(['available_dates' => $availableDates]);
  exit;
}

// GET /api/transfers (search for available instances)
if ($_SERVER['REQUEST_METHOD'] === 'GET' && preg_match('#^/transfers/?$#', $uri)) {
  $id = isset($_GET['id']) ? (int)$_GET['id'] : null;
  
  // Get single instance
  if ($id) {
    $stmt = $pdo->prepare('
      SELECT ti.*,
        ts.route_id, ts.departure_time, ts.days_of_week,
        tr.origin_city_id, tr.destination_city_id, tr.origin_specific, tr.destination_specific,
        tr.transfer_type_id, tr.base_price, tr.duration_minutes, tr.distance_km, tr.capacity,
        tr.description as route_description, tr.ad, tr.discount_percent,
        oc.name as origin_city_name, op.name as origin_province_name,
        dc.name as destination_city_name, dp.name as destination_province_name,
        tt.name as transfer_type_name, tt.icon as transfer_type_icon
      FROM transfer_instances ti
      INNER JOIN transfer_schedules ts ON ti.schedule_id = ts.id
      INNER JOIN transfer_routes tr ON ts.route_id = tr.id
      INNER JOIN cities oc ON tr.origin_city_id = oc.id
      INNER JOIN cities dc ON tr.destination_city_id = dc.id
      LEFT JOIN provinces op ON oc.province_id = op.id
      LEFT JOIN provinces dp ON dc.province_id = dp.id
      INNER JOIN transfer_types tt ON tr.transfer_type_id = tt.id
      WHERE ti.id = ?
    ');
    $stmt->execute([$id]);
    $instance = $stmt->fetch();
    if (!$instance) {
      json_error('Transfer instance not found', 404);
    }
    
    // Calculate price (use instance price if set, otherwise base price)
    $instance['price'] = $instance['price'] ?? $instance['base_price'];
    
    // Get images from route
    $images = get_entity_images($pdo, 'transfer_route', $instance['route_id']);
    $instance['images'] = $images;
    $instance['image_url'] = $images[0] ?? null;
    
    json_ok(['transfer' => $instance]);
  }
  
  // Search for available instances
  $origin_city_id = isset($_GET['origin_city_id']) ? (int)$_GET['origin_city_id'] : null;
  $destination_city_id = isset($_GET['destination_city_id']) ? (int)$_GET['destination_city_id'] : null;
  $transfer_type_id = isset($_GET['transfer_type_id']) ? (int)$_GET['transfer_type_id'] : null;
  $date = $_GET['date'] ?? null;
  $passenger_count = isset($_GET['passenger_count']) ? (int)$_GET['passenger_count'] : 1;
  $page = max(1, (int)($_GET['page'] ?? 1));
  $limit = min(50, max(1, (int)($_GET['limit'] ?? 12)));
  $offset = ($page - 1) * $limit;
  
  if (!$date) {
    json_error('date is required', 400);
  }
  
  $where = ['ti.departure_date >= CAST(? AS DATE)', 'ti.status = ?'];
  $params = [$date, 'scheduled'];
  
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
  
  // Availability check
  $where[] = 'ti.seats_available >= ?';
  $params[] = $passenger_count;
  
  $whereSql = 'WHERE ' . implode(' AND ', $where);
  
  // Get promoted transfers (1-2 random)
  $promotedTransfers = [];
  $promotedIds = [];
  
  try {
    $promotedWhere = array_merge(['tr.ad = 1'], $where);
    $promotedWhereSql = 'WHERE ' . implode(' AND ', $promotedWhere);
    $promotedParams = $params;
    
    $promotedSql = "SELECT TOP 2 ti.*,
        ts.route_id,
        tr.base_price, tr.duration_minutes, tr.capacity,
        tr.ad, tr.discount_percent,
        oc.name as origin_city_name, dc.name as destination_city_name,
        tt.name as transfer_type_name
      FROM transfer_instances ti
      INNER JOIN transfer_schedules ts ON ti.schedule_id = ts.id
      INNER JOIN transfer_routes tr ON ts.route_id = tr.id
      INNER JOIN cities oc ON tr.origin_city_id = oc.id
      INNER JOIN cities dc ON tr.destination_city_id = dc.id
      INNER JOIN transfer_types tt ON tr.transfer_type_id = tt.id
      $promotedWhereSql
      ORDER BY NEWID()";
    
    $promotedStmt = $pdo->prepare($promotedSql);
    $promotedStmt->execute($promotedParams);
    $promotedTransfers = $promotedStmt->fetchAll();
    $promotedIds = array_column($promotedTransfers, 'id');
  } catch (PDOException $e) {
    error_log('Promoted transfers query failed: ' . $e->getMessage());
  }
  
  // Regular transfers (excluding promoted ones)
  $regularWhere = $where;
  $regularWhere[] = "(tr.ad = 0 OR tr.ad IS NULL)";
  if (!empty($promotedIds)) {
    $placeholders = implode(',', array_fill(0, count($promotedIds), '?'));
    $regularWhere[] = "ti.id NOT IN ($placeholders)";
    $regularParams = array_merge($params, $promotedIds);
  } else {
    $regularParams = $params;
  }
  
  $regularWhereSql = 'WHERE ' . implode(' AND ', $regularWhere);
  $offsetInt = (int)$offset;
  $limitInt = (int)$limit;
  
  $sql = "SELECT ti.*,
      ts.route_id,
      tr.base_price, tr.duration_minutes, tr.capacity,
      tr.ad, tr.discount_percent,
      oc.name as origin_city_name, dc.name as destination_city_name,
      tt.name as transfer_type_name
    FROM transfer_instances ti
    INNER JOIN transfer_schedules ts ON ti.schedule_id = ts.id
    INNER JOIN transfer_routes tr ON ts.route_id = tr.id
    INNER JOIN cities oc ON tr.origin_city_id = oc.id
    INNER JOIN cities dc ON tr.destination_city_id = dc.id
    INNER JOIN transfer_types tt ON tr.transfer_type_id = tt.id
    $regularWhereSql
    ORDER BY ti.departure_datetime ASC
    OFFSET $offsetInt ROWS FETCH NEXT $limitInt ROWS ONLY";
  
  $stmt = $pdo->prepare($sql);
  $stmt->execute($regularParams);
  $transfers = $stmt->fetchAll();
  
  // Calculate prices and discounted prices
  foreach ($promotedTransfers as &$transfer) {
    $price = $transfer['price'] ?? $transfer['base_price'];
    $transfer['price'] = $price;
    if ($transfer['discount_percent'] > 0) {
      $transfer['discounted_price'] = round($price * (1 - $transfer['discount_percent'] / 100), 2);
    }
  }
  foreach ($transfers as &$transfer) {
    $price = $transfer['price'] ?? $transfer['base_price'];
    $transfer['price'] = $price;
    if ($transfer['discount_percent'] > 0) {
      $transfer['discounted_price'] = round($price * (1 - $transfer['discount_percent'] / 100), 2);
    }
  }
  
  json_ok(['page' => $page, 'limit' => $limit, 'promoted' => $promotedTransfers, 'results' => $transfers]);
}

// POST /api/transfers/book (book a transfer instance)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && preg_match('#^/transfers/book/?$#', $uri)) {
  requireAuth();
  
  $user = get_authenticated_user();
  $input = json_decode(file_get_contents('php://input'), true);
  
  $instance_id = isset($input['instance_id']) ? (int)$input['instance_id'] : null;
  $passenger_count = isset($input['passenger_count']) ? (int)$input['passenger_count'] : 1;
  
  if (!$instance_id || $passenger_count < 1) {
    json_error('Missing or invalid required fields: instance_id, passenger_count', 400);
  }
  
  // Get instance and check availability
  $stmt = $pdo->prepare('
    SELECT ti.*, tr.base_price, tr.discount_percent, tr.capacity
    FROM transfer_instances ti
    INNER JOIN transfer_schedules ts ON ti.schedule_id = ts.id
    INNER JOIN transfer_routes tr ON ts.route_id = tr.id
    WHERE ti.id = ? AND ti.status = ?
  ');
  $stmt->execute([$instance_id, 'scheduled']);
  $instance = $stmt->fetch();
  
  if (!$instance) {
    json_error('Transfer instance not found or not available', 404);
  }
  
  if ($instance['seats_available'] < $passenger_count) {
    json_error('Not enough seats available', 400);
  }
  
  // Calculate price
  $price = $instance['price'] ?? $instance['base_price'];
  $totalPrice = $price * $passenger_count;
  
  if ($instance['discount_percent'] > 0) {
    $totalPrice = round($totalPrice * (1 - $instance['discount_percent'] / 100), 2);
  }
  
  // Get transfer_id from route
  $stmt = $pdo->prepare('
    SELECT tr.id as transfer_id
    FROM transfer_instances ti
    INNER JOIN transfer_schedules ts ON ti.schedule_id = ts.id
    INNER JOIN transfer_routes tr ON ts.route_id = tr.id
    WHERE ti.id = ?
  ');
  $stmt->execute([$instance_id]);
  $routeInfo = $stmt->fetch();
  $transferId = $routeInfo['transfer_id'] ?? null;
  
  if (!$transferId) {
    json_error('Unable to determine transfer for booking', 500);
  }
  
  // Start transaction
  $pdo->beginTransaction();
  
  try {
    // Create booking in transfer_bookings table
    $stmt = $pdo->prepare('
      INSERT INTO transfer_bookings (user_id, transfer_id, passenger_count, total_price, status)
      VALUES (?, ?, ?, ?, ?)
    ');
    $stmt->execute([
      (int)$user['id'], $transferId,
      $passenger_count, $totalPrice, 'confirmed'
    ]);
    $bookingId = (int)$pdo->lastInsertId();
    
    // Update availability
    $updateStmt = $pdo->prepare('UPDATE transfer_instances SET seats_available = seats_available - ? WHERE id = ?');
    $updateStmt->execute([$passenger_count, $instance_id]);
    
    // If seats_available drops below capacity, reduce vehicles_available
    $updateStmt = $pdo->prepare('
      UPDATE transfer_instances 
      SET vehicles_available = FLOOR(seats_available / ?)
      WHERE id = ? AND seats_available < (vehicles_available * ?)
    ');
    $updateStmt->execute([$instance['capacity'], $instance_id, $instance['capacity']]);
    
    // Update booking count on transfer
    $updateStmt = $pdo->prepare('UPDATE transfers SET booking_count = booking_count + 1 WHERE id = ?');
    $updateStmt->execute([$transferId]);
    
    $pdo->commit();
    
    // Fetch booking with details
    $stmt = $pdo->prepare('SELECT * FROM transfer_bookings WHERE id = ?');
    $stmt->execute([$bookingId]);
    $booking = $stmt->fetch();
    $booking['type'] = 'transfer';
    $booking['item_id'] = $transferId;
    
    json_ok(['booking' => $booking], 201);
  } catch (Exception $e) {
    $pdo->rollBack();
    error_log('Booking failed: ' . $e->getMessage());
    json_error('Booking failed', 500);
  }
}

json_error('Not found', 404);






