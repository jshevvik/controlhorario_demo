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

// Verificar que el usuario sea administrador o supervisor
$empleado = getEmpleado();
if (!$empleado || !in_array($empleado['rol'], ['admin', 'supervisor'])) {
    echo json_encode(['success' => false, 'error' => 'Sin permisos']);
    exit;
}

// Verificar que sea una petición GET o POST
if (!isset($_GET['id']) && !isset($_POST['id'])) {
    echo json_encode(['success' => false, 'error' => 'ID de solicitud requerido']);
    exit;
}

$solicitudId = $_GET['id'] ?? $_POST['id'];

// Validar ID
if (!$solicitudId || !is_numeric($solicitudId)) {
    echo json_encode(['success' => false, 'error' => 'ID de solicitud inválido']);
    exit;
}

try {
    // Obtener detalles completos de la solicitud
    $stmt = $pdo->prepare("
        SELECT s.*, 
               e.nombre, e.apellidos, e.email,
               CASE 
                   WHEN s.supervisor_id IS NOT NULL THEN 
                       CONCAT(a.nombre, ' ', a.apellidos)
                   ELSE NULL 
               END as aprobador_nombre,
               a.email as aprobador_email
        FROM solicitudes s
        JOIN empleados e ON e.id = s.empleado_id
        LEFT JOIN empleados a ON a.id = s.supervisor_id
        WHERE s.id = ?
    ");
    $stmt->execute([$solicitudId]);
    $solicitud = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$solicitud) {
        echo json_encode(['success' => false, 'error' => 'Solicitud no encontrada']);
        exit;
    }
    
    // Formatear los datos para mostrar
    $tipoTexto = [
        'vacaciones' => 'Vacaciones',
        'permiso' => 'Permiso',
        'baja' => 'Baja médica',
        'extra' => 'Horas extra',
        'ausencia' => 'Ausencia'
    ][$solicitud['tipo']] ?? ucfirst($solicitud['tipo']);
    
    $estadoTexto = [
        'pendiente' => 'Pendiente',
        'aprobado' => 'Aprobado',
        'rechazado' => 'Rechazado'
    ][$solicitud['estado']] ?? ucfirst($solicitud['estado']);
    
    $estadoClass = [
        'pendiente' => 'warning',
        'aprobado' => 'success',
        'rechazado' => 'danger'
    ][$solicitud['estado']] ?? 'secondary';
    
    // Verificar si existe archivo adjunto
    $archivoInfo = null;
    if (!empty($solicitud['archivo'])) {
        $rutaArchivo = __DIR__ . '/../../uploads/solicitudes/' . $solicitud['archivo'];
        if (file_exists($rutaArchivo)) {
            $archivoInfo = [
                'nombre' => $solicitud['archivo'],
                'nombre_original' => $solicitud['archivo'], // Puedes mejorarlo guardando el nombre original
                'tamaño' => filesize($rutaArchivo),
                'extension' => strtolower(pathinfo($solicitud['archivo'], PATHINFO_EXTENSION)),
                'existe' => true
            ];
        } else {
            $archivoInfo = [
                'nombre' => $solicitud['archivo'],
                'existe' => false
            ];
        }
    }

    // Obtener historial de cambios
    $historial = getHistorialSolicitud($solicitudId);
    
    // Preparar respuesta
    $response = [
        'success' => true,
        'solicitud' => [
            'id' => $solicitud['id'],
            'tipo' => $solicitud['tipo'],
            'tipo_texto' => $tipoTexto,
            'estado' => $solicitud['estado'],
            'estado_texto' => $estadoTexto,
            'estado_class' => $estadoClass,
            'empleado_nombre' => $solicitud['nombre'] . ' ' . $solicitud['apellidos'],
            'empleado_email' => $solicitud['email'],
            'fecha_inicio' => $solicitud['fecha_inicio'],
            'fecha_fin' => $solicitud['fecha_fin'],
            'horas' => $solicitud['horas'],
            'hora_inicio' => $solicitud['hora_inicio'],
            'hora_fin' => $solicitud['hora_fin'],
            'medio_dia' => $solicitud['medio_dia'],
            'comentario_empleado' => $solicitud['comentario_empleado'],
            'comentario_admin' => $solicitud['comentario_admin'],
            'fecha_solicitud' => $solicitud['fecha_solicitud'],
            'fecha_respuesta' => $solicitud['fecha_respuesta'],
            'aprobador_nombre' => $solicitud['aprobador_nombre'],
            'aprobador_email' => $solicitud['aprobador_email'],
            'tipo_ausencia' => $solicitud['tipo_ausencia'] ?? null,
            'archivo' => $archivoInfo
        ],
        'historial' => $historial
    ];
    
    echo json_encode($response);
    
} catch (PDOException $e) {
    error_log("Error de BD al obtener detalles de solicitud: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Error de base de datos']);
} catch (Exception $e) {
    error_log("Error general al obtener detalles de solicitud: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Error interno del servidor']);
}
?>
