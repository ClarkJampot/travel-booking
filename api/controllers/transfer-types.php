<?php
// Transfer Types controller
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

// GET /api/transfers/types
if ($_SERVER['REQUEST_METHOD'] === 'GET' && preg_match('#^/transfers/types/?$#', $uri)) {
  $id = isset($_GET['id']) ? (int)$_GET['id'] : null;
  
  // Get single type
  if ($id) {
    $stmt = $pdo->prepare('SELECT * FROM transfer_types WHERE id = ?');
    $stmt->execute([$id]);
    $type = $stmt->fetch();
    if (!$type) {
      json_error('Transfer type not found', 404);
    }
    json_ok(['type' => $type]);
  }
  
  // List all types
  $stmt = $pdo->prepare('SELECT * FROM transfer_types ORDER BY name ASC');
  $stmt->execute();
  $types = $stmt->fetchAll();
  
  json_ok(['results' => $types]);
}

// POST /api/transfers/types (admin only)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && preg_match('#^/transfers/types/?$#', $uri)) {
  requireRole(['admin']);
  
  $input = json_decode(file_get_contents('php://input'), true);
  
  $name = trim($input['name'] ?? '');
  $description = trim($input['description'] ?? '');
  $icon = trim($input['icon'] ?? '');
  
  if (!$name) {
    json_error('Missing required field: name', 400);
  }
  
  // Check if name already exists
  $checkStmt = $pdo->prepare('SELECT id FROM transfer_types WHERE name = ?');
  $checkStmt->execute([$name]);
  if ($checkStmt->fetch()) {
    json_error('Transfer type already exists', 400);
  }
  
  $stmt = $pdo->prepare('INSERT INTO transfer_types (name, description, icon) VALUES (?, ?, ?)');
  $stmt->execute([$name, $description, $icon]);
  $typeId = (int)$pdo->lastInsertId();
  
  $stmt = $pdo->prepare('SELECT * FROM transfer_types WHERE id = ?');
  $stmt->execute([$typeId]);
  $type = $stmt->fetch();
  
  json_ok(['type' => $type], 201);
}

// PUT /api/transfers/types/:id (admin only)
if ($_SERVER['REQUEST_METHOD'] === 'PUT' && preg_match('#^/transfers/types/(\d+)/?$#', $uri, $matches)) {
  requireRole(['admin']);
  
  $id = (int)$matches[1];
  $input = json_decode(file_get_contents('php://input'), true);
  
  $stmt = $pdo->prepare('SELECT id FROM transfer_types WHERE id = ?');
  $stmt->execute([$id]);
  if (!$stmt->fetch()) {
    json_error('Transfer type not found', 404);
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
  if (isset($input['icon'])) {
    $updates[] = 'icon = ?';
    $params[] = trim($input['icon']);
  }
  
  if (empty($updates)) {
    json_error('No fields to update', 400);
  }
  
  $params[] = $id;
  $sql = 'UPDATE transfer_types SET ' . implode(', ', $updates) . ' WHERE id = ?';
  $stmt = $pdo->prepare($sql);
  $stmt->execute($params);
  
  $stmt = $pdo->prepare('SELECT * FROM transfer_types WHERE id = ?');
  $stmt->execute([$id]);
  $type = $stmt->fetch();
  
  json_ok(['type' => $type]);
}

// DELETE /api/transfers/types/:id (admin only)
if ($_SERVER['REQUEST_METHOD'] === 'DELETE' && preg_match('#^/transfers/types/(\d+)/?$#', $uri, $matches)) {
  requireRole(['admin']);
  
  $id = (int)$matches[1];
  
  $stmt = $pdo->prepare('SELECT id FROM transfer_types WHERE id = ?');
  $stmt->execute([$id]);
  if (!$stmt->fetch()) {
    json_error('Transfer type not found', 404);
  }
  
  // Check if type is used in routes
  $checkStmt = $pdo->prepare('SELECT COUNT(*) as count FROM transfer_routes WHERE transfer_type_id = ?');
  $checkStmt->execute([$id]);
  $result = $checkStmt->fetch();
  if ($result['count'] > 0) {
    json_error('Cannot delete transfer type: it is used in transfer routes', 400);
  }
  
  $stmt = $pdo->prepare('DELETE FROM transfer_types WHERE id = ?');
  $stmt->execute([$id]);
  
  json_ok(['message' => 'Transfer type deleted successfully']);
}

json_error('Not found', 404);









