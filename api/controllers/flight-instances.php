<?php
declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../middleware/auth.php';
require_once __DIR__ . '/../helpers/ResponseHelper.php';
require_once __DIR__ . '/../helpers/ErrorHandler.php';
require_once __DIR__ . '/../services/FlightService.php';

try {
  $pdo = db_pdo();
  $flightService = new FlightService($pdo);
} catch (Throwable $e) {
  ResponseHelper::error('Database connection failed', 500);
}


if ($_SERVER['REQUEST_METHOD'] === 'GET' && preg_match('#^/flights/availability/?$#', $uri)) {
  $routeId = isset($_GET['route_id']) ? (int)$_GET['route_id'] : null;
  $isReturn = isset($_GET['return']) && $_GET['return'] === 'true';
  
  if (!$routeId) {
    ResponseHelper::error('route_id is required', 400);
  }
  
  try {
    $availableDates = $flightService->getAvailableDates($routeId, $isReturn);
    ResponseHelper::successSimple(['available_dates' => $availableDates]);
  } catch (Throwable $e) {
    ErrorHandler::logException($e);
    ResponseHelper::error('Failed to fetch available dates', 500);
  }
  exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'GET' && preg_match('#^/flights/?$#', $uri)) {
  $id = isset($_GET['id']) ? (int)$_GET['id'] : null;
  
  if ($id) {
    try {
      $flight = $flightService->getFlightById($id);
      if (!$flight) {
        ResponseHelper::error('Flight route or instance not found', 404);
      }
      ResponseHelper::successSimple(['flight' => $flight]);
    } catch (Throwable $e) {
      ErrorHandler::logException($e);
      ResponseHelper::error('Failed to fetch flight', 500);
    }
    exit;
  }
  
  $filters = [];
  if (isset($_GET['origin_airport_id'])) {
    $filters['origin_airport_id'] = (int)$_GET['origin_airport_id'];
  }
  if (isset($_GET['destination_airport_id'])) {
    $filters['destination_airport_id'] = (int)$_GET['destination_airport_id'];
  }
  if (isset($_GET['origin_city_id'])) {
    $filters['origin_city_id'] = (int)$_GET['origin_city_id'];
  }
  if (isset($_GET['destination_city_id'])) {
    $filters['destination_city_id'] = (int)$_GET['destination_city_id'];
  }
  if (isset($_GET['min_price'])) {
    $filters['min_price'] = (float)$_GET['min_price'];
  }
  if (isset($_GET['max_price'])) {
    $filters['max_price'] = (float)$_GET['max_price'];
  }
  
  $page = max(1, (int)($_GET['page'] ?? 1));
  $limit = min(50, max(1, (int)($_GET['limit'] ?? 12)));
  
  try {
    $result = $flightService->listRoutes($filters, $page, $limit);
    ResponseHelper::successSimple($result);
  } catch (Throwable $e) {
    ErrorHandler::logException($e);
    ResponseHelper::error('Failed to fetch flights', 500);
  }
  exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'GET' && preg_match('#^/flights/instances/?$#', $uri)) {
  $routeId = isset($_GET['route_id']) ? (int)$_GET['route_id'] : null;
  $departureDate = $_GET['departure_date'] ?? null;
  $returnDate = $_GET['return_date'] ?? null;
  $class = $_GET['class'] ?? 'economy';
  $passengerCount = isset($_GET['passenger_count']) ? (int)$_GET['passenger_count'] : 1;
  
  if (!$routeId || !$departureDate) {
    ResponseHelper::error('Missing required parameters: route_id, departure_date', 400);
  }
  
  try {
    $result = $flightService->getInstances($routeId, $departureDate, $returnDate, $class, $passengerCount);
    ResponseHelper::successSimple($result);
  } catch (InvalidArgumentException $e) {
    ResponseHelper::error($e->getMessage(), 400);
  } catch (Throwable $e) {
    ErrorHandler::logException($e);
    ResponseHelper::error('Failed to fetch flight instances', 500);
  }
  exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && preg_match('#^/flights/book/?$#', $uri)) {
  requireCustomer();
  
  $user = get_authenticated_user();
  $input = json_decode(file_get_contents('php://input'), true);
  
  $instanceId = isset($input['instance_id']) ? (int)$input['instance_id'] : null;
  $class = trim($input['class'] ?? 'economy');
  $passengerCount = isset($input['passenger_count']) ? (int)$input['passenger_count'] : 1;
  
  if (!$instanceId || !in_array($class, ['economy', 'business', 'first']) || $passengerCount < 1) {
    ResponseHelper::error('Missing or invalid required fields: instance_id, class, passenger_count', 400);
  }
  
  try {
    $booking = $flightService->bookFlight((int)$user['id'], $instanceId, $class, $passengerCount);
    ResponseHelper::successSimple(['booking' => $booking], 201);
  } catch (InvalidArgumentException $e) {
    ResponseHelper::error($e->getMessage(), 400);
  } catch (RuntimeException $e) {
    $code = (int)($e->getCode() ?: 400);
    ResponseHelper::error($e->getMessage(), $code);
  } catch (Throwable $e) {
    ErrorHandler::logException($e);
    ResponseHelper::error('Booking failed', 500);
  }
}

ResponseHelper::error('Not found', 404);

