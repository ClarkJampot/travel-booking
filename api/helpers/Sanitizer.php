<?php
declare(strict_types=1);

class Sanitizer {
  public static function sanitizeString(?string $value): ?string {
    if ($value === null) {
      return null;
    }
    $value = trim($value);
    // FILTER_SANITIZE_STRING is deprecated in PHP 8.1+
    // For input sanitization, strip HTML/PHP tags
    // HTML encoding should be done at output time (e.g., htmlspecialchars in templates)
    $value = strip_tags($value);
    return $value === '' ? null : $value;
  }
  
  public static function sanitizeInt($value): ?int {
    if ($value === null || $value === '') {
      return null;
    }
    return filter_var($value, FILTER_SANITIZE_NUMBER_INT) ? (int)$value : null;
  }
  
  public static function sanitizeFloat($value): ?float {
    if ($value === null || $value === '') {
      return null;
    }
    return filter_var($value, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION) ? (float)$value : null;
  }
  
  public static function sanitizeEmail(?string $value): ?string {
    if ($value === null) {
      return null;
    }
    $value = trim($value);
    return filter_var($value, FILTER_SANITIZE_EMAIL) ?: null;
  }
  
  public static function sanitizeUrl(?string $value): ?string {
    if ($value === null) {
      return null;
    }
    $value = trim($value);
    return filter_var($value, FILTER_SANITIZE_URL) ?: null;
  }
  
  public static function sanitizeArray(array $data, array $rules): array {
    $sanitized = [];
    foreach ($rules as $field => $type) {
      if (!isset($data[$field])) {
        continue;
      }
      switch ($type) {
        case 'string':
          $sanitized[$field] = self::sanitizeString($data[$field]);
          break;
        case 'int':
          $sanitized[$field] = self::sanitizeInt($data[$field]);
          break;
        case 'float':
          $sanitized[$field] = self::sanitizeFloat($data[$field]);
          break;
        case 'email':
          $sanitized[$field] = self::sanitizeEmail($data[$field]);
          break;
        case 'url':
          $sanitized[$field] = self::sanitizeUrl($data[$field]);
          break;
        default:
          $sanitized[$field] = $data[$field];
      }
    }
    return $sanitized;
  }
}

