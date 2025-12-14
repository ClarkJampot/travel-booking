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
    $type = isset($_GET['type']) ? trim($_GET['type']) : null; // hotels, flights, destinations, all
    
    // #region agent log
    file_put_contents(__DIR__ . '/../../.cursor/debug.log', json_encode(['sessionId' => 'debug-session', 'runId' => 'run1', 'hypothesisId' => 'A', 'location' => 'search.php:19', 'message' => 'Search API called', 'data' => ['query' => $query, 'type' => $type], 'timestamp' => time() * 1000]) . "\n", FILE_APPEND);
    // #endregion
    
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
        ORDER BY d.name ASC 
        OFFSET $offsetInt ROWS FETCH NEXT $limitInt ROWS ONLY";
      
      $stmt = $pdo->prepare($sql);
      $stmt->execute($params);
      $destinations = $stmt->fetchAll();
      
      // #region agent log
      file_put_contents(__DIR__ . '/../../.cursor/debug.log', json_encode(['sessionId' => 'debug-session', 'runId' => 'run1', 'hypothesisId' => 'C', 'location' => 'search.php:57', 'message' => 'Destinations search results', 'data' => ['count' => count($destinations), 'first_result' => $destinations[0] ?? null], 'timestamp' => time() * 1000]) . "\n", FILE_APPEND);
      // #endregion
      
      foreach ($destinations as &$dest) {
        $dest['type'] = 'destination';
      }
      
      $results['destinations'] = $destinations;
    } catch (PDOException $e) {
      // #region agent log
      file_put_contents(__DIR__ . '/../../.cursor/debug.log', json_encode(['sessionId' => 'debug-session', 'runId' => 'run1', 'hypothesisId' => 'C', 'location' => 'search.php:65', 'message' => 'Destinations search error', 'data' => ['error' => $e->getMessage()], 'timestamp' => time() * 1000]) . "\n", FILE_APPEND);
      // #endregion
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
      
      // #region agent log
      file_put_contents(__DIR__ . '/../../.cursor/debug.log', json_encode(['sessionId' => 'debug-session', 'runId' => 'run1', 'hypothesisId' => 'K', 'location' => 'search.php:127', 'message' => 'Flights search starting', 'data' => ['query' => $query, 'searchPattern' => $searchPattern], 'timestamp' => time() * 1000]) . "\n", FILE_APPEND);
      // #endregion
      
      // First, check if there are any flight routes at all
      $checkStmt = $pdo->query('SELECT COUNT(*) as total FROM flight_routes WHERE deleted_at IS NULL');
      $totalFlights = $checkStmt->fetch()['total'];
      
      // #region agent log
      file_put_contents(__DIR__ . '/../../.cursor/debug.log', json_encode(['sessionId' => 'debug-session', 'runId' => 'run1', 'hypothesisId' => 'K', 'location' => 'search.php:132', 'message' => 'Total flights in DB', 'data' => ['total' => $totalFlights], 'timestamp' => time() * 1000]) . "\n", FILE_APPEND);
      // #endregion
      
      $stmt = $pdo->prepare('SELECT TOP 50 fr.id, fr.airline, oa.code as origin, da.code as destination,
        oc.name as origin_city_name, op.name as origin_province_name,
        dc.name as destination_city_name, pd.name as destination_province_name,
        fr.base_price_economy as price, \'flight\' as type,
        (SELECT TOP 1 image_url FROM entity_images WHERE entity_type = \'flight_route\' AND entity_id = fr.id ORDER BY display_order ASC, id ASC) as image_url
        FROM flight_routes fr
        INNER JOIN airports oa ON fr.origin_airport_id = oa.id
        INNER JOIN airports da ON fr.destination_airport_id = da.id
        LEFT JOIN cities oc ON oa.city_id = oc.id
        LEFT JOIN provinces op ON oc.province_id = op.id
        LEFT JOIN cities dc ON da.city_id = dc.id
        LEFT JOIN provinces pd ON dc.province_id = pd.id
        WHERE (fr.airline COLLATE SQL_Latin1_General_CP1_CI_AI LIKE ? OR oa.code COLLATE SQL_Latin1_General_CP1_CI_AI LIKE ? OR da.code COLLATE SQL_Latin1_General_CP1_CI_AI LIKE ? OR oa.name COLLATE SQL_Latin1_General_CP1_CI_AI LIKE ? OR da.name COLLATE SQL_Latin1_General_CP1_CI_AI LIKE ? OR oc.name COLLATE SQL_Latin1_General_CP1_CI_AI LIKE ? OR dc.name COLLATE SQL_Latin1_General_CP1_CI_AI LIKE ? OR op.name COLLATE SQL_Latin1_General_CP1_CI_AI LIKE ? OR pd.name COLLATE SQL_Latin1_General_CP1_CI_AI LIKE ?)
          AND fr.deleted_at IS NULL
        ORDER BY fr.base_price_economy ASC');
      $stmt->execute([$searchPattern, $searchPattern, $searchPattern, $searchPattern, $searchPattern, $searchPattern, $searchPattern, $searchPattern, $searchPattern]);
      $flights = $stmt->fetchAll();
      // #region agent log
      file_put_contents(__DIR__ . '/../../.cursor/debug.log', json_encode(['sessionId' => 'debug-session', 'runId' => 'run1', 'hypothesisId' => 'B', 'location' => 'search.php:151', 'message' => 'Flights search results', 'data' => ['count' => count($flights), 'first_result' => $flights[0] ?? null, 'query' => $query], 'timestamp' => time() * 1000]) . "\n", FILE_APPEND);
      // #endregion
      $results['flights'] = $flights;
    } catch (PDOException $e) {
      // #region agent log
      file_put_contents(__DIR__ . '/../../.cursor/debug.log', json_encode(['sessionId' => 'debug-session', 'runId' => 'run1', 'hypothesisId' => 'B', 'location' => 'search.php:148', 'message' => 'Flights search error', 'data' => ['error' => $e->getMessage(), 'query' => $query], 'timestamp' => time() * 1000]) . "\n", FILE_APPEND);
      // #endregion
      error_log('Search flights error: ' . $e->getMessage());
      $results['flights'] = [];
    }
  }
  
  // Search transfers - following flights pattern
  if (!$type || $type === 'transfers' || $type === 'all') {
    try {
      $searchTerm = str_replace(['%', '_', '[', ']'], ['[%]', '[_]', '[[]', '[]]'], $query);
      $searchPattern = '%' . $searchTerm . '%';
      
      $stmt = $pdo->prepare('SELECT TOP 50 tr.id, tr.base_price as price, tr.description,
        oc.name as origin_city_name, op.name as origin_province_name,
        dc.name as destination_city_name, dp.name as destination_province_name,
        tr.origin_specific, tr.destination_specific, \'transfer\' as type,
        (SELECT TOP 1 image_url FROM entity_images WHERE entity_type = \'transfer_route\' AND entity_id = tr.id ORDER BY display_order ASC, id ASC) as image_url
        FROM transfer_routes tr
        INNER JOIN cities oc ON tr.origin_city_id = oc.id
        INNER JOIN cities dc ON tr.destination_city_id = dc.id
        LEFT JOIN provinces op ON oc.province_id = op.id
        LEFT JOIN provinces dp ON dc.province_id = dp.id
        WHERE (oc.name COLLATE SQL_Latin1_General_CP1_CI_AI LIKE ? OR dc.name COLLATE SQL_Latin1_General_CP1_CI_AI LIKE ? OR op.name COLLATE SQL_Latin1_General_CP1_CI_AI LIKE ? OR dp.name COLLATE SQL_Latin1_General_CP1_CI_AI LIKE ? OR tr.origin_specific COLLATE SQL_Latin1_General_CP1_CI_AI LIKE ? OR tr.destination_specific COLLATE SQL_Latin1_General_CP1_CI_AI LIKE ? OR tr.description COLLATE SQL_Latin1_General_CP1_CI_AI LIKE ?)
          AND tr.deleted_at IS NULL
        ORDER BY tr.base_price ASC');
      $stmt->execute([$searchPattern, $searchPattern, $searchPattern, $searchPattern, $searchPattern, $searchPattern, $searchPattern]);
      $transfers = $stmt->fetchAll();
      
      // Add service field for display
      foreach ($transfers as &$transfer) {
        $transfer['service'] = ($transfer['origin_specific'] ?? $transfer['origin_city_name']) . ' to ' . ($transfer['destination_specific'] ?? $transfer['destination_city_name']);
      }
      
      // #region agent log
      file_put_contents(__DIR__ . '/../../.cursor/debug.log', json_encode(['sessionId' => 'debug-session', 'runId' => 'run1', 'hypothesisId' => 'D', 'location' => 'search.php:168', 'message' => 'Transfers search results', 'data' => ['count' => count($transfers), 'first_result' => $transfers[0] ?? null], 'timestamp' => time() * 1000]) . "\n", FILE_APPEND);
      // #endregion
      $results['transfers'] = $transfers;
    } catch (PDOException $e) {
      // #region agent log
      file_put_contents(__DIR__ . '/../../.cursor/debug.log', json_encode(['sessionId' => 'debug-session', 'runId' => 'run1', 'hypothesisId' => 'D', 'location' => 'search.php:170', 'message' => 'Transfers search error', 'data' => ['error' => $e->getMessage()], 'timestamp' => time() * 1000]) . "\n", FILE_APPEND);
      // #endregion
      error_log('Search transfers error: ' . $e->getMessage());
      $results['transfers'] = [];
    }
  }
  
  // Search activities - following hotels pattern
  if (!$type || $type === 'activities' || $type === 'all') {
    try {
      $searchTerm = str_replace(['%', '_', '[', ']'], ['[%]', '[_]', '[[]', '[]]'], $query);
      $searchPattern = '%' . $searchTerm . '%';
      
      $stmt = $pdo->prepare('SELECT a.*, c.name as city_name, p.name as province_name,
        (SELECT TOP 1 image_url FROM entity_images WHERE entity_type = \'activity\' AND entity_id = a.id ORDER BY display_order ASC, id ASC) as image_url
        FROM activities a
        LEFT JOIN cities c ON a.city_id = c.id
        LEFT JOIN provinces p ON c.province_id = p.id
        WHERE (a.title COLLATE SQL_Latin1_General_CP1_CI_AI LIKE ? OR c.name COLLATE SQL_Latin1_General_CP1_CI_AI LIKE ? OR p.name COLLATE SQL_Latin1_General_CP1_CI_AI LIKE ? OR a.description COLLATE SQL_Latin1_General_CP1_CI_AI LIKE ?)
          AND a.deleted_at IS NULL
        ORDER BY a.price ASC');
      $stmt->execute([$searchPattern, $searchPattern, $searchPattern, $searchPattern]);
      $activities = $stmt->fetchAll();
      
      // Add type field to each result
      foreach ($activities as &$activity) {
        $activity['type'] = 'activity';
      }
      
      $results['activities'] = $activities;
    } catch (PDOException $e) {
      error_log('Search activities error: ' . $e->getMessage());
      $results['activities'] = [];
    }
  }
  
    // Ensure results always has the requested type, even if empty
    if ($type && $type !== 'all') {
      // Validate type
      $validTypes = ['hotels', 'flights', 'destinations', 'transfers', 'activities'];
      if (!in_array($type, $validTypes)) {
        json_error('Invalid search type. Valid types: ' . implode(', ', $validTypes) . ', all', 400);
      }
      if (!isset($results[$type])) {
        $results[$type] = [];
      }
    }
    // #region agent log
    file_put_contents(__DIR__ . '/../../.cursor/debug.log', json_encode(['sessionId' => 'debug-session', 'runId' => 'run1', 'hypothesisId' => 'E', 'location' => 'search.php:215', 'message' => 'Search API final results', 'data' => ['result_keys' => array_keys($results), 'result_counts' => array_map('count', $results), 'type_requested' => $type], 'timestamp' => time() * 1000]) . "\n", FILE_APPEND);
    // #endregion
    error_log('Search API returning results: ' . json_encode(array_keys($results)) . ', results structure: ' . json_encode($results));
    json_ok(['query' => $query, 'results' => $results]);
  } catch (Throwable $e) {
    error_log('Search controller error: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
    error_log('Stack trace: ' . $e->getTraceAsString());
    json_error('Search failed: ' . $e->getMessage(), 500);
  }
}

json_error('Not found', 404);

