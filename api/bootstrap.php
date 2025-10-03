<?php
// Basic bootstrap for PHP API
declare(strict_types=1);

// Sessions
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

// CORS 
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
  http_response_code(204);
  exit;
}

// JSON helpers
function json_ok($data, int $code = 200): void {
  http_response_code($code);
  header('Content-Type: application/json');
  echo json_encode($data, JSON_UNESCAPED_UNICODE);
}

function json_error(string $message, int $code = 400): void {
  http_response_code($code);
  header('Content-Type: application/json');
  echo json_encode(['error' => $message], JSON_UNESCAPED_UNICODE);
}


