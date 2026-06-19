<?php
// backend/config.php

// Permitir el acceso explícito desde tu frontend en Render para eliminar el error amarillo de CORS
header("Access-Control-Allow-Origin: https://mamis.onrender.com");
header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept, Authorization");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Credentials: true");

// Responder de inmediato con un estado OK a las peticiones de pre-vuelo (OPTIONS) del navegador
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
    // Establece la conexión formal utilizando PDO con codificación UTF-8 para evitar problemas de acentos
    $pdo = new PDO("mysql:host=$host;dbname=$db;port=$port;charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, // Reportar errores como excepciones legibles
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC // Retornar las consultas como arreglos asociativos
    ]);
} catch (PDOException $e) {
    // Si la base de datos falla, se corta el flujo y se le avisa al frontend en formato JSON
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'Fallo crítico de conexión: ' . $e->getMessage()]);
    exit;
}
?>
