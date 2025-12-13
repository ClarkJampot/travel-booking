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
  
  // Get single route or instance
  if ($id) {
    // First, try to get as route
    $stmt = $pdo->prepare('
      SELECT fr.*,
        oa.code as origin_code, oa.name as origin_name,
        da.code as destination_code, da.name as destination_name,
        oc.name as origin_city_name, op.name as origin_province_name,
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
    
    if ($route) {
      // It's a route - return route data
      $route['images'] = [];
      $route['image_url'] = null;
      $route['price'] = $route['base_price_economy'];
      json_ok(['flight' => $route]);
      exit;
    }
    
    // If not a route, try as instance (for backward compatibility)
    $stmt = $pdo->prepare('
      SELECT fi.*,
        fs.route_id, fs.route_pair_id, fs.departure_time, fs.days_of_week,
        fr.origin_airport_id, fr.destination_airport_id, fr.airline,
        fr.base_price_economy, fr.base_price_business, fr.base_price_first,
        fr.duration_minutes, fr.aircraft_type,
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
      json_error('Flight route or instance not found', 404);
    }
    
    // Calculate prices (use instance price if set, otherwise base price)
    $instance['price_economy'] = $instance['price_economy'] ?? $instance['base_price_economy'];
    $instance['price_business'] = $instance['price_business'] ?? $instance['base_price_business'];
    $instance['price_first'] = $instance['price_first'] ?? $instance['base_price_first'];
    
    // No images for flight routes
    $instance['images'] = [];
    $instance['image_url'] = null;
    
    json_ok(['flight' => $instance]);
    exit;
  }
  
  // List flight routes (always return routes, not instances)
  $origin_airport_id = isset($_GET['origin_airport_id']) ? (int)$_GET['origin_airport_id'] : null;
  $destination_airport_id = isset($_GET['destination_airport_id']) ? (int)$_GET['destination_airport_id'] : null;
  $origin_city_id = isset($_GET['origin_city_id']) ? (int)$_GET['origin_city_id'] : null;
  $destination_city_id = isset($_GET['destination_city_id']) ? (int)$_GET['destination_city_id'] : null;
  $min_price = isset($_GET['min_price']) ? (float)$_GET['min_price'] : null;
  $max_price = isset($_GET['max_price']) ? (float)$_GET['max_price'] : null;
  $page = max(1, (int)($_GET['page'] ?? 1));
  $limit = min(50, max(1, (int)($_GET['limit'] ?? 12)));
  $offset = ($page - 1) * $limit;
  
  $where = [];
  $params = [];
  
  if ($origin_airport_id !== null) {
    $where[] = 'fr.origin_airport_id = ?';
    $params[] = $origin_airport_id;
  }
  if ($origin_city_id !== null) {
    $where[] = 'oc.id = ?';
    $params[] = $origin_city_id;
  }
  if ($destination_airport_id !== null) {
    $where[] = 'fr.destination_airport_id = ?';
    $params[] = $destination_airport_id;
  }
  if ($destination_city_id !== null) {
    $where[] = 'dc.id = ?';
    $params[] = $destination_city_id;
  }
  if ($min_price !== null) {
    $where[] = 'fr.base_price_economy >= ?';
    $params[] = $min_price;
  }
  if ($max_price !== null) {
    $where[] = 'fr.base_price_economy <= ?';
    $params[] = $max_price;
  }
  
  $whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';
  $offsetInt = (int)$offset;
  $limitInt = (int)$limit;
  
  // Get promoted routes (with same filters including date)
  $promotedFlights = [];
  $promotedIds = [];
  try {
    $promotedWhere = array_merge(['fr.ad = 1'], $where);
    $promotedWhereSql = 'WHERE ' . implode(' AND ', $promotedWhere);
    $promotedParams = $params;
    
    $promotedSql = "SELECT TOP 2 fr.id, fr.origin_airport_id, fr.destination_airport_id, fr.airline, 
        fr.base_price_economy, fr.base_price_business, fr.base_price_first, 
        fr.duration_minutes, fr.aircraft_type, fr.ad, fr.discount_percent, fr.created_by, fr.created_at,
        oa.code as origin_code, oa.name as origin_name,
        da.code as destination_code, da.name as destination_name,
        oc.name as origin_city_name, dc.name as destination_city_name,
        NULL as image_url
      FROM flight_routes fr
      INNER JOIN airports oa ON fr.origin_airport_id = oa.id
      INNER JOIN airports da ON fr.destination_airport_id = da.id
      LEFT JOIN cities oc ON oa.city_id = oc.id
      LEFT JOIN cities dc ON da.city_id = dc.id
      $promotedWhereSql
      ORDER BY NEWID()";
    
    $promotedStmt = $pdo->prepare($promotedSql);
    $promotedStmt->execute($promotedParams);
    $promotedFlights = $promotedStmt->fetchAll();
    $promotedIds = array_column($promotedFlights, 'id');
  } catch (PDOException $e) {
    error_log('Promoted routes query failed: ' . $e->getMessage());
  }
  
  // Regular routes
  $regularWhere = $where;
  $regularWhere[] = "(fr.ad = 0 OR fr.ad IS NULL)";
  if (!empty($promotedIds)) {
    $placeholders = implode(',', array_fill(0, count($promotedIds), '?'));
    $regularWhere[] = "fr.id NOT IN ($placeholders)";
    $regularParams = array_merge($params, $promotedIds);
  } else {
    $regularParams = $params;
  }
  
  $regularWhereSql = 'WHERE ' . implode(' AND ', $regularWhere);
  
  $sql = "SELECT fr.id, fr.origin_airport_id, fr.destination_airport_id, fr.airline, 
      fr.base_price_economy, fr.base_price_business, fr.base_price_first, 
      fr.duration_minutes, fr.aircraft_type, fr.ad, fr.discount_percent, fr.created_by, fr.created_at,
      oa.code as origin_code, oa.name as origin_name,
      da.code as destination_code, da.name as destination_name,
      oc.name as origin_city_name, dc.name as destination_city_name,
      NULL as image_url
    FROM flight_routes fr
    INNER JOIN airports oa ON fr.origin_airport_id = oa.id
    INNER JOIN airports da ON fr.destination_airport_id = da.id
    LEFT JOIN cities oc ON oa.city_id = oc.id
    LEFT JOIN cities dc ON da.city_id = dc.id
    $regularWhereSql
    ORDER BY fr.id ASC
    OFFSET $offsetInt ROWS FETCH NEXT $limitInt ROWS ONLY";
  
  $stmt = $pdo->prepare($sql);
  $stmt->execute($regularParams);
  $flights = $stmt->fetchAll();
  
  // Format prices for routes
  foreach ($promotedFlights as &$flight) {
    $flight['price'] = $flight['base_price_economy'];
    $flight['departure_date'] = null;
    $flight['departure_datetime'] = null;
    if ($flight['discount_percent'] > 0) {
      $flight['discounted_price'] = round($flight['price'] * (1 - $flight['discount_percent'] / 100), 2);
    }
  }
  foreach ($flights as &$flight) {
    $flight['price'] = $flight['base_price_economy'];
    $flight['departure_date'] = null;
    $flight['departure_datetime'] = null;
    if ($flight['discount_percent'] > 0) {
      $flight['discounted_price'] = round($flight['price'] * (1 - $flight['discount_percent'] / 100), 2);
    }
  }
  
  json_ok(['page' => $page, 'limit' => $limit, 'promoted' => $promotedFlights, 'results' => $flights]);
}

// GET /api/flights/instances (get available instances for a route)
if ($_SERVER['REQUEST_METHOD'] === 'GET' && preg_match('#^/flights/instances/?$#', $uri)) {
  $route_id = isset($_GET['route_id']) ? (int)$_GET['route_id'] : null;
  $departure_date = $_GET['departure_date'] ?? null;
  $return_date = $_GET['return_date'] ?? null;
  $class = $_GET['class'] ?? 'economy';
  $passenger_count = isset($_GET['passenger_count']) ? (int)$_GET['passenger_count'] : 1;
  
  if (!$route_id || !$departure_date) {
    json_error('Missing required parameters: route_id, departure_date', 400);
  }
  
  if (!in_array($class, ['economy', 'business', 'first'])) {
    json_error('Invalid class. Must be economy, business, or first', 400);
  }
  
  if ($passenger_count < 1) {
    json_error('Passenger count must be at least 1', 400);
  }
  
  // Get available instances for the route and date
  $seatsAvailableColumn = 'seats_' . $class . '_available';
  
  $stmt = $pdo->prepare("
    SELECT fi.*,
      fs.departure_time,
      fr.base_price_economy, fr.base_price_business, fr.base_price_first,
      fr.discount_percent,
      fr.airline, fr.duration_minutes
    FROM flight_instances fi
    INNER JOIN flight_schedules fs ON fi.schedule_id = fs.id
    INNER JOIN flight_routes fr ON fs.route_id = fr.id
    WHERE fs.route_id = ?
      AND fi.departure_date = CAST(? AS DATE)
      AND fi.status = 'scheduled'
      AND fi.$seatsAvailableColumn >= ?
    ORDER BY fi.departure_datetime ASC
  ");
  
  $stmt->execute([$route_id, $departure_date, $passenger_count]);
  $instances = $stmt->fetchAll();
  
  // Calculate prices for each instance
  foreach ($instances as &$instance) {
    $basePrice = $instance['base_price_' . $class] ?? $instance['base_price_economy'];
    $price = $instance['price_' . $class] ?? $basePrice;
    $instance['price'] = $price;
    $instance['price_' . $class] = $price;
    
    if ($instance['discount_percent'] > 0) {
      $instance['discounted_price'] = round($price * (1 - $instance['discount_percent'] / 100), 2);
    }
    
    // Add seat availability info
    $instance['seats_available'] = $instance[$seatsAvailableColumn];
    $instance['seats_total'] = $instance['seats_' . $class . '_total'];
  }
  
  $result = ['instances' => $instances];
  
  // If return_date is provided, find return route using flight_route_pairs
  if ($return_date) {
    $returnRouteStmt = $pdo->prepare("
      SELECT frp.return_route_id
      FROM flight_route_pairs frp
      WHERE frp.outbound_route_id = ?
    ");
    $returnRouteStmt->execute([$route_id]);
    $returnRoute = $returnRouteStmt->fetch();
    
    if ($returnRoute && $returnRoute['return_route_id']) {
      $returnRouteId = (int)$returnRoute['return_route_id'];
      
      // Get return instances
      $returnStmt = $pdo->prepare("
        SELECT fi.*,
          fs.departure_time,
          fr.base_price_economy, fr.base_price_business, fr.base_price_first,
          fr.discount_percent,
          fr.airline, fr.duration_minutes
        FROM flight_instances fi
        INNER JOIN flight_schedules fs ON fi.schedule_id = fs.id
        INNER JOIN flight_routes fr ON fs.route_id = fr.id
        WHERE fs.route_id = ?
          AND fi.departure_date = CAST(? AS DATE)
          AND fi.status = 'scheduled'
          AND fi.$seatsAvailableColumn >= ?
        ORDER BY fi.departure_datetime ASC
      ");
      
      $returnStmt->execute([$returnRouteId, $return_date, $passenger_count]);
      $returnInstances = $returnStmt->fetchAll();
      
      // Calculate prices for return instances
      foreach ($returnInstances as &$returnInstance) {
        $basePrice = $returnInstance['base_price_' . $class] ?? $returnInstance['base_price_economy'];
        $price = $returnInstance['price_' . $class] ?? $basePrice;
        $returnInstance['price'] = $price;
        $returnInstance['price_' . $class] = $price;
        
        if ($returnInstance['discount_percent'] > 0) {
          $returnInstance['discounted_price'] = round($price * (1 - $returnInstance['discount_percent'] / 100), 2);
        }
        
        $returnInstance['seats_available'] = $returnInstance[$seatsAvailableColumn];
        $returnInstance['seats_total'] = $returnInstance['seats_' . $class . '_total'];
      }
      
      $result['return_instances'] = $returnInstances;
    } else {
      $result['return_instances'] = [];
    }
  }
  
  json_ok($result);
  exit;
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
  
  // Get flight_id from route - need to find or create a flight record
  // Get route details to find matching flight
  $stmt = $pdo->prepare('
    SELECT oa.name as origin_name, da.name as destination_name, fr.airline, fr.id as route_id
    FROM flight_instances fi
    INNER JOIN flight_schedules fs ON fi.schedule_id = fs.id
    LEFT JOIN flight_routes fr ON fs.route_id = fr.id
    LEFT JOIN airports oa ON fr.origin_airport_id = oa.id
    LEFT JOIN airports da ON fr.destination_airport_id = da.id
    WHERE fi.id = ?
  ');
  $stmt->execute([$instance_id]);
  $routeDetails = $stmt->fetch();
  
  $flightId = null;
  if ($routeDetails) {
    // Try to find existing flight
    $stmt = $pdo->prepare('SELECT id FROM flights WHERE airline = ? AND origin = ? AND destination = ? AND depart_date = ?');
    $stmt->execute([$routeDetails['airline'], $routeDetails['origin_name'], $routeDetails['destination_name'], $instance['departure_date']]);
    $existingFlight = $stmt->fetch();
    
    if ($existingFlight) {
      $flightId = (int)$existingFlight['id'];
    } else {
      // Create a flight record (simplified - in production you'd want more details)
      $stmt = $pdo->prepare('INSERT INTO flights (airline, origin, destination, depart_date, price, created_by) VALUES (?, ?, ?, ?, ?, ?)');
      $stmt->execute([$routeDetails['airline'], $routeDetails['origin_name'], $routeDetails['destination_name'], $instance['departure_date'], $basePrice, (int)$user['id']]);
      $flightId = (int)$pdo->lastInsertId();
    }
  }
  
  if (!$flightId) {
    json_error('Unable to determine flight for booking', 500);
  }
  
  // Start transaction
  $pdo->beginTransaction();
  
  try {
    // Create booking in flight_bookings table
    $passengerDetailsJson = json_encode($passenger_details);
    $stmt = $pdo->prepare('
      INSERT INTO flight_bookings (user_id, flight_id, class, passenger_count, passenger_details, total_price, status)
      VALUES (?, ?, ?, ?, ?, ?, ?)
    ');
    $stmt->execute([
      (int)$user['id'], $flightId, $class,
      $passenger_count, $passengerDetailsJson, $totalPrice, 'confirmed'
    ]);
    $bookingId = (int)$pdo->lastInsertId();
    
    // Update availability
    $updateStmt = $pdo->prepare("UPDATE flight_instances SET $seatsAvailableColumn = $seatsAvailableColumn - ? WHERE id = ?");
    $updateStmt->execute([$passenger_count, $instance_id]);
    
    // Update booking count on flight
    $updateStmt = $pdo->prepare('UPDATE flights SET booking_count = booking_count + 1 WHERE id = ?');
    $updateStmt->execute([$flightId]);
    
    $pdo->commit();
    
    // Fetch booking with details
    $stmt = $pdo->prepare('SELECT * FROM flight_bookings WHERE id = ?');
    $stmt->execute([$bookingId]);
    $booking = $stmt->fetch();
    $booking['type'] = 'flight';
    $booking['item_id'] = $flightId;
    
    json_ok(['booking' => $booking], 201);
  } catch (Exception $e) {
    $pdo->rollBack();
    error_log('Booking failed: ' . $e->getMessage());
    json_error('Booking failed', 500);
  }
}

json_error('Not found', 404);

