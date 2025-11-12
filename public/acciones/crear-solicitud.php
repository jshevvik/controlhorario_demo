<?php

require_once __DIR__ . '/../../includes/init.php';

header('Content-Type: application/json');

// Verificar que el usuario esté autenticado y sea admin/supervisor
if (!isAdminOrSupervisor() || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Acceso denegado']);
    exit;
}

try {
    // Leer datos de FormData (no JSON)
    $empleado_id = $_POST['empleado_id'] ?? null;
    $tipo = $_POST['tipo'] ?? 'baja';
    $fecha_inicio = $_POST['fecha_inicio'] ?? null;
    $fecha_fin = $_POST['fecha_fin'] ?? null;
    $medio_dia = isset($_POST['medio_dia']) ? (int)$_POST['medio_dia'] : 0;
    $horas = isset($_POST['horas']) && $_POST['horas'] !== '' ? (float)$_POST['horas'] : null;
    $comentario_admin = $_POST['comentario_admin'] ?? '';
    $archivo = null;
    
    // Validar tipo de solicitud
    $tipos_validos = ['vacaciones', 'permiso', 'baja', 'extra', 'ausencia'];
    if (!in_array($tipo, $tipos_validos)) {
        throw new Exception('Tipo de solicitud inválido');
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
        
        $nombreArchivo = date('YmdHis') . '_' . uniqid() . '.' . $extension;
        $destino = $dir . $nombreArchivo;
        
        if (move_uploaded_file($_FILES['archivo']['tmp_name'], $destino)) {
            $archivo = $nombreArchivo;
        } else {
            throw new Exception('Error al subir el archivo');
        }
    }
    
    // Validaciones
    if (!$empleado_id || !$fecha_inicio || !$fecha_fin) {
        throw new Exception('Faltan datos requeridos');
    }
    
    if (strtotime($fecha_inicio) > strtotime($fecha_fin)) {
        throw new Exception('La fecha inicio debe ser menor o igual a la fecha fin');
    }
    
    // Verificar que el empleado exista
    $stmt = $pdo->prepare('SELECT id, nombre, apellidos, email FROM empleados WHERE id = ?');
    $stmt->execute([$empleado_id]);
    $empleado = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$empleado) {
        throw new Exception('El empleado no existe');
    }
    
    // Crear solicitud con estado aprobado
    $adminId = $_SESSION['empleado_id'];
    
    // PASO 1: Si es baja médica, eliminar vacaciones solapadas y devolver días
    if ($tipo === 'baja') {
        // Encontrar vacaciones aprobadas que se solapan
        $stmtVacaciones = $pdo->prepare('
            SELECT id, fecha_inicio, fecha_fin, medio_dia, horas
            FROM solicitudes
            WHERE empleado_id = :empleado_id
            AND tipo = "vacaciones"
            AND estado = "aprobado"
            AND (
                (fecha_inicio <= :fecha_fin AND fecha_fin >= :fecha_inicio)
            )
        ');
        
        $stmtVacaciones->execute([
            ':empleado_id' => $empleado_id,
            ':fecha_inicio' => $fecha_inicio,
            ':fecha_fin' => $fecha_fin
        ]);
        
        $vacaciones_solapadas = $stmtVacaciones->fetchAll(PDO::FETCH_ASSOC);
        
        // Procesar cada solicitud de vacaciones solapada
        foreach ($vacaciones_solapadas as $vac) {
            // Calcular días que se deben devolver
            $dia_inicio_vac = new DateTime($vac['fecha_inicio']);
            $dia_fin_vac = new DateTime($vac['fecha_fin']);
            $dia_inicio_baja = new DateTime($fecha_inicio);
            $dia_fin_baja = new DateTime($fecha_fin);
            
            // Calcular período de solapamiento
            $inicio_solapamiento = max($dia_inicio_vac, $dia_inicio_baja);
            $fin_solapamiento = min($dia_fin_vac, $dia_fin_baja);
            
            // Calcular días de solapamiento (incluir ambas fechas)
            $dias_solapados = $fin_solapamiento->diff($inicio_solapamiento)->days + 1;
            
            // Si la vacación es medio día, contar como 0.5
            if ($vac['medio_dia'] == 1) {
                $dias_solapados = 0.5;
            }
            
            // PASO 1.1: Actualizar saldo del empleado
            // Buscar el saldo de vacaciones en solicitudes_balances
            $stmtSaldo = $pdo->prepare('
                SELECT balance 
                FROM solicitudes_balances 
                WHERE empleado_id = :empleado_id 
                AND tipo = "vacaciones"
                LIMIT 1
            ');
            
            $stmtSaldo->execute([':empleado_id' => $empleado_id]);
            $saldo = $stmtSaldo->fetch(PDO::FETCH_ASSOC);
            
            if ($saldo !== false) {
                // Devolver los días de vacaciones que se eliminan
                $nuevo_balance = $saldo['balance'] + $dias_solapados;
                $stmtUpdateSaldo = $pdo->prepare('
                    UPDATE solicitudes_balances 
                    SET balance = :balance
                    WHERE empleado_id = :empleado_id
                    AND tipo = "vacaciones"
                ');
                $stmtUpdateSaldo->execute([
                    ':balance' => $nuevo_balance,
                    ':empleado_id' => $empleado_id
                ]);
            }
            
            // PASO 1.2: Reajustar las fechas de la solicitud de vacaciones
            // Si la baja cubre TODO el período de vacaciones, eliminar
            if ($dia_inicio_baja <= $dia_inicio_vac && $dia_fin_baja >= $dia_fin_vac) {
                // La baja cubre TODA la vacación, eliminarla completamente
                $stmtDeleteVac = $pdo->prepare('
                    DELETE FROM solicitudes 
                    WHERE id = :id
                ');
                $stmtDeleteVac->execute([':id' => $vac['id']]);
            } else if ($dia_inicio_baja <= $dia_inicio_vac) {
                // La baja cubre el INICIO de la vacación, mover fecha inicio
                $nueva_fecha_inicio = $dia_fin_baja->add(new DateInterval('P1D'))->format('Y-m-d');
                $stmtUpdateVac = $pdo->prepare('
                    UPDATE solicitudes 
                    SET fecha_inicio = :fecha_inicio
                    WHERE id = :id
                ');
                $stmtUpdateVac->execute([
                    ':fecha_inicio' => $nueva_fecha_inicio,
                    ':id' => $vac['id']
                ]);
            } else if ($dia_fin_baja >= $dia_fin_vac) {
                // La baja cubre el FINAL de la vacación, mover fecha fin
                $nueva_fecha_fin = $dia_inicio_baja->sub(new DateInterval('P1D'))->format('Y-m-d');
                $stmtUpdateVac = $pdo->prepare('
                    UPDATE solicitudes 
                    SET fecha_fin = :fecha_fin
                    WHERE id = :id
                ');
                $stmtUpdateVac->execute([
                    ':fecha_fin' => $nueva_fecha_fin,
                    ':id' => $vac['id']
                ]);
            } else {
                // La baja está DENTRO de la vacación, dividir en dos períodos
                // Período 1: desde inicio vacación hasta día anterior a inicio baja
                $fecha_fin_periodo1 = $dia_inicio_baja->sub(new DateInterval('P1D'))->format('Y-m-d');
                
                // Actualizar la solicitud original con el primer período
                $stmtUpdateVac = $pdo->prepare('
                    UPDATE solicitudes 
                    SET fecha_fin = :fecha_fin
                    WHERE id = :id
                ');
                $stmtUpdateVac->execute([
                    ':fecha_fin' => $fecha_fin_periodo1,
                    ':id' => $vac['id']
                ]);
                
                // Período 2: desde día después de fin baja hasta fin vacación
                $fecha_inicio_periodo2 = $dia_fin_baja->add(new DateInterval('P1D'))->format('Y-m-d');
                
                // Crear nueva solicitud para el segundo período
                $stmtInsertVac2 = $pdo->prepare('
                    INSERT INTO solicitudes 
                    (empleado_id, tipo, estado, fecha_inicio, fecha_fin, medio_dia, horas, comentario_empleado, comentario_admin, supervisor_id, fecha_respuesta, archivo)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), ?)
                ');
                
                $stmtInsertVac2->execute([
                    $empleado_id,
                    'vacaciones',
                    'aprobado',
                    $fecha_inicio_periodo2,
                    $vac['fecha_fin'],
                    $vac['medio_dia'],
                    $vac['horas'],
                    $vac['comentario_empleado'] ?? '',
                    $vac['comentario_admin'] ?? '',
                    $vac['supervisor_id'] ?? $adminId,
                    $vac['archivo'] ?? null
                ]);
            }
        }
    }
    
    // PASO 2: Crear la nueva solicitud (baja, vacaciones, permiso, etc)
    $stmt = $pdo->prepare('
        INSERT INTO solicitudes 
        (empleado_id, tipo, estado, fecha_inicio, fecha_fin, medio_dia, horas, comentario_empleado, comentario_admin, supervisor_id, fecha_respuesta, archivo)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), ?)
    ');
    
    $stmt->execute([
        $empleado_id,
        $tipo,
        'aprobado',
        $fecha_inicio,
        $fecha_fin,
        $medio_dia,
        $horas,
        '',
        $comentario_admin,
        $adminId,
        $archivo
    ]);
    
    $solicitudId = $pdo->lastInsertId();
    
    // Registrar creación en historial
    registrarCambioSolicitud(
        $solicitudId, 
        'crear', 
        null, 
        null, 
        null, 
        "Solicitud creada por administrador" . ($comentario_admin ? ": $comentario_admin" : "")
    );
    
    // Obtener nombre del admin
    $stmtAdmin = $pdo->prepare('SELECT nombre, apellidos FROM empleados WHERE id = ?');
    $stmtAdmin->execute([$adminId]);
    $admin = $stmtAdmin->fetch(PDO::FETCH_ASSOC);
    
    // Crear notificación para el empleado
    $tipo_mensajes = [
        'baja' => 'baja médica',
        'vacaciones' => 'solicitud de vacaciones',
        'permiso' => 'solicitud de permiso',
        'extra' => 'solicitud de horas extra',
        'ausencia' => 'solicitud de ausencia'
    ];
    
    $tipo_display = $tipo_mensajes[$tipo] ?? $tipo;
    
    $mensaje = sprintf(
        'Se ha creado una %s: %s - %s',
        $tipo_display,
        formatearFecha($fecha_inicio),
        formatearFecha($fecha_fin)
    );
    
    if ($comentario_admin) {
        $mensaje .= "\n" . $comentario_admin;
    }
    
    // Si se han eliminado vacaciones, agregar nota
    if ($tipo === 'baja' && !empty($vacaciones_solapadas)) {
        $mensaje .= "\n\nNOTA: Se han eliminado " . count($vacaciones_solapadas) . " solicitud(es) de vacaciones solapada(s) y se han devuelto los días al saldo.";
    }
    
    $stmtNotif = $pdo->prepare('
        INSERT INTO notificaciones (empleado_id, tipo, mensaje, url)
        VALUES (?, ?, ?, ?)
    ');
    
    $url = $config['ruta_absoluta'] . 'solicitudes';
    $notif_type = $tipo . '_creada';
    $stmtNotif->execute([
        $empleado_id,
        $notif_type,
        $mensaje,
        $url
    ]);
    
    echo json_encode([
        'success' => true,
        'message' => ucfirst($tipo_display) . ' creada correctamente',
        'solicitud_id' => $solicitudId
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
