<?php
declare(strict_types=1);

require_once __DIR__ . '/BaseService.php';
require_once __DIR__ . '/../repositories/FlightRepository.php';

class FlightService extends BaseService {
  private FlightRepository $repository;
  
  public function __construct(PDO $pdo) {
    parent::__construct($pdo);
    $this->repository = new FlightRepository($pdo);
  }
  
  public function getAvailableDates(int $routeId, bool $isReturn = false): array {
    if ($isReturn) {
      $returnRouteId = $this->repository->getReturnRouteId($routeId);
      if (!$returnRouteId) {
        return [];
      }
      $routeId = $returnRouteId;
    }
    
    return $this->repository->getAvailableDates($routeId);
  }
  
  public function getFlightById(int $id): ?array {
    $route = $this->repository->getRouteById($id);
    if ($route) {
      $route['images'] = [];
      $route['image_url'] = null;
      $route['price'] = $route['base_price_economy'];
      return $route;
    }
    
    $instance = $this->repository->getInstanceById($id);
    if (!$instance) {
      return null;
    }
    
    $instance['price_economy'] = $instance['price_economy'] ?? $instance['base_price_economy'];
    $instance['price_business'] = $instance['price_business'] ?? $instance['base_price_business'];
    $instance['price_first'] = $instance['price_first'] ?? $instance['base_price_first'];
    $instance['images'] = [];
    $instance['image_url'] = null;
    
    return $instance;
  }
  
  public function listRoutes(array $filters, int $page, int $limit): array {
    $result = $this->repository->getRoutes($filters, $page, $limit);
    
    foreach ($result['promoted'] as &$flight) {
      $flight['price'] = $flight['base_price_economy'];
      $flight['departure_date'] = null;
      $flight['departure_datetime'] = null;
      if ($flight['discount_percent'] > 0) {
        $flight['discounted_price'] = round($flight['price'] * (1 - $flight['discount_percent'] / 100), 2);
      }
    }
    
    foreach ($result['regular'] as &$flight) {
      $flight['price'] = $flight['base_price_economy'];
      $flight['departure_date'] = null;
      $flight['departure_datetime'] = null;
      if ($flight['discount_percent'] > 0) {
        $flight['discounted_price'] = round($flight['price'] * (1 - $flight['discount_percent'] / 100), 2);
      }
    }
    
    return [
      'page' => $page,
      'limit' => $limit,
      'promoted' => $result['promoted'],
      'results' => $result['regular']
    ];
  }
  
  public function getInstances(int $routeId, string $departureDate, ?string $returnDate, string $class, int $passengerCount): array {
    if (!in_array($class, ['economy', 'business', 'first'])) {
      throw new InvalidArgumentException('Invalid class. Must be economy, business, or first');
    }
    
    if ($passengerCount < 1) {
      throw new InvalidArgumentException('Passenger count must be at least 1');
    }
    
    $instances = $this->repository->getInstancesForRoute($routeId, $departureDate, $class, $passengerCount);
    
    foreach ($instances as &$instance) {
      $basePrice = $instance['base_price_' . $class] ?? $instance['base_price_economy'];
      $price = $instance['price_' . $class] ?? $basePrice;
      $instance['price'] = $price;
      $instance['price_' . $class] = $price;
      
      if ($instance['discount_percent'] > 0) {
        $instance['discounted_price'] = round($price * (1 - $instance['discount_percent'] / 100), 2);
      }
      
      $seatsAvailableColumn = 'seats_' . $class . '_available';
      $instance['seats_available'] = $instance[$seatsAvailableColumn];
      $instance['seats_total'] = $instance['seats_' . $class . '_total'];
    }
    
    $result = ['instances' => $instances];
    
    if ($returnDate) {
      $returnRouteId = $this->repository->getReturnRouteId($routeId);
      if ($returnRouteId) {
        try {
          $returnInstances = $this->repository->getInstancesForRoute($returnRouteId, $returnDate, $class, $passengerCount);
          
          foreach ($returnInstances as &$returnInstance) {
            $basePrice = $returnInstance['base_price_' . $class] ?? $returnInstance['base_price_economy'];
            $price = $returnInstance['price_' . $class] ?? $basePrice;
            $returnInstance['price'] = $price;
            $returnInstance['price_' . $class] = $price;
            
            if ($returnInstance['discount_percent'] > 0) {
              $returnInstance['discounted_price'] = round($price * (1 - $returnInstance['discount_percent'] / 100), 2);
            }
            
            $seatsAvailableColumn = 'seats_' . $class . '_available';
            $returnInstance['seats_available'] = $returnInstance[$seatsAvailableColumn];
            $returnInstance['seats_total'] = $returnInstance['seats_' . $class . '_total'];
          }
          
          $result['return_instances'] = $returnInstances;
        } catch (PDOException $e) {
          error_log('Return flight instances query error: ' . $e->getMessage());
          $result['return_instances'] = [];
        }
      } else {
        $result['return_instances'] = [];
      }
    }
    
    return $result;
  }
  
  public function bookFlight(int $userId, int $instanceId, string $class, int $passengerCount): array {
    if (!in_array($class, ['economy', 'business', 'first'])) {
      throw new InvalidArgumentException('Invalid class');
    }
    
    if ($passengerCount < 1) {
      throw new InvalidArgumentException('Passenger count must be at least 1');
    }
    
    $instance = $this->repository->getInstanceForBooking($instanceId);
    if (!$instance) {
      throw new RuntimeException('Flight instance not found or not available', 404);
    }
    
    $seatsAvailableColumn = 'seats_' . $class . '_available';
    if ($instance[$seatsAvailableColumn] < $passengerCount) {
      throw new RuntimeException('Not enough seats available', 400);
    }
    
    $basePrice = $instance['base_price_' . $class] ?? $instance['base_price_economy'];
    $price = $instance['price_' . $class] ?? $basePrice;
    $totalPrice = $price * $passengerCount;
    
    if ($instance['discount_percent'] > 0) {
      $totalPrice = round($totalPrice * (1 - $instance['discount_percent'] / 100), 2);
    }
    
    $this->pdo->beginTransaction();
    
    try {
      $bookingId = $this->repository->createBooking($userId, $instanceId, $class, $passengerCount, $totalPrice);
      $this->repository->updateSeatAvailability($instanceId, $class, $passengerCount);
      
      $this->pdo->commit();
      
      $booking = $this->repository->getBookingById($bookingId);
      $booking['type'] = 'flight';
      $booking['item_id'] = $instanceId;
      
      return $booking;
    } catch (Exception $e) {
      $this->pdo->rollBack();
      throw $e;
    }
  }
}

