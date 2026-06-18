<?php
// backend/config.php

// Permitir de forma explícita que tu sitio de Render se conecte sin bloqueos de CORS
header("Access-Control-Allow-Origin: https://mamis.onrender.com");
header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept, Authorization");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS, PUT, DELETE");
header("Access-Control-Allow-Credentials: true");

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit(0);
}

// ⚠️ DEBES REEMPLAZAR ESTOS DATOS CON LOS QUE TE DA CLEVER CLOUD EN SU PANEL
$host = 'bxai5nugdj0qtsguxlnm-mysql.services.clever-cloud.com'; // El host largo que te dé Clever Cloud
$port = '3306';
$db   = 'bxai5nugdj0qtsguxlnm'; // Nombre de tu BD
$user = 'tu_usuario_de_clever';  // El usuario que te asignó Clever Cloud (NO 'root')
$pass = 'tu_contraseña_de_clever'; // La contraseña larga que te dio Clever Cloud

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
    echo json_encode(['status' => 'error', 'message' => 'Fallo de conexión al cluster: ' . $e->getMessage()]);
    exit;
}
?>
