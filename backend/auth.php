<?php
// backend/auth.php
require_once 'config.php';
header('Content-Type: application/json');

$action = $_GET['action'] ?? '';
// URL base de tu backend obtenido de tus capturas de Clever Cloud
$url_base = "https://app-f11f01f7-d577-43bd-b5a4-bc58a8917f37.cleverapps.io";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $json = file_get_contents('php://input');
    $datos = json_decode($json, true);
    
    $email = $datos['email'] ?? '';
    $password = $datos['password'] ?? '';
    
    // --- 1. ACCIÓN: REGISTRO ---
    if ($action === 'register') {
        if (empty($email) || empty($password)) {
            echo json_encode(['status' => 'error', 'message' => 'Campos incompletos.']);
            exit;
        }

        $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            echo json_encode(['status' => 'error', 'message' => 'El correo ya existe en esta app de citas.']);
            exit;
        }
        
        $passwordHash = password_hash($password, PASSWORD_BCRYPT);
        $token = bin2hex(random_bytes(16));
        
        $stmt = $pdo->prepare("INSERT INTO usuarios (email, password, token, status) VALUES (?, ?, ?, 'inactive')");
        if ($stmt->execute([$email, $passwordHash, $token])) {
            $enlaceVerificar = "$url_base/auth.php?action=verify&token=$token";
            echo json_encode([
                'status' => 'success',
                'message' => '¡Registro exitoso! Te hemos enviado un correo de bienvenida.',
                'link_simulado' => $enlaceVerificar
            ]);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Error al guardar el usuario.']);
        }
        exit;
    }
    
    // --- 2. ACCIÓN: INICIO DE SESIÓN ---
    if ($action === 'login') {
        if (empty($email) || empty($password)) {
            echo json_encode(['status' => 'error', 'message' => 'Campos incompletos.']);
            exit;
        }

        $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE email = ?");
        $stmt->execute([$email]);
        $usuario = $stmt->fetch();
        
        if ($usuario && password_verify($password, $usuario['password'])) {
            if ($usuario['status'] !== 'active') {
                echo json_encode(['status' => 'error', 'message' => 'Por favor, valida tu cuenta primero usando el enlace enviado a tu correo electrónico.']);
                exit;
            }
            echo json_encode(['status' => 'success', 'message' => '¡Sesión iniciada con éxito!']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Credenciales inválidas.']);
        }
        exit;
    }

    // --- 3. ACCIÓN: SOLICITAR RECUPERACIÓN DE CONTRASEÑA ---
    if ($action === 'recover_request') {
        if (empty($email)) {
            echo json_encode(['status' => 'error', 'message' => 'Por favor escribe tu correo electrónico.']);
            exit;
        }

        $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $token = bin2hex(random_bytes(16));
            $stmt = $pdo->prepare("UPDATE usuarios SET token = ? WHERE email = ?");
            $stmt->execute([$token, $email]);
            
            $enlaceRecuperar = "$url_base/auth.php?action=reset_view&token=$token";
            echo json_encode([
                'status' => 'success',
                'message' => 'Enlace de recuperación generado. Revisa tu bandeja de entrada de Gmail.',
                'link_simulado' => $enlaceRecuperar
            ]);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'El correo electrónico no está registrado.']);
        }
        exit;
    }
}

// --- 4. ACCIÓN: VALIDAR CUENTA (VÍA GET DESDE EL CORREO) ---
if ($_SERVER['REQUEST_METHOD'] === 'GET' && $action === 'verify') {
    $token = $_GET['token'] ?? '';
    $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE token = ?");
    $stmt->execute([$token]);
    $usuario = $stmt->fetch();
    
    if ($usuario) {
        $update = $pdo->prepare("UPDATE usuarios SET status = 'active', token = NULL WHERE id = ?");
        $update->execute([$usuario['id']]);
        echo "
        <div style='font-family: sans-serif; text-align: center; margin-top: 60px;'>
            <h1 style='color: #e11d48;'>❤️ ¡Cuenta Activada con Éxito!</h1>
            <p style='font-size: 1.1rem;'>Bienvenido a la app de citas donde vas a encontrar tu media naranja.</p>
            <br><br>
            <a href='https://mamis.onrender.com' style='background: #e11d48; color: white; padding: 12px 25px; text-decoration: none; font-weight: bold; border-radius: 8px;'>Regresar a la Aplicación e Iniciar Sesión</a>
        </div>";
    } else {
        echo "<h2>El enlace de validación es inválido o ya fue utilizado.</h2>";
    }
    exit;
}

// --- 5. ACCIÓN: VISTA DE CAMBIO DE CONTRASEÑA ---
if ($_SERVER['REQUEST_METHOD'] === 'GET' && $action === 'reset_view') {
    $token = $_GET['token'] ?? '';
    echo "
    <div style='font-family: sans-serif; text-align: center; margin-top: 60px; max-width: 400px; margin-left: auto; margin-right: auto; padding: 20px; border: 1px solid #ddd; border-radius: 10px;'>
        <h2 style='color: #e11d48;'>Restablecer Contraseña</h2>
        <p>Escribe tu nueva contraseña de seguridad:</p>
        <form action='auth.php?action=update_password' method='POST'>
            <input type='hidden' name='token' value='$token'>
            <input type='password' name='nueva_password' placeholder='Nueva contraseña' required style='width: 100%; padding: 10px; margin-bottom: 15px; box-sizing: border-box;'>
            <button type='submit' style='background: #e11d48; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; width: 100%; font-weight: bold;'>Actualizar Contraseña</button>
        </form>
    </div>";
    exit;
}

// --- 6. ACCIÓN: PROCESAR ACTUALIZACIÓN DE CONTRASEÑA ---
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
                <h2 style='color: green;'>✔️ ¡Contraseña restablecida correctamente!</h2>
                <br>
                <a href='https://mamis.onrender.com' style='background: #e11d48; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Volver a la App</a>
            </div>";
        } else {
            echo "<h2>El token de restablecimiento ha expirado o es inválido.</h2>";
        }
    }
    exit;
}
?>
