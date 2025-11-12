<?php

require_once __DIR__ . '/../../includes/init.php';
session_start();
header('Content-Type: application/json');

// Debug logging
error_log("DEBUG - Aprobar solicitud iniciado. POST data: " . print_r($_POST, true));
error_log("DEBUG - Session data: " . print_r($_SESSION, true));

$id = $_POST['solicitud_id'] ?? $_POST['id'] ?? 0;
$accion = $_POST['accion'] ?? 'aprobar'; // Default aprobar para compatibilidad
$comentario = $_POST['comentario'] ?? '';

error_log("DEBUG - ID: $id, Accion: $accion, Comentario: $comentario");

// Verificar si el usuario está autenticado
if (!isset($_SESSION['empleado_id'])) {
    exit(json_encode(['success' => false, 'error' => 'No autenticado']));
}

// Verificar si el usuario es administrador
if (!isAdmin()) {
    exit(json_encode(['success' => false, 'error' => 'No autorizado']));
}

// Validar el ID recibido
if (!$id || !is_numeric($id)) {
    exit(json_encode(['success' => false, 'error' => 'ID vacío o inválido']));
}

// Validar la acción
if (!in_array($accion, ['aprobar', 'rechazar'])) {
    exit(json_encode(['success' => false, 'error' => 'Acción inválida']));
}

// Determinar el nuevo estado
$nuevoEstado = ($accion === 'aprobar') ? 'aprobado' : 'rechazado';

try {
    // Preparar y ejecutar la consulta
    $stmt = $pdo->prepare("UPDATE solicitudes SET estado=?, fecha_respuesta=NOW(), supervisor_id=?, comentario_admin=? WHERE id=?");
    $ok = $stmt->execute([$nuevoEstado, $_SESSION['empleado_id'], $comentario, $id]);

    if ($ok) {
        // Registrar en historial
        registrarCambioSolicitud(
            $id, 
            $accion, 
            'estado', 
            'pendiente', 
            $nuevoEstado, 
            $comentario
        );
        
        // Crear notificación para el empleado que hizo la solicitud
        $stmtEmpleado = $pdo->prepare("SELECT empleado_id, tipo, fecha_inicio, fecha_fin FROM solicitudes WHERE id = ?");
        $stmtEmpleado->execute([$id]);
        $solicitud = $stmtEmpleado->fetch(PDO::FETCH_ASSOC);
        
        if ($solicitud) {
            $tipoSolicitud = formatearTipo($solicitud['tipo']);
            $fechas = '';
            if ($solicitud['fecha_inicio']) {
                $fechas = ' para ' . formatearFecha($solicitud['fecha_inicio']);
                if ($solicitud['fecha_fin'] && $solicitud['fecha_fin'] !== $solicitud['fecha_inicio']) {
                    $fechas .= ' - ' . formatearFecha($solicitud['fecha_fin']);
                }
            }
            
            $mensaje = "Tu solicitud de $tipoSolicitud$fechas ha sido " . ($nuevoEstado === 'aprobado' ? 'APROBADA' : 'RECHAZADA');
            if ($comentario) {
                $mensaje .= ". Comentario del administrador: " . $comentario;
            }
            
            error_log("DEBUG - Creando notificación: '$mensaje' para empleado ID: " . $solicitud['empleado_id']);
            crearNotificacion($mensaje, $solicitud['empleado_id'], $nuevoEstado === 'aprobado' ? 'aprobacion' : 'alerta', 'admin/ver-solicitudes');
            error_log("DEBUG - Notificación creada exitosamente");
        }
        
        echo json_encode(['success' => true, 'message' => 'Solicitud ' . ($nuevoEstado === 'aprobado' ? 'aprobada' : 'rechazada') . ' correctamente']);
    } else {
        echo json_encode(['success' => false, 'error' => 'Error al actualizar la solicitud']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'Error en la base de datos: ' . $e->getMessage()]);
}
