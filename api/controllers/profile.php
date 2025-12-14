<?php
// Profile controller
declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../middleware/auth.php';

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
      $stmt = $pdo->prepare('
        SELECT ti.id, tr.origin_specific as origin, tr.destination_specific as destination,
          oc.name as origin_city_name, op.name as origin_province_name,
          dc.name as destination_city_name, dp.name as destination_province_name
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

try {
  $pdo = db_pdo();
} catch (Throwable $e) {
  json_error('Database connection failed', 500);
}

$uri = $GLOBALS['API_URI'] ?? $_SERVER['REQUEST_URI'];

// GET /api/profile (uses authenticated user only)
if ($_SERVER['REQUEST_METHOD'] === 'GET' && preg_match('#^/profile/?$#', $uri)) {
  requireAuth();
  
  $user = get_authenticated_user();
  $userId = (int)$user['id'];
  
  // Get user information
  $stmt = $pdo->prepare('SELECT u.id, u.email, u.first_name, u.last_name, r.name as role_name 
    FROM users u 
    LEFT JOIN roles r ON u.role_id = r.id 
    WHERE u.id = ?');
  $stmt->execute([$userId]);
  $userInfo = $stmt->fetch();
  
  if (!$userInfo) {
    json_error('User not found', 404);
  }
  
  // Get bookings summary
  $userRole = $userInfo['role_name'] ?? 'customer';
  $isOwnerOrAgency = $userRole === 'owner' || $userRole === 'agency';
  
  $bookingsSummary = [
    'total' => 0,
    'confirmed' => 0,
    'cancelled' => 0,
    'completed' => 0,
    'total_spent' => 0,
    'total_revenue' => 0,
    'recent' => []
  ];
  
  try {
    $types = ['hotel', 'flight', 'activity', 'transfer'];
    $bookingTables = [
      'hotel' => 'hotel_bookings',
      'flight' => 'flight_bookings',
      'activity' => 'activity_bookings',
      'transfer' => 'transfer_bookings'
    ];
    $entityTables = [
      'hotel' => 'hotels',
      'flight' => 'flight_routes',
      'activity' => 'activities',
      'transfer' => 'transfer_routes'
    ];
    $itemIdColumns = [
      'hotel' => 'hotel_id',
      'flight' => 'instance_id',
      'activity' => 'activity_id',
      'transfer' => 'instance_id'
    ];
    
    if ($isOwnerOrAgency) {
      // For owners/agencies: Calculate revenue from bookings on their content
      foreach ($types as $type) {
        $bookingTable = $bookingTables[$type];
        $entityTable = $entityTables[$type];
        $itemIdColumn = $itemIdColumns[$type];
        
        // Join bookings with entities where created_by = userId
        if ($type === 'flight') {
          // For flights: Join through instances → schedules → routes
          $stmt = $pdo->prepare("
            SELECT DISTINCT b.status, b.total_price 
            FROM $bookingTable b
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
          // For transfers: Join through instances → schedules → routes
          $stmt = $pdo->prepare("
            SELECT b.status, b.total_price 
            FROM $bookingTable b
            INNER JOIN transfer_instances ti ON b.instance_id = ti.id
            INNER JOIN transfer_schedules ts ON ti.schedule_id = ts.id
            INNER JOIN transfer_routes tr ON ts.route_id = tr.id
            WHERE tr.created_by = ? AND tr.deleted_at IS NULL
          ");
          $stmt->execute([$userId]);
        } else {
          // For hotels and activities: Direct join
          $deletedFilter = "";
          try {
            $testStmt = $pdo->query("SELECT TOP 1 deleted_at FROM $entityTable");
            $testStmt->fetch();
            $deletedFilter = "AND e.deleted_at IS NULL";
          } catch (PDOException $e) {
            // Column doesn't exist, skip filter
          }
          $stmt = $pdo->prepare("
            SELECT b.status, b.total_price 
            FROM $bookingTable b
            INNER JOIN $entityTable e ON b.$itemIdColumn = e.id
            WHERE e.created_by = ? $deletedFilter
          ");
          $stmt->execute([$userId]);
        }
        $bookings = $stmt->fetchAll();
        
        foreach ($bookings as $booking) {
          $bookingsSummary['total']++;
          $status = $booking['status'];
          if (isset($bookingsSummary[$status])) {
            $bookingsSummary[$status]++;
          }
          // Calculate total revenue (only confirmed and completed bookings)
          if (in_array($status, ['confirmed', 'completed'])) {
            $bookingsSummary['total_revenue'] += (float)($booking['total_price'] ?? 0);
          }
        }
      }
    } else {
      // For customers: Calculate from bookings they made
      foreach ($types as $type) {
        $table = $bookingTables[$type];
        $stmt = $pdo->prepare("SELECT status, total_price FROM $table WHERE user_id = ?");
        $stmt->execute([$userId]);
        $bookings = $stmt->fetchAll();
        
        foreach ($bookings as $booking) {
          $bookingsSummary['total']++;
          $status = $booking['status'];
          if (isset($bookingsSummary[$status])) {
            $bookingsSummary[$status]++;
          }
          // Calculate total spent (only confirmed and completed bookings)
          if (in_array($status, ['confirmed', 'completed'])) {
            $bookingsSummary['total_spent'] += (float)($booking['total_price'] ?? 0);
          }
        }
      }
      
      // Get recent bookings (last 5) with item details for customers only
      $recentBookings = [];
      foreach ($types as $type) {
        $table = $bookingTables[$type];
        $itemIdColumn = $itemIdColumns[$type];
        $stmt = $pdo->prepare("SELECT TOP 5 *, '$type' as type, $itemIdColumn as item_id FROM $table WHERE user_id = ? ORDER BY booked_at DESC");
        $stmt->execute([$userId]);
        $bookings = $stmt->fetchAll();
        
        // Add item details to each booking
        foreach ($bookings as &$booking) {
          $itemId = (int)$booking['item_id'];
          $booking['item_details'] = getItemDetails($pdo, $type, $itemId);
        }
        
        $recentBookings = array_merge($recentBookings, $bookings);
      }
      
      // Sort by booked_at and take top 5
      usort($recentBookings, function($a, $b) {
        return strtotime($b['booked_at']) - strtotime($a['booked_at']);
      });
      $bookingsSummary['recent'] = array_slice($recentBookings, 0, 5);
    }
    
  } catch (Exception $e) {
    error_log('Profile bookings summary failed: ' . $e->getMessage());
  }
  
  // Get all content created by this user, separated by promoted and all
  $result = [
    'user' => $userInfo,
    'bookings' => $bookingsSummary,
    'promoted' => [
      'hotels' => [],
      'flights' => [],
      'activities' => [],
      'transfers' => []
    ],
    'all' => [
      'hotels' => [],
      'flights' => [],
      'activities' => [],
      'transfers' => []
    ]
  ];
  
  // Hotels - promoted
  try {
    $deletedFilter = "";
    try {
      $testStmt = $pdo->query("SELECT TOP 1 deleted_at FROM hotels");
      $testStmt->fetch();
      $deletedFilter = "AND h.deleted_at IS NULL";
    } catch (PDOException $e) {
      // Column doesn't exist, skip filter
    }
    $stmt = $pdo->prepare("SELECT h.*, c.name as city_name, p.name as province_name, p.region,
      (SELECT TOP 1 image_url FROM entity_images WHERE entity_type = 'hotel' AND entity_id = h.id ORDER BY display_order ASC, id ASC) as image_url
      FROM hotels h 
      LEFT JOIN cities c ON h.city_id = c.id 
      LEFT JOIN provinces p ON h.province_id = p.id 
      WHERE h.created_by = ? AND h.ad = 1 $deletedFilter
      ORDER BY h.price_per_night ASC");
    $stmt->execute([$userId]);
    $promotedHotels = $stmt->fetchAll();
    foreach ($promotedHotels as &$hotel) {
      if (isset($hotel['discount_percent']) && $hotel['discount_percent'] > 0) {
        $hotel['discounted_price'] = round($hotel['price_per_night'] * (1 - $hotel['discount_percent'] / 100), 2);
      }
    }
    $result['promoted']['hotels'] = $promotedHotels;
  } catch (PDOException $e) {
    error_log('Profile promoted hotels query failed: ' . $e->getMessage());
    $result['promoted']['hotels'] = [];
  }
  
  // Hotels - all
  try {
    $orderBy = "h.price_per_night ASC";
    $deletedFilter = "";
    try {
      $testStmt = $pdo->query("SELECT TOP 1 ad FROM hotels");
      $testStmt->fetch();
      $orderBy = "h.ad DESC, h.price_per_night ASC";
    } catch (PDOException $e) {
      // Column doesn't exist, use default order
    }
    try {
      $testStmt = $pdo->query("SELECT TOP 1 deleted_at FROM hotels");
      $testStmt->fetch();
      $deletedFilter = "AND h.deleted_at IS NULL";
    } catch (PDOException $e) {
      // Column doesn't exist, skip filter
    }
    $stmt = $pdo->prepare("SELECT h.*, c.name as city_name, p.name as province_name, p.region,
      (SELECT TOP 1 image_url FROM entity_images WHERE entity_type = 'hotel' AND entity_id = h.id ORDER BY display_order ASC, id ASC) as image_url
      FROM hotels h 
      LEFT JOIN cities c ON h.city_id = c.id 
      LEFT JOIN provinces p ON h.province_id = p.id 
      WHERE h.created_by = ? $deletedFilter
      ORDER BY $orderBy");
    $stmt->execute([$userId]);
    $allHotels = $stmt->fetchAll();
    foreach ($allHotels as &$hotel) {
      if (isset($hotel['discount_percent']) && $hotel['discount_percent'] > 0) {
        $hotel['discounted_price'] = round($hotel['price_per_night'] * (1 - $hotel['discount_percent'] / 100), 2);
      }
    }
    $result['all']['hotels'] = $allHotels;
  } catch (PDOException $e) {
    error_log('Profile all hotels query failed: ' . $e->getMessage());
    $result['all']['hotels'] = [];
  }
  
  // Flights - promoted
  try {
    $deletedFilter = "";
    try {
      $testStmt = $pdo->query("SELECT TOP 1 deleted_at FROM flight_routes");
      $testStmt->fetch();
      $deletedFilter = "AND fr.deleted_at IS NULL";
    } catch (PDOException $e) {
      // Column doesn't exist, skip filter
    }
    $stmt = $pdo->prepare("SELECT fr.*,
      oa.code as origin_code, oa.name as origin_name,
      da.code as destination_code, da.name as destination_name,
      oc.name as origin_city_name, op.name as origin_province_name,
      dc.name as destination_city_name, dp.name as destination_province_name,
      (SELECT TOP 1 image_url FROM entity_images WHERE entity_type = 'flight_route' AND entity_id = fr.id ORDER BY display_order ASC, id ASC) as image_url
      FROM flight_routes fr
      INNER JOIN airports oa ON fr.origin_airport_id = oa.id
      INNER JOIN airports da ON fr.destination_airport_id = da.id
      LEFT JOIN cities oc ON oa.city_id = oc.id
      LEFT JOIN provinces op ON oc.province_id = op.id
      LEFT JOIN cities dc ON da.city_id = dc.id
      LEFT JOIN provinces dp ON dc.province_id = dp.id
      WHERE fr.created_by = ? AND fr.ad = 1 $deletedFilter
      ORDER BY fr.base_price_economy ASC");
    $stmt->execute([$userId]);
    $promotedFlights = $stmt->fetchAll();
    foreach ($promotedFlights as &$flight) {
      $flight['airline'] = $flight['airline'];
      $flight['origin'] = $flight['origin_code'];
      $flight['destination'] = $flight['destination_code'];
      $flight['price'] = $flight['base_price_economy'];
      if (isset($flight['discount_percent']) && $flight['discount_percent'] > 0) {
        $flight['discounted_price'] = round($flight['base_price_economy'] * (1 - $flight['discount_percent'] / 100), 2);
      }
    }
    $result['promoted']['flights'] = $promotedFlights;
  } catch (PDOException $e) {
    error_log('Profile promoted flights query failed: ' . $e->getMessage());
    $result['promoted']['flights'] = [];
  }
  
  // Flights - all
  try {
    $orderBy = "fr.base_price_economy ASC";
    $deletedFilter = "";
    try {
      $testStmt = $pdo->query("SELECT TOP 1 ad FROM flight_routes");
      $testStmt->fetch();
      $orderBy = "fr.ad DESC, fr.base_price_economy ASC";
    } catch (PDOException $e) {
      // Column doesn't exist, use default order
    }
    try {
      $testStmt = $pdo->query("SELECT TOP 1 deleted_at FROM flight_routes");
      $testStmt->fetch();
      $deletedFilter = "AND fr.deleted_at IS NULL";
    } catch (PDOException $e) {
      // Column doesn't exist, skip filter
    }
    $stmt = $pdo->prepare("SELECT fr.*,
      oa.code as origin_code, oa.name as origin_name,
      da.code as destination_code, da.name as destination_name,
      oc.name as origin_city_name, op.name as origin_province_name,
      dc.name as destination_city_name, dp.name as destination_province_name,
      (SELECT TOP 1 image_url FROM entity_images WHERE entity_type = 'flight_route' AND entity_id = fr.id ORDER BY display_order ASC, id ASC) as image_url
      FROM flight_routes fr
      INNER JOIN airports oa ON fr.origin_airport_id = oa.id
      INNER JOIN airports da ON fr.destination_airport_id = da.id
      LEFT JOIN cities oc ON oa.city_id = oc.id
      LEFT JOIN provinces op ON oc.province_id = op.id
      LEFT JOIN cities dc ON da.city_id = dc.id
      LEFT JOIN provinces dp ON dc.province_id = dp.id
      WHERE fr.created_by = ? $deletedFilter
      ORDER BY $orderBy");
    $stmt->execute([$userId]);
    $allFlights = $stmt->fetchAll();
    foreach ($allFlights as &$flight) {
      $flight['airline'] = $flight['airline'];
      $flight['origin'] = $flight['origin_code'];
      $flight['destination'] = $flight['destination_code'];
      $flight['price'] = $flight['base_price_economy'];
      if (isset($flight['discount_percent']) && $flight['discount_percent'] > 0) {
        $flight['discounted_price'] = round($flight['base_price_economy'] * (1 - $flight['discount_percent'] / 100), 2);
      }
    }
    $result['all']['flights'] = $allFlights;
  } catch (PDOException $e) {
    error_log('Profile all flights query failed: ' . $e->getMessage());
    $result['all']['flights'] = [];
  }
  
  // Activities - promoted
  try {
    $stmt = $pdo->prepare("SELECT a.*, c.name as city_name, p.name as province_name, p.region,
      (SELECT TOP 1 image_url FROM entity_images WHERE entity_type = 'activity' AND entity_id = a.id ORDER BY display_order ASC, id ASC) as image_url
      FROM activities a 
      LEFT JOIN cities c ON a.city_id = c.id 
      LEFT JOIN provinces p ON c.province_id = p.id 
      WHERE a.created_by = ? AND a.ad = 1 AND a.deleted_at IS NULL
      ORDER BY a.date ASC, a.price ASC");
    $stmt->execute([$userId]);
    $promotedActivities = $stmt->fetchAll();
    foreach ($promotedActivities as &$activity) {
      if (isset($activity['discount_percent']) && $activity['discount_percent'] > 0) {
        $activity['discounted_price'] = round($activity['price'] * (1 - $activity['discount_percent'] / 100), 2);
      }
    }
    $result['promoted']['activities'] = $promotedActivities;
  } catch (PDOException $e) {
    error_log('Profile promoted activities query failed: ' . $e->getMessage());
    $result['promoted']['activities'] = [];
  }
  
  // Activities - all
  try {
    $orderBy = "a.price ASC, a.id ASC";
    try {
      $testStmt = $pdo->query("SELECT TOP 1 ad FROM activities");
      $testStmt->fetch();
      $orderBy = "a.ad DESC, a.price ASC, a.id ASC";
    } catch (PDOException $e) {
      // Column doesn't exist, use default order
    }
    $stmt = $pdo->prepare("SELECT a.*, c.name as city_name, p.name as province_name, p.region,
      (SELECT TOP 1 image_url FROM entity_images WHERE entity_type = 'activity' AND entity_id = a.id ORDER BY display_order ASC, id ASC) as image_url
      FROM activities a 
      LEFT JOIN cities c ON a.city_id = c.id 
      LEFT JOIN provinces p ON c.province_id = p.id 
      WHERE a.created_by = ? AND a.deleted_at IS NULL
      ORDER BY $orderBy");
    $stmt->execute([$userId]);
    $allActivities = $stmt->fetchAll();
    foreach ($allActivities as &$activity) {
      if (isset($activity['discount_percent']) && $activity['discount_percent'] > 0) {
        $activity['discounted_price'] = round($activity['price'] * (1 - $activity['discount_percent'] / 100), 2);
      }
    }
    $result['all']['activities'] = $allActivities;
  } catch (PDOException $e) {
    error_log('Profile all activities query failed: ' . $e->getMessage());
    $result['all']['activities'] = [];
  }
  
  // Transfers - promoted
  try {
    $deletedFilter = "";
    try {
      $testStmt = $pdo->query("SELECT TOP 1 deleted_at FROM transfer_routes");
      $testStmt->fetch();
      $deletedFilter = "AND tr.deleted_at IS NULL";
    } catch (PDOException $e) {
      // Column doesn't exist, skip filter
    }
    $stmt = $pdo->prepare("SELECT tr.*,
      oc.name as origin_city_name, op.name as origin_province_name,
      dc.name as destination_city_name, dp.name as destination_province_name,
      (SELECT TOP 1 image_url FROM entity_images WHERE entity_type = 'transfer_route' AND entity_id = tr.id ORDER BY display_order ASC, id ASC) as image_url
      FROM transfer_routes tr
      INNER JOIN cities oc ON tr.origin_city_id = oc.id
      INNER JOIN cities dc ON tr.destination_city_id = dc.id
      LEFT JOIN provinces op ON oc.province_id = op.id
      LEFT JOIN provinces dp ON dc.province_id = dp.id
      WHERE tr.created_by = ? AND tr.ad = 1 $deletedFilter
      ORDER BY tr.base_price ASC");
    $stmt->execute([$userId]);
    $promotedTransfers = $stmt->fetchAll();
    foreach ($promotedTransfers as &$transfer) {
      $transfer['service'] = ($transfer['origin_specific'] ?? $transfer['origin_city_name']) . ' to ' . ($transfer['destination_specific'] ?? $transfer['destination_city_name']);
      $transfer['origin'] = $transfer['origin_specific'] ?? $transfer['origin_city_name'];
      $transfer['destination'] = $transfer['destination_specific'] ?? $transfer['destination_city_name'];
      $transfer['price'] = $transfer['base_price'];
      if (isset($transfer['discount_percent']) && $transfer['discount_percent'] > 0) {
        $transfer['discounted_price'] = round($transfer['base_price'] * (1 - $transfer['discount_percent'] / 100), 2);
      }
    }
    $result['promoted']['transfers'] = $promotedTransfers;
  } catch (PDOException $e) {
    error_log('Profile promoted transfers query failed: ' . $e->getMessage());
    $result['promoted']['transfers'] = [];
  }
  
  // Transfers - all
  try {
    $orderBy = "tr.base_price ASC";
    $deletedFilter = "";
    try {
      $testStmt = $pdo->query("SELECT TOP 1 ad FROM transfer_routes");
      $testStmt->fetch();
      $orderBy = "tr.ad DESC, tr.base_price ASC";
    } catch (PDOException $e) {
      // Column doesn't exist, use default order
    }
    try {
      $testStmt = $pdo->query("SELECT TOP 1 deleted_at FROM transfer_routes");
      $testStmt->fetch();
      $deletedFilter = "AND tr.deleted_at IS NULL";
    } catch (PDOException $e) {
      // Column doesn't exist, skip filter
    }
    $stmt = $pdo->prepare("SELECT tr.*,
      oc.name as origin_city_name, op.name as origin_province_name,
      dc.name as destination_city_name, dp.name as destination_province_name,
      (SELECT TOP 1 image_url FROM entity_images WHERE entity_type = 'transfer_route' AND entity_id = tr.id ORDER BY display_order ASC, id ASC) as image_url
      FROM transfer_routes tr
      INNER JOIN cities oc ON tr.origin_city_id = oc.id
      INNER JOIN cities dc ON tr.destination_city_id = dc.id
      LEFT JOIN provinces op ON oc.province_id = op.id
      LEFT JOIN provinces dp ON dc.province_id = dp.id
      WHERE tr.created_by = ? $deletedFilter
      ORDER BY $orderBy");
    $stmt->execute([$userId]);
    $allTransfers = $stmt->fetchAll();
    foreach ($allTransfers as &$transfer) {
      $transfer['service'] = ($transfer['origin_specific'] ?? $transfer['origin_city_name']) . ' to ' . ($transfer['destination_specific'] ?? $transfer['destination_city_name']);
      $transfer['origin'] = $transfer['origin_specific'] ?? $transfer['origin_city_name'];
      $transfer['destination'] = $transfer['destination_specific'] ?? $transfer['destination_city_name'];
      $transfer['price'] = $transfer['base_price'];
      if (isset($transfer['discount_percent']) && $transfer['discount_percent'] > 0) {
        $transfer['discounted_price'] = round($transfer['base_price'] * (1 - $transfer['discount_percent'] / 100), 2);
      }
    }
    $result['all']['transfers'] = $allTransfers;
  } catch (PDOException $e) {
    error_log('Profile all transfers query failed: ' . $e->getMessage());
    $result['all']['transfers'] = [];
  }
  
  json_ok($result);
}

json_error('Not found', 404);

