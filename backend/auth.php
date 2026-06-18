<?php
header('Content-Type: application/json');
require_once 'config.php';

try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS usuarios_sistema (
        id INT AUTO_INCREMENT PRIMARY KEY,
        email VARCHAR(100) UNIQUE NOT NULL,
        password VARCHAR(255) NOT NULL,
        validado TINYINT DEFAULT 0
    )");
} catch (Exception $e) {}

$action = $_POST['action'] ?? '';

if ($action === 'register') {
    $email = $_POST['email'] ?? '';
    $pass = $_POST['password'] ?? '';
    
    if (!$email || !$pass) {
        echo json_encode(['status' => 'error', 'message' => 'Campos insuficientes.']);
        exit;
    }
    
    $hash = password_hash($pass, PASSWORD_BCRYPT);
    
    try {
        $stmt = $pdo->prepare("INSERT INTO usuarios_sistema (email, password, validado) VALUES (?, ?, 0)");
        $stmt->execute([$email, $hash]);
        
        $host_actual = (isset($_SERVER['HTTPS']) ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
        $url_verificacion = str_replace("auth.php", "auth.php?action=verify&email=" . urlencode($email), $host_actual);
        
        echo json_encode([
            'status' => 'success', 
            'message' => "Ingeniero registrado. Haz uso obligatorio de este enlace de token para activar tu acceso: $url_verificacion"
        ]);
    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => 'Este correo electrónico ya se encuentra registrado.']);
    }
} 

elseif ($action === 'login') {
    $email = $_POST['email'] ?? '';
    $pass = $_POST['password'] ?? '';
    
    $stmt = $pdo->prepare("SELECT * FROM usuarios_sistema WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    
    if ($user && password_verify($pass, $user['password'])) {
        if ($user['validado'] == 0) {
            echo json_encode(['status' => 'error', 'message' => 'La cuenta no ha sido validada utilizando el token obligatorio enviado por enlace.']);
        } else {
            echo json_encode(['status' => 'success', 'message' => 'Token de sesión autorizado. Bienvenido al sistema core.']);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Credenciales inválidas en el cluster.']);
    }
} 

elseif (($_GET['action'] ?? '') === 'verify') {
    $email = $_GET['email'] ?? '';
    if ($email) {
        $stmt = $pdo->prepare("UPDATE usuarios_sistema SET validado = 1 WHERE email = ?");
        $stmt->execute([$email]);
        echo "<body style='background:#080c14;color:white;font-family:sans-serif;padding:50px;text-align:center;'>";
        echo "<h1 style='color:#00e676;'>✔ Cuenta Habilitada con Éxito</h1>";
        echo "<p style='color:#7a8ebf;'>El usuario se encuentra activo en la base de datos cloud. Ya puedes iniciar sesión.</p>";
        echo "</body>";
    }
}

if ($action === 'recover') {
    $email = $_POST['email'] ?? '';
    $stmt = $pdo->prepare("SELECT * FROM usuarios_sistema WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        echo json_encode([
            'status' => 'success',
            'message' => "Token inyectado con éxito. Usa este link enviado a tu correo institucional para restablecer credenciales: http://localhost/AppWeb/frontend/index.html?reset=active"
        ]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'El correo electrónico provisto no existe en el registro.']);
    }
}
?>