<?php
// Bookings controller
declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../middleware/auth.php';
require_once __DIR__ . '/../helpers/Router.php';

try {
  $pdo = db_pdo();
} catch (Throwable $e) {
  json_error('Database connection failed', 500);
}

if (!isset($uri)) {
  $uri = Router::parseUri();
}

/**
 * Get booking table name for entity type
 */
function getBookingTable(string $type): string {
  $tables = [
    'hotel' => 'hotel_bookings',
    'flight' => 'flight_bookings',
    'activity' => 'activity_bookings',
    'transfer' => 'transfer_bookings'
  ];
  return $tables[$type] ?? '';
}

/**
 * Get item ID column name for booking table
 */
function getItemIdColumn(string $type): string {
  $columns = [
    'hotel' => 'hotel_id',
    'flight' => 'instance_id',
    'activity' => 'activity_id',
    'transfer' => 'instance_id'
  ];
  return $columns[$type] ?? '';
}

/**
 * Get entity table name for entity type (for booking_count updates)
 * Note: Only hotels and activities have booking_count columns
 */
function getEntityTable(string $type): string {
  $tables = [
    'hotel' => 'hotels',
    'activity' => 'activities'
    // Note: flights and transfers don't have booking_count columns
  ];
  return $tables[$type] ?? '';
}

/**
 * Get item details for a booking
 */
