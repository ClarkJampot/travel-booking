<?php
declare(strict_types=1);

class BaseService {
  protected PDO $pdo;
  
  public function __construct(PDO $pdo) {
    $this->pdo = $pdo;
  }
}

