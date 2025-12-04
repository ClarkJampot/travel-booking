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
  try {
    $query = trim($_GET['q'] ?? '');
    $type = isset($_GET['type']) ? trim($_GET['type']) : null; // hotels, flights, activities, transfers, destinations, all
    
    if (!$query) {
      json_error('Missing search query', 400);
    }
    
    $results = [];
    // Use case-insensitive search with wildcards - escape special characters
    $escapedQuery = str_replace(['%', '_', '[', ']'], ['[%]', '[_]', '[[]', '[]]'], $query);
    $searchPattern = '%' . $escapedQuery . '%';
  
  // Search destinations - EXACT COPY from destinations.php
  if (!$type || $type === 'destinations' || $type === 'all') {
    try {
      // EXACT same pattern as destinations controller
      $searchTerm = str_replace(['%', '_', '[', ']'], ['[%]', '[_]', '[[]', '[]]'], $query);
      $searchPattern = '%' . $searchTerm . '%';
      
      $where = [];
      $params = [];
      $where[] = "(d.name COLLATE SQL_Latin1_General_CP1_CI_AI LIKE ? OR d.description COLLATE SQL_Latin1_General_CP1_CI_AI LIKE ?)";
      $params[] = $searchPattern;
      $params[] = $searchPattern;
      
      $whereSql = 'WHERE ' . implode(' AND ', $where);
      $offsetInt = 0;
      $limitInt = 50;
      
      $sql = "SELECT d.*,
        (SELECT TOP 1 image_url FROM entity_images WHERE entity_type = 'destination' AND entity_id = d.id ORDER BY display_order ASC, id ASC) as image_url
        FROM destinations d 
        $whereSql 
        ORDER BY d.featured DESC, d.name ASC 
        OFFSET $offsetInt ROWS FETCH NEXT $limitInt ROWS ONLY";
      
      $stmt = $pdo->prepare($sql);
      $stmt->execute($params);
      $destinations = $stmt->fetchAll();
      
      foreach ($destinations as &$dest) {
        $dest['type'] = 'destination';
      }
      
      $results['destinations'] = $destinations;
    } catch (PDOException $e) {
      $results['destinations'] = [];
    }
  }
  
  // Search hotels - EXACT COPY of hotels.php search logic
  if (!$type || $type === 'hotels' || $type === 'all') {
    try {
      // EXACT same logic as hotels.php lines 76-85
      $searchTerm = str_replace(['%', '_', '[', ']'], ['[%]', '[_]', '[[]', '[]]'], $query);
      $searchPattern = '%' . $searchTerm . '%';
      
      $where = [];
      $params = [];
      $where[] = "(h.name COLLATE SQL_Latin1_General_CP1_CI_AI LIKE ? OR c.name COLLATE SQL_Latin1_General_CP1_CI_AI LIKE ? OR p.name COLLATE SQL_Latin1_General_CP1_CI_AI LIKE ? OR h.description COLLATE SQL_Latin1_General_CP1_CI_AI LIKE ?)";
      $params[] = $searchPattern;
      $params[] = $searchPattern;
      $params[] = $searchPattern;
      $params[] = $searchPattern;
      
      $whereSql = 'WHERE ' . implode(' AND ', $where);
      $offsetInt = 0;
      $limitInt = 50;
      
      $sql = "SELECT h.*, c.name as city_name, p.name as province_name,
        (SELECT TOP 1 image_url FROM entity_images WHERE entity_type = 'hotel' AND entity_id = h.id ORDER BY display_order ASC, id ASC) as image_url
        FROM hotels h 
        LEFT JOIN cities c ON h.city_id = c.id 
        LEFT JOIN provinces p ON h.province_id = p.id 
        $whereSql 
        ORDER BY h.price_per_night ASC 
        OFFSET $offsetInt ROWS FETCH NEXT $limitInt ROWS ONLY";
      
      $stmt = $pdo->prepare($sql);
      $stmt->execute($params);
      $hotels = $stmt->fetchAll();
      
      // Add type field to each result
      foreach ($hotels as &$hotel) {
        $hotel['type'] = 'hotel';
      }
      
      $results['hotels'] = $hotels;
    } catch (PDOException $e) {
      error_log('Search hotels error: ' . $e->getMessage());
      $results['hotels'] = [];
    }
  }
  
  // Search flights - EXACT COPY from flights controller pattern
  if (!$type || $type === 'flights' || $type === 'all') {
    try {
      $searchTerm = str_replace(['%', '_', '[', ']'], ['[%]', '[_]', '[[]', '[]]'], $query);
      $searchPattern = '%' . $searchTerm . '%';
      
      $stmt = $pdo->prepare('SELECT f.id, f.airline, f.origin, f.destination, 
        co.name as origin_city_name, po.name as origin_province_name,
        cd.name as destination_city_name, pd.name as destination_province_name,
        f.depart_date, f.price, f.description, "flight" as type,
        (SELECT TOP 1 image_url FROM entity_images WHERE entity_type = \'flight\' AND entity_id = f.id ORDER BY display_order ASC, id ASC) as image_url
        FROM flights f
        LEFT JOIN cities co ON f.origin_city_id = co.id
        LEFT JOIN provinces po ON co.province_id = po.id
        LEFT JOIN cities cd ON f.destination_city_id = cd.id
        LEFT JOIN provinces pd ON cd.province_id = pd.id
        WHERE (f.airline COLLATE SQL_Latin1_General_CP1_CI_AI LIKE ? OR f.origin COLLATE SQL_Latin1_General_CP1_CI_AI LIKE ? OR f.destination COLLATE SQL_Latin1_General_CP1_CI_AI LIKE ? OR co.name COLLATE SQL_Latin1_General_CP1_CI_AI LIKE ? OR cd.name COLLATE SQL_Latin1_General_CP1_CI_AI LIKE ? OR po.name COLLATE SQL_Latin1_General_CP1_CI_AI LIKE ? OR pd.name COLLATE SQL_Latin1_General_CP1_CI_AI LIKE ? OR f.description COLLATE SQL_Latin1_General_CP1_CI_AI LIKE ?)
        ORDER BY f.depart_date ASC, f.price ASC');
      $stmt->execute([$searchPattern, $searchPattern, $searchPattern, $searchPattern, $searchPattern, $searchPattern, $searchPattern, $searchPattern]);
      $results['flights'] = $stmt->fetchAll();
    } catch (PDOException $e) {
      $results['flights'] = [];
    }
  }
  
  // Search activities - EXACT COPY from activities controller pattern
  if (!$type || $type === 'activities' || $type === 'all') {
    try {
      $searchTerm = str_replace(['%', '_', '[', ']'], ['[%]', '[_]', '[[]', '[]]'], $query);
      $searchPattern = '%' . $searchTerm . '%';
      
      $stmt = $pdo->prepare('SELECT a.id, a.title, c.name as city_name, p.name as province_name, a.date, a.price, a.description, "activity" as type,
        (SELECT TOP 1 image_url FROM entity_images WHERE entity_type = \'activity\' AND entity_id = a.id ORDER BY display_order ASC, id ASC) as image_url
        FROM activities a 
        LEFT JOIN cities c ON a.city_id = c.id 
        LEFT JOIN provinces p ON c.province_id = p.id 
        WHERE (a.title COLLATE SQL_Latin1_General_CP1_CI_AI LIKE ? OR c.name COLLATE SQL_Latin1_General_CP1_CI_AI LIKE ? OR p.name COLLATE SQL_Latin1_General_CP1_CI_AI LIKE ? OR a.description COLLATE SQL_Latin1_General_CP1_CI_AI LIKE ?)
        ORDER BY a.date ASC, a.price ASC');
      $stmt->execute([$searchPattern, $searchPattern, $searchPattern, $searchPattern]);
      $results['activities'] = $stmt->fetchAll();
    } catch (PDOException $e) {
      $results['activities'] = [];
    }
  }
  
  // Search transfers - EXACT COPY from transfers.php
  if (!$type || $type === 'transfers' || $type === 'all') {
    try {
      // EXACT same pattern as transfers controller
      $searchTerm = str_replace(['%', '_', '[', ']'], ['[%]', '[_]', '[[]', '[]]'], $query);
      $searchPattern = '%' . $searchTerm . '%';
      
      $where = [];
      $params = [];
      $where[] = "(t.service COLLATE SQL_Latin1_General_CP1_CI_AI LIKE ? OR t.origin COLLATE SQL_Latin1_General_CP1_CI_AI LIKE ? OR t.destination COLLATE SQL_Latin1_General_CP1_CI_AI LIKE ? OR co.name COLLATE SQL_Latin1_General_CP1_CI_AI LIKE ? OR cd.name COLLATE SQL_Latin1_General_CP1_CI_AI LIKE ? OR po.name COLLATE SQL_Latin1_General_CP1_CI_AI LIKE ? OR pd.name COLLATE SQL_Latin1_General_CP1_CI_AI LIKE ? OR t.description COLLATE SQL_Latin1_General_CP1_CI_AI LIKE ?)";
      $params[] = $searchPattern;
      $params[] = $searchPattern;
      $params[] = $searchPattern;
      $params[] = $searchPattern;
      $params[] = $searchPattern;
      $params[] = $searchPattern;
      $params[] = $searchPattern;
      $params[] = $searchPattern;
      
      $whereSql = 'WHERE ' . implode(' AND ', $where);
      $offsetInt = 0;
      $limitInt = 50;
      
      $sql = "SELECT t.*, 
        co.name as origin_city_name, po.name as origin_province_name,
        cd.name as destination_city_name, pd.name as destination_province_name,
        (SELECT TOP 1 image_url FROM entity_images WHERE entity_type = 'transfer' AND entity_id = t.id ORDER BY display_order ASC, id ASC) as image_url
        FROM transfers t
        LEFT JOIN cities co ON t.origin_city_id = co.id
        LEFT JOIN provinces po ON co.province_id = po.id
        LEFT JOIN cities cd ON t.destination_city_id = cd.id
        LEFT JOIN provinces pd ON cd.province_id = pd.id
        $whereSql 
        ORDER BY t.date ASC, t.price ASC 
        OFFSET $offsetInt ROWS FETCH NEXT $limitInt ROWS ONLY";
      
      $stmt = $pdo->prepare($sql);
      $stmt->execute($params);
      $transfers = $stmt->fetchAll();
      
      foreach ($transfers as &$transfer) {
        $transfer['type'] = 'transfer';
      }
      
      $results['transfers'] = $transfers;
    } catch (PDOException $e) {
      $results['transfers'] = [];
    }
  }
  
    // Ensure results always has the requested type, even if empty
    if ($type && $type !== 'all') {
      if (!isset($results[$type])) {
        $results[$type] = [];
      }
    }
    error_log('Search API returning results: ' . json_encode(array_keys($results)) . ', results structure: ' . json_encode($results));
    json_ok(['query' => $query, 'results' => $results]);
  } catch (Throwable $e) {
    error_log('Search controller error: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
    error_log('Stack trace: ' . $e->getTraceAsString());
    json_error('Search failed: ' . $e->getMessage(), 500);
  }
}

json_error('Not found', 404);

