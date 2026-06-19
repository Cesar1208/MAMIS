<?php
// backend/auth.php
header("Access-Control-Allow-Origin: https://mamis.onrender.com");
header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept, Authorization");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Credentials: true");
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit(0);
}

require_once 'config.php';
require 'phpmailer/Exception.php';
require 'phpmailer/PHPMailer.php';
require 'phpmailer/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$action = $_POST['action'] ?? $_GET['action'] ?? '';

if ($action === 'register') {
    $email = $_POST['email'] ?? '';
    $pass = $_POST['password'] ?? '';
    
    if (!$email || !$pass) {
        echo json_encode(['status' => 'error', 'message' => 'Parámetros del formulario incompletos.']);
        exit;
    }
    
    $hash = password_hash($pass, PASSWORD_BCRYPT);
    $token = bin2hex(random_bytes(32)); 
    
    try {
        $stmt = $pdo->prepare("INSERT INTO usuarios (email, password, token, status) VALUES (?, ?, ?, 'inactive')");
        $stmt->execute([$email, $hash, $token]);
        
        $mail = new PHPMailer(true);
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com'; 
        $mail->SMTPAuth   = true;
        $mail->Username   = 'TU_CORREO_DE_GMAIL@gmail.com'; // Pon tu correo real aquí
        $mail->Password   = 'TU_CLAVE_DE_APLICACION';      // Token de 16 letras de Google
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;
        
        $mail->setFrom('TU_CORREO_DE_GMAIL@gmail.com', 'AeroCore Cloud');
        $mail->addAddress($email);
        
        // Enlace de producción apuntando a tu backend de Clever Cloud
        // ⚠️ REEMPLAZA CON LA URL QUE TE OTORGUE CLEVER CLOUD EN TU PROYECTO
        $enlaceVerificacion = "https://tu-app-php.cleverapps.io/backend/auth.php?action=verify&token=" . $token;
        
        $mail->isHTML(true);
        $mail->Subject = 'Verificacion de Cuenta - Sistema de Vuelos PWA';
        $mail->Body    = "<h2>¡Registro exitoso en AeroCore!</h2>
                          <p>Para activar tu cuenta y poder ingresar al sistema core, haz clic en el siguiente enlace:</p>
                          <p><a href='{$enlaceVerificacion}'>{$enlaceVerificacion}</a></p>";
        
        $mail->send();
        echo json_encode(['status' => 'success', 'message' => 'Usuario guardado. Se ha enviado un correo real de verificación.']);
        
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => 'Error al despachar correo: ' . $mail->ErrorInfo]);
    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => 'El correo electrónico ya se encuentra registrado.']);
    }
}

elseif ($action === 'verify') {
    $tokenRecibido = $_GET['token'] ?? '';
    if (!$tokenRecibido) { die("Token inválido."); }
    
    $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE token = ?");
    $stmt->execute([$tokenRecibido]);
    $user = $stmt->fetch();
    
    if ($user) {
        $update = $pdo->prepare("UPDATE usuarios SET status = 'active', token = NULL WHERE id = ?");
        $update->execute([$user['id']]);
        echo "<h1>¡Cuenta activada!</h1><p>Tu usuario ha sido verificado en MySQL de Clever Cloud. Regresa a la app e inicia sesión.</p>";
    } else {
        echo "<h1>Error de verificación</h1><p>El enlace ha expirado o ya fue utilizado.</p>";
    }
}

elseif ($action === 'login') {
    $email = $_POST['email'] ?? '';
    $pass = $_POST['password'] ?? '';
    
    $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    
    if ($user && password_verify($pass, $user['password'])) {
        if ($user['status'] !== 'active') {
            echo json_encode(['status' => 'error', 'message' => 'Cuenta inactiva. Verifica tu correo electrónico primero.']);
            exit;
        }
        echo json_encode(['status' => 'success', 'message' => 'Sesión autorizada en el clúster cloud.']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Credenciales incorrectas de acceso.']);
    }
}
?>
