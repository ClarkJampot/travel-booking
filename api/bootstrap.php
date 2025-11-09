<?php
// Bootstrap file for PHP API
declare(strict_types=1);

// Error reporting (disable in production)
error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');

// Load configuration
if (!file_exists(__DIR__ . '/config.php')) {
  http_response_code(500);
  header('Content-Type: application/json');
  echo json_encode(['error' => 'Configuration file missing']);
  exit;
}
require_once __DIR__ . '/config.php';

// CORS headers
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
  http_response_code(204);
  exit;
}

// JSON response helpers
function json_ok($data, int $code = 200): void {
  http_response_code($code);
  header('Content-Type: application/json');
  echo json_encode($data, JSON_UNESCAPED_UNICODE);
  exit;
}

function json_error(string $message, int $code = 400, array $data = []): void {
  http_response_code($code);
  header('Content-Type: application/json');
  $response = ['error' => $message];
  if (!empty($data)) {
    $response = array_merge($response, $data);
  }
  echo json_encode($response, JSON_UNESCAPED_UNICODE);
  exit;
}

// Error handler
set_error_handler(function($severity, $message, $file, $line) {
  if (error_reporting() & $severity) {
    json_error('Internal server error', 500);
  }
});

// Exception handler
set_exception_handler(function($exception) {
  error_log('Uncaught exception: ' . $exception->getMessage() . ' in ' . $exception->getFile() . ':' . $exception->getLine());
  json_error('Internal server error', 500);
});
