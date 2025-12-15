<?php
declare(strict_types=1);

class ErrorHandler {
  public static function log(string $message, array $context = []): void {
    $logMessage = $message;
    if (!empty($context)) {
      $logMessage .= ' | Context: ' . json_encode($context);
    }
    error_log($logMessage);
  }
  
  public static function logException(\Throwable $exception, array $context = []): void {
    $message = 'Exception: ' . $exception->getMessage() . ' in ' . $exception->getFile() . ':' . $exception->getLine();
    self::log($message, $context);
  }
  
  public static function logError(string $message, string $file = '', int $line = 0, array $context = []): void {
    $logMessage = $message;
    if ($file) {
      $logMessage .= " in {$file}";
      if ($line) {
        $logMessage .= ":{$line}";
      }
    }
    self::log($logMessage, $context);
  }
}

