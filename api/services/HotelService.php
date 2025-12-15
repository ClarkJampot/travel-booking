<?php
declare(strict_types=1);

require_once __DIR__ . '/BaseService.php';
require_once __DIR__ . '/../helpers/QueryBuilder.php';
require_once __DIR__ . '/../helpers/FilterHelper.php';
require_once __DIR__ . '/../helpers/ImageHelper.php';
require_once __DIR__ . '/../helpers/PromotionHelper.php';
require_once __DIR__ . '/../helpers/ErrorHandler.php';

class HotelService extends BaseService {
  public function getById(int $id): ?array {
    $stmt = $this->pdo->prepare('SELECT h.*, c.name as city_name, p.name as province_name, p.region 
      FROM hotels h 
      LEFT JOIN cities c ON h.city_id = c.id 
      LEFT JOIN provinces p ON h.province_id = p.id 
      WHERE h.id = ? AND h.deleted_at IS NULL');
    $stmt->execute([$id]);
    $hotel = $stmt->fetch();
    if (!$hotel) {
      return null;
    }
    
    $images = ImageHelper::getEntityImages($this->pdo, 'hotel', $id);
    return ImageHelper::addImageUrlToEntity($hotel, $images);
  }
  
  public function list(array $filters = []): array {
    $pagination = FilterHelper::parsePagination();
    $page = $pagination['page'];
    $limit = $pagination['limit'];
    
    $qb = new QueryBuilder();
    $qb->join('LEFT JOIN cities c ON h.city_id = c.id');
    $qb->join('LEFT JOIN provinces p ON h.province_id = p.id');
    $qb->where('h.deleted_at IS NULL');
    
    FilterHelper::addLocationFilters($qb, 'h');
    FilterHelper::addPriceFilters($qb, 'h.price_per_night');
    FilterHelper::addCreatedByFilter($qb, 'h');
    
    if (isset($filters['destination_id'])) {
      $qb->where('h.destination_id = ?', $filters['destination_id']);
    }
    
    FilterHelper::addSearchFilter($qb, ['h.name', 'c.name', 'p.name', 'h.description']);
    
    $selectClause = "h.*, CAST(h.ad AS INT) as ad, c.name as city_name, p.name as province_name, p.region,
      (SELECT TOP 1 image_url FROM entity_images WHERE entity_type = 'hotel' AND entity_id = h.id ORDER BY display_order ASC, id ASC) as image_url";
    $fromClause = "hotels h";
    
    $promotedHotels = PromotionHelper::getPromotedItems($this->pdo, $qb, 'h', $selectClause, $fromClause, 2);
    $promotedIds = PromotionHelper::getPromotedIds($promotedHotels);
    
    $regularQb = clone $qb;
    PromotionHelper::excludePromoted($regularQb, 'h', $promotedIds);
    $regularQb->orderBy("h.price_per_night ASC, h.id ASC");
    $regularQb->paginate($page, $limit);
    
    try {
      $sql = $regularQb->buildSelect($selectClause, $fromClause);
      $stmt = $this->pdo->prepare($sql);
      $stmt->execute($regularQb->getParams());
      $hotels = $stmt->fetchAll();
      
      foreach ($promotedHotels as &$hotel) {
        if (isset($hotel['discount_percent']) && $hotel['discount_percent'] > 0) {
          $hotel['discounted_price'] = round($hotel['price_per_night'] * (1 - $hotel['discount_percent'] / 100), 2);
        }
      }
      foreach ($hotels as &$hotel) {
        if (isset($hotel['discount_percent']) && $hotel['discount_percent'] > 0) {
          $hotel['discounted_price'] = round($hotel['price_per_night'] * (1 - $hotel['discount_percent'] / 100), 2);
        }
      }
      
      return [
        'page' => $page,
        'limit' => $limit,
        'promoted' => $promotedHotels,
        'results' => $hotels
      ];
    } catch (PDOException $e) {
      ErrorHandler::logException($e, [
        'sql' => $sql ?? null,
        'params' => $regularQb->getParams() ?? null
      ]);
      throw $e;
    }
  }
  
  public function create(array $data, int $userId): array {
    $name = trim($data['name'] ?? '');
    $destination_id = isset($data['destination_id']) ? (int)$data['destination_id'] : null;
    $city_id = isset($data['city_id']) ? (int)$data['city_id'] : null;
    $province_id = isset($data['province_id']) ? (int)$data['province_id'] : null;
    $price_per_night = isset($data['price_per_night']) ? (float)$data['price_per_night'] : null;
    $description = trim($data['description'] ?? '');
    $images = $data['images'] ?? [];
    
    if (!$name || $city_id === null || $province_id === null || $price_per_night === null) {
      throw new InvalidArgumentException('Missing required fields: name, city_id, province_id, price_per_night');
    }
    
    if ($price_per_night < 0) {
      throw new InvalidArgumentException('Price must be positive');
    }
    
    $createdBy = ($data['user_role'] ?? '') === 'admin' && isset($data['created_by']) ? (int)$data['created_by'] : $userId;
    
    $stmt = $this->pdo->prepare('INSERT INTO hotels (name, destination_id, city_id, province_id, price_per_night, description, created_by) VALUES (?, ?, ?, ?, ?, ?, ?)');
    $stmt->execute([$name, $destination_id, $city_id, $province_id, $price_per_night, $description, $createdBy]);
    $hotelId = (int)$this->pdo->lastInsertId();
    
    if (!empty($images) && is_array($images)) {
      $normalizedImages = array_map(function($url) {
        return trim($url);
      }, array_filter($images));
      ImageHelper::saveEntityImages($this->pdo, 'hotel', $hotelId, $normalizedImages);
      ImageHelper::cleanupTempFiles(24);
    }
    
    return $this->getById($hotelId);
  }
  
  public function update(int $id, array $data, int $userId, string $userRole): array {
    $stmt = $this->pdo->prepare('SELECT created_by FROM hotels WHERE id = ?');
    $stmt->execute([$id]);
    $hotel = $stmt->fetch();
    if (!$hotel) {
      throw new RuntimeException('Hotel not found', 404);
    }
    
    if ($userRole !== 'admin' && $hotel['created_by'] != $userId) {
      throw new RuntimeException('Forbidden', 403);
    }
    
    $updates = [];
    $params = [];
    
    if (isset($data['name'])) {
      $updates[] = 'name = ?';
      $params[] = trim($data['name']);
    }
    if (isset($data['destination_id'])) {
      $updates[] = 'destination_id = ?';
      $params[] = (int)$data['destination_id'];
    }
    if (isset($data['city_id'])) {
      $updates[] = 'city_id = ?';
      $params[] = (int)$data['city_id'];
    }
    if (isset($data['province_id'])) {
      $updates[] = 'province_id = ?';
      $params[] = (int)$data['province_id'];
    }
    if (isset($data['price_per_night'])) {
      $updates[] = 'price_per_night = ?';
      $params[] = (float)$data['price_per_night'];
    }
    if (isset($data['description'])) {
      $updates[] = 'description = ?';
      $params[] = trim($data['description']);
    }
    
    $images = $data['images'] ?? null;
    
    if (empty($updates) && $images === null) {
      throw new InvalidArgumentException('No fields to update');
    }
    
    if (!empty($updates)) {
      $updates[] = 'updated_at = SYSUTCDATETIME()';
      $params[] = $id;
      $sql = 'UPDATE hotels SET ' . implode(', ', $updates) . ' WHERE id = ?';
      $stmt = $this->pdo->prepare($sql);
      $stmt->execute($params);
    }
    
    if ($images !== null && is_array($images)) {
      $normalizedImages = array_map(function($url) {
        return trim($url);
      }, array_filter($images));
      ImageHelper::saveEntityImages($this->pdo, 'hotel', $id, $normalizedImages);
    }
    
    return $this->getById($id);
  }
  
  public function delete(int $id, int $userId, string $userRole): void {
    $stmt = $this->pdo->prepare('SELECT created_by FROM hotels WHERE id = ?');
    $stmt->execute([$id]);
    $hotel = $stmt->fetch();
    if (!$hotel) {
      throw new RuntimeException('Hotel not found', 404);
    }
    
    if ($userRole !== 'admin' && $hotel['created_by'] != $userId) {
      throw new RuntimeException('Forbidden', 403);
    }
    
    $stmt = $this->pdo->prepare('UPDATE hotels SET deleted_at = SYSUTCDATETIME() WHERE id = ?');
    $stmt->execute([$id]);
  }
}

