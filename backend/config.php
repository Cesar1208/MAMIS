<?php
// backend/config.php

// Cabeceras estrictas de comunicación asíncrona cloud
header("Access-Control-Allow-Origin: https://mamis.onrender.com");
header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept, Authorization");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS, PUT, DELETE");
header("Access-Control-Allow-Credentials: true");

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit(0);
}

// ⚠️ DEBES SUSTITUIR ESTOS 4 PARÁMETROS CON LAS CREDENCIALES EXACTAS DE TU PANEL DE CLEVER CLOUD
$host = 'bxai5nugdj0qtsguxlnm-mysql.services.clever-cloud.com'; 
$port = '3306';
$db   = 'bxai5nugdj0qtsguxlnm'; // Tu base de datos cloud
$user = 'tu_usuario_de_clever_cloud';  // NO uses 'root' en producción
$pass = 'tu_contraseña_de_clever_cloud'; 

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
    echo json_encode(['status' => 'error', 'message' => 'Fallo físico de enlace con MySQL: ' . $e->getMessage()]);
    exit;
}
?>
