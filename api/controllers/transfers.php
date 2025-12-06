<?php
// Transfers controller
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

// GET /api/transfers
if ($_SERVER['REQUEST_METHOD'] === 'GET' && preg_match('#^/transfers/?$#', $uri)) {
  $id = isset($_GET['id']) ? (int)$_GET['id'] : null;
  
  // Get single transfer
  if ($id) {
    $stmt = $pdo->prepare('SELECT * FROM transfers WHERE id = ?');
    $stmt->execute([$id]);
    $transfer = $stmt->fetch();
    if (!$transfer) {
      json_error('Transfer not found', 404);
    }
    // Fetch all images from entity_images
    $images = get_entity_images($pdo, 'transfer', $id);
    $transfer['images'] = $images;
    // Add image_url for backward compatibility (first image)
    $transfer['image_url'] = $images[0] ?? null;
    json_ok(['transfer' => $transfer]);
  }
  
  // List transfers
  $origin_city_id = isset($_GET['origin_city_id']) ? (int)$_GET['origin_city_id'] : null;
  $destination_city_id = isset($_GET['destination_city_id']) ? (int)$_GET['destination_city_id'] : null;
  $date = $_GET['date'] ?? null;
  $q = isset($_GET['q']) ? trim($_GET['q']) : null;
  $createdBy = isset($_GET['createdBy']) ? (int)$_GET['createdBy'] : null;
  $page = max(1, (int)($_GET['page'] ?? 1));
  $limit = min(50, max(1, (int)($_GET['limit'] ?? 10)));
  $offset = ($page - 1) * $limit;
  
  $where = [];
  $params = [];
  
  if ($origin_city_id !== null) {
    $where[] = 't.origin_city_id = ?';
    $params[] = $origin_city_id;
  }
  if ($destination_city_id !== null) {
    $where[] = 't.destination_city_id = ?';
    $params[] = $destination_city_id;
  }
  if ($date) {
    $where[] = 't.date >= ?';
    $params[] = $date;
  }
  if ($q) {
    // Escape special characters for SQL LIKE: %, _, [, ]
    $searchTerm = str_replace(['%', '_', '[', ']'], ['[%]', '[_]', '[[]', '[]]'], $q);
    $where[] = "(t.service COLLATE SQL_Latin1_General_CP1_CI_AI LIKE ? OR t.origin COLLATE SQL_Latin1_General_CP1_CI_AI LIKE ? OR t.destination COLLATE SQL_Latin1_General_CP1_CI_AI LIKE ? OR co.name COLLATE SQL_Latin1_General_CP1_CI_AI LIKE ? OR cd.name COLLATE SQL_Latin1_General_CP1_CI_AI LIKE ? OR po.name COLLATE SQL_Latin1_General_CP1_CI_AI LIKE ? OR pd.name COLLATE SQL_Latin1_General_CP1_CI_AI LIKE ? OR t.description COLLATE SQL_Latin1_General_CP1_CI_AI LIKE ?)";
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
    $where[] = 't.created_by = ?';
    $params[] = $createdBy;
  }
  
  $whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';
  
  // Check if promotion columns exist
  $promotedTransfers = [];
  $promotedIds = [];
  
  try {
    // First, get 1-2 random promoted items matching filters
    $promotedWhere = array_merge(['t.ad = 1'], $where);
    $promotedWhereSql = 'WHERE ' . implode(' AND ', $promotedWhere);
    $promotedParams = $params;
    
    $promotedSql = "SELECT TOP 2 t.*, 
      co.name as origin_city_name, po.name as origin_province_name,
      cd.name as destination_city_name, pd.name as destination_province_name,
      (SELECT TOP 1 image_url FROM entity_images WHERE entity_type = 'transfer' AND entity_id = t.id ORDER BY display_order ASC, id ASC) as image_url
      FROM transfers t
      LEFT JOIN cities co ON t.origin_city_id = co.id
      LEFT JOIN provinces po ON co.province_id = po.id
      LEFT JOIN cities cd ON t.destination_city_id = cd.id
      LEFT JOIN provinces pd ON cd.province_id = pd.id
      $promotedWhereSql 
      ORDER BY NEWID()";
    
    $promotedStmt = $pdo->prepare($promotedSql);
    $promotedStmt->execute($promotedParams);
    $promotedTransfers = $promotedStmt->fetchAll();
    
    // Get promoted IDs to exclude from regular results
    $promotedIds = array_column($promotedTransfers, 'id');
  } catch (PDOException $e) {
    // If columns don't exist, just continue without promoted items
    error_log('Promoted transfers query failed (columns may not exist): ' . $e->getMessage());
    $promotedTransfers = [];
    $promotedIds = [];
  }
  
  try {
    // Now get regular items (excluding ALL promoted ones)
    $regularWhere = $where;
    // Exclude all promoted items from regular results
    $regularWhere[] = "(t.ad = 0 OR t.ad IS NULL)";
    if (!empty($promotedIds)) {
      $placeholders = implode(',', array_fill(0, count($promotedIds), '?'));
      $regularWhere[] = "t.id NOT IN ($placeholders)";
      $regularParams = array_merge($params, $promotedIds);
    } else {
      $regularParams = $params;
    }
    
    $regularWhereSql = $regularWhere ? ('WHERE ' . implode(' AND ', $regularWhere)) : '';
    $offsetInt = (int)$offset;
    $limitInt = (int)$limit;
    
    // Try with promotion-aware ORDER BY first, fall back if columns don't exist
    $orderBy = "t.date ASC, t.price ASC, t.id ASC";
    $sql = "SELECT t.*, 
      co.name as origin_city_name, po.name as origin_province_name,
      cd.name as destination_city_name, pd.name as destination_province_name,
      (SELECT TOP 1 image_url FROM entity_images WHERE entity_type = 'transfer' AND entity_id = t.id ORDER BY display_order ASC, id ASC) as image_url
      FROM transfers t
      LEFT JOIN cities co ON t.origin_city_id = co.id
      LEFT JOIN provinces po ON co.province_id = po.id
      LEFT JOIN cities cd ON t.destination_city_id = cd.id
      LEFT JOIN provinces pd ON cd.province_id = pd.id
      $regularWhereSql 
      ORDER BY $orderBy
      OFFSET $offsetInt ROWS FETCH NEXT $limitInt ROWS ONLY";
    
    try {
      $stmt = $pdo->prepare($sql);
      $stmt->execute($regularParams);
      $transfers = $stmt->fetchAll();
    } catch (PDOException $e) {
      // If query fails, try simpler version
      error_log('Transfers query failed, trying simpler version: ' . $e->getMessage());
      $sql = "SELECT t.*, 
        co.name as origin_city_name, po.name as origin_province_name,
        cd.name as destination_city_name, pd.name as destination_province_name,
        (SELECT TOP 1 image_url FROM entity_images WHERE entity_type = 'transfer' AND entity_id = t.id ORDER BY display_order ASC, id ASC) as image_url
        FROM transfers t
        LEFT JOIN cities co ON t.origin_city_id = co.id
        LEFT JOIN provinces po ON co.province_id = po.id
        LEFT JOIN cities cd ON t.destination_city_id = cd.id
        LEFT JOIN provinces pd ON cd.province_id = pd.id
        $regularWhereSql 
        ORDER BY t.date ASC, t.price ASC, t.id ASC
        OFFSET $offsetInt ROWS FETCH NEXT $limitInt ROWS ONLY";
      $stmt = $pdo->prepare($sql);
      $stmt->execute($regularParams);
      $transfers = $stmt->fetchAll();
    }
    
    // Calculate discounted prices (only if discount_percent column exists)
    foreach ($promotedTransfers as &$transfer) {
      if (isset($transfer['discount_percent']) && $transfer['discount_percent'] > 0) {
        $transfer['discounted_price'] = round($transfer['price'] * (1 - $transfer['discount_percent'] / 100), 2);
      }
    }
    foreach ($transfers as &$transfer) {
      if (isset($transfer['discount_percent']) && $transfer['discount_percent'] > 0) {
        $transfer['discounted_price'] = round($transfer['price'] * (1 - $transfer['discount_percent'] / 100), 2);
      }
    }
    
    json_ok(['page' => $page, 'limit' => $limit, 'promoted' => $promotedTransfers, 'results' => $transfers]);
  } catch (PDOException $e) {
    error_log('Transfers query failed: ' . $e->getMessage() . ' | SQL: ' . ($sql ?? ''));
    json_error('Database query failed', 500);
  }
}

