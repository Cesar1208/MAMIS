<?php
// backend/config.php

// Evita por completo el bloqueo de CORS al conectar desde el Frontend
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept, Authorization");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS, PUT, DELETE");

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit(0);
}

// Credenciales reales del Cluster de MySQL en Clever Cloud
$host = 'bxai5nugdj0qtsguxlnm-mysql.services.clever-cloud.com'; 
$port = '3306';
$db   = 'bxai5nugdj0qtsguxlnm'; 
$user = 'root';                 // Cambia 'root' por tu usuario real de Clever Cloud si es diferente
$pass = '';                     // Cambia esto por la contraseña de tu base de datos de Clever Cloud

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
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'Error de conexión al cluster: ' . $e->getMessage()]);
    exit;
}
?>
