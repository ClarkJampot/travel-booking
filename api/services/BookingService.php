<?php
declare(strict_types=1);

require_once __DIR__ . '/BaseService.php';
require_once __DIR__ . '/../repositories/BookingRepository.php';
require_once __DIR__ . '/../helpers/BookingHelper.php';

class BookingService extends BaseService {
  private BookingRepository $repository;
  
  public function __construct(PDO $pdo) {
    parent::__construct($pdo);
    $this->repository = new BookingRepository($pdo);
  }
  
  public function getBookingById(int $bookingId, int $userId, bool $isOwner = false): ?array {
    if ($isOwner) {
      return $this->repository->findBookingByIdForOwner($bookingId, $userId);
    }
    return $this->repository->findBookingById($bookingId, $userId);
  }
  
  public function getUserBookings(int $userId, ?string $itemType, ?string $status, int $page, int $limit): array {
    $allBookings = $this->repository->getUserBookings($userId, $itemType, $status);
    
    $total = count($allBookings);
    $offset = ($page - 1) * $limit;
    $paginatedBookings = array_slice($allBookings, $offset, $limit);
    
    return [
      'page' => $page,
      'limit' => $limit,
      'total' => $total,
      'results' => $paginatedBookings
    ];
  }
  
  public function createBooking(int $userId, string $itemType, int $itemId, array $data, float $totalPrice): array {
    if (!in_array($itemType, ['hotel', 'flight', 'activity', 'transfer'])) {
      throw new InvalidArgumentException('Invalid item_type');
    }
    
    $itemDetails = BookingHelper::getItemDetails($this->pdo, $itemType, $itemId);
    if (!$itemDetails) {
      throw new RuntimeException(ucfirst($itemType) . ' not found', 404);
    }
    
    $bookingId = 0;
    
    if ($itemType === 'hotel') {
      $checkIn = $data['check_in'] ?? null;
      $checkOut = $data['check_out'] ?? null;
      $guests = isset($data['guests']) ? (int)$data['guests'] : 1;
      
      if (!$checkIn || !$checkOut) {
        throw new InvalidArgumentException('Missing required fields: check_in, check_out');
      }
      
      $bookingId = $this->repository->createHotelBooking($userId, $itemId, $checkIn, $checkOut, $guests, $totalPrice);
    } elseif ($itemType === 'flight') {
      $class = $data['class'] ?? null;
      $passengerCount = isset($data['passenger_count']) ? (int)$data['passenger_count'] : 1;
      
      $bookingId = $this->repository->createFlightBooking($userId, $itemId, $class, $passengerCount, $totalPrice);
    } elseif ($itemType === 'activity') {
      $participantCount = isset($data['participant_count']) ? (int)$data['participant_count'] : 1;
      $date = $data['date'] ?? null;
      
      if (!$date) {
        throw new InvalidArgumentException('Missing required field: date');
      }
      
      $bookingId = $this->repository->createActivityBooking($userId, $itemId, $date, $participantCount, $totalPrice);
    } elseif ($itemType === 'transfer') {
      $passengerCount = isset($data['passenger_count']) ? (int)$data['passenger_count'] : 1;
      
      $bookingId = $this->repository->createTransferBooking($userId, $itemId, $passengerCount, $totalPrice);
    }
    
    $this->repository->updateBookingCount($itemType, $itemId, 1);
    
    $booking = $this->repository->findBookingById($bookingId, $userId);
    if (!$booking) {
      throw new RuntimeException('Failed to retrieve created booking', 500);
    }
    
    return $booking;
  }
  
  public function updateBookingStatus(int $bookingId, int $userId, string $status, bool $isOwner = false): array {
    if (!in_array($status, ['confirmed', 'cancelled', 'completed'])) {
      throw new InvalidArgumentException('Invalid status');
    }
    
    $booking = $this->getBookingById($bookingId, $userId, $isOwner);
    if (!$booking) {
      throw new RuntimeException('Booking not found', 404);
    }
    
    $wasCancelled = $booking['status'] === 'cancelled';
    $this->repository->updateBookingStatus($booking['type'], $bookingId, $status);
    
    if ($status === 'cancelled' && !$wasCancelled) {
      $itemIdColumn = BookingHelper::getItemIdColumn($booking['type']);
      $itemId = (int)$booking[$itemIdColumn];
      $this->repository->updateBookingCount($booking['type'], $itemId, -1);
    }
    
    return $this->getBookingById($bookingId, $userId, $isOwner);
  }
  
  public function cancelBooking(int $bookingId, int $userId): void {
    $booking = $this->repository->findBookingById($bookingId, $userId);
    if (!$booking) {
      throw new RuntimeException('Booking not found', 404);
    }
    
    $wasCancelled = $booking['status'] === 'cancelled';
    $this->repository->updateBookingStatus($booking['type'], $bookingId, 'cancelled');
    
    if (!$wasCancelled) {
      $itemIdColumn = BookingHelper::getItemIdColumn($booking['type']);
      $itemId = (int)$booking[$itemIdColumn];
      $this->repository->updateBookingCount($booking['type'], $itemId, -1);
    }
  }
}

