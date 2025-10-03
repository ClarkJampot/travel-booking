<?php
// Copy this file to db.php and fill in your SQL Server credentials

const DB_SERVER = 'YOURPC\\SQLEXPRESS';
const DB_DATABASE = 'booking-system';
const DB_UID = 'your_sql_login';
const DB_PWD = 'your_password';

function db_pdo(): PDO {
  $dsn = 'sqlsrv:Server=' . DB_SERVER . ';Database=' . DB_DATABASE;
  $pdo = new PDO($dsn, DB_UID, DB_PWD, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
  ]);
  return $pdo;
}


