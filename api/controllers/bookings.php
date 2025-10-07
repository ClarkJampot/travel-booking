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
  $page = max(1, (int)($_GET['page'] ?? 1));
  $limit = min(50, max(1, (int)($_GET['limit'] ?? 10)));
  $offset = ($page - 1) * $limit;

  $sql = "SELECT * FROM bookings ORDER BY booked_at DESC, id DESC OFFSET $offset ROWS FETCH NEXT $limit ROWS ONLY";
  $stmt = $pdo->prepare($sql);
  $stmt->execute();
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
  requireAuth();

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
