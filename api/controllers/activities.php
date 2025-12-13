<?php
// Activities controller
declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../middleware/auth.php';
require_once __DIR__ . '/../helpers/QueryBuilder.php';
require_once __DIR__ . '/../helpers/FilterHelper.php';
require_once __DIR__ . '/../helpers/ImageHelper.php';
require_once __DIR__ . '/../helpers/ResponseHelper.php';
require_once __DIR__ . '/../helpers/PromotionHelper.php';

try {
  $pdo = db_pdo();
} catch (Throwable $e) {
  ResponseHelper::error('Database connection failed', 500);
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
      WHERE a.id = ? AND a.deleted_at IS NULL');
    $stmt->execute([$id]);
    $activity = $stmt->fetch();
    if (!$activity) {
      ResponseHelper::error('Activity not found', 404);
    }
    // Fetch all images from entity_images
    $images = ImageHelper::getEntityImages($pdo, 'activity', $id);
    $activity = ImageHelper::addImageUrlToEntity($activity, $images);
    ResponseHelper::successSimple(['activity' => $activity]);
  }
  
  // List activities
  $pagination = FilterHelper::parsePagination();
  $page = $pagination['page'];
  $limit = $pagination['limit'];
  
  // Build base query
  $qb = new QueryBuilder();
  
  // Add joins first (needed for search and location filters)
  $qb->join('LEFT JOIN cities c ON a.city_id = c.id');
  $qb->join('LEFT JOIN provinces p ON c.province_id = p.id');
  
  // Add filters
  FilterHelper::addLocationFilters($qb, 'a');
  FilterHelper::addPriceFilters($qb, 'a.price');
  FilterHelper::addCreatedByFilter($qb, 'a');
  
  // Add destination_id filter
  $destination_id = isset($_GET['destination_id']) ? (int)$_GET['destination_id'] : null;
  if ($destination_id !== null) {
    $qb->where('a.destination_id = ?', $destination_id);
  }
  
  // Add date filter (activities specific)
  $date = $_GET['date'] ?? null;
  if ($date) {
    $qb->where('a.date >= ?', $date);
  }
  
  // Add search filter (uses joined tables c and p)
  FilterHelper::addSearchFilter($qb, ['a.title', 'c.name', 'p.name', 'a.description']);
  
  // Get promoted items
  $selectClause = "a.*, CAST(a.ad AS INT) as ad, c.name as city_name, p.name as province_name, p.region,
    (SELECT TOP 1 image_url FROM entity_images WHERE entity_type = 'activity' AND entity_id = a.id ORDER BY display_order ASC, id ASC) as image_url";
  $fromClause = "activities a";
  
  $promotedActivities = PromotionHelper::getPromotedItems($pdo, $qb, 'a', $selectClause, $fromClause, 2);
  $promotedIds = PromotionHelper::getPromotedIds($promotedActivities);
  
  // Build regular items query (excluding promoted)
  $regularQb = clone $qb;
  PromotionHelper::excludePromoted($regularQb, 'a', $promotedIds);
  $regularQb->orderBy("a.price ASC, a.id ASC");
  $regularQb->paginate($page, $limit);
  
  try {
    $sql = $regularQb->buildSelect($selectClause, $fromClause);
    $stmt = $pdo->prepare($sql);
    $stmt->execute($regularQb->getParams());
    $activities = $stmt->fetchAll();
    
    // Calculate discounted prices
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
    
    $response = [
      'page' => $page,
      'limit' => $limit,
      'promoted' => $promotedActivities,
      'results' => $activities
    ];
    ResponseHelper::successSimple($response);
  } catch (PDOException $e) {
    $errorMsg = 'Activities query failed: ' . $e->getMessage();
    if (isset($sql)) {
      $errorMsg .= ' | SQL: ' . $sql;
    }
    if (isset($regularQb)) {
      $errorMsg .= ' | Params: ' . json_encode($regularQb->getParams());
    }
    error_log($errorMsg);
    ResponseHelper::error('Database query failed', 500);
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
    ResponseHelper::error('Missing required fields: title, city_id, date, price', 400);
  }
  
  if ($price < 0) {
    ResponseHelper::error('Price must be positive', 400);
  }
  
  $createdBy = $user['role'] === 'admin' && isset($input['created_by']) ? (int)$input['created_by'] : $user['id'];
  
  $stmt = $pdo->prepare('INSERT INTO activities (title, destination_id, city_id, date, price, description, created_by) VALUES (?, ?, ?, ?, ?, ?, ?)');
  $stmt->execute([$title, $destination_id, $city_id, $date, $price, $description, $createdBy]);
  $activityId = (int)$pdo->lastInsertId();
  
  // Save images using ImageHelper
  if (!empty($images) && is_array($images)) {
    $normalizedImages = array_map(function($url) {
      return trim($url);
    }, array_filter($images));
    ImageHelper::saveEntityImages($pdo, 'activity', $activityId, $normalizedImages);
  }
  
  $stmt = $pdo->prepare('SELECT a.*, c.name as city_name, p.name as province_name, p.region 
    FROM activities a 
    LEFT JOIN cities c ON a.city_id = c.id 
    LEFT JOIN provinces p ON c.province_id = p.id 
    WHERE a.id = ?');
  $stmt->execute([$activityId]);
  $activity = $stmt->fetch();
  
  // Fetch all images from entity_images
  $allImages = ImageHelper::getEntityImages($pdo, 'activity', $activityId);
  $activity = ImageHelper::addImageUrlToEntity($activity, $allImages);
  
  http_response_code(201);
  ResponseHelper::successSimple(['activity' => $activity]);
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
    ResponseHelper::error('Activity not found', 404);
  }
  
  // Check permission
  if ($user['role'] !== 'admin' && $activity['created_by'] != $user['id']) {
    ResponseHelper::error('Forbidden', 403);
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
    ResponseHelper::error('No fields to update', 400);
  }
  
  if (!empty($updates)) {
    $params[] = $id;
    $sql = 'UPDATE activities SET ' . implode(', ', $updates) . ' WHERE id = ?';
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
  }
  
  // Update images if provided
  if ($images !== null && is_array($images)) {
    $normalizedImages = array_map(function($url) {
      return trim($url);
    }, array_filter($images));
    ImageHelper::saveEntityImages($pdo, 'activity', $id, $normalizedImages);
  }
  
  $stmt = $pdo->prepare('SELECT a.*, c.name as city_name, p.name as province_name, p.region 
    FROM activities a 
    LEFT JOIN cities c ON a.city_id = c.id 
    LEFT JOIN provinces p ON c.province_id = p.id 
    WHERE a.id = ?');
  $stmt->execute([$id]);
  $activity = $stmt->fetch();
  
  // Fetch all images from entity_images
  $allImages = ImageHelper::getEntityImages($pdo, 'activity', $id);
  $activity = ImageHelper::addImageUrlToEntity($activity, $allImages);
  
  ResponseHelper::successSimple(['activity' => $activity]);
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
    ResponseHelper::error('Activity not found', 404);
  }
  
  // Check permission
  if ($user['role'] !== 'admin' && $activity['created_by'] != $user['id']) {
    ResponseHelper::error('Forbidden', 403);
  }
  
  // Soft delete
  $stmt = $pdo->prepare('UPDATE activities SET deleted_at = SYSUTCDATETIME() WHERE id = ?');
  $stmt->execute([$id]);
  
  ResponseHelper::successSimple(['message' => 'Activity deleted successfully']);
}

ResponseHelper::error('Not found', 404);
