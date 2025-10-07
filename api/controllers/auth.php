<?php
declare(strict_types=1);
require_once __DIR__ . '/../bootstrap.php';

// Load DB
if (!file_exists(__DIR__ . '/../db.php')) {
  json_error('Missing api/db.php', 500);
  exit;
}
require_once __DIR__ . '/../db.php';

try {
  $pdo = db_pdo();
} catch (Throwable $e) {
  json_error('DB connection failed: ' . $e->getMessage(), 500);
  exit;
}

// POST /api/auth/register
if ($_SERVER['REQUEST_METHOD'] === 'POST' && preg_match('#/api/auth/register/?$#', $_SERVER['REQUEST_URI'])) {
  $input = json_decode(file_get_contents('php://input'), true);
  $email = $input['email'] ?? '';
  $password = $input['password'] ?? '';
  $full_name = $input['full_name'] ?? '';
  $role = $input['role'] ?? 'customer';

  if (!$email || !$password || !$full_name) {
    json_error('Missing required fields: email, password, full_name', 400);
    exit;
  }

  // Check if email exists
  $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ?');
  $stmt->execute([$email]);
  if ($stmt->fetch()) {
    json_error('Email already registered', 409);
    exit;
  }

  // Get role_id
  $stmt = $pdo->prepare('SELECT id FROM roles WHERE name = ?');
  $stmt->execute([$role]);
  $roleRow = $stmt->fetch();
  if (!$roleRow) {
    json_error('Invalid role', 400);
    exit;
  }

  // Create user
  $hash = password_hash($password, PASSWORD_DEFAULT);
  $stmt = $pdo->prepare('INSERT INTO users (email, password_hash, full_name, role_id) VALUES (?, ?, ?, ?)');
  $stmt->execute([$email, $hash, $full_name, $roleRow['id']]);
  $userId = $pdo->lastInsertId();

  // Get user with role
  $stmt = $pdo->prepare('SELECT u.id, u.email, u.full_name, r.name as role FROM users u JOIN roles r ON r.id = u.role_id WHERE u.id = ?');
  $stmt->execute([$userId]);
  $user = $stmt->fetch();

  // Set session
  $_SESSION['user'] = [
    'id' => $user['id'],
    'email' => $user['email'],
    'name' => $user['full_name'],
    'role' => $user['role']
  ];

  json_ok(['user' => $_SESSION['user']], 201);
  exit;
}

// POST /api/auth/login
if ($_SERVER['REQUEST_METHOD'] === 'POST' && preg_match('#/api/auth/login/?$#', $_SERVER['REQUEST_URI'])) {
  $input = json_decode(file_get_contents('php://input'), true);
  $email = $input['email'] ?? '';
  $password = $input['password'] ?? '';

  if (!$email || !$password) {
    json_error('Missing email or password', 400);
    exit;
  }

  // Get user with role
  $stmt = $pdo->prepare('SELECT u.id, u.email, u.password_hash, u.full_name, r.name as role FROM users u JOIN roles r ON r.id = u.role_id WHERE u.email = ?');
  $stmt->execute([$email]);
  $user = $stmt->fetch();

  if (!$user || !password_verify($password, $user['password_hash'])) {
    json_error('Invalid credentials', 401);
    exit;
  }

  // Set session
  $_SESSION['user'] = [
    'id' => $user['id'],
    'email' => $user['email'],
    'name' => $user['full_name'],
    'role' => $user['role']
  ];

  json_ok(['user' => $_SESSION['user']]);
  exit;
}

// POST /api/auth/logout
if ($_SERVER['REQUEST_METHOD'] === 'POST' && preg_match('#/api/auth/logout/?$#', $_SERVER['REQUEST_URI'])) {
  if (session_status() === PHP_SESSION_ACTIVE) {
    session_destroy();
  }
  json_ok(['ok' => true]);
  exit;
}

// GET /api/auth/me
if ($_SERVER['REQUEST_METHOD'] === 'GET' && preg_match('#/api/auth/me/?$#', $_SERVER['REQUEST_URI'])) {
  json_ok(['user' => $_SESSION['user'] ?? null]);
  exit;
}

json_error('Not found', 404);
