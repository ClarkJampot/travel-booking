<?php
// ImageHelper for standardized entity image operations
declare(strict_types=1);

class ImageHelper {
  /**
   * Get all images for an entity
   * @param PDO $pdo Database connection
   * @param string $entityType Entity type (hotel, flight, destination, etc.)
   * @param int $entityId Entity ID
   * @return array Array of image URLs ordered by display_order
   */
  public static function getEntityImages(PDO $pdo, string $entityType, int $entityId): array {
    $stmt = $pdo->prepare('SELECT image_url FROM entity_images WHERE entity_type = ? AND entity_id = ? ORDER BY display_order ASC, id ASC');
    $stmt->execute([$entityType, $entityId]);
    $images = $stmt->fetchAll(PDO::FETCH_COLUMN);
    return $images ?: [];
  }
  
  /**
   * Get primary image URL for an entity (first image from entity_images)
   * @param PDO $pdo Database connection
   * @param string $entityType Entity type
   * @param int $entityId Entity ID
   * @return string|null Primary image URL or null if no images
   */
  public static function getPrimaryImage(PDO $pdo, string $entityType, int $entityId): ?string {
    $stmt = $pdo->prepare('SELECT TOP 1 image_url FROM entity_images WHERE entity_type = ? AND entity_id = ? ORDER BY display_order ASC, id ASC');
    $stmt->execute([$entityType, $entityId]);
    $image = $stmt->fetchColumn();
    return $image ?: null;
  }
  
  /**
   * Save images for an entity
   * @param PDO $pdo Database connection
   * @param string $entityType Entity type
   * @param int $entityId Entity ID
   * @param array $imageUrls Array of image URLs
   * @return bool Success
   */
  public static function saveEntityImages(PDO $pdo, string $entityType, int $entityId, array $imageUrls): bool {
    try {
      // Delete existing images
      self::deleteEntityImages($pdo, $entityType, $entityId);
      
      // Move files from temp to correct location and update URLs
      $finalUrls = [];
      // Use same path calculation as upload.php: __DIR__ is api/helpers/, go up two levels to project root
      $baseDir = dirname(dirname(__DIR__)) . '/public/uploads';
      
      // Map entity types to directory names (pluralize)
      $dirMap = [
        'hotel' => 'hotels',
        'activity' => 'activities',
        'flight_route' => 'flights',
        'transfer_route' => 'transfers',
        'destination' => 'destinations'
      ];
      $dirName = $dirMap[$entityType] ?? $entityType;
      
      error_log("ImageHelper::saveEntityImages called - entityType: {$entityType}, entityId: {$entityId}, dirName: {$dirName}, imageUrls: " . json_encode($imageUrls));
      error_log("baseDir calculated: {$baseDir}");
      
      foreach ($imageUrls as $index => $imageUrl) {
        $finalUrl = $imageUrl;
        
        error_log("Processing imageUrl[{$index}]: {$imageUrl}");
        
        // If URL is in temp location, move it to entity/{id}/
        // Handle both /uploads/temp/ and /travel-booking/uploads/temp/
        if (strpos($imageUrl, '/travel-booking/uploads/temp/') === 0) {
          error_log("Matched /travel-booking/uploads/temp/ pattern");
          // Extract filename from URL
          $filename = basename($imageUrl);
          // Build target path: public/uploads/{entity}/{entity_id}/{filename}
          $targetDir = $baseDir . '/' . $dirName . '/' . $entityId;
          $targetPath = $targetDir . '/' . $filename;
          // Build temp path: public/uploads/temp/{filename}
          $tempPath = $baseDir . '/temp/' . $filename;
          
          error_log("tempPath: {$tempPath}, targetPath: {$targetPath}, baseDir: {$baseDir}");
          
          // Create target directory if it doesn't exist
          if (!is_dir($targetDir)) {
            mkdir($targetDir, 0755, true);
            error_log("Created target directory: {$targetDir}");
          }
          
          // Move file from temp to target location
          if (file_exists($tempPath)) {
            error_log("Temp file exists, attempting move...");
            if (rename($tempPath, $targetPath)) {
              $finalUrl = '/travel-booking/uploads/' . $dirName . '/' . $entityId . '/' . $filename;
              error_log("Successfully moved file from {$tempPath} to {$targetPath}");
            } else {
              $error = "Failed to move file from {$tempPath} to {$targetPath}";
              error_log($error);
              // Try copy as fallback
              if (copy($tempPath, $targetPath)) {
                unlink($tempPath);
                $finalUrl = '/travel-booking/uploads/' . $dirName . '/' . $entityId . '/' . $filename;
                error_log("Used copy+delete fallback for {$tempPath}");
              } else {
                error_log("Copy fallback also failed for {$tempPath}");
              }
            }
          } else {
            error_log("Temp file not found: {$tempPath}");
          }
        } elseif (strpos($imageUrl, '/uploads/temp/') === 0) {
          error_log("Matched /uploads/temp/ pattern");
          // Legacy format without /travel-booking prefix
          $filename = basename($imageUrl);
          // Build target path: public/uploads/{entity}/{entity_id}/{filename}
          $targetDir = $baseDir . '/' . $dirName . '/' . $entityId;
          $targetPath = $targetDir . '/' . $filename;
          // Build temp path: public/uploads/temp/{filename}
          $tempPath = $baseDir . '/temp/' . $filename;
          
          error_log("tempPath: {$tempPath}, targetPath: {$targetPath}, baseDir: {$baseDir}");
          
          // Create target directory if it doesn't exist
          if (!is_dir($targetDir)) {
            mkdir($targetDir, 0755, true);
            error_log("Created target directory: {$targetDir}");
          }
          
          // Move file from temp to target location
          if (file_exists($tempPath)) {
            error_log("Temp file exists, attempting move...");
            if (rename($tempPath, $targetPath)) {
              $finalUrl = '/travel-booking/uploads/' . $dirName . '/' . $entityId . '/' . $filename;
              error_log("Successfully moved file from {$tempPath} to {$targetPath}");
            } else {
              $error = "Failed to move file from {$tempPath} to {$targetPath}";
              error_log($error);
              // Try copy as fallback
              if (copy($tempPath, $targetPath)) {
                unlink($tempPath);
                $finalUrl = '/travel-booking/uploads/' . $dirName . '/' . $entityId . '/' . $filename;
                error_log("Used copy+delete fallback for {$tempPath}");
              } else {
                error_log("Copy fallback also failed for {$tempPath}");
              }
            }
          } else {
            error_log("Temp file not found: {$tempPath}");
          }
        } else {
          error_log("Image URL does not match temp pattern, keeping as-is: {$imageUrl}");
        }
        
        $finalUrls[] = $finalUrl;
        error_log("Final URL for image[{$index}]: {$finalUrl}");
      }
      
      // Insert new images with updated URLs
      $stmt = $pdo->prepare('INSERT INTO entity_images (entity_type, entity_id, image_url, display_order) VALUES (?, ?, ?, ?)');
      foreach ($finalUrls as $index => $imageUrl) {
        $stmt->execute([$entityType, $entityId, $imageUrl, $index + 1]);
      }
      return true;
    } catch (PDOException $e) {
      error_log('Error saving entity images: ' . $e->getMessage());
      return false;
    } catch (Exception $e) {
      error_log('Error moving entity images: ' . $e->getMessage());
      return false;
    }
  }
  
