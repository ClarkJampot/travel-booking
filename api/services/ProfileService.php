<?php
declare(strict_types=1);

require_once __DIR__ . '/BaseService.php';
require_once __DIR__ . '/../repositories/ProfileRepository.php';
require_once __DIR__ . '/../helpers/BookingHelper.php';

class ProfileService extends BaseService {
  private ProfileRepository $repository;
  
  public function __construct(PDO $pdo) {
    parent::__construct($pdo);
    $this->repository = new ProfileRepository($pdo);
  }
  
  public function getProfile(int $userId): array {
    $userInfo = $this->repository->getUserInfo($userId);
    if (!$userInfo) {
      throw new RuntimeException('User not found', 404);
    }
    
    $userRole = $userInfo['role_name'] ?? 'customer';
    $isOwnerOrAgency = $userRole === 'owner' || $userRole === 'agency';
    
    $bookingsSummary = $this->getBookingsSummary($userId, $isOwnerOrAgency);
    $content = $this->getUserContent($userId);
    
    return [
      'user' => $userInfo,
      'bookings' => $bookingsSummary,
      'promoted' => $content['promoted'],
      'all' => $content['all']
    ];
  }
  
  private function getBookingsSummary(int $userId, bool $isOwnerOrAgency): array {
    $summary = [
      'total' => 0,
      'confirmed' => 0,
      'cancelled' => 0,
      'completed' => 0,
      'total_spent' => 0,
      'total_revenue' => 0,
      'recent' => []
    ];
    
    try {
      $types = ['hotel', 'flight', 'activity', 'transfer'];
      
      if ($isOwnerOrAgency) {
        foreach ($types as $type) {
          $bookings = $this->repository->getOwnerBookings($userId, $type);
          foreach ($bookings as $booking) {
            $summary['total']++;
            $status = $booking['status'];
            if (isset($summary[$status])) {
              $summary[$status]++;
            }
            if (in_array($status, ['confirmed', 'completed'])) {
              $summary['total_revenue'] += (float)($booking['total_price'] ?? 0);
            }
          }
        }
      } else {
        foreach ($types as $type) {
          $bookings = $this->repository->getCustomerBookings($userId, $type);
          foreach ($bookings as $booking) {
            $summary['total']++;
            $status = $booking['status'];
            if (isset($summary[$status])) {
              $summary[$status]++;
            }
            if (in_array($status, ['confirmed', 'completed'])) {
              $summary['total_spent'] += (float)($booking['total_price'] ?? 0);
            }
          }
        }
        
        $recentBookings = [];
        foreach ($types as $type) {
          $bookings = $this->repository->getRecentCustomerBookings($userId, $type);
          foreach ($bookings as &$booking) {
            $itemId = (int)$booking['item_id'];
            $booking['item_details'] = BookingHelper::getItemDetails($this->pdo, $type, $itemId);
          }
          $recentBookings = array_merge($recentBookings, $bookings);
        }
        
        usort($recentBookings, function($a, $b) {
          return strtotime($b['booked_at']) - strtotime($a['booked_at']);
        });
        $summary['recent'] = array_slice($recentBookings, 0, 5);
      }
    } catch (Exception $e) {
      error_log('Profile bookings summary failed: ' . $e->getMessage());
    }
    
    return $summary;
  }
  
  private function getUserContent(int $userId): array {
    $result = [
      'promoted' => [
        'hotels' => [],
        'flights' => [],
        'activities' => [],
        'transfers' => []
      ],
      'all' => [
        'hotels' => [],
        'flights' => [],
        'activities' => [],
        'transfers' => []
      ]
    ];
    
    try {
      $promotedHotels = $this->repository->getUserHotels($userId, true);
      $result['promoted']['hotels'] = $this->applyDiscounts($promotedHotels, 'price_per_night');
    } catch (PDOException $e) {
      error_log('Profile promoted hotels query failed: ' . $e->getMessage());
    }
    
    try {
      $allHotels = $this->repository->getUserHotels($userId, false);
      $result['all']['hotels'] = $this->applyDiscounts($allHotels, 'price_per_night');
    } catch (PDOException $e) {
      error_log('Profile all hotels query failed: ' . $e->getMessage());
    }
    
    try {
      $promotedFlights = $this->repository->getUserFlights($userId, true);
      $result['promoted']['flights'] = $this->formatFlights($promotedFlights);
    } catch (PDOException $e) {
      error_log('Profile promoted flights query failed: ' . $e->getMessage());
    }
    
    try {
      $allFlights = $this->repository->getUserFlights($userId, false);
      $result['all']['flights'] = $this->formatFlights($allFlights);
    } catch (PDOException $e) {
      error_log('Profile all flights query failed: ' . $e->getMessage());
    }
    
    try {
      $promotedActivities = $this->repository->getUserActivities($userId, true);
      $result['promoted']['activities'] = $this->applyDiscounts($promotedActivities, 'price');
    } catch (PDOException $e) {
      error_log('Profile promoted activities query failed: ' . $e->getMessage());
    }
    
    try {
      $allActivities = $this->repository->getUserActivities($userId, false);
      $result['all']['activities'] = $this->applyDiscounts($allActivities, 'price');
    } catch (PDOException $e) {
      error_log('Profile all activities query failed: ' . $e->getMessage());
    }
    
    try {
      $promotedTransfers = $this->repository->getUserTransfers($userId, true);
      $result['promoted']['transfers'] = $this->formatTransfers($promotedTransfers);
    } catch (PDOException $e) {
      error_log('Profile promoted transfers query failed: ' . $e->getMessage());
    }
    
    try {
      $allTransfers = $this->repository->getUserTransfers($userId, false);
      $result['all']['transfers'] = $this->formatTransfers($allTransfers);
    } catch (PDOException $e) {
      error_log('Profile all transfers query failed: ' . $e->getMessage());
    }
    
    return $result;
  }
  
  private function applyDiscounts(array $items, string $priceField): array {
    foreach ($items as &$item) {
      if (isset($item['discount_percent']) && $item['discount_percent'] > 0) {
        $price = $item[$priceField] ?? 0;
        $item['discounted_price'] = round($price * (1 - $item['discount_percent'] / 100), 2);
      }
    }
    return $items;
  }
  
  private function formatFlights(array $flights): array {
    foreach ($flights as &$flight) {
      $flight['airline'] = $flight['airline'];
      $flight['origin'] = $flight['origin_code'];
      $flight['destination'] = $flight['destination_code'];
      $flight['price'] = $flight['base_price_economy'];
      if (isset($flight['discount_percent']) && $flight['discount_percent'] > 0) {
        $flight['discounted_price'] = round($flight['base_price_economy'] * (1 - $flight['discount_percent'] / 100), 2);
      }
    }
    return $flights;
  }
  
  private function formatTransfers(array $transfers): array {
    foreach ($transfers as &$transfer) {
      $transfer['service'] = ($transfer['origin_specific'] ?? $transfer['origin_city_name']) . ' to ' . ($transfer['destination_specific'] ?? $transfer['destination_city_name']);
      $transfer['origin'] = $transfer['origin_specific'] ?? $transfer['origin_city_name'];
      $transfer['destination'] = $transfer['destination_specific'] ?? $transfer['destination_city_name'];
      $transfer['price'] = $transfer['base_price'];
      if (isset($transfer['discount_percent']) && $transfer['discount_percent'] > 0) {
        $transfer['discounted_price'] = round($transfer['base_price'] * (1 - $transfer['discount_percent'] / 100), 2);
      }
    }
    return $transfers;
  }
}

