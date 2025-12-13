<?php
// Hotels controller
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
      WHERE h.id = ? AND h.deleted_at IS NULL');
    $stmt->execute([$id]);
    $hotel = $stmt->fetch();
    if (!$hotel) {
      ResponseHelper::error('Hotel not found', 404);
    }
    // Fetch all images from entity_images
    $images = ImageHelper::getEntityImages($pdo, 'hotel', $id);
    $hotel = ImageHelper::addImageUrlToEntity($hotel, $images);
    ResponseHelper::successSimple(['hotel' => $hotel]);
  }
  
  // List hotels
  $pagination = FilterHelper::parsePagination();
  $page = $pagination['page'];
  $limit = $pagination['limit'];
  
  // Build base query
  $qb = new QueryBuilder();
  
  // Add joins first (needed for search and location filters)
  $qb->join('LEFT JOIN cities c ON h.city_id = c.id');
  $qb->join('LEFT JOIN provinces p ON h.province_id = p.id');
  
  // Add filters
  FilterHelper::addLocationFilters($qb, 'h');
  FilterHelper::addPriceFilters($qb, 'h.price_per_night');
  FilterHelper::addCreatedByFilter($qb, 'h');
  
  // Add destination_id filter
  $destination_id = isset($_GET['destination_id']) ? (int)$_GET['destination_id'] : null;
  if ($destination_id !== null) {
    $qb->where('h.destination_id = ?', $destination_id);
  }
  
  // Add search filter (uses joined tables c and p)
  FilterHelper::addSearchFilter($qb, ['h.name', 'c.name', 'p.name', 'h.description']);
  
  // Get promoted items
  $selectClause = "h.*, CAST(h.ad AS INT) as ad, c.name as city_name, p.name as province_name, p.region,
    (SELECT TOP 1 image_url FROM entity_images WHERE entity_type = 'hotel' AND entity_id = h.id ORDER BY display_order ASC, id ASC) as image_url";
  $fromClause = "hotels h";
  
  $promotedHotels = PromotionHelper::getPromotedItems($pdo, $qb, 'h', $selectClause, $fromClause, 2);
  $promotedIds = PromotionHelper::getPromotedIds($promotedHotels);
  
  // Build regular items query (excluding promoted)
  $regularQb = clone $qb;
  PromotionHelper::excludePromoted($regularQb, 'h', $promotedIds);
  $regularQb->orderBy("h.price_per_night ASC, h.id ASC");
  $regularQb->paginate($page, $limit);
  
  try {
    $sql = $regularQb->buildSelect($selectClause, $fromClause);
    $stmt = $pdo->prepare($sql);
    $stmt->execute($regularQb->getParams());
    $hotels = $stmt->fetchAll();
    
    // Calculate discounted prices
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
    
    $response = [
      'page' => $page,
      'limit' => $limit,
      'promoted' => $promotedHotels,
      'results' => $hotels
    ];
    ResponseHelper::successSimple($response);
  } catch (PDOException $e) {
    $errorMsg = 'Hotels query failed: ' . $e->getMessage();
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
    ResponseHelper::error('Missing required fields: name, city_id, province_id, price_per_night', 400);
  }
  
  if ($price_per_night < 0) {
    ResponseHelper::error('Price must be positive', 400);
  }
  
  $createdBy = $user['role'] === 'admin' && isset($input['created_by']) ? (int)$input['created_by'] : $user['id'];
  
  $stmt = $pdo->prepare('INSERT INTO hotels (name, destination_id, city_id, province_id, price_per_night, description, created_by) VALUES (?, ?, ?, ?, ?, ?, ?)');
  $stmt->execute([$name, $destination_id, $city_id, $province_id, $price_per_night, $description, $createdBy]);
  $hotelId = (int)$pdo->lastInsertId();
  
  // Save images using ImageHelper
  if (!empty($images) && is_array($images)) {
    $normalizedImages = array_map(function($url) {
      return trim($url);
    }, array_filter($images));
    ImageHelper::saveEntityImages($pdo, 'hotel', $hotelId, $normalizedImages);
  }
  
  $stmt = $pdo->prepare('SELECT h.*, c.name as city_name, p.name as province_name, p.region 
    FROM hotels h 
    LEFT JOIN cities c ON h.city_id = c.id 
    LEFT JOIN provinces p ON h.province_id = p.id 
    WHERE h.id = ?');
  $stmt->execute([$hotelId]);
  $hotel = $stmt->fetch();
  
  // Fetch all images from entity_images
  $allImages = ImageHelper::getEntityImages($pdo, 'hotel', $hotelId);
  $hotel = ImageHelper::addImageUrlToEntity($hotel, $allImages);
  
  http_response_code(201);
  ResponseHelper::successSimple(['hotel' => $hotel]);
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
    ResponseHelper::error('Forbidden', 403);
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
    ResponseHelper::error('No fields to update', 400);
  }
  
  if (!empty($updates)) {
    $params[] = $id;
    $sql = 'UPDATE hotels SET ' . implode(', ', $updates) . ' WHERE id = ?';
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
  }
  
  // Update images if provided
  if ($images !== null && is_array($images)) {
    $normalizedImages = array_map(function($url) {
      return trim($url);
    }, array_filter($images));
    ImageHelper::saveEntityImages($pdo, 'hotel', $id, $normalizedImages);
  }
  
  $stmt = $pdo->prepare('SELECT h.*, c.name as city_name, p.name as province_name, p.region 
    FROM hotels h 
    LEFT JOIN cities c ON h.city_id = c.id 
    LEFT JOIN provinces p ON h.province_id = p.id 
    WHERE h.id = ?');
  $stmt->execute([$id]);
  $hotel = $stmt->fetch();
  
  // Fetch all images from entity_images
  $allImages = ImageHelper::getEntityImages($pdo, 'hotel', $id);
  $hotel = ImageHelper::addImageUrlToEntity($hotel, $allImages);
  
  ResponseHelper::successSimple(['hotel' => $hotel]);
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
    ResponseHelper::error('Hotel not found', 404);
  }
  
  // Check permission
  if ($user['role'] !== 'admin' && $hotel['created_by'] != $user['id']) {
    ResponseHelper::error('Forbidden', 403);
  }
  
  // Soft delete
  $stmt = $pdo->prepare('UPDATE hotels SET deleted_at = SYSUTCDATETIME() WHERE id = ?');
  $stmt->execute([$id]);
  
  ResponseHelper::successSimple(['message' => 'Hotel deleted successfully']);
}

ResponseHelper::error('Not found', 404);
