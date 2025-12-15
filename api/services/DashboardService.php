<?php
declare(strict_types=1);

require_once __DIR__ . '/BaseService.php';
require_once __DIR__ . '/../repositories/DashboardRepository.php';
require_once __DIR__ . '/../helpers/StatsHelper.php';

class DashboardService extends BaseService {
  private DashboardRepository $repository;
  
  public function __construct(PDO $pdo) {
    parent::__construct($pdo);
    $this->repository = new DashboardRepository($pdo);
  }
  
  public function getStats(int $userId): array {
    $stats = [
      'total_bookings' => 0,
      'total_revenue' => 0,
      'confirmed_bookings' => 0,
      'cancelled_bookings' => 0,
      'completed_bookings' => 0,
      'total_items' => 0,
      'hotels_count' => 0,
      'flights_count' => 0,
      'activities_count' => 0,
      'transfers_count' => 0
    ];
    
    $stats['hotels_count'] = StatsHelper::countUserItems($this->pdo, 'hotel', $userId);
    $stats['flights_count'] = StatsHelper::countUserItems($this->pdo, 'flight', $userId);
    $stats['activities_count'] = StatsHelper::countUserItems($this->pdo, 'activity', $userId);
    $stats['transfers_count'] = StatsHelper::countUserItems($this->pdo, 'transfer', $userId);
    $stats['total_items'] = $stats['hotels_count'] + $stats['flights_count'] + $stats['activities_count'] + $stats['transfers_count'];
    
    foreach (['hotel', 'flight', 'activity', 'transfer'] as $type) {
      try {
        $bookings = $this->repository->getOwnerBookings($userId, $type);
        
        foreach ($bookings as $booking) {
          $stats['total_bookings']++;
          
          if (in_array($booking['status'] ?? '', ['confirmed', 'completed'])) {
            $stats['total_revenue'] += (float)($booking['total_price'] ?? 0);
          }
          
          if ($booking['status'] === 'confirmed') {
            $stats['confirmed_bookings']++;
          } elseif ($booking['status'] === 'cancelled') {
            $stats['cancelled_bookings']++;
          } elseif ($booking['status'] === 'completed') {
            $stats['completed_bookings']++;
          }
        }
      } catch (Exception $e) {
        error_log("Dashboard stats error for $type: " . $e->getMessage());
      }
    }
    
    return $stats;
  }
  
  public function getBookings(int $userId, ?string $itemType, ?string $status, ?string $customerName, ?string $keyword, int $page, int $limit): array {
    $types = $itemType ? [$itemType] : ['hotel', 'flight', 'activity', 'transfer'];
    $allBookings = [];
    $offset = ($page - 1) * $limit;
    
    foreach ($types as $type) {
      try {
        $bookings = $this->repository->getOwnerBookingsWithFilters($userId, $type, $status, $customerName, $keyword, $offset, $limit);
        
        foreach ($bookings as $booking) {
          $booking['item_id'] = (int)$booking['item_id'];
          $allBookings[] = $booking;
        }
      } catch (Exception $e) {
        error_log("Dashboard bookings error for $type: " . $e->getMessage());
      }
    }
    
    usort($allBookings, function($a, $b) {
      return strtotime($b['booked_at']) - strtotime($a['booked_at']);
    });
    
    $total = count($allBookings);
    $paginatedBookings = array_slice($allBookings, $offset, $limit);
    
    return [
      'page' => $page,
      'limit' => $limit,
      'total' => $total,
      'results' => $paginatedBookings
    ];
  }
  
  public function getItems(int $userId, ?string $itemType): array {
    return $this->repository->getUserItems($userId, $itemType);
  }
}

