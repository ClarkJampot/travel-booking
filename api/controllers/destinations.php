<?php
// Destinations controller
declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../middleware/auth.php';
require_once __DIR__ . '/../helpers/QueryBuilder.php';
require_once __DIR__ . '/../helpers/FilterHelper.php';
require_once __DIR__ . '/../helpers/ImageHelper.php';
require_once __DIR__ . '/../helpers/ResponseHelper.php';

try {
  $pdo = db_pdo();
} catch (Throwable $e) {
  error_log('Database error: ' . $e->getMessage());
  ResponseHelper::error('Database connection failed', 500);
}

// GET /api/destinations
$uri = $GLOBALS['API_URI'] ?? $_SERVER['REQUEST_URI'];
if ($_SERVER['REQUEST_METHOD'] === 'GET' && preg_match('#^/destinations/?$#', $uri)) {
  $id = isset($_GET['id']) ? (int)$_GET['id'] : null;
  
  // Get single destination
  if ($id) {
    $stmt = $pdo->prepare('SELECT * FROM destinations WHERE id = ?');
    $stmt->execute([$id]);
    $destination = $stmt->fetch();
    if (!$destination) {
      ResponseHelper::error('Destination not found', 404);
    }
    // Fetch all images from entity_images
    $images = ImageHelper::getEntityImages($pdo, 'destination', $id);
    $destination = ImageHelper::addImageUrlToEntity($destination, $images);
    ResponseHelper::successSimple(['destination' => $destination]);
  }
  
  // List destinations
  $pagination = FilterHelper::parsePagination();
  $page = $pagination['page'];
  $limit = min(50, max(1, (int)($_GET['limit'] ?? 20)));
  
  // Build query
  $qb = new QueryBuilder();
  
  // Add joins if needed (for location filters)
  $province_id = isset($_GET['province_id']) ? (int)$_GET['province_id'] : null;
  $city_id = isset($_GET['city_id']) ? (int)$_GET['city_id'] : null;
  if ($province_id !== null || $city_id !== null) {
    $qb->join('LEFT JOIN provinces p ON d.province_id = p.id');
    $qb->join('LEFT JOIN cities c ON d.city_id = c.id');
  }
  
  FilterHelper::addLocationFilters($qb, 'd');
  FilterHelper::addSearchFilter($qb, ['d.name', 'd.description']);
  
  $qb->orderBy("d.name ASC");
  $qb->paginate($page, $limit);
  
  try {
    $selectClause = "d.*,
      (SELECT TOP 1 image_url FROM entity_images WHERE entity_type = 'destination' AND entity_id = d.id ORDER BY display_order ASC, id ASC) as image_url";
    $fromClause = "destinations d";
    
    $sql = $qb->buildSelect($selectClause, $fromClause);
    $stmt = $pdo->prepare($sql);
    $stmt->execute($qb->getParams());
    $destinations = $stmt->fetchAll();
    
    ResponseHelper::success($destinations, $page, $limit);
  } catch (PDOException $e) {
    $errorMsg = 'Destinations query failed: ' . $e->getMessage();
    if (isset($sql)) {
      $errorMsg .= ' | SQL: ' . $sql;
    }
    if (isset($qb)) {
      $errorMsg .= ' | Params: ' . json_encode($qb->getParams());
    }
    error_log($errorMsg);
    ResponseHelper::error('Database query failed', 500);
  }
}

