<?php
declare(strict_types=1);

require_once __DIR__ . '/BaseService.php';
require_once __DIR__ . '/../repositories/TransferRepository.php';
require_once __DIR__ . '/../helpers/ImageHelper.php';

class TransferService extends BaseService {
  private TransferRepository $repository;
  
  public function __construct(PDO $pdo) {
    parent::__construct($pdo);
    $this->repository = new TransferRepository($pdo);
  }
  
  public function getAvailableDates(int $transferId): array {
    return $this->repository->getAvailableDates($transferId);
  }
  
  public function getInstancesForRoute(int $transferId, string $date, int $passengerCount): array {
    if ($passengerCount < 1) {
      throw new InvalidArgumentException('Passenger count must be at least 1');
    }
    
    $instances = $this->repository->getInstancesForRoute($transferId, $date, $passengerCount);
    
    foreach ($instances as &$instance) {
      $price = $instance['price'] ?? $instance['base_price'];
      $instance['price'] = $price;
      if ($instance['discount_percent'] > 0) {
        $instance['discounted_price'] = round($price * (1 - $instance['discount_percent'] / 100), 2);
      }
    }
    
    return $instances;
  }
  
  public function getTransferById(int $id): ?array {
    $route = $this->repository->getRouteById($id);
    if ($route) {
      $route['images'] = [];
      $route['image_url'] = null;
      $route['price'] = $route['base_price'];
      $route['service'] = ($route['origin_specific'] ?? $route['origin_city_name']) . ' to ' . ($route['destination_specific'] ?? $route['destination_city_name']);
      $route['origin'] = $route['origin_specific'] ?? $route['origin_city_name'];
      $route['destination'] = $route['destination_specific'] ?? $route['destination_city_name'];
      
      $images = ImageHelper::getEntityImages($this->pdo, 'transfer_route', $id);
      $route['images'] = $images;
      $route['image_url'] = $images[0] ?? null;
      
      return $route;
    }
    
    $instance = $this->repository->getInstanceById($id);
    if (!$instance) {
      return null;
    }
    
    $instance['price'] = $instance['price'] ?? $instance['base_price'];
    
    $images = ImageHelper::getEntityImages($this->pdo, 'transfer_route', $instance['route_id']);
    $instance['images'] = $images;
    $instance['image_url'] = $images[0] ?? null;
    
    return $instance;
  }
  
  public function listRoutes(array $filters, int $page, int $limit): array {
    $result = $this->repository->getRoutes($filters, $page, $limit);
    
    foreach ($result['promoted'] as &$route) {
      $route['service'] = ($route['origin_specific'] ?? $route['origin_city_name']) . ' to ' . ($route['destination_specific'] ?? $route['destination_city_name']);
      $route['origin'] = $route['origin_specific'] ?? $route['origin_city_name'];
      $route['destination'] = $route['destination_specific'] ?? $route['destination_city_name'];
      $route['price'] = $route['base_price'];
      if (isset($route['discount_percent']) && $route['discount_percent'] > 0) {
        $route['discounted_price'] = round($route['base_price'] * (1 - $route['discount_percent'] / 100), 2);
      }
    }
    
    foreach ($result['regular'] as &$route) {
      $route['service'] = ($route['origin_specific'] ?? $route['origin_city_name']) . ' to ' . ($route['destination_specific'] ?? $route['destination_city_name']);
      $route['origin'] = $route['origin_specific'] ?? $route['origin_city_name'];
      $route['destination'] = $route['destination_specific'] ?? $route['destination_city_name'];
      $route['price'] = $route['base_price'];
      if (isset($route['discount_percent']) && $route['discount_percent'] > 0) {
        $route['discounted_price'] = round($route['base_price'] * (1 - $route['discount_percent'] / 100), 2);
      }
    }
    
    return [
      'page' => $page,
      'limit' => $limit,
      'promoted' => $result['promoted'],
      'results' => $result['regular']
    ];
  }
  
  public function listInstances(array $filters, string $date, int $passengerCount, int $page, int $limit): array {
    if ($passengerCount < 1) {
      throw new InvalidArgumentException('Passenger count must be at least 1');
    }
    
    $result = $this->repository->getInstancesWithFilters($filters, $date, $passengerCount, $page, $limit);
    
    foreach ($result['promoted'] as &$transfer) {
      $price = $transfer['price'] ?? $transfer['base_price'];
      $transfer['price'] = $price;
      if ($transfer['discount_percent'] > 0) {
        $transfer['discounted_price'] = round($price * (1 - $transfer['discount_percent'] / 100), 2);
      }
    }
    
    foreach ($result['regular'] as &$transfer) {
      $price = $transfer['price'] ?? $transfer['base_price'];
      $transfer['price'] = $price;
      if ($transfer['discount_percent'] > 0) {
        $transfer['discounted_price'] = round($price * (1 - $transfer['discount_percent'] / 100), 2);
      }
    }
    
    return [
      'page' => $page,
      'limit' => $limit,
      'promoted' => $result['promoted'],
      'results' => $result['regular']
    ];
  }
  
  public function bookTransfer(int $userId, int $instanceId, int $passengerCount): array {
    if ($passengerCount < 1) {
      throw new InvalidArgumentException('Passenger count must be at least 1');
    }
    
    $instance = $this->repository->getInstanceForBooking($instanceId);
    if (!$instance) {
      throw new RuntimeException('Transfer instance not found or not available', 404);
    }
    
    if ($instance['seats_available'] < $passengerCount) {
      throw new RuntimeException('Not enough seats available', 400);
    }
    
    $price = $instance['price'] ?? $instance['base_price'];
    $totalPrice = $price * $passengerCount;
    
    if ($instance['discount_percent'] > 0) {
      $totalPrice = round($totalPrice * (1 - $instance['discount_percent'] / 100), 2);
    }
    
    $this->pdo->beginTransaction();
    
    try {
      $bookingId = $this->repository->createBooking($userId, $instanceId, $passengerCount, $totalPrice);
      $this->repository->updateSeatAvailability($instanceId, $passengerCount, $instance['capacity']);
      
      $this->pdo->commit();
      
      $booking = $this->repository->getBookingById($bookingId);
      $booking['type'] = 'transfer';
      $booking['item_id'] = $instanceId;
      
      return $booking;
    } catch (Exception $e) {
      $this->pdo->rollBack();
      throw $e;
    }
  }
}

