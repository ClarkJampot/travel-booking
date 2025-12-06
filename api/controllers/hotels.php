<?php
// Hotels controller
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

// GET /api/hotels
if ($_SERVER['REQUEST_METHOD'] === 'GET' && preg_match('#^/hotels/?$#', $uri)) {
  $id = isset($_GET['id']) ? (int)$_GET['id'] : null;
  
  // Get single hotel
  if ($id) {
    $stmt = $pdo->prepare('SELECT h.*, c.name as city_name, p.name as province_name, p.region 
      FROM hotels h 
      LEFT JOIN cities c ON h.city_id = c.id 
      LEFT JOIN provinces p ON h.province_id = p.id 
      WHERE h.id = ?');
    $stmt->execute([$id]);
    $hotel = $stmt->fetch();
    if (!$hotel) {
      json_error('Hotel not found', 404);
    }
    // Fetch all images from entity_images
    $images = get_entity_images($pdo, 'hotel', $id);
    $hotel['images'] = $images;
    // Add image_url for backward compatibility (first image)
    $hotel['image_url'] = $images[0] ?? null;
    json_ok(['hotel' => $hotel]);
  }
  
  // List hotels
  $city_id = isset($_GET['city_id']) ? (int)$_GET['city_id'] : null;
  $province_id = isset($_GET['province_id']) ? (int)$_GET['province_id'] : null;
  $destination_id = isset($_GET['destination_id']) ? (int)$_GET['destination_id'] : null;
  $minPrice = isset($_GET['minPrice']) ? (float)$_GET['minPrice'] : null;
  $maxPrice = isset($_GET['maxPrice']) ? (float)$_GET['maxPrice'] : null;
  $q = isset($_GET['q']) ? trim($_GET['q']) : null;
  $createdBy = isset($_GET['createdBy']) ? (int)$_GET['createdBy'] : null;
  $page = max(1, (int)($_GET['page'] ?? 1));
  $limit = min(50, max(1, (int)($_GET['limit'] ?? 10)));
  $offset = ($page - 1) * $limit;
  
  $where = [];
  $params = [];
  
  if ($city_id !== null) {
    $where[] = 'h.city_id = ?';
    $params[] = $city_id;
  }
  if ($province_id !== null) {
    $where[] = 'h.province_id = ?';
    $params[] = $province_id;
  }
  if ($destination_id !== null) {
    $where[] = 'h.destination_id = ?';
    $params[] = $destination_id;
  }
  if ($minPrice !== null) {
    $where[] = 'h.price_per_night >= ?';
    $params[] = $minPrice;
  }
  if ($maxPrice !== null) {
    $where[] = 'h.price_per_night <= ?';
    $params[] = $maxPrice;
  }
  if ($q) {
    // Escape special characters for SQL LIKE: %, _, [, ]
    $searchTerm = str_replace(['%', '_', '[', ']'], ['[%]', '[_]', '[[]', '[]]'], $q);
    $where[] = "(h.name COLLATE SQL_Latin1_General_CP1_CI_AI LIKE ? OR c.name COLLATE SQL_Latin1_General_CP1_CI_AI LIKE ? OR p.name COLLATE SQL_Latin1_General_CP1_CI_AI LIKE ? OR h.description COLLATE SQL_Latin1_General_CP1_CI_AI LIKE ?)";
    $searchPattern = '%' . $searchTerm . '%';
    $params[] = $searchPattern;
    $params[] = $searchPattern;
    $params[] = $searchPattern;
    $params[] = $searchPattern;
  }
  if ($createdBy !== null) {
    $where[] = 'h.created_by = ?';
    $params[] = $createdBy;
  }
  
  $whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';
  
  // Check if promotion columns exist by trying a simple query
  $promotedHotels = [];
  $promotedIds = [];
  
  try {
    // First, get 1-2 random promoted items matching filters
    $promotedWhere = array_merge(['h.ad = 1'], $where);
    $promotedWhereSql = 'WHERE ' . implode(' AND ', $promotedWhere);
    $promotedParams = $params;
    
    $promotedSql = "SELECT TOP 2 h.*, c.name as city_name, p.name as province_name, p.region,
      (SELECT TOP 1 image_url FROM entity_images WHERE entity_type = 'hotel' AND entity_id = h.id ORDER BY display_order ASC, id ASC) as image_url
      FROM hotels h 
      LEFT JOIN cities c ON h.city_id = c.id 
      LEFT JOIN provinces p ON h.province_id = p.id 
      $promotedWhereSql 
      ORDER BY NEWID()";
    
    $promotedStmt = $pdo->prepare($promotedSql);
    $promotedStmt->execute($promotedParams);
    $promotedHotels = $promotedStmt->fetchAll();
    
    // Get promoted IDs to exclude from regular results
    $promotedIds = array_column($promotedHotels, 'id');
  } catch (PDOException $e) {
    // If columns don't exist, just continue without promoted items
    error_log('Promoted hotels query failed (columns may not exist): ' . $e->getMessage());
    $promotedHotels = [];
    $promotedIds = [];
  }
  
  try {
    // Now get regular items (excluding ALL promoted ones)
    $regularWhere = $where;
    // Exclude all promoted items from regular results
    $regularWhere[] = "(h.ad = 0 OR h.ad IS NULL)";
    if (!empty($promotedIds)) {
      $placeholders = implode(',', array_fill(0, count($promotedIds), '?'));
      $regularWhere[] = "h.id NOT IN ($placeholders)";
      $regularParams = array_merge($params, $promotedIds);
    } else {
      $regularParams = $params;
    }
    
    $regularWhereSql = $regularWhere ? ('WHERE ' . implode(' AND ', $regularWhere)) : '';
    $offsetInt = (int)$offset;
    $limitInt = (int)$limit;
    
    // Try with promotion-aware ORDER BY first, fall back if columns don't exist
    $orderBy = "h.price_per_night ASC, h.id ASC";
    $sql = "SELECT h.*, c.name as city_name, p.name as province_name, p.region,
      (SELECT TOP 1 image_url FROM entity_images WHERE entity_type = 'hotel' AND entity_id = h.id ORDER BY display_order ASC, id ASC) as image_url
      FROM hotels h 
      LEFT JOIN cities c ON h.city_id = c.id 
      LEFT JOIN provinces p ON h.province_id = p.id 
      $regularWhereSql 
      ORDER BY $orderBy
      OFFSET $offsetInt ROWS FETCH NEXT $limitInt ROWS ONLY";
    
    try {
      $stmt = $pdo->prepare($sql);
      $stmt->execute($regularParams);
      $hotels = $stmt->fetchAll();
    } catch (PDOException $e) {
      // If query fails, try simpler version without promotion columns
      error_log('Hotels query failed, trying simpler version: ' . $e->getMessage());
      $sql = "SELECT h.*, c.name as city_name, p.name as province_name, p.region,
        (SELECT TOP 1 image_url FROM entity_images WHERE entity_type = 'hotel' AND entity_id = h.id ORDER BY display_order ASC, id ASC) as image_url
        FROM hotels h 
        LEFT JOIN cities c ON h.city_id = c.id 
        LEFT JOIN provinces p ON h.province_id = p.id 
        $regularWhereSql 
        ORDER BY h.price_per_night ASC, h.id ASC
        OFFSET $offsetInt ROWS FETCH NEXT $limitInt ROWS ONLY";
      $stmt = $pdo->prepare($sql);
      $stmt->execute($regularParams);
      $hotels = $stmt->fetchAll();
    }
    
    // Calculate discounted prices (only if discount_percent column exists)
    foreach ($promotedHotels as &$hotel) {
      if (isset($hotel['discount_percent']) && $hotel['discount_percent'] > 0) {
        $hotel['discounted_price'] = round($hotel['price_per_night'] * (1 - $hotel['discount_percent'] / 100), 2);
      }
    }
    foreach ($hotels as &$hotel) {
      if (isset($hotel['discount_percent']) && $hotel['discount_percent'] > 0) {
        $hotel['discounted_price'] = round($hotel['price_per_night'] * (1 - $hotel['discount_percent'] / 100), 2);
      }
    }
    
    json_ok(['page' => $page, 'limit' => $limit, 'promoted' => $promotedHotels, 'results' => $hotels]);
  } catch (PDOException $e) {
    error_log('Hotels query failed: ' . $e->getMessage() . ' | SQL: ' . ($sql ?? ''));
    json_error('Database query failed', 500);
  }
}

// POST /api/hotels (owner/admin only)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && preg_match('#^/hotels/?$#', $uri)) {
  requireRole(['owner', 'admin']);
  
  $user = get_authenticated_user();
  $input = json_decode(file_get_contents('php://input'), true);
  
  $name = trim($input['name'] ?? '');
  $destination_id = isset($input['destination_id']) ? (int)$input['destination_id'] : null;
  $city_id = isset($input['city_id']) ? (int)$input['city_id'] : null;
  $province_id = isset($input['province_id']) ? (int)$input['province_id'] : null;
  $price_per_night = isset($input['price_per_night']) ? (float)$input['price_per_night'] : null;
  $description = trim($input['description'] ?? '');
  $images = $input['images'] ?? []; // Array of image URLs
  
  if (!$name || $city_id === null || $province_id === null || $price_per_night === null) {
    json_error('Missing required fields: name, city_id, province_id, price_per_night', 400);
  }
  
  if ($price_per_night < 0) {
    json_error('Price must be positive', 400);
  }
  
  $createdBy = $user['role'] === 'admin' && isset($input['created_by']) ? (int)$input['created_by'] : $user['id'];
  
  $stmt = $pdo->prepare('INSERT INTO hotels (name, destination_id, city_id, province_id, price_per_night, description, created_by) VALUES (?, ?, ?, ?, ?, ?, ?)');
  $stmt->execute([$name, $destination_id, $city_id, $province_id, $price_per_night, $description, $createdBy]);
  $hotelId = (int)$pdo->lastInsertId();
  
  // Insert images into entity_images
  if (!empty($images) && is_array($images)) {
    $imgStmt = $pdo->prepare('INSERT INTO entity_images (entity_type, entity_id, image_url, display_order) VALUES (?, ?, ?, ?)');
    foreach ($images as $index => $imageUrl) {
      if (!empty($imageUrl)) {
        $imgStmt->execute(['hotel', $hotelId, trim($imageUrl), $index + 1]);
      }
    }
  }
  
  $stmt = $pdo->prepare('SELECT h.*, c.name as city_name, p.name as province_name, p.region 
    FROM hotels h 
    LEFT JOIN cities c ON h.city_id = c.id 
    LEFT JOIN provinces p ON h.province_id = p.id 
    WHERE h.id = ?');
  $stmt->execute([$hotelId]);
  $hotel = $stmt->fetch();
  
  // Fetch all images from entity_images
  $allImages = get_entity_images($pdo, 'hotel', $hotelId);
  $hotel['images'] = $allImages;
  $hotel['image_url'] = $allImages[0] ?? null;
  
  json_ok(['hotel' => $hotel], 201);
}

// PUT /api/hotels/:id (owner/admin only)
if ($_SERVER['REQUEST_METHOD'] === 'PUT' && preg_match('#^/hotels/(\d+)/?$#', $uri, $matches)) {
  requireRole(['owner', 'admin']);
  
  $id = (int)$matches[1];
  $user = get_authenticated_user();
  $input = json_decode(file_get_contents('php://input'), true);
  
  // Check if hotel exists
  $stmt = $pdo->prepare('SELECT created_by FROM hotels WHERE id = ?');
  $stmt->execute([$id]);
  $hotel = $stmt->fetch();
  if (!$hotel) {
    json_error('Hotel not found', 404);
  }
  
  // Check permission (owner can only edit their own, admin can edit any)
  if ($user['role'] !== 'admin' && $hotel['created_by'] != $user['id']) {
    json_error('Forbidden', 403);
  }
  
  $updates = [];
  $params = [];
  
  if (isset($input['name'])) {
    $updates[] = 'name = ?';
    $params[] = trim($input['name']);
  }
  if (isset($input['destination_id'])) {
    $updates[] = 'destination_id = ?';
    $params[] = (int)$input['destination_id'];
  }
  if (isset($input['city_id'])) {
    $updates[] = 'city_id = ?';
    $params[] = (int)$input['city_id'];
  }
  if (isset($input['province_id'])) {
    $updates[] = 'province_id = ?';
    $params[] = (int)$input['province_id'];
  }
  if (isset($input['price_per_night'])) {
    $updates[] = 'price_per_night = ?';
    $params[] = (float)$input['price_per_night'];
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
    $sql = 'UPDATE hotels SET ' . implode(', ', $updates) . ' WHERE id = ?';
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
  }
  
  // Update images if provided
  if ($images !== null && is_array($images)) {
    // Delete existing images
    $delStmt = $pdo->prepare('DELETE FROM entity_images WHERE entity_type = ? AND entity_id = ?');
    $delStmt->execute(['hotel', $id]);
    
    // Insert new images
    if (!empty($images)) {
      $imgStmt = $pdo->prepare('INSERT INTO entity_images (entity_type, entity_id, image_url, display_order) VALUES (?, ?, ?, ?)');
      foreach ($images as $index => $imageUrl) {
        if (!empty($imageUrl)) {
          $imgStmt->execute(['hotel', $id, trim($imageUrl), $index + 1]);
        }
      }
    }
  }
  
  $stmt = $pdo->prepare('SELECT h.*, c.name as city_name, p.name as province_name, p.region 
    FROM hotels h 
    LEFT JOIN cities c ON h.city_id = c.id 
    LEFT JOIN provinces p ON h.province_id = p.id 
    WHERE h.id = ?');
  $stmt->execute([$id]);
  $hotel = $stmt->fetch();
  
  // Fetch all images from entity_images
  $allImages = get_entity_images($pdo, 'hotel', $id);
  $hotel['images'] = $allImages;
  $hotel['image_url'] = $allImages[0] ?? null;
  
  json_ok(['hotel' => $hotel]);
}

// DELETE /api/hotels/:id (owner/admin only)
if ($_SERVER['REQUEST_METHOD'] === 'DELETE' && preg_match('#^/hotels/(\d+)/?$#', $uri, $matches)) {
  requireRole(['owner', 'admin']);
  
  $id = (int)$matches[1];
  $user = get_authenticated_user();
  
  // Check if hotel exists
  $stmt = $pdo->prepare('SELECT created_by FROM hotels WHERE id = ?');
  $stmt->execute([$id]);
  $hotel = $stmt->fetch();
  if (!$hotel) {
    json_error('Hotel not found', 404);
  }
  
  // Check permission
  if ($user['role'] !== 'admin' && $hotel['created_by'] != $user['id']) {
    json_error('Forbidden', 403);
  }
  
  $stmt = $pdo->prepare('DELETE FROM hotels WHERE id = ?');
  $stmt->execute([$id]);
  
  json_ok(['message' => 'Hotel deleted successfully']);
}

json_error('Not found', 404);
