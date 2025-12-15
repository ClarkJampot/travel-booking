<?php
declare(strict_types=1);

require_once __DIR__ . '/BaseRepository.php';

class FlightRepository extends BaseRepository {
  public function getReturnRouteId(int $outboundRouteId): ?int {
    $stmt = $this->pdo->prepare("
      SELECT frp.return_route_id
      FROM flight_route_pairs frp
      WHERE frp.outbound_route_id = ?
    ");
    $stmt->execute([$outboundRouteId]);
    $result = $stmt->fetch();
    return $result ? (int)$result['return_route_id'] : null;
  }
  
  public function getAvailableDates(int $routeId): array {
    $stmt = $this->pdo->prepare('
      SELECT DISTINCT CAST(fi.departure_date AS DATE) as available_date
      FROM flight_instances fi
      INNER JOIN flight_schedules fs ON fi.schedule_id = fs.id
      WHERE fs.route_id = ?
        AND fi.status = ?
        AND fi.departure_date >= CAST(GETDATE() AS DATE)
        AND (
          fi.seats_economy_available > 0 
          OR fi.seats_business_available > 0 
          OR fi.seats_first_available > 0
        )
      ORDER BY available_date ASC
    ');
    $stmt->execute([$routeId, 'scheduled']);
    $results = $stmt->fetchAll();
    return array_map(function($row) {
      return $row['available_date'];
    }, $results);
  }
  
  public function getRouteById(int $id): ?array {
    $stmt = $this->pdo->prepare('
      SELECT fr.*,
        oa.code as origin_code, oa.name as origin_name,
        da.code as destination_code, da.name as destination_name,
        oc.name as origin_city_name, op.name as origin_province_name,
        dc.name as destination_city_name, dp.name as destination_province_name
      FROM flight_routes fr
      INNER JOIN airports oa ON fr.origin_airport_id = oa.id
      INNER JOIN airports da ON fr.destination_airport_id = da.id
      LEFT JOIN cities oc ON oa.city_id = oc.id
      LEFT JOIN provinces op ON oc.province_id = op.id
      LEFT JOIN cities dc ON da.city_id = dc.id
      LEFT JOIN provinces dp ON dc.province_id = dp.id
      WHERE fr.id = ?
    ');
    $stmt->execute([$id]);
    return $stmt->fetch() ?: null;
  }
  
  public function getInstanceById(int $id): ?array {
    $stmt = $this->pdo->prepare('
      SELECT fi.*,
        fs.route_id, fs.route_pair_id, fs.departure_time, fs.days_of_week,
        fr.origin_airport_id, fr.destination_airport_id, fr.airline,
        fr.base_price_economy, fr.base_price_business, fr.base_price_first,
        fr.aircraft_type,
        fr.ad, fr.discount_percent,
        oa.code as origin_code, oa.name as origin_name,
        da.code as destination_code, da.name as destination_name,
        oc.name as origin_city_name, op.name as origin_province_name,
        dc.name as destination_city_name, dp.name as destination_province_name
      FROM flight_instances fi
      INNER JOIN flight_schedules fs ON fi.schedule_id = fs.id
      LEFT JOIN flight_routes fr ON fs.route_id = fr.id
      LEFT JOIN flight_route_pairs frp ON fs.route_pair_id = frp.id
      LEFT JOIN flight_routes outbound ON frp.outbound_route_id = outbound.id
      LEFT JOIN flight_routes return_route ON frp.return_route_id = return_route.id
      LEFT JOIN airports oa ON COALESCE(fr.origin_airport_id, outbound.origin_airport_id) = oa.id
      LEFT JOIN airports da ON COALESCE(fr.destination_airport_id, outbound.destination_airport_id) = da.id
      LEFT JOIN cities oc ON oa.city_id = oc.id
      LEFT JOIN provinces op ON oc.province_id = op.id
      LEFT JOIN cities dc ON da.city_id = dc.id
      LEFT JOIN provinces dp ON dc.province_id = dp.id
      WHERE fi.id = ?
    ');
    $stmt->execute([$id]);
    return $stmt->fetch() ?: null;
  }
  
  public function getRoutes(array $filters, int $page, int $limit): array {
    $where = [];
    $params = [];
    
    if (isset($filters['origin_airport_id'])) {
      $where[] = 'fr.origin_airport_id = ?';
      $params[] = $filters['origin_airport_id'];
    }
    if (isset($filters['origin_city_id'])) {
      $where[] = 'oc.id = ?';
      $params[] = $filters['origin_city_id'];
    }
    if (isset($filters['destination_airport_id'])) {
      $where[] = 'fr.destination_airport_id = ?';
      $params[] = $filters['destination_airport_id'];
    }
    if (isset($filters['destination_city_id'])) {
      $where[] = 'dc.id = ?';
      $params[] = $filters['destination_city_id'];
    }
    if (isset($filters['min_price'])) {
      $where[] = 'fr.base_price_economy >= ?';
      $params[] = $filters['min_price'];
    }
    if (isset($filters['max_price'])) {
      $where[] = 'fr.base_price_economy <= ?';
      $params[] = $filters['max_price'];
    }
    
    $whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';
    $offset = ($page - 1) * $limit;
    
    $promotedFlights = [];
    $promotedIds = [];
    try {
      $promotedWhere = array_merge(['fr.ad = 1'], $where);
      $promotedWhereSql = 'WHERE ' . implode(' AND ', $promotedWhere);
      
      $promotedSql = "SELECT TOP 2 fr.id, fr.origin_airport_id, fr.destination_airport_id, fr.airline, 
          fr.base_price_economy, fr.base_price_business, fr.base_price_first, 
          fr.aircraft_type, fr.ad, fr.discount_percent, fr.created_by, fr.created_at,
          oa.code as origin_code, oa.name as origin_name,
          da.code as destination_code, da.name as destination_name,
          oc.name as origin_city_name, dc.name as destination_city_name,
          NULL as image_url
        FROM flight_routes fr
        INNER JOIN airports oa ON fr.origin_airport_id = oa.id
        INNER JOIN airports da ON fr.destination_airport_id = da.id
        LEFT JOIN cities oc ON oa.city_id = oc.id
        LEFT JOIN cities dc ON da.city_id = dc.id
        $promotedWhereSql
        ORDER BY NEWID()";
      
      $promotedStmt = $this->pdo->prepare($promotedSql);
      $promotedStmt->execute($params);
      $promotedFlights = $promotedStmt->fetchAll();
      $promotedIds = array_column($promotedFlights, 'id');
    } catch (PDOException $e) {
      error_log('Promoted routes query failed: ' . $e->getMessage());
    }
    
    $regularWhere = $where;
    $regularWhere[] = "(fr.ad = 0 OR fr.ad IS NULL)";
    if (!empty($promotedIds)) {
      $placeholders = implode(',', array_fill(0, count($promotedIds), '?'));
      $regularWhere[] = "fr.id NOT IN ($placeholders)";
      $regularParams = array_merge($params, $promotedIds);
    } else {
      $regularParams = $params;
    }
    
    $regularWhereSql = 'WHERE ' . implode(' AND ', $regularWhere);
    
    $sql = "SELECT fr.id, fr.origin_airport_id, fr.destination_airport_id, fr.airline, 
        fr.base_price_economy, fr.base_price_business, fr.base_price_first, 
        fr.aircraft_type, fr.ad, fr.discount_percent, fr.created_by, fr.created_at,
        oa.code as origin_code, oa.name as origin_name,
        da.code as destination_code, da.name as destination_name,
        oc.name as origin_city_name, dc.name as destination_city_name,
        NULL as image_url
      FROM flight_routes fr
      INNER JOIN airports oa ON fr.origin_airport_id = oa.id
      INNER JOIN airports da ON fr.destination_airport_id = da.id
      LEFT JOIN cities oc ON oa.city_id = oc.id
      LEFT JOIN cities dc ON da.city_id = dc.id
      $regularWhereSql
      ORDER BY fr.id ASC
      OFFSET $offset ROWS FETCH NEXT $limit ROWS ONLY";
    
    $stmt = $this->pdo->prepare($sql);
    $stmt->execute($regularParams);
    $flights = $stmt->fetchAll();
    
    return [
      'promoted' => $promotedFlights,
      'regular' => $flights
    ];
  }
  
  public function getInstancesForRoute(int $routeId, string $departureDate, string $class, int $passengerCount): array {
    $validColumns = ['seats_economy_available', 'seats_business_available', 'seats_first_available'];
    $seatsAvailableColumn = 'seats_' . $class . '_available';
    
    if (!in_array($seatsAvailableColumn, $validColumns)) {
      throw new InvalidArgumentException('Invalid class');
    }
    
    $stmt = $this->pdo->prepare("
      SELECT fi.*,
        fs.departure_time,
        fr.base_price_economy, fr.base_price_business, fr.base_price_first,
        fr.discount_percent,
        fr.airline
      FROM flight_instances fi
      INNER JOIN flight_schedules fs ON fi.schedule_id = fs.id
      INNER JOIN flight_routes fr ON fs.route_id = fr.id
      WHERE fs.route_id = ?
        AND fi.departure_date = CAST(? AS DATE)
        AND fi.status = 'scheduled'
        AND fi.$seatsAvailableColumn >= ?
      ORDER BY fi.departure_datetime ASC
    ");
    
    $stmt->execute([$routeId, $departureDate, $passengerCount]);
    return $stmt->fetchAll();
  }
  
  public function getInstanceForBooking(int $instanceId): ?array {
    $stmt = $this->pdo->prepare('
      SELECT fi.*, fr.base_price_economy, fr.base_price_business, fr.base_price_first, fr.discount_percent
      FROM flight_instances fi
      INNER JOIN flight_schedules fs ON fi.schedule_id = fs.id
      LEFT JOIN flight_routes fr ON fs.route_id = fr.id
      WHERE fi.id = ? AND fi.status = ?
    ');
    $stmt->execute([$instanceId, 'scheduled']);
    return $stmt->fetch() ?: null;
  }
  
  public function createBooking(int $userId, int $instanceId, string $class, int $passengerCount, float $totalPrice): int {
    $stmt = $this->pdo->prepare('
      INSERT INTO flight_bookings (user_id, instance_id, class, passenger_count, total_price, status)
      VALUES (?, ?, ?, ?, ?, ?)
    ');
    $stmt->execute([
      $userId, $instanceId, $class,
      $passengerCount, $totalPrice, 'confirmed'
    ]);
    return (int)$this->pdo->lastInsertId();
  }
  
  public function updateSeatAvailability(int $instanceId, string $class, int $passengerCount): void {
    $validColumns = ['seats_economy_available', 'seats_business_available', 'seats_first_available'];
    $seatsAvailableColumn = 'seats_' . $class . '_available';
    
    if (!in_array($seatsAvailableColumn, $validColumns)) {
      throw new InvalidArgumentException('Invalid class');
    }
    
    $stmt = $this->pdo->prepare("UPDATE flight_instances SET $seatsAvailableColumn = $seatsAvailableColumn - ? WHERE id = ?");
    $stmt->execute([$passengerCount, $instanceId]);
  }
  
  public function getBookingById(int $bookingId): ?array {
    $stmt = $this->pdo->prepare('SELECT * FROM flight_bookings WHERE id = ?');
    $stmt->execute([$bookingId]);
    return $stmt->fetch() ?: null;
  }
}

