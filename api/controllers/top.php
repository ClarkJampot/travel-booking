<?php
declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../helpers/ResponseHelper.php';

try {
  $pdo = db_pdo();
} catch (Throwable $e) {
  ResponseHelper::error('Database connection failed', 500);
}


// GET /api/top/hotels
if ($_SERVER['REQUEST_METHOD'] === 'GET' && preg_match('#^/top/hotels/?$#', $uri)) {
  $limit = min(10, max(1, (int)($_GET['limit'] ?? 4)));
  
  // Weighted scoring: (booking_count * 0.6) + (days_since_created_penalty * 0.4)
  $sql = "SELECT TOP $limit h.*,
    ((h.booking_count * 0.6) + (DATEDIFF(day, h.created_at, GETDATE()) * -0.1 * 0.4)) as score,
    (SELECT TOP 1 image_url FROM entity_images WHERE entity_type = 'hotel' AND entity_id = h.id ORDER BY display_order ASC, id ASC) as image_url
    FROM hotels h 
    WHERE h.deleted_at IS NULL
    ORDER BY score DESC, h.booking_count DESC";
  
  $stmt = $pdo->prepare($sql);
  $stmt->execute();
  $rows = $stmt->fetchAll();
  ResponseHelper::successSimple(['results' => $rows]);
}

elseif ($_SERVER['REQUEST_METHOD'] === 'GET' && preg_match('#^/top/flights/?$#', $uri)) {
  $limit = min(10, max(1, (int)($_GET['limit'] ?? 4)));
  
  // Count bookings per route through instances
  $sql = "SELECT TOP $limit fr.*,
    (SELECT COUNT(*) FROM flight_bookings fb
     INNER JOIN flight_instances fi ON fb.instance_id = fi.id
     INNER JOIN flight_schedules fs ON fi.schedule_id = fs.id
     WHERE (fs.route_id = fr.id OR fs.route_pair_id IN (
       SELECT id FROM flight_route_pairs WHERE outbound_route_id = fr.id OR return_route_id = fr.id
     ))) as booking_count,
    ((SELECT COUNT(*) FROM flight_bookings fb
      INNER JOIN flight_instances fi ON fb.instance_id = fi.id
      INNER JOIN flight_schedules fs ON fi.schedule_id = fs.id
      WHERE (fs.route_id = fr.id OR fs.route_pair_id IN (
        SELECT id FROM flight_route_pairs WHERE outbound_route_id = fr.id OR return_route_id = fr.id
      ))) * 0.4 + (DATEDIFF(day, fr.created_at, GETDATE()) * -0.1 * 0.2)) as score,
    (SELECT TOP 1 image_url FROM entity_images WHERE entity_type = 'flight_route' AND entity_id = fr.id ORDER BY display_order ASC, id ASC) as image_url
    FROM flight_routes fr
    WHERE fr.deleted_at IS NULL
    ORDER BY score DESC, booking_count DESC, fr.created_at DESC";
  
  $stmt = $pdo->prepare($sql);
  $stmt->execute();
  $rows = $stmt->fetchAll();
  ResponseHelper::successSimple(['results' => $rows]);
}

// GET /api/top/activities
elseif ($_SERVER['REQUEST_METHOD'] === 'GET' && preg_match('#^/top/activities/?$#', $uri)) {
  $limit = min(10, max(1, (int)($_GET['limit'] ?? 4)));
  
  // Weighted scoring: (booking_count * 0.6) + (days_since_created_penalty * 0.4)
  $sql = "SELECT TOP $limit a.*, c.name as city_name, p.name as province_name,
    ((a.booking_count * 0.6) + (DATEDIFF(day, a.created_at, GETDATE()) * -0.1 * 0.4)) as score,
    (SELECT TOP 1 image_url FROM entity_images WHERE entity_type = 'activity' AND entity_id = a.id ORDER BY display_order ASC, id ASC) as image_url
    FROM activities a
    LEFT JOIN cities c ON a.city_id = c.id
    LEFT JOIN provinces p ON c.province_id = p.id
    WHERE a.deleted_at IS NULL
    ORDER BY score DESC, a.booking_count DESC";
  
  $stmt = $pdo->prepare($sql);
  $stmt->execute();
  $rows = $stmt->fetchAll();
  ResponseHelper::successSimple(['results' => $rows]);
}

else {
  ResponseHelper::error('Not found', 404);
}
