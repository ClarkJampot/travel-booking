<?php
// Top items controller
declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../db.php';

try {
  $pdo = db_pdo();
} catch (Throwable $e) {
  json_error('Database connection failed', 500);
}

$uri = $GLOBALS['API_URI'] ?? $_SERVER['REQUEST_URI'];

// GET /api/top/hotels
if ($_SERVER['REQUEST_METHOD'] === 'GET' && preg_match('#^/top/hotels/?$#', $uri)) {
  $limit = min(10, max(1, (int)($_GET['limit'] ?? 4)));
  
  // Weighted scoring: (booking_count * 0.6) + (days_since_created_penalty * 0.4)
  $sql = "SELECT TOP $limit h.*,
    ((h.booking_count * 0.6) + (DATEDIFF(day, h.created_at, GETDATE()) * -0.1 * 0.4)) as score,
    (SELECT TOP 1 image_url FROM entity_images WHERE entity_type = 'hotel' AND entity_id = h.id ORDER BY display_order ASC, id ASC) as image_url
    FROM hotels h 
    ORDER BY score DESC, h.booking_count DESC";
  
  $stmt = $pdo->prepare($sql);
  $stmt->execute();
  $rows = $stmt->fetchAll();
  json_ok(['results' => $rows]);
}

// GET /api/top/flights
elseif ($_SERVER['REQUEST_METHOD'] === 'GET' && preg_match('#^/top/flights/?$#', $uri)) {
  $limit = min(10, max(1, (int)($_GET['limit'] ?? 4)));
  
  $sql = "SELECT TOP $limit f.*,
    ((f.booking_count * 0.4) + (DATEDIFF(day, f.created_at, GETDATE()) * -0.1 * 0.2)) as score,
    (SELECT TOP 1 image_url FROM entity_images WHERE entity_type = 'flight' AND entity_id = f.id ORDER BY display_order ASC, id ASC) as image_url
    FROM flights f 
    ORDER BY score DESC, f.booking_count DESC, f.created_at DESC";
  
  $stmt = $pdo->prepare($sql);
  $stmt->execute();
  $rows = $stmt->fetchAll();
  json_ok(['results' => $rows]);
}

// GET /api/top/activities
elseif ($_SERVER['REQUEST_METHOD'] === 'GET' && preg_match('#^/top/activities/?$#', $uri)) {
  $limit = min(10, max(1, (int)($_GET['limit'] ?? 4)));
  
  $sql = "SELECT TOP $limit a.*,
    ((a.booking_count * 0.4) + (DATEDIFF(day, a.created_at, GETDATE()) * -0.1 * 0.2)) as score,
    (SELECT TOP 1 image_url FROM entity_images WHERE entity_type = 'activity' AND entity_id = a.id ORDER BY display_order ASC, id ASC) as image_url
    FROM activities a 
    ORDER BY score DESC, a.booking_count DESC, a.created_at DESC";
  
  $stmt = $pdo->prepare($sql);
  $stmt->execute();
  $rows = $stmt->fetchAll();
  json_ok(['results' => $rows]);
}

// GET /api/top/transfers
elseif ($_SERVER['REQUEST_METHOD'] === 'GET' && preg_match('#^/top/transfers/?$#', $uri)) {
  $limit = min(10, max(1, (int)($_GET['limit'] ?? 4)));
  
  $sql = "SELECT TOP $limit t.*,
    ((t.booking_count * 0.4) + (DATEDIFF(day, t.created_at, GETDATE()) * -0.1 * 0.2)) as score,
    (SELECT TOP 1 image_url FROM entity_images WHERE entity_type = 'transfer' AND entity_id = t.id ORDER BY display_order ASC, id ASC) as image_url
    FROM transfers t 
    ORDER BY score DESC, t.booking_count DESC, t.created_at DESC";
  
  $stmt = $pdo->prepare($sql);
  $stmt->execute();
  $rows = $stmt->fetchAll();
  json_ok(['results' => $rows]);
}

else {
  json_error('Not found', 404);
}
