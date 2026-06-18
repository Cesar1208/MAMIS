<?php
// backend/auth.php

// Cabeceras estrictas de seguridad de origen cruzado para Render
header("Access-Control-Allow-Origin: https://mamis.onrender.com");
header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept, Authorization");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Credentials: true");
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit(0);
}

// Inclusión obligatoria de la conexión y librerías de mensajería
require_once 'config.php';
require 'phpmailer/Exception.php';
require 'phpmailer/PHPMailer.php';
require 'phpmailer/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$action = $_POST['action'] ?? $_GET['action'] ?? '';

// ===================================================
// FLUJO A: REGISTRO DE USUARIO NUEVO
// ===================================================
if ($action === 'register') {
    $email = $_POST['email'] ?? '';
    $pass = $_POST['password'] ?? '';
    
    if (!$email || !$pass) {
        echo json_encode(['status' => 'error', 'message' => 'Campos insuficientes en el formulario.']);
        exit;
    }
    
    // Cifrado simétrico de contraseña y token de verificación único
    $hash = password_hash($pass, PASSWORD_BCRYPT);
    $token = bin2hex(random_bytes(32)); 
    
    try {
        // Registro inicial con estatus 'inactive' tal como estipula la rúbrica
        $stmt = $pdo->prepare("INSERT INTO usuarios (email, password, token, status) VALUES (?, ?, ?, 'inactive')");
        $stmt->execute([$email, $hash, $token]);
        
        // Configuración e inicio del cliente SMTP de PHPMailer
        $mail = new PHPMailer(true);
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com'; 
        $mail->SMTPAuth   = true;
        $mail->Username   = 'TU_CORREO_DE_GMAIL@gmail.com'; // Coloca aquí tu correo institucional o personal
        $mail->Password   = 'TU_CONTRASEÑA_DE_APLICACION';  // Tu clave de 16 letras generada en Google
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;
        
        $mail->setFrom('TU_CORREO_DE_GMAIL@gmail.com', 'Sistema de Gestión de Vuelos');
        $mail->addAddress($email);
        
        // Generación del enlace dinámico apuntando hacia tu servidor en Clever Cloud
        // ⚠️ REEMPLAZA ESTA URL POR TU DOMINIO REAL EN CLEVER CLOUD
        $enlaceVerificacion = "https://tu-app-php.cleverapps.io/backend/auth.php?action=verify&token=" . $token;
        
        $mail->isHTML(true);
        $mail->Subject = 'Activa tu Cuenta - Sistema PWA Vuelos';
        $mail->Body    = "<h2>¡Bienvenido al sistema relacional core!</h2>
                          <p>Para completar el registro y validar tus accesos, pulsa el siguiente enlace:</p>
                          <p><a href='{$enlaceVerificacion}'>{$enlaceVerificacion}</a></p>";
        
        $mail->send();
        
        echo json_encode([
            'status' => 'success', 
            'message' => 'Usuario registrado de forma persistente. Revisa tu correo electrónico para validar la cuenta.'
        ]);
        
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => 'Error al despachar el correo electrónico: ' . $mail->ErrorInfo]);
    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => 'El correo electrónico ya existe en la base de datos o fallo interno: ' . $e->getMessage()]);
    }
}

// ===================================================
// FLUJO B: VERIFICACIÓN Y ACTIVACIÓN DEL ENLACE DE CORREO
// ===================================================
elseif ($action === 'verify') {
    $tokenRecibido = $_GET['token'] ?? '';
    
    if (!$tokenRecibido) {
        die("<h3>Error: Token de validación no recibido o expirado.</h3>");
    }
    
    // Comprobar la existencia del token en la base de datos
    $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE token = ?");
    $stmt->execute([$tokenRecibido]);
    $user = $stmt->fetch();
    
    if ($user) {
        // Cambiar estatus de la cuenta a 'active' de manera real
        $update = $pdo->prepare("UPDATE usuarios SET status = 'active', token = NULL WHERE id = ?");
        $update->execute([$user['id']]);
        
        echo "<div style='font-family:sans-serif; text-align:center; margin-top:50px;'>
                <h1 style='color:#4CAF50;'>¡Cuenta Validada Exitosamente!</h1>
                <p>Tu usuario ya se encuentra activo en MySQL Cloud. Regresa a la PWA para iniciar sesión.</p>
              </div>";
    } else {
        echo "<div style='font-family:sans-serif; text-align:center; margin-top:50px;'>
                <h1 style='color:#F44336;'>Error de Validación</h1>
                <p>El enlace ha expirado o el token de seguridad ya ha sido utilizado.</p>
              </div>";
    }
}

// ===================================================
// FLUJO C: INICIO DE SESIÓN COMPROBANDO ESTADO DE ACTIVACIÓN
// ===================================================
elseif ($action === 'login') {
    $email = $_POST['email'] ?? '';
    $pass = $_POST['password'] ?? '';
    
    $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    
    if ($user && password_verify($pass, $user['password'])) {
        // Bloquear el inicio de sesión si el usuario sigue en estado 'inactive'
        if ($user['status'] !== 'active') {
            echo json_encode([
                'status' => 'error', 
                'message' => 'Acceso denegado. Es necesario validar tu cuenta mediante el enlace de correo antes de ingresar.'
            ]);
            exit;
        }
        echo json_encode([
            'status' => 'success', 
            'message' => 'Autenticación exitosa. Cargando entorno de base de datos relacional...'
        ]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Credenciales inválidas. Comprueba tu correo o contraseña.']);
    }
}
?>
