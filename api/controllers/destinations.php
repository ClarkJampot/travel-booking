<?php
// Destinations controller
declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../middleware/auth.php';

try {
  $pdo = db_pdo();
} catch (Throwable $e) {
  error_log('Database error: ' . $e->getMessage());
  json_error('Database connection failed', 500);
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
      json_error('Destination not found', 404);
    }
    // Fetch all images from entity_images
    $images = get_entity_images($pdo, 'destination', $id);
    $destination['images'] = $images;
    // Add image_url for backward compatibility (first image)
    $destination['image_url'] = $images[0] ?? null;
    json_ok(['destination' => $destination]);
  }
  
  // List destinations
  $featured = isset($_GET['featured']) ? (bool)$_GET['featured'] : null;
  $province_id = isset($_GET['province_id']) ? (int)$_GET['province_id'] : null;
  $city_id = isset($_GET['city_id']) ? (int)$_GET['city_id'] : null;
  $q = isset($_GET['q']) ? trim($_GET['q']) : null;
  $page = max(1, (int)($_GET['page'] ?? 1));
  $limit = min(50, max(1, (int)($_GET['limit'] ?? 20)));
  $offset = ($page - 1) * $limit;
  
  $where = [];
  $params = [];
  $joins = [];
  
  if ($featured !== null) {
    $where[] = 'd.featured = ?';
    $params[] = $featured ? 1 : 0;
  }
  
  if ($province_id !== null) {
    $where[] = 'd.province_id = ?';
    $params[] = $province_id;
  }
  
  if ($city_id !== null) {
    $where[] = 'd.city_id = ?';
    $params[] = $city_id;
  }
  
  if ($q) {
    // Escape special characters for SQL LIKE: %, _, [, ]
    $searchTerm = str_replace(['%', '_', '[', ']'], ['[%]', '[_]', '[[]', '[]]'], $q);
    $where[] = "(d.name COLLATE SQL_Latin1_General_CP1_CI_AI LIKE ? OR d.description COLLATE SQL_Latin1_General_CP1_CI_AI LIKE ?)";
    $searchPattern = '%' . $searchTerm . '%';
    $params[] = $searchPattern;
    $params[] = $searchPattern;
  }
  
  // Add joins if we need location data
  $joinSql = '';
  if ($province_id !== null || $city_id !== null || $q) {
    $joinSql = 'LEFT JOIN provinces p ON d.province_id = p.id LEFT JOIN cities c ON d.city_id = c.id';
  }
  
  $whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';
  $offsetInt = (int)$offset;
  $limitInt = (int)$limit;
  
  try {
    $orderBy = "d.featured DESC, d.name ASC";
    $sql = "SELECT d.*,
      (SELECT TOP 1 image_url FROM entity_images WHERE entity_type = 'destination' AND entity_id = d.id ORDER BY display_order ASC, id ASC) as image_url
      FROM destinations d $joinSql $whereSql ORDER BY $orderBy OFFSET $offsetInt ROWS FETCH NEXT $limitInt ROWS ONLY";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $destinations = $stmt->fetchAll();
    
    json_ok(['page' => $page, 'limit' => $limit, 'results' => $destinations]);
  } catch (PDOException $e) {
    error_log('SQL Error: ' . $e->getMessage() . ' | SQL: ' . ($sql ?? ''));
    json_error('Database query failed', 500);
  }
}

