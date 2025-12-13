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
  
  $stmt = $pdo->prepare('SELECT COUNT(*) as count FROM flight_routes WHERE created_by = ? AND deleted_at IS NULL');
  $stmt->execute([$userId]);
  $stats['flights_count'] = (int)$stmt->fetch()['count'];
  
  $stmt = $pdo->prepare('SELECT COUNT(*) as count FROM activities WHERE created_by = ? AND deleted_at IS NULL');
  $stmt->execute([$userId]);
  $stats['activities_count'] = (int)$stmt->fetch()['count'];
  
  $stmt = $pdo->prepare('SELECT COUNT(*) as count FROM transfer_routes WHERE created_by = ? AND deleted_at IS NULL');
  $stmt->execute([$userId]);
  $stats['transfers_count'] = (int)$stmt->fetch()['count'];
  
  $stats['total_items'] = $stats['hotels_count'] + $stats['flights_count'] + $stats['activities_count'] + $stats['transfers_count'];
  
  // Get bookings for user's items
  foreach (['hotel', 'flight', 'activity', 'transfer'] as $type) {
    try {
      if ($type === 'hotel') {
        $stmt = $pdo->prepare("
          SELECT b.*, 'hotel' as type, b.hotel_id as item_id
          FROM hotel_bookings b
          INNER JOIN hotels i ON b.hotel_id = i.id
          WHERE i.created_by = ? AND i.deleted_at IS NULL
        ");
        $stmt->execute([$userId]);
      } else if ($type === 'flight') {
        $stmt = $pdo->prepare("
          SELECT DISTINCT b.*, 'flight' as type, b.instance_id as item_id
          FROM flight_bookings b
          INNER JOIN flight_instances fi ON b.instance_id = fi.id
          INNER JOIN flight_schedules fs ON fi.schedule_id = fs.id
          LEFT JOIN flight_routes fr ON fs.route_id = fr.id
          LEFT JOIN flight_route_pairs frp ON fs.route_pair_id = frp.id
          LEFT JOIN flight_routes outbound ON frp.outbound_route_id = outbound.id
          LEFT JOIN flight_routes return_route ON frp.return_route_id = return_route.id
          WHERE (fr.created_by = ? OR outbound.created_by = ? OR return_route.created_by = ?)
            AND (fr.deleted_at IS NULL OR outbound.deleted_at IS NULL OR return_route.deleted_at IS NULL)
        ");
        $stmt->execute([$userId, $userId, $userId]);
      } else if ($type === 'transfer') {
        $stmt = $pdo->prepare("
          SELECT b.*, 'transfer' as type, b.instance_id as item_id
          FROM transfer_bookings b
          INNER JOIN transfer_instances ti ON b.instance_id = ti.id
          INNER JOIN transfer_schedules ts ON ti.schedule_id = ts.id
          INNER JOIN transfer_routes tr ON ts.route_id = tr.id
          WHERE tr.created_by = ? AND tr.deleted_at IS NULL
        ");
        $stmt->execute([$userId]);
      } else {
        $stmt = $pdo->prepare("
          SELECT b.*, 'activity' as type, b.activity_id as item_id
          FROM activity_bookings b
          INNER JOIN activities i ON b.activity_id = i.id
          WHERE i.created_by = ? AND i.deleted_at IS NULL
        ");
        $stmt->execute([$userId]);
      }
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
  
  $types = $itemType ? [$itemType] : ['hotel', 'flight', 'activity', 'transfer'];
  $allBookings = [];
  
  foreach ($types as $type) {
    try {
      $where = [];
      $params = [];
      $offsetInt = (int)$offset;
      $limitInt = (int)$limit;
      
      if ($type === 'hotel') {
        $where[] = "i.created_by = ?";
        $params[] = $userId;
        if ($status) {
          $where[] = 'b.status = ?';
          $params[] = $status;
        }
        $whereSql = 'WHERE ' . implode(' AND ', $where);
        $sql = "
          SELECT b.*, 'hotel' as type, b.hotel_id as item_id, i.name as item_name
          FROM hotel_bookings b
          INNER JOIN hotels i ON b.hotel_id = i.id
          $whereSql AND i.deleted_at IS NULL
          ORDER BY b.booked_at DESC, b.id DESC
          OFFSET $offsetInt ROWS FETCH NEXT $limitInt ROWS ONLY
        ";
      } else if ($type === 'flight') {
        $where[] = "(fr.created_by = ? OR outbound.created_by = ? OR return_route.created_by = ?)";
        $params[] = $userId;
        $params[] = $userId;
        $params[] = $userId;
        if ($status) {
          $where[] = 'b.status = ?';
          $params[] = $status;
        }
        $whereSql = 'WHERE ' . implode(' AND ', $where);
        $sql = "
          SELECT DISTINCT b.*, 'flight' as type, b.instance_id as item_id, fr.airline as item_name
          FROM flight_bookings b
          INNER JOIN flight_instances fi ON b.instance_id = fi.id
          INNER JOIN flight_schedules fs ON fi.schedule_id = fs.id
          LEFT JOIN flight_routes fr ON fs.route_id = fr.id
          LEFT JOIN flight_route_pairs frp ON fs.route_pair_id = frp.id
          LEFT JOIN flight_routes outbound ON frp.outbound_route_id = outbound.id
          LEFT JOIN flight_routes return_route ON frp.return_route_id = return_route.id
          $whereSql AND (fr.deleted_at IS NULL OR outbound.deleted_at IS NULL OR return_route.deleted_at IS NULL)
          ORDER BY b.booked_at DESC, b.id DESC
          OFFSET $offsetInt ROWS FETCH NEXT $limitInt ROWS ONLY
        ";
      } else if ($type === 'transfer') {
        $where[] = "tr.created_by = ?";
        $params[] = $userId;
        if ($status) {
          $where[] = 'b.status = ?';
          $params[] = $status;
        }
        $whereSql = 'WHERE ' . implode(' AND ', $where);
        $sql = "
          SELECT b.*, 'transfer' as type, b.instance_id as item_id, tt.name as item_name
          FROM transfer_bookings b
          INNER JOIN transfer_instances ti ON b.instance_id = ti.id
          INNER JOIN transfer_schedules ts ON ti.schedule_id = ts.id
          INNER JOIN transfer_routes tr ON ts.route_id = tr.id
          INNER JOIN transfer_types tt ON tr.transfer_type_id = tt.id
          $whereSql AND tr.deleted_at IS NULL
          ORDER BY b.booked_at DESC, b.id DESC
          OFFSET $offsetInt ROWS FETCH NEXT $limitInt ROWS ONLY
        ";
      } else {
        $where[] = "i.created_by = ?";
        $params[] = $userId;
        if ($status) {
          $where[] = 'b.status = ?';
          $params[] = $status;
        }
        $whereSql = 'WHERE ' . implode(' AND ', $where);
        $sql = "
          SELECT b.*, 'activity' as type, b.activity_id as item_id, i.title as item_name
          FROM activity_bookings b
          INNER JOIN activities i ON b.activity_id = i.id
          $whereSql AND i.deleted_at IS NULL
          ORDER BY b.booked_at DESC, b.id DESC
          OFFSET $offsetInt ROWS FETCH NEXT $limitInt ROWS ONLY
        ";
      }
      
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
    if ($type === 'hotel') {
      $stmt = $pdo->prepare("SELECT * FROM hotels WHERE created_by = ? AND deleted_at IS NULL ORDER BY id DESC");
      $stmt->execute([$userId]);
      $result['hotels'] = $stmt->fetchAll();
    } else if ($type === 'flight') {
      $stmt = $pdo->prepare("SELECT * FROM flight_routes WHERE created_by = ? AND deleted_at IS NULL ORDER BY id DESC");
      $stmt->execute([$userId]);
      $result['flights'] = $stmt->fetchAll();
    } else if ($type === 'activity') {
      $stmt = $pdo->prepare("SELECT * FROM activities WHERE created_by = ? AND deleted_at IS NULL ORDER BY id DESC");
      $stmt->execute([$userId]);
      $result['activities'] = $stmt->fetchAll();
    } else if ($type === 'transfer') {
      $stmt = $pdo->prepare("SELECT * FROM transfer_routes WHERE created_by = ? AND deleted_at IS NULL ORDER BY id DESC");
      $stmt->execute([$userId]);
      $result['transfers'] = $stmt->fetchAll();
    }
  }
  
  json_ok($result);
}

json_error('Not found', 404);

