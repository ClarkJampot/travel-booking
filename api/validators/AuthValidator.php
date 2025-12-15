<?php
declare(strict_types=1);

require_once __DIR__ . '/BaseValidator.php';

class AuthValidator extends BaseValidator {
  public function validateRegister(array $data): bool {
    $this->errors = [];
    
    try {
      $email = ValidationHelper::validateString($data['email'] ?? null, 'email', true, 255, false);
      if ($email && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $this->addError('email', "Field 'email' must be a valid email address");
      }
    } catch (InvalidArgumentException $e) {
      $this->addError('email', $e->getMessage());
    }
    
    $password = $data['password'] ?? '';
    if (empty($password)) {
      $this->addError('password', "Field 'password' is required");
    } elseif (strlen($password) < 6) {
      $this->addError('password', "Field 'password' must be at least 6 characters");
    }
    
    try {
      ValidationHelper::validateString($data['first_name'] ?? null, 'first_name', true, 100);
    } catch (InvalidArgumentException $e) {
      $this->addError('first_name', $e->getMessage());
    }
    
    if (isset($data['last_name'])) {
      try {
        ValidationHelper::validateString($data['last_name'], 'last_name', false, 100);
      } catch (InvalidArgumentException $e) {
        $this->addError('last_name', $e->getMessage());
      }
    }
    
    return !$this->hasErrors();
  }
  
  public function validateLogin(array $data): bool {
    $this->errors = [];
    
    try {
      ValidationHelper::validateString($data['email'] ?? null, 'email', true, 255, false);
    } catch (InvalidArgumentException $e) {
      $this->addError('email', $e->getMessage());
    }
    
    $password = $data['password'] ?? '';
    if (empty($password)) {
      $this->addError('password', "Field 'password' is required");
    }
    
    return !$this->hasErrors();
  }
}