// POST /api/transfers (agency/admin only)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && preg_match('#^/transfers/?$#', $uri)) {
  requireRole(['agency', 'admin']);
  
  $user = get_authenticated_user();
  $input = json_decode(file_get_contents('php://input'), true);
  
  $service = trim($input['service'] ?? '');
  $origin = trim($input['origin'] ?? '');
  $destination = trim($input['destination'] ?? '');
  $date = $input['date'] ?? '';
  $price = isset($input['price']) ? (float)$input['price'] : null;
  $description = trim($input['description'] ?? '');
  $images = $input['images'] ?? []; // Array of image URLs
  
  if (!$service || !$origin || !$destination || !$date || $price === null) {
    json_error('Missing required fields: service, origin, destination, date, price', 400);
  }
  
  if ($price < 0) {
    json_error('Price must be positive', 400);
  }
  
  $createdBy = $user['role'] === 'admin' && isset($input['created_by']) ? (int)$input['created_by'] : $user['id'];
  
  $stmt = $pdo->prepare('INSERT INTO transfers (service, origin, destination, date, price, description, created_by) VALUES (?, ?, ?, ?, ?, ?, ?)');
  $stmt->execute([$service, $origin, $destination, $date, $price, $description, $createdBy]);
  $transferId = (int)$pdo->lastInsertId();
  
  // Insert images into entity_images
  if (!empty($images) && is_array($images)) {
    $imgStmt = $pdo->prepare('INSERT INTO entity_images (entity_type, entity_id, image_url, display_order) VALUES (?, ?, ?, ?)');
    foreach ($images as $index => $imageUrl) {
      if (!empty($imageUrl)) {
        $imgStmt->execute(['transfer', $transferId, trim($imageUrl), $index + 1]);
      }
    }
  }
  
  $stmt = $pdo->prepare('SELECT * FROM transfers WHERE id = ?');
  $stmt->execute([$transferId]);
  $transfer = $stmt->fetch();
  
  // Fetch all images from entity_images
  $allImages = get_entity_images($pdo, 'transfer', $transferId);
  $transfer['images'] = $allImages;
  $transfer['image_url'] = $allImages[0] ?? null;
  
  json_ok(['transfer' => $transfer], 201);
}

