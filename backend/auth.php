<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit(0);
}

header('Content-Type: application/json');
require_once 'config.php';

$action = $_POST['action'] ?? $_GET['action'] ?? '';

// REGISTRO DE USUARIOS
if ($action === 'register') {
    $email = $_POST['email'] ?? '';
    $pass = $_POST['password'] ?? '';
    
    if (!$email || !$pass) {
        echo json_encode(['status' => 'error', 'message' => 'Campos insuficientes.']);
        exit;
    }
    
    $hash = password_hash($pass, PASSWORD_BCRYPT);
    
    try {
        // CORRECCIÓN: Apuntar a la tabla 'usuarios' que creaste en phpMyAdmin
        $stmt = $pdo->prepare("INSERT INTO usuarios (email, password, status) VALUES (?, ?, 'active')");
        $stmt->execute([$email, $hash]);
        
        echo json_encode([
            'status' => 'success', 
            'message' => "Usuario registrado con éxito en la base de datos local."
        ]);
    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => 'El correo ya existe o hubo un error: ' . $e->getMessage()]);
    }
} 

// INICIO DE SESIÓN
elseif ($action === 'login') {
    $email = $_POST['email'] ?? '';
    $pass = $_POST['password'] ?? '';
    
    // CORRECCIÓN: Apuntar a la tabla 'usuarios'
    $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    
    if ($user && password_verify($pass, $user['password'])) {
        echo json_encode(['status' => 'success', 'message' => 'Sesión autorizada. Bienvenido al sistema core.']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Credenciales inválidas.']);
    }
}
?>
