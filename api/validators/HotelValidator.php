<?php
declare(strict_types=1);

require_once __DIR__ . '/BaseValidator.php';

class HotelValidator extends BaseValidator {
  public function validate(array $data): bool {
    $this->errors = [];
    
    try {
      ValidationHelper::validateString($data['name'] ?? null, 'name', true, 255);
    } catch (InvalidArgumentException $e) {
      $this->addError('name', $e->getMessage());
    }
    
    try {
      ValidationHelper::validateInt($data['city_id'] ?? null, 'city_id', true, 1);
    } catch (InvalidArgumentException $e) {
      $this->addError('city_id', $e->getMessage());
    }
    
    try {
      ValidationHelper::validateInt($data['province_id'] ?? null, 'province_id', true, 1);
    } catch (InvalidArgumentException $e) {
      $this->addError('province_id', $e->getMessage());
    }
    
    try {
      ValidationHelper::validateDecimal($data['price_per_night'] ?? null, 'price_per_night', true, 0);
    } catch (InvalidArgumentException $e) {
      $this->addError('price_per_night', $e->getMessage());
    }
    
    if (isset($data['destination_id'])) {
      try {
        ValidationHelper::validateInt($data['destination_id'], 'destination_id', false, 1);
      } catch (InvalidArgumentException $e) {
        $this->addError('destination_id', $e->getMessage());
      }
    }
    
    if (isset($data['description'])) {
      try {
        ValidationHelper::validateString($data['description'], 'description', false, 2000);
      } catch (InvalidArgumentException $e) {
        $this->addError('description', $e->getMessage());
      }
    }
    
    return !$this->hasErrors();
  }
}

