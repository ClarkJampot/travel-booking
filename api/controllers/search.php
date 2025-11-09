<?php
// Search controller
declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../db.php';

try {
  $pdo = db_pdo();
} catch (Throwable $e) {
  json_error('Database connection failed', 500);
}

$uri = $GLOBALS['API_URI'] ?? $_SERVER['REQUEST_URI'];

// GET /api/search
if ($_SERVER['REQUEST_METHOD'] === 'GET' && preg_match('#^/search/?$#', $uri)) {
  $query = trim($_GET['q'] ?? '');
  $type = $_GET['type'] ?? null; // hotels, flights, activities, transfers, destinations, all
  
  if (!$query) {
    json_error('Missing search query', 400);
  }
  
  $results = [];
  $searchTerm = '%' . $query . '%';
  
  // Search destinations
  if (!$type || $type === 'destinations' || $type === 'all') {
    $stmt = $pdo->prepare('SELECT id, name, country, description, image_url, featured, "destination" as type FROM destinations WHERE name LIKE ? OR country LIKE ? OR description LIKE ? ORDER BY featured DESC, name ASC');
    $stmt->execute([$searchTerm, $searchTerm, $searchTerm]);
    $results['destinations'] = $stmt->fetchAll();
  }
  
  // Search hotels
  if (!$type || $type === 'hotels' || $type === 'all') {
    $stmt = $pdo->prepare('SELECT id, name, city, country, price_per_night, rating, description, image_url, "hotel" as type FROM hotels WHERE name LIKE ? OR city LIKE ? OR country LIKE ? OR description LIKE ? ORDER BY rating DESC, price_per_night ASC');
    $stmt->execute([$searchTerm, $searchTerm, $searchTerm, $searchTerm]);
    $results['hotels'] = $stmt->fetchAll();
  }
  
  // Search flights
  if (!$type || $type === 'flights' || $type === 'all') {
    $stmt = $pdo->prepare('SELECT id, airline, origin, destination, depart_date, price, description, image_url, "flight" as type FROM flights WHERE airline LIKE ? OR origin LIKE ? OR destination LIKE ? OR description LIKE ? ORDER BY depart_date ASC, price ASC');
    $stmt->execute([$searchTerm, $searchTerm, $searchTerm, $searchTerm]);
    $results['flights'] = $stmt->fetchAll();
  }
  
  // Search activities
  if (!$type || $type === 'activities' || $type === 'all') {
    $stmt = $pdo->prepare('SELECT id, title, city, date, price, description, image_url, "activity" as type FROM activities WHERE title LIKE ? OR city LIKE ? OR description LIKE ? ORDER BY date ASC, price ASC');
    $stmt->execute([$searchTerm, $searchTerm, $searchTerm]);
    $results['activities'] = $stmt->fetchAll();
  }
  
  // Search transfers
  if (!$type || $type === 'transfers' || $type === 'all') {
    $stmt = $pdo->prepare('SELECT id, service, origin, destination, date, price, description, image_url, "transfer" as type FROM transfers WHERE service LIKE ? OR origin LIKE ? OR destination LIKE ? OR description LIKE ? ORDER BY date ASC, price ASC');
    $stmt->execute([$searchTerm, $searchTerm, $searchTerm, $searchTerm]);
    $results['transfers'] = $stmt->fetchAll();
  }
  
  json_ok(['query' => $query, 'results' => $results]);
}

json_error('Not found', 404);