// POST /api/destinations (admin only)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && preg_match('#^/destinations/?$#', $uri)) {
  requireRole(['admin']);
  
  $input = json_decode(file_get_contents('php://input'), true);
  
  $name = trim($input['name'] ?? '');
  $description = trim($input['description'] ?? '');
  $images = $input['images'] ?? []; // Array of image URLs
  
  if (!$name) {
    ResponseHelper::error('Missing required fields: name', 400);
  }
  
  $stmt = $pdo->prepare('INSERT INTO destinations (name, description) VALUES (?, ?)');
  $stmt->execute([$name, $description]);
  $destinationId = (int)$pdo->lastInsertId();
  
  // Save images using ImageHelper
  if (!empty($images) && is_array($images)) {
    $normalizedImages = array_map(function($url) {
      return trim($url);
    }, array_filter($images));
    ImageHelper::saveEntityImages($pdo, 'destination', $destinationId, $normalizedImages);
  }
  
  $stmt = $pdo->prepare('SELECT * FROM destinations WHERE id = ?');
  $stmt->execute([$destinationId]);
  $destination = $stmt->fetch();
  
  // Fetch all images from entity_images
  $allImages = ImageHelper::getEntityImages($pdo, 'destination', $destinationId);
  $destination = ImageHelper::addImageUrlToEntity($destination, $allImages);
  
  http_response_code(201);
  ResponseHelper::successSimple(['destination' => $destination]);
}

// PUT /api/destinations/:id (admin only)
if ($_SERVER['REQUEST_METHOD'] === 'PUT' && preg_match('#^/destinations/(\d+)/?$#', $uri, $matches)) {
  requireRole(['admin']);
  
  $id = (int)$matches[1];
  $input = json_decode(file_get_contents('php://input'), true);
  
  // Check if destination exists
  $stmt = $pdo->prepare('SELECT id FROM destinations WHERE id = ?');
  $stmt->execute([$id]);
  if (!$stmt->fetch()) {
    ResponseHelper::error('Destination not found', 404);
  }
  
  $updates = [];
  $params = [];
  
  if (isset($input['name'])) {
    $updates[] = 'name = ?';
    $params[] = trim($input['name']);
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
    $sql = 'UPDATE destinations SET ' . implode(', ', $updates) . ' WHERE id = ?';
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
  }
  
  // Update images if provided
  if ($images !== null && is_array($images)) {
    $normalizedImages = array_map(function($url) {
      return trim($url);
    }, array_filter($images));
    ImageHelper::saveEntityImages($pdo, 'destination', $id, $normalizedImages);
  }
  
  $stmt = $pdo->prepare('SELECT * FROM destinations WHERE id = ?');
  $stmt->execute([$id]);
  $destination = $stmt->fetch();
  
  // Fetch all images from entity_images
  $allImages = ImageHelper::getEntityImages($pdo, 'destination', $id);
  $destination = ImageHelper::addImageUrlToEntity($destination, $allImages);
  
  ResponseHelper::successSimple(['destination' => $destination]);
}

// DELETE /api/destinations/:id (admin only)
if ($_SERVER['REQUEST_METHOD'] === 'DELETE' && preg_match('#^/destinations/(\d+)/?$#', $uri, $matches)) {
  requireRole(['admin']);
  
  $id = (int)$matches[1];
  
  // Check if destination exists
  $stmt = $pdo->prepare('SELECT id FROM destinations WHERE id = ?');
  $stmt->execute([$id]);
  if (!$stmt->fetch()) {
    ResponseHelper::error('Destination not found', 404);
  }
  
  // Check if destination is used
  $stmt = $pdo->prepare('SELECT COUNT(*) as count FROM hotels WHERE destination_id = ?');
  $stmt->execute([$id]);
  $hotelCount = $stmt->fetch()['count'];
  
  $stmt = $pdo->prepare('SELECT COUNT(*) as count FROM activities WHERE destination_id = ?');
  $stmt->execute([$id]);
  $activityCount = $stmt->fetch()['count'];
  
  if ($hotelCount > 0 || $activityCount > 0) {
    ResponseHelper::error('Cannot delete destination that is in use', 400);
  }
  
  $stmt = $pdo->prepare('DELETE FROM destinations WHERE id = ?');
  $stmt->execute([$id]);
  
  ResponseHelper::successSimple(['message' => 'Destination deleted successfully']);
}

    ResponseHelper::error('Not found', 404);

