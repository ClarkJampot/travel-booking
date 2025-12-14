<?php
// Validation Helper
// Provides common validation functions for API endpoints

class ValidationHelper {
  /**
   * Validate and sanitize an integer
   */
  public static function validateInt($value, $fieldName, $required = true, $min = null, $max = null) {
    if ($value === null || $value === '') {
      if ($required) {
        throw new InvalidArgumentException("Field '{$fieldName}' is required");
      }
      return null;
    }
    
    if (!is_numeric($value)) {
      throw new InvalidArgumentException("Field '{$fieldName}' must be a valid number");
    }
    
    $intValue = (int)$value;
    
    if ($min !== null && $intValue < $min) {
      throw new InvalidArgumentException("Field '{$fieldName}' must be at least {$min}");
    }
    
    if ($max !== null && $intValue > $max) {
      throw new InvalidArgumentException("Field '{$fieldName}' must be at most {$max}");
    }
    
    return $intValue;
  }
  
  /**
   * Validate and sanitize a float/decimal
   */
  public static function validateDecimal($value, $fieldName, $required = true, $min = null, $max = null, $precision = 2) {
    if ($value === null || $value === '' || $value === false) {
      if ($required) {
        throw new InvalidArgumentException("Field '{$fieldName}' is required");
      }
      return null;
    }
    
    if (!is_numeric($value)) {
      throw new InvalidArgumentException("Field '{$fieldName}' must be a valid number");
    }
    
    $floatValue = (float)$value;
    
    // Check for overflow (DECIMAL(10,2) max is 99999999.99)
    if ($precision === 2 && $floatValue > 99999999.99) {
      throw new InvalidArgumentException("Field '{$fieldName}' is too large (maximum: 99,999,999.99)");
    }
    
    if ($min !== null && $floatValue < $min) {
      throw new InvalidArgumentException("Field '{$fieldName}' must be at least {$min}");
    }
    
    if ($max !== null && $floatValue > $max) {
      throw new InvalidArgumentException("Field '{$fieldName}' must be at most {$max}");
    }
    
    return round($floatValue, $precision);
  }
  
  /**
   * Validate and sanitize a string
   */
  public static function validateString($value, $fieldName, $required = false, $maxLength = null, $allowEmpty = true) {
    if ($value === null) {
      if ($required) {
        throw new InvalidArgumentException("Field '{$fieldName}' is required");
      }
      return null;
    }
    
    $stringValue = trim((string)$value);
    
    if (!$allowEmpty && $stringValue === '') {
      if ($required) {
        throw new InvalidArgumentException("Field '{$fieldName}' cannot be empty");
      }
      return null;
    }
    
    if ($maxLength !== null && strlen($stringValue) > $maxLength) {
      throw new InvalidArgumentException("Field '{$fieldName}' must be at most {$maxLength} characters");
    }
    
    return $stringValue === '' ? null : $stringValue;
  }
  
  /**
   * Validate time format (HH:MM)
   */
  public static function validateTime($value, $fieldName, $required = true) {
    if ($value === null || $value === '') {
      if ($required) {
        throw new InvalidArgumentException("Field '{$fieldName}' is required");
      }
      return null;
    }
    
    $timeValue = trim((string)$value);
    
    if (!preg_match('/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/', $timeValue)) {
      throw new InvalidArgumentException("Field '{$fieldName}' must be in HH:MM format (24-hour)");
    }
    
    return $timeValue;
  }
  
  /**
   * Validate days of week format (comma-separated numbers 0-6)
   */
  public static function validateDaysOfWeek($value, $fieldName, $required = true) {
    if ($value === null || $value === '') {
      if ($required) {
        throw new InvalidArgumentException("Field '{$fieldName}' is required");
      }
      return null;
    }
    
    $daysValue = trim((string)$value);
    $daysArray = array_map('trim', explode(',', $daysValue));
    
    foreach ($daysArray as $day) {
      if (!is_numeric($day) || (int)$day < 0 || (int)$day > 6) {
        throw new InvalidArgumentException("Field '{$fieldName}' must contain comma-separated numbers from 0-6 (0=Sunday, 6=Saturday)");
      }
    }
    
    return $daysValue;
  }
  
  /**
   * Validate that two IDs are different
   */
  public static function validateDifferent($value1, $value2, $fieldName1, $fieldName2) {
    if ($value1 === $value2) {
      throw new InvalidArgumentException("{$fieldName1} and {$fieldName2} cannot be the same");
    }
  }
}

