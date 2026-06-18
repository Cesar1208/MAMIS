<?php
// backend/config.php

// Permitir peticiones desde tu frontend local
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS, DELETE, PUT");

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit(0);
}

// Configuración para el entorno LOCAL de XAMPP
$host = '127.0.0.1';
$port = '3306';
$db   = 'bxai5nugdj0qtsguxlnm'; // El nombre de la base de datos que creaste en tu localhost
$user = 'root';                 // Usuario por defecto de XAMPP
$pass = '';                     // Contraseña vacía por defecto en XAMPP

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
    echo json_encode([
        'status' => 'error', 
        'message' => 'Error de conexión local: ' . $e->getMessage()
    ]);
    exit;
}
