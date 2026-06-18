<?php
// backend/auth.php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept, Authorization");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit(0);
}

require_once 'config.php';

// Carga de librerías de PHPMailer para el envío real
require 'phpmailer/Exception.php';
require 'phpmailer/PHPMailer.php';
require 'phpmailer/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$action = $_POST['action'] ?? $_GET['action'] ?? '';

// ===================================================
// REGISTRO DE USUARIOS + TOKEN DE VERIFICACIÓN
// ===================================================
if ($action === 'register') {
    $email = $_POST['email'] ?? '';
    $pass = $_POST['password'] ?? '';
    
    if (!$email || !$pass) {
        echo json_encode(['status' => 'error', 'message' => 'Campos insuficientes.']);
        exit;
    }
    
    $hash = password_hash($pass, PASSWORD_BCRYPT);
    $token = bin2hex(random_bytes(32)); // Token real
    
    try {
        // Se registra con estatus 'inactive' hasta que valide en su bandeja
        $stmt = $pdo->prepare("INSERT INTO usuarios (email, password, token, status) VALUES (?, ?, ?, 'inactive')");
        $stmt->execute([$email, $hash, $token]);
        
        // Configuración de PHPMailer mediante SMTP seguro
        $mail = new PHPMailer(true);
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com'; 
        $mail->SMTPAuth   = true;
        $mail->Username   = 'TU_CORREO_DE_PROYECTOS@gmail.com'; // Coloca tu correo aquí
        $mail->Password   = 'TU_CONTRASEÑA_DE_APLICACION';    // Tu token de 16 letras de Google
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;
        
        $mail->setFrom('TU_CORREO_DE_PROYECTOS@gmail.com', 'Sistema Vuelos');
        $mail->addAddress($email);
        
        // Enlace real de producción que apunta a Clever Cloud
        $enlaceVerificacion = "https://tu-app-php.cleverapps.io/backend/auth.php?action=verify&token=" . $token;
        
        $mail->isHTML(true);
        $mail->Subject = 'Verifica tu cuenta - Sistema Relacional';
        $mail->Body    = "<h3>¡Registro Completo!</h3>
                          <p>Para activar tu cuenta y poder ingresar, haz clic en el siguiente enlace:</p>
                          <p><a href='{$enlaceVerificacion}'>{$enlaceVerificacion}</a></p>";
        
        $mail->send();
        
        echo json_encode([
            'status' => 'success', 
            'message' => 'Usuario registrado. Se ha enviado un correo real de verificación para activar tu cuenta.'
        ]);
        
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => 'Error al despachar correo: ' . $mail->ErrorInfo]);
    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => 'El correo ya existe en MySQL o hay un error: ' . $e->getMessage()]);
    }
}

// ===================================================
// ENLACE DE VALIDACIÓN (ACTIVACIÓN DE CUENTA)
// ===================================================
elseif ($action === 'verify') {
    $tokenRecibido = $_GET['token'] ?? '';
    
    if (!$tokenRecibido) {
        die("Token no válido.");
    }
    
    $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE token = ?");
    $stmt->execute([tokenRecibido]);
    $user = $stmt->fetch();
    
    if ($user) {
        // Activamos la cuenta y removemos el token de un solo uso
        $update = $pdo->prepare("UPDATE usuarios SET status = 'active', token = NULL WHERE id = ?");
        $update->execute([$user['id']]);
        
        echo "<h1>¡Cuenta verificada exitosamente!</h1><p>Ya puedes volver a la aplicación e iniciar sesión sin restricciones.</p>";
    } else {
        echo "<h1>Enlace expirado</h1><p>El token de verificación ya no es válido.</p>";
    }
}

// ===================================================
// INICIO DE SESIÓN COMPROBANDO ESTATUS
// ===================================================
elseif ($action === 'login') {
    $email = $_POST['email'] ?? '';
    $pass = $_POST['password'] ?? '';
    
    $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    
    if ($user && password_verify($pass, $user['password'])) {
        if ($user['status'] !== 'active') {
            echo json_encode(['status' => 'error', 'message' => 'Esta cuenta no ha sido validada. Por favor, revisa tu correo electrónico.']);
            exit;
        }
        echo json_encode(['status' => 'success', 'message' => 'Sesión autorizada. Bienvenido al sistema core.']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Credenciales inválidas.']);
    }
}
?>
