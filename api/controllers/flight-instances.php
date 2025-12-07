<?php
// Flight Instances controller
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

// GET /api/flights (search for available instances)
if ($_SERVER['REQUEST_METHOD'] === 'GET' && preg_match('#^/flights/?$#', $uri)) {
  $id = isset($_GET['id']) ? (int)$_GET['id'] : null;
  
  // Get single instance
  if ($id) {
    $stmt = $pdo->prepare('
      SELECT fi.*,
        fs.route_id, fs.route_pair_id, fs.departure_time, fs.days_of_week,
        fr.origin_airport_id, fr.destination_airport_id, fr.airline,
        fr.base_price_economy, fr.base_price_business, fr.base_price_first,
        fr.duration_minutes, fr.aircraft_type, fr.description as route_description,
        fr.ad, fr.discount_percent,
        oa.code as origin_code, oa.name as origin_name,
        da.code as destination_code, da.name as destination_name,
        oc.name as origin_city_name, op.name as origin_province_name,
        dc.name as destination_city_name, dp.name as destination_province_name
      FROM flight_instances fi
      INNER JOIN flight_schedules fs ON fi.schedule_id = fs.id
      LEFT JOIN flight_routes fr ON fs.route_id = fr.id
      LEFT JOIN flight_route_pairs frp ON fs.route_pair_id = frp.id
      LEFT JOIN flight_routes outbound ON frp.outbound_route_id = outbound.id
      LEFT JOIN flight_routes return_route ON frp.return_route_id = return_route.id
      LEFT JOIN airports oa ON COALESCE(fr.origin_airport_id, outbound.origin_airport_id) = oa.id
      LEFT JOIN airports da ON COALESCE(fr.destination_airport_id, outbound.destination_airport_id) = da.id
      LEFT JOIN cities oc ON oa.city_id = oc.id
      LEFT JOIN provinces op ON oc.province_id = op.id
      LEFT JOIN cities dc ON da.city_id = dc.id
      LEFT JOIN provinces dp ON dc.province_id = dp.id
      WHERE fi.id = ?
    ');
    $stmt->execute([$id]);
    $instance = $stmt->fetch();
    if (!$instance) {
      json_error('Flight instance not found', 404);
    }
    
    // Calculate prices (use instance price if set, otherwise base price)
    $instance['price_economy'] = $instance['price_economy'] ?? $instance['base_price_economy'];
    $instance['price_business'] = $instance['price_business'] ?? $instance['base_price_business'];
    $instance['price_first'] = $instance['price_first'] ?? $instance['base_price_first'];
    
    // Get images from route
    if ($instance['route_id']) {
      $images = get_entity_images($pdo, 'flight_route', $instance['route_id']);
      $instance['images'] = $images;
      $instance['image_url'] = $images[0] ?? null;
    }
    
    json_ok(['flight' => $instance]);
  }
  
  // Search for available instances
  $origin_airport_id = isset($_GET['origin_airport_id']) ? (int)$_GET['origin_airport_id'] : null;
  $destination_airport_id = isset($_GET['destination_airport_id']) ? (int)$_GET['destination_airport_id'] : null;
  $departure_date = $_GET['departure_date'] ?? null;
  $return_date = $_GET['return_date'] ?? null;
  $class = $_GET['class'] ?? 'economy';
  $trip_type = $_GET['trip_type'] ?? 'one-way';
  $passenger_count = isset($_GET['passenger_count']) ? (int)$_GET['passenger_count'] : 1;
  $page = max(1, (int)($_GET['page'] ?? 1));
  $limit = min(50, max(1, (int)($_GET['limit'] ?? 12)));
  $offset = ($page - 1) * $limit;
  
  if (!$departure_date) {
    json_error('departure_date is required', 400);
  }
  
  $where = ['fi.departure_date >= CAST(? AS DATE)', 'fi.status = ?'];
  $params = [$departure_date, 'scheduled'];
  
  // For round-trip, we need to handle both outbound and return
  if ($trip_type === 'round-trip' && $return_date) {
    // This is simplified - in production, you'd want to match outbound/return pairs
    $where[] = 'fs.route_pair_id IS NOT NULL';
  } else {
    $where[] = 'fs.route_id IS NOT NULL';
  }
  
  if ($origin_airport_id !== null) {
    $where[] = 'COALESCE(fr.origin_airport_id, outbound.origin_airport_id) = ?';
    $params[] = $origin_airport_id;
  }
  if ($destination_airport_id !== null) {
    $where[] = 'COALESCE(fr.destination_airport_id, outbound.destination_airport_id) = ?';
    $params[] = $destination_airport_id;
  }
  
  // Availability check based on class
  $seatsAvailableColumn = 'seats_' . $class . '_available';
  $where[] = "fi.$seatsAvailableColumn >= ?";
  $params[] = $passenger_count;
  
  $whereSql = 'WHERE ' . implode(' AND ', $where);
  
  // Get promoted flights (1-2 random)
  $promotedFlights = [];
  $promotedIds = [];
  
  try {
    $promotedWhere = array_merge(['fr.ad = 1'], $where);
    $promotedWhereSql = 'WHERE ' . implode(' AND ', $promotedWhere);
    $promotedParams = $params;
    
    $promotedSql = "SELECT TOP 2 fi.*,
        fs.route_id, fs.route_pair_id,
        fr.airline, fr.duration_minutes,
        fr.base_price_economy, fr.base_price_business, fr.base_price_first,
        fr.ad, fr.discount_percent,
        oa.code as origin_code, oa.name as origin_name,
        da.code as destination_code, da.name as destination_name,
        oc.name as origin_city_name, dc.name as destination_city_name
      FROM flight_instances fi
      INNER JOIN flight_schedules fs ON fi.schedule_id = fs.id
      LEFT JOIN flight_routes fr ON fs.route_id = fr.id
      LEFT JOIN flight_route_pairs frp ON fs.route_pair_id = frp.id
      LEFT JOIN flight_routes outbound ON frp.outbound_route_id = outbound.id
      LEFT JOIN airports oa ON COALESCE(fr.origin_airport_id, outbound.origin_airport_id) = oa.id
      LEFT JOIN airports da ON COALESCE(fr.destination_airport_id, outbound.destination_airport_id) = da.id
      LEFT JOIN cities oc ON oa.city_id = oc.id
      LEFT JOIN cities dc ON da.city_id = dc.id
      $promotedWhereSql
      ORDER BY NEWID()";
    
    $promotedStmt = $pdo->prepare($promotedSql);
    $promotedStmt->execute($promotedParams);
    $promotedFlights = $promotedStmt->fetchAll();
    $promotedIds = array_column($promotedFlights, 'id');
  } catch (PDOException $e) {
    error_log('Promoted flights query failed: ' . $e->getMessage());
  }
  
  // Regular flights (excluding promoted ones)
  $regularWhere = $where;
  $regularWhere[] = "(fr.ad = 0 OR fr.ad IS NULL)";
  if (!empty($promotedIds)) {
    $placeholders = implode(',', array_fill(0, count($promotedIds), '?'));
    $regularWhere[] = "fi.id NOT IN ($placeholders)";
    $regularParams = array_merge($params, $promotedIds);
  } else {
    $regularParams = $params;
  }
  
  $regularWhereSql = 'WHERE ' . implode(' AND ', $regularWhere);
  $offsetInt = (int)$offset;
  $limitInt = (int)$limit;
  
  $sql = "SELECT fi.*,
      fs.route_id, fs.route_pair_id,
      fr.airline, fr.duration_minutes,
      fr.base_price_economy, fr.base_price_business, fr.base_price_first,
      fr.ad, fr.discount_percent,
      oa.code as origin_code, oa.name as origin_name,
      da.code as destination_code, da.name as destination_name,
      oc.name as origin_city_name, dc.name as destination_city_name
    FROM flight_instances fi
    INNER JOIN flight_schedules fs ON fi.schedule_id = fs.id
    LEFT JOIN flight_routes fr ON fs.route_id = fr.id
    LEFT JOIN flight_route_pairs frp ON fs.route_pair_id = frp.id
    LEFT JOIN flight_routes outbound ON frp.outbound_route_id = outbound.id
    LEFT JOIN airports oa ON COALESCE(fr.origin_airport_id, outbound.origin_airport_id) = oa.id
    LEFT JOIN airports da ON COALESCE(fr.destination_airport_id, outbound.destination_airport_id) = da.id
    LEFT JOIN cities oc ON oa.city_id = oc.id
    LEFT JOIN cities dc ON da.city_id = dc.id
    $regularWhereSql
    ORDER BY fi.departure_datetime ASC
    OFFSET $offsetInt ROWS FETCH NEXT $limitInt ROWS ONLY";
  
  $stmt = $pdo->prepare($sql);
  $stmt->execute($regularParams);
  $flights = $stmt->fetchAll();
  
  // Calculate prices and discounted prices
  foreach ($promotedFlights as &$flight) {
    $basePrice = $flight['base_price_' . $class] ?? $flight['base_price_economy'];
    $price = $flight['price_' . $class] ?? $basePrice;
    $flight['price'] = $price;
    if ($flight['discount_percent'] > 0) {
      $flight['discounted_price'] = round($price * (1 - $flight['discount_percent'] / 100), 2);
    }
  }
  foreach ($flights as &$flight) {
    $basePrice = $flight['base_price_' . $class] ?? $flight['base_price_economy'];
    $price = $flight['price_' . $class] ?? $basePrice;
    $flight['price'] = $price;
    if ($flight['discount_percent'] > 0) {
      $flight['discounted_price'] = round($price * (1 - $flight['discount_percent'] / 100), 2);
    }
  }
  
  json_ok(['page' => $page, 'limit' => $limit, 'promoted' => $promotedFlights, 'results' => $flights]);
}

// POST /api/flights/book (book a flight instance)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && preg_match('#^/flights/book/?$#', $uri)) {
  requireAuth();
  
  $user = get_authenticated_user();
  $input = json_decode(file_get_contents('php://input'), true);
  
  $instance_id = isset($input['instance_id']) ? (int)$input['instance_id'] : null;
  $class = trim($input['class'] ?? 'economy');
  $passenger_count = isset($input['passenger_count']) ? (int)$input['passenger_count'] : 1;
  $passenger_details = $input['passenger_details'] ?? [];
  
  if (!$instance_id || !in_array($class, ['economy', 'business', 'first']) || $passenger_count < 1) {
    json_error('Missing or invalid required fields: instance_id, class, passenger_count', 400);
  }
  
  // Get instance and check availability
  $stmt = $pdo->prepare('
    SELECT fi.*, fr.base_price_economy, fr.base_price_business, fr.base_price_first, fr.discount_percent
    FROM flight_instances fi
    INNER JOIN flight_schedules fs ON fi.schedule_id = fs.id
    LEFT JOIN flight_routes fr ON fs.route_id = fr.id
    WHERE fi.id = ? AND fi.status = ?
  ');
  $stmt->execute([$instance_id, 'scheduled']);
  $instance = $stmt->fetch();
  
  if (!$instance) {
    json_error('Flight instance not found or not available', 404);
  }
  
  $seatsAvailableColumn = 'seats_' . $class . '_available';
  $seatsTotalColumn = 'seats_' . $class . '_total';
  
  if ($instance[$seatsAvailableColumn] < $passenger_count) {
    json_error('Not enough seats available', 400);
  }
  
  // Calculate price
  $basePrice = $instance['base_price_' . $class] ?? $instance['base_price_economy'];
  $price = $instance['price_' . $class] ?? $basePrice;
  $totalPrice = $price * $passenger_count;
  
  if ($instance['discount_percent'] > 0) {
    $totalPrice = round($totalPrice * (1 - $instance['discount_percent'] / 100), 2);
  }
  
  // Start transaction
  $pdo->beginTransaction();
  
  try {
    // Create booking
    $passengerDetailsJson = json_encode($passenger_details);
    $stmt = $pdo->prepare('
      INSERT INTO bookings (user_id, item_type, item_id, flight_instance_id, class, passenger_count, passenger_details, total_price, status)
      VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ');
    $stmt->execute([
      $user['id'], 'flight', $instance_id, $instance_id, $class,
      $passenger_count, $passengerDetailsJson, $totalPrice, 'confirmed'
    ]);
    $bookingId = (int)$pdo->lastInsertId();
    
    // Update availability
    $updateStmt = $pdo->prepare("UPDATE flight_instances SET $seatsAvailableColumn = $seatsAvailableColumn - ? WHERE id = ?");
    $updateStmt->execute([$passenger_count, $instance_id]);
    
    $pdo->commit();
    
    // Fetch booking with details
    $stmt = $pdo->prepare('SELECT * FROM bookings WHERE id = ?');
    $stmt->execute([$bookingId]);
    $booking = $stmt->fetch();
    
    json_ok(['booking' => $booking], 201);
  } catch (Exception $e) {
    $pdo->rollBack();
    error_log('Booking failed: ' . $e->getMessage());
    json_error('Booking failed', 500);
  }
}

json_error('Not found', 404);

