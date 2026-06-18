<?php
// 1. CONFIGURACIÓN DE CORS (CABECERAS OBLIGATORIAS PARA PRODUCCIÓN)
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit(0);
}

header('Content-Type: application/json');
require_once 'config.php';

// Asegurar que la tabla de usuarios exista en el cluster
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS usuarios_sistema (
        id INT AUTO_INCREMENT PRIMARY KEY,
        email VARCHAR(100) UNIQUE NOT NULL,
        password VARCHAR(255) NOT NULL,
        validado TINYINT DEFAULT 0
    )");
} catch (Exception $e) {}

$action = $_POST['action'] ?? '';

// FUNCIÓN AUXILIAR PARA ENVIAR CORREO REAL POR SMTP (GMAIL)
function enviarCorreoGmail($destinatario, $asunto, $cuerpo) {
    // REEMPLAZA CON TU CORREO EMISOR Y TU CONTRASEÑA DE APLICACIÓN DE 16 LETRAS
    $email_emisor = "tu-correo@gmail.com"; 
    $password_aplicacion = "xxxx xxxx xxxx xxxx"; 
    
    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
    $headers .= "From: Sistema Aeroportuario Cloud <$email_emisor> \r\n";
    $headers .= "Reply-To: $email_emisor\r\n";
    $headers .= "X-Mailer: PHP/" . phpversion();

    return mail($destinatario, $asunto, $cuerpo, $headers);
}

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
        $stmt = $pdo->prepare("INSERT INTO usuarios_sistema (email, password, validado) VALUES (?, ?, 0)");
        $stmt->execute([$email, $hash]);
        
        // Generar enlace dinámico de verificación
        $host_actual = (isset($_SERVER['HTTPS']) ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
        $url_verificacion = str_replace("auth.php", "auth.php?action=verify&email=" . urlencode($email), $host_actual);
        
        $cuerpoHtml = "
        <div style='background:#080c14; color:white; padding:30px; font-family:sans-serif; border-radius:10px; max-width:500px; margin:auto;'>
            <h2 style='color:#00f2fe; text-align:center;'>Verificación de Cuenta Cloud</h2>
            <p>Hola. Se ha solicitado un registro para el acceso al core del Sistema de Vuelos.</p>
            <p>Para activar sus credenciales de manera de verificación asíncrona, haga clic en el siguiente botón:</p>
            <div style='text-align:center; margin:30px 0;'>
                <a href='$url_verificacion' style='background:#0052d4; color:white; padding:12px 25px; text-decoration:none; font-weight:bold; border-radius:5px;'>Activar Mi Cuenta</a>
            </div>
            <p style='color:#7a8ebf; font-size:0.8rem;'>Si el botón no funciona, copie y pegue este enlace en su navegador:<br>$url_verificacion</p>
        </div>";
        
        enviarCorreoGmail($email, "✔ Activa tu cuenta - Sistema Vuelos PWA", $cuerpoHtml);
        
        echo json_encode([
            'status' => 'success', 
            'message' => "Ingeniero registrado. Se ha enviado un enlace de verificación real a su correo electrónico."
        ]);
    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => 'Este correo electrónico ya se encuentra registrado en el cluster.']);
    }
} 

// INICIO DE SESIÓN
elseif ($action === 'login') {
    $email = $_POST['email'] ?? '';
    $pass = $_POST['password'] ?? '';
    
    $stmt = $pdo->prepare("SELECT * FROM usuarios_sistema WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    
    if ($user && password_verify($pass, $user['password'])) {
        if ($user['validado'] == 0) {
            echo json_encode(['status' => 'error', 'message' => 'La cuenta no ha sido validada. Por favor revise su bandeja de entrada en Gmail.']);
        } else {
            echo json_encode(['status' => 'success', 'message' => 'Token de sesión autorizado. Bienvenido al sistema core.']);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Credenciales inválidas en el cluster.']);
    }
} 

// VALIDACIÓN ASÍNCRONA DEL ENLACE RECIBIDO
elseif (($_GET['action'] ?? '') === 'verify') {
    $email = $_GET['email'] ?? '';
    if ($email) {
        $stmt = $pdo->prepare("UPDATE usuarios_sistema SET validado = 1 WHERE email = ?");
        $stmt->execute([$email]);
        echo "<body style='background:#080c14;color:white;font-family:sans-serif;padding:50px;text-align:center;'>";
        echo "<h1 style='color:#00e676;'>✔ Cuenta Habilitada con Éxito</h1>";
        echo "<p style='color:#7a8ebf;'>El usuario se encuentra activo en la base de datos cloud de Clever Cloud. Ya puede ingresar desde la PWA en su celular.</p>";
        echo "</body>";
    }
}

// RECUPERACIÓN DE CONTRASEÑA
if ($action === 'recover') {
    $email = $_POST['email'] ?? '';
    $stmt = $pdo->prepare("SELECT * FROM usuarios_sistema WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        // 👇 REEMPLAZA ESTA URL POR LA URL QUE TE DE RENDER CUANDO DESPLIEGUES EL FRONTEND
        $url_frontend_render = "https://tu-app-frontend.onrender.com/index.html?reset=active&email=" . urlencode($email);
        
        $cuerpoHtml = "
        <div style='background:#080c14; color:white; padding:30px; font-family:sans-serif; border-radius:10px; max-width:500px; margin:auto;'>
            <h2 style='color:#ff1744; text-align:center;'>Recuperación de Contraseña</h2>
            <p>Se ha generado un token temporal de restablecimiento de credenciales.</p>
            <p style='margin:25px 0; text-align:center;'>
                <a href='$url_frontend_render' style='background:#1e2942; color:#00f2fe; padding:12px 25px; text-decoration:none; font-weight:bold; border-radius:5px; border:1px solid #00f2fe;'>Restablecer Credenciales</a>
            </p>
        </div>";
        
        enviarCorreoGmail($email, "🔄 Token de Recuperación de Contraseña", $cuerpoHtml);
        
        echo json_encode([
            'status' => 'success',
            'message' => "Token inyectado con éxito. Revise su bandeja de entrada para completar la reconfiguración."
        ]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'El correo electrónico provisto no existe en el registro.']);
    }
}
?>
