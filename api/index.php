<?php
declare(strict_types=1);
require __DIR__ . '/bootstrap.php';

// Health check
if ($_SERVER['REQUEST_URI'] === '/travel-booking/api/' || $_SERVER['REQUEST_URI'] === '/api/' || preg_match('#/api/?$#', $_SERVER['REQUEST_URI'])) {
  json_ok(['ok' => true, 'message' => 'PHP API online']);
  exit;
}

// Route: /api/hotels
if (preg_match('#/api/hotels/?$#', $_SERVER['REQUEST_URI'])) {
  require __DIR__ . '/controllers/hotels.php';
  exit;
}

// 404
json_error('Not found', 404);


