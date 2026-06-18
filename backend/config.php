<?php
// ==========================================
// CONFIGURACIÓN DE CABECERAS CORE (CORS)
// ==========================================
// Permite que tu PWA en Render se comunique de forma segura con Clever Cloud
header("Access-Control-Allow-Origin: https://mamis.onrender.com");
header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept, Authorization");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS, PUT, DELETE");
header("Access-Control-Allow-Credentials: true");

// Responder con estatus 200 de inmediato a las peticiones de pre-vuelo (OPTIONS)
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit(0);
}

// ==========================================
// PARÁMETROS DE CONEXIÓN CLOUD MYSQL
// ==========================================
// ⚠️ REEMPLAZA ESTOS DATOS CON LOS VALORES EXACTOS DE TU PANEL DE CLEVER CLOUD
$host = 'bxai5nugdj0qtsguxlnm-mysql.services.clever-cloud.com'; 
$port = '3306';
$db   = 'bxai5nugdj0qtsguxlnm'; // Nombre de tu base de datos cloud
$user = 'tu_usuario_de_clever';  // El usuario asignado por Clever Cloud (No uses 'root')
$pass = 'tu_password_de_clever'; // La clave alfanumérica provista por Clever Cloud

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
        'message' => 'Fallo crítico de conexión al cluster cloud: ' . $e->getMessage()
    ]);
    exit;
}
?>
