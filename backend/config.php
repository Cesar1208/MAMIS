<?php
// backend/config.php

// Permitir peticiones seguras desde tu interfaz de Render para eliminar el error de CORS
header("Access-Control-Allow-Origin: https://mamis.onrender.com");
header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept, Authorization");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Credentials: true");

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit(0);
}

// Credenciales de tu base de datos de Clever Cloud (Verificadas en tu captura)
$host = 'bxai5nugdj0qtsguxlnm-mysql.services.clever-cloud.com';
$port = '3306';
$db   = 'bxai5nugdj0qtsguxlnm';
$user = 'uuj9b8v8wbw9b2v';
$pass = 'XfF9N3pZ8vK7wQ2m'; 

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;port=$port;charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
} catch (PDOException $e) {
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'Error de conexión: ' . $e->getMessage()]);
    exit;
}
?>
