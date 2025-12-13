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
      
      // Insert new images
      $stmt = $pdo->prepare('INSERT INTO entity_images (entity_type, entity_id, image_url, display_order) VALUES (?, ?, ?, ?)');
      foreach ($imageUrls as $index => $imageUrl) {
        $stmt->execute([$entityType, $entityId, $imageUrl, $index + 1]);
      }
      return true;
    } catch (PDOException $e) {
      error_log('Error saving entity images: ' . $e->getMessage());
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


