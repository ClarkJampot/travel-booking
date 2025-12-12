<?php
// FilterHelper for parsing common filters and building WHERE clauses
declare(strict_types=1);

require_once __DIR__ . '/QueryBuilder.php';

class FilterHelper {
  /**
   * Parse pagination parameters from GET request
   */
  public static function parsePagination(): array {
    $page = max(1, (int)($_GET['page'] ?? 1));
    $limit = min(50, max(1, (int)($_GET['limit'] ?? 10)));
    return ['page' => $page, 'limit' => $limit];
  }
  
  /**
   * Add common location filters (city_id, province_id, destination_id)
   */
  public static function addLocationFilters(QueryBuilder $qb, string $prefix = ''): void {
    $city_id = isset($_GET['city_id']) ? (int)$_GET['city_id'] : null;
    $province_id = isset($_GET['province_id']) ? (int)$_GET['province_id'] : null;
    $destination_id = isset($_GET['destination_id']) ? (int)$_GET['destination_id'] : null;
    
    $tablePrefix = $prefix ? "$prefix." : '';
    
    if ($city_id !== null) {
      $qb->where("{$tablePrefix}city_id = ?", $city_id);
    }
    if ($province_id !== null) {
      $qb->where("{$tablePrefix}province_id = ?", $province_id);
    }
    if ($destination_id !== null) {
      $qb->where("{$tablePrefix}destination_id = ?", $destination_id);
    }
  }
  
  /**
   * Add price range filters
   */
  public static function addPriceFilters(QueryBuilder $qb, string $priceColumn): void {
    $minPrice = isset($_GET['minPrice']) ? (float)$_GET['minPrice'] : null;
    $maxPrice = isset($_GET['maxPrice']) ? (float)$_GET['maxPrice'] : null;
    
    if ($minPrice !== null) {
      $qb->where("$priceColumn >= ?", $minPrice);
    }
    if ($maxPrice !== null) {
      $qb->where("$priceColumn <= ?", $maxPrice);
    }
  }
  
  /**
   * Add search query filter
   * @param QueryBuilder $qb QueryBuilder instance
   * @param array $searchColumns Array of column names to search in (e.g., ['h.name', 'c.name'])
   */
  public static function addSearchFilter(QueryBuilder $qb, array $searchColumns): void {
    $q = isset($_GET['q']) ? trim($_GET['q']) : null;
    if (!$q || empty($searchColumns)) {
      return;
    }
    
    // Escape special characters for SQL LIKE: %, _, [, ]
    $searchTerm = self::escapeSearchTerm($q);
    $searchPattern = self::buildSearchPattern($q);
    
    // Build condition with placeholders
    $conditions = [];
    foreach ($searchColumns as $column) {
      $conditions[] = "$column COLLATE SQL_Latin1_General_CP1_CI_AI LIKE ?";
    }
    
    $whereClause = '(' . implode(' OR ', $conditions) . ')';
    $qb->where($whereClause);
    
    // Add search pattern for each column
    $params = array_fill(0, count($searchColumns), $searchPattern);
    $qb->addParams($params);
  }
  
  /**
   * Add created_by filter
   */
  public static function addCreatedByFilter(QueryBuilder $qb, string $prefix = ''): void {
    $createdBy = isset($_GET['createdBy']) ? (int)$_GET['createdBy'] : null;
    if ($createdBy !== null) {
      $tablePrefix = $prefix ? "$prefix." : '';
      $qb->where("{$tablePrefix}created_by = ?", $createdBy);
    }
  }
  
  /**
   * Escape search term for SQL LIKE
   */
  public static function escapeSearchTerm(string $term): string {
    return str_replace(['%', '_', '[', ']'], ['[%]', '[_]', '[[]', '[]]'], $term);
  }
  
  /**
   * Build search pattern for LIKE query
   */
  public static function buildSearchPattern(string $term, bool $prefix = true, bool $suffix = true): string {
    $escaped = self::escapeSearchTerm($term);
    $pattern = '';
    if ($prefix) {
      $pattern .= '%';
    }
    $pattern .= $escaped;
    if ($suffix) {
      $pattern .= '%';
    }
    return $pattern;
  }
}

