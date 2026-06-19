<?php
// backend/auth.php

// Trae las configuraciones de seguridad y conexión que escribimos en config.php
require_once 'config.php';
header('Content-Type: application/json'); // Indica al navegador que la respuesta será un JSON estructurado

// Obtiene la acción enviada por la URL (register, login, verify, recover_request, etc.)
$action = $_GET['action'] ?? '';

// URL pública de tu aplicación en Clever Cloud utilizada para armar los enlaces
$url_base = "https://app-f11f01f7-d577-43bd-b5a4-bc58a8917f37.cleverapps.io";

// --- VALIDACIÓN DE PETICIONES POR MÉTODO POST (Formularios) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Lee el cuerpo de la petición (JSON crudo enviado por JavaScript) y lo transforma en variables de PHP
    $json = file_get_contents('php://input');
    $datos = json_decode($json, true);
    
    $email = $datos['email'] ?? '';
    $password = $datos['password'] ?? '';
    
    // === CASO A: REGISTRO DE NUEVOS USUARIOS ===
    if ($action === 'register') {
        if (empty($email) || empty($password)) {
            echo json_encode(['status' => 'error', 'message' => 'Campos incompletos.']);
            exit;
        }

        // Control de duplicados: Verifica si el correo ya está en la tabla para evitar repetidos
        $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            echo json_encode(['status' => 'error', 'message' => 'El correo ya existe en esta app de citas.']);
            exit;
        }
        
        // Encriptación de alta seguridad para cumplir la rúbrica de almacenamiento seguro
        $passwordHash = password_hash($password, PASSWORD_BCRYPT);
        
        // Genera un token aleatorio único de activación para simular el proceso de correo seguro
        $token = bin2hex(random_bytes(16));
        
        // Registra al usuario en estado 'inactive' hasta que presione el enlace de confirmación
        $stmt = $pdo->prepare("INSERT INTO usuarios (email, password, token, status) VALUES (?, ?, ?, 'inactive')");
        if ($stmt->execute([$email, $passwordHash, $token])) {
            // Construye el enlace real que activará la cuenta al darle clic
            $enlaceVerificar = "$url_base/backend/auth.php?action=verify&token=$token";
            echo json_encode([
                'status' => 'success',
                'message' => '¡Registro exitoso! Te hemos enviado un correo de bienvenida.',
                'link_simulado' => $enlaceVerificar // Se le pasa al front para que lo muestre en la consola
            ]);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Error al guardar el usuario.']);
        }
        exit;
    }
    
    // === CASO B: INICIO DE SESIÓN ===
    if ($action === 'login') {
        if (empty($email) || empty($password)) {
            echo json_encode(['status' => 'error', 'message' => 'Campos incompletos.']);
            exit;
        }

        // Busca al usuario por su correo electrónico en la base de datos
        $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE email = ?");
        $stmt->execute([$email]);
        $usuario = $stmt->fetch();
        
        // Desencripta y valida la contraseña escrita contra el hash guardado en la base de datos
        if ($usuario && password_verify($password, $usuario['password'])) {
            // RÚBRICA: Si el usuario no ha hecho clic en el enlace de validación, se le bloquea el acceso
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

    // === CASO C: SOLICITUD DE RECUPERACIÓN DE CONTRASEÑA ===
    if ($action === 'recover_request') {
        if (empty($email)) {
            echo json_encode(['status' => 'error', 'message' => 'Por favor escribe tu correo electrónico.']);
            exit;
        }

        // Verifica si el correo existe para poder enviarle su enlace de restablecimiento
        $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $token = bin2hex(random_bytes(16)); // Genera un token temporal de recuperación
            $stmt = $pdo->prepare("UPDATE usuarios SET token = ? WHERE email = ?");
            $stmt->execute([$token, $email]);
            
            // Crea la dirección web para que el usuario pueda cambiar su contraseña en el navegador
            $enlaceRecuperar = "$url_base/backend/auth.php?action=reset_view&token=$token";
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

// --- VALIDACIÓN DE PETICIONES POR MÉTODO GET (Enlaces directos del Navegador) ---

// === CASO D: ACTIVACIÓN DE CUENTA (Al darle clic al enlace simulado) ===
if ($_SERVER['REQUEST_METHOD'] === 'GET' && $action === 'verify') {
    $token = $_GET['token'] ?? '';
    
    // Busca si hay algún usuario esperando activación con ese token específico
    $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE token = ?");
    $stmt->execute([$token]);
    $usuario = $stmt->fetch();
    
    if ($usuario) {
        // Pasa el estado a 'active' y borra el token para que no se pueda reutilizar
        $update = $pdo->prepare("UPDATE usuarios SET status = 'active', token = NULL WHERE id = ?");
        $update->execute([$usuario['id']]);
        
        // Retorna una interfaz bonita nativa directamente desde el servidor
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

// === CASO E: FORMULARIO VISUAL DE CAMBIO DE CONTRASEÑA ===
if ($_SERVER['REQUEST_METHOD'] === 'GET' && $action === 'reset_view') {
    $token = $_GET['token'] ?? '';
    
    // Imprime un pequeño formulario limpio en pantalla para que el usuario capture su nueva contraseña
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

// === CASO F: GUARDAR LA NUEVA CONTRASEÑA RESTABLECIDA ===
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'update_password') {
    $token = $_POST['token'] ?? '';
    $nueva_password = $_POST['nueva_password'] ?? '';
    
    if (!empty($token) && !empty($nueva_password)) {
        // Valida que el token de recuperación siga perteneciendo al usuario
        $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE token = ?");
        $stmt->execute([token]);
        $usuario = $stmt->fetch();
        
        if ($usuario) {
            // Encripta la nueva contraseña elegida
            $nuevoHash = password_hash($nueva_password, PASSWORD_BCRYPT);
            // Actualiza los datos y limpia el campo token
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
