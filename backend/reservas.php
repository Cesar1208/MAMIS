<?php
// AGREGAR ESTO AL PRINCIPIO DE RESERVAS.PHP
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit(0);
}

header('Content-Type: application/json');
require_once 'config.php';

// ... Todo el resto de tu código normal de insertar, listar o borrar de la tabla RESERVA ...
