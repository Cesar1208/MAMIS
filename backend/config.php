<?php
// backend/config.php

// Inyección automática o manual del cluster de bases de datos de Clever Cloud
$host = getenv('MYSQL_ADDON_HOST') ?: 'bxai5nugdj0qtsguxlnm-mysql.services.clever-cloud.com';
$db   = getenv('MYSQL_ADDON_DB')   ?: 'bxai5nugdj0qtsguxlnm';
$user = getenv('MYSQL_ADDON_USER') ?: 'ut0dtjyxsh15rnav';
$pass = getenv('MYSQL_ADDON_PASSWORD') ?: 'ttTTxlFk0wDtAfs7ByC5';
$port = getenv('MYSQL_ADDON_PORT') ?: '3306';

$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset;port=$port";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
     $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
     throw new \PDOException($e->getMessage(), (int)$e->getCode());
}
?>
