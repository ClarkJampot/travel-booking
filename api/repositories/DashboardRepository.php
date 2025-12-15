<?php
declare(strict_types=1);

require_once __DIR__ . '/BaseRepository.php';
require_once __DIR__ . '/../helpers/StatsHelper.php';

class DashboardRepository extends BaseRepository {
  public function getOwnerBookings(int $userId, string $type): array {
    if ($type === 'hotel') {
      $stmt = $this->pdo->prepare("
        SELECT b.*, 'hotel' as type, b.hotel_id as item_id
        FROM hotel_bookings b
        INNER JOIN hotels i ON b.hotel_id = i.id
        WHERE i.created_by = ? AND i.deleted_at IS NULL
      ");
      $stmt->execute([$userId]);
    } else if ($type === 'flight') {
      $stmt = $this->pdo->prepare("
        SELECT DISTINCT b.*, 'flight' as type, b.instance_id as item_id
        FROM flight_bookings b
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
        SELECT b.*, 'transfer' as type, b.instance_id as item_id
        FROM transfer_bookings b
        INNER JOIN transfer_instances ti ON b.instance_id = ti.id
        INNER JOIN transfer_schedules ts ON ti.schedule_id = ts.id
        INNER JOIN transfer_routes tr ON ts.route_id = tr.id
        WHERE tr.created_by = ? AND tr.deleted_at IS NULL
      ");
      $stmt->execute([$userId]);
    } else {
      $stmt = $this->pdo->prepare("
        SELECT b.*, 'activity' as type, b.activity_id as item_id
        FROM activity_bookings b
        INNER JOIN activities i ON b.activity_id = i.id
        WHERE i.created_by = ? AND i.deleted_at IS NULL
      ");
      $stmt->execute([$userId]);
    }
    return $stmt->fetchAll();
  }
  
  public function getOwnerBookingsWithFilters(int $userId, string $type, ?string $status, ?string $customerName, ?string $keyword, int $offset, int $limit): array {
    $where = [];
    $params = [];
    
    if ($type === 'hotel') {
      $where[] = "i.created_by = ?";
      $params[] = $userId;
      if ($status) {
        $where[] = 'b.status = ?';
        $params[] = $status;
      }
      if ($customerName) {
        $where[] = "(u.first_name + ' ' + COALESCE(u.last_name, '')) LIKE ?";
        $params[] = '%' . str_replace(['%', '_', '[', ']'], ['[%]', '[_]', '[[]', '[]]'], $customerName) . '%';
      }
      if ($keyword) {
        $searchTerm = str_replace(['%', '_', '[', ']'], ['[%]', '[_]', '[[]', '[]]'], $keyword);
        $where[] = "(CAST(b.id AS NVARCHAR) LIKE ? OR i.name LIKE ? OR (u.first_name + ' ' + COALESCE(u.last_name, '')) LIKE ?)";
        $params[] = '%' . $searchTerm . '%';
        $params[] = '%' . $searchTerm . '%';
        $params[] = '%' . $searchTerm . '%';
      }
      $whereSql = 'WHERE ' . implode(' AND ', $where);
      $sql = "
        SELECT b.*, 'hotel' as type, b.hotel_id as item_id, i.name as item_name,
          (u.first_name + ' ' + COALESCE(u.last_name, '')) as customer_name,
          b.guests as participants
        FROM hotel_bookings b
        INNER JOIN hotels i ON b.hotel_id = i.id
        LEFT JOIN users u ON b.user_id = u.id
        $whereSql AND i.deleted_at IS NULL
        ORDER BY b.booked_at DESC, b.id DESC
        OFFSET $offset ROWS FETCH NEXT $limit ROWS ONLY
      ";
    } else if ($type === 'flight') {
      $where[] = "(fr.created_by = ? OR outbound.created_by = ? OR return_route.created_by = ?)";
      $params[] = $userId;
      $params[] = $userId;
      $params[] = $userId;
      if ($status) {
        $where[] = 'b.status = ?';
        $params[] = $status;
      }
      if ($customerName) {
        $where[] = "(u.first_name + ' ' + COALESCE(u.last_name, '')) LIKE ?";
        $params[] = '%' . str_replace(['%', '_', '[', ']'], ['[%]', '[_]', '[[]', '[]]'], $customerName) . '%';
      }
      if ($keyword) {
        $searchTerm = str_replace(['%', '_', '[', ']'], ['[%]', '[_]', '[[]', '[]]'], $keyword);
        $where[] = "(CAST(b.id AS NVARCHAR) LIKE ? OR fr.airline LIKE ? OR (u.first_name + ' ' + COALESCE(u.last_name, '')) LIKE ?)";
        $params[] = '%' . $searchTerm . '%';
        $params[] = '%' . $searchTerm . '%';
        $params[] = '%' . $searchTerm . '%';
      }
      $whereSql = 'WHERE ' . implode(' AND ', $where);
      $sql = "
        SELECT DISTINCT b.*, 'flight' as type, b.instance_id as item_id, fr.airline as item_name,
          (u.first_name + ' ' + COALESCE(u.last_name, '')) as customer_name,
          b.passenger_count as participants
        FROM flight_bookings b
        INNER JOIN flight_instances fi ON b.instance_id = fi.id
        INNER JOIN flight_schedules fs ON fi.schedule_id = fs.id
        LEFT JOIN flight_routes fr ON fs.route_id = fr.id
        LEFT JOIN flight_route_pairs frp ON fs.route_pair_id = frp.id
        LEFT JOIN flight_routes outbound ON frp.outbound_route_id = outbound.id
        LEFT JOIN flight_routes return_route ON frp.return_route_id = return_route.id
        LEFT JOIN users u ON b.user_id = u.id
        $whereSql AND (fr.deleted_at IS NULL OR outbound.deleted_at IS NULL OR return_route.deleted_at IS NULL)
        ORDER BY b.booked_at DESC, b.id DESC
        OFFSET $offset ROWS FETCH NEXT $limit ROWS ONLY
      ";
    } else if ($type === 'transfer') {
      $where[] = "tr.created_by = ?";
      $params[] = $userId;
      if ($status) {
        $where[] = 'b.status = ?';
        $params[] = $status;
      }
      if ($customerName) {
        $where[] = "(u.first_name + ' ' + COALESCE(u.last_name, '')) LIKE ?";
        $params[] = '%' . str_replace(['%', '_', '[', ']'], ['[%]', '[_]', '[[]', '[]]'], $customerName) . '%';
      }
      if ($keyword) {
        $searchTerm = str_replace(['%', '_', '[', ']'], ['[%]', '[_]', '[[]', '[]]'], $keyword);
        $where[] = "(CAST(b.id AS NVARCHAR) LIKE ? OR (oc.name + ' to ' + dc.name) LIKE ? OR (u.first_name + ' ' + COALESCE(u.last_name, '')) LIKE ?)";
        $params[] = '%' . $searchTerm . '%';
        $params[] = '%' . $searchTerm . '%';
        $params[] = '%' . $searchTerm . '%';
      }
      $whereSql = 'WHERE ' . implode(' AND ', $where);
      $sql = "
        SELECT b.*, 'transfer' as type, b.instance_id as item_id,
          (oc.name + ' to ' + dc.name) as item_name,
          (u.first_name + ' ' + COALESCE(u.last_name, '')) as customer_name,
          b.passenger_count as participants
        FROM transfer_bookings b
        INNER JOIN transfer_instances ti ON b.instance_id = ti.id
        INNER JOIN transfer_schedules ts ON ti.schedule_id = ts.id
        INNER JOIN transfer_routes tr ON ts.route_id = tr.id
        INNER JOIN cities oc ON tr.origin_city_id = oc.id
        INNER JOIN cities dc ON tr.destination_city_id = dc.id
        LEFT JOIN users u ON b.user_id = u.id
        $whereSql AND tr.deleted_at IS NULL
        ORDER BY b.booked_at DESC, b.id DESC
        OFFSET $offset ROWS FETCH NEXT $limit ROWS ONLY
      ";
    } else {
      $where[] = "i.created_by = ?";
      $params[] = $userId;
      if ($status) {
        $where[] = 'b.status = ?';
        $params[] = $status;
      }
      if ($customerName) {
        $where[] = "(u.first_name + ' ' + COALESCE(u.last_name, '')) LIKE ?";
        $params[] = '%' . str_replace(['%', '_', '[', ']'], ['[%]', '[_]', '[[]', '[]]'], $customerName) . '%';
      }
      if ($keyword) {
        $searchTerm = str_replace(['%', '_', '[', ']'], ['[%]', '[_]', '[[]', '[]]'], $keyword);
        $where[] = "(CAST(b.id AS NVARCHAR) LIKE ? OR i.title LIKE ? OR (u.first_name + ' ' + COALESCE(u.last_name, '')) LIKE ?)";
        $params[] = '%' . $searchTerm . '%';
        $params[] = '%' . $searchTerm . '%';
        $params[] = '%' . $searchTerm . '%';
      }
      $whereSql = 'WHERE ' . implode(' AND ', $where);
      $sql = "
        SELECT b.*, 'activity' as type, b.activity_id as item_id, i.title as item_name,
          (u.first_name + ' ' + COALESCE(u.last_name, '')) as customer_name,
          b.participant_count as participants
        FROM activity_bookings b
        INNER JOIN activities i ON b.activity_id = i.id
        LEFT JOIN users u ON b.user_id = u.id
        $whereSql AND i.deleted_at IS NULL
        ORDER BY b.booked_at DESC, b.id DESC
        OFFSET $offset ROWS FETCH NEXT $limit ROWS ONLY
      ";
    }
    
    $stmt = $this->pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
  }
  
  public function getUserItems(int $userId, ?string $itemType): array {
    $result = [
      'hotels' => [],
      'flights' => [],
      'activities' => [],
      'transfers' => []
    ];
    
    $types = $itemType ? [$itemType] : ['hotel', 'flight', 'activity', 'transfer'];
    
    foreach ($types as $type) {
      if ($type === 'hotel') {
        $stmt = $this->pdo->prepare("SELECT h.*, CAST(h.ad AS INT) as ad, c.name as city_name, c.province_id, p.name as province_name 
          FROM hotels h 
          LEFT JOIN cities c ON h.city_id = c.id 
          LEFT JOIN provinces p ON c.province_id = p.id 
          WHERE h.created_by = ? AND h.deleted_at IS NULL ORDER BY h.id DESC");
        $stmt->execute([$userId]);
        $result['hotels'] = $stmt->fetchAll();
      } else if ($type === 'flight') {
        $stmt = $this->pdo->prepare("SELECT fr.*, CAST(fr.ad AS INT) as ad,
          oc.name as origin_city_name, oc.province_id as origin_province_id, op.name as origin_province_name,
          dc.name as destination_city_name, dc.province_id as destination_province_id, dp.name as destination_province_name
          FROM flight_routes fr
          INNER JOIN airports oa ON fr.origin_airport_id = oa.id
          INNER JOIN airports da ON fr.destination_airport_id = da.id
          LEFT JOIN cities oc ON oa.city_id = oc.id
          LEFT JOIN provinces op ON oc.province_id = op.id
          LEFT JOIN cities dc ON da.city_id = dc.id
          LEFT JOIN provinces dp ON dc.province_id = dp.id
          WHERE fr.created_by = ? AND fr.deleted_at IS NULL ORDER BY fr.id DESC");
        $stmt->execute([$userId]);
        $result['flights'] = $stmt->fetchAll();
      } else if ($type === 'activity') {
        $stmt = $this->pdo->prepare("SELECT a.*, CAST(a.ad AS INT) as ad, c.name as city_name, c.province_id, p.name as province_name 
          FROM activities a 
          LEFT JOIN cities c ON a.city_id = c.id 
          LEFT JOIN provinces p ON c.province_id = p.id 
          WHERE a.created_by = ? AND a.deleted_at IS NULL ORDER BY a.id DESC");
        $stmt->execute([$userId]);
        $result['activities'] = $stmt->fetchAll();
      } else if ($type === 'transfer') {
        $stmt = $this->pdo->prepare("SELECT tr.*, CAST(tr.ad AS INT) as ad,
          oc.name as origin_city_name, oc.province_id as origin_province_id, op.name as origin_province_name,
          dc.name as destination_city_name, dc.province_id as destination_province_id, dp.name as destination_province_name
          FROM transfer_routes tr
          INNER JOIN cities oc ON tr.origin_city_id = oc.id
          INNER JOIN cities dc ON tr.destination_city_id = dc.id
          LEFT JOIN provinces op ON oc.province_id = op.id
          LEFT JOIN provinces dp ON dc.province_id = dp.id
          WHERE tr.created_by = ? AND tr.deleted_at IS NULL ORDER BY tr.id DESC");
        $stmt->execute([$userId]);
        $result['transfers'] = $stmt->fetchAll();
      }
    }
    
    return $result;
  }
}

