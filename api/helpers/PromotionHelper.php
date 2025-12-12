<?php
// PromotionHelper for promoted items logic
declare(strict_types=1);

require_once __DIR__ . '/QueryBuilder.php';

class PromotionHelper {
  /**
   * Get promoted items matching filters
   * @param PDO $pdo Database connection
   * @param QueryBuilder $baseQuery QueryBuilder with base filters
   * @param string $tableAlias Table alias (e.g., 'h' for hotels)
   * @param string $selectClause SELECT clause
   * @param string $fromClause FROM clause with joins
   * @param int $limit Maximum number of promoted items to return
   * @return array Array of promoted items
   */
  public static function getPromotedItems(
    PDO $pdo,
    QueryBuilder $baseQuery,
    string $tableAlias,
    string $selectClause,
    string $fromClause,
    int $limit = 2
  ): array {
    try {
      // Clone the base query and add promotion filter
      $promotedQuery = clone $baseQuery;
      $promotedQuery->where("$tableAlias.ad = 1");
      
      // Build SQL with TOP for SQL Server (NEWID() for random order)
      $joinSql = $promotedQuery->getJoinSql();
      $whereSql = $promotedQuery->getWhereSql();
      $params = $promotedQuery->getParams();
      
      // SQL Server: Use TOP with NEWID() for random selection
      $sql = "SELECT TOP $limit $selectClause FROM $fromClause";
      if ($joinSql) {
        $sql .= " $joinSql";
      }
      if ($whereSql) {
        $sql .= " $whereSql";
      }
      $sql .= " ORDER BY NEWID()";
      
      $stmt = $pdo->prepare($sql);
      $stmt->execute($params);
      return $stmt->fetchAll();
    } catch (PDOException $e) {
      // If promotion column doesn't exist, return empty array
      error_log('Error fetching promoted items: ' . $e->getMessage() . ' | SQL: ' . ($sql ?? ''));
      return [];
    }
  }
  
  /**
   * Get promoted item IDs
   * @param array $promotedItems Array of promoted items
   * @return array Array of IDs
   */
  public static function getPromotedIds(array $promotedItems): array {
    return array_column($promotedItems, 'id');
  }
  
  /**
   * Build query to exclude promoted items
   * @param QueryBuilder $baseQuery Base query builder
   * @param string $tableAlias Table alias
   * @param array $promotedIds Array of promoted item IDs to exclude
   * @return QueryBuilder Modified query builder
   */
  public static function excludePromoted(QueryBuilder $baseQuery, string $tableAlias, array $promotedIds): QueryBuilder {
    // Exclude promoted items
    $baseQuery->where("($tableAlias.ad = 0 OR $tableAlias.ad IS NULL)");
    
    if (!empty($promotedIds)) {
      $baseQuery->whereNotIn("$tableAlias.id", $promotedIds);
    }
    
    return $baseQuery;
  }
  
  /**
   * Calculate discount percentage
   * @param float $originalPrice Original price
   * @param float $discountedPrice Discounted price
   * @return float Discount percentage
   */
  public static function calculateDiscount(float $originalPrice, float $discountedPrice): float {
    if ($originalPrice <= 0) {
      return 0;
    }
    return round((($originalPrice - $discountedPrice) / $originalPrice) * 100, 2);
  }
  
  /**
   * Check if item is promoted
   * @param array $item Item data
   * @return bool True if promoted
   */
  public static function isPromoted(array $item): bool {
    return isset($item['ad']) && (int)$item['ad'] === 1;
  }
}

