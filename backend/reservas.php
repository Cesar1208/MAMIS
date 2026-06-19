<?php
// backend/reservas.php
require_once 'config.php';

header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        // Listar o consumir todas las reservas de la base de datos
        $stmt = $pdo->query("SELECT * FROM reservas ORDER BY fecha ASC");
        $reservas = $stmt->fetchAll();
        echo json_encode(['status' => 'success', 'data' => $reservas]);
        break;
        
    case 'POST':
        // Operación CRUD: Crear una nueva cita
        $input = json_decode(file_get_contents('php://input'), true);
        $usuario_id = $input['usuario_id'] ?? 1;
        $detalle = $input['detalle'] ?? '';
        $fecha = $input['fecha'] ?? '';
        
        if (empty($detalle) || empty($fecha)) {
            echo json_encode(['status' => 'error', 'message' => 'Campos obligatorios incompletos.']);
            exit;
        }
        
        $stmt = $pdo->prepare("INSERT INTO reservas (usuario_id, detalle, fecha) VALUES (?, ?, ?)");
        if ($stmt->execute([$usuario_id, $detalle, $fecha])) {
            echo json_encode(['status' => 'success', 'message' => 'Cita agendada de forma persistente.']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'No se pudo registrar la cita.']);
        }
        break;
        
    default:
        http_response_code(405);
        echo json_encode(['status' => 'error', 'message' => 'Método no permitido.']);
        break;
}
?>
