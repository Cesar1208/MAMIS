<?php
// backend/reservas.php

require_once 'config.php';
header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];

// === OPERACIÓN CRUD: LEER / CONSULTAR (GET) ===
if ($method === 'GET') {
    $email = $_GET['email'] ?? '';
    if (empty($email)) {
        echo json_encode([]);
        exit;
    }
    
    // Ejecuta una consulta asíncrona para traer de forma ordenada las citas del usuario
    $stmt = $pdo->prepare("SELECT * FROM reservas WHERE usuario_email = ? ORDER BY fecha_hora ASC");
    $stmt->execute([$email]);
    echo json_encode($stmt->fetchAll());
    exit;
}

// === OPERACIÓN CRUD: INSERTAR / CREAR (POST) ===
if ($method === 'POST') {
    $json = file_get_contents('php://input');
    $datos = json_decode($json, true);
    
    $email = $datos['email'] ?? '';
    $nombre_cita = $datos['nombre_cita'] ?? '';
    $fecha_hora = $datos['fecha_hora'] ?? '';
    
    if (empty($email) || empty($nombre_cita) || empty($fecha_hora)) {
        echo json_encode(['status' => 'error', 'message' => 'Por favor, completa todos los campos de la cita.']);
        exit;
    }
    
    // Almacenamiento persistente relacional de la cita agendada
    $stmt = $pdo->prepare("INSERT INTO reservas (usuario_email, nombre_cita, fecha_hora) VALUES (?, ?, ?)");
    if ($stmt->execute([$email, $nombre_cita, $fecha_hora])) {
        echo json_encode(['status' => 'success', 'message' => '¡Cita registrada con éxito en Clever Cloud!']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'No se pudo almacenar la cita.']);
    }
    exit;
}
?>
