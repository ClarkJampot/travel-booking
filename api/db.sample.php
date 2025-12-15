<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';

function db_pdo(): PDO {
  $dsn = 'sqlsrv:Server=' . DB_SERVER . ';Database=' . DB_DATABASE;
  $pdo = new PDO($dsn, DB_UID, DB_PWD, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
  ]);
  return $pdo;
}


