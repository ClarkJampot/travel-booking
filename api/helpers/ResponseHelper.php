<?php
// ResponseHelper for standardized API responses
declare(strict_types=1);

class ResponseHelper {
  /**
   * Format success response with pagination
   */
  public static function success(array $data, int $page = 1, int $limit = 10, ?int $total = null): void {
    $response = [
      'success' => true,
      'page' => $page,
      'limit' => $limit,
      'results' => $data
    ];
    
    if ($total !== null) {
      $response['total'] = $total;
      $response['totalPages'] = (int)ceil($total / $limit);
    }
    
    json_ok($response);
  }
  
  /**
   * Format success response without pagination
   */
  public static function successSimple(array $data): void {
    json_ok($data);
  }
  
  /**
   * Format error response
   */
  public static function error(string $message, int $code = 400, array $errors = []): void {
    $response = [
      'success' => false,
      'error' => $message
    ];
    
    if (!empty($errors)) {
      $response['errors'] = $errors;
    }
    
    json_error($message, $code);
  }
  
  /**
   * Format pagination metadata
   */
  public static function paginationMeta(int $page, int $limit, int $total): array {
    return [
      'page' => $page,
      'limit' => $limit,
      'total' => $total,
      'totalPages' => (int)ceil($total / $limit),
      'hasNext' => ($page * $limit) < $total,
      'hasPrev' => $page > 1
    ];
  }
}

