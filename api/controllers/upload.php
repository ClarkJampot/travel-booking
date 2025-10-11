<?php
declare(strict_types=1);
require_once __DIR__ . '/../bootstrap.php';

// Load DB
if (!file_exists(__DIR__ . '/../db.php')) {
  json_error('Missing api/db.php', 500);
  exit;
}
require_once __DIR__ . '/../db.php';

// Require authentication
require_once __DIR__ . '/../middleware/auth.php';
requireAuth();

// POST /api/upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && preg_match('#/api/upload/?$#', $_SERVER['REQUEST_URI'])) {
  if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
    json_error('No image uploaded or upload error', 400);
    exit;
  }

  $file = $_FILES['image'];
  $category = $_POST['category'] ?? 'general';
  
  // Validate file type
  $allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];
  $finfo = finfo_open(FILEINFO_MIME_TYPE);
  $mimeType = finfo_file($finfo, $file['tmp_name']);
  finfo_close($finfo);
  
  if (!in_array($mimeType, $allowedTypes)) {
    json_error('Invalid file type. Only JPEG, PNG, and WebP are allowed.', 400);
    exit;
  }
  
  // Validate file size (5MB max)
  if ($file['size'] > 5 * 1024 * 1024) {
    json_error('File too large. Maximum size is 5MB.', 400);
    exit;
  }
  
  // Validate category
  $allowedCategories = ['hotels', 'flights', 'activities', 'transfers', 'profiles', 'ads'];
  if (!in_array($category, $allowedCategories)) {
    json_error('Invalid category', 400);
    exit;
  }
  
  // Generate unique filename
  $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
  $filename = uniqid() . '_' . time() . '.' . $extension;
  $uploadPath = __DIR__ . '/../public/uploads/' . $category . '/' . $filename;
  
  // Ensure directory exists
  $uploadDir = dirname($uploadPath);
  if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
  }
  
  // Move uploaded file
  if (!move_uploaded_file($file['tmp_name'], $uploadPath)) {
    json_error('Failed to save uploaded file', 500);
    exit;
  }
  
  // Return the URL path
  $urlPath = '/uploads/' . $category . '/' . $filename;
  json_ok(['url' => $urlPath, 'filename' => $filename], 201);
  exit;
}

json_error('Not found', 404);
