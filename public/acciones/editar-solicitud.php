<?php
require_once __DIR__ . '/../../includes/init.php';

header('Content-Type: application/json');

// Verificar autenticación y permisos
if (!isset($_SESSION['empleado_id'])) {
    echo json_encode(['success' => false, 'error' => 'No autenticado']);
    exit;
}

$empleado = getEmpleado();
if (!$empleado || !isAdmin()) {
    echo json_encode(['success' => false, 'error' => 'Sin permisos']);
    exit;
}

// Recibir datos (FormData o POST)
$solicitudId = $_POST['id'] ?? null;
$fechaInicio = $_POST['fecha_inicio'] ?? null;
$fechaFin = $_POST['fecha_fin'] ?? null;
$horas = $_POST['horas'] ?? null;
$medioDia = $_POST['medio_dia'] ?? 0;
$comentario = $_POST['comentario_admin'] ?? null;
$archivo = null;

if (!$solicitudId) {
    echo json_encode(['success' => false, 'error' => 'ID de solicitud requerido']);
    exit;
}

try {
    // Obtener solicitud actual
    $stmt = $pdo->prepare("SELECT * FROM solicitudes WHERE id = ? AND estado = 'aprobado'");
    $stmt->execute([$solicitudId]);
    $solicitud = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$solicitud) {
        echo json_encode(['success' => false, 'error' => 'Solicitud no encontrada o no está aprobada']);
        exit;
    }
    
    // Validar que solo pueda editar solicitudes aprobadas
    // Ahora permite editar cualquier tipo de solicitud, no solo vacaciones y bajas médicas
    if ($solicitud['estado'] !== 'aprobado') {
        echo json_encode(['success' => false, 'error' => 'Solo se pueden editar solicitudes aprobadas']);
        exit;
    }
    
    // Gestión de archivo adjunto (opcional)
    if (!empty($_FILES['archivo']['name'])) {
        $extensionesPermitidas = ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png', 'gif'];
        $extension = strtolower(pathinfo($_FILES['archivo']['name'], PATHINFO_EXTENSION));
        $tamañoMaximo = 5 * 1024 * 1024; // 5MB
        
        if (!in_array($extension, $extensionesPermitidas)) {
            throw new Exception('Tipo de archivo no permitido. Solo: ' . implode(', ', $extensionesPermitidas));
        }
        
        if ($_FILES['archivo']['size'] > $tamañoMaximo) {
            throw new Exception('El archivo es demasiado grande. Máximo 5MB.');
        }
        
        $dir = __DIR__ . '/../../uploads/solicitudes/';
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        
        // Eliminar archivo anterior si existe
        if ($solicitud['archivo'] && file_exists($dir . $solicitud['archivo'])) {
            unlink($dir . $solicitud['archivo']);
        }
        
        $nombreArchivo = date('YmdHis') . '_' . uniqid() . '.' . $extension;
        $destino = $dir . $nombreArchivo;
        
        if (move_uploaded_file($_FILES['archivo']['tmp_name'], $destino)) {
            $archivo = $nombreArchivo;
        } else {
            throw new Exception('Error al subir el archivo');
        }
    }
    
    // Preparar valores actualizados
    $updateFechaInicio = $fechaInicio ? date('Y-m-d', strtotime($fechaInicio)) : $solicitud['fecha_inicio'];
    $updateFechaFin = $fechaFin ? date('Y-m-d', strtotime($fechaFin)) : $solicitud['fecha_fin'];
    $updateMedioDia = $medioDia ? 1 : 0;
    $updateHoras = ($solicitud['tipo'] === 'baja' && $horas !== null) ? floatval($horas) : $solicitud['horas'];
    $updateComentario = $comentario ?? $solicitud['comentario_admin'];
    $updateArchivo = $archivo ?? $solicitud['archivo'];
    
    // Validar fechas
    if ($updateFechaInicio > $updateFechaFin) {
        echo json_encode(['success' => false, 'error' => 'La fecha de inicio no puede ser posterior a la de fin']);
        exit;
    }
    
    // Actualizar solicitud
    $sql = "UPDATE solicitudes 
            SET fecha_inicio = ?, fecha_fin = ?, medio_dia = ?, horas = ?, comentario_admin = ?, archivo = ?
            WHERE id = ?";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        $updateFechaInicio,
        $updateFechaFin,
        $updateMedioDia,
        $updateHoras,
        $updateComentario,
        $updateArchivo,
        $solicitudId
    ]);
    
    // Crear notificación para el empleado que hizo la solicitud
    $empleadoSolicitud = $solicitud['empleado_id'];
    $adminId = $_SESSION['empleado_id'];
    
    $stmt = $pdo->prepare("SELECT nombre, apellidos FROM empleados WHERE id = ?");
    $stmt->execute([$adminId]);
    $adminData = $stmt->fetch(PDO::FETCH_ASSOC);
    $adminNombre = $adminData['nombre'] . ' ' . $adminData['apellidos'];
    
    $tipoTexto = $solicitud['tipo'] === 'vacaciones' ? 'Vacaciones' : 'Baja médica';
    $mensaje = "Tu solicitud de $tipoTexto ha sido corregida por $adminNombre. Nueva fecha: " . date('d/m/Y', strtotime($updateFechaInicio)) . " - " . date('d/m/Y', strtotime($updateFechaFin));
    
    $stmtNotif = $pdo->prepare("
        INSERT INTO notificaciones (empleado_id, tipo, mensaje, url)
        VALUES (?, 'solicitud_modificada', ?, ?)
    ");
    $stmtNotif->execute([
        $empleadoSolicitud,
        $mensaje,
        'solicitudes.php?id=' . $solicitudId
    ]);
    
    echo json_encode(['success' => true, 'message' => 'Solicitud actualizada correctamente']);
    
} catch (PDOException $e) {
    error_log("Error de BD al editar solicitud: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Error de base de datos']);
} catch (Exception $e) {
    error_log("Error general al editar solicitud: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Error interno del servidor']);
}
?>
