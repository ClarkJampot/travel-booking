<?php
declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../helpers/ResponseHelper.php';

try {
  $pdo = db_pdo();
} catch (Throwable $e) {
  ResponseHelper::error('Database connection failed', 500);
}


// GET /api/provinces
if ($_SERVER['REQUEST_METHOD'] === 'GET' && preg_match('#^/provinces/?$#', $uri)) {
  $id = isset($_GET['id']) ? (int)$_GET['id'] : null;
  
  // Get single province
  if ($id) {
    $stmt = $pdo->prepare('SELECT * FROM provinces WHERE id = ?');
    $stmt->execute([$id]);
    $province = $stmt->fetch();
    if (!$province) {
      ResponseHelper::error('Province not found', 404);
    }
    ResponseHelper::successSimple(['province' => $province]);
  }
  
  // List all provinces
  $region = $_GET['region'] ?? null;
  $where = [];
  $params = [];
  
  if ($region) {
    $where[] = 'region = ?';
    $params[] = $region;
  }
  
  $whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';
  $sql = "SELECT * FROM provinces $whereSql ORDER BY region, name ASC";
  
  $stmt = $pdo->prepare($sql);
  $stmt->execute($params);
  $provinces = $stmt->fetchAll();
  
  ResponseHelper::successSimple(['results' => $provinces]);
}

// GET /api/provinces/:id
elseif ($_SERVER['REQUEST_METHOD'] === 'GET' && preg_match('#^/provinces/(\d+)/?$#', $uri, $matches)) {
  $id = (int)$matches[1];
  
  $stmt = $pdo->prepare('SELECT * FROM provinces WHERE id = ?');
  $stmt->execute([$id]);
  $province = $stmt->fetch();
  
  if (!$province) {
    ResponseHelper::error('Province not found', 404);
  }
  
  ResponseHelper::successSimple(['province' => $province]);
}

// GET /api/cities
elseif ($_SERVER['REQUEST_METHOD'] === 'GET' && preg_match('#^/cities/?$#', $uri)) {
  $id = isset($_GET['id']) ? (int)$_GET['id'] : null;
  
  // Get single city
  if ($id) {
    $stmt = $pdo->prepare('SELECT c.*, p.name as province_name, p.region 
      FROM cities c 
      LEFT JOIN provinces p ON c.province_id = p.id 
      WHERE c.id = ?');
    $stmt->execute([$id]);
    $city = $stmt->fetch();
    if (!$city) {
      ResponseHelper::error('City not found', 404);
    }
    ResponseHelper::successSimple(['city' => $city]);
  }
  
  // List cities
  $province_id = isset($_GET['province_id']) ? (int)$_GET['province_id'] : null;
  $where = [];
  $params = [];
  
  if ($province_id !== null) {
    $where[] = 'c.province_id = ?';
    $params[] = $province_id;
  }
  
  $whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';
  $sql = "SELECT c.*, p.name as province_name, p.region 
    FROM cities c 
    LEFT JOIN provinces p ON c.province_id = p.id 
    $whereSql 
    ORDER BY p.name, c.name ASC";
  
  $stmt = $pdo->prepare($sql);
  $stmt->execute($params);
  $cities = $stmt->fetchAll();
  
  ResponseHelper::successSimple(['results' => $cities]);
}

// GET /api/cities/:id
elseif ($_SERVER['REQUEST_METHOD'] === 'GET' && preg_match('#^/cities/(\d+)/?$#', $uri, $matches)) {
  $id = (int)$matches[1];
  
  $stmt = $pdo->prepare('SELECT c.*, p.name as province_name, p.region 
    FROM cities c 
    LEFT JOIN provinces p ON c.province_id = p.id 
    WHERE c.id = ?');
  $stmt->execute([$id]);
  $city = $stmt->fetch();
  
  if (!$city) {
    ResponseHelper::error('City not found', 404);
  }
  
  ResponseHelper::successSimple(['city' => $city]);
}

else {
  ResponseHelper::error('Not found', 404);
}



