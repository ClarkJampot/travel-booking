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
    $stmt = $pdo->prepare('SELECT * FROM hotels WHERE id = ?');
    $stmt->execute([$id]);
    $hotel = $stmt->fetch();
    if (!$hotel) {
      json_error('Hotel not found', 404);
    }
    json_ok(['hotel' => $hotel]);
  }
  
  // List hotels
  $city = $_GET['city'] ?? null;
  $country = $_GET['country'] ?? null;
  $destination_id = isset($_GET['destination_id']) ? (int)$_GET['destination_id'] : null;
  $minPrice = isset($_GET['minPrice']) ? (float)$_GET['minPrice'] : null;
  $maxPrice = isset($_GET['maxPrice']) ? (float)$_GET['maxPrice'] : null;
  $ratingMin = isset($_GET['ratingMin']) ? (float)$_GET['ratingMin'] : null;
  $createdBy = isset($_GET['createdBy']) ? (int)$_GET['createdBy'] : null;
  $page = max(1, (int)($_GET['page'] ?? 1));
  $limit = min(50, max(1, (int)($_GET['limit'] ?? 10)));
  $offset = ($page - 1) * $limit;
  
  $where = [];
  $params = [];
  
  if ($city) {
    $where[] = 'city = ?';
    $params[] = $city;
  }
  if ($country) {
    $where[] = 'country = ?';
    $params[] = $country;
  }
  if ($destination_id !== null) {
    $where[] = 'destination_id = ?';
    $params[] = $destination_id;
  }
  if ($minPrice !== null) {
    $where[] = 'price_per_night >= ?';
    $params[] = $minPrice;
  }
  if ($maxPrice !== null) {
    $where[] = 'price_per_night <= ?';
    $params[] = $maxPrice;
  }
  if ($ratingMin !== null) {
    $where[] = 'rating >= ?';
    $params[] = $ratingMin;
  }
  if ($createdBy !== null) {
    $where[] = 'created_by = ?';
    $params[] = $createdBy;
  }
  
  $whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';
  // SQL Server OFFSET/FETCH syntax - must use integers directly, not parameters
  $offsetInt = (int)$offset;
  $limitInt = (int)$limit;
  $sql = "SELECT * FROM hotels $whereSql ORDER BY rating DESC, price_per_night ASC, id ASC OFFSET $offsetInt ROWS FETCH NEXT $limitInt ROWS ONLY";
  
  $stmt = $pdo->prepare($sql);
  $stmt->execute($params);
  $hotels = $stmt->fetchAll();
  
  json_ok(['page' => $page, 'limit' => $limit, 'results' => $hotels]);
}

// POST /api/hotels (owner/admin only)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && preg_match('#^/hotels/?$#', $uri)) {
  requireRole(['owner', 'admin']);
  
  $user = get_authenticated_user();
  $input = json_decode(file_get_contents('php://input'), true);
  
  $name = trim($input['name'] ?? '');
  $destination_id = isset($input['destination_id']) ? (int)$input['destination_id'] : null;
  $city = trim($input['city'] ?? '');
  $country = trim($input['country'] ?? '');
  $price_per_night = isset($input['price_per_night']) ? (float)$input['price_per_night'] : null;
  $rating = isset($input['rating']) ? (float)$input['rating'] : 0;
  $description = trim($input['description'] ?? '');
  $image_url = trim($input['image_url'] ?? '');
  
  if (!$name || !$city || !$country || $price_per_night === null) {
    json_error('Missing required fields: name, city, country, price_per_night', 400);
  }
  
  if ($price_per_night < 0) {
    json_error('Price must be positive', 400);
  }
  
  $createdBy = $user['role'] === 'admin' && isset($input['created_by']) ? (int)$input['created_by'] : $user['id'];
  
  $stmt = $pdo->prepare('INSERT INTO hotels (name, destination_id, city, country, price_per_night, rating, description, image_url, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)');
  $stmt->execute([$name, $destination_id, $city, $country, $price_per_night, $rating, $description, $image_url, $createdBy]);
  $hotelId = (int)$pdo->lastInsertId();
  
  $stmt = $pdo->prepare('SELECT * FROM hotels WHERE id = ?');
  $stmt->execute([$hotelId]);
  $hotel = $stmt->fetch();
  
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
  if (isset($input['city'])) {
    $updates[] = 'city = ?';
    $params[] = trim($input['city']);
  }
  if (isset($input['country'])) {
    $updates[] = 'country = ?';
    $params[] = trim($input['country']);
  }
  if (isset($input['price_per_night'])) {
    $updates[] = 'price_per_night = ?';
    $params[] = (float)$input['price_per_night'];
  }
  if (isset($input['rating'])) {
    $updates[] = 'rating = ?';
    $params[] = (float)$input['rating'];
  }
  if (isset($input['description'])) {
    $updates[] = 'description = ?';
    $params[] = trim($input['description']);
  }
  if (isset($input['image_url'])) {
    $updates[] = 'image_url = ?';
    $params[] = trim($input['image_url']);
  }
  
  if (empty($updates)) {
    json_error('No fields to update', 400);
  }
  
  $params[] = $id;
  $sql = 'UPDATE hotels SET ' . implode(', ', $updates) . ' WHERE id = ?';
  $stmt = $pdo->prepare($sql);
  $stmt->execute($params);
  
  $stmt = $pdo->prepare('SELECT * FROM hotels WHERE id = ?');
  $stmt->execute([$id]);
  $hotel = $stmt->fetch();
  
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
