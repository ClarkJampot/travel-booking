<?php
declare(strict_types=1);

require_once __DIR__ . '/BaseService.php';
require_once __DIR__ . '/../jwt.php';
require_once __DIR__ . '/../helpers/ErrorHandler.php';

class AuthService extends BaseService {
  public function register(array $data): array {
    $email = trim($data['email'] ?? '');
    $password = $data['password'] ?? '';
    $firstName = trim($data['first_name'] ?? '');
    $lastName = trim($data['last_name'] ?? '');
    $role = $data['role'] ?? 'customer';
    
    if (!$email || !$password || !$firstName) {
      throw new InvalidArgumentException('Missing required fields: email, password, first_name');
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
      throw new InvalidArgumentException('Invalid email format');
    }
    
    if (strlen($password) < 6) {
      throw new InvalidArgumentException('Password must be at least 6 characters');
    }
    
    $stmt = $this->pdo->prepare('SELECT id FROM users WHERE email = ?');
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
      throw new RuntimeException('Email already registered', 409);
    }
    
    $stmt = $this->pdo->prepare('SELECT id FROM roles WHERE name = ?');
    $stmt->execute([$role]);
    $roleRow = $stmt->fetch();
    if (!$roleRow) {
      throw new InvalidArgumentException('Invalid role');
    }
    
    $hash = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $this->pdo->prepare('INSERT INTO users (email, password_hash, first_name, last_name, role_id) VALUES (?, ?, ?, ?, ?)');
    $stmt->execute([$email, $hash, $firstName, $lastName ?: null, $roleRow['id']]);
    $userId = (int)$this->pdo->lastInsertId();
    
    $stmt = $this->pdo->prepare('SELECT u.id, u.email, u.first_name, u.last_name, r.name as role FROM users u JOIN roles r ON r.id = u.role_id WHERE u.id = ?');
    $stmt->execute([$userId]);
    $user = $stmt->fetch();
    
    $token = jwt_generate(['user_id' => $userId, 'email' => $email, 'role' => $user['role']]);
    jwt_store($userId, $token);
    
    return ['user' => $user, 'token' => $token];
  }
  
  public function login(string $email, string $password): array {
    if (!$email || !$password) {
      throw new InvalidArgumentException('Missing email or password');
    }
    
    $stmt = $this->pdo->prepare('SELECT u.id, u.email, u.password_hash, u.first_name, u.last_name, r.name as role FROM users u JOIN roles r ON r.id = u.role_id WHERE u.email = ?');
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    
    if (!$user || !password_verify($password, $user['password_hash'])) {
      throw new RuntimeException('Invalid credentials', 401);
    }
    
    $userId = (int)$user['id'];
    $token = jwt_generate(['user_id' => $userId, 'email' => $user['email'], 'role' => $user['role']]);
    jwt_store($userId, $token);
    
    unset($user['password_hash']);
    
    return ['user' => $user, 'token' => $token];
  }
  
  public function refreshToken(string $token): string {
    $payload = jwt_validate($token);
    if (!$payload || !isset($payload['user_id'])) {
      throw new RuntimeException('Invalid token', 401);
    }
    
    jwt_revoke($token);
    
    $newToken = jwt_generate(['user_id' => $payload['user_id'], 'email' => $payload['email'], 'role' => $payload['role']]);
    jwt_store($payload['user_id'], $newToken);
    
    return $newToken;
  }
}

