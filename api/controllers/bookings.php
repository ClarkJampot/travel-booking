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

// GET /api/bookings
if ($_SERVER['REQUEST_METHOD'] === 'GET' && preg_match('#^/bookings/?$#', $uri)) {
  requireAuth();
  
  $user = get_authenticated_user();
  $id = isset($_GET['id']) ? (int)$_GET['id'] : null;
  
  // Get single booking
  if ($id) {
    $stmt = $pdo->prepare('SELECT * FROM bookings WHERE id = ? AND user_id = ?');
    $stmt->execute([$id, $user['id']]);
    $booking = $stmt->fetch();
    if (!$booking) {
      json_error('Booking not found', 404);
    }
    
    // Get item details
    $itemDetails = null;
    if ($booking['item_type'] === 'hotel') {
      $stmt = $pdo->prepare('SELECT h.name, c.name as city_name, p.name as province_name 
        FROM hotels h 
        LEFT JOIN cities c ON h.city_id = c.id 
        LEFT JOIN provinces p ON h.province_id = p.id 
        WHERE h.id = ?');
      $stmt->execute([$booking['item_id']]);
      $itemDetails = $stmt->fetch();
    } elseif ($booking['item_type'] === 'flight') {
      $stmt = $pdo->prepare('SELECT airline, origin, destination FROM flights WHERE id = ?');
      $stmt->execute([$booking['item_id']]);
      $itemDetails = $stmt->fetch();
    } elseif ($booking['item_type'] === 'activity') {
      $stmt = $pdo->prepare('SELECT title, city FROM activities WHERE id = ?');
      $stmt->execute([$booking['item_id']]);
      $itemDetails = $stmt->fetch();
    } elseif ($booking['item_type'] === 'transfer') {
      $stmt = $pdo->prepare('SELECT service, origin, destination FROM transfers WHERE id = ?');
      $stmt->execute([$booking['item_id']]);
      $itemDetails = $stmt->fetch();
    }
    
    $booking['item_details'] = $itemDetails;
    json_ok(['booking' => $booking]);
  }
  
  // List user's bookings
  $itemType = $_GET['item_type'] ?? null;
  $status = $_GET['status'] ?? null;
  $page = max(1, (int)($_GET['page'] ?? 1));
  $limit = min(50, max(1, (int)($_GET['limit'] ?? 10)));
  $offset = ($page - 1) * $limit;
  
  $where = ['user_id = ?'];
  $params = [$user['id']];
  
  if ($itemType) {
    $where[] = 'item_type = ?';
    $params[] = $itemType;
  }
  if ($status) {
    $where[] = 'status = ?';
    $params[] = $status;
  }
  
  $whereSql = 'WHERE ' . implode(' AND ', $where);
  // SQL Server OFFSET/FETCH syntax - must use integers directly, not parameters
  $offsetInt = (int)$offset;
  $limitInt = (int)$limit;
  $sql = "SELECT * FROM bookings $whereSql ORDER BY booked_at DESC, id DESC OFFSET $offsetInt ROWS FETCH NEXT $limitInt ROWS ONLY";
  
  $stmt = $pdo->prepare($sql);
  $stmt->execute($params);
  $bookings = $stmt->fetchAll();
  
  json_ok(['page' => $page, 'limit' => $limit, 'results' => $bookings]);
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
  
  // Verify item exists and get price
  $itemPrice = 0;
  if ($itemType === 'hotel') {
    $stmt = $pdo->prepare('SELECT price_per_night FROM hotels WHERE id = ?');
    $stmt->execute([$itemId]);
    $item = $stmt->fetch();
    if (!$item) {
      json_error('Hotel not found', 404);
    }
    $itemPrice = (float)$item['price_per_night'];
  } elseif ($itemType === 'flight') {
    $stmt = $pdo->prepare('SELECT price FROM flights WHERE id = ?');
    $stmt->execute([$itemId]);
    $item = $stmt->fetch();
    if (!$item) {
      json_error('Flight not found', 404);
    }
    $itemPrice = (float)$item['price'];
  } elseif ($itemType === 'activity') {
    $stmt = $pdo->prepare('SELECT price FROM activities WHERE id = ?');
    $stmt->execute([$itemId]);
    $item = $stmt->fetch();
    if (!$item) {
      json_error('Activity not found', 404);
    }
    $itemPrice = (float)$item['price'];
  } elseif ($itemType === 'transfer') {
    $stmt = $pdo->prepare('SELECT price FROM transfers WHERE id = ?');
    $stmt->execute([$itemId]);
    $item = $stmt->fetch();
    if (!$item) {
      json_error('Transfer not found', 404);
    }
    $itemPrice = (float)$item['price'];
  }
  
  // Create booking
  $stmt = $pdo->prepare('INSERT INTO bookings (user_id, item_type, item_id, total_price, status) VALUES (?, ?, ?, ?, ?)');
  $stmt->execute([$user['id'], $itemType, $itemId, $totalPrice, 'confirmed']);
  $bookingId = (int)$pdo->lastInsertId();
  
  // Update booking count
  $table = $itemType . 's';
  $stmt = $pdo->prepare("UPDATE $table SET booking_count = booking_count + 1 WHERE id = ?");
  $stmt->execute([$itemId]);
  
  $stmt = $pdo->prepare('SELECT * FROM bookings WHERE id = ?');
  $stmt->execute([$bookingId]);
  $booking = $stmt->fetch();
  
  json_ok(['booking' => $booking], 201);
}

// PUT /api/bookings/:id
if ($_SERVER['REQUEST_METHOD'] === 'PUT' && preg_match('#^/bookings/(\d+)/?$#', $uri, $matches)) {
  requireAuth();
  
  $id = (int)$matches[1];
  $user = get_authenticated_user();
  $input = json_decode(file_get_contents('php://input'), true);
  
  // Check if booking exists and belongs to user
  $stmt = $pdo->prepare('SELECT * FROM bookings WHERE id = ? AND user_id = ?');
  $stmt->execute([$id, $user['id']]);
  $booking = $stmt->fetch();
  if (!$booking) {
    json_error('Booking not found', 404);
  }
  
  // Update status
  if (isset($input['status'])) {
    $status = trim($input['status']);
    if (!in_array($status, ['confirmed', 'cancelled', 'completed'])) {
      json_error('Invalid status', 400);
    }
    
    $stmt = $pdo->prepare('UPDATE bookings SET status = ? WHERE id = ?');
    $stmt->execute([$status, $id]);
    
    // If cancelled, decrease booking count
    if ($status === 'cancelled' && $booking['status'] !== 'cancelled') {
      $table = $booking['item_type'] . 's';
      $stmt = $pdo->prepare("UPDATE $table SET booking_count = booking_count - 1 WHERE id = ?");
      $stmt->execute([$booking['item_id']]);
    }
  }
  
  $stmt = $pdo->prepare('SELECT * FROM bookings WHERE id = ?');
  $stmt->execute([$id]);
  $booking = $stmt->fetch();
  
  json_ok(['booking' => $booking]);
}

// DELETE /api/bookings/:id (cancel booking)
if ($_SERVER['REQUEST_METHOD'] === 'DELETE' && preg_match('#^/bookings/(\d+)/?$#', $uri, $matches)) {
  requireAuth();
  
  $id = (int)$matches[1];
  $user = get_authenticated_user();
  
  // Check if booking exists and belongs to user
  $stmt = $pdo->prepare('SELECT * FROM bookings WHERE id = ? AND user_id = ?');
  $stmt->execute([$id, $user['id']]);
  $booking = $stmt->fetch();
  if (!$booking) {
    json_error('Booking not found', 404);
  }
  
  // Update status to cancelled
  $stmt = $pdo->prepare('UPDATE bookings SET status = ? WHERE id = ?');
  $stmt->execute(['cancelled', $id]);
  
  // Decrease booking count
  if ($booking['status'] !== 'cancelled') {
    $table = $booking['item_type'] . 's';
    $stmt = $pdo->prepare("UPDATE $table SET booking_count = booking_count - 1 WHERE id = ?");
    $stmt->execute([$booking['item_id']]);
  }
  
  json_ok(['message' => 'Booking cancelled successfully']);
}

json_error('Not found', 404);
