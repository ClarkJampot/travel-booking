<?php
// Flights controller
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

// GET /api/flights
if ($_SERVER['REQUEST_METHOD'] === 'GET' && preg_match('#^/flights/?$#', $uri)) {
  $id = isset($_GET['id']) ? (int)$_GET['id'] : null;
  
  // Get single flight
  if ($id) {
    $stmt = $pdo->prepare('SELECT * FROM flights WHERE id = ?');
    $stmt->execute([$id]);
    $flight = $stmt->fetch();
    if (!$flight) {
      json_error('Flight not found', 404);
    }
    // Fetch all images from entity_images
    $images = get_entity_images($pdo, 'flight', $id);
    $flight['images'] = $images;
    // Add image_url for backward compatibility (first image)
    $flight['image_url'] = $images[0] ?? null;
    json_ok(['flight' => $flight]);
  }
  
  // List flights
  $origin_city_id = isset($_GET['origin_city_id']) ? (int)$_GET['origin_city_id'] : null;
  $destination_city_id = isset($_GET['destination_city_id']) ? (int)$_GET['destination_city_id'] : null;
  $date = $_GET['date'] ?? null;
  $trip_type = $_GET['trip_type'] ?? null;
  $q = isset($_GET['q']) ? trim($_GET['q']) : null;
  $createdBy = isset($_GET['createdBy']) ? (int)$_GET['createdBy'] : null;
  $page = max(1, (int)($_GET['page'] ?? 1));
  $limit = min(50, max(1, (int)($_GET['limit'] ?? 10)));
  $offset = ($page - 1) * $limit;
  
  $where = [];
  $params = [];
  
  if ($origin_city_id !== null) {
    $where[] = 'f.origin_city_id = ?';
    $params[] = $origin_city_id;
  }
  if ($destination_city_id !== null) {
    $where[] = 'f.destination_city_id = ?';
    $params[] = $destination_city_id;
  }
  if ($date) {
    $where[] = 'f.depart_date >= ?';
    $params[] = $date;
  }
  // Only add trip_type filter if we're sure the column exists (will be caught in try-catch if not)
  if ($trip_type && in_array($trip_type, ['one-way', 'round-trip'])) {
    $where[] = 'f.trip_type = ?';
    $params[] = $trip_type;
  }
  if ($q) {
    // Escape special characters for SQL LIKE: %, _, [, ]
    $searchTerm = str_replace(['%', '_', '[', ']'], ['[%]', '[_]', '[[]', '[]]'], $q);
    $where[] = "(f.airline COLLATE SQL_Latin1_General_CP1_CI_AI LIKE ? OR f.origin COLLATE SQL_Latin1_General_CP1_CI_AI LIKE ? OR f.destination COLLATE SQL_Latin1_General_CP1_CI_AI LIKE ? OR co.name COLLATE SQL_Latin1_General_CP1_CI_AI LIKE ? OR cd.name COLLATE SQL_Latin1_General_CP1_CI_AI LIKE ? OR po.name COLLATE SQL_Latin1_General_CP1_CI_AI LIKE ? OR pd.name COLLATE SQL_Latin1_General_CP1_CI_AI LIKE ? OR f.description COLLATE SQL_Latin1_General_CP1_CI_AI LIKE ?)";
    $searchPattern = '%' . $searchTerm . '%';
    $params[] = $searchPattern;
    $params[] = $searchPattern;
    $params[] = $searchPattern;
    $params[] = $searchPattern;
    $params[] = $searchPattern;
    $params[] = $searchPattern;
    $params[] = $searchPattern;
    $params[] = $searchPattern;
  }
  if ($createdBy !== null) {
    $where[] = 'f.created_by = ?';
    $params[] = $createdBy;
  }
  
  $whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';
  
  // Check if promotion columns exist
  $promotedFlights = [];
  $promotedIds = [];
  
  try {
    // First, get 1-2 random promoted items matching filters
    $promotedWhere = array_merge(['f.ad = 1'], $where);
    $promotedWhereSql = 'WHERE ' . implode(' AND ', $promotedWhere);
    $promotedParams = $params;
    
    $promotedSql = "SELECT TOP 2 f.*, CAST(f.ad AS INT) as ad,
      co.name as origin_city_name, po.name as origin_province_name,
      cd.name as destination_city_name, pd.name as destination_province_name,
      (SELECT TOP 1 image_url FROM entity_images WHERE entity_type = 'flight' AND entity_id = f.id ORDER BY display_order ASC, id ASC) as image_url
      FROM flights f
      LEFT JOIN cities co ON f.origin_city_id = co.id
      LEFT JOIN provinces po ON co.province_id = po.id
      LEFT JOIN cities cd ON f.destination_city_id = cd.id
      LEFT JOIN provinces pd ON cd.province_id = pd.id
      $promotedWhereSql 
      ORDER BY NEWID()";
    
    $promotedStmt = $pdo->prepare($promotedSql);
    $promotedStmt->execute($promotedParams);
    $promotedFlights = $promotedStmt->fetchAll();
    
    // Get promoted IDs to exclude from regular results
    $promotedIds = array_column($promotedFlights, 'id');
  } catch (PDOException $e) {
    // If columns don't exist, just continue without promoted items
    error_log('Promoted flights query failed (columns may not exist): ' . $e->getMessage());
    $promotedFlights = [];
    $promotedIds = [];
  }
  
  try {
    // Now get regular items (excluding ALL promoted ones)
    $regularWhere = $where;
    // Exclude all promoted items from regular results
    $regularWhere[] = "(f.ad = 0 OR f.ad IS NULL)";
    if (!empty($promotedIds)) {
      $placeholders = implode(',', array_fill(0, count($promotedIds), '?'));
      $regularWhere[] = "f.id NOT IN ($placeholders)";
      $regularParams = array_merge($params, $promotedIds);
    } else {
      $regularParams = $params;
    }
    
    $regularWhereSql = $regularWhere ? ('WHERE ' . implode(' AND ', $regularWhere)) : '';
    $offsetInt = (int)$offset;
    $limitInt = (int)$limit;
    
    // Try with promotion-aware ORDER BY first, fall back if columns don't exist
    $orderBy = "f.depart_date ASC, f.price ASC, f.id ASC";
    $sql = "SELECT f.*, CAST(f.ad AS INT) as ad,
      co.name as origin_city_name, po.name as origin_province_name,
      cd.name as destination_city_name, pd.name as destination_province_name,
      (SELECT TOP 1 image_url FROM entity_images WHERE entity_type = 'flight' AND entity_id = f.id ORDER BY display_order ASC, id ASC) as image_url
      FROM flights f
      LEFT JOIN cities co ON f.origin_city_id = co.id
      LEFT JOIN provinces po ON co.province_id = po.id
      LEFT JOIN cities cd ON f.destination_city_id = cd.id
      LEFT JOIN provinces pd ON cd.province_id = pd.id
      $regularWhereSql 
      ORDER BY $orderBy
      OFFSET $offsetInt ROWS FETCH NEXT $limitInt ROWS ONLY";
    
    try {
      $stmt = $pdo->prepare($sql);
      $stmt->execute($regularParams);
      $flights = $stmt->fetchAll();
    } catch (PDOException $e) {
      // If query fails (maybe trip_type column doesn't exist), rebuild WHERE without trip_type
      error_log('Flights query failed, trying simpler version: ' . $e->getMessage());
      $simpleWhere = [];
      $simpleParams = [];
      foreach ($where as $i => $condition) {
        if (strpos($condition, 'trip_type') === false) {
          $simpleWhere[] = $condition;
          $simpleParams[] = $params[$i];
        }
      }
      // Exclude promoted IDs if any
      if (!empty($promotedIds)) {
        $placeholders = implode(',', array_fill(0, count($promotedIds), '?'));
        $simpleWhere[] = "f.id NOT IN ($placeholders)";
        $simpleParams = array_merge($simpleParams, $promotedIds);
      }
      $simpleWhereSql = $simpleWhere ? ('WHERE ' . implode(' AND ', $simpleWhere)) : '';
      $sql = "SELECT f.*, 
        co.name as origin_city_name, po.name as origin_province_name,
        cd.name as destination_city_name, pd.name as destination_province_name,
        (SELECT TOP 1 image_url FROM entity_images WHERE entity_type = 'flight' AND entity_id = f.id ORDER BY display_order ASC, id ASC) as image_url
        FROM flights f
        LEFT JOIN cities co ON f.origin_city_id = co.id
        LEFT JOIN provinces po ON co.province_id = po.id
        LEFT JOIN cities cd ON f.destination_city_id = cd.id
        LEFT JOIN provinces pd ON cd.province_id = pd.id
        $simpleWhereSql 
        ORDER BY f.depart_date ASC, f.price ASC, f.id ASC
        OFFSET $offsetInt ROWS FETCH NEXT $limitInt ROWS ONLY";
      $stmt = $pdo->prepare($sql);
      $stmt->execute($simpleParams);
      $flights = $stmt->fetchAll();
    }
    
    // Calculate discounted prices (only if discount_percent column exists)
    foreach ($promotedFlights as &$flight) {
      if (isset($flight['discount_percent']) && $flight['discount_percent'] > 0) {
        $flight['discounted_price'] = round($flight['price'] * (1 - $flight['discount_percent'] / 100), 2);
      }
    }
    foreach ($flights as &$flight) {
      if (isset($flight['discount_percent']) && $flight['discount_percent'] > 0) {
        $flight['discounted_price'] = round($flight['price'] * (1 - $flight['discount_percent'] / 100), 2);
      }
    }
    
    json_ok(['page' => $page, 'limit' => $limit, 'promoted' => $promotedFlights, 'results' => $flights]);
  } catch (PDOException $e) {
    error_log('Flights query failed: ' . $e->getMessage() . ' | SQL: ' . ($sql ?? ''));
    json_error('Database query failed', 500);
  }
}

