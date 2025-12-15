<?php
declare(strict_types=1);

/**
 * Stats Helper
 * Provides utilities for calculating statistics
 */
class StatsHelper {
  /**
   * Count items created by a user
   * @param PDO $pdo Database connection
   * @param string $entityType Entity type (hotel, flight, activity, transfer)
   * @param int $userId User ID
   * @return int Count of items
   */
  public static function countUserItems(PDO $pdo, string $entityType, int $userId): int {
    $tableMap = [
      'hotel' => ['table' => 'hotels', 'hasDeletedAt' => false],
      'flight' => ['table' => 'flight_routes', 'hasDeletedAt' => true],
      'activity' => ['table' => 'activities', 'hasDeletedAt' => true],
      'transfer' => ['table' => 'transfer_routes', 'hasDeletedAt' => true]
    ];
    
    if (!isset($tableMap[$entityType])) {
      return 0;
    }
    
    $config = $tableMap[$entityType];
    $table = $config['table'];
    $deletedFilter = $config['hasDeletedAt'] ? 'AND deleted_at IS NULL' : '';
    
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM $table WHERE created_by = ? $deletedFilter");
    $stmt->execute([$userId]);
    $result = $stmt->fetch();
    return (int)($result['count'] ?? 0);
  }
}
