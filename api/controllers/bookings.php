<?php
declare(strict_types=1);
require_once __DIR__ . '/../bootstrap.php';

// Load DB
if (!file_exists(__DIR__ . '/../db.php')) {
  json_error('Missing api/db.php', 500);
  exit;
}
require_once __DIR__ . '/../db.php';

try {
  $pdo = db_pdo();
} catch (Throwable $e) {
  json_error('DB connection failed: ' . $e->getMessage(), 500);
  exit;
}

// GET /api/bookings
if ($_SERVER['REQUEST_METHOD'] === 'GET' && preg_match('#/api/bookings/?$#', $_SERVER['REQUEST_URI'])) {
  require_once __DIR__ . '/../middleware/auth.php';
  requireOnboarding();
  
  $page = max(1, (int)($_GET['page'] ?? 1));
  $limit = min(50, max(1, (int)($_GET['limit'] ?? 10)));
  $offset = ($page - 1) * $limit;

  // Filter by current user
  $userId = $_SESSION['user']['id'];
  $sql = "SELECT b.*, 
    CASE 
      WHEN b.item_type = 'hotel' THEN h.name
      WHEN b.item_type = 'flight' THEN f.airline
      WHEN b.item_type = 'activity' THEN a.title
      WHEN b.item_type = 'transfer' THEN t.service
    END as item_name,
    CASE 
      WHEN b.item_type = 'hotel' THEN CONCAT(h.city, ', ', h.country)
      WHEN b.item_type = 'flight' THEN CONCAT(f.origin, ' → ', f.destination)
      WHEN b.item_type = 'activity' THEN a.city
      WHEN b.item_type = 'transfer' THEN CONCAT(t.origin, ' → ', t.destination)
    END as item_description
    FROM bookings b
    LEFT JOIN hotels h ON b.item_type = 'hotel' AND b.item_id = h.id
    LEFT JOIN flights f ON b.item_type = 'flight' AND b.item_id = f.id
    LEFT JOIN activities a ON b.item_type = 'activity' AND b.item_id = a.id
    LEFT JOIN transfers t ON b.item_type = 'transfer' AND b.item_id = t.id
    WHERE b.user_id = ?
    ORDER BY b.booked_at DESC, b.id DESC 
    OFFSET $offset ROWS FETCH NEXT $limit ROWS ONLY";
  
  $stmt = $pdo->prepare($sql);
  $stmt->execute([$userId]);
  $rows = $stmt->fetchAll();
  json_ok(['page' => $page, 'limit' => $limit, 'results' => $rows]);
  exit;
}

// GET /api/bookings/:id
if ($_SERVER['REQUEST_METHOD'] === 'GET' && preg_match('#/api/bookings/(\d+)/?$#', $_SERVER['REQUEST_URI'], $matches)) {
  $id = (int)$matches[1];
  $stmt = $pdo->prepare('SELECT * FROM bookings WHERE id = ?');
  $stmt->execute([$id]);
  $booking = $stmt->fetch();
  if (!$booking) {
    json_error('Booking not found', 404);
    exit;
  }
  json_ok(['booking' => $booking]);
  exit;
}

// POST /api/bookings
if ($_SERVER['REQUEST_METHOD'] === 'POST' && preg_match('#/api/bookings/?$#', $_SERVER['REQUEST_URI'])) {
  require_once __DIR__ . '/../middleware/auth.php';
  requireOnboarding();

  $input = json_decode(file_get_contents('php://input'), true);
  $itemType = $input['itemType'] ?? '';
  $itemId = isset($input['itemId']) ? (int)$input['itemId'] : 0;
  $totalPrice = isset($input['totalPrice']) ? (float)$input['totalPrice'] : 0;

  if (!$itemType || !$itemId || !$totalPrice) {
    json_error('Missing required fields: itemType, itemId, totalPrice', 400);
    exit;
  }

  $userId = $_SESSION['user']['id'];
  $stmt = $pdo->prepare('INSERT INTO bookings (user_id, item_type, item_id, total_price) VALUES (?, ?, ?, ?)');
  $stmt->execute([$userId, $itemType, $itemId, $totalPrice]);
  $bookingId = $pdo->lastInsertId();

  json_ok(['id' => $bookingId, 'message' => 'Booking created'], 201);
  exit;
}

json_error('Not found', 404);
