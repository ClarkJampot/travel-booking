<?php
declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../middleware/auth.php';
require_once __DIR__ . '/../helpers/QueryBuilder.php';
require_once __DIR__ . '/../helpers/FilterHelper.php';
require_once __DIR__ . '/../helpers/ImageHelper.php';
require_once __DIR__ . '/../helpers/ResponseHelper.php';
require_once __DIR__ . '/../helpers/PromotionHelper.php';
require_once __DIR__ . '/../helpers/ErrorHandler.php';
require_once __DIR__ . '/../helpers/Router.php';
require_once __DIR__ . '/../services/HotelService.php';

try {
  $pdo = db_pdo();
  $hotelService = new HotelService($pdo);
} catch (Throwable $e) {
  ResponseHelper::error('Database connection failed', 500);
}

if (!isset($uri)) {
  $uri = Router::parseUri();
}

if ($_SERVER['REQUEST_METHOD'] === 'GET' && preg_match('#^/hotels/?$#', $uri)) {
  $id = isset($_GET['id']) ? (int)$_GET['id'] : null;
  
  if ($id) {
    try {
      $hotel = $hotelService->getById($id);
      if (!$hotel) {
        ResponseHelper::error('Hotel not found', 404);
      }
      ResponseHelper::successSimple(['hotel' => $hotel]);
    } catch (Throwable $e) {
      ErrorHandler::logException($e);
      ResponseHelper::error('Failed to fetch hotel', 500);
    }
  } else {
    try {
      $filters = [];
      if (isset($_GET['destination_id'])) {
        $filters['destination_id'] = (int)$_GET['destination_id'];
      }
      $response = $hotelService->list($filters);
      ResponseHelper::successSimple($response);
    } catch (Throwable $e) {
      ErrorHandler::logException($e);
      ResponseHelper::error('Database query failed', 500);
    }
  }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && preg_match('#^/hotels/?$#', $uri)) {
  requireRole(['owner', 'admin']);
  
  $user = get_authenticated_user();
  $input = json_decode(file_get_contents('php://input'), true);
  $input['user_role'] = $user['role'];
  
  try {
    $hotel = $hotelService->create($input, (int)$user['id']);
    ResponseHelper::successSimple(['hotel' => $hotel], 201);
  } catch (InvalidArgumentException $e) {
    ResponseHelper::error($e->getMessage(), 400);
  } catch (Throwable $e) {
    ErrorHandler::logException($e);
    ResponseHelper::error('Failed to create hotel', 500);
  }
}

if ($_SERVER['REQUEST_METHOD'] === 'PUT' && preg_match('#^/hotels/(\d+)/?$#', $uri, $matches)) {
  requireRole(['owner', 'admin']);
  
  $id = (int)$matches[1];
  $user = get_authenticated_user();
  $input = json_decode(file_get_contents('php://input'), true);
  
  try {
    $hotel = $hotelService->update($id, $input, (int)$user['id'], $user['role']);
    ResponseHelper::successSimple(['hotel' => $hotel]);
  } catch (InvalidArgumentException $e) {
    ResponseHelper::error($e->getMessage(), 400);
  } catch (RuntimeException $e) {
    $code = (int)($e->getCode() ?: 400);
    ResponseHelper::error($e->getMessage(), $code);
  } catch (Throwable $e) {
    ErrorHandler::logException($e);
    ResponseHelper::error('Failed to update hotel', 500);
  }
}

if ($_SERVER['REQUEST_METHOD'] === 'DELETE' && preg_match('#^/hotels/(\d+)/?$#', $uri, $matches)) {
  requireRole(['owner', 'admin']);
  
  $id = (int)$matches[1];
  $user = get_authenticated_user();
  
  try {
    $hotelService->delete($id, (int)$user['id'], $user['role']);
    ResponseHelper::successSimple(['message' => 'Hotel deleted successfully']);
  } catch (RuntimeException $e) {
    $code = (int)($e->getCode() ?: 400);
    ResponseHelper::error($e->getMessage(), $code);
  } catch (Throwable $e) {
    ErrorHandler::logException($e);
    ResponseHelper::error('Failed to delete hotel', 500);
  }
}

ResponseHelper::error('Not found', 404);
