<?php
// Dashboard controller for owners/agencies
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

// GET /api/dashboard
if ($_SERVER['REQUEST_METHOD'] === 'GET' && preg_match('#^/dashboard/?$#', $uri)) {
  requireAuth();
  requireRole(['owner', 'agency']);
  
  $user = get_authenticated_user();
  $userId = (int)$user['id'];
  
  // Get overview stats
  $stats = [
    'total_bookings' => 0,
    'total_revenue' => 0,
    'confirmed_bookings' => 0,
    'cancelled_bookings' => 0,
    'completed_bookings' => 0,
    'total_items' => 0,
    'hotels_count' => 0,
    'flights_count' => 0,
    'activities_count' => 0,
    'transfers_count' => 0
  ];
  
  // Count items created by user
  $stmt = $pdo->prepare('SELECT COUNT(*) as count FROM hotels WHERE created_by = ?');
  $stmt->execute([$userId]);
  $stats['hotels_count'] = (int)$stmt->fetch()['count'];
  
  $stmt = $pdo->prepare('SELECT COUNT(*) as count FROM flights WHERE created_by = ?');
  $stmt->execute([$userId]);
  $stats['flights_count'] = (int)$stmt->fetch()['count'];
  
  $stmt = $pdo->prepare('SELECT COUNT(*) as count FROM activities WHERE created_by = ?');
  $stmt->execute([$userId]);
  $stats['activities_count'] = (int)$stmt->fetch()['count'];
  
  $stmt = $pdo->prepare('SELECT COUNT(*) as count FROM transfers WHERE created_by = ?');
  $stmt->execute([$userId]);
  $stats['transfers_count'] = (int)$stmt->fetch()['count'];
  
  $stats['total_items'] = $stats['hotels_count'] + $stats['flights_count'] + $stats['activities_count'] + $stats['transfers_count'];
  
  // Get bookings for user's items
  $bookingTables = [
    'hotel' => ['table' => 'hotel_bookings', 'item_column' => 'hotel_id', 'item_table' => 'hotels'],
    'flight' => ['table' => 'flight_bookings', 'item_column' => 'flight_id', 'item_table' => 'flights'],
    'activity' => ['table' => 'activity_bookings', 'item_column' => 'activity_id', 'item_table' => 'activities'],
    'transfer' => ['table' => 'transfer_bookings', 'item_column' => 'transfer_id', 'item_table' => 'transfers']
  ];
  
  foreach ($bookingTables as $type => $config) {
    try {
      $stmt = $pdo->prepare("
        SELECT b.*, '$type' as type, b.{$config['item_column']} as item_id
        FROM {$config['table']} b
        INNER JOIN {$config['item_table']} i ON b.{$config['item_column']} = i.id
        WHERE i.created_by = ?
      ");
      $stmt->execute([$userId]);
      $bookings = $stmt->fetchAll();
      
      foreach ($bookings as $booking) {
        $stats['total_bookings']++;
        $stats['total_revenue'] += (float)$booking['total_price'];
        
        if ($booking['status'] === 'confirmed') {
          $stats['confirmed_bookings']++;
        } elseif ($booking['status'] === 'cancelled') {
          $stats['cancelled_bookings']++;
        } elseif ($booking['status'] === 'completed') {
          $stats['completed_bookings']++;
        }
      }
    } catch (Exception $e) {
      error_log("Dashboard stats error for $type: " . $e->getMessage());
    }
  }
  
  json_ok(['stats' => $stats]);
}

// GET /api/dashboard/bookings
if ($_SERVER['REQUEST_METHOD'] === 'GET' && preg_match('#^/dashboard/bookings/?$#', $uri)) {
  requireAuth();
  requireRole(['owner', 'agency']);
  
  $user = get_authenticated_user();
  $userId = (int)$user['id'];
  
  $itemType = $_GET['item_type'] ?? null;
  $status = $_GET['status'] ?? null;
  $page = max(1, (int)($_GET['page'] ?? 1));
  $limit = min(50, max(1, (int)($_GET['limit'] ?? 20)));
  $offset = ($page - 1) * $limit;
  
  $bookingTables = [
    'hotel' => ['table' => 'hotel_bookings', 'item_column' => 'hotel_id', 'item_table' => 'hotels'],
    'flight' => ['table' => 'flight_bookings', 'item_column' => 'flight_id', 'item_table' => 'flights'],
    'activity' => ['table' => 'activity_bookings', 'item_column' => 'activity_id', 'item_table' => 'activities'],
    'transfer' => ['table' => 'transfer_bookings', 'item_column' => 'transfer_id', 'item_table' => 'transfers']
  ];
  
  $types = $itemType ? [$itemType] : array_keys($bookingTables);
  $allBookings = [];
  
  foreach ($types as $type) {
    if (!isset($bookingTables[$type])) continue;
    
    try {
      $config = $bookingTables[$type];
      $nameField = $type === 'activity' ? 'title' : ($type === 'flight' ? 'airline' : ($type === 'transfer' ? 'service' : 'name'));
      
      $where = ["i.created_by = ?"];
      $params = [$userId];
      
      if ($status) {
        $where[] = 'b.status = ?';
        $params[] = $status;
      }
      
      $whereSql = 'WHERE ' . implode(' AND ', $where);
      $offsetInt = (int)$offset;
      $limitInt = (int)$limit;
      
      $sql = "
        SELECT b.*, '$type' as type, b.{$config['item_column']} as item_id, i.$nameField as item_name
        FROM {$config['table']} b
        INNER JOIN {$config['item_table']} i ON b.{$config['item_column']} = i.id
        $whereSql
        ORDER BY b.booked_at DESC, b.id DESC
        OFFSET $offsetInt ROWS FETCH NEXT $limitInt ROWS ONLY
      ";
      
      $stmt = $pdo->prepare($sql);
      $stmt->execute($params);
      $bookings = $stmt->fetchAll();
      
      foreach ($bookings as $booking) {
        $booking['item_id'] = (int)$booking['item_id'];
        $allBookings[] = $booking;
      }
    } catch (Exception $e) {
      error_log("Dashboard bookings error for $type: " . $e->getMessage());
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

// GET /api/dashboard/items
if ($_SERVER['REQUEST_METHOD'] === 'GET' && preg_match('#^/dashboard/items/?$#', $uri)) {
  requireAuth();
  requireRole(['owner', 'agency']);
  
  $user = get_authenticated_user();
  $userId = (int)$user['id'];
  
  $itemType = $_GET['item_type'] ?? null;
  
  $result = [
    'hotels' => [],
    'flights' => [],
    'activities' => [],
    'transfers' => []
  ];
  
  $types = $itemType ? [$itemType] : ['hotel', 'flight', 'activity', 'transfer'];
  
  foreach ($types as $type) {
    $table = $type . 's';
    $stmt = $pdo->prepare("SELECT * FROM $table WHERE created_by = ? ORDER BY id DESC");
    $stmt->execute([$userId]);
    $items = $stmt->fetchAll();
    $result[$table] = $items;
  }
  
  json_ok($result);
}

json_error('Not found', 404);

