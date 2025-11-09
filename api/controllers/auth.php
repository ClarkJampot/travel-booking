<?php
// Authentication controller
declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../jwt.php';
require_once __DIR__ . '/../middleware/auth.php';

try {
  $pdo = db_pdo();
} catch (Throwable $e) {
  json_error('Database connection failed', 500);
}

$uri = $GLOBALS['API_URI'] ?? $_SERVER['REQUEST_URI'];

// POST /api/auth/register
if ($_SERVER['REQUEST_METHOD'] === 'POST' && preg_match('#^/auth/register/?$#', $uri)) {
  $input = json_decode(file_get_contents('php://input'), true);
  
  $email = trim($input['email'] ?? '');
  $password = $input['password'] ?? '';
  $full_name = trim($input['full_name'] ?? '');
  $role = $input['role'] ?? 'customer';
  
  // Validation
  if (!$email || !$password || !$full_name) {
    json_error('Missing required fields: email, password, full_name', 400);
  }
  
  if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    json_error('Invalid email format', 400);
  }
  
  if (strlen($password) < 6) {
    json_error('Password must be at least 6 characters', 400);
  }
  
  // Check if email exists
  $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ?');
  $stmt->execute([$email]);
  if ($stmt->fetch()) {
    json_error('Email already registered', 409);
  }
  
  // Get role_id
  $stmt = $pdo->prepare('SELECT id FROM roles WHERE name = ?');
  $stmt->execute([$role]);
  $roleRow = $stmt->fetch();
  if (!$roleRow) {
    json_error('Invalid role', 400);
  }
  
  // Create user
  $hash = password_hash($password, PASSWORD_DEFAULT);
  $stmt = $pdo->prepare('INSERT INTO users (email, password_hash, full_name, role_id) VALUES (?, ?, ?, ?)');
  $stmt->execute([$email, $hash, $full_name, $roleRow['id']]);
  $userId = (int)$pdo->lastInsertId();
  
  // Get user with role
  $stmt = $pdo->prepare('SELECT u.id, u.email, u.full_name, u.phone, u.address, r.name as role FROM users u JOIN roles r ON r.id = u.role_id WHERE u.id = ?');
  $stmt->execute([$userId]);
  $user = $stmt->fetch();
  
  // Generate JWT token
  $token = jwt_generate(['user_id' => $userId, 'email' => $email, 'role' => $user['role']]);
  jwt_store($userId, $token);
  
  json_ok([
    'user' => $user,
    'token' => $token
  ], 201);
}

// POST /api/auth/login
if ($_SERVER['REQUEST_METHOD'] === 'POST' && preg_match('#^/auth/login/?$#', $uri)) {
  $input = json_decode(file_get_contents('php://input'), true);
  
  $email = trim($input['email'] ?? '');
  $password = $input['password'] ?? '';
  
  if (!$email || !$password) {
    json_error('Missing email or password', 400);
  }
  
  // Get user with role
  $stmt = $pdo->prepare('SELECT u.id, u.email, u.password_hash, u.full_name, u.phone, u.address, r.name as role FROM users u JOIN roles r ON r.id = u.role_id WHERE u.email = ?');
  $stmt->execute([$email]);
  $user = $stmt->fetch();
  
  if (!$user || !password_verify($password, $user['password_hash'])) {
    json_error('Invalid credentials', 401);
  }
  
  // Generate JWT token
  $token = jwt_generate(['user_id' => $user['id'], 'email' => $user['email'], 'role' => $user['role']]);
  jwt_store($user['id'], $token);
  
  unset($user['password_hash']);
  
  json_ok([
    'user' => $user,
    'token' => $token
  ]);
}

// POST /api/auth/logout
if ($_SERVER['REQUEST_METHOD'] === 'POST' && preg_match('#^/auth/logout/?$#', $uri)) {
  $token = get_auth_token();
  if ($token) {
    jwt_revoke($token);
  }
  json_ok(['message' => 'Logged out successfully']);
}

// GET /api/auth/me
if ($_SERVER['REQUEST_METHOD'] === 'GET' && preg_match('#^/auth/me/?$#', $uri)) {
  $user = get_authenticated_user();
  if (!$user) {
    json_error('Unauthorized', 401);
  }
  json_ok(['user' => $user]);
}

// POST /api/auth/refresh
if ($_SERVER['REQUEST_METHOD'] === 'POST' && preg_match('#^/auth/refresh/?$#', $uri)) {
  $token = get_auth_token();
  if (!$token) {
    json_error('No token provided', 401);
  }
  
  $payload = jwt_validate($token);
  if (!$payload || !isset($payload['user_id'])) {
    json_error('Invalid token', 401);
  }
  
  // Revoke old token
  jwt_revoke($token);
  
  // Generate new token
  $newToken = jwt_generate(['user_id' => $payload['user_id'], 'email' => $payload['email'], 'role' => $payload['role']]);
  jwt_store($payload['user_id'], $newToken);
  
  json_ok(['token' => $newToken]);
}

json_error('Not found', 404);