  /**
   * Delete all images for an entity
   * @param PDO $pdo Database connection
   * @param string $entityType Entity type
   * @param int $entityId Entity ID
   * @return bool Success
   */
  public static function deleteEntityImages(PDO $pdo, string $entityType, int $entityId): bool {
    try {
      $stmt = $pdo->prepare('DELETE FROM entity_images WHERE entity_type = ? AND entity_id = ?');
      $stmt->execute([$entityType, $entityId]);
      return true;
    } catch (PDOException $e) {
      error_log('Error deleting entity images: ' . $e->getMessage());
      return false;
    }
  }
  
  /**
   * Clean up old files in temp directory
   * Removes files older than specified hours (default 24 hours)
   * @param int $maxAgeHours Maximum age in hours (default 24)
   * @return array Statistics: ['deleted' => count, 'failed' => count, 'errors' => []]
   */
  public static function cleanupTempFiles(int $maxAgeHours = 24): array {
    // Use same path calculation as upload.php
    $baseDir = dirname(dirname(__DIR__)) . '/public/uploads/temp';
    $stats = ['deleted' => 0, 'failed' => 0, 'errors' => []];
    
    if (!is_dir($baseDir)) {
      return $stats;
    }
    
    $maxAge = time() - ($maxAgeHours * 3600);
    $files = glob($baseDir . '/*');
    
    foreach ($files as $file) {
      if (is_file($file)) {
        $fileAge = filemtime($file);
        if ($fileAge < $maxAge) {
          if (@unlink($file)) {
            $stats['deleted']++;
          } else {
            $stats['failed']++;
            $stats['errors'][] = "Failed to delete: {$file}";
          }
        }
      }
    }
    
    return $stats;
  }
  
  /**
   * Normalize image URL (ensure it's a valid URL or path)
   * @param string $url Image URL
   * @return string Normalized URL
   */
  public static function normalizeImageUrl(string $url): string {
    // Remove leading/trailing whitespace
    $url = trim($url);
    
    // If it's already a full URL, return as is
    if (filter_var($url, FILTER_VALIDATE_URL)) {
      return $url;
    }
    
    // If it starts with /, it's a relative path
    if (strpos($url, '/') === 0) {
      return $url;
    }
    
    // Otherwise, assume it's a relative path and add /
    return '/' . $url;
  }
  
  /**
   * Add image_url to entity result (for backward compatibility)
   * @param array $entity Entity data
   * @param array $images Array of image URLs
   * @return array Entity with image_url added
   */
  public static function addImageUrlToEntity(array $entity, array $images): array {
    $entity['images'] = $images;
    $entity['image_url'] = $images[0] ?? null;
    return $entity;
  }
}

