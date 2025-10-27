<?php
session_start(); // Asegurar que la sesión esté iniciada
require_once __DIR__ . '/../../includes/init.php';

// Verificar que el usuario esté autenticado
if (!isset($_SESSION['empleado_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'No autorizado']);
    exit;
}

// Verificar que el usuario sea administrador
$empleado = getEmpleado();
if (!$empleado || $empleado['rol'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Acceso denegado']);
    exit;
}

try {
    // Obtener datos del request
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($input['id']) || !is_numeric($input['id'])) {
        echo json_encode(['success' => false, 'error' => 'ID de festivo no válido']);
        exit;
    }
    
    $festivo_id = (int)$input['id'];
    
    // Verificar que el festivo existe
    $stmt = $pdo->prepare("SELECT id FROM festivos WHERE id = ?");
    $stmt->execute([$festivo_id]);
    
    if (!$stmt->fetch()) {
        echo json_encode(['success' => false, 'error' => 'Festivo no encontrado']);
        exit;
    }
    
    // Eliminar el festivo
    $stmt = $pdo->prepare("DELETE FROM festivos WHERE id = ?");
    $stmt->execute([$festivo_id]);
    
    echo json_encode(['success' => true, 'message' => 'Festivo eliminado correctamente']);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'Error interno del servidor']);
}