// PUT /api/transfers/:id (agency/admin only)
if ($_SERVER['REQUEST_METHOD'] === 'PUT' && preg_match('#^/transfers/(\d+)/?$#', $uri, $matches)) {
  requireRole(['agency', 'admin']);
  
  $id = (int)$matches[1];
  $user = get_authenticated_user();
  $input = json_decode(file_get_contents('php://input'), true);
  
  // Check if transfer exists
  $stmt = $pdo->prepare('SELECT created_by FROM transfers WHERE id = ?');
  $stmt->execute([$id]);
  $transfer = $stmt->fetch();
  if (!$transfer) {
    json_error('Transfer not found', 404);
  }
  
  // Check permission
  if ($user['role'] !== 'admin' && $transfer['created_by'] != $user['id']) {
    json_error('Forbidden', 403);
  }
  
  $updates = [];
  $params = [];
  
  if (isset($input['service'])) {
    $updates[] = 'service = ?';
    $params[] = trim($input['service']);
  }
  if (isset($input['origin'])) {
    $updates[] = 'origin = ?';
    $params[] = trim($input['origin']);
  }
  if (isset($input['destination'])) {
    $updates[] = 'destination = ?';
    $params[] = trim($input['destination']);
  }
  if (isset($input['date'])) {
    $updates[] = 'date = ?';
    $params[] = $input['date'];
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
    $sql = 'UPDATE transfers SET ' . implode(', ', $updates) . ' WHERE id = ?';
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
  }
  
  // Update images if provided
  if ($images !== null && is_array($images)) {
    // Delete existing images
    $delStmt = $pdo->prepare('DELETE FROM entity_images WHERE entity_type = ? AND entity_id = ?');
    $delStmt->execute(['transfer', $id]);
    
    // Insert new images
    if (!empty($images)) {
      $imgStmt = $pdo->prepare('INSERT INTO entity_images (entity_type, entity_id, image_url, display_order) VALUES (?, ?, ?, ?)');
      foreach ($images as $index => $imageUrl) {
        if (!empty($imageUrl)) {
          $imgStmt->execute(['transfer', $id, trim($imageUrl), $index + 1]);
        }
      }
    }
  }
  
  $stmt = $pdo->prepare('SELECT * FROM transfers WHERE id = ?');
  $stmt->execute([$id]);
  $transfer = $stmt->fetch();
  
  // Fetch all images from entity_images
  $allImages = get_entity_images($pdo, 'transfer', $id);
  $transfer['images'] = $allImages;
  $transfer['image_url'] = $allImages[0] ?? null;
  
  json_ok(['transfer' => $transfer]);
}

// DELETE /api/transfers/:id (agency/admin only)
if ($_SERVER['REQUEST_METHOD'] === 'DELETE' && preg_match('#^/transfers/(\d+)/?$#', $uri, $matches)) {
  requireRole(['agency', 'admin']);
  
  $id = (int)$matches[1];
  $user = get_authenticated_user();
  
  // Check if transfer exists
  $stmt = $pdo->prepare('SELECT created_by FROM transfers WHERE id = ?');
  $stmt->execute([$id]);
  $transfer = $stmt->fetch();
  if (!$transfer) {
    json_error('Transfer not found', 404);
  }
  
  // Check permission
  if ($user['role'] !== 'admin' && $transfer['created_by'] != $user['id']) {
    json_error('Forbidden', 403);
  }
  
  $stmt = $pdo->prepare('DELETE FROM transfers WHERE id = ?');
  $stmt->execute([$id]);
  
  json_ok(['message' => 'Transfer deleted successfully']);
}

json_error('Not found', 404);
