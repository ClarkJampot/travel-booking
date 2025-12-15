<?php
declare(strict_types=1);

/**
 * Booking Helper
 * Provides utilities for booking-related operations
 */
class BookingHelper {
  /**
   * Get item details for a booking
   * @param PDO $pdo Database connection
   * @param string $type Item type (hotel, flight, activity, transfer)
   * @param int $itemId Item ID
   * @param bool $useInstance Whether to use instance table (for flights/transfers) or route table. Defaults to true for flights/transfers, false for hotels/activities
   * @return array|null Item details or null if not found
   */
  public static function getItemDetails(PDO $pdo, string $type, int $itemId, ?bool $useInstance = null): ?array {
    // Auto-detect if useInstance should be true based on type
    if ($useInstance === null) {
      $useInstance = ($type === 'flight' || $type === 'transfer');
    }
    switch ($type) {
      case 'hotel':
        $stmt = $pdo->prepare('SELECT h.id, h.name, c.name as city_name, p.name as province_name 
          FROM hotels h 
          LEFT JOIN cities c ON h.city_id = c.id 
          LEFT JOIN provinces p ON h.province_id = p.id 
          WHERE h.id = ? AND h.deleted_at IS NULL');
        $stmt->execute([$itemId]);
        return $stmt->fetch() ?: null;
        
      case 'flight':
        if ($useInstance) {
          // For bookings, use flight_instances
          $stmt = $pdo->prepare('
            SELECT fi.id, fr.airline, 
              oa.code as origin, oa.name as origin_name,
              da.code as destination, da.name as destination_name,
              oc.name as origin_city_name, op.name as origin_province_name,
              dc.name as destination_city_name, pd.name as destination_province_name
            FROM flight_instances fi
            INNER JOIN flight_schedules fs ON fi.schedule_id = fs.id
            INNER JOIN flight_routes fr ON fs.route_id = fr.id
            INNER JOIN airports oa ON fr.origin_airport_id = oa.id
            INNER JOIN airports da ON fr.destination_airport_id = da.id
            LEFT JOIN cities oc ON oa.city_id = oc.id
            LEFT JOIN provinces op ON oc.province_id = op.id
            LEFT JOIN cities dc ON da.city_id = dc.id
            LEFT JOIN provinces pd ON dc.province_id = pd.id
            WHERE fi.id = ?
          ');
        } else {
          // For profile, use flight_routes (legacy)
          $stmt = $pdo->prepare('SELECT f.id, f.airline, f.origin, f.destination, 
            co.name as origin_city_name, po.name as origin_province_name,
            cd.name as destination_city_name, pd.name as destination_province_name
            FROM flights f
            LEFT JOIN cities co ON f.origin_city_id = co.id
            LEFT JOIN provinces po ON co.province_id = po.id
            LEFT JOIN cities cd ON f.destination_city_id = cd.id
            LEFT JOIN provinces pd ON cd.province_id = pd.id
            WHERE f.id = ?');
        }
        $stmt->execute([$itemId]);
        return $stmt->fetch() ?: null;
        
      case 'activity':
        $stmt = $pdo->prepare('SELECT a.id, a.title, c.name as city_name, p.name as province_name
          FROM activities a
          LEFT JOIN cities c ON a.city_id = c.id
          LEFT JOIN provinces p ON c.province_id = p.id
          WHERE a.id = ? AND a.deleted_at IS NULL');
        $stmt->execute([$itemId]);
        return $stmt->fetch() ?: null;
        
      case 'transfer':
        $stmt = $pdo->prepare('
          SELECT ti.id, tr.origin_specific as origin, tr.destination_specific as destination,
            oc.name as origin_city_name, op.name as origin_province_name,
            dc.name as destination_city_name, dp.name as destination_province_name
          FROM transfer_instances ti
          INNER JOIN transfer_schedules ts ON ti.schedule_id = ts.id
          INNER JOIN transfer_routes tr ON ts.route_id = tr.id
          LEFT JOIN cities oc ON tr.origin_city_id = oc.id
          LEFT JOIN provinces op ON oc.province_id = op.id
          LEFT JOIN cities dc ON tr.destination_city_id = dc.id
          LEFT JOIN provinces dp ON dc.province_id = dp.id
          WHERE ti.id = ?
        ');
        $stmt->execute([$itemId]);
        $result = $stmt->fetch();
        if ($result) {
          // Compute service field (not a database column)
          $result['service'] = ($result['origin'] ?? $result['origin_city_name']) . ' to ' . ($result['destination'] ?? $result['destination_city_name']);
        }
        return $result ?: null;
        
      default:
        return null;
    }
  }
  
  /**
   * Get booking table name for a type
   * @param string $type Item type
   * @return string Table name
   */
  public static function getBookingTable(string $type): string {
    $tables = [
      'hotel' => 'hotel_bookings',
      'flight' => 'flight_bookings',
      'activity' => 'activity_bookings',
      'transfer' => 'transfer_bookings'
    ];
    return $tables[$type] ?? '';
  }
  
  /**
   * Get item ID column name for a booking type
   * @param string $type Item type
   * @return string Column name
   */
  public static function getItemIdColumn(string $type): string {
    $columns = [
      'hotel' => 'hotel_id',
      'flight' => 'instance_id',
      'activity' => 'activity_id',
      'transfer' => 'instance_id'
    ];
    return $columns[$type] ?? '';
  }
}