// POST /api/destinations (admin only)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && preg_match('#^/destinations/?$#', $uri)) {
  requireRole(['admin']);
  
  $input = json_decode(file_get_contents('php://input'), true);
  
  $name = trim($input['name'] ?? '');
  $description = trim($input['description'] ?? '');
  $images = $input['images'] ?? []; // Array of image URLs
  $featured = isset($input['featured']) ? (bool)$input['featured'] : false;
  
  if (!$name) {
    json_error('Missing required fields: name', 400);
  }
  
  $stmt = $pdo->prepare('INSERT INTO destinations (name, description, featured) VALUES (?, ?, ?)');
  $stmt->execute([$name, $description, $featured ? 1 : 0]);
  $destinationId = (int)$pdo->lastInsertId();
  
  // Insert images into entity_images
  if (!empty($images) && is_array($images)) {
    $imgStmt = $pdo->prepare('INSERT INTO entity_images (entity_type, entity_id, image_url, display_order) VALUES (?, ?, ?, ?)');
    foreach ($images as $index => $imageUrl) {
      if (!empty($imageUrl)) {
        $imgStmt->execute(['destination', $destinationId, trim($imageUrl), $index + 1]);
      }
    }
  }
  
  $stmt = $pdo->prepare('SELECT * FROM destinations WHERE id = ?');
  $stmt->execute([$destinationId]);
  $destination = $stmt->fetch();
  
  // Fetch all images from entity_images
  $allImages = get_entity_images($pdo, 'destination', $destinationId);
  $destination['images'] = $allImages;
  $destination['image_url'] = $allImages[0] ?? null;
  
  json_ok(['destination' => $destination], 201);
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
    json_error('Destination not found', 404);
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
  if (isset($input['featured'])) {
    $updates[] = 'featured = ?';
    $params[] = (bool)$input['featured'] ? 1 : 0;
  }
  
  // Handle images update (replace all existing images)
  $images = $input['images'] ?? null;
  
  if (empty($updates) && $images === null) {
    json_error('No fields to update', 400);
  }
  
  if (!empty($updates)) {
    $params[] = $id;
    $sql = 'UPDATE destinations SET ' . implode(', ', $updates) . ' WHERE id = ?';
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
  }
  
  // Update images if provided
  if ($images !== null && is_array($images)) {
    // Delete existing images
    $delStmt = $pdo->prepare('DELETE FROM entity_images WHERE entity_type = ? AND entity_id = ?');
    $delStmt->execute(['destination', $id]);
    
    // Insert new images
    if (!empty($images)) {
      $imgStmt = $pdo->prepare('INSERT INTO entity_images (entity_type, entity_id, image_url, display_order) VALUES (?, ?, ?, ?)');
      foreach ($images as $index => $imageUrl) {
        if (!empty($imageUrl)) {
          $imgStmt->execute(['destination', $id, trim($imageUrl), $index + 1]);
        }
      }
    }
  }
  
  $stmt = $pdo->prepare('SELECT * FROM destinations WHERE id = ?');
  $stmt->execute([$id]);
  $destination = $stmt->fetch();
  
  // Fetch all images from entity_images
  $allImages = get_entity_images($pdo, 'destination', $id);
  $destination['images'] = $allImages;
  $destination['image_url'] = $allImages[0] ?? null;
  
  json_ok(['destination' => $destination]);
}

// DELETE /api/destinations/:id (admin only)
if ($_SERVER['REQUEST_METHOD'] === 'DELETE' && preg_match('#^/destinations/(\d+)/?$#', $uri, $matches)) {
  requireRole(['admin']);
  
  $id = (int)$matches[1];
  
  // Check if destination exists
  $stmt = $pdo->prepare('SELECT id FROM destinations WHERE id = ?');
  $stmt->execute([$id]);
  if (!$stmt->fetch()) {
    json_error('Destination not found', 404);
  }
  
  // Check if destination is used
  $stmt = $pdo->prepare('SELECT COUNT(*) as count FROM hotels WHERE destination_id = ?');
  $stmt->execute([$id]);
  $hotelCount = $stmt->fetch()['count'];
  
  $stmt = $pdo->prepare('SELECT COUNT(*) as count FROM activities WHERE destination_id = ?');
  $stmt->execute([$id]);
  $activityCount = $stmt->fetch()['count'];
  
  if ($hotelCount > 0 || $activityCount > 0) {
    json_error('Cannot delete destination that is in use', 400);
  }
  
  $stmt = $pdo->prepare('DELETE FROM destinations WHERE id = ?');
  $stmt->execute([$id]);
  
  json_ok(['message' => 'Destination deleted successfully']);
}

json_error('Not found', 404);

