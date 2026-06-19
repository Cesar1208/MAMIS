<?php
// backend/reservas.php
header("Access-Control-Allow-Origin: https://mamis.onrender.com");
header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept, Authorization");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS, PUT, DELETE");
header("Access-Control-Allow-Credentials: true");

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit(0);
}

require_once 'config.php';

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        try {
            $stmt = $pdo->query("SELECT * FROM reservas ORDER BY fecha DESC");
            echo json_encode($stmt->fetchAll());
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
        break;

    case 'POST':
        $dni = $_POST['dni'] ?? null;
        $idVuelo = $_POST['idVuelo'] ?? null;
        $fecha = $_POST['fecha'] ?? null;
        $precio = $_POST['precio'] ?? null;

        if (!$dni || !$idVuelo || !$fecha || !$precio) {
            echo json_encode(['status' => 'error', 'message' => 'Datos incompletos para procesar la reserva.']);
            exit;
        }

        try {
            $stmt = $pdo->prepare("INSERT INTO reservas (dni, idVuelo, fecha, precio) VALUES (?, ?, ?, ?)");
            $stmt->execute([$dni, $idVuelo, $fecha, $precio]);
            echo json_encode(['status' => 'success', 'message' => 'Registro insertado de forma persistente en MySQL.']);
        } catch (PDOException $e) {
            echo json_encode(['status' => 'error', 'message' => 'Fallo en base de datos: ' . $e->getMessage()]);
        }
        break;

    case 'DELETE':
        $dni = $_GET['dni'] ?? null;
        $idVuelo = $_GET['idVuelo'] ?? null;

        if (!$dni || !$idVuelo) {
            echo json_encode(['status' => 'error', 'message' => 'Identificadores ausentes para la baja.']);
            exit;
        }

        try {
            $stmt = $pdo->prepare("DELETE FROM reservas WHERE dni = ? AND idVuelo = ?");
            $stmt->execute([$dni, $idVuelo]);
            echo json_encode(['status' => 'success', 'message' => 'Registro eliminado de las tablas físicas de MySQL Cloud.']);
        } catch (PDOException $e) {
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
        break;

    default:
        http_response_code(405);
        echo json_encode(['status' => 'error', 'message' => 'Método no soportado.']);
        break;
}
?>
