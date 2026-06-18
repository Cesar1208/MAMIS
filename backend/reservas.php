<?php
header('Content-Type: application/json');
require_once 'config.php';

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        $stmt = $pdo->query("SELECT * FROM RESERVA");
        $data = $stmt->fetchAll();
        echo json_encode($data);
        break;
        
    case 'POST':
        $dni = $_POST['dni'] ?? '';
        $idVuelo = $_POST['idVuelo'] ?? '';
        $fecha = $_POST['fecha'] ?? '';
        $precio = $_POST['precio'] ?? '';
        
        try {
            $stmt = $pdo->prepare("INSERT INTO RESERVA (dni, idVuelo, fecha, precio) VALUES (?, ?, ?, ?)");
            $stmt->execute([$dni, $idVuelo, $fecha, $precio]);
            echo json_encode(['status' => 'success', 'message' => 'Fila guardada en la base de datos relacional de Clever Cloud.']);
        } catch (PDOException $e) {
            echo json_encode([
                'status' => 'error', 
                'message' => 'Fallo relacional de integridad: Verifique que el DNI y el ID Vuelo coincidan con registros existentes en sus tablas padre.'
            ]);
        }
        break;
        
    case 'DELETE':
        $dni = $_GET['dni'] ?? '';
        $idVuelo = $_GET['idVuelo'] ?? '';
        
        $stmt = $pdo->prepare("DELETE FROM RESERVA WHERE dni = ? AND idVuelo = ?");
        $stmt->execute([$dni, $idVuelo]);
        echo json_encode(['status' => 'success', 'message' => 'Fila purgada de la infraestructura.']);
        break;
}
?>