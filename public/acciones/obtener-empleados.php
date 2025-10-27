<?php

error_log("OBTENER-EMPLEADOS: Iniciando script");

require_once __DIR__ . '/../../includes/init.php';
error_log("OBTENER-EMPLEADOS: init.php cargado");

header('Content-Type: application/json');

// Verificar que el usuario esté autenticado y sea admin/supervisor
error_log("OBTENER-EMPLEADOS: SESSION ID = " . ($_SESSION['empleado_id'] ?? 'NO SET'));

if (!isset($_SESSION['empleado_id'])) {
    error_log("OBTENER-EMPLEADOS: No autenticado");
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'No autenticado']);
    exit;
}

try {
    error_log("OBTENER-EMPLEADOS: Preparando query");
    $stmt = $pdo->prepare('SELECT id, nombre, apellidos, email FROM empleados ORDER BY nombre, apellidos');
    error_log("OBTENER-EMPLEADOS: Ejecutando query");
    $stmt->execute();
    $empleados = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    error_log("OBTENER-EMPLEADOS: Empleados obtenidos: " . count($empleados));
    echo json_encode(['success' => true, 'empleados' => $empleados]);
} catch (Exception $e) {
    error_log("OBTENER-EMPLEADOS: Error - " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
