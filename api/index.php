<?php
// API Router
declare(strict_types=1);

require __DIR__ . '/bootstrap.php';

// Get request URI and normalize
$uri = $_SERVER['REQUEST_URI'] ?? '';
$basePath = '/travel-booking/api';
$apiPath = '/api';

// Remove base path if present
if (strpos($uri, $basePath) === 0) {
  $uri = substr($uri, strlen($basePath));
} elseif (strpos($uri, $apiPath) === 0) {
  $uri = substr($uri, strlen($apiPath));
}

// Remove query string (use parse_url for better reliability)
$parsed = parse_url($uri);
$uri = $parsed['path'] ?? $uri;

// Ensure URI starts with /
if ($uri === '' || ($uri[0] ?? '') !== '/') {
  $uri = '/' . $uri;
}

// Set normalized URI in global for controllers to use
$GLOBALS['API_URI'] = $uri;

// Health check
if ($uri === '' || $uri === '/') {
  json_ok(['ok' => true, 'message' => 'Travel Booking API v1.0']);
}

// Route: /api/auth/*
if (preg_match('#^/auth/(register|login|logout|me|refresh)/?$#', $uri, $matches)) {
  require __DIR__ . '/controllers/auth.php';
  exit;
}

// Route: /api/destinations
elseif (preg_match('#^/destinations(?:/(\d+))?/?$#', $uri, $matches)) {
  require __DIR__ . '/controllers/destinations.php';
  exit;
}

// Route: /api/hotels
elseif (preg_match('#^/hotels(?:/(\d+))?/?$#', $uri, $matches)) {
  require __DIR__ . '/controllers/hotels.php';
  exit;
}

// Route: /api/airports
elseif (preg_match('#^/airports(?:/(\d+))?/?$#', $uri, $matches)) {
  require __DIR__ . '/controllers/airports.php';
  exit;
}

// Route: /api/flights/routes
elseif (preg_match('#^/flights/routes(?:/(\d+))?/?$#', $uri, $matches)) {
  require __DIR__ . '/controllers/flight-routes.php';
  exit;
}

// Route: /api/flights/instances
elseif (preg_match('#^/flights/instances/?$#', $uri)) {
  require __DIR__ . '/controllers/flight-instances.php';
  exit;
}

// Route: /api/flights/book
elseif (preg_match('#^/flights/book/?$#', $uri)) {
  require __DIR__ . '/controllers/flight-instances.php';
  exit;
}

// Route: /api/flights (routes listing and detail)
elseif (preg_match('#^/flights(?:/(\d+))?/?$#', $uri, $matches)) {
  require __DIR__ . '/controllers/flight-instances.php';
  exit;
}

// Route: /api/transfer-instances
elseif (preg_match('#^/transfer-instances/?$#', $uri)) {
  require __DIR__ . '/controllers/transfer-instances.php';
  exit;
}

// Route: /api/transfers/book
elseif (preg_match('#^/transfers/book/?$#', $uri)) {
  require __DIR__ . '/controllers/transfer-instances.php';
  exit;
}

// Route: /api/transfers
elseif (preg_match('#^/transfers(?:/(\d+))?/?$#', $uri, $matches)) {
  require __DIR__ . '/controllers/transfers.php';
  exit;
}

// Route: /api/activities
elseif (preg_match('#^/activities(?:/(\d+))?/?$#', $uri, $matches)) {
  require __DIR__ . '/controllers/activities.php';
  exit;
}

// Route: /api/bookings
elseif (preg_match('#^/bookings(?:/(\d+))?/?$#', $uri, $matches)) {
  require __DIR__ . '/controllers/bookings.php';
  exit;
}

// Route: /api/ads
elseif (preg_match('#^/ads(?:/(\d+))?/?$#', $uri, $matches)) {
  require __DIR__ . '/controllers/ads.php';
  exit;
}

// Route: /api/upload
elseif (preg_match('#^/upload/?$#', $uri)) {
  require __DIR__ . '/controllers/upload.php';
  exit;
}

// Route: /api/search
elseif (preg_match('#^/search/?$#', $uri)) {
  require __DIR__ . '/controllers/search.php';
  exit;
}

// Route: /api/top/*
elseif (preg_match('#^/top/(hotels|flights)/?$#', $uri, $matches)) {
  require __DIR__ . '/controllers/top.php';
  exit;
}

// Route: /api/provinces and /api/cities
elseif (preg_match('#^/(provinces|cities)(?:/(\d+))?/?$#', $uri, $matches)) {
  require __DIR__ . '/controllers/locations.php';
  exit;
}

// Route: /api/profile
elseif (preg_match('#^/profile/?$#', $uri)) {
  require __DIR__ . '/controllers/profile.php';
  exit;
}

// Route: /api/dashboard
elseif (preg_match('#^/dashboard(?:/(bookings|items))?/?$#', $uri, $matches)) {
  require __DIR__ . '/controllers/dashboard.php';
  exit;
}

// Route: /api/promotions
elseif (preg_match('#^/promotions/?$#', $uri)) {
  require __DIR__ . '/controllers/promotions.php';
  exit;
}

// 404 Not Found
json_error('Not found', 404);
