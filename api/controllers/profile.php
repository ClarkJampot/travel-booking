<?php
declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../middleware/auth.php';
require_once __DIR__ . '/../helpers/ResponseHelper.php';
require_once __DIR__ . '/../helpers/ErrorHandler.php';
require_once __DIR__ . '/../helpers/Router.php';
require_once __DIR__ . '/../services/ProfileService.php';

try {
  $pdo = db_pdo();
  $profileService = new ProfileService($pdo);
} catch (Throwable $e) {
  ResponseHelper::error('Database connection failed', 500);
}

if (!isset($uri)) {
  $uri = Router::parseUri();
}

if ($_SERVER['REQUEST_METHOD'] === 'GET' && preg_match('#^/profile/?$#', $uri)) {
  requireAuth();
  
  $user = get_authenticated_user();
  $userId = (int)$user['id'];
  
  try {
    $result = $profileService->getProfile($userId);
    ResponseHelper::successSimple($result);
  } catch (RuntimeException $e) {
    $code = (int)($e->getCode() ?: 404);
    ResponseHelper::error($e->getMessage(), $code);
  } catch (Throwable $e) {
    ErrorHandler::logException($e);
    ResponseHelper::error('Failed to fetch profile', 500);
  }
}

ResponseHelper::error('Not found', 404);

