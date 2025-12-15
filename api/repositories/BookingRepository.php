<?php
declare(strict_types=1);

require_once __DIR__ . '/BaseRepository.php';
require_once __DIR__ . '/../helpers/BookingHelper.php';

class BookingRepository extends BaseRepository {
  public function findBookingById(int $bookingId, int $userId): ?array {
    $types = ['hotel', 'flight', 'activity', 'transfer'];
    
    foreach ($types as $type) {
      $table = BookingHelper::getBookingTable($type);
      $stmt = $this->pdo->prepare("SELECT *, '$type' as type FROM $table WHERE id = ? AND user_id = ?");
      $stmt->execute([$bookingId, $userId]);
      $booking = $stmt->fetch();
      
      if ($booking) {
        $itemIdColumn = BookingHelper::getItemIdColumn($type);
        $itemId = (int)$booking[$itemIdColumn];
        $booking['item_id'] = $itemId;
        $booking['item_details'] = BookingHelper::getItemDetails($this->pdo, $type, $itemId);
        return $booking;
      }
    }
    
    return null;
  }
  
  public function findBookingByIdForOwner(int $bookingId, int $ownerId): ?array {
    $types = ['hotel', 'flight', 'activity', 'transfer'];
    
    foreach ($types as $type) {
      $table = BookingHelper::getBookingTable($type);
      $itemIdColumn = BookingHelper::getItemIdColumn($type);
      
      if ($type === 'hotel') {
        $stmt = $this->pdo->prepare("
          SELECT b.*, 'hotel' as type
          FROM $table b
          INNER JOIN hotels i ON b.$itemIdColumn = i.id
          WHERE b.id = ? AND i.created_by = ? AND i.deleted_at IS NULL
        ");
        $stmt->execute([$bookingId, $ownerId]);
      } else if ($type === 'flight') {
        $stmt = $this->pdo->prepare("
          SELECT DISTINCT b.*, 'flight' as type
          FROM $table b
          INNER JOIN flight_instances fi ON b.$itemIdColumn = fi.id
          INNER JOIN flight_schedules fs ON fi.schedule_id = fs.id
          LEFT JOIN flight_routes fr ON fs.route_id = fr.id
          LEFT JOIN flight_route_pairs frp ON fs.route_pair_id = frp.id
          LEFT JOIN flight_routes outbound ON frp.outbound_route_id = outbound.id
          LEFT JOIN flight_routes return_route ON frp.return_route_id = return_route.id
          WHERE b.id = ? 
            AND (fr.created_by = ? OR outbound.created_by = ? OR return_route.created_by = ?)
            AND (fr.deleted_at IS NULL OR outbound.deleted_at IS NULL OR return_route.deleted_at IS NULL)
        ");
        $stmt->execute([$bookingId, $ownerId, $ownerId, $ownerId]);
      } else if ($type === 'transfer') {
        $stmt = $this->pdo->prepare("
          SELECT b.*, 'transfer' as type
          FROM $table b
          INNER JOIN transfer_instances ti ON b.$itemIdColumn = ti.id
          INNER JOIN transfer_schedules ts ON ti.schedule_id = ts.id
          INNER JOIN transfer_routes tr ON ts.route_id = tr.id
          WHERE b.id = ? AND tr.created_by = ? AND tr.deleted_at IS NULL
        ");
        $stmt->execute([$bookingId, $ownerId]);
      } else {
        $stmt = $this->pdo->prepare("
          SELECT b.*, 'activity' as type
          FROM $table b
          INNER JOIN activities i ON b.$itemIdColumn = i.id
          WHERE b.id = ? AND i.created_by = ? AND i.deleted_at IS NULL
        ");
        $stmt->execute([$bookingId, $ownerId]);
      }
      
      $booking = $stmt->fetch();
      
      if ($booking) {
        $itemId = (int)$booking[$itemIdColumn];
        $booking['item_id'] = $itemId;
        $booking['item_details'] = BookingHelper::getItemDetails($this->pdo, $type, $itemId);
        return $booking;
      }
    }
    
    return null;
  }
  
  public function getUserBookings(int $userId, ?string $itemType, ?string $status): array {
    $types = ['hotel', 'flight', 'activity', 'transfer'];
    if ($itemType && in_array($itemType, $types)) {
      $types = [$itemType];
    }
    
    $allBookings = [];
    
    foreach ($types as $type) {
      $table = BookingHelper::getBookingTable($type);
      $itemIdColumn = BookingHelper::getItemIdColumn($type);
      
      $where = ['user_id = ?'];
      $params = [$userId];
      
      if ($status) {
        $statusNormalized = trim(strtolower($status));
        $where[] = 'LOWER(status) = ?';
        $params[] = $statusNormalized;
      }
      
      $whereSql = 'WHERE ' . implode(' AND ', $where);
      $sql = "SELECT *, '$type' as type, $itemIdColumn as item_id FROM $table $whereSql ORDER BY booked_at DESC, id DESC";
      
      $stmt = $this->pdo->prepare($sql);
      $stmt->execute($params);
      $bookings = $stmt->fetchAll();
      
      foreach ($bookings as $booking) {
        $booking['item_id'] = (int)$booking['item_id'];
        $booking['item_details'] = BookingHelper::getItemDetails($this->pdo, $type, $booking['item_id']);
        $allBookings[] = $booking;
      }
    }
    
    usort($allBookings, function($a, $b) {
      return strtotime($b['booked_at']) - strtotime($a['booked_at']);
    });
    
    return $allBookings;
  }
  
  public function createHotelBooking(int $userId, int $itemId, string $checkIn, string $checkOut, int $guests, float $totalPrice): int {
    $table = BookingHelper::getBookingTable('hotel');
    $itemIdColumn = BookingHelper::getItemIdColumn('hotel');
    $stmt = $this->pdo->prepare("INSERT INTO $table (user_id, $itemIdColumn, check_in, check_out, guests, total_price, status) VALUES (?, ?, ?, ?, ?, ?, 'confirmed')");
    $stmt->execute([$userId, $itemId, $checkIn, $checkOut, $guests, $totalPrice]);
    return (int)$this->pdo->lastInsertId();
  }
  
  public function createFlightBooking(int $userId, int $itemId, string $class, int $passengerCount, float $totalPrice): int {
    $table = BookingHelper::getBookingTable('flight');
    $itemIdColumn = BookingHelper::getItemIdColumn('flight');
    $stmt = $this->pdo->prepare("INSERT INTO $table (user_id, $itemIdColumn, class, passenger_count, total_price, status) VALUES (?, ?, ?, ?, ?, 'confirmed')");
    $stmt->execute([$userId, $itemId, $class, $passengerCount, $totalPrice]);
    return (int)$this->pdo->lastInsertId();
  }
  
  public function createActivityBooking(int $userId, int $itemId, string $date, int $participantCount, float $totalPrice): int {
    $table = BookingHelper::getBookingTable('activity');
    $itemIdColumn = BookingHelper::getItemIdColumn('activity');
    $stmt = $this->pdo->prepare("INSERT INTO $table (user_id, $itemIdColumn, date, participant_count, total_price, status) VALUES (?, ?, ?, ?, ?, 'confirmed')");
    $stmt->execute([$userId, $itemId, $date, $participantCount, $totalPrice]);
    return (int)$this->pdo->lastInsertId();
  }
  
  public function createTransferBooking(int $userId, int $itemId, int $passengerCount, float $totalPrice): int {
    $table = BookingHelper::getBookingTable('transfer');
    $itemIdColumn = BookingHelper::getItemIdColumn('transfer');
    $stmt = $this->pdo->prepare("INSERT INTO $table (user_id, $itemIdColumn, passenger_count, total_price, status) VALUES (?, ?, ?, ?, 'confirmed')");
    $stmt->execute([$userId, $itemId, $passengerCount, $totalPrice]);
    return (int)$this->pdo->lastInsertId();
  }
  
  public function updateBookingStatus(string $type, int $bookingId, string $status): void {
    $table = BookingHelper::getBookingTable($type);
    $cancelledClause = $status === 'cancelled' ? ", cancelled_at = SYSUTCDATETIME()" : "";
    $stmt = $this->pdo->prepare("UPDATE $table SET status = ?, updated_at = SYSUTCDATETIME()$cancelledClause WHERE id = ?");
    $stmt->execute([$status, $bookingId]);
  }
  
  public function updateBookingCount(string $type, int $itemId, int $delta): void {
    try {
      $entityTable = $type . 's';
      $stmt = $this->pdo->prepare("UPDATE $entityTable SET booking_count = booking_count + ? WHERE id = ?");
      $stmt->execute([$delta, $itemId]);
    } catch (PDOException $e) {
      error_log('Failed to update booking_count: ' . $e->getMessage());
    }
  }
}

