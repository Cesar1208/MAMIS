<?php
// backend/auth.php

// Reutiliza la configuración de base de datos y cabeceras CORS
require_once 'config.php';
header('Content-Type: application/json');

$action = $_GET['action'] ?? '';
// URL base de tu backend en Clever Cloud para armar los enlaces dinámicos de simulación
$url_base = "https://app-f11f01f7-d577-43bd-b5a4-bc58a8917f37.cleverapps.io";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Captura y decodifica el cuerpo JSON asíncrono enviado por el frontend
    $json = file_get_contents('php://input');
    $datos = json_decode($json, true);
    
    $email = $datos['email'] ?? '';
    $password = $datos['password'] ?? '';
    
    // === REGISTRO DE USUARIOS ===
    if ($action === 'register') {
        if (empty($email) || empty($password)) {
            echo json_encode(['status' => 'error', 'message' => 'Campos obligatorios incompletos.']);
            exit;
        }

        // Validación de duplicados para cumplir con las restricciones de consistencia
        $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            echo json_encode(['status' => 'error', 'message' => 'Este correo electrónico ya está registrado.']);
            exit;
        }
        
        // Encriptación segura de la contraseña usando el estándar BCRYPT
        $passwordHash = password_hash($password, PASSWORD_BCRYPT);
        // Generación de un token único aleatorio para la activación por enlace
        $token = bin2hex(random_bytes(16));
        
        // Almacenamiento persistente en la tabla 'usuarios' con estado inicial 'inactive'
        $stmt = $pdo->prepare("INSERT INTO usuarios (email, password, token, status) VALUES (?, ?, ?, 'inactive')");
        if ($stmt->execute([$email, $passwordHash, $token])) {
            $enlaceVerificar = "$url_base/backend/auth.php?action=verify&token=$token";
            echo json_encode([
                'status' => 'success',
                'message' => '¡Usuario registrado! Se requiere validación de cuenta.',
                'link_simulado' => $enlaceVerificar // Se envía al frontend para simular el correo en la consola
            ]);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Error al guardar el registro en la base de datos.']);
        }
        exit;
    }
    
    // === INICIO DE SESIÓN ===
    if ($action === 'login') {
        if (empty($email) || empty($password)) {
            echo json_encode(['status' => 'error', 'message' => 'Por favor, llena todos los campos.']);
            exit;
        }

        $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE email = ?");
        $stmt->execute([$email]);
        $usuario = $stmt->fetch();
        
        // Verifica la contraseña contra el hash e inspecciona si la cuenta está activada
        if ($usuario && password_verify($password, $usuario['password'])) {
            if ($usuario['status'] !== 'active') {
                echo json_encode(['status' => 'error', 'message' => 'Tu cuenta no está activa. Usa el enlace de verificación primero.']);
                exit;
            }
            echo json_encode(['status' => 'success', 'message' => '¡Sesión iniciada correctamente!']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'El correo o la contraseña son incorrectos.']);
        }
        exit;
    }

    // === SOLICITUD DE RECUPERACIÓN DE CONTRASEÑA ===
    if ($action === 'recover_request') {
        if (empty($email)) {
            echo json_encode(['status' => 'error', 'message' => 'Escribe tu correo para recuperar.']);
            exit;
        }

        $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $token = bin2hex(random_bytes(16));
            $stmt = $pdo->prepare("UPDATE usuarios SET token = ? WHERE email = ?");
            $stmt->execute([$token, $email]);
            
            $enlaceRecuperar = "$url_base/backend/auth.php?action=reset_view&token=$token";
            echo json_encode([
                'status' => 'success',
                'message' => 'Enlace de recuperación generado con éxito.',
                'link_simulado' => $enlaceRecuperar
            ]);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Ese correo electrónico no existe en el sistema.']);
        }
        exit;
    }
}

// === ENLACES DIRECTOS GET (Interacción desde el navegador) ===

// Activación de la cuenta al hacer clic en el enlace simulado de la consola
if ($_SERVER['REQUEST_METHOD'] === 'GET' && $action === 'verify') {
    $token = $_GET['token'] ?? '';
    
    $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE token = ?");
    $stmt->execute([$token]);
    $usuario = $stmt->fetch();
    
    if ($usuario) {
        // Cambia el estado a 'active' y quita el token de un solo uso
        $update = $pdo->prepare("UPDATE usuarios SET status = 'active', token = NULL WHERE id = ?");
        $update->execute([$usuario['id']]);
        
        echo "
        <div style='font-family: sans-serif; text-align: center; margin-top: 80px;'>
            <h1 style='color: #e11d48;'>❤️ ¡Cuenta Activada con Éxito!</h1>
            <p style='font-size: 1.1rem;'>Tu usuario ha sido validado correctamente. Ya puedes iniciar sesión.</p>
            <br><br>
            <a href='https://mamis.onrender.com' style='background: #e11d48; color: white; padding: 12px 25px; text-decoration: none; font-weight: bold; border-radius: 8px;'>Ir a la Aplicación e Iniciar Sesión</a>
        </div>";
    } else {
        echo "<h2>El enlace de verificación expiró o ya fue utilizado.</h2>";
    }
    exit;
}

// Vista html del formulario para restablecer la contraseña
if ($_SERVER['REQUEST_METHOD'] === 'GET' && $action === 'reset_view') {
    $token = $_GET['token'] ?? '';
    echo "
    <div style='font-family: sans-serif; text-align: center; margin-top: 60px; max-width: 400px; margin-left: auto; margin-right: auto; padding: 20px; border: 1px solid #ddd; border-radius: 10px;'>
        <h2 style='color: #e11d48;'>Restablecer Contraseña</h2>
        <form action='auth.php?action=update_password' method='POST'>
            <input type='hidden' name='token' value='$token'>
            <input type='password' name='nueva_password' placeholder='Escribe tu nueva contraseña' required style='width: 100%; padding: 10px; margin-bottom: 15px; box-sizing: border-box;'>
            <button type='submit' style='background: #e11d48; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; width: 100%; font-weight: bold;'>Actualizar Contraseña</button>
        </form>
    </div>";
    exit;
}

// Procesamiento y guardado de la nueva contraseña enviada por formulario POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'update_password') {
    $token = $_POST['token'] ?? '';
    $nueva_password = $_POST['nueva_password'] ?? '';
    
    if (!empty($token) && !empty($nueva_password)) {
        $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE token = ?");
        $stmt->execute([$token]);
        $usuario = $stmt->fetch();
        
        if ($usuario) {
            $nuevoHash = password_hash($nueva_password, PASSWORD_BCRYPT);
            $update = $pdo->prepare("UPDATE usuarios SET password = ?, token = NULL WHERE id = ?");
            $update->execute([$nuevoHash, $usuario['id']]);
            echo "
            <div style='font-family: sans-serif; text-align: center; margin-top: 60px;'>
                <h2 style='color: green;'>✔️ ¡Contraseña actualizada exitosamente!</h2>
                <br>
                <a href='https://mamis.onrender.com' style='background: #e11d48; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Volver a CupidCore</a>
            </div>";
        } else {
            echo "<h2>Token no válido. Inténtalo de nuevo.</h2>";
        }
    }
    exit;
}
?>
