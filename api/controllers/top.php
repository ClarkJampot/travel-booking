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

// GET /api/top/hotels
if ($_SERVER['REQUEST_METHOD'] === 'GET' && preg_match('#/api/top/hotels/?$#', $_SERVER['REQUEST_URI'])) {
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
  exit;
}

// GET /api/top/flights
if ($_SERVER['REQUEST_METHOD'] === 'GET' && preg_match('#/api/top/flights/?$#', $_SERVER['REQUEST_URI'])) {
  $limit = min(10, max(1, (int)($_GET['limit'] ?? 4)));
  
  $sql = "SELECT TOP $limit *, 
    ((booking_count * 0.4) + (DATEDIFF(day, created_at, GETDATE()) * -0.1 * 0.2)) as score
    FROM flights 
    ORDER BY score DESC, booking_count DESC, created_at DESC";
  
  $stmt = $pdo->prepare($sql);
  $stmt->execute();
  $rows = $stmt->fetchAll();
  json_ok(['results' => $rows]);
  exit;
}

// GET /api/top/activities
if ($_SERVER['REQUEST_METHOD'] === 'GET' && preg_match('#/api/top/activities/?$#', $_SERVER['REQUEST_URI'])) {
  $limit = min(10, max(1, (int)($_GET['limit'] ?? 4)));
  
  $sql = "SELECT TOP $limit *, 
    ((booking_count * 0.4) + (DATEDIFF(day, created_at, GETDATE()) * -0.1 * 0.2)) as score
    FROM activities 
    ORDER BY score DESC, booking_count DESC, created_at DESC";
  
  $stmt = $pdo->prepare($sql);
  $stmt->execute();
  $rows = $stmt->fetchAll();
  json_ok(['results' => $rows]);
  exit;
}

// GET /api/top/transfers
if ($_SERVER['REQUEST_METHOD'] === 'GET' && preg_match('#/api/top/transfers/?$#', $_SERVER['REQUEST_URI'])) {
  $limit = min(10, max(1, (int)($_GET['limit'] ?? 4)));
  
  $sql = "SELECT TOP $limit *, 
    ((booking_count * 0.4) + (DATEDIFF(day, created_at, GETDATE()) * -0.1 * 0.2)) as score
    FROM transfers 
    ORDER BY score DESC, booking_count DESC, created_at DESC";
  
  $stmt = $pdo->prepare($sql);
  $stmt->execute();
  $rows = $stmt->fetchAll();
  json_ok(['results' => $rows]);
  exit;
}

json_error('Not found', 404);
