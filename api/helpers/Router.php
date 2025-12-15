<?php
declare(strict_types=1);

class Router {
  public static function parseUri(string $basePath = '/travel-booking/api', string $apiPath = '/api'): string {
    $uri = $_SERVER['REQUEST_URI'] ?? '';
    
    if (strpos($uri, $basePath) === 0) {
      $uri = substr($uri, strlen($basePath));
    } elseif (strpos($uri, $apiPath) === 0) {
      $uri = substr($uri, strlen($apiPath));
    }
    
    $parsed = parse_url($uri);
    $uri = $parsed['path'] ?? $uri;
    
    if ($uri === '' || ($uri[0] ?? '') !== '/') {
      $uri = '/' . $uri;
    }
    
    return $uri;
  }
}

