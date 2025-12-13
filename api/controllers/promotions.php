<?php
// Promotions controller
declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../db.php';

try {
  $pdo = db_pdo();
} catch (Throwable $e) {
  json_error('Database connection failed', 500);
}

$uri = $GLOBALS['API_URI'] ?? $_SERVER['REQUEST_URI'];

// GET /api/promotions
if ($_SERVER['REQUEST_METHOD'] === 'GET' && preg_match('#^/promotions/?$#', $uri)) {
  $limit = min(15, max(1, (int)($_GET['limit'] ?? 15)));
  
  $results = [];
  $limitInt = (int)$limit;
  
  try {
    // Get promoted hotels
    $stmt = $pdo->prepare("SELECT TOP $limitInt h.id, h.name, h.discount_percent, h.created_by,
      (SELECT TOP 1 image_url FROM entity_images WHERE entity_type = 'hotel' AND entity_id = h.id ORDER BY display_order ASC, id ASC) as image_url
      FROM hotels h
      WHERE h.ad = 1
      ORDER BY NEWID()");
    $stmt->execute([]);
    $hotels = $stmt->fetchAll();
    foreach ($hotels as $hotel) {
      $results[] = [
        'id' => $hotel['id'],
        'type' => 'hotel',
        'name' => $hotel['name'] ?? 'Hotel',
        'image_url' => $hotel['image_url'],
        'discount_percent' => isset($hotel['discount_percent']) ? (float)$hotel['discount_percent'] : 0,
        'created_by' => isset($hotel['created_by']) ? (int)$hotel['created_by'] : 0,
        'link_url' => isset($hotel['created_by']) ? 'profile.html?user_id=' . $hotel['created_by'] : '#'
      ];
    }
  } catch (PDOException $e) {
    error_log('Promotions hotels query failed: ' . $e->getMessage());
  }
  
  try {
    // Get promoted flights
    $stmt = $pdo->prepare("SELECT TOP $limitInt fr.id, fr.airline, oa.code as origin, da.code as destination, fr.discount_percent, fr.created_by,
      (SELECT TOP 1 image_url FROM entity_images WHERE entity_type = 'flight_route' AND entity_id = fr.id ORDER BY display_order ASC, id ASC) as image_url
      FROM flight_routes fr
      INNER JOIN airports oa ON fr.origin_airport_id = oa.id
      INNER JOIN airports da ON fr.destination_airport_id = da.id
      WHERE fr.ad = 1 AND fr.deleted_at IS NULL
      ORDER BY NEWID()");
    $stmt->execute([]);
    $flights = $stmt->fetchAll();
    foreach ($flights as $flight) {
      $flightName = ($flight['airline'] ?? '') . ' - ' . ($flight['origin'] ?? '') . ' to ' . ($flight['destination'] ?? '');
      $results[] = [
        'id' => $flight['id'],
        'type' => 'flight',
        'name' => $flightName ?: 'Flight',
        'image_url' => $flight['image_url'],
        'discount_percent' => isset($flight['discount_percent']) ? (float)$flight['discount_percent'] : 0,
        'created_by' => isset($flight['created_by']) ? (int)$flight['created_by'] : 0,
        'link_url' => isset($flight['created_by']) ? 'profile.html?user_id=' . $flight['created_by'] : '#'
      ];
    }
  } catch (PDOException $e) {
    error_log('Promotions flights query failed: ' . $e->getMessage());
  }
  
  try {
    // Get promoted activities
    $stmt = $pdo->prepare("SELECT TOP $limitInt a.id, a.title, a.discount_percent, a.created_by,
      (SELECT TOP 1 image_url FROM entity_images WHERE entity_type = 'activity' AND entity_id = a.id ORDER BY display_order ASC, id ASC) as image_url
      FROM activities a
      WHERE a.ad = 1
      ORDER BY NEWID()");
    $stmt->execute([]);
    $activities = $stmt->fetchAll();
    foreach ($activities as $activity) {
      $results[] = [
        'id' => $activity['id'],
        'type' => 'activity',
        'name' => $activity['title'] ?? 'Activity',
        'image_url' => $activity['image_url'],
        'discount_percent' => isset($activity['discount_percent']) ? (float)$activity['discount_percent'] : 0,
        'created_by' => isset($activity['created_by']) ? (int)$activity['created_by'] : 0,
        'link_url' => isset($activity['created_by']) ? 'profile.html?user_id=' . $activity['created_by'] : '#'
      ];
    }
  } catch (PDOException $e) {
    error_log('Promotions activities query failed: ' . $e->getMessage());
  }
  
  try {
    // Get promoted transfers
    $stmt = $pdo->prepare("SELECT TOP $limitInt tr.id, tt.name as service, tr.discount_percent, tr.created_by,
      (SELECT TOP 1 image_url FROM entity_images WHERE entity_type = 'transfer_route' AND entity_id = tr.id ORDER BY display_order ASC, id ASC) as image_url
      FROM transfer_routes tr
      INNER JOIN transfer_types tt ON tr.transfer_type_id = tt.id
      WHERE tr.ad = 1 AND tr.deleted_at IS NULL
      ORDER BY NEWID()");
    $stmt->execute([]);
    $transfers = $stmt->fetchAll();
    foreach ($transfers as $transfer) {
      $results[] = [
        'id' => $transfer['id'],
        'type' => 'transfer',
        'name' => $transfer['service'] ?? 'Transfer',
        'image_url' => $transfer['image_url'],
        'discount_percent' => isset($transfer['discount_percent']) ? (float)$transfer['discount_percent'] : 0,
        'created_by' => isset($transfer['created_by']) ? (int)$transfer['created_by'] : 0,
        'link_url' => isset($transfer['created_by']) ? 'profile.html?user_id=' . $transfer['created_by'] : '#'
      ];
    }
  } catch (PDOException $e) {
    error_log('Promotions transfers query failed: ' . $e->getMessage());
  }
  
  // Shuffle results for variety
  shuffle($results);
  
  // Limit to top 15
  $results = array_slice($results, 0, 15);
  
  json_ok(['results' => $results]);
}

json_error('Not found', 404);

