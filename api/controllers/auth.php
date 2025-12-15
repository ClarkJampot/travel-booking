<?php
declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../jwt.php';
require_once __DIR__ . '/../middleware/auth.php';
require_once __DIR__ . '/../helpers/ResponseHelper.php';
require_once __DIR__ . '/../services/AuthService.php';
require_once __DIR__ . '/../helpers/ErrorHandler.php';

try {
  $pdo = db_pdo();
  $authService = new AuthService($pdo);
} catch (Throwable $e) {
  ResponseHelper::error('Database connection failed', 500);
}


if ($_SERVER['REQUEST_METHOD'] === 'POST' && preg_match('#^/auth/register/?$#', $uri)) {
  $input = json_decode(file_get_contents('php://input'), true);
  
  try {
    $result = $authService->register($input);
    ResponseHelper::successSimple($result, 201);
  } catch (InvalidArgumentException $e) {
    ResponseHelper::error($e->getMessage(), 400);
  } catch (RuntimeException $e) {
    $code = (int)($e->getCode() ?: 400);
    ResponseHelper::error($e->getMessage(), $code);
  } catch (Throwable $e) {
    ErrorHandler::logException($e);
    ResponseHelper::error('Registration failed', 500);
  }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && preg_match('#^/auth/login/?$#', $uri)) {
  $input = json_decode(file_get_contents('php://input'), true);
  
  try {
    $result = $authService->login(trim($input['email'] ?? ''), $input['password'] ?? '');
    ResponseHelper::successSimple($result);
  } catch (InvalidArgumentException $e) {
    ResponseHelper::error($e->getMessage(), 400);
  } catch (RuntimeException $e) {
    $code = (int)($e->getCode() ?: 401);
    ResponseHelper::error($e->getMessage(), $code);
  } catch (Throwable $e) {
    ErrorHandler::logException($e);
    ResponseHelper::error('Login failed', 500);
  }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && preg_match('#^/auth/logout/?$#', $uri)) {
  $token = get_auth_token();
  if ($token) {
    jwt_revoke($token);
  }
  ResponseHelper::successSimple(['message' => 'Logged out successfully']);
}

if ($_SERVER['REQUEST_METHOD'] === 'GET' && preg_match('#^/auth/me/?$#', $uri)) {
  $user = get_authenticated_user();
  if (!$user) {
    ResponseHelper::error('Unauthorized', 401);
  }
  ResponseHelper::successSimple(['user' => $user]);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && preg_match('#^/auth/refresh/?$#', $uri)) {
  $token = get_auth_token();
  if (!$token) {
    ResponseHelper::error('No token provided', 401);
  }
  
  try {
    $newToken = $authService->refreshToken($token);
    ResponseHelper::successSimple(['token' => $newToken]);
  } catch (RuntimeException $e) {
    $code = (int)($e->getCode() ?: 401);
    ResponseHelper::error($e->getMessage(), $code);
  } catch (Throwable $e) {
    ErrorHandler::logException($e);
    ResponseHelper::error('Token refresh failed', 500);
  }
}

ResponseHelper::error('Not found', 404);
