<?php
// backend/auth.php
require_once 'config.php';

header('Content-Type: application/json');

$action = $_GET['action'] ?? '';

// URL base de tu backend para armar los enlaces de verificación automáticos
$url_backend = "https://app-f11f01f7-d577-43bd-b5a4-bc58a8917f37.cleverapps.io/backend";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    // --- 1. ACCIÓN: REGISTRO DE USUARIO ---
    if ($action === 'register') {
        $email = filter_var($input['email'] ?? '', FILTER_VALIDATE_EMAIL);
        $password = $input['password'] ?? '';
        
        if (!$email || strlen($password) < 4) {
            echo json_encode(['status' => 'error', 'message' => 'Datos de registro inválidos.']);
            exit;
        }
        
        // Verificar si el usuario ya existe en la base de datos
        $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            echo json_encode(['status' => 'error', 'message' => 'El correo electrónico ya está registrado.']);
            exit;
        }
        
        $passwordHash = password_hash($password, PASSWORD_BCRYPT);
        $token = bin2hex(random_bytes(16)); // Token único de activación
        
        // Insertar usuario con estado 'inactive'
        $stmt = $pdo->prepare("INSERT INTO usuarios (email, password, token, status) VALUES (?, ?, ?, 'inactive')");
        if ($stmt->execute([$email, $passwordHash, $token])) {
            
            // Simulación / Configuración del cuerpo del correo interactivo y estético
            $enlaceVerificacion = $url_backend . "/auth.php?action=verify&token=" . $token;
            
            echo json_encode([
                'status' => 'success',
                'message' => 'Usuario creado con éxito en segundo plano.',
                'email_simulated' => true,
                'link' => $enlaceVerificacion
            ]);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'No se pudo guardar el registro.']);
        }
        exit;
    }
    
    // --- 2. ACCIÓN: INICIO DE SESIÓN ---
    if ($action === 'login') {
        $email = filter_var($input['email'] ?? '', FILTER_VALIDATE_EMAIL);
        $password = $input['password'] ?? '';
        
        $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        
        if (!$user || !password_verify($password, $user['password'])) {
            echo json_encode(['status' => 'error', 'message' => 'Credenciales incorrectas.']);
            exit;
        }
        
        // Validar si la cuenta está verificada
        if ($user['status'] !== 'active') {
            echo json_encode(['status' => 'error', 'message' => 'Cuenta pendiente. Debes validar tu cuenta por correo primero.']);
            exit;
        }
        
        echo json_encode([
            'status' => 'success',
            'message' => 'Sesión iniciada correctamente.',
            'user' => ['id' => $user['id'], 'email' => $user['email']]
        ]);
        exit;
    }
}

// --- 3. ACCIÓN: VERIFICACIÓN DE TOKEN (GET) ---
if ($_SERVER['REQUEST_METHOD'] === 'GET' && $action === 'verify') {
    $token = $_GET['token'] ?? '';
    
    if (empty($token)) {
        die("Token no proporcionado o inválido.");
    }
    
    // Buscar usuario con dicho token
    $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE token = ?");
    $stmt->execute([$token]);
    $user = $stmt->fetch();
    
    if ($user) {
        // Cambiar estado a activo y limpiar el token
        $update = $pdo->prepare("UPDATE usuarios SET status = 'active', token = NULL WHERE id = ?");
        $update->execute([$user['id']]);
        
        // Respuesta HTML estética de confirmación exitosa
        echo "
        <div style='font-family: sans-serif; text-align: center; margin-top: 50px; padding: 20px;'>
            <h1 style='color: #10b981;'>✓ ¡Cuenta Activada con Éxito!</h1>
            <p style='font-size: 18px; color: #374151;'>Tu cuenta de CupidCore ha sido verificada correctamente.</p>
            <p>Ya puedes regresar a la aplicación e iniciar sesión sin problemas.</p>
            <br>
            <a href='https://mamis.onrender.com' style='background-color: #3b82f6; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; font-weight: bold;'>Ir a la PWA en Vivo</a>
        </div>
        ";
    } else {
        echo "<h2>El enlace de verificación ha expirado o es inválido.</h2>";
    }
    exit;
}
?>
