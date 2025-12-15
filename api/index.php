<?php
declare(strict_types=1);

require __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/helpers/Router.php';
require_once __DIR__ . '/helpers/ResponseHelper.php';

$uri = Router::parseUri();

if ($uri === '' || $uri === '/') {
  ResponseHelper::successSimple(['ok' => true, 'message' => 'Travel Booking API v1.0']);
}

if (preg_match('#^/auth/(register|login|logout|me|refresh)/?$#', $uri, $matches)) {
  require __DIR__ . '/controllers/auth.php';
  exit;
}

elseif (preg_match('#^/destinations(?:/(\d+))?/?$#', $uri, $matches)) {
  require __DIR__ . '/controllers/destinations.php';
  exit;
}

elseif (preg_match('#^/hotels(?:/(\d+))?/?$#', $uri, $matches)) {
  require __DIR__ . '/controllers/hotels.php';
  exit;
}

elseif (preg_match('#^/airports(?:/(\d+))?/?$#', $uri, $matches)) {
  require __DIR__ . '/controllers/airports.php';
  exit;
}

elseif (preg_match('#^/flights/routes(?:/(\d+))?/?$#', $uri, $matches)) {
  require __DIR__ . '/controllers/flight-routes.php';
  exit;
}

elseif (preg_match('#^/flights/instances/?$#', $uri)) {
  require __DIR__ . '/controllers/flight-instances.php';
  exit;
}

elseif (preg_match('#^/flights/book/?$#', $uri)) {
  require __DIR__ . '/controllers/flight-instances.php';
  exit;
}

elseif (preg_match('#^/flights/availability/?$#', $uri)) {
  require __DIR__ . '/controllers/flight-instances.php';
  exit;
}

elseif (preg_match('#^/flights(?:/(\d+))?/?$#', $uri, $matches)) {
  require __DIR__ . '/controllers/flight-instances.php';
  exit;
}

elseif (preg_match('#^/transfer-instances/?$#', $uri)) {
  require __DIR__ . '/controllers/transfer-instances.php';
  exit;
}

elseif (preg_match('#^/transfers/book/?$#', $uri)) {
  require __DIR__ . '/controllers/transfer-instances.php';
  exit;
}

elseif (preg_match('#^/transfers/availability/?$#', $uri)) {
  require __DIR__ . '/controllers/transfer-instances.php';
  exit;
}

elseif (preg_match('#^/transfers/routes(?:/(\d+))?/?$#', $uri, $matches)) {
  require __DIR__ . '/controllers/transfer-routes.php';
  exit;
}

elseif (preg_match('#^/transfers(?:/(\d+))?/?$#', $uri, $matches)) {
  require __DIR__ . '/controllers/transfer-instances.php';
  exit;
}

elseif (preg_match('#^/activities(?:/(\d+))?/?$#', $uri, $matches)) {
  require __DIR__ . '/controllers/activities.php';
  exit;
}

elseif (preg_match('#^/bookings(?:/(\d+))?/?$#', $uri, $matches)) {
  require __DIR__ . '/controllers/bookings.php';
  exit;
}

elseif (preg_match('#^/ads(?:/(\d+))?/?$#', $uri, $matches)) {
  require __DIR__ . '/controllers/ads.php';
  exit;
}

elseif (preg_match('#^/upload/?$#', $uri)) {
  require __DIR__ . '/controllers/upload.php';
  exit;
}

elseif (preg_match('#^/cleanup/#', $uri)) {
  require __DIR__ . '/controllers/cleanup.php';
  exit;
}

elseif (preg_match('#^/search/?$#', $uri)) {
  require __DIR__ . '/controllers/search.php';
  exit;
}

elseif (preg_match('#^/top/(hotels|flights|activities)/?$#', $uri, $matches)) {
  require __DIR__ . '/controllers/top.php';
  exit;
}

elseif (preg_match('#^/(provinces|cities)(?:/(\d+))?/?$#', $uri, $matches)) {
  require __DIR__ . '/controllers/locations.php';
  exit;
}

elseif (preg_match('#^/profile/?$#', $uri)) {
  require __DIR__ . '/controllers/profile.php';
  exit;
}

elseif (preg_match('#^/dashboard(?:/(bookings|items))?/?$#', $uri, $matches)) {
  require __DIR__ . '/controllers/dashboard.php';
  exit;
}

elseif (preg_match('#^/promotions/?$#', $uri)) {
  require __DIR__ . '/controllers/promotions.php';
  exit;
}

ResponseHelper::error('Not found', 404);
