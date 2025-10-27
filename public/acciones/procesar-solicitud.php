<?php
$timeStart = microtime(true);
error_log("DEBUG - Inicio del procesamiento: " . date('Y-m-d H:i:s'));

require_once __DIR__ . '/../../includes/init.php';

// Configuración para AJAX
if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

$userId = $_SESSION['user_id'] ?? $_SESSION['empleado_id'] ?? null;

if (!$userId) {
    echo json_encode(['success'=>false, 'error'=>'Usuario no autenticado']);
    exit;
}


$tipo         = $_POST['tipo']         ?? '';
$fechaInicio  = $_POST['start_date']   ?? $_POST['fecha_inicio'] ?? null;
$fechaFin     = $_POST['end_date']     ?? $_POST['fecha_fin'] ?? null;
$medioDia     = isset($_POST['half_day']) ? (int)$_POST['half_day'] : 0;
$horas        = isset($_POST['horas']) ? floatval($_POST['horas']) : null;
$horaInicio   = $_POST['hora_inicio']  ?? null;  // Nueva: hora de inicio para horas extra
$horaFin      = $_POST['hora_fin']     ?? null;  // Nueva: hora de fin para horas extra
$tipoAusencia = $_POST['tipo_ausencia'] ?? null; // Nueva: tipo específico de ausencia
$comentario   = $_POST['motivo'] ?? $_POST['reason'] ?? $_POST['comentario'] ?? '';
$archivo      = null;

// Debug: Log de datos recibidos


// Gestión de archivo adjunto (opcional)
if (!empty($_FILES['file']['name'])) {
    $timeFile = microtime(true);

    
    $extensionesPermitidas = ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png', 'gif'];
    $extension = strtolower(pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION));
    $tamañoMaximo = 5 * 1024 * 1024; // 5MB
    
    if (!in_array($extension, $extensionesPermitidas)) {
        echo json_encode(['success'=>false, 'error'=>'Tipo de archivo no permitido. Solo: ' . implode(', ', $extensionesPermitidas)]);
        exit;
    }
    
    if ($_FILES['file']['size'] > $tamañoMaximo) {
        echo json_encode(['success'=>false, 'error'=>'El archivo es demasiado grande. Máximo 5MB.']);
        exit;
    }
    
    $dir = __DIR__ . '/../../uploads/solicitudes/';
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    
    $nombreArchivo = date('YmdHis') . '_' . uniqid() . '.' . $extension;
    $destino = $dir . $nombreArchivo;
    
    if (move_uploaded_file($_FILES['file']['tmp_name'], $destino)) {
        $archivo = $nombreArchivo;
        $timeFileEnd = microtime(true);
        error_log("DEBUG - Archivo procesado en " . round(($timeFileEnd - $timeFile) * 1000, 2) . "ms");
    } else {
        echo json_encode(['success'=>false, 'error'=>'Error al subir el archivo']);
        exit;
    }
} else {

}

// Convertir fechas al formato SQL si están presentes
$timeDates = microtime(true);
$fechaInicioSQL = $fechaInicio;
$fechaFinSQL = $fechaFin;

// Si las fechas vienen en formato DD/MM/YYYY, convertirlas
if ($fechaInicio && strpos($fechaInicio, '/') !== false) {
    $fechaInicioSQL = toSQLDate($fechaInicio);
}
if ($fechaFin && strpos($fechaFin, '/') !== false) {
    $fechaFinSQL = toSQLDate($fechaFin);
}



// Validaciones básicas
if (!$tipo) {
    echo json_encode(['success'=>false, 'error'=>'Tipo de solicitud requerido']);
    exit;
}
if (in_array($tipo, ['vacaciones', 'permiso', 'baja', 'ausencia']) && (!$fechaInicioSQL || !$fechaFinSQL)) {
    echo json_encode(['success'=>false, 'error'=>'Debes indicar el rango de fechas.']);
    exit;
}
if ($tipo == 'extra' && (!$horas || $horas <= 0)) {
    echo json_encode(['success'=>false, 'error'=>'Debes indicar las horas extra.']);
    exit;
}

// Revisa si ya tiene una solicitud pendiente para ese rango (solo para tipos con fechas)
if (in_array($tipo, ['vacaciones', 'permiso', 'baja', 'ausencia'])) {
    $timeCheck = microtime(true);

    
    $sqlCheck = "SELECT id FROM solicitudes WHERE empleado_id = ? AND tipo = ? 
        AND estado = 'pendiente'
        AND (
            (fecha_inicio <= ? AND fecha_fin >= ?) OR
            (fecha_inicio <= ? AND fecha_fin >= ?) OR
            (fecha_inicio >= ? AND fecha_fin <= ?)
        )";
    $stmtCheck = $pdo->prepare($sqlCheck);
    $stmtCheck->execute([
        $userId,
        $tipo,
        $fechaFinSQL, $fechaFinSQL,  
        $fechaInicioSQL, $fechaInicioSQL, 
        $fechaInicioSQL, $fechaFinSQL 
    ]);
    if ($stmtCheck->fetch()) {
        echo json_encode(['success'=>false, 'error'=>'Ya tienes una solicitud pendiente para este periodo. Puedes modificarla o cancelarla desde tu historial.']);
        exit;
    }
    
    $timeCheckEnd = microtime(true);
    error_log("DEBUG - Validación duplicados completada en " . round(($timeCheckEnd - $timeCheck) * 1000, 2) . "ms");
}

