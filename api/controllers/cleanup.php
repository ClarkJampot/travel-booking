<?php
// Cleanup controller for maintenance tasks
declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../helpers/ImageHelper.php';
require_once __DIR__ . '/../helpers/ResponseHelper.php';

$uri = $GLOBALS['API_URI'] ?? $_SERVER['REQUEST_URI'];

// POST /api/cleanup/temp-files
// Cleans up old files in temp directory
// Optional query param: max_age_hours (default: 24)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && preg_match('#^/cleanup/temp-files/?$#', $uri)) {
  $maxAgeHours = isset($_GET['max_age_hours']) ? (int)$_GET['max_age_hours'] : 24;
  
  if ($maxAgeHours < 1) {
    ResponseHelper::error('max_age_hours must be at least 1', 400);
  }
  
  $stats = ImageHelper::cleanupTempFiles($maxAgeHours);
  
  ResponseHelper::successSimple([
    'message' => 'Cleanup completed',
    'stats' => $stats
  ]);
}

// GET /api/cleanup/temp-files/stats
// Get statistics about temp directory
if ($_SERVER['REQUEST_METHOD'] === 'GET' && preg_match('#^/cleanup/temp-files/stats/?$#', $uri)) {
  // Use same path calculation as upload.php
  $baseDir = dirname(dirname(__DIR__)) . '/public/uploads/temp';
  $stats = [
    'total_files' => 0,
    'total_size' => 0,
    'oldest_file' => null,
    'newest_file' => null
  ];
  
  if (is_dir($baseDir)) {
    $files = glob($baseDir . '/*');
    $oldestTime = null;
    $newestTime = null;
    
    foreach ($files as $file) {
      if (is_file($file)) {
        $stats['total_files']++;
        $stats['total_size'] += filesize($file);
        $fileTime = filemtime($file);
        
        if ($oldestTime === null || $fileTime < $oldestTime) {
          $oldestTime = $fileTime;
          $stats['oldest_file'] = [
            'name' => basename($file),
            'age_hours' => round((time() - $fileTime) / 3600, 2)
          ];
        }
        
        if ($newestTime === null || $fileTime > $newestTime) {
          $newestTime = $fileTime;
          $stats['newest_file'] = [
            'name' => basename($file),
            'age_hours' => round((time() - $fileTime) / 3600, 2)
          ];
        }
      }
    }
    
    $stats['total_size_mb'] = round($stats['total_size'] / 1024 / 1024, 2);
  }
  
  ResponseHelper::successSimple(['stats' => $stats]);
}

ResponseHelper::error('Not found', 404);

