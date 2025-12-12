<?php
// QueryBuilder helper for building SQL queries dynamically
declare(strict_types=1);

class QueryBuilder {
  private array $where = [];
  private array $params = [];
  private array $joins = [];
  private string $orderBy = '';
  private ?int $offset = null;
  private ?int $limit = null;
  
  /**
   * Add a WHERE condition
   */
  public function where(string $condition, $value = null): self {
    $this->where[] = $condition;
    if ($value !== null) {
      if (is_array($value)) {
        // Multiple values for one condition
        $this->params = array_merge($this->params, $value);
      } else {
        $this->params[] = $value;
      }
    }
    return $this;
  }
  
  /**
   * Add multiple parameters for the last WHERE condition
   */
  public function addParams(array $params): self {
    $this->params = array_merge($this->params, $params);
    return $this;
  }
  
  /**
   * Add a WHERE IN condition
   */
  public function whereIn(string $column, array $values): self {
    if (empty($values)) {
      return $this;
    }
    $placeholders = implode(',', array_fill(0, count($values), '?'));
    $this->where[] = "$column IN ($placeholders)";
    $this->params = array_merge($this->params, $values);
    return $this;
  }
  
  /**
   * Add a WHERE NOT IN condition
   */
  public function whereNotIn(string $column, array $values): self {
    if (empty($values)) {
      return $this;
    }
    $placeholders = implode(',', array_fill(0, count($values), '?'));
    $this->where[] = "$column NOT IN ($placeholders)";
    $this->params = array_merge($this->params, $values);
    return $this;
  }
  
  /**
   * Add a JOIN clause
   */
  public function join(string $join): self {
    $this->joins[] = $join;
    return $this;
  }
  
  /**
   * Set ORDER BY clause
   */
  public function orderBy(string $orderBy): self {
    $this->orderBy = $orderBy;
    return $this;
  }
  
  /**
   * Set pagination
   */
  public function paginate(int $page, int $limit): self {
    $this->offset = max(0, ($page - 1) * $limit);
    $this->limit = min(50, max(1, $limit));
    return $this;
  }
  
  /**
   * Get WHERE clause SQL
   */
  public function getWhereSql(): string {
    return $this->where ? ('WHERE ' . implode(' AND ', $this->where)) : '';
  }
  
  /**
   * Get JOIN clause SQL
   */
  public function getJoinSql(): string {
    return implode(' ', $this->joins);
  }
  
  /**
   * Get ORDER BY clause SQL
   */
  public function getOrderBySql(): string {
    return $this->orderBy ? "ORDER BY $this->orderBy" : '';
  }
  
  /**
   * Get pagination SQL (SQL Server OFFSET/FETCH NEXT)
   */
  public function getPaginationSql(): string {
    if ($this->offset === null || $this->limit === null) {
      return '';
    }
    $offsetInt = (int)$this->offset;
    $limitInt = (int)$this->limit;
    return "OFFSET $offsetInt ROWS FETCH NEXT $limitInt ROWS ONLY";
  }
  
  /**
   * Get all parameters
   */
  public function getParams(): array {
    return $this->params;
  }
  
  /**
   * Build complete SELECT query
   */
  public function buildSelect(string $select, string $from): string {
    $joinSql = $this->getJoinSql();
    $whereSql = $this->getWhereSql();
    $orderBySql = $this->getOrderBySql();
    $paginationSql = $this->getPaginationSql();
    
    $sql = "SELECT $select FROM $from";
    if ($joinSql) {
      $sql .= " $joinSql";
    }
    if ($whereSql) {
      $sql .= " $whereSql";
    }
    if ($orderBySql) {
      $sql .= " $orderBySql";
    }
    if ($paginationSql) {
      $sql .= " $paginationSql";
    }
    
    return $sql;
  }
  
  /**
   * Reset the builder
   */
  public function reset(): self {
    $this->where = [];
    $this->params = [];
    $this->joins = [];
    $this->orderBy = '';
    $this->offset = null;
    $this->limit = null;
    return $this;
  }
}

