<?php
// Profile controller
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

// GET /api/profile?user_id=X
if ($_SERVER['REQUEST_METHOD'] === 'GET' && preg_match('#^/profile/?$#', $uri)) {
  $userId = isset($_GET['user_id']) ? (int)$_GET['user_id'] : null;
  
  if (!$userId) {
    json_error('Missing user_id parameter', 400);
  }
  
  // Get user information
  $stmt = $pdo->prepare('SELECT u.id, u.email, u.full_name, u.phone, u.address, r.name as role_name 
    FROM users u 
    LEFT JOIN roles r ON u.role_id = r.id 
    WHERE u.id = ?');
  $stmt->execute([$userId]);
  $user = $stmt->fetch();
  
  if (!$user) {
    json_error('User not found', 404);
  }
  
  // Get all content created by this user, separated by promoted and all
  $result = [
    'user' => $user,
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
    $stmt = $pdo->prepare("SELECT h.*, c.name as city_name, p.name as province_name, p.region,
      (SELECT TOP 1 image_url FROM entity_images WHERE entity_type = 'hotel' AND entity_id = h.id ORDER BY display_order ASC, id ASC) as image_url
      FROM hotels h 
      LEFT JOIN cities c ON h.city_id = c.id 
      LEFT JOIN provinces p ON h.province_id = p.id 
      WHERE h.created_by = ? AND h.ad = 1
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
    try {
      $testStmt = $pdo->query("SELECT TOP 1 ad FROM hotels");
      $testStmt->fetch();
      $orderBy = "h.ad DESC, h.price_per_night ASC";
    } catch (PDOException $e) {
      // Column doesn't exist, use default order
    }
    $stmt = $pdo->prepare("SELECT h.*, c.name as city_name, p.name as province_name, p.region,
      (SELECT TOP 1 image_url FROM entity_images WHERE entity_type = 'hotel' AND entity_id = h.id ORDER BY display_order ASC, id ASC) as image_url
      FROM hotels h 
      LEFT JOIN cities c ON h.city_id = c.id 
      LEFT JOIN provinces p ON h.province_id = p.id 
      WHERE h.created_by = ?
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
    $stmt = $pdo->prepare("SELECT f.*, 
      co.name as origin_city_name, po.name as origin_province_name,
      cd.name as destination_city_name, pd.name as destination_province_name,
      (SELECT TOP 1 image_url FROM entity_images WHERE entity_type = 'flight' AND entity_id = f.id ORDER BY display_order ASC, id ASC) as image_url
      FROM flights f
      LEFT JOIN cities co ON f.origin_city_id = co.id
      LEFT JOIN provinces po ON co.province_id = po.id
      LEFT JOIN cities cd ON f.destination_city_id = cd.id
      LEFT JOIN provinces pd ON cd.province_id = pd.id
      WHERE f.created_by = ? AND f.ad = 1
      ORDER BY f.depart_date ASC, f.price ASC");
    $stmt->execute([$userId]);
    $promotedFlights = $stmt->fetchAll();
    foreach ($promotedFlights as &$flight) {
      if (isset($flight['discount_percent']) && $flight['discount_percent'] > 0) {
        $flight['discounted_price'] = round($flight['price'] * (1 - $flight['discount_percent'] / 100), 2);
      }
    }
    $result['promoted']['flights'] = $promotedFlights;
  } catch (PDOException $e) {
    error_log('Profile promoted flights query failed: ' . $e->getMessage());
    $result['promoted']['flights'] = [];
  }
  
  // Flights - all
  try {
    $orderBy = "f.depart_date ASC, f.price ASC";
    try {
      $testStmt = $pdo->query("SELECT TOP 1 ad FROM flights");
      $testStmt->fetch();
      $orderBy = "f.ad DESC, f.depart_date ASC, f.price ASC";
    } catch (PDOException $e) {
      // Column doesn't exist, use default order
    }
    $stmt = $pdo->prepare("SELECT f.*, 
      co.name as origin_city_name, po.name as origin_province_name,
      cd.name as destination_city_name, pd.name as destination_province_name,
      (SELECT TOP 1 image_url FROM entity_images WHERE entity_type = 'flight' AND entity_id = f.id ORDER BY display_order ASC, id ASC) as image_url
      FROM flights f
      LEFT JOIN cities co ON f.origin_city_id = co.id
      LEFT JOIN provinces po ON co.province_id = po.id
      LEFT JOIN cities cd ON f.destination_city_id = cd.id
      LEFT JOIN provinces pd ON cd.province_id = pd.id
      WHERE f.created_by = ?
      ORDER BY $orderBy");
    $stmt->execute([$userId]);
    $allFlights = $stmt->fetchAll();
    foreach ($allFlights as &$flight) {
      if (isset($flight['discount_percent']) && $flight['discount_percent'] > 0) {
        $flight['discounted_price'] = round($flight['price'] * (1 - $flight['discount_percent'] / 100), 2);
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
      WHERE a.created_by = ? AND a.ad = 1
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
    $orderBy = "a.date ASC, a.price ASC";
    try {
      $testStmt = $pdo->query("SELECT TOP 1 ad FROM activities");
      $testStmt->fetch();
      $orderBy = "a.ad DESC, a.date ASC, a.price ASC";
    } catch (PDOException $e) {
      // Column doesn't exist, use default order
    }
    $stmt = $pdo->prepare("SELECT a.*, c.name as city_name, p.name as province_name, p.region,
      (SELECT TOP 1 image_url FROM entity_images WHERE entity_type = 'activity' AND entity_id = a.id ORDER BY display_order ASC, id ASC) as image_url
      FROM activities a 
      LEFT JOIN cities c ON a.city_id = c.id 
      LEFT JOIN provinces p ON c.province_id = p.id 
      WHERE a.created_by = ?
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
    $stmt = $pdo->prepare("SELECT t.*, 
      co.name as origin_city_name, po.name as origin_province_name,
      cd.name as destination_city_name, pd.name as destination_province_name,
      (SELECT TOP 1 image_url FROM entity_images WHERE entity_type = 'transfer' AND entity_id = t.id ORDER BY display_order ASC, id ASC) as image_url
      FROM transfers t
      LEFT JOIN cities co ON t.origin_city_id = co.id
      LEFT JOIN provinces po ON co.province_id = po.id
      LEFT JOIN cities cd ON t.destination_city_id = cd.id
      LEFT JOIN provinces pd ON cd.province_id = pd.id
      WHERE t.created_by = ? AND t.ad = 1
      ORDER BY t.date ASC, t.price ASC");
    $stmt->execute([$userId]);
    $promotedTransfers = $stmt->fetchAll();
    foreach ($promotedTransfers as &$transfer) {
      if (isset($transfer['discount_percent']) && $transfer['discount_percent'] > 0) {
        $transfer['discounted_price'] = round($transfer['price'] * (1 - $transfer['discount_percent'] / 100), 2);
      }
    }
    $result['promoted']['transfers'] = $promotedTransfers;
  } catch (PDOException $e) {
    error_log('Profile promoted transfers query failed: ' . $e->getMessage());
    $result['promoted']['transfers'] = [];
  }
  
  // Transfers - all
  try {
    $orderBy = "t.date ASC, t.price ASC";
    try {
      $testStmt = $pdo->query("SELECT TOP 1 ad FROM transfers");
      $testStmt->fetch();
      $orderBy = "t.ad DESC, t.date ASC, t.price ASC";
    } catch (PDOException $e) {
      // Column doesn't exist, use default order
    }
    $stmt = $pdo->prepare("SELECT t.*, 
      co.name as origin_city_name, po.name as origin_province_name,
      cd.name as destination_city_name, pd.name as destination_province_name,
      (SELECT TOP 1 image_url FROM entity_images WHERE entity_type = 'transfer' AND entity_id = t.id ORDER BY display_order ASC, id ASC) as image_url
      FROM transfers t
      LEFT JOIN cities co ON t.origin_city_id = co.id
      LEFT JOIN provinces po ON co.province_id = po.id
      LEFT JOIN cities cd ON t.destination_city_id = cd.id
      LEFT JOIN provinces pd ON cd.province_id = pd.id
      WHERE t.created_by = ?
      ORDER BY $orderBy");
    $stmt->execute([$userId]);
    $allTransfers = $stmt->fetchAll();
    foreach ($allTransfers as &$transfer) {
      if (isset($transfer['discount_percent']) && $transfer['discount_percent'] > 0) {
        $transfer['discounted_price'] = round($transfer['price'] * (1 - $transfer['discount_percent'] / 100), 2);
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

