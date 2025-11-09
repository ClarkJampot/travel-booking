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
  
  // Weighted scoring: (booking_count * 0.4) + (rating * 20 * 0.4) + (days_since_created_penalty * 0.2)
  $sql = "SELECT TOP $limit *, 
    ((booking_count * 0.4) + (rating * 20 * 0.4) + (DATEDIFF(day, created_at, GETDATE()) * -0.1 * 0.2)) as score
    FROM hotels 
    ORDER BY score DESC, rating DESC, booking_count DESC";
  
  $stmt = $pdo->prepare($sql);
  $stmt->execute();
  $rows = $stmt->fetchAll();
  json_ok(['results' => $rows]);
}

// GET /api/top/flights
elseif ($_SERVER['REQUEST_METHOD'] === 'GET' && preg_match('#^/top/flights/?$#', $uri)) {
  $limit = min(10, max(1, (int)($_GET['limit'] ?? 4)));
  
  $sql = "SELECT TOP $limit *, 
    ((booking_count * 0.4) + (DATEDIFF(day, created_at, GETDATE()) * -0.1 * 0.2)) as score
    FROM flights 
    ORDER BY score DESC, booking_count DESC, created_at DESC";
  
  $stmt = $pdo->prepare($sql);
  $stmt->execute();
  $rows = $stmt->fetchAll();
  json_ok(['results' => $rows]);
}

// GET /api/top/activities
elseif ($_SERVER['REQUEST_METHOD'] === 'GET' && preg_match('#^/top/activities/?$#', $uri)) {
  $limit = min(10, max(1, (int)($_GET['limit'] ?? 4)));
  
  $sql = "SELECT TOP $limit *, 
    ((booking_count * 0.4) + (DATEDIFF(day, created_at, GETDATE()) * -0.1 * 0.2)) as score
    FROM activities 
    ORDER BY score DESC, booking_count DESC, created_at DESC";
  
  $stmt = $pdo->prepare($sql);
  $stmt->execute();
  $rows = $stmt->fetchAll();
  json_ok(['results' => $rows]);
}

// GET /api/top/transfers
elseif ($_SERVER['REQUEST_METHOD'] === 'GET' && preg_match('#^/top/transfers/?$#', $uri)) {
  $limit = min(10, max(1, (int)($_GET['limit'] ?? 4)));
  
  $sql = "SELECT TOP $limit *, 
    ((booking_count * 0.4) + (DATEDIFF(day, created_at, GETDATE()) * -0.1 * 0.2)) as score
    FROM transfers 
    ORDER BY score DESC, booking_count DESC, created_at DESC";
  
  $stmt = $pdo->prepare($sql);
  $stmt->execute();
  $rows = $stmt->fetchAll();
  json_ok(['results' => $rows]);
}

else {
  json_error('Not found', 404);
}
