<?php
// Bookings controller
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
    'flight' => 'flight_id',
    'activity' => 'activity_id',
    'transfer' => 'transfer_id'
  ];
  return $columns[$type] ?? '';
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
      $stmt = $pdo->prepare('SELECT f.id, f.airline, f.origin, f.destination, 
        co.name as origin_city_name, po.name as origin_province_name,
        cd.name as destination_city_name, pd.name as destination_province_name
        FROM flights f
        LEFT JOIN cities co ON f.origin_city_id = co.id
        LEFT JOIN provinces po ON co.province_id = po.id
        LEFT JOIN cities cd ON f.destination_city_id = cd.id
        LEFT JOIN provinces pd ON cd.province_id = pd.id
        WHERE f.id = ?');
      $stmt->execute([$itemId]);
      return $stmt->fetch() ?: null;
      
    case 'activity':
      $stmt = $pdo->prepare('SELECT a.id, a.title, c.name as city_name, p.name as province_name
        FROM activities a
        LEFT JOIN cities c ON a.city_id = c.id
        LEFT JOIN provinces p ON c.province_id = p.id
        WHERE a.id = ?');
      $stmt->execute([$itemId]);
      return $stmt->fetch() ?: null;
      
    case 'transfer':
      $stmt = $pdo->prepare('SELECT t.id, t.service, t.origin, t.destination,
        co.name as origin_city_name, po.name as origin_province_name,
        cd.name as destination_city_name, pd.name as destination_province_name
        FROM transfers t
        LEFT JOIN cities co ON t.origin_city_id = co.id
        LEFT JOIN provinces po ON co.province_id = po.id
        LEFT JOIN cities cd ON t.destination_city_id = cd.id
        LEFT JOIN provinces pd ON cd.province_id = pd.id
        WHERE t.id = ?');
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
      $booking['item_id'] = (int)$booking['item_id'];
      $booking['item_details'] = getItemDetails($pdo, $type, $booking['item_id']);
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
  requireAuth();
  
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
    try {
      $entityTable = $itemType . 's';
      $stmt = $pdo->prepare("UPDATE $entityTable SET booking_count = booking_count + 1 WHERE id = ?");
      $stmt->execute([$itemId]);
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
  $input = json_decode(file_get_contents('php://input'), true);
  
  // Find booking
  $booking = findBookingById($pdo, $id, (int)$user['id']);
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
    if ($status === 'cancelled' && $booking['status'] !== 'cancelled') {
      $entityTable = $type . 's';
      $stmt = $pdo->prepare("UPDATE $entityTable SET booking_count = booking_count - 1 WHERE id = ?");
      $stmt->execute([$itemId]);
    }
  }
  
  // Get updated booking
  $updatedBooking = findBookingById($pdo, $id, (int)$user['id']);
  
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
  if ($booking['status'] !== 'cancelled') {
    $entityTable = $type . 's';
    $stmt = $pdo->prepare("UPDATE $entityTable SET booking_count = booking_count - 1 WHERE id = ?");
    $stmt->execute([$itemId]);
  }
  
  json_ok(['message' => 'Booking cancelled successfully']);
}

json_error('Not found', 404);
