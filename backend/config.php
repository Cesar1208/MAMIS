<?php
// backend/config.php

// CREDENCIALES REALES EXTRAÍDAS DE TU PANEL EN LA CAPTURA image_2bf6e7.png
$host = 'bxai5nugdj0qtsguxlnm-mysql.services.clever-cloud.com'; 
$db   = 'bxai5nugdj0qtsguxlnm'; 
$user = 'ut0dtjyxsh15rnav'; 
$pass = 'ttTXlFk0wDtAfs7ByC5'; 
$port = '3306';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;port=$port;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
     $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
     echo json_encode(['status' => 'error', 'message' => 'Fallo crítico de conexión: ' . $e->getMessage()]);
     exit;
}
