<?php
declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../middleware/auth.php';
require_once __DIR__ . '/../helpers/ResponseHelper.php';

try {
  $pdo = db_pdo();
} catch (Throwable $e) {
  ResponseHelper::error('Database connection failed', 500);
}


// GET /api/ads
if ($_SERVER['REQUEST_METHOD'] === 'GET' && preg_match('#^/ads/?$#', $uri)) {
  $placement = $_GET['placement'] ?? null;
  $createdBy = isset($_GET['created_by']) ? (int)$_GET['created_by'] : null;
  $page = max(1, (int)($_GET['page'] ?? 1));
  $limit = min(50, max(1, (int)($_GET['limit'] ?? 10)));
  $offset = ($page - 1) * $limit;
  
  $where = ['active = 1'];
  $params = [];
  
  if ($placement) {
    $where[] = 'placement = ?';
    $params[] = $placement;
  }
  
  if ($createdBy !== null) {
    $where[] = 'created_by = ?';
    $params[] = $createdBy;
  }
  
  $whereSql = 'WHERE ' . implode(' AND ', $where);
  // SQL Server OFFSET/FETCH syntax - must use integers directly, not parameters
  $offsetInt = (int)$offset;
  $limitInt = (int)$limit;
  $sql = "SELECT * FROM ads $whereSql ORDER BY id ASC OFFSET $offsetInt ROWS FETCH NEXT $limitInt ROWS ONLY";
  
  $stmt = $pdo->prepare($sql);
  $stmt->execute($params);
  $ads = $stmt->fetchAll();
  
  ResponseHelper::successSimple(['page' => $page, 'limit' => $limit, 'results' => $ads]);
}

// POST /api/ads (admin only)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && preg_match('#^/ads/?$#', $uri)) {
  requireRole(['admin']);
  
  $user = get_authenticated_user();
  $input = json_decode(file_get_contents('php://input'), true);
  
  $placement = trim($input['placement'] ?? '');
  $title = trim($input['title'] ?? '');
  $image_url = trim($input['image_url'] ?? '');
  $link_url = trim($input['link_url'] ?? '');
  $active = isset($input['active']) ? (bool)$input['active'] : true;
  $createdBy = isset($input['created_by']) ? (int)$input['created_by'] : $user['id'];
  
  if (!$placement || !$title) {
    ResponseHelper::error('Missing required fields: placement, title', 400);
  }
  
  if (!in_array($placement, ['home', 'listing', 'sidebar'])) {
    ResponseHelper::error('Invalid placement', 400);
  }
  
  $stmt = $pdo->prepare('INSERT INTO ads (placement, title, image_url, link_url, active, created_by) VALUES (?, ?, ?, ?, ?, ?)');
  $stmt->execute([$placement, $title, $image_url, $link_url, $active ? 1 : 0, $createdBy]);
  $adId = (int)$pdo->lastInsertId();
  
  $stmt = $pdo->prepare('SELECT * FROM ads WHERE id = ?');
  $stmt->execute([$adId]);
  $ad = $stmt->fetch();
  
  ResponseHelper::successSimple(['ad' => $ad], 201);
}

// PUT /api/ads/:id (admin only)
if ($_SERVER['REQUEST_METHOD'] === 'PUT' && preg_match('#^/ads/(\d+)/?$#', $uri, $matches)) {
  requireRole(['admin']);
  
  $id = (int)$matches[1];
  $input = json_decode(file_get_contents('php://input'), true);
  
  // Check if ad exists
  $stmt = $pdo->prepare('SELECT id FROM ads WHERE id = ?');
  $stmt->execute([$id]);
  if (!$stmt->fetch()) {
    ResponseHelper::error('Ad not found', 404);
  }
  
  $updates = [];
  $params = [];
  
  if (isset($input['placement'])) {
    $updates[] = 'placement = ?';
    $params[] = trim($input['placement']);
  }
  if (isset($input['title'])) {
    $updates[] = 'title = ?';
    $params[] = trim($input['title']);
  }
  if (isset($input['image_url'])) {
    $updates[] = 'image_url = ?';
    $params[] = trim($input['image_url']);
  }
  if (isset($input['link_url'])) {
    $updates[] = 'link_url = ?';
    $params[] = trim($input['link_url']);
  }
  if (isset($input['active'])) {
    $updates[] = 'active = ?';
    $params[] = (bool)$input['active'] ? 1 : 0;
  }
  
  if (empty($updates)) {
    ResponseHelper::error('No fields to update', 400);
  }
  
  $params[] = $id;
  $sql = 'UPDATE ads SET ' . implode(', ', $updates) . ' WHERE id = ?';
  $stmt = $pdo->prepare($sql);
  $stmt->execute($params);
  
  $stmt = $pdo->prepare('SELECT * FROM ads WHERE id = ?');
  $stmt->execute([$id]);
  $ad = $stmt->fetch();
  
  ResponseHelper::successSimple(['ad' => $ad]);
}

if ($_SERVER['REQUEST_METHOD'] === 'DELETE' && preg_match('#^/ads/(\d+)/?$#', $uri, $matches)) {
  requireRole(['admin']);
  
  $id = (int)$matches[1];
  
  $stmt = $pdo->prepare('SELECT id FROM ads WHERE id = ?');
  $stmt->execute([$id]);
  if (!$stmt->fetch()) {
    ResponseHelper::error('Ad not found', 404);
  }
  
  $stmt = $pdo->prepare('DELETE FROM ads WHERE id = ?');
  $stmt->execute([$id]);
  
  ResponseHelper::successSimple(['message' => 'Ad deleted successfully']);
}

ResponseHelper::error('Not found', 404);
