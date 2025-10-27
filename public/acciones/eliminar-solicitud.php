<?php
session_start();
require_once __DIR__ . '/../../includes/init.php';

// Configurar respuesta JSON
header('Content-Type: application/json');

// Verificar que el usuario esté autenticado
if (!isset($_SESSION['empleado_id'])) {
    echo json_encode(['success' => false, 'error' => 'No autenticado']);
    exit;
}

// Verificar que el usuario sea solo administrador (eliminar es solo para admin)
$empleado = getEmpleado();
if (!$empleado || $empleado['rol'] !== 'admin') {
    echo json_encode(['success' => false, 'error' => 'Solo administradores pueden eliminar solicitudes']);
    exit;
}

// Verificar que sea una petición POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Método no permitido']);
    exit;
}

// Obtener datos del formulario o JSON
$input = json_decode(file_get_contents('php://input'), true);
$solicitudId = $input['id'] ?? $_POST['solicitud_id'] ?? $_POST['id'] ?? null;

// Validar datos
if (!$solicitudId || !is_numeric($solicitudId)) {
    echo json_encode(['success' => false, 'error' => 'ID de solicitud inválido']);
    exit;
}

try {
    // Verificar que la solicitud existe
    $stmt = $pdo->prepare("SELECT id, empleado_id, tipo, estado FROM solicitudes WHERE id = ?");
    $stmt->execute([$solicitudId]);
    $solicitud = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$solicitud) {
        echo json_encode(['success' => false, 'error' => 'Solicitud no encontrada']);
        exit;
    }
    
    // Log de la acción antes de eliminar
    error_log("Eliminando solicitud ID: {$solicitudId} por usuario ID: {$empleado['id']} ({$empleado['nombre']})");
    
    // Eliminar la solicitud
    $deleteStmt = $pdo->prepare("DELETE FROM solicitudes WHERE id = ?");
    $result = $deleteStmt->execute([$solicitudId]);
    
    if ($result) {
        // Log de éxito

        
        echo json_encode([
            'success' => true, 
            'message' => 'Solicitud eliminada correctamente'
        ]);
    } else {
        // Error en la eliminación

        echo json_encode(['success' => false, 'error' => 'Error al eliminar la solicitud']);
    }
    
} catch (PDOException $e) {
    // Error de base de datos
    error_log("Error de BD al eliminar solicitud: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Error de base de datos']);
} catch (Exception $e) {
    // Error general
    error_log("Error general al eliminar solicitud: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Error interno del servidor']);
}
?>
