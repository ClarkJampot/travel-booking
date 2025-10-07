<?php
// Auth middleware functions

function requireAuth(): void {
  if (!isset($_SESSION['user'])) {
    json_error('Unauthorized', 401);
    exit;
  }
}

function requireRole(array $allowedRoles): void {
  requireAuth();
  $userRole = $_SESSION['user']['role'] ?? '';
  if (!in_array($userRole, $allowedRoles)) {
    json_error('Forbidden', 403);
    exit;
  }
}

function getCurrentUser(): ?array {
  return $_SESSION['user'] ?? null;
}
