<?php
require_once __DIR__ . '/../../includes/init.php';

header('Content-Type: application/json');

$empId = $_SESSION['empleado_id'] ?? null;
if (!$empId) {
    http_response_code(403);
    echo json_encode(['count' => 0, 'error' => 'No autorizado']);
    exit;
}

try {
    // Contar notificaciones no leídas
    $sql = "SELECT COUNT(*) as count FROM notificaciones WHERE (empleado_id = ? OR empleado_id IS NULL) AND leido = 0";
    $st = $pdo->prepare($sql);
    $st->execute([$empId]);
    $result = $st->fetch(PDO::FETCH_ASSOC);
    
    echo json_encode(['count' => (int)$result['count']]);
} catch (Exception $e) {
    error_log("Error al obtener contador de notificaciones: " . $e->getMessage());
    echo json_encode(['count' => 0, 'error' => 'Error interno del servidor']);
}
?>