function getItemDetails(PDO $pdo, string $type, int $itemId): ?array {
  switch ($type) {
    case 'hotel':
      $stmt = $pdo->prepare('SELECT h.id, h.name, c.name as city_name, p.name as province_name 
        FROM hotels h 
        LEFT JOIN cities c ON h.city_id = c.id 
        LEFT JOIN provinces p ON h.province_id = p.id 
        WHERE h.id = ?');
      $stmt->execute([$itemId]);
      return $stmt->fetch() ?: null;
      
    case 'flight':
      // Get flight instance with route info
      $stmt = $pdo->prepare('
        SELECT fi.id, fi.departure_datetime, fi.departure_date, fr.id as route_id, fr.airline,
          oa.code as origin, oa.name as origin_name,
          da.code as destination, da.name as destination_name,
          oc.name as origin_city_name, op.name as origin_province_name,
          dc.name as destination_city_name, pd.name as destination_province_name
        FROM flight_instances fi
        INNER JOIN flight_schedules fs ON fi.schedule_id = fs.id
        INNER JOIN flight_routes fr ON fs.route_id = fr.id
        INNER JOIN airports oa ON fr.origin_airport_id = oa.id
        INNER JOIN airports da ON fr.destination_airport_id = da.id
        LEFT JOIN cities oc ON oa.city_id = oc.id
        LEFT JOIN provinces op ON oc.province_id = op.id
        LEFT JOIN cities dc ON da.city_id = dc.id
        LEFT JOIN provinces pd ON dc.province_id = pd.id
        WHERE fi.id = ?
      ');
      $stmt->execute([$itemId]);
      $flight = $stmt->fetch();
      
      if (!$flight) {
        return null;
      }
      
      // Check if this route is part of a route pair (roundtrip)
      $pairStmt = $pdo->prepare('
        SELECT frp.id as route_pair_id, frp.outbound_route_id, frp.return_route_id
        FROM flight_route_pairs frp
        WHERE frp.outbound_route_id = ? OR frp.return_route_id = ?
      ');
      $pairStmt->execute([$flight['route_id'], $flight['route_id']]);
      $pair = $pairStmt->fetch();
      
      if ($pair) {
        // Determine if current route is outbound or return
        $isOutbound = ($flight['route_id'] == $pair['outbound_route_id']);
        $otherRouteId = $isOutbound ? $pair['return_route_id'] : $pair['outbound_route_id'];
        
        // Get other leg details
        $otherStmt = $pdo->prepare('
          SELECT 
            other_fr.airline,
            other_oa.code as return_origin, other_oa.name as return_origin_name,
            other_da.code as return_destination, other_da.name as return_destination_name,
            other_oc.name as return_origin_city_name, other_op.name as return_origin_province_name,
            other_dc.name as return_destination_city_name, other_dp.name as return_destination_province_name
          FROM flight_routes other_fr
          LEFT JOIN airports other_oa ON other_fr.origin_airport_id = other_oa.id
          LEFT JOIN airports other_da ON other_fr.destination_airport_id = other_da.id
          LEFT JOIN cities other_oc ON other_oa.city_id = other_oc.id
          LEFT JOIN provinces other_op ON other_oc.province_id = other_op.id
          LEFT JOIN cities other_dc ON other_da.city_id = other_dc.id
          LEFT JOIN provinces other_dp ON other_dc.province_id = other_dp.id
          WHERE other_fr.id = ?
        ');
        $otherStmt->execute([$otherRouteId]);
        $otherDetails = $otherStmt->fetch();
        
        if ($otherDetails) {
          $flight['route_pair_id'] = $pair['route_pair_id'];
          $flight['is_roundtrip'] = true;
          $flight['return_origin'] = $otherDetails['return_origin'];
          $flight['return_origin_name'] = $otherDetails['return_origin_name'];
          $flight['return_destination'] = $otherDetails['return_destination'];
          $flight['return_destination_name'] = $otherDetails['return_destination_name'];
          $flight['return_origin_city_name'] = $otherDetails['return_origin_city_name'];
          $flight['return_origin_province_name'] = $otherDetails['return_origin_province_name'];
          $flight['return_destination_city_name'] = $otherDetails['return_destination_city_name'];
          $flight['return_destination_province_name'] = $otherDetails['return_destination_province_name'];
        }
      }
      
      // Ensure route_id is preserved for finding related bookings
      if (!isset($flight['route_id']) && isset($flight['id'])) {
        // Re-fetch route_id if missing
        $routeStmt = $pdo->prepare('
          SELECT fs.route_id
          FROM flight_instances fi
          INNER JOIN flight_schedules fs ON fi.schedule_id = fs.id
          WHERE fi.id = ?
        ');
        $routeStmt->execute([$flight['id']]);
        $routeInfo = $routeStmt->fetch();
        if ($routeInfo) {
          $flight['route_id'] = $routeInfo['route_id'];
        }
      }
      
      return $flight;
      
    case 'activity':
      $stmt = $pdo->prepare('SELECT a.id, a.title, c.name as city_name, p.name as province_name
        FROM activities a
        LEFT JOIN cities c ON a.city_id = c.id
        LEFT JOIN provinces p ON c.province_id = p.id
        WHERE a.id = ?');
      $stmt->execute([$itemId]);
      return $stmt->fetch() ?: null;
      
    case 'transfer':
      $stmt = $pdo->prepare('
        SELECT ti.id, tr.origin_specific as origin, tr.destination_specific as destination,
          oc.name as origin_city_name, op.name as origin_province_name,
          dc.name as destination_city_name, pd.name as destination_province_name
        FROM transfer_instances ti
        INNER JOIN transfer_schedules ts ON ti.schedule_id = ts.id
        INNER JOIN transfer_routes tr ON ts.route_id = tr.id
        LEFT JOIN cities oc ON tr.origin_city_id = oc.id
        LEFT JOIN provinces op ON oc.province_id = op.id
        LEFT JOIN cities dc ON tr.destination_city_id = dc.id
        LEFT JOIN provinces pd ON dc.province_id = pd.id
        WHERE ti.id = ?
      ');
      $stmt->execute([$itemId]);
      return $stmt->fetch() ?: null;
      
    default:
      return null;
  }
}

/**
 * Find booking by ID across all tables
 */
function findBookingById(PDO $pdo, int $bookingId, int $userId): ?array {
  $types = ['hotel', 'flight', 'activity', 'transfer'];
  
  foreach ($types as $type) {
    $table = getBookingTable($type);
    $stmt = $pdo->prepare("SELECT *, '$type' as type FROM $table WHERE id = ? AND user_id = ?");
    $stmt->execute([$bookingId, $userId]);
    $booking = $stmt->fetch();
    
    if ($booking) {
      $itemIdColumn = getItemIdColumn($type);
      $itemId = (int)$booking[$itemIdColumn];
      $booking['item_id'] = $itemId;
      $booking['item_details'] = getItemDetails($pdo, $type, $itemId);
      return $booking;
    }
  }
  
  return null;
}

/**
 * Find booking by ID and verify item ownership for agency/owner
 */
function findBookingByIdForOwner(PDO $pdo, int $bookingId, int $ownerId): ?array {
  $types = ['hotel', 'flight', 'activity', 'transfer'];
  
  foreach ($types as $type) {
    $table = getBookingTable($type);
    $itemIdColumn = getItemIdColumn($type);
    
    if ($type === 'hotel') {
      $stmt = $pdo->prepare("
        SELECT b.*, 'hotel' as type
        FROM $table b
        INNER JOIN hotels i ON b.$itemIdColumn = i.id
        WHERE b.id = ? AND i.created_by = ? AND i.deleted_at IS NULL
      ");
      $stmt->execute([$bookingId, $ownerId]);
    } else if ($type === 'flight') {
      $stmt = $pdo->prepare("
        SELECT DISTINCT b.*, 'flight' as type
        FROM $table b
        INNER JOIN flight_instances fi ON b.$itemIdColumn = fi.id
        INNER JOIN flight_schedules fs ON fi.schedule_id = fs.id
        LEFT JOIN flight_routes fr ON fs.route_id = fr.id
        LEFT JOIN flight_route_pairs frp ON fs.route_pair_id = frp.id
        LEFT JOIN flight_routes outbound ON frp.outbound_route_id = outbound.id
        LEFT JOIN flight_routes return_route ON frp.return_route_id = return_route.id
        WHERE b.id = ? 
          AND (fr.created_by = ? OR outbound.created_by = ? OR return_route.created_by = ?)
          AND (fr.deleted_at IS NULL OR outbound.deleted_at IS NULL OR return_route.deleted_at IS NULL)
      ");
      $stmt->execute([$bookingId, $ownerId, $ownerId, $ownerId]);
    } else if ($type === 'transfer') {
      $stmt = $pdo->prepare("
        SELECT b.*, 'transfer' as type
        FROM $table b
        INNER JOIN transfer_instances ti ON b.$itemIdColumn = ti.id
        INNER JOIN transfer_schedules ts ON ti.schedule_id = ts.id
        INNER JOIN transfer_routes tr ON ts.route_id = tr.id
        WHERE b.id = ? AND tr.created_by = ? AND tr.deleted_at IS NULL
      ");
      $stmt->execute([$bookingId, $ownerId]);
    } else {
      $stmt = $pdo->prepare("
        SELECT b.*, 'activity' as type
        FROM $table b
        INNER JOIN activities i ON b.$itemIdColumn = i.id
        WHERE b.id = ? AND i.created_by = ? AND i.deleted_at IS NULL
      ");
      $stmt->execute([$bookingId, $ownerId]);
    }
    
    $booking = $stmt->fetch();
    
    if ($booking) {
      $itemId = (int)$booking[$itemIdColumn];
      $booking['item_id'] = $itemId;
      $booking['item_details'] = getItemDetails($pdo, $type, $itemId);
      return $booking;
    }
  }
  
  return null;
}

// GET /api/bookings
if ($_SERVER['REQUEST_METHOD'] === 'GET' && preg_match('#^/bookings/?$#', $uri)) {
  requireAuth();
  
  $user = get_authenticated_user();
  $id = isset($_GET['id']) ? (int)$_GET['id'] : null;
  
  // Get single booking
  if ($id) {
    $booking = findBookingById($pdo, $id, (int)$user['id']);
    if (!$booking) {
      json_error('Booking not found', 404);
    }
    json_ok(['booking' => $booking]);
  }
  
  // List user's bookings - query all tables and merge
  $itemType = $_GET['item_type'] ?? null;
  $status = $_GET['status'] ?? null;
  $page = max(1, (int)($_GET['page'] ?? 1));
  $limit = min(50, max(1, (int)($_GET['limit'] ?? 10)));
  $offset = ($page - 1) * $limit;
  
  $types = ['hotel', 'flight', 'activity', 'transfer'];
  if ($itemType && in_array($itemType, $types)) {
    $types = [$itemType];
  }
  
  $allBookings = [];
  $userId = (int)$user['id'];
  $processedRoundtripIds = []; // Track which bookings have been included as related bookings
  
  foreach ($types as $type) {
    $table = getBookingTable($type);
    $itemIdColumn = getItemIdColumn($type);
    
    $where = ['user_id = ?'];
    $params = [$userId];
    
    if ($status) {
      $statusNormalized = trim(strtolower($status));
      $where[] = 'LOWER(status) = ?';
      $params[] = $statusNormalized;
    }
    
    $whereSql = 'WHERE ' . implode(' AND ', $where);
    $sql = "SELECT *, '$type' as type, $itemIdColumn as item_id FROM $table $whereSql ORDER BY booked_at DESC, id DESC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $bookings = $stmt->fetchAll();
    
    foreach ($bookings as $booking) {
      // Skip if this booking was already included as a related booking
      if (in_array($booking['id'], $processedRoundtripIds)) {
        continue;
      }
      
      $booking['item_id'] = (int)$booking['item_id'];
      $booking['item_details'] = getItemDetails($pdo, $type, $booking['item_id']);
      
      // For roundtrip flights, find the related booking (departure/return)
      if ($type === 'flight' && isset($booking['item_details']['route_pair_id']) && $booking['item_details']['route_pair_id']) {
        // Find the related booking by checking if the route is part of the same route pair
        $currentRouteId = $booking['item_details']['route_id'] ?? null;
        $routePairId = (int)$booking['item_details']['route_pair_id'];
        
        if ($currentRouteId) {
          // Get the other route ID from the pair
          $pairStmt = $pdo->prepare('
            SELECT 
              CASE WHEN outbound_route_id = ? THEN return_route_id ELSE outbound_route_id END as other_route_id
            FROM flight_route_pairs
            WHERE id = ? AND (outbound_route_id = ? OR return_route_id = ?)
          ');
          $pairStmt->execute([$currentRouteId, $routePairId, $currentRouteId, $currentRouteId]);
          $pairInfo = $pairStmt->fetch();
          $otherRouteId = $pairInfo['other_route_id'] ?? null;
          
          if ($otherRouteId) {
            // Find the related booking (other leg of the roundtrip) - booked within 10 seconds
            $relatedBookingStmt = $pdo->prepare("
              SELECT fb.*, 'flight' as type, fb.instance_id as item_id, fi.departure_date, fi.departure_datetime
              FROM flight_bookings fb
              INNER JOIN flight_instances fi ON fb.instance_id = fi.id
              INNER JOIN flight_schedules fs ON fi.schedule_id = fs.id
              WHERE fb.user_id = ? 
                AND fb.id != ?
                AND fs.route_id = ?
                AND ABS(DATEDIFF(SECOND, fb.booked_at, ?)) < 10
                AND fb.status = ?
            ");
            $bookedAt = $booking['booked_at'];
            $status = $booking['status'];
            $relatedBookingStmt->execute([
              $userId,
              $booking['id'],
              $otherRouteId,
              $bookedAt,
              $status
            ]);
            $relatedBooking = $relatedBookingStmt->fetch();
            if ($relatedBooking) {
              $relatedBooking['item_id'] = (int)$relatedBooking['item_id'];
              $relatedBooking['item_details'] = getItemDetails($pdo, 'flight', $relatedBooking['item_id']);
              $booking['related_booking'] = $relatedBooking;
              $booking['item_details']['is_roundtrip'] = true;
              
              // Determine which is departure and which is return based on dates
              $currentDate = $booking['item_details']['departure_date'] ?? null;
              $relatedDate = $relatedBooking['departure_date'] ?? $relatedBooking['item_details']['departure_date'] ?? null;
              
              if ($currentDate && $relatedDate) {
                // The earlier date is departure, later is return
                if (strtotime($currentDate) <= strtotime($relatedDate)) {
                  $booking['item_details']['departure_date'] = $currentDate;
                  $booking['item_details']['return_date'] = $relatedDate;
                } else {
                  $booking['item_details']['departure_date'] = $relatedDate;
                  $booking['item_details']['return_date'] = $currentDate;
                }
              } else if ($currentDate) {
                $booking['item_details']['departure_date'] = $currentDate;
              } else if ($relatedDate) {
                $booking['item_details']['departure_date'] = $relatedDate;
              }
              
              // Mark the related booking as processed so it won't appear separately
              $processedRoundtripIds[] = (int)$relatedBooking['id'];
            }
          }
        }
      }
      
      $allBookings[] = $booking;
    }
  }
  
  // Sort all bookings by booked_at DESC
  usort($allBookings, function($a, $b) {
    return strtotime($b['booked_at']) - strtotime($a['booked_at']);
  });
  
  // Apply pagination
  $total = count($allBookings);
  $paginatedBookings = array_slice($allBookings, $offset, $limit);
  
  json_ok([
    'page' => $page,
    'limit' => $limit,
    'total' => $total,
    'results' => $paginatedBookings
  ]);
}

// POST /api/bookings
if ($_SERVER['REQUEST_METHOD'] === 'POST' && preg_match('#^/bookings/?$#', $uri)) {
  requireCustomer();
  
  $user = get_authenticated_user();
  $input = json_decode(file_get_contents('php://input'), true);
  
  $itemType = trim($input['item_type'] ?? '');
  $itemId = isset($input['item_id']) ? (int)$input['item_id'] : 0;
  $totalPrice = isset($input['total_price']) ? (float)$input['total_price'] : 0;
  
  if (!$itemType || !$itemId || !$totalPrice) {
    json_error('Missing required fields: item_type, item_id, total_price', 400);
  }
  
  if (!in_array($itemType, ['hotel', 'flight', 'activity', 'transfer'])) {
    json_error('Invalid item_type', 400);
  }
  
  $userId = (int)$user['id'];
  $table = getBookingTable($itemType);
  $itemIdColumn = getItemIdColumn($itemType);
  
  // Verify item exists
  $itemDetails = getItemDetails($pdo, $itemType, $itemId);
  if (!$itemDetails) {
    json_error(ucfirst($itemType) . ' not found', 404);
  }
  
  // Create booking based on type
  try {
    if ($itemType === 'hotel') {
      $checkIn = $input['check_in'] ?? null;
      $checkOut = $input['check_out'] ?? null;
      $guests = isset($input['guests']) ? (int)$input['guests'] : 1;
      
      if (!$checkIn || !$checkOut) {
        json_error('Missing required fields: check_in, check_out', 400);
      }
      
      $stmt = $pdo->prepare("INSERT INTO $table (user_id, $itemIdColumn, check_in, check_out, guests, total_price, status) VALUES (?, ?, ?, ?, ?, ?, 'confirmed')");
      $stmt->execute([$userId, $itemId, $checkIn, $checkOut, $guests, $totalPrice]);
      
    } elseif ($itemType === 'flight') {
      $class = $input['class'] ?? null;
      $passengerCount = isset($input['passenger_count']) ? (int)$input['passenger_count'] : 1;
      
      $stmt = $pdo->prepare("INSERT INTO $table (user_id, $itemIdColumn, class, passenger_count, total_price, status) VALUES (?, ?, ?, ?, ?, 'confirmed')");
      $stmt->execute([$userId, $itemId, $class, $passengerCount, $totalPrice]);
      
    } elseif ($itemType === 'activity') {
      $participantCount = isset($input['participant_count']) ? (int)$input['participant_count'] : 1;
      $date = $input['date'] ?? null;
      
      if (!$date) {
        json_error('Missing required field: date', 400);
      }
      
      $stmt = $pdo->prepare("INSERT INTO $table (user_id, $itemIdColumn, date, participant_count, total_price, status) VALUES (?, ?, ?, ?, ?, 'confirmed')");
      $stmt->execute([$userId, $itemId, $date, $participantCount, $totalPrice]);
      
    } elseif ($itemType === 'transfer') {
      $passengerCount = isset($input['passenger_count']) ? (int)$input['passenger_count'] : 1;
      
      $stmt = $pdo->prepare("INSERT INTO $table (user_id, $itemIdColumn, passenger_count, total_price, status) VALUES (?, ?, ?, ?, 'confirmed')");
      $stmt->execute([$userId, $itemId, $passengerCount, $totalPrice]);
    }
    
    $bookingId = (int)$pdo->lastInsertId();
    
    // Update booking count (ignore if column doesn't exist)
    // Note: Only hotels and activities have booking_count columns
    try {
      $entityTable = getEntityTable($itemType);
      if ($entityTable) {
        $stmt = $pdo->prepare("UPDATE $entityTable SET booking_count = booking_count + 1 WHERE id = ?");
        $stmt->execute([$itemId]);
      }
    } catch (PDOException $e) {
      // booking_count column might not exist, log but don't fail
      error_log('Failed to update booking_count: ' . $e->getMessage());
    }
    
    // Get created booking
    $booking = findBookingById($pdo, $bookingId, $userId);
    
    if (!$booking) {
      json_error('Failed to retrieve created booking', 500);
    }
    
    json_ok(['booking' => $booking], 201);
  } catch (PDOException $e) {
    error_log('Booking creation failed: ' . $e->getMessage());
    json_error('Failed to create booking: ' . $e->getMessage(), 500);
  } catch (Exception $e) {
    error_log('Booking creation error: ' . $e->getMessage());
    json_error('Failed to create booking', 500);
  }
}

// PUT /api/bookings/:id
if ($_SERVER['REQUEST_METHOD'] === 'PUT' && preg_match('#^/bookings/(\d+)/?$#', $uri, $matches)) {
  requireAuth();
  
  $id = (int)$matches[1];
  $user = get_authenticated_user();
  $userRole = $user['role'] ?? 'customer';
  $userId = (int)$user['id'];
  $input = json_decode(file_get_contents('php://input'), true);
  
  // Find booking - for agency/owner, check item ownership; for customers, check booking ownership
  $booking = null;
  if ($userRole === 'owner' || $userRole === 'agency') {
    $booking = findBookingByIdForOwner($pdo, $id, $userId);
  } else {
    $booking = findBookingById($pdo, $id, $userId);
  }
  
  if (!$booking) {
    json_error('Booking not found', 404);
  }
  
  $type = $booking['type'];
  $table = getBookingTable($type);
  $itemIdColumn = getItemIdColumn($type);
  $itemId = (int)$booking[$itemIdColumn];
  
  // Update status
  if (isset($input['status'])) {
    $status = trim($input['status']);
    if (!in_array($status, ['confirmed', 'cancelled', 'completed'])) {
      json_error('Invalid status', 400);
    }
    
    $stmt = $pdo->prepare("UPDATE $table SET status = ?, updated_at = SYSUTCDATETIME()" . ($status === 'cancelled' ? ", cancelled_at = SYSUTCDATETIME()" : "") . " WHERE id = ?");
    $stmt->execute([$status, $id]);
    
    // If cancelled, decrease booking count
    // Note: Only hotels and activities have booking_count columns
    if ($status === 'cancelled' && $booking['status'] !== 'cancelled') {
      $entityTable = getEntityTable($type);
      if ($entityTable) {
        $stmt = $pdo->prepare("UPDATE $entityTable SET booking_count = booking_count - 1 WHERE id = ?");
        $stmt->execute([$itemId]);
      }
    }
  }
  
  // Get updated booking
  if ($userRole === 'owner' || $userRole === 'agency') {
    $updatedBooking = findBookingByIdForOwner($pdo, $id, $userId);
  } else {
    $updatedBooking = findBookingById($pdo, $id, $userId);
  }
  
  json_ok(['booking' => $updatedBooking]);
}

// DELETE /api/bookings/:id (cancel booking)
if ($_SERVER['REQUEST_METHOD'] === 'DELETE' && preg_match('#^/bookings/(\d+)/?$#', $uri, $matches)) {
  requireAuth();
  
  $id = (int)$matches[1];
  $user = get_authenticated_user();
  
  // Find booking
  $booking = findBookingById($pdo, $id, (int)$user['id']);
  if (!$booking) {
    json_error('Booking not found', 404);
  }
  
  $type = $booking['type'];
  $table = getBookingTable($type);
  $itemIdColumn = getItemIdColumn($type);
  $itemId = (int)$booking[$itemIdColumn];
  
  // Update status to cancelled
  $stmt = $pdo->prepare("UPDATE $table SET status = 'cancelled', cancelled_at = SYSUTCDATETIME(), updated_at = SYSUTCDATETIME() WHERE id = ?");
  $stmt->execute([$id]);
  
  // Decrease booking count if not already cancelled
  // Note: Only hotels and activities have booking_count columns
  if ($booking['status'] !== 'cancelled') {
    $entityTable = getEntityTable($type);
    if ($entityTable) {
      $stmt = $pdo->prepare("UPDATE $entityTable SET booking_count = booking_count - 1 WHERE id = ?");
      $stmt->execute([$itemId]);
    }
  }
  
  json_ok(['message' => 'Booking cancelled successfully']);
}

json_error('Not found', 404);
