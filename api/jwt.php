<?php
// JWT token handling functions
declare(strict_types=1);

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';

/**
 * Generate JWT token
 * @param array $payload User data to encode
 * @return string JWT token
 */
function jwt_generate(array $payload): string {
  $header = [
    'typ' => 'JWT',
    'alg' => 'HS256'
  ];
  
  $headerEncoded = base64url_encode(json_encode($header));
  $payload['exp'] = time() + JWT_EXPIRY;
  $payload['iat'] = time();
  $payloadEncoded = base64url_encode(json_encode($payload));
  
  $signature = hash_hmac('sha256', "$headerEncoded.$payloadEncoded", JWT_SECRET, true);
  $signatureEncoded = base64url_encode($signature);
  
  return "$headerEncoded.$payloadEncoded.$signatureEncoded";
}

/**
 * Validate JWT token
 * @param string $token JWT token
 * @return array|null Decoded payload or null if invalid
 */
function jwt_validate(string $token): ?array {
  $parts = explode('.', $token);
  if (count($parts) !== 3) {
    return null;
  }
  
  [$headerEncoded, $payloadEncoded, $signatureEncoded] = $parts;
  
  // Verify signature
  $signature = base64url_decode($signatureEncoded);
  $expectedSignature = hash_hmac('sha256', "$headerEncoded.$payloadEncoded", JWT_SECRET, true);
  
  if (!hash_equals($signature, $expectedSignature)) {
    return null;
  }
  
  // Decode payload
  $payload = json_decode(base64url_decode($payloadEncoded), true);
  if (!$payload) {
    return null;
  }
  
  // Check expiration
  if (isset($payload['exp']) && $payload['exp'] < time()) {
    return null;
  }
  
  return $payload;
}

/**
 * Store JWT token in database
 * @param int $userId User ID
 * @param string $token JWT token
 * @return bool Success
 */
function jwt_store(int $userId, string $token): bool {
  try {
    $pdo = db_pdo();
    $expiresAt = date('Y-m-d H:i:s', time() + JWT_EXPIRY);
    
    $stmt = $pdo->prepare('INSERT INTO jwt_tokens (user_id, token, expires_at) VALUES (?, ?, ?)');
    return $stmt->execute([$userId, $token, $expiresAt]);
  } catch (Exception $e) {
    return false;
  }
}

/**
 * Revoke JWT token
 * @param string $token JWT token
 * @return bool Success
 */
function jwt_revoke(string $token): bool {
  try {
    $pdo = db_pdo();
    $stmt = $pdo->prepare('DELETE FROM jwt_tokens WHERE token = ?');
    return $stmt->execute([$token]);
  } catch (Exception $e) {
    return false;
  }
}

/**
 * Check if token is revoked
 * @param string $token JWT token
 * @return bool True if revoked
 */
function jwt_is_revoked(string $token): bool {
  try {
    $pdo = db_pdo();
    $stmt = $pdo->prepare('SELECT id FROM jwt_tokens WHERE token = ? AND expires_at > GETDATE()');
    $stmt->execute([$token]);
    return $stmt->fetch() === false;
  } catch (Exception $e) {
    return true;
  }
}

/**
 * Clean expired tokens
 * @return int Number of tokens deleted
 */
function jwt_clean_expired(): int {
  try {
    $pdo = db_pdo();
    $stmt = $pdo->prepare('DELETE FROM jwt_tokens WHERE expires_at < GETDATE()');
    $stmt->execute();
    return $stmt->rowCount();
  } catch (Exception $e) {
    return 0;
  }
}

/**
 * Base64 URL encode
 */
function base64url_encode(string $data): string {
  return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}

/**
 * Base64 URL decode
 */
function base64url_decode(string $data): string {
  return base64_decode(strtr($data, '-_', '+/'));
}


