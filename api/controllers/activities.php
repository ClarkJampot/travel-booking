<?php
// Activities controller
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

// GET /api/activities
if ($_SERVER['REQUEST_METHOD'] === 'GET' && preg_match('#^/activities/?$#', $uri)) {
  $id = isset($_GET['id']) ? (int)$_GET['id'] : null;
  
  // Get single activity
  if ($id) {
    $stmt = $pdo->prepare('SELECT a.*, c.name as city_name, p.name as province_name, p.region 
      FROM activities a 
      LEFT JOIN cities c ON a.city_id = c.id 
      LEFT JOIN provinces p ON c.province_id = p.id 
      WHERE a.id = ?');
    $stmt->execute([$id]);
    $activity = $stmt->fetch();
    if (!$activity) {
      json_error('Activity not found', 404);
    }
    // Fetch all images from entity_images
    $images = get_entity_images($pdo, 'activity', $id);
    $activity['images'] = $images;
    // Add image_url for backward compatibility (first image)
    $activity['image_url'] = $images[0] ?? null;
    json_ok(['activity' => $activity]);
  }
  
  // List activities
  $city_id = isset($_GET['city_id']) ? (int)$_GET['city_id'] : null;
  $destination_id = isset($_GET['destination_id']) ? (int)$_GET['destination_id'] : null;
  $date = $_GET['date'] ?? null;
  $q = isset($_GET['q']) ? trim($_GET['q']) : null;
  $createdBy = isset($_GET['createdBy']) ? (int)$_GET['createdBy'] : null;
  $page = max(1, (int)($_GET['page'] ?? 1));
  $limit = min(50, max(1, (int)($_GET['limit'] ?? 10)));
  $offset = ($page - 1) * $limit;
  
  $where = [];
  $params = [];
  
  if ($city_id !== null) {
    $where[] = 'a.city_id = ?';
    $params[] = $city_id;
  }
  if ($destination_id !== null) {
    $where[] = 'a.destination_id = ?';
    $params[] = $destination_id;
  }
  if ($date) {
    $where[] = 'a.date >= ?';
    $params[] = $date;
  }
  if ($q) {
    // Escape special characters for SQL LIKE: %, _, [, ]
    $searchTerm = str_replace(['%', '_', '[', ']'], ['[%]', '[_]', '[[]', '[]]'], $q);
    $where[] = "(a.title COLLATE SQL_Latin1_General_CP1_CI_AI LIKE ? OR c.name COLLATE SQL_Latin1_General_CP1_CI_AI LIKE ? OR p.name COLLATE SQL_Latin1_General_CP1_CI_AI LIKE ? OR a.description COLLATE SQL_Latin1_General_CP1_CI_AI LIKE ?)";
    $searchPattern = '%' . $searchTerm . '%';
    $params[] = $searchPattern;
    $params[] = $searchPattern;
    $params[] = $searchPattern;
    $params[] = $searchPattern;
  }
  if ($createdBy !== null) {
    $where[] = 'a.created_by = ?';
    $params[] = $createdBy;
  }
  
  $whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';
  
  // Check if promotion columns exist
  $promotedActivities = [];
  $promotedIds = [];
  
  try {
    // First, get 1-2 random promoted items matching filters
    $promotedWhere = array_merge(['a.ad = 1'], $where);
    $promotedWhereSql = 'WHERE ' . implode(' AND ', $promotedWhere);
    $promotedParams = $params;
    
    $promotedSql = "SELECT TOP 2 a.*, CAST(a.ad AS INT) as ad, c.name as city_name, p.name as province_name, p.region,
      (SELECT TOP 1 image_url FROM entity_images WHERE entity_type = 'activity' AND entity_id = a.id ORDER BY display_order ASC, id ASC) as image_url
      FROM activities a 
      LEFT JOIN cities c ON a.city_id = c.id 
      LEFT JOIN provinces p ON c.province_id = p.id 
      $promotedWhereSql 
      ORDER BY NEWID()";
    
    $promotedStmt = $pdo->prepare($promotedSql);
    $promotedStmt->execute($promotedParams);
    $promotedActivities = $promotedStmt->fetchAll();
    
    // Get promoted IDs to exclude from regular results
    $promotedIds = array_column($promotedActivities, 'id');
  } catch (PDOException $e) {
    // If columns don't exist, just continue without promoted items
    error_log('Promoted activities query failed (columns may not exist): ' . $e->getMessage());
    $promotedActivities = [];
    $promotedIds = [];
  }
  
  try {
    // Now get regular items (excluding ALL promoted ones)
    $regularWhere = $where;
    // Exclude all promoted items from regular results
    $regularWhere[] = "(a.ad = 0 OR a.ad IS NULL)";
    if (!empty($promotedIds)) {
      $placeholders = implode(',', array_fill(0, count($promotedIds), '?'));
      $regularWhere[] = "a.id NOT IN ($placeholders)";
      $regularParams = array_merge($params, $promotedIds);
    } else {
      $regularParams = $params;
    }
    
    $regularWhereSql = $regularWhere ? ('WHERE ' . implode(' AND ', $regularWhere)) : '';
    $offsetInt = (int)$offset;
    $limitInt = (int)$limit;
    
    // Try with promotion-aware ORDER BY first, fall back if columns don't exist
    $orderBy = "a.date ASC, a.price ASC, a.id ASC";
    $sql = "SELECT a.*, CAST(a.ad AS INT) as ad, c.name as city_name, p.name as province_name, p.region,
      (SELECT TOP 1 image_url FROM entity_images WHERE entity_type = 'activity' AND entity_id = a.id ORDER BY display_order ASC, id ASC) as image_url
      FROM activities a 
      LEFT JOIN cities c ON a.city_id = c.id 
      LEFT JOIN provinces p ON c.province_id = p.id 
      $regularWhereSql 
      ORDER BY $orderBy
      OFFSET $offsetInt ROWS FETCH NEXT $limitInt ROWS ONLY";
    
    try {
      $stmt = $pdo->prepare($sql);
      $stmt->execute($regularParams);
      $activities = $stmt->fetchAll();
    } catch (PDOException $e) {
      // If query fails, try simpler version
      error_log('Activities query failed, trying simpler version: ' . $e->getMessage());
      $sql = "SELECT a.*, CAST(a.ad AS INT) as ad, c.name as city_name, p.name as province_name, p.region,
        (SELECT TOP 1 image_url FROM entity_images WHERE entity_type = 'activity' AND entity_id = a.id ORDER BY display_order ASC, id ASC) as image_url
        FROM activities a 
        LEFT JOIN cities c ON a.city_id = c.id 
        LEFT JOIN provinces p ON c.province_id = p.id 
        $regularWhereSql 
        ORDER BY a.date ASC, a.price ASC, a.id ASC
        OFFSET $offsetInt ROWS FETCH NEXT $limitInt ROWS ONLY";
      $stmt = $pdo->prepare($sql);
      $stmt->execute($regularParams);
      $activities = $stmt->fetchAll();
    }
    
    // Calculate discounted prices (only if discount_percent column exists)
    foreach ($promotedActivities as &$activity) {
      if (isset($activity['discount_percent']) && $activity['discount_percent'] > 0) {
        $activity['discounted_price'] = round($activity['price'] * (1 - $activity['discount_percent'] / 100), 2);
      }
    }
    foreach ($activities as &$activity) {
      if (isset($activity['discount_percent']) && $activity['discount_percent'] > 0) {
        $activity['discounted_price'] = round($activity['price'] * (1 - $activity['discount_percent'] / 100), 2);
      }
    }
    
    json_ok(['page' => $page, 'limit' => $limit, 'promoted' => $promotedActivities, 'results' => $activities]);
  } catch (PDOException $e) {
    error_log('Activities query failed: ' . $e->getMessage() . ' | SQL: ' . ($sql ?? ''));
    json_error('Database query failed', 500);
  }
}

// POST /api/activities (agency/admin only)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && preg_match('#^/activities/?$#', $uri)) {
  requireRole(['agency', 'admin']);
  
  $user = get_authenticated_user();
  $input = json_decode(file_get_contents('php://input'), true);
  
  $title = trim($input['title'] ?? '');
  $destination_id = isset($input['destination_id']) ? (int)$input['destination_id'] : null;
  $city_id = isset($input['city_id']) ? (int)$input['city_id'] : null;
  $date = $input['date'] ?? '';
  $price = isset($input['price']) ? (float)$input['price'] : null;
  $description = trim($input['description'] ?? '');
  $images = $input['images'] ?? []; // Array of image URLs
  
  if (!$title || $city_id === null || !$date || $price === null) {
    json_error('Missing required fields: title, city_id, date, price', 400);
  }
  
  if ($price < 0) {
    json_error('Price must be positive', 400);
  }
  
  $createdBy = $user['role'] === 'admin' && isset($input['created_by']) ? (int)$input['created_by'] : $user['id'];
  
  $stmt = $pdo->prepare('INSERT INTO activities (title, destination_id, city_id, date, price, description, created_by) VALUES (?, ?, ?, ?, ?, ?, ?)');
  $stmt->execute([$title, $destination_id, $city_id, $date, $price, $description, $createdBy]);
  $activityId = (int)$pdo->lastInsertId();
  
  // Insert images into entity_images
  if (!empty($images) && is_array($images)) {
    $imgStmt = $pdo->prepare('INSERT INTO entity_images (entity_type, entity_id, image_url, display_order) VALUES (?, ?, ?, ?)');
    foreach ($images as $index => $imageUrl) {
      if (!empty($imageUrl)) {
        $imgStmt->execute(['activity', $activityId, trim($imageUrl), $index + 1]);
      }
    }
  }
  
  $stmt = $pdo->prepare('SELECT a.*, c.name as city_name, p.name as province_name, p.region 
    FROM activities a 
    LEFT JOIN cities c ON a.city_id = c.id 
    LEFT JOIN provinces p ON c.province_id = p.id 
    WHERE a.id = ?');
  $stmt->execute([$activityId]);
  $activity = $stmt->fetch();
  
  // Fetch all images from entity_images
  $allImages = get_entity_images($pdo, 'activity', $activityId);
  $activity['images'] = $allImages;
  $activity['image_url'] = $allImages[0] ?? null;
  
  json_ok(['activity' => $activity], 201);
}

// PUT /api/activities/:id (agency/admin only)
if ($_SERVER['REQUEST_METHOD'] === 'PUT' && preg_match('#^/activities/(\d+)/?$#', $uri, $matches)) {
  requireRole(['agency', 'admin']);
  
  $id = (int)$matches[1];
  $user = get_authenticated_user();
  $input = json_decode(file_get_contents('php://input'), true);
  
  // Check if activity exists
  $stmt = $pdo->prepare('SELECT created_by FROM activities WHERE id = ?');
  $stmt->execute([$id]);
  $activity = $stmt->fetch();
  if (!$activity) {
    json_error('Activity not found', 404);
  }
  
  // Check permission
  if ($user['role'] !== 'admin' && $activity['created_by'] != $user['id']) {
    json_error('Forbidden', 403);
  }
  
  $updates = [];
  $params = [];
  
  if (isset($input['title'])) {
    $updates[] = 'title = ?';
    $params[] = trim($input['title']);
  }
  if (isset($input['destination_id'])) {
    $updates[] = 'destination_id = ?';
    $params[] = (int)$input['destination_id'];
  }
  if (isset($input['city_id'])) {
    $updates[] = 'city_id = ?';
    $params[] = (int)$input['city_id'];
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
    $sql = 'UPDATE activities SET ' . implode(', ', $updates) . ' WHERE id = ?';
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
  }
  
  // Update images if provided
  if ($images !== null && is_array($images)) {
    // Delete existing images
    $delStmt = $pdo->prepare('DELETE FROM entity_images WHERE entity_type = ? AND entity_id = ?');
    $delStmt->execute(['activity', $id]);
    
    // Insert new images
    if (!empty($images)) {
      $imgStmt = $pdo->prepare('INSERT INTO entity_images (entity_type, entity_id, image_url, display_order) VALUES (?, ?, ?, ?)');
      foreach ($images as $index => $imageUrl) {
        if (!empty($imageUrl)) {
          $imgStmt->execute(['activity', $id, trim($imageUrl), $index + 1]);
        }
      }
    }
  }
  
  $stmt = $pdo->prepare('SELECT a.*, c.name as city_name, p.name as province_name, p.region 
    FROM activities a 
    LEFT JOIN cities c ON a.city_id = c.id 
    LEFT JOIN provinces p ON c.province_id = p.id 
    WHERE a.id = ?');
  $stmt->execute([$id]);
  $activity = $stmt->fetch();
  
  // Fetch all images from entity_images
  $allImages = get_entity_images($pdo, 'activity', $id);
  $activity['images'] = $allImages;
  $activity['image_url'] = $allImages[0] ?? null;
  
  json_ok(['activity' => $activity]);
}

// DELETE /api/activities/:id (agency/admin only)
if ($_SERVER['REQUEST_METHOD'] === 'DELETE' && preg_match('#^/activities/(\d+)/?$#', $uri, $matches)) {
  requireRole(['agency', 'admin']);
  
  $id = (int)$matches[1];
  $user = get_authenticated_user();
  
  // Check if activity exists
  $stmt = $pdo->prepare('SELECT created_by FROM activities WHERE id = ?');
  $stmt->execute([$id]);
  $activity = $stmt->fetch();
  if (!$activity) {
    json_error('Activity not found', 404);
  }
  
  // Check permission
  if ($user['role'] !== 'admin' && $activity['created_by'] != $user['id']) {
    json_error('Forbidden', 403);
  }
  
  $stmt = $pdo->prepare('DELETE FROM activities WHERE id = ?');
  $stmt->execute([$id]);
  
  json_ok(['message' => 'Activity deleted successfully']);
}

json_error('Not found', 404);
