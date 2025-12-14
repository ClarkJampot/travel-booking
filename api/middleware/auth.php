<?php
// Authentication middleware functions
declare(strict_types=1);

require_once __DIR__ . '/../jwt.php';
require_once __DIR__ . '/../db.php';

/**
 * Get JWT token from request headers
 * @return string|null Token or null
 */
function get_auth_token(): ?string {
  $headers = getallheaders();
  if (isset($headers['Authorization'])) {
    $auth = $headers['Authorization'];
    if (preg_match('/Bearer\s+(.*)$/i', $auth, $matches)) {
      return $matches[1];
    }
  }
  return null;
}

/**
 * Get current authenticated user from JWT token
 * @return array|null User data or null
 */
function get_authenticated_user(): ?array {
  $token = get_auth_token();
  if (!$token) {
    return null;
  }
  
  // Validate token
  $payload = jwt_validate($token);
  if (!$payload) {
    return null;
  }
  
  // Check if token is revoked
  if (jwt_is_revoked($token)) {
    return null;
  }
  
  // Get user from database
  try {
    $pdo = db_pdo();
    $stmt = $pdo->prepare('SELECT u.id, u.email, u.first_name, u.last_name, r.name as role FROM users u JOIN roles r ON r.id = u.role_id WHERE u.id = ?');
    $stmt->execute([$payload['user_id']]);
    $user = $stmt->fetch();
    
    if ($user) {
      $user['role'] = $user['role'];
      return $user;
    }
  } catch (Exception $e) {
    return null;
  }
  
  return null;
}

/**
 * Require authentication - exits if not authenticated
 */
function requireAuth(): void {
  $user = get_authenticated_user();
  if (!$user) {
    json_error('Unauthorized', 401);
  }
}

/**
 * Require specific role(s) - exits if user doesn't have required role
 * @param array $allowedRoles Allowed roles
 */
function requireRole(array $allowedRoles): void {
  requireAuth();
  $user = get_authenticated_user();
  if (!$user || !in_array($user['role'], $allowedRoles)) {
    json_error('Forbidden', 403);
  }
}

/**
 * Require customer role - exits if user is not a customer
 */
function requireCustomer(): void {
  requireAuth();
  $user = get_authenticated_user();
  if (!$user || $user['role'] !== 'customer') {
    json_error('Only customers can make bookings. Please log in with a customer account or register as a customer.', 403);
  }
}
