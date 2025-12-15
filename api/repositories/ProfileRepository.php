<?php
declare(strict_types=1);

require_once __DIR__ . '/BaseRepository.php';
require_once __DIR__ . '/../helpers/BookingHelper.php';

class ProfileRepository extends BaseRepository {
  public function getUserInfo(int $userId): ?array {
    $stmt = $this->pdo->prepare('SELECT u.id, u.email, u.first_name, u.last_name, r.name as role_name 
      FROM users u 
      LEFT JOIN roles r ON u.role_id = r.id 
      WHERE u.id = ?');
    $stmt->execute([$userId]);
    return $stmt->fetch() ?: null;
  }
  
  public function getOwnerBookings(int $userId, string $type): array {
    $bookingTable = BookingHelper::getBookingTable($type);
    $entityTable = $type === 'hotel' ? 'hotels' : ($type === 'flight' ? 'flight_routes' : ($type === 'activity' ? 'activities' : 'transfer_routes'));
    $itemIdColumn = BookingHelper::getItemIdColumn($type);
    
    if ($type === 'flight') {
      $stmt = $this->pdo->prepare("
        SELECT DISTINCT b.status, b.total_price 
        FROM $bookingTable b
        INNER JOIN flight_instances fi ON b.instance_id = fi.id
        INNER JOIN flight_schedules fs ON fi.schedule_id = fs.id
        LEFT JOIN flight_routes fr ON fs.route_id = fr.id
        LEFT JOIN flight_route_pairs frp ON fs.route_pair_id = frp.id
        LEFT JOIN flight_routes outbound ON frp.outbound_route_id = outbound.id
        LEFT JOIN flight_routes return_route ON frp.return_route_id = return_route.id
        WHERE (fr.created_by = ? OR outbound.created_by = ? OR return_route.created_by = ?)
          AND (fr.deleted_at IS NULL OR outbound.deleted_at IS NULL OR return_route.deleted_at IS NULL)
      ");
      $stmt->execute([$userId, $userId, $userId]);
    } else if ($type === 'transfer') {
      $stmt = $this->pdo->prepare("
        SELECT b.status, b.total_price 
        FROM $bookingTable b
        INNER JOIN transfer_instances ti ON b.instance_id = ti.id
        INNER JOIN transfer_schedules ts ON ti.schedule_id = ts.id
        INNER JOIN transfer_routes tr ON ts.route_id = tr.id
        WHERE tr.created_by = ? AND tr.deleted_at IS NULL
      ");
      $stmt->execute([$userId]);
    } else {
      $deletedFilter = "";
      try {
        $testStmt = $this->pdo->query("SELECT TOP 1 deleted_at FROM $entityTable");
        $testStmt->fetch();
        $deletedFilter = "AND e.deleted_at IS NULL";
      } catch (PDOException $e) {
      }
      $stmt = $this->pdo->prepare("
        SELECT b.status, b.total_price 
        FROM $bookingTable b
        INNER JOIN $entityTable e ON b.$itemIdColumn = e.id
        WHERE e.created_by = ? $deletedFilter
      ");
      $stmt->execute([$userId]);
    }
    return $stmt->fetchAll();
  }
  
  public function getCustomerBookings(int $userId, string $type): array {
    $table = BookingHelper::getBookingTable($type);
    $stmt = $this->pdo->prepare("SELECT status, total_price FROM $table WHERE user_id = ?");
    $stmt->execute([$userId]);
    return $stmt->fetchAll();
  }
  
  public function getRecentCustomerBookings(int $userId, string $type): array {
    $table = BookingHelper::getBookingTable($type);
    $itemIdColumn = BookingHelper::getItemIdColumn($type);
    $stmt = $this->pdo->prepare("SELECT TOP 5 *, '$type' as type, $itemIdColumn as item_id FROM $table WHERE user_id = ? ORDER BY booked_at DESC");
    $stmt->execute([$userId]);
    return $stmt->fetchAll();
  }
  
  public function getUserHotels(int $userId, bool $promotedOnly = false): array {
    $deletedFilter = "";
    try {
      $testStmt = $this->pdo->query("SELECT TOP 1 deleted_at FROM hotels");
      $testStmt->fetch();
      $deletedFilter = "AND h.deleted_at IS NULL";
    } catch (PDOException $e) {
    }
    
    $adFilter = $promotedOnly ? "AND h.ad = 1" : "";
    $orderBy = $promotedOnly ? "h.price_per_night ASC" : $this->getOrderBy('hotels', 'h.price_per_night ASC');
    
    $stmt = $this->pdo->prepare("SELECT h.*, c.name as city_name, p.name as province_name, p.region,
      (SELECT TOP 1 image_url FROM entity_images WHERE entity_type = 'hotel' AND entity_id = h.id ORDER BY display_order ASC, id ASC) as image_url
      FROM hotels h 
      LEFT JOIN cities c ON h.city_id = c.id 
      LEFT JOIN provinces p ON h.province_id = p.id 
      WHERE h.created_by = ? $adFilter $deletedFilter
      ORDER BY $orderBy");
    $stmt->execute([$userId]);
    return $stmt->fetchAll();
  }
  
  public function getUserFlights(int $userId, bool $promotedOnly = false): array {
    $deletedFilter = "";
    try {
      $testStmt = $this->pdo->query("SELECT TOP 1 deleted_at FROM flight_routes");
      $testStmt->fetch();
      $deletedFilter = "AND fr.deleted_at IS NULL";
    } catch (PDOException $e) {
    }
    
    $adFilter = $promotedOnly ? "AND fr.ad = 1" : "";
    $orderBy = $promotedOnly ? "fr.base_price_economy ASC" : $this->getOrderBy('flight_routes', 'fr.base_price_economy ASC');
    
    $stmt = $this->pdo->prepare("SELECT fr.*,
      oa.code as origin_code, oa.name as origin_name,
      da.code as destination_code, da.name as destination_name,
      oc.name as origin_city_name, op.name as origin_province_name,
      dc.name as destination_city_name, dp.name as destination_province_name,
      (SELECT TOP 1 image_url FROM entity_images WHERE entity_type = 'flight_route' AND entity_id = fr.id ORDER BY display_order ASC, id ASC) as image_url
      FROM flight_routes fr
      INNER JOIN airports oa ON fr.origin_airport_id = oa.id
      INNER JOIN airports da ON fr.destination_airport_id = da.id
      LEFT JOIN cities oc ON oa.city_id = oc.id
      LEFT JOIN provinces op ON oc.province_id = op.id
      LEFT JOIN cities dc ON da.city_id = dc.id
      LEFT JOIN provinces dp ON dc.province_id = dp.id
      WHERE fr.created_by = ? $adFilter $deletedFilter
      ORDER BY $orderBy");
    $stmt->execute([$userId]);
    return $stmt->fetchAll();
  }
  
  public function getUserActivities(int $userId, bool $promotedOnly = false): array {
    $adFilter = $promotedOnly ? "AND a.ad = 1" : "";
    $orderBy = $promotedOnly ? "a.date ASC, a.price ASC" : $this->getOrderBy('activities', 'a.price ASC, a.id ASC');
    
    $stmt = $this->pdo->prepare("SELECT a.*, c.name as city_name, p.name as province_name, p.region,
      (SELECT TOP 1 image_url FROM entity_images WHERE entity_type = 'activity' AND entity_id = a.id ORDER BY display_order ASC, id ASC) as image_url
      FROM activities a 
      LEFT JOIN cities c ON a.city_id = c.id 
      LEFT JOIN provinces p ON c.province_id = p.id 
      WHERE a.created_by = ? $adFilter AND a.deleted_at IS NULL
      ORDER BY $orderBy");
    $stmt->execute([$userId]);
    return $stmt->fetchAll();
  }
  
  public function getUserTransfers(int $userId, bool $promotedOnly = false): array {
    $deletedFilter = "";
    try {
      $testStmt = $this->pdo->query("SELECT TOP 1 deleted_at FROM transfer_routes");
      $testStmt->fetch();
      $deletedFilter = "AND tr.deleted_at IS NULL";
    } catch (PDOException $e) {
    }
    
    $adFilter = $promotedOnly ? "AND tr.ad = 1" : "";
    $orderBy = $promotedOnly ? "tr.base_price ASC" : $this->getOrderBy('transfer_routes', 'tr.base_price ASC');
    
    $stmt = $this->pdo->prepare("SELECT tr.*,
      oc.name as origin_city_name, op.name as origin_province_name,
      dc.name as destination_city_name, dp.name as destination_province_name,
      (SELECT TOP 1 image_url FROM entity_images WHERE entity_type = 'transfer_route' AND entity_id = tr.id ORDER BY display_order ASC, id ASC) as image_url
      FROM transfer_routes tr
      INNER JOIN cities oc ON tr.origin_city_id = oc.id
      INNER JOIN cities dc ON tr.destination_city_id = dc.id
      LEFT JOIN provinces op ON oc.province_id = op.id
      LEFT JOIN provinces dp ON dc.province_id = dp.id
      WHERE tr.created_by = ? $adFilter $deletedFilter
      ORDER BY $orderBy");
    $stmt->execute([$userId]);
    return $stmt->fetchAll();
  }
  
  private function getOrderBy(string $table, string $default): string {
    try {
      $testStmt = $this->pdo->query("SELECT TOP 1 ad FROM $table");
      $testStmt->fetch();
      // Sort by promoted items first (ad DESC), then by the default order
      // Extract the column name from default (e.g., "h.price_per_night ASC" -> "h.price_per_night")
      $defaultColumn = trim(str_replace([' ASC', ' DESC'], '', $default));
      return "ad DESC, $defaultColumn ASC";
    } catch (PDOException $e) {
      return $default;
    }
  }
}

