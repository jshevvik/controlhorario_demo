<?php
require_once __DIR__ . '/../../includes/init.php';

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

if (!$notificacionId) {
    echo json_encode(['success' => false, 'error' => 'ID de notificación requerido']);
    exit;
}

try {
    // Verificar que la notificación pertenece al empleado
    $stmt = $pdo->prepare("SELECT empleado_id FROM notificaciones WHERE id = ?");
    $stmt->execute([$notificacionId]);
    $notif = $stmt->fetch();
    
    if (!$notif || ($notif['empleado_id'] !== null && $notif['empleado_id'] != $empId)) {
        echo json_encode(['success' => false, 'error' => 'Notificación no encontrada']);
        exit;
    }
    
    // Eliminar la notificación
    $stmt = $pdo->prepare("DELETE FROM notificaciones WHERE id = ?");
    $stmt->execute([$notificacionId]);
    
    echo json_encode(['success' => true, 'message' => 'Notificación eliminada']);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
