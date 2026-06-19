<?php
// backend/config.php

// Permitir peticiones seguras únicamente desde tu PWA en Render
header("Access-Control-Allow-Origin: https://mamis.onrender.com");
header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept, Authorization");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS, PUT, DELETE");
header("Access-Control-Allow-Credentials: true");

// Manejo del preflight (Peticiones OPTIONS automáticas del navegador)
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit(0);
}

// Configuración de la Base de Datos en la nube (Clever Cloud)
$host = 'bxai5nugdj0qtsguxlnm-mysql.services.clever-cloud.com';
$port = '3306';
$db   = 'bxai5nugdj0qtsguxlnm';
$user = 'uuj9b8v8wbw9b2v'; 
$pass = 'XfF9N3pZ8vK7wQ2m'; // Configura aquí la contraseña real de tu base de datos cloud

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;port=$port;charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ]);
} catch (PDOException $e) {
    header('Content-Type: application/json');
    echo json_encode([
        'status' => 'error', 
        'message' => 'Fallo crítico de conexión al clúster: ' . $e->getMessage()
    ]);
    exit;
}
?>
