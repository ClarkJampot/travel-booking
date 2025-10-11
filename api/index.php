<?php
declare(strict_types=1);
require __DIR__ . '/bootstrap.php';

// Debug: Log the request URI
error_log('API Request URI: ' . $_SERVER['REQUEST_URI']);

// Debug endpoint
if (preg_match('#/api/debug/?$#', $_SERVER['REQUEST_URI']) || preg_match('#/travel-booking/api/debug/?$#', $_SERVER['REQUEST_URI'])) {
  json_ok([
    'request_uri' => $_SERVER['REQUEST_URI'],
    'script_name' => $_SERVER['SCRIPT_NAME'],
    'query_string' => $_SERVER['QUERY_STRING'] ?? '',
    'method' => $_SERVER['REQUEST_METHOD']
  ]);
  exit;
}

// Health check
if ($_SERVER['REQUEST_URI'] === '/travel-booking/api/' || $_SERVER['REQUEST_URI'] === '/api/' || preg_match('#/api/?$#', $_SERVER['REQUEST_URI'])) {
  json_ok(['ok' => true, 'message' => 'PHP API online']);
  exit;
}

// Route: /api/auth/*
if (preg_match('#/api/auth/#', $_SERVER['REQUEST_URI'])) {
  require __DIR__ . '/controllers/auth.php';
  exit;
}

// Route: /api/hotels
if (preg_match('#/api/hotels/?(\?.*)?$#', $_SERVER['REQUEST_URI'])) {
  require __DIR__ . '/controllers/hotels.php';
  exit;
}

// Route: /api/flights
if (preg_match('#/api/flights/?(\?.*)?$#', $_SERVER['REQUEST_URI'])) {
  require __DIR__ . '/controllers/flights.php';
  exit;
}

// Route: /api/activities
if (preg_match('#/api/activities/?(\?.*)?$#', $_SERVER['REQUEST_URI'])) {
  require __DIR__ . '/controllers/activities.php';
  exit;
}

// Route: /api/transfers
if (preg_match('#/api/transfers/?(\?.*)?$#', $_SERVER['REQUEST_URI'])) {
  require __DIR__ . '/controllers/transfers.php';
  exit;
}

// Route: /api/bookings
if (preg_match('#/api/bookings/#', $_SERVER['REQUEST_URI'])) {
  require __DIR__ . '/controllers/bookings.php';
  exit;
}

// Route: /api/upload
if (preg_match('#/api/upload/?$#', $_SERVER['REQUEST_URI'])) {
  require __DIR__ . '/controllers/upload.php';
  exit;
}

// Route: /api/ads
if (preg_match('#/api/ads/?(\?.*)?$#', $_SERVER['REQUEST_URI'])) {
  require __DIR__ . '/controllers/ads.php';
  exit;
}

// 404
json_error('Not found', 404);


