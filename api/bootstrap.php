<?php
declare(strict_types=1);

if (!file_exists(__DIR__ . '/config.php')) {
  http_response_code(500);
  header('Content-Type: application/json');
  echo json_encode(['error' => 'Configuration file missing. Copy config.sample.php to config.php and configure.']);
  exit;
}
require_once __DIR__ . '/config.php';

$requiredConstants = [
  'DB_SERVER', 'DB_DATABASE', 'DB_UID', 'DB_PWD',
  'JWT_SECRET', 'JWT_EXPIRY',
  'UPLOAD_MAX_SIZE', 'UPLOAD_ALLOWED_TYPES', 'UPLOAD_CATEGORIES',
  'ENVIRONMENT'
];

foreach ($requiredConstants as $constant) {
  if (!defined($constant)) {
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode(['error' => "Configuration error: Missing constant {$constant}"]);
    exit;
  }
}

if (JWT_SECRET === 'your-secret-key-change-this-in-production' && ENVIRONMENT === 'production') {
  http_response_code(500);
  header('Content-Type: application/json');
  echo json_encode(['error' => 'Configuration error: JWT_SECRET must be changed in production']);
  exit;
}

$isDevelopment = ENVIRONMENT === 'development';
error_reporting($isDevelopment ? E_ALL : 0);
ini_set('display_errors', $isDevelopment ? '1' : '0');
ini_set('log_errors', '1');

$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
$allowedOrigins = defined('CORS_ALLOWED_ORIGINS') ? CORS_ALLOWED_ORIGINS : ['http://localhost', 'http://localhost/travel-booking'];

if (in_array($origin, $allowedOrigins)) {
  header('Access-Control-Allow-Origin: ' . $origin);
} elseif (ENVIRONMENT === 'development') {
  header('Access-Control-Allow-Origin: *');
}

header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
  http_response_code(204);
  exit;
}

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

require_once __DIR__ . '/helpers/ErrorHandler.php';

set_error_handler(function($severity, $message, $file, $line) {
  if (error_reporting() & $severity) {
    ErrorHandler::logError($message, $file, $line);
    if (ENVIRONMENT === 'production') {
      json_error('Internal server error', 500);
    } else {
      json_error('Internal server error: ' . $message, 500);
    }
  }
});

set_exception_handler(function($exception) {
  ErrorHandler::logException($exception);
  if (ENVIRONMENT === 'production') {
    json_error('Internal server error', 500);
  } else {
    json_error('Internal server error: ' . $exception->getMessage(), 500);
  }
});
