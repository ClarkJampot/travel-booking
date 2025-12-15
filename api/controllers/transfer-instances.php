<?php
declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../middleware/auth.php';
require_once __DIR__ . '/../helpers/ResponseHelper.php';
require_once __DIR__ . '/../helpers/ErrorHandler.php';
require_once __DIR__ . '/../services/TransferService.php';

try {
  $pdo = db_pdo();
  $transferService = new TransferService($pdo);
} catch (Throwable $e) {
  ResponseHelper::error('Database connection failed', 500);
}

if ($_SERVER['REQUEST_METHOD'] === 'GET' && preg_match('#^/transfer-instances/?$#', $uri)) {
  $transferId = isset($_GET['transfer_id']) ? (int)$_GET['transfer_id'] : null;
  $date = $_GET['date'] ?? null;
  $passengerCount = isset($_GET['passenger_count']) ? (int)$_GET['passenger_count'] : 1;
  
  if (!$transferId || !$date) {
    ResponseHelper::error('transfer_id and date are required', 400);
  }
  
  try {
    $instances = $transferService->getInstancesForRoute($transferId, $date, $passengerCount);
    ResponseHelper::successSimple(['instances' => $instances]);
  } catch (InvalidArgumentException $e) {
    ResponseHelper::error($e->getMessage(), 400);
  } catch (Throwable $e) {
    ErrorHandler::logException($e);
    ResponseHelper::error('Failed to fetch transfer instances', 500);
  }
  exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'GET' && preg_match('#^/transfers/availability/?$#', $uri)) {
  $transferId = isset($_GET['transfer_id']) ? (int)$_GET['transfer_id'] : null;
  
  if (!$transferId) {
    ResponseHelper::error('transfer_id is required', 400);
  }
  
  try {
    $availableDates = $transferService->getAvailableDates($transferId);
    ResponseHelper::successSimple(['available_dates' => $availableDates]);
  } catch (Throwable $e) {
    ErrorHandler::logException($e);
    ResponseHelper::error('Failed to fetch available dates', 500);
  }
  exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'GET' && preg_match('#^/transfers/?$#', $uri)) {
  $id = isset($_GET['id']) ? (int)$_GET['id'] : null;
  
  if ($id) {
    try {
      $transfer = $transferService->getTransferById($id);
      if (!$transfer) {
        ResponseHelper::error('Transfer route or instance not found', 404);
      }
      ResponseHelper::successSimple(['transfer' => $transfer]);
    } catch (Throwable $e) {
      ErrorHandler::logException($e);
      ResponseHelper::error('Failed to fetch transfer', 500);
    }
    exit;
  }
  
  $filters = [];
  if (isset($_GET['origin_city_id'])) {
    $filters['origin_city_id'] = (int)$_GET['origin_city_id'];
  }
  if (isset($_GET['destination_city_id'])) {
    $filters['destination_city_id'] = (int)$_GET['destination_city_id'];
  }
  if (isset($_GET['origin_province_id'])) {
    $filters['origin_province_id'] = (int)$_GET['origin_province_id'];
  }
  if (isset($_GET['destination_province_id'])) {
    $filters['destination_province_id'] = (int)$_GET['destination_province_id'];
  }
  if (isset($_GET['minPrice'])) {
    $filters['min_price'] = (float)$_GET['minPrice'];
  }
  if (isset($_GET['maxPrice'])) {
    $filters['max_price'] = (float)$_GET['maxPrice'];
  }
  
  $date = $_GET['date'] ?? null;
  $passengerCount = isset($_GET['passenger_count']) ? (int)$_GET['passenger_count'] : 1;
  $page = max(1, (int)($_GET['page'] ?? 1));
  $limit = min(50, max(1, (int)($_GET['limit'] ?? 12)));
  
  try {
    if (!$date) {
      $result = $transferService->listRoutes($filters, $page, $limit);
    } else {
      $result = $transferService->listInstances($filters, $date, $passengerCount, $page, $limit);
    }
    ResponseHelper::successSimple($result);
  } catch (InvalidArgumentException $e) {
    ResponseHelper::error($e->getMessage(), 400);
  } catch (Throwable $e) {
    ErrorHandler::logException($e);
    ResponseHelper::error('Failed to fetch transfers', 500);
  }
  exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && preg_match('#^/transfers/book/?$#', $uri)) {
  requireCustomer();
  
  $user = get_authenticated_user();
  $input = json_decode(file_get_contents('php://input'), true);
  
  $instanceId = isset($input['instance_id']) ? (int)$input['instance_id'] : null;
  $passengerCount = isset($input['passenger_count']) ? (int)$input['passenger_count'] : 1;
  
  if (!$instanceId || $passengerCount < 1) {
    ResponseHelper::error('Missing or invalid required fields: instance_id, passenger_count', 400);
  }
  
  try {
    $booking = $transferService->bookTransfer((int)$user['id'], $instanceId, $passengerCount);
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
