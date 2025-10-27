<?php
require_once __DIR__ . '/../../includes/init.php';

header('Content-Type: application/json');

$empId = $_SESSION['empleado_id'] ?? null;
if (!$empId) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'No autorizado']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Método no permitido']);
    exit;
}

$notificacionId = $_POST['notificacion_id'] ?? null;

if (!$notificacionId || !is_numeric($notificacionId)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'ID de notificación inválido']);
    exit;
}

try {
    // Marcar la notificación específica como leída
    $sql = "UPDATE notificaciones SET leido = 1 WHERE id = ? AND (empleado_id = ? OR empleado_id IS NULL)";
    $st = $pdo->prepare($sql);
    $result = $st->execute([$notificacionId, $empId]);
    
    if ($result) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => 'No se pudo actualizar la notificación']);
    }
} catch (Exception $e) {
    error_log("Error al marcar notificación como leída: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Error interno del servidor']);
}
?>