// POST /api/flights (agency/admin only)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && preg_match('#^/flights/?$#', $uri)) {
  requireRole(['agency', 'admin']);
  
  $user = get_authenticated_user();
  $input = json_decode(file_get_contents('php://input'), true);
  
  $airline = trim($input['airline'] ?? '');
  $origin = trim($input['origin'] ?? '');
  $destination = trim($input['destination'] ?? '');
  $depart_date = $input['depart_date'] ?? '';
  $price = isset($input['price']) ? (float)$input['price'] : null;
  $description = trim($input['description'] ?? '');
  $images = $input['images'] ?? []; // Array of image URLs
  
  if (!$airline || !$origin || !$destination || !$depart_date || $price === null) {
    json_error('Missing required fields: airline, origin, destination, depart_date, price', 400);
  }
  
  if ($price < 0) {
    json_error('Price must be positive', 400);
  }
  
  $createdBy = $user['role'] === 'admin' && isset($input['created_by']) ? (int)$input['created_by'] : $user['id'];
  
  $stmt = $pdo->prepare('INSERT INTO flights (airline, origin, destination, depart_date, price, description, created_by) VALUES (?, ?, ?, ?, ?, ?, ?)');
  $stmt->execute([$airline, $origin, $destination, $depart_date, $price, $description, $createdBy]);
  $flightId = (int)$pdo->lastInsertId();
  
  // Insert images into entity_images
  if (!empty($images) && is_array($images)) {
    $imgStmt = $pdo->prepare('INSERT INTO entity_images (entity_type, entity_id, image_url, display_order) VALUES (?, ?, ?, ?)');
    foreach ($images as $index => $imageUrl) {
      if (!empty($imageUrl)) {
        $imgStmt->execute(['flight', $flightId, trim($imageUrl), $index + 1]);
      }
    }
  }
  
  $stmt = $pdo->prepare('SELECT * FROM flights WHERE id = ?');
  $stmt->execute([$flightId]);
  $flight = $stmt->fetch();
  
  // Fetch all images from entity_images
  $allImages = get_entity_images($pdo, 'flight', $flightId);
  $flight['images'] = $allImages;
  $flight['image_url'] = $allImages[0] ?? null;
  
  json_ok(['flight' => $flight], 201);
}

