<?php
// Upload controller
declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../middleware/auth.php';

// Require authentication
requireAuth();

$uri = $GLOBALS['API_URI'] ?? $_SERVER['REQUEST_URI'];

// POST /api/upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && preg_match('#^/upload/?$#', $uri)) {
  if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
    json_error('No image uploaded or upload error', 400);
  }
  
  $file = $_FILES['image'];
  $category = $_POST['category'] ?? 'general';
  $entity_type = $_POST['entity_type'] ?? null;
  $entity_id = isset($_POST['entity_id']) && $_POST['entity_id'] !== '' ? (int)$_POST['entity_id'] : null;
  
  // Validate file type
  $finfo = finfo_open(FILEINFO_MIME_TYPE);
  $mimeType = finfo_file($finfo, $file['tmp_name']);
  finfo_close($finfo);
  
  if (!in_array($mimeType, UPLOAD_ALLOWED_TYPES)) {
    json_error('Invalid file type. Only JPEG, PNG, and WebP are allowed.', 400);
  }
  
  // Validate file size
  if ($file['size'] > UPLOAD_MAX_SIZE) {
    json_error('File too large. Maximum size is ' . (UPLOAD_MAX_SIZE / 1024 / 1024) . 'MB.', 400);
  }
  
  // Validate category
  if (!in_array($category, UPLOAD_CATEGORIES)) {
    json_error('Invalid category', 400);
  }
  
  // Generate unique filename
  $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
  $originalFilename = pathinfo($file['name'], PATHINFO_FILENAME);
  $filename = $originalFilename . '_' . uniqid() . '.' . $extension;
  
  // Determine upload path: /public/uploads/{entity}/{entity_id}/filename
  // Use consistent path calculation: go up two levels from api/controllers/ to project root
  $baseUploadDir = dirname(dirname(__DIR__)) . '/public/uploads';
  
  // If entity_id is provided, use the new structure; otherwise upload to temp
  if ($entity_type && $entity_id) {
    $uploadPath = $baseUploadDir . '/' . $entity_type . '/' . $entity_id . '/' . $filename;
    $urlPath = '/travel-booking/uploads/' . $entity_type . '/' . $entity_id . '/' . $filename;
  } else {
    // Upload to temp location for new entities (will be moved after entity creation)
    $uploadPath = $baseUploadDir . '/temp/' . $filename;
    $urlPath = '/travel-booking/uploads/temp/' . $filename;
  }
  
  // Ensure directory exists
  $uploadDir = dirname($uploadPath);
  if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
  }
  
  // Move uploaded file
  if (!move_uploaded_file($file['tmp_name'], $uploadPath)) {
    json_error('Failed to save uploaded file', 500);
  }
  
  // Return the URL path
  json_ok(['url' => $urlPath, 'filename' => $filename], 201);
}

json_error('Not found', 404);