try {
    $timeInsert = microtime(true);

    
    // Para ausencias, agregar el tipo específico al comentario
    if ($tipo === 'ausencia' && $tipoAusencia) {
        $tiposAusencia = [
            'cita_medica' => 'Cita médica',
            'cuidado_familiar' => 'Cuidado familiar',
            'fallecimiento_familiar' => 'Fallecimiento de un familiar',
            'accidente_enfermedad_familiar' => 'Accidente o enfermedad grave de un familiar',
            'hospitalizacion_familiar' => 'Hospitalización o intervención quirúrgica de familiar',
            'matrimonio' => 'Matrimonio',
            'nacimiento_hijo' => 'Nacimiento de hijo/a',
            'mudanza' => 'Mudanza o cambio de domicilio'
        ];
        
        $tipoAusenciaTexto = $tiposAusencia[$tipoAusencia] ?? $tipoAusencia;
        $comentario = "Tipo: $tipoAusenciaTexto" . ($comentario ? " - $comentario" : "");
    }
    
    $pdo->beginTransaction();

    $sql = "INSERT INTO solicitudes
                (empleado_id, tipo, fecha_inicio, fecha_fin, medio_dia, horas, hora_inicio, hora_fin, comentario_empleado, archivo, estado)
            VALUES
                (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pendiente')";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        $userId,
        $tipo,
        $fechaInicioSQL,
        $fechaFinSQL,
        $medioDia,
        $horas,
        $horaInicio,
        $horaFin,
        $comentario,
        $archivo
    ]);

    $pdo->commit();

    // Crear notificación para administradores
    $empleado_nombre = $_SESSION['user_name'] ?? $_SESSION['nombre'] ?? 'Empleado';
    $mensaje = "Nueva solicitud de $tipo de $empleado_nombre";
    $url = "admin/ver-solicitudes";
    
    // Obtener todos los administradores y supervisores
    $stmt = $pdo->prepare("SELECT id FROM empleados WHERE rol IN ('admin', 'supervisor')");
    $stmt->execute();
    $admins = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Crear notificación para cada administrador
    foreach ($admins as $admin) {
        crearNotificacion($mensaje, $admin['id'], 'solicitud', $url);
    }

} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    exit;
}

$timeInsertEnd = microtime(true);
error_log("DEBUG - Inserción BD completada en " . round(($timeInsertEnd - $timeInsert) * 1000, 2) . "ms");

// --- Respuesta inmediata al cliente ---
echo json_encode(['success' => true]);

// --- Envío asíncrono de emails (después de responder al cliente) ---
if (function_exists('fastcgi_finish_request')) {
    fastcgi_finish_request(); // Envía la respuesta inmediatamente
}

// TEMPORALMENTE DESACTIVADO - El envío de emails está causando lentitud
/*
try {
    $timeEmail = microtime(true);

    
    $roles = ['admin', 'supervisor'];
    $placeholders = implode(',', array_fill(0, count($roles), '?'));
    $stmt = $pdo->prepare("SELECT email, nombre, apellidos FROM empleados WHERE rol IN ($placeholders) AND email IS NOT NULL AND email != ''");
    $stmt->execute($roles);
    $destinatarios = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (!empty($destinatarios)) {
        // Preparar asunto y cuerpo
        $empleado_nombre = $_SESSION['user_name'] ?? $_SESSION['nombre'] ?? 'Empleado';
        $asunto = "Nueva solicitud de $tipo";
        $body = "<p>El empleado <strong>$empleado_nombre</strong> ha registrado una solicitud de <b>$tipo</b>.</p>";
        
        if ($fechaInicio && $fechaFin) {
            $body .= "<p>Periodo: <b>" . date('d/m/Y', strtotime($fechaInicio)) . "</b> a <b>" . date('d/m/Y', strtotime($fechaFin)) . "</b></p>";
        }
        if ($horas) {
            $body .= "<p>Horas: <b>$horas</b>";
            if ($horaInicio && $horaFin) {
                $body .= " (de $horaInicio a $horaFin)";
            }
            $body .= "</p>";
        }
        if ($comentario) {
            $body .= "<p>Comentario: " . htmlspecialchars($comentario) . "</p>";
        }
        
        // Solo incluir enlace si la configuración está disponible
        if (isset($config['ruta_absoluta'])) {
            $body .= "<p><a href=\"{$config['ruta_absoluta']}index.php?page=admin/ver-solicitudes\">Ver solicitudes pendientes</a></p>";
        }

        // Enviar email a cada destinatario
        foreach ($destinatarios as $d) {
            if (!empty($d['email'])) {
                $to = $d['email'];
                $headers = "From: noreply@miempresa.com\r\n";
                $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
                mail($to, $asunto, $body, $headers);
            }
        }
        
        $timeEmailEnd = microtime(true);
        error_log("DEBUG - Emails enviados en " . round(($timeEmailEnd - $timeEmail) * 1000, 2) . "ms");
    }
} catch (Exception $e) {
    // Log del error pero no interrumpir el proceso
    error_log("Error enviando notificación de solicitud: " . $e->getMessage());
}
*/

$timeTotal = microtime(true) - $timeStart;
error_log("DEBUG - Tiempo total de procesamiento: " . round($timeTotal * 1000, 2) . "ms");

exit;
