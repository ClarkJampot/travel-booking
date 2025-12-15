<?php
declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../middleware/auth.php';
require_once __DIR__ . '/../helpers/ResponseHelper.php';
require_once __DIR__ . '/../helpers/ErrorHandler.php';
require_once __DIR__ . '/../services/DashboardService.php';

try {
  $pdo = db_pdo();
  $dashboardService = new DashboardService($pdo);
} catch (Throwable $e) {
  ResponseHelper::error('Database connection failed', 500);
}

if ($_SERVER['REQUEST_METHOD'] === 'GET' && preg_match('#^/dashboard/?$#', $uri)) {
  requireAuth();
  requireRole(['owner', 'agency']);
  
  $user = get_authenticated_user();
  
  try {
    $stats = $dashboardService->getStats((int)$user['id']);
    ResponseHelper::successSimple(['stats' => $stats]);
  } catch (Throwable $e) {
    ErrorHandler::logException($e);
    ResponseHelper::error('Failed to fetch dashboard stats', 500);
  }
  exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'GET' && preg_match('#^/dashboard/bookings/?$#', $uri)) {
  requireAuth();
  requireRole(['owner', 'agency']);
  
  $user = get_authenticated_user();
  $itemType = $_GET['item_type'] ?? null;
  $status = $_GET['status'] ?? null;
  $customerName = $_GET['customer_name'] ?? null;
  $keyword = $_GET['keyword'] ?? null;
  $page = max(1, (int)($_GET['page'] ?? 1));
  $limit = min(50, max(1, (int)($_GET['limit'] ?? 20)));
  
  try {
    $result = $dashboardService->getBookings((int)$user['id'], $itemType, $status, $customerName, $keyword, $page, $limit);
    ResponseHelper::successSimple($result);
  } catch (Throwable $e) {
    ErrorHandler::logException($e);
    ResponseHelper::error('Failed to fetch dashboard bookings', 500);
  }
  exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'GET' && preg_match('#^/dashboard/items/?$#', $uri)) {
  requireAuth();
  requireRole(['owner', 'agency']);
  
  $user = get_authenticated_user();
  $itemType = $_GET['item_type'] ?? null;
  
  try {
    $result = $dashboardService->getItems((int)$user['id'], $itemType);
    ResponseHelper::successSimple($result);
  } catch (Throwable $e) {
    ErrorHandler::logException($e);
    ResponseHelper::error('Failed to fetch dashboard items', 500);
  }
  exit;
}

ResponseHelper::error('Not found', 404);
