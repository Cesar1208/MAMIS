<?php
// backend/auth.php

// 1. CONFIGURACIÓN DE ENCABEZADOS CORS CONTROLADOS Y SEGUROS
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept, Authorization");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");

// 2. RESPUESTA INMEDIATA A LA PETICIÓN PREVIA (OPTIONS) DEL NAVEGADOR
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit(0);
}

// 3. INCLUSIÓN DE LA CONEXIÓN A LA BASE DE DATOS MAPPED EN CLEVER CLOUD
require_once 'config.php';

header('Content-Type: application/json');

// Leer el flujo de datos JSON entrante de peticiones asíncronas de Fetch
$input = json_decode(file_get_contents('php://input'), true);
$action = isset($_GET['action']) ? $_GET['action'] : '';

// --- LÓGICA DE REGISTRO DE USUARIO ---
if ($action === 'register') {
    $email = isset($input['email']) ? trim($input['email']) : '';
    $password = isset($input['password']) ? trim($input['password']) : '';

    if (empty($email) || empty($password)) {
        echo json_encode(['status' => 'error', 'message' => 'Campos incompletos.']);
        exit;
    }

    try {
        // Verificar si el usuario ya se encuentra registrado en la tabla correspondientes
        $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            echo json_encode(['status' => 'error', 'message' => 'El correo electrónico ya está registrado.']);
            exit;
        }

        // Almacenar el registro con contraseña cifrada y token de verificación inactivo
        $passwordHash = password_hash($password, PASSWORD_BCRYPT);
        $token = bin2hex(random_bytes(16));

        $stmt = $pdo->prepare("INSERT INTO usuarios (email, password, token, activo) VALUES (?, ?, ?, 0)");
        $stmt->execute([$email, $passwordHash, $token]);

        // Generar enlace dinámico usando el nuevo dominio mapeado limpio
        $linkSimulado = "https://mamis-cesar.cleverapps.io/backend/auth.php?action=activate&token=" . $token;

        echo json_encode([
            'status' => 'success',
            'message' => '¡Registro exitoso! Cuenta creada de manera persistente en la nube.',
            'link_simulado' => $linkSimulado
        ]);
        exit;
    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => 'Error en base de datos: ' . $e->getMessage()]);
        exit;
    }
}

// --- LÓGICA DE ACTIVACIÓN DE CUENTA (MÉTODO GET) ---
if ($action === 'activate') {
    $token = isset($_GET['token']) ? trim($_GET['token']) : '';

    if (empty($token)) {
        echo "<h1>Módulo de Activación</h1><p>Token inválido o ausente.</p>";
        exit;
    }

    try {
        $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE token = ?");
        $stmt->execute([$token]);
        $usuario = $stmt->fetch();

        if ($usuario) {
            // Modificar de manera persistente el estado de la cuenta a activa
            $update = $pdo->prepare("UPDATE usuarios SET activo = 1, token = NULL WHERE id = ?");
            $update->execute([$usuario['id']]);
            echo "<body style='background:#121214;color:#fff;font-family:sans-serif;text-align:center;padding-top:100px;'>";
            echo "<h1>❤️ ¡Cuenta Activada con Éxito!</h1>";
            echo "<p>Tu cuenta ha sido validada en Clever Cloud de forma persistente. Ya puedes regresar a la aplicación e iniciar sesión.</p>";
            echo "</body>";
            exit;
        } else {
            echo "<h1>Error de Validación</h1><p>El enlace de activación ya fue utilizado o expiró.</p>";
            exit;
        }
    } catch (PDOException $e) {
        echo "<h1>Error Crítico</h1><p>" . $e->getMessage() . "</p>";
        exit;
    }
}

// --- LÓGICA DE INICIO DE SESIÓN ---
if ($action === 'login') {
    $email = isset($input['email']) ? trim($input['email']) : '';
    $password = isset($input['password']) ? trim($input['password']) : '';

    try {
        $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            if ((int)$user['activo'] === 1) {
                echo json_encode(['status' => 'success', 'message' => 'Sesión iniciada con éxito. Redireccionando al panel CRUD...']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Esta cuenta no ha sido activada. Revisa el link de activación en la consola inferior.']);
            }
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Credenciales incorrectas de acceso.']);
        }
        exit;
    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => 'Error: ' . $e->getMessage()]);
        exit;
    }
}

// --- LÓGICA DE RECUPERACIÓN DE CONTRASEÑA ---
if ($action === 'recover_request') {
    $email = isset($input['email']) ? trim($input['email']) : '';

    try {
        $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $token = bin2hex(random_bytes(16));
            $update = $pdo->prepare("UPDATE usuarios SET token = ? WHERE email = ?");
            $update->execute([$token, $email]);

            $linkSimulado = "https://mamis-cesar.cleverapps.io/backend/auth.php?action=reset_view&token=" . $token;

            echo json_encode([
                'status' => 'success',
                'message' => 'Enlace de recuperación generado dinámicamente.',
                'link_simulado' => $linkSimulado
            ]);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'El correo electrónico no se encuentra registrado.']);
        }
        exit;
    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => 'Error: ' . $e->getMessage()]);
        exit;
    }
}

echo json_encode(['status' => 'error', 'message' => 'Acción no permitida o ruta no válida.']);
