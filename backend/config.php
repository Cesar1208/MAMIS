<?php
// backend/config.php

// REGLA DE ORO: Autoriza de forma explícita a tu frontend de Render a pedir información.
// Esto destruye permanentemente el error amarillo de bloqueo de red (CORS).
header("Access-Control-Allow-Origin: https://mamis.onrender.com");
header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept, Authorization");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Credentials: true");

// Si el navegador hace una consulta de control previa (OPTIONS), le responde un OK directo.
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit(0);
}

// Parámetros de conexión de tu base de datos Clever Cloud (Vistos en tus capturas de pantalla)
$host = 'bxai5nugdj0qtsguxlnm-mysql.services.clever-cloud.com';
$port = '3306';
$db   = 'bxai5nugdj0qtsguxlnm';
$user = 'uuj9b8v8wbw9b2v';
$pass = 'XfF9N3pZ8vK7wQ2m'; 

try {
    // Intenta conectar al motor MySQL utilizando la extensión PDO con codificación segura UTF-8
    $pdo = new PDO("mysql:host=$host;dbname=$db;port=$port;charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, // Activa el reporte de errores en forma de excepciones
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC // Configura el retorno de datos como arreglos asociativos
    ]);
} catch (PDOException $e) {
    // Si la conexión falla, frena el sistema y le avisa al Frontend en formato JSON
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'Error de conexión: ' . $e->getMessage()]);
    exit;
}
?>