// PUT /api/flights/:id (agency/admin only)
if ($_SERVER['REQUEST_METHOD'] === 'PUT' && preg_match('#^/flights/(\d+)/?$#', $uri, $matches)) {
  requireRole(['agency', 'admin']);
  
  $id = (int)$matches[1];
  $user = get_authenticated_user();
  $input = json_decode(file_get_contents('php://input'), true);
  
  // Check if flight exists
  $stmt = $pdo->prepare('SELECT created_by FROM flights WHERE id = ?');
  $stmt->execute([$id]);
  $flight = $stmt->fetch();
  if (!$flight) {
    json_error('Flight not found', 404);
  }
  
  // Check permission
  if ($user['role'] !== 'admin' && $flight['created_by'] != $user['id']) {
    json_error('Forbidden', 403);
  }
  
  $updates = [];
  $params = [];
  
  if (isset($input['airline'])) {
    $updates[] = 'airline = ?';
    $params[] = trim($input['airline']);
  }
  if (isset($input['origin'])) {
    $updates[] = 'origin = ?';
    $params[] = trim($input['origin']);
  }
  if (isset($input['destination'])) {
    $updates[] = 'destination = ?';
    $params[] = trim($input['destination']);
  }
  if (isset($input['depart_date'])) {
    $updates[] = 'depart_date = ?';
    $params[] = $input['depart_date'];
  }
  if (isset($input['price'])) {
    $updates[] = 'price = ?';
    $params[] = (float)$input['price'];
  }
  if (isset($input['description'])) {
    $updates[] = 'description = ?';
    $params[] = trim($input['description']);
  }
  
  // Handle images update (replace all existing images)
  $images = $input['images'] ?? null;
  
  if (empty($updates) && $images === null) {
    json_error('No fields to update', 400);
  }
  
  if (!empty($updates)) {
    $params[] = $id;
    $sql = 'UPDATE flights SET ' . implode(', ', $updates) . ' WHERE id = ?';
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
  }
  
  // Update images if provided
  if ($images !== null && is_array($images)) {
    // Delete existing images
    $delStmt = $pdo->prepare('DELETE FROM entity_images WHERE entity_type = ? AND entity_id = ?');
    $delStmt->execute(['flight', $id]);
    
    // Insert new images
    if (!empty($images)) {
      $imgStmt = $pdo->prepare('INSERT INTO entity_images (entity_type, entity_id, image_url, display_order) VALUES (?, ?, ?, ?)');
      foreach ($images as $index => $imageUrl) {
        if (!empty($imageUrl)) {
          $imgStmt->execute(['flight', $id, trim($imageUrl), $index + 1]);
        }
      }
    }
  }
  
  $stmt = $pdo->prepare('SELECT * FROM flights WHERE id = ?');
  $stmt->execute([$id]);
  $flight = $stmt->fetch();
  
  // Fetch all images from entity_images
  $allImages = get_entity_images($pdo, 'flight', $id);
  $flight['images'] = $allImages;
  $flight['image_url'] = $allImages[0] ?? null;
  
  json_ok(['flight' => $flight]);
}

// DELETE /api/flights/:id (agency/admin only)
if ($_SERVER['REQUEST_METHOD'] === 'DELETE' && preg_match('#^/flights/(\d+)/?$#', $uri, $matches)) {
  requireRole(['agency', 'admin']);
  
  $id = (int)$matches[1];
  $user = get_authenticated_user();
  
  // Check if flight exists
  $stmt = $pdo->prepare('SELECT created_by FROM flights WHERE id = ?');
  $stmt->execute([$id]);
  $flight = $stmt->fetch();
  if (!$flight) {
    json_error('Flight not found', 404);
  }
  
  // Check permission
  if ($user['role'] !== 'admin' && $flight['created_by'] != $user['id']) {
    json_error('Forbidden', 403);
  }
  
  $stmt = $pdo->prepare('DELETE FROM flights WHERE id = ?');
  $stmt->execute([$id]);
  
  json_ok(['message' => 'Flight deleted successfully']);
}

json_error('Not found', 404);
