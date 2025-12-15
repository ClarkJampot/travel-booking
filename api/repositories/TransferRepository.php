<?php
declare(strict_types=1);

require_once __DIR__ . '/BaseRepository.php';

class TransferRepository extends BaseRepository {
  public function getAvailableDates(int $transferId): array {
    $stmt = $this->pdo->prepare('
      SELECT DISTINCT CAST(ti.departure_date AS DATE) as available_date
      FROM transfer_instances ti
      INNER JOIN transfer_schedules ts ON ti.schedule_id = ts.id
      INNER JOIN transfer_routes tr ON ts.route_id = tr.id
      WHERE tr.id = ?
        AND ti.status = ?
        AND ti.departure_date >= CAST(GETDATE() AS DATE)
        AND ti.seats_available > 0
      ORDER BY available_date ASC
    ');
    $stmt->execute([$transferId, 'scheduled']);
    $results = $stmt->fetchAll();
    return array_map(function($row) {
      return $row['available_date'];
    }, $results);
  }
  
  public function getInstancesForRoute(int $transferId, string $date, int $passengerCount): array {
    $stmt = $this->pdo->prepare('
      SELECT ti.*,
        ts.route_id, ts.departure_time,
        tr.base_price, tr.discount_percent,
        tr.origin_city_id, tr.destination_city_id,
        oc.name as origin_city_name, dc.name as destination_city_name
      FROM transfer_instances ti
      INNER JOIN transfer_schedules ts ON ti.schedule_id = ts.id
      INNER JOIN transfer_routes tr ON ts.route_id = tr.id
      INNER JOIN cities oc ON tr.origin_city_id = oc.id
      INNER JOIN cities dc ON tr.destination_city_id = dc.id
      WHERE tr.id = ?
        AND CAST(ti.departure_datetime AS DATE) = CAST(? AS DATE)
        AND ti.status = ?
        AND ti.seats_available >= ?
      ORDER BY ti.departure_datetime ASC
    ');
    $stmt->execute([$transferId, $date, 'scheduled', $passengerCount]);
    return $stmt->fetchAll();
  }
  
  public function getRouteById(int $id): ?array {
    $stmt = $this->pdo->prepare('
      SELECT tr.*,
        oc.name as origin_city_name, op.name as origin_province_name,
        dc.name as destination_city_name, dp.name as destination_province_name
      FROM transfer_routes tr
      INNER JOIN cities oc ON tr.origin_city_id = oc.id
      INNER JOIN cities dc ON tr.destination_city_id = dc.id
      LEFT JOIN provinces op ON oc.province_id = op.id
      LEFT JOIN provinces dp ON dc.province_id = dp.id
      WHERE tr.id = ? AND tr.deleted_at IS NULL
    ');
    $stmt->execute([$id]);
    return $stmt->fetch() ?: null;
  }
  
  public function getInstanceById(int $id): ?array {
    $stmt = $this->pdo->prepare('
      SELECT ti.*,
        ts.route_id, ts.departure_time, ts.days_of_week,
        tr.origin_city_id, tr.destination_city_id, tr.origin_specific, tr.destination_specific,
        tr.base_price,
        tr.description as route_description, tr.ad, tr.discount_percent,
        oc.name as origin_city_name, op.name as origin_province_name,
        dc.name as destination_city_name, dp.name as destination_province_name
      FROM transfer_instances ti
      INNER JOIN transfer_schedules ts ON ti.schedule_id = ts.id
      INNER JOIN transfer_routes tr ON ts.route_id = tr.id
      INNER JOIN cities oc ON tr.origin_city_id = oc.id
      INNER JOIN cities dc ON tr.destination_city_id = dc.id
      LEFT JOIN provinces op ON oc.province_id = op.id
      LEFT JOIN provinces dp ON dc.province_id = dp.id
      WHERE ti.id = ?
    ');
    $stmt->execute([$id]);
    return $stmt->fetch() ?: null;
  }
  
  public function getRoutes(array $filters, int $page, int $limit): array {
    $where = ['tr.deleted_at IS NULL'];
    $params = [];
    
    if (isset($filters['origin_city_id'])) {
      $where[] = 'tr.origin_city_id = ?';
      $params[] = $filters['origin_city_id'];
    } else if (isset($filters['origin_province_id'])) {
      $where[] = 'oc.province_id = ?';
      $params[] = $filters['origin_province_id'];
    }
    
    if (isset($filters['destination_city_id'])) {
      $where[] = 'tr.destination_city_id = ?';
      $params[] = $filters['destination_city_id'];
    } else if (isset($filters['destination_province_id'])) {
      $where[] = 'dc.province_id = ?';
      $params[] = $filters['destination_province_id'];
    }
    
    if (isset($filters['min_price'])) {
      $where[] = 'tr.base_price >= ?';
      $params[] = $filters['min_price'];
    }
    if (isset($filters['max_price'])) {
      $where[] = 'tr.base_price <= ?';
      $params[] = $filters['max_price'];
    }
    
    $whereSql = 'WHERE ' . implode(' AND ', $where);
    $offset = ($page - 1) * $limit;
    
    $promotedRoutes = [];
    $promotedIds = [];
    try {
      $promotedWhere = array_merge(['tr.ad = 1'], $where);
      $promotedWhereSql = 'WHERE ' . implode(' AND ', $promotedWhere);
      
      $promotedSql = "SELECT TOP 2 tr.*,
          oc.name as origin_city_name, op.name as origin_province_name,
          dc.name as destination_city_name, dp.name as destination_province_name,
          (SELECT TOP 1 image_url FROM entity_images WHERE entity_type = 'transfer_route' AND entity_id = tr.id ORDER BY display_order ASC, id ASC) as image_url
        FROM transfer_routes tr
        INNER JOIN cities oc ON tr.origin_city_id = oc.id
        INNER JOIN cities dc ON tr.destination_city_id = dc.id
        LEFT JOIN provinces op ON oc.province_id = op.id
        LEFT JOIN provinces dp ON dc.province_id = dp.id
        $promotedWhereSql
        ORDER BY NEWID()";
      
      $promotedStmt = $this->pdo->prepare($promotedSql);
      $promotedStmt->execute($params);
      $promotedRoutes = $promotedStmt->fetchAll();
      $promotedIds = array_column($promotedRoutes, 'id');
    } catch (PDOException $e) {
      error_log('Promoted routes query failed: ' . $e->getMessage());
    }
    
    $regularWhere = $where;
    $regularWhere[] = "(tr.ad = 0 OR tr.ad IS NULL)";
    if (!empty($promotedIds)) {
      $placeholders = implode(',', array_fill(0, count($promotedIds), '?'));
      $regularWhere[] = "tr.id NOT IN ($placeholders)";
      $regularParams = array_merge($params, $promotedIds);
    } else {
      $regularParams = $params;
    }
    
    $regularWhereSql = 'WHERE ' . implode(' AND ', $regularWhere);
    
    $sql = "SELECT tr.*,
        oc.name as origin_city_name, op.name as origin_province_name,
        dc.name as destination_city_name, dp.name as destination_province_name,
        (SELECT TOP 1 image_url FROM entity_images WHERE entity_type = 'transfer_route' AND entity_id = tr.id ORDER BY display_order ASC, id ASC) as image_url
      FROM transfer_routes tr
      INNER JOIN cities oc ON tr.origin_city_id = oc.id
      INNER JOIN cities dc ON tr.destination_city_id = dc.id
      LEFT JOIN provinces op ON oc.province_id = op.id
      LEFT JOIN provinces dp ON dc.province_id = dp.id
      $regularWhereSql
      ORDER BY tr.created_at DESC
      OFFSET $offset ROWS FETCH NEXT $limit ROWS ONLY";
    
    $stmt = $this->pdo->prepare($sql);
    $stmt->execute($regularParams);
    $routes = $stmt->fetchAll();
    
    return [
      'promoted' => $promotedRoutes,
      'regular' => $routes
    ];
  }
  
  public function getInstancesWithFilters(array $filters, string $date, int $passengerCount, int $page, int $limit): array {
    $where = ['ti.departure_date >= CAST(? AS DATE)', 'ti.status = ?'];
    $params = [$date, 'scheduled'];
    
    if (isset($filters['origin_city_id'])) {
      $where[] = 'tr.origin_city_id = ?';
      $params[] = $filters['origin_city_id'];
    }
    if (isset($filters['destination_city_id'])) {
      $where[] = 'tr.destination_city_id = ?';
      $params[] = $filters['destination_city_id'];
    }
    
    $where[] = 'ti.seats_available >= ?';
    $params[] = $passengerCount;
    
    $whereSql = 'WHERE ' . implode(' AND ', $where);
    $offset = ($page - 1) * $limit;
    
    $promotedTransfers = [];
    $promotedIds = [];
    
    try {
      $promotedWhere = array_merge(['tr.ad = 1'], $where);
      $promotedWhereSql = 'WHERE ' . implode(' AND ', $promotedWhere);
      
      $promotedSql = "SELECT TOP 2 ti.*,
          ts.route_id,
          tr.base_price,
          tr.ad, tr.discount_percent,
          oc.name as origin_city_name, dc.name as destination_city_name
        FROM transfer_instances ti
        INNER JOIN transfer_schedules ts ON ti.schedule_id = ts.id
        INNER JOIN transfer_routes tr ON ts.route_id = tr.id
        INNER JOIN cities oc ON tr.origin_city_id = oc.id
        INNER JOIN cities dc ON tr.destination_city_id = dc.id
        $promotedWhereSql
        ORDER BY NEWID()";
      
      $promotedStmt = $this->pdo->prepare($promotedSql);
      $promotedStmt->execute($params);
      $promotedTransfers = $promotedStmt->fetchAll();
      $promotedIds = array_column($promotedTransfers, 'id');
    } catch (PDOException $e) {
      error_log('Promoted transfers query failed: ' . $e->getMessage());
    }
    
    $regularWhere = $where;
    $regularWhere[] = "(tr.ad = 0 OR tr.ad IS NULL)";
    if (!empty($promotedIds)) {
      $placeholders = implode(',', array_fill(0, count($promotedIds), '?'));
      $regularWhere[] = "ti.id NOT IN ($placeholders)";
      $regularParams = array_merge($params, $promotedIds);
    } else {
      $regularParams = $params;
    }
    
    $regularWhereSql = 'WHERE ' . implode(' AND ', $regularWhere);
    
    $sql = "SELECT ti.*,
        ts.route_id,
        tr.base_price,
        tr.ad, tr.discount_percent,
        oc.name as origin_city_name, dc.name as destination_city_name
      FROM transfer_instances ti
      INNER JOIN transfer_schedules ts ON ti.schedule_id = ts.id
      INNER JOIN transfer_routes tr ON ts.route_id = tr.id
      INNER JOIN cities oc ON tr.origin_city_id = oc.id
      INNER JOIN cities dc ON tr.destination_city_id = dc.id
      $regularWhereSql
      ORDER BY ti.departure_datetime ASC
      OFFSET $offset ROWS FETCH NEXT $limit ROWS ONLY";
    
    $stmt = $this->pdo->prepare($sql);
    $stmt->execute($regularParams);
    $transfers = $stmt->fetchAll();
    
    return [
      'promoted' => $promotedTransfers,
      'regular' => $transfers
    ];
  }
  
  public function getInstanceForBooking(int $instanceId): ?array {
    $stmt = $this->pdo->prepare('
      SELECT ti.*, tr.base_price, tr.discount_percent
      FROM transfer_instances ti
      INNER JOIN transfer_schedules ts ON ti.schedule_id = ts.id
      INNER JOIN transfer_routes tr ON ts.route_id = tr.id
      WHERE ti.id = ? AND ti.status = ?
    ');
    $stmt->execute([$instanceId, 'scheduled']);
    $instance = $stmt->fetch();
    if ($instance) {
      // Set capacity to 12 seats per vehicle (as used in instance creation)
      $instance['capacity'] = 12;
    }
    return $instance ?: null;
  }
  
  public function createBooking(int $userId, int $instanceId, int $passengerCount, float $totalPrice): int {
    $stmt = $this->pdo->prepare('
      INSERT INTO transfer_bookings (user_id, instance_id, passenger_count, total_price, status)
      VALUES (?, ?, ?, ?, ?)
    ');
    $stmt->execute([
      $userId, $instanceId,
      $passengerCount, $totalPrice, 'confirmed'
    ]);
    return (int)$this->pdo->lastInsertId();
  }
  
  public function updateSeatAvailability(int $instanceId, int $passengerCount, int $capacity): void {
    $stmt = $this->pdo->prepare('UPDATE transfer_instances SET seats_available = seats_available - ? WHERE id = ?');
    $stmt->execute([$passengerCount, $instanceId]);
    
    $stmt = $this->pdo->prepare('
      UPDATE transfer_instances 
      SET vehicles_available = FLOOR(seats_available / ?)
      WHERE id = ? AND seats_available < (vehicles_available * ?)
    ');
    $stmt->execute([$capacity, $instanceId, $capacity]);
  }
  
  public function getBookingById(int $bookingId): ?array {
    $stmt = $this->pdo->prepare('SELECT * FROM transfer_bookings WHERE id = ?');
    $stmt->execute([$bookingId]);
    return $stmt->fetch() ?: null;
  }
}

