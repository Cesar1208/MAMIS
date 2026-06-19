<?php
// backend/config.php

// MODIFICACIÓN DE CORS: Permitir acceso general a todas las solicitudes externas para romper el bloqueo del navegador
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept, Authorization");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");

// Responder inmediatamente con estado exitoso a la petición OPTIONS de pre-vuelo
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit(0);
}

// Credenciales de tu base de datos en Clever Cloud (Verificadas en tus capturas)
$host = 'bxai5nugdj0qtsguxlnm-mysql.services.clever-cloud.com';
$port = '3306';
$db   = 'bxai5nugdj0qtsguxlnm';
$user = 'uuj9b8v8wbw9b2v';
$pass = 'XfF9N3pZ8vK7wQ2m'; 

try {
    // Establece la conexión formal utilizando PDO con codificación UTF-8
    $pdo = new PDO("mysql:host=$host;dbname=$db;port=$port;charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
} catch (PDOException $e) {
    // Enviar error en JSON si falla la conexión a la base de datos
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'Fallo crítico de conexión: ' . $e->getMessage()]);
    exit;
}
?>
