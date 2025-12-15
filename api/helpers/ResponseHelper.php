<?php
declare(strict_types=1);

class ResponseHelper {
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
  
  public static function successSimple(array $data, int $code = 200): void {
    http_response_code($code);
    json_ok($data, $code);
  }
  
  public static function error(string $message, int $code = 400, array $errors = []): void {
    json_error($message, $code, $errors);
  }
  
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

