<?php
declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../middleware/auth.php';
require_once __DIR__ . '/../helpers/ResponseHelper.php';
require_once __DIR__ . '/../helpers/Router.php';

requireAuth();

if (!isset($uri)) {
  $uri = Router::parseUri();
}

// POST /api/upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && preg_match('#^/upload/?$#', $uri)) {
  if (!isset($_FILES['image'])) {
    ResponseHelper::error('No image file provided', 400);
  }
  
  if ($_FILES['image']['error'] !== UPLOAD_ERR_OK) {
    $errorMessages = [
      UPLOAD_ERR_INI_SIZE => 'File exceeds upload_max_filesize directive',
      UPLOAD_ERR_FORM_SIZE => 'File exceeds MAX_FILE_SIZE directive',
      UPLOAD_ERR_PARTIAL => 'File was only partially uploaded',
      UPLOAD_ERR_NO_FILE => 'No file was uploaded',
      UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder',
      UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
      UPLOAD_ERR_EXTENSION => 'File upload stopped by extension'
    ];
    $errorMsg = $errorMessages[$_FILES['image']['error']] ?? 'Unknown upload error';
    ResponseHelper::error('Upload error: ' . $errorMsg, 400);
  }
  
  $file = $_FILES['image'];
  $category = $_POST['category'] ?? 'general';
  $entity_type = $_POST['entity_type'] ?? null;
  $entity_id = isset($_POST['entity_id']) && $_POST['entity_id'] !== '' ? (int)$_POST['entity_id'] : null;
  
  $finfo = finfo_open(FILEINFO_MIME_TYPE);
  $mimeType = finfo_file($finfo, $file['tmp_name']);
  finfo_close($finfo);
  
  if (!in_array($mimeType, UPLOAD_ALLOWED_TYPES)) {
    ResponseHelper::error('Invalid file type. Only JPEG, PNG, and WebP are allowed.', 400);
  }
  
  if ($file['size'] > UPLOAD_MAX_SIZE) {
    ResponseHelper::error('File too large. Maximum size is ' . (UPLOAD_MAX_SIZE / 1024 / 1024) . 'MB.', 400);
  }
  
  if (!in_array($category, UPLOAD_CATEGORIES)) {
    ResponseHelper::error('Invalid category', 400);
  }
  
  $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
  $originalFilename = pathinfo($file['name'], PATHINFO_FILENAME);
  $filename = $originalFilename . '_' . uniqid() . '.' . $extension;
  
  $baseUploadDir = dirname(dirname(__DIR__)) . '/public/uploads';
  
  if ($entity_type && $entity_id) {
    $uploadPath = $baseUploadDir . '/' . $entity_type . '/' . $entity_id . '/' . $filename;
    $urlPath = '/travel-booking/uploads/' . $entity_type . '/' . $entity_id . '/' . $filename;
  } else {
    $uploadPath = $baseUploadDir . '/temp/' . $filename;
    $urlPath = '/travel-booking/uploads/temp/' . $filename;
  }
  
  $uploadDir = dirname($uploadPath);
  if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
  }
  
  if (!move_uploaded_file($file['tmp_name'], $uploadPath)) {
    ResponseHelper::error('Failed to save uploaded file', 500);
  }
  
  ResponseHelper::successSimple(['url' => $urlPath, 'filename' => $filename], 201);
}

ResponseHelper::error('Not found', 404);
