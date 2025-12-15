<?php
declare(strict_types=1);

require_once __DIR__ . '/../helpers/ValidationHelper.php';

class BaseValidator {
  protected array $errors = [];
  
  protected function addError(string $field, string $message): void {
    $this->errors[$field] = $message;
  }
  
  public function getErrors(): array {
    return $this->errors;
  }
  
  public function hasErrors(): bool {
    return !empty($this->errors);
  }
  
  public function validate(array $data): bool {
    $this->errors = [];
    return true;
  }
}

