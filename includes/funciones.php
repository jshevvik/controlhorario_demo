<?php

// Devuelve true si hay un usuario logueado
function isLoggedIn() {
    return !empty($_SESSION['empleado_id']);
}

// Protege rutas que requieren sesión
function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit;
    }
}

// Obtiene el ID de empleado logueado
function getEmpleadoId() {
    return $_SESSION['empleado_id'] ?? null;
}

// Devuelve el array del empleado logueado 
function getEmpleado() {
    global $pdo;
    if (empty($_SESSION['empleado_id'])) return null;
    $stmt = $pdo->prepare("SELECT * FROM empleados WHERE id = ?");
    $stmt->execute([ $_SESSION['empleado_id'] ]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

// Devuelve todos los empleados (para administradores)
function getTodosLosEmpleados() {
    global $pdo;
    $stmt = $pdo->prepare("SELECT id, nombre, usuario, email, rol FROM empleados ORDER BY nombre ASC");
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// True si el usuario logueado es admin 
function isAdmin() {
    $emp = getEmpleado();
    return $emp && $emp['rol'] === 'admin';
}

// True si el usuario logueado es super admin
function isSuperAdmin() {
    $emp = getEmpleado();
    return $emp && $emp['rol'] === 'admin' && !empty($emp['es_super_admin']);
}

// True si el usuario logueado es supervisor
function isSupervisor() {
    $emp = getEmpleado();
    return $emp && $emp['rol'] === 'supervisor';
}

// True si el usuario logueado es admin o supervisor
function isAdminOrSupervisor() {
    $emp = getEmpleado();
    return $emp && in_array($emp['rol'], ['admin', 'supervisor']);
}

// True si el usuario puede crear/eliminar empleados (solo admin)
function canManageEmployees() {
    return isAdmin();
}

/**
 * Elimina un empleado y todos sus registros relacionados
 * @param int $empleadoId ID del empleado a eliminar
 * @param int $adminId ID del admin que realiza la acción (para auditoría)
 * @return array ['success' => bool, 'message' => string]
 */
function eliminarEmpleado($empleadoId, $adminId = null) {
    global $pdo;
    
    try {
        // Verificar que el empleado existe
        $stmt = $pdo->prepare("SELECT id, nombre, apellidos, usuario, rol, es_super_admin FROM empleados WHERE id = ?");
        $stmt->execute([$empleadoId]);
        $empleado = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$empleado) {
            return ['success' => false, 'message' => 'Empleado no encontrado'];
        }
        
        // Verificar que no se intente eliminar a sí mismo
        if ($empleadoId === $_SESSION['empleado_id']) {
            return ['success' => false, 'message' => 'No puedes eliminar tu propia cuenta'];
        }
        
        // PROTECCIÓN: No se puede eliminar al super admin
        if (!empty($empleado['es_super_admin'])) {
            return ['success' => false, 'message' => 'No se puede eliminar al super administrador'];
        }
        
        // Solo admin puede eliminar a otro admin
        if ($empleado['rol'] === 'admin' && !isAdmin()) {
            return ['success' => false, 'message' => 'No tienes permisos para eliminar un administrador'];
        }
        
        // Iniciar transacción
        $pdo->beginTransaction();
        
        // Eliminar registros relacionados en orden (de dependientes a principales)
        $pdo->prepare("DELETE FROM notificaciones WHERE empleado_id = ?")->execute([$empleadoId]);
        $pdo->prepare("DELETE FROM fichajes WHERE empleado_id = ?")->execute([$empleadoId]);
        $pdo->prepare("DELETE FROM solicitudes_balances WHERE empleado_id = ?")->execute([$empleadoId]);
        $pdo->prepare("DELETE FROM solicitudes WHERE empleado_id = ?")->execute([$empleadoId]);
        $pdo->prepare("DELETE FROM permisos_empleados WHERE empleado_id = ?")->execute([$empleadoId]);
        $pdo->prepare("DELETE FROM horarios_empleados WHERE empleado_id = ?")->execute([$empleadoId]);
        
        // Eliminar el empleado
        $pdo->prepare("DELETE FROM empleados WHERE id = ?")->execute([$empleadoId]);
        
        // Registrar en auditoría si está disponible
        if ($adminId && function_exists('registrarActividadSeguridad')) {
            registrarActividadSeguridad(
                'eliminar_empleado',
                $empleado['usuario'] . ' (' . $empleado['nombre'] . ' ' . $empleado['apellidos'] . ')',
                'Empleado eliminado exitosamente',
                $adminId
            );
        }
        
        $pdo->commit();
        
        return [
            'success' => true, 
            'message' => 'Empleado eliminado correctamente'
        ];
        
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log("Error al eliminar empleado ID $empleadoId: " . $e->getMessage());
        return [
            'success' => false, 
            'message' => 'Error al eliminar el empleado: ' . $e->getMessage()
        ];
    }
}

// Protege una ruta para sólo administradores 
function requireAdmin() {
    if (!isAdmin()) {
        
        header('Location: ../login.php');
        exit;
    }
}

// Función para registrar actividad de seguridad usando tabla auditoria existente
function registrarActividadSeguridad($accion, $usuario_afectado, $resultado, $admin_id) {
    global $pdo;
    try {
        // Usar tabla auditoria existente con formato adaptado
        $detalle = "Usuario: $usuario_afectado | Resultado: $resultado | IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'unknown');
        
        $stmt = $pdo->prepare("INSERT INTO auditoria (usuario_id, accion, detalle) VALUES (?, ?, ?)");
        $stmt->execute([
            $admin_id,
            $accion,
            $detalle
        ]);
        return true;
    } catch (Exception $e) {
        error_log("Error al registrar actividad de seguridad: " . $e->getMessage());
        error_log("LOG_SEGURIDAD_FALLBACK: [$accion] $usuario_afectado - $resultado (Admin: $admin_id)");
        return false;
    }
}

// Función para registrar inicios de sesión usando tabla auditoria existente
function registrarLogin($usuario, $empleado_id = null, $exitoso = true) {
    global $pdo;
    try {
        $accion = $exitoso ? 'login_exitoso' : 'login_fallido';
        $detalle = "Usuario: $usuario | IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'unknown') . " | UserAgent: " . ($_SERVER['HTTP_USER_AGENT'] ?? 'unknown');
        
        $stmt = $pdo->prepare("INSERT INTO auditoria (usuario_id, accion, detalle) VALUES (?, ?, ?)");
        $stmt->execute([
            $empleado_id,
            $accion,
            $detalle
        ]);
        return true;
    } catch (Exception $e) {
        error_log("Error al registrar login: " . $e->getMessage());
        error_log("LOGIN_LOG_FALLBACK: Usuario: $usuario, Exitoso: " . ($exitoso ? 'SI' : 'NO') . ", Empleado ID: $empleado_id");
        return false;
    }
}

// Protege una ruta para administradores y supervisores
function requireAdminOrSupervisor() {
    if (!isAdminOrSupervisor()) {
        header('Location: ../login.php');
        exit;
    }
}

// ========================================
// FUNCIONES DE PERMISOS GRANULARES
// ========================================

/**
 * Obtiene los permisos de un empleado
 * @param int $empleadoId ID del empleado
 * @return array|null Permisos del empleado
 */
function getPermisosEmpleado($empleadoId = null) {
    global $pdo;
    
    if ($empleadoId === null) {
        $empleadoId = $_SESSION['empleado_id'] ?? 0;
    }
    
    if (!$empleadoId) {
        return null;
    }
    
    try {
        $stmt = $pdo->prepare("SELECT * FROM permisos_empleados WHERE empleado_id = ?");
        $stmt->execute([$empleadoId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error al obtener permisos: " . $e->getMessage());
        return null;
    }
}

/**
 * Verifica si el empleado tiene un permiso específico
 * @param string $permiso Nombre del permiso
 * @param int|null $empleadoId ID del empleado (null = usuario actual)
 * @return bool
 */
function tienePermiso($permiso, $empleadoId = null) {
    // Admin siempre tiene todos los permisos
    if (isAdmin()) {
        return true;
    }
    
    $permisos = getPermisosEmpleado($empleadoId);
    
    if (!$permisos) {
        return false;
    }
    
    return !empty($permisos[$permiso]);
}

/**
 * Registra un cambio en una solicitud
 * @param int $solicitudId
 * @param string $accion
 * @param string|null $campo
 * @param string|null $valorAnterior
 * @param string|null $valorNuevo
 * @param string|null $comentario
 */
function registrarCambioSolicitud($solicitudId, $accion, $campo = null, $valorAnterior = null, $valorNuevo = null, $comentario = null) {
    global $pdo;
    
    $empleadoId = $_SESSION['empleado_id'] ?? null;
    $ipAddress = $_SERVER['REMOTE_ADDR'] ?? '';
    
    try {
        $stmt = $pdo->prepare("
            INSERT INTO solicitudes_historial 
            (solicitud_id, empleado_id, accion, campo_modificado, valor_anterior, valor_nuevo, comentario, ip_address) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $solicitudId,
            $empleadoId,
            $accion,
            $campo,
            $valorAnterior,
            $valorNuevo,
            $comentario,
            $ipAddress
        ]);
    } catch (PDOException $e) {
        error_log("Error al registrar cambio en solicitud: " . $e->getMessage());
    }
}

/**
 * Obtiene el historial de cambios de una solicitud
 * @param int $solicitudId
 * @return array
 */
function getHistorialSolicitud($solicitudId) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            SELECT 
                h.*,
                e.nombre,
                e.apellidos,
                e.rol
            FROM solicitudes_historial h
            LEFT JOIN empleados e ON h.empleado_id = e.id
            WHERE h.solicitud_id = ?
            ORDER BY h.fecha DESC
        ");
        $stmt->execute([$solicitudId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error al obtener historial de solicitud: " . $e->getMessage());
        return [];
    }
}

/**
 * Devuelve la fecha y hora actual en formato:
 * 9 junio 2025 — 09:20
 */
function getFechaActual(): string
{

    $meses = [
        1 => 'Enero',   2 => 'Febrero', 3 => 'Marzo',     4 => 'Abril',
        5 => 'Mayo',    6 => 'Junio',   7 => 'Julio',     8 => 'Agosto',
        9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre'
    ];

    $dia  = date('j');
    $mes  = $meses[(int)date('n')];
    $anyo = date('Y');
    $hora = date('H:i');

    return "{$dia} de {$mes} de {$anyo} — {$hora}";
}


// Generar avatar aleatorio de Gravatar
function getAvatarUrl(string $email, int $size = 120): string {
    $hash = md5(strtolower(trim($email)));
    return "https://www.gravatar.com/avatar/{$hash}?s={$size}&d=identicon";
}

// Crear notificaciones
function crearNotificacion ($mensaje, $empleadoId=null, $tipo = 'info', $url= null){
    global $pdo;
    error_log("DEBUG - crearNotificacion llamada: mensaje='$mensaje', empleadoId=$empleadoId, tipo='$tipo', url='$url'");
    try {
        $sql = "INSERT INTO notificaciones (empleado_id, tipo, mensaje, url) VALUES (?,?,?,?)";
        $st = $pdo->prepare($sql);
        $result = $st->execute([$empleadoId, $tipo, $mensaje, $url]);
        error_log("DEBUG - Notificación insertada en BD. Resultado: " . ($result ? 'exitoso' : 'falló'));
        if (!$result) {
            error_log("DEBUG - Error en BD: " . print_r($st->errorInfo(), true));
        }
        return $result;
    } catch (Exception $e) {
        error_log("DEBUG - Excepción en crearNotificacion: " . $e->getMessage());
        return false;
    }
}

// Obtener color Bootstrap según tipo de notificación
function getColorNotificacion($tipo) {
    return match($tipo) {
        'alerta'     => 'danger',
        'aprobacion' => 'success',
        'solicitud'  => 'info',
        default      => 'primary'
    };
}

// Obtener icono Bootstrap Icon según tipo de notificación
function getIconoNotificacion($tipo) {
    return match($tipo) {
        'alerta'     => 'bi-exclamation-triangle-fill',
        'aprobacion' => 'bi-check-circle-fill',
        'solicitud'  => 'bi-bell-fill',
        default      => 'bi-info-circle-fill'
    };
}

// Obtener resumen de solicitudes del empleado en el año actual
function getResumenSolicitudes($pdo, $empleadoId) {
    $anioActual = date('Y');
    $resumen = [];
    
    $tipos = ['vacaciones', 'ausencias', 'bajas'];
    
    foreach ($tipos as $tipo) {
        // Solicitudes aprobadas
        $sql = "SELECT COUNT(*) as total, SUM(DATEDIFF(fecha_fin, fecha_inicio) + 1) as dias 
                FROM solicitudes 
                WHERE empleado_id = ? AND tipo = ? AND YEAR(fecha_inicio) = ? AND estado = 'aprobado'";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$empleadoId, $tipo, $anioActual]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $diasAprobados = $row['dias'] ?? 0;
        $totalAprobadas = $row['total'] ?? 0;
        
        // Solicitudes pendientes
        $sqlPendientes = "SELECT COUNT(*) as total, SUM(DATEDIFF(fecha_fin, fecha_inicio) + 1) as dias 
                          FROM solicitudes 
                          WHERE empleado_id = ? AND tipo = ? AND YEAR(fecha_inicio) = ? AND estado = 'pendiente'";
        $stmtPendientes = $pdo->prepare($sqlPendientes);
        $stmtPendientes->execute([$empleadoId, $tipo, $anioActual]);
        $rowPendientes = $stmtPendientes->fetch(PDO::FETCH_ASSOC);
        
        $diasPendientes = $rowPendientes['dias'] ?? 0;
        $totalPendientes = $rowPendientes['total'] ?? 0;
        
        $resumen[$tipo] = [
            'total' => $totalAprobadas,
            'dias' => $diasAprobados,
            'pendientes' => $totalPendientes,
            'dias_pendientes' => $diasPendientes,
            'dias_totales' => ($diasAprobados + $diasPendientes)
        ];
    }
    
    return $resumen;
}

function getHorasExtra($pdo, $empleadoId, $anio = null) {
    if (!$anio) {
        $anio = date('Y');
    }
    
    // Consulta simplificada para calcular horas extra
    $sql = "SELECT 
                COALESCE(SUM(
                    CASE 
                        WHEN TIME_TO_SEC(tiempo_trabajado) > (horas_dia * 3600) 
                        THEN (TIME_TO_SEC(tiempo_trabajado) - (horas_dia * 3600)) / 3600
                        ELSE 0
                    END
                ), 0) as horas_extra
            FROM (
                SELECT 
                    DATE(f.hora) as fecha,
                    TIMEDIFF(
                        MAX(CASE WHEN f.tipo = 'salida' THEN f.hora END), 
                        MIN(CASE WHEN f.tipo = 'entrada' THEN f.hora END)
                    ) as tiempo_trabajado,
                    COALESCE(h.horas_dia, 8) as horas_dia
                FROM fichajes f
                LEFT JOIN horarios h ON f.empleado_id = h.empleado_id 
                    AND h.dia = DAYNAME(f.hora)
                WHERE f.empleado_id = ? 
                    AND f.tipo IN ('entrada', 'salida')
                    AND YEAR(f.hora) = ?
                GROUP BY DATE(f.hora)
            ) as daily_work";
    
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$empleadoId, $anio]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $horasExtra = floatval($result['horas_extra'] ?? 0);
        return [
            'horas' => intval(floor($horasExtra)),
            'minutos' => intval(round(($horasExtra - floor($horasExtra)) * 60)),
            'total' => round($horasExtra, 2)
        ];
    } catch (Exception $e) {
        return [
            'horas' => 0,
            'minutos' => 0,
            'total' => 0
        ];
    }
}

// Devuelve el último fichaje del día para un empleado (o null si no hay)
function getUltimoFichajeHoy($empleadoId) {
    global $pdo;
    $sql = "SELECT * FROM fichajes WHERE empleado_id = ? AND DATE(hora) = CURDATE() ORDER BY hora DESC LIMIT 1";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$empleadoId]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function appendCacheBuster($url) {

    $delimiter = (strpos($url, '?') !== false) ? '&' : '?';
    return $url . $delimiter . 'v=' . time();
}

function traducirFecha(string $fecha): string {
    static $dias_es = [
        'Monday' => 'Lunes', 'Tuesday' => 'Martes', 'Wednesday' => 'Miércoles',
        'Thursday' => 'Jueves', 'Friday' => 'Viernes', 'Saturday' => 'Sábado', 'Sunday' => 'Domingo'
    ];
    
    static $meses_es = [
        'January' => 'enero', 'February' => 'febrero', 'March' => 'marzo', 
        'April' => 'abril', 'May' => 'mayo', 'June' => 'junio', 'July' => 'julio',
        'August' => 'agosto', 'September' => 'septiembre', 'October' => 'octubre',
        'November' => 'noviembre', 'December' => 'diciembre'
    ];
    
    $timestamp = strtotime($fecha);
    return sprintf('%s %s de %s de %s',
        $dias_es[date('l', $timestamp)],
        date('j', $timestamp),
        $meses_es[date('F', $timestamp)],
        date('Y', $timestamp)
    );
}

function calcularDuracion(?string $inicio, ?string $fin = null): string {
    if (!$inicio) return '0h 0m';
    
    $start = new DateTime($inicio);
    $end = $fin ? new DateTime($fin) : new DateTime();
    
    $interval = $start->diff($end);
    return $interval->format('%hh %im');
}

function calcularMinutosDiferencia(string $inicio, string $fin): int {
    $start = new DateTime($inicio);
    $end = new DateTime($fin);
    $diff = $start->diff($end);
    return ($diff->h * 60) + $diff->i;
}


function obtenerSaldos($pdo, $empleadoId) {
    // Configura los máximos
    $diasVacaciones = 24;
    //$diasPermiso    = 4;

    // Días usados VACACIONES - Contar solo días laborables
    $stmt = $pdo->prepare("SELECT id, fecha_inicio, fecha_fin FROM solicitudes 
                           WHERE empleado_id = ? AND tipo = 'vacaciones' AND estado = 'aprobado'");
    $stmt->execute([$empleadoId]);
    $solicitudes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $usadosVacaciones = 0;
    foreach ($solicitudes as $sol) {
        $usadosVacaciones += contarDiasLaborables($sol['fecha_inicio'], $sol['fecha_fin'], $pdo);
    }

    // Días usados PERMISOS
    //$stmt = $pdo->prepare("SELECT SUM(DATEDIFF(fecha_fin, fecha_inicio) + 1)
                           //FROM solicitudes WHERE empleado_id = ? AND tipo = 'permiso' AND estado = 'aprobado'");
    //$stmt->execute([$empleadoId]);
    //$usadosPermiso = (int)($stmt->fetchColumn() ?: 0);

    // Días de BAJA MÉDICA usados - Contar solo días laborables
    $stmt = $pdo->prepare("SELECT id, fecha_inicio, fecha_fin FROM solicitudes 
                           WHERE empleado_id = ? AND tipo = 'baja' AND estado = 'aprobado'");
    $stmt->execute([$empleadoId]);
    $solicitudes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $bajaUsada = 0;
    foreach ($solicitudes as $sol) {
        $bajaUsada += contarDiasLaborables($sol['fecha_inicio'], $sol['fecha_fin'], $pdo);
    }

    // Horas de EXTRA usadas (no requiere cambio)
    $stmt = $pdo->prepare("SELECT SUM(horas)
                           FROM solicitudes WHERE empleado_id = ? AND tipo = 'extra' AND estado = 'aprobado'");
    $stmt->execute([$empleadoId]);
    $extraUsada = (float)($stmt->fetchColumn() ?: 0);

    // Días de AUSENCIA usados - Contar solo días laborables
    $stmt = $pdo->prepare("SELECT id, fecha_inicio, fecha_fin FROM solicitudes 
                           WHERE empleado_id = ? AND tipo = 'ausencia' AND estado = 'aprobado'");
    $stmt->execute([$empleadoId]);
    $solicitudes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $ausenciaUsada = 0;
    foreach ($solicitudes as $sol) {
        $ausenciaUsada += contarDiasLaborables($sol['fecha_inicio'], $sol['fecha_fin'], $pdo);
    }

    return [
       
        'vacaciones' => ['usado' => $usadosVacaciones, 'max' => $diasVacaciones],
        //'permiso'    => ['usado' => $usadosPermiso,    'max' => $diasPermiso],
        // Solo lo usado (porque no hay máximo)
        'baja'       => $bajaUsada,
        'extra'      => $extraUsada,
        'ausencia'   => $ausenciaUsada
    ];
}



function saldoFmtVisual($saldo, $tipo = 'd') {
    // Para vacaciones y permisos: array con usado y max
    if (is_array($saldo) && isset($saldo['usado']) && isset($saldo['max'])) {
        $restante = $saldo['max'] - $saldo['usado'];
        if ($restante < 0) $restante = 0;
        return "{$restante}/{$saldo['max']} {$tipo}";
    }
    // Para el resto: número simple
    if (is_numeric($saldo)) {
        if ($tipo === ' h.' || $tipo === 'h') {
            return "{$saldo} h.";
        } else {
            return "{$saldo} d.";
        }
    }
    return "0 {$tipo}";
}



function guardarSaldo(PDO $pdo, int $empleadoId, string $tipo, $valor): bool
{
    $sql = 'INSERT INTO solicitudes_balances (empleado_id,tipo,balance)
            VALUES (?,?,?)
            ON DUPLICATE KEY UPDATE balance = VALUES(balance)';
    return $pdo->prepare($sql)->execute([$empleadoId, $tipo, $valor]);
}


function formatSaldo($valor, string $unidad): string
{
    if ($valor === null) return '&infin;';
    return $valor . ' ' . $unidad;
}

/**
 * Calcula el número de días laborables (lunes-viernes) excluyendo festivos
 * entre dos fechas
 * @param DateTime|string $fechaInicio Fecha de inicio (puede ser string YYYY-MM-DD o DateTime)
 * @param DateTime|string $fechaFin Fecha de fin (puede ser string YYYY-MM-DD o DateTime)
 * @param PDO $pdo Conexión a BD para obtener festivos
 * @return int Número de días laborables
 */
function contarDiasLaborables($fechaInicio, $fechaFin, ?PDO $pdo = null): int {
    // Convertir strings a DateTime si es necesario
    if (is_string($fechaInicio)) {
        $fechaInicio = new DateTime($fechaInicio);
    }
    if (is_string($fechaFin)) {
        $fechaFin = new DateTime($fechaFin);
    }
    
    // Obtener festivos si se proporciona $pdo
    $festivos = [];
    if ($pdo) {
        try {
            $stmt = $pdo->query("SELECT fecha FROM festivos");
            $festivos = $stmt->fetchAll(PDO::FETCH_COLUMN);
        } catch (Exception $e) {
            // Si no puede obtener festivos, continúa sin ellos
            error_log("Error obteniendo festivos: " . $e->getMessage());
        }
    }
    
    $diasLaborables = 0;
    $fecha = clone $fechaInicio;
    
    // Iterar desde la fecha de inicio hasta la de fin (inclusive)
    while ($fecha <= $fechaFin) {
        $dayOfWeek = (int)$fecha->format('w'); // 0 = domingo, 1 = lunes, 6 = sábado
        $fechaStr = $fecha->format('Y-m-d');
        
        // Contar si es día laborable (lunes=1 a viernes=5) y no es festivo
        if ($dayOfWeek >= 1 && $dayOfWeek <= 5 && !in_array($fechaStr, $festivos)) {
            $diasLaborables++;
        }
        
        // Avanzar al siguiente día
        $fecha->add(new DateInterval('P1D'));
    }
    
    return $diasLaborables;
}


function obtenerFestivos(
    PDO $pdo,
    int $dias      = 30,
    ?string $region= null 
): array {
    error_log("DEBUG obtenerFestivos: region = " . ($region ?? 'NULL') . ", dias = " . $dias);
    
    if ($region === null) {
        // Si no hay región, mostrar todos los festivos nacionales
        $sql = 'SELECT nombre, fecha, alcance, region
                  FROM festivos
                 WHERE fecha BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL ? DAY)
                   AND alcance = "nacional"
                 ORDER BY fecha
                 LIMIT 10';
        $st = $pdo->prepare($sql);
        $st->execute([$dias]);
        error_log("DEBUG obtenerFestivos: SQL sin región = " . $sql);
    } else {
        // Si hay región, mostrar nacionales + regionales + locales que coincidan
        $sql = 'SELECT nombre, fecha, alcance, region
                  FROM festivos
                 WHERE fecha BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL ? DAY)
                   AND (
                        alcance = "nacional"
                     OR (alcance = "autonomico" AND region = ?)
                     OR (alcance = "local"      AND region = ?)
                   )
                 ORDER BY fecha
                 LIMIT 10';
        $st = $pdo->prepare($sql);
        $st->execute([$dias, $region, $region]);
        error_log("DEBUG obtenerFestivos: SQL con región = " . $sql);
    }
    
    $result = $st->fetchAll(PDO::FETCH_ASSOC);
    error_log("DEBUG obtenerFestivos: resultado = " . print_r($result, true));
    return $result;
}

/**
 * Obtiene TODOS los festivos para mostrar en el calendario
 * Sin limitaciones de tiempo ni cantidad
 */
function obtenerFestivosCalendario(PDO $pdo, ?string $region = null): array {
    if ($region === null) {
        // Si no hay región, mostrar todos los festivos nacionales
        $sql = 'SELECT nombre, fecha, alcance, region
                  FROM festivos
                 WHERE alcance = "nacional"
                 ORDER BY fecha';
        $st = $pdo->prepare($sql);
        $st->execute();
    } else {
        // Si hay región, mostrar nacionales + regionales + locales que coincidan
        $sql = 'SELECT nombre, fecha, alcance, region
                  FROM festivos
                 WHERE (
                        alcance = "nacional"
                     OR (alcance = "autonomico" AND region = ?)
                     OR (alcance = "local"      AND region = ?)
                   )
                 ORDER BY fecha';
        $st = $pdo->prepare($sql);
        $st->execute([$region, $region]);
    }
    
    return $st->fetchAll(PDO::FETCH_ASSOC);
}

function obtenerProximosEventos(PDO $pdo, int $empleadoId, int $dias = 30, ?string $region = null): array {
    $eventos = [];
    
    // Obtener festivos usando la función existente
    $festivos = obtenerFestivos($pdo, $dias, $region);
    foreach ($festivos as $festivo) {
        $eventos[] = [
            'tipo' => 'festivo',
            'titulo' => $festivo['nombre'],
            'fecha' => $festivo['fecha'],
            'fecha_inicio' => $festivo['fecha'],
            'fecha_fin' => $festivo['fecha'],
            'icono' => 'bi-calendar2-date-fill',
            'color' => '#dc3545',
            'colorTitulo' => '#dc3545'
        ];
    }
    
    // Obtener eventos del calendario (para todos o específicos del empleado) EXCLUYENDO festivos
    try {
        $sql_eventos = "SELECT * FROM eventos_calendario 
                       WHERE tipo = 'evento' 
                       AND (empleado_id IS NULL OR empleado_id = :empleado_id)
                       AND fecha_inicio BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL :dias DAY)
                       ORDER BY fecha_inicio
                       LIMIT 10";
        $stmt_eventos = $pdo->prepare($sql_eventos);
        $stmt_eventos->execute([
            'empleado_id' => $empleadoId,
            'dias' => $dias
        ]);
        $eventos_calendario = $stmt_eventos->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($eventos_calendario as $evento) {
            $eventos[] = [
                'tipo' => 'evento',
                'titulo' => $evento['titulo'],
                'fecha' => $evento['fecha_inicio'],
                'fecha_inicio' => $evento['fecha_inicio'],
                'fecha_fin' => $evento['fecha_fin'],
                'descripcion' => $evento['descripcion'] ?? null,
                'icono' => 'bi-calendar-plus',
                'color' => $evento['color'] ?? '#007bff',
                'colorTitulo' => $evento['color'] ?? '#007bff',
                'para_todos' => $evento['empleado_id'] === null
            ];
        }
    } catch (Exception $e) {
        error_log("Error al obtener eventos del calendario en próximos eventos: " . $e->getMessage());
    }
    
    // Obtener solicitudes aprobadas del empleado
    $sql = "SELECT tipo, fecha_inicio, fecha_fin, horas, hora_inicio, hora_fin, comentario_admin as motivo, comentario_empleado
            FROM solicitudes 
            WHERE empleado_id = :empleado_id 
              AND estado = 'aprobado'
              AND fecha_inicio BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL :dias DAY)
            ORDER BY fecha_inicio
            LIMIT 15";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        'empleado_id' => $empleadoId,
        'dias' => $dias
    ]);
    
    $solicitudes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($solicitudes as $solicitud) {
        $tipoTextos = [
            'vacaciones' => 'Vacaciones',
            'permiso' => 'Permiso',
            'baja' => 'Baja médica',
            'extra' => 'Horas extras',
            'ausencia' => 'Ausencia'
        ];
        
        $iconos = [
            'vacaciones' => 'bi-umbrella-fill',
            'permiso' => 'bi-clock-history',
            'baja' => 'bi-thermometer-half',
            'extra' => 'bi-alarm',
            'ausencia' => 'bi-person-dash'
        ];
        
        $colores = [
            'vacaciones' => 'primary',  // azul
            'permiso' => 'info',        // azul claro  
            'baja' => 'success',        // verde
            'extra' => 'warning',       // amarillo
            'ausencia' => 'danger'      // rojo
        ];
        
        $titulo = $tipoTextos[$solicitud['tipo']] ?? ucfirst($solicitud['tipo']);
        
        // Para horas extras, mostrar las horas
        if ($solicitud['tipo'] === 'extra') {
            $titulo .= ' (' . $solicitud['horas'] . ' h.';
            if ($solicitud['hora_inicio'] && $solicitud['hora_fin']) {
                $titulo .= ' ' . substr($solicitud['hora_inicio'], 0, 5) . '-' . substr($solicitud['hora_fin'], 0, 5);
            }
            $titulo .= ')';
        }
        
        $eventos[] = [
            'tipo' => $solicitud['tipo'],
            'titulo' => $titulo,
            'fecha' => $solicitud['fecha_inicio'],
            'fecha_inicio' => $solicitud['fecha_inicio'],
            'fecha_fin' => $solicitud['fecha_fin'],
            'horas' => $solicitud['horas'] ?? null,
            'hora_inicio' => $solicitud['hora_inicio'] ?? null,
            'hora_fin' => $solicitud['hora_fin'] ?? null,
            'motivo' => $solicitud['motivo'] ?? null,
            'icono' => $iconos[$solicitud['tipo']] ?? 'bi-calendar-event',
            'color' => $colores[$solicitud['tipo']] ?? 'secondary',
            'colorTitulo' => $colores[$solicitud['tipo']] ?? 'secondary'
        ];
    }
    
    // Ordenar todos los eventos por fecha
    usort($eventos, function($a, $b) {
        return strtotime($a['fecha']) - strtotime($b['fecha']);
    });
    
    return array_slice($eventos, 0, 10); // Limitar a 10 eventos
}

/*  eventos para FullCalendar  */
function getEventosCalendario($pdo, $empleadoId) {
    $eventos = [];
    
    // Obtener solicitudes del empleado
    $sql = "SELECT 
                tipo, 
                estado, 
                fecha_inicio AS start, 
                fecha_fin AS end,
                horas, 
                hora_inicio,
                hora_fin,
                comentario_admin AS motivo,
                comentario_empleado
            FROM solicitudes
            WHERE empleado_id = :empleado_id
            ORDER BY fecha_inicio DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['empleado_id' => $empleadoId]);
    $solicitudes = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($solicitudes as $row) {
        $eventos[] = [
            "title" => ucfirst($row["tipo"]),
            "tipo" => $row["tipo"],
            "estado" => $row["estado"],
            "start" => $row["start"],
            "end" => $row["end"],
            "horas" => $row["horas"] ?? null,
            "hora_inicio" => $row["hora_inicio"] ?? null,
            "hora_fin" => $row["hora_fin"] ?? null,
            "motivo" => $row["motivo"] ?? null,
            "comentario_empleado" => $row["comentario_empleado"] ?? null,
            "categoria" => "solicitud"
        ];
    }
    
    // Obtener festivos
    try {
        $sql_festivos = "SELECT fecha, nombre FROM festivos ORDER BY fecha";
        $stmt_festivos = $pdo->query($sql_festivos);
        $festivos = $stmt_festivos->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($festivos as $festivo) {
            $eventos[] = [
                "title" => $festivo["nombre"],
                "start" => $festivo["fecha"],
                "end" => $festivo["fecha"],
                "allDay" => true,
                "color" => "#dc3545", 
                "categoria" => "festivo",
                "tipo" => "festivo"
            ];
        }
    } catch (Exception $e) {
        error_log("Error al obtener festivos: " . $e->getMessage());
    }
    
    // Obtener eventos del calendario (para todos o específicos del empleado) EXCLUYENDO festivos
    try {
        $sql_eventos = "SELECT * FROM eventos_calendario 
                       WHERE tipo = 'evento' 
                       AND (empleado_id IS NULL OR empleado_id = :empleado_id)
                       ORDER BY fecha_inicio";
        $stmt_eventos = $pdo->prepare($sql_eventos);
        $stmt_eventos->execute(['empleado_id' => $empleadoId]);
        $eventos_calendario = $stmt_eventos->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($eventos_calendario as $evento) {
            $eventos[] = [
                "title" => $evento["titulo"],
                "start" => $evento["fecha_inicio"],
                "end" => $evento["fecha_fin"],
                "color" => $evento["color"] ?? "#007bff",
                "allDay" => true,
                "categoria" => "evento",
                "tipo" => "evento",
                "descripcion" => $evento["descripcion"] ?? null,
                "para_todos" => $evento["empleado_id"] === null
            ];
        }
    } catch (Exception $e) {
        error_log("Error al obtener eventos del calendario: " . $e->getMessage());
    }
    
    return $eventos;
}

function obtenerHorariosEmpleado($pdo, $empleadoId) {
    
    $stmt = $pdo->prepare("SELECT dia, hora_inicio, hora_fin, hora_inicio_tarde, hora_fin_tarde, horario_partido FROM horarios_empleados WHERE empleado_id = ?");
    $stmt->execute([$empleadoId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function toSQLDate($str) {
    // Convierte DD/MM/YYYY a YYYY-MM-DD
    if (empty($str)) {
        return null;
    }
    
    // Si ya está en formato SQL, devolverlo tal como está
    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $str)) {
        return $str;
    }
    
    // Convertir DD/MM/YYYY a YYYY-MM-DD
    $partes = explode('/', $str);
    if(count($partes) === 3) {
        // Validar que los elementos sean números
        if (is_numeric($partes[0]) && is_numeric($partes[1]) && is_numeric($partes[2])) {
            return $partes[2] . '-' . str_pad($partes[1], 2, '0', STR_PAD_LEFT) . '-' . str_pad($partes[0], 2, '0', STR_PAD_LEFT);
        }
    }
    
    return null;
}

// Devuelve la URL del avatar de un empleado
function obtenerAvatarEmpleado($empleado, $config) {
    if (!empty($empleado['avatar'])) {
        $avatarFisica = rtrim($config['UPLOADS_DIR'], '/\\') . '/' . ltrim($empleado['avatar'], '/\\');
        $avatarWeb    = rtrim($config['UPLOADS_URL'], '/\\') . '/' . ltrim($empleado['avatar'], '/\\');
        
        // Verificar si el archivo existe
        if (@file_exists($avatarFisica)) {
            return $avatarWeb . '?v=' . time();
        }
    }
    
    // Fallback a Gravatar
    $email = !empty($empleado['email']) ? strtolower(trim($empleado['email'])) : 'user@example.com';
    $hash = md5($email);
    return "https://www.gravatar.com/avatar/{$hash}?s=80&d=identicon&r=pg";
}

// Genera la URL de paginación manteniendo los filtros
function generarUrlPaginacion($pagina, $parametrosActuales) {
    $parametros = $parametrosActuales;
    $parametros['pagina'] = $pagina;
    return '?' . http_build_query($parametros);
}

// Formatea el tipo de solicitud
function formatearTipo($tipo) {
    $tipos = [
        'vacaciones' => 'Vacaciones',
        'permiso' => 'Permiso',
        'baja' => 'Baja médica',
        'extra' => 'Horas extra',
        'ausencia' => 'Ausencia'
    ];
    return $tipos[$tipo] ?? ucfirst($tipo);
}

// Formatea una fecha (d/m/Y)
function formatearFecha($fecha) {
    if (!$fecha) return '-';
    return date('d/m/Y', strtotime($fecha));
}

// Formatea una fecha y hora (d/m/Y H:i)
function formatearFechaHora($fechaHora) {
    if (!$fechaHora) return '-';
    return date('d/m/Y H:i', strtotime($fechaHora));
}

function getHistorialSolicitudes($pdo, $empleadoId) {
    $stmt = $pdo->prepare("SELECT tipo, estado, fecha_inicio, fecha_fin, horas, hora_inicio, hora_fin, comentario_admin, comentario_empleado, archivo 
                           FROM solicitudes 
                           WHERE empleado_id = ? 
                           ORDER BY fecha_inicio DESC LIMIT 30");
    $stmt->execute([$empleadoId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Obtiene los horarios de un empleado organizados por día
 * @param PDO $pdo Conexión a la base de datos
 * @param int $empleadoId ID del empleado
 * @return array Array con los horarios del empleado
 */
function getHorariosEmpleado($pdo, $empleadoId) {
    $horarios = $pdo->prepare("SELECT * FROM horarios_empleados WHERE empleado_id = ? ORDER BY FIELD(dia, 'Lunes','Martes','Miércoles','Jueves','Viernes','Sábado','Domingo')");
    $horarios->execute([$empleadoId]);
    return $horarios->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Obtiene los fichajes de un empleado con filtros de fecha opcionales
 * @param PDO $pdo Conexión a la base de datos
 * @param int $empleadoId ID del empleado
 * @param string|null $fechaDesde Fecha desde (formato Y-m-d)
 * @param string|null $fechaHasta Fecha hasta (formato Y-m-d)
 * @return array Array con los fichajes del empleado
 */
function getFichajesEmpleado($pdo, $empleadoId, $fechaDesde = null, $fechaHasta = null) {
    $whereFecha = '';
    $params = [$empleadoId];
    
    if (!empty($fechaDesde)) {
        $whereFecha .= " AND DATE(hora) >= ?";
        $params[] = $fechaDesde;
    }
    if (!empty($fechaHasta)) {
        $whereFecha .= " AND DATE(hora) <= ?";
        $params[] = $fechaHasta;
    }

    $fichajes = $pdo->prepare("SELECT * FROM fichajes WHERE empleado_id = ? $whereFecha ORDER BY hora ASC");
    $fichajes->execute($params);
    return $fichajes->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Procesa los fichajes raw y los agrupa en bloques de entrada-salida
 * @param array $fichajesRaw Array de fichajes sin procesar
 * @return array Array de bloques de fichajes organizados
 */
function procesarFichajesEnBloques($fichajesRaw) {
    $bloques = [];
    $bloqueActual = null;
    
    foreach ($fichajesRaw as $f) {
        $tipo = $f['tipo'];
        $hora = $f['hora'];
        $fecha = substr($hora, 0, 10);

        if ($tipo == 'entrada') {
            if ($bloqueActual) $bloques[] = $bloqueActual;
            $bloqueActual = [
                'fecha'   => $fecha,
                'entrada' => $hora,
                'salida'  => null,
                'pausas'  => []
            ];
        }
        elseif ($tipo == 'salida') {
            if ($bloqueActual) {
                $bloqueActual['salida'] = $hora;
                $bloques[] = $bloqueActual;
                $bloqueActual = null;
            }
        }
        elseif ($tipo == 'pausa_inicio') {
            if ($bloqueActual) {
                $bloqueActual['pausas'][] = ['inicio' => $hora, 'fin' => null];
            }
        }
        elseif ($tipo == 'pausa_fin') {
            if ($bloqueActual && count($bloqueActual['pausas'])) {
                for ($i = count($bloqueActual['pausas'])-1; $i>=0; $i--) {
                    if ($bloqueActual['pausas'][$i]['fin'] === null) {
                        $bloqueActual['pausas'][$i]['fin'] = $hora;
                        break;
                    }
                }
            }
        }
    }
    
    if ($bloqueActual) $bloques[] = $bloqueActual;

    // Ordenar por fecha y hora de entrada
    usort($bloques, function($a, $b) {
        return strcmp($a['entrada'], $b['entrada']);
    });
    
    return $bloques;
}

/**
 * Convierte los bloques de fichajes en formato tabla para mostrar
 * @param array $bloques Array de bloques de fichajes
 * @return array Array formateado para mostrar en tabla
 */
function formatearFichajesParaTabla($bloques) {
    $fichajesTabla = [];
    $filasPorFecha = [];
    
    foreach ($bloques as $b) {
        $fecha = $b['fecha'];
        $entrada = $b['entrada'];
        $salida = $b['salida'];
        $pausas = $b['pausas'];
        
        // Si no existe la fecha, crear nueva entrada
        if (!isset($filasPorFecha[$fecha])) {
            $filasPorFecha[$fecha] = [
                'fecha' => $fecha,
                'primera_entrada' => $entrada,
                'ultima_salida' => $salida,
                'minutos_trabajo' => 0,
                'minutos_descanso' => 0,
                'bloques' => []
            ];
        }
        
        // Actualizar primera entrada y última salida
        if ($entrada && (!$filasPorFecha[$fecha]['primera_entrada'] || $entrada < $filasPorFecha[$fecha]['primera_entrada'])) {
            $filasPorFecha[$fecha]['primera_entrada'] = $entrada;
        }
        if ($salida && (!$filasPorFecha[$fecha]['ultima_salida'] || $salida > $filasPorFecha[$fecha]['ultima_salida'])) {
            $filasPorFecha[$fecha]['ultima_salida'] = $salida;
        }
        
        // Calcular minutos de descanso de este bloque
        $minDescanso = 0;
        foreach ($pausas as $p) {
            if (!empty($p['inicio']) && !empty($p['fin'])) {
                $tIni = strtotime($p['inicio']);
                $tFin = strtotime($p['fin']);
                if ($tFin > $tIni) $minDescanso += ($tFin - $tIni) / 60;
            }
        }
        
        // Calcular minutos de trabajo de este bloque
        $minTrabajo = 0;
        if ($entrada && $salida) {
            $tEntrada = strtotime($entrada);
            $tSalida = strtotime($salida);
            $minTotal = ($tSalida > $tEntrada) ? (($tSalida - $tEntrada) / 60) : 0;
            $minTrabajo = $minTotal - $minDescanso;
        }
        
        // Sumar a los totales del día
        $filasPorFecha[$fecha]['minutos_trabajo'] += $minTrabajo;
        $filasPorFecha[$fecha]['minutos_descanso'] += $minDescanso;
        $filasPorFecha[$fecha]['bloques'][] = $b;
    }
    
    // Formatear para la tabla
    foreach ($filasPorFecha as $fecha => $data) {
        $fichajesTabla[] = [
            'fecha'    => date('d/m/Y', strtotime($fecha)),
            'entrada'  => $data['primera_entrada'] ? date('H:i', strtotime($data['primera_entrada'])) : '--',
            'salida'   => $data['ultima_salida'] ? date('H:i', strtotime($data['ultima_salida'])) : '--',
            'trabajo'  => $data['minutos_trabajo'] > 0 ? (floor($data['minutos_trabajo']/60).'h '.($data['minutos_trabajo']%60).'min') : '--',
            'descanso' => $data['minutos_descanso'] > 0 ? (floor($data['minutos_descanso']/60).'h '.($data['minutos_descanso']%60).'min') : '--'
        ];
    }
    
    // Ordenar por fecha
    usort($fichajesTabla, function($a, $b) {
        return strtotime(str_replace('/', '-', $b['fecha'])) - strtotime(str_replace('/', '-', $a['fecha']));
    });
    
    return $fichajesTabla;
}

/**
 * Obtiene los fichajes procesados de un empleado para mostrar en tabla
 * @param PDO $pdo Conexión a la base de datos
 * @param int $empleadoId ID del empleado
 * @param string|null $fechaDesde Fecha desde (formato Y-m-d)
 * @param string|null $fechaHasta Fecha hasta (formato Y-m-d)
 * @return array Array formateado para mostrar en tabla
 */
function getFichajesTabla($pdo, $empleadoId, $fechaDesde = null, $fechaHasta = null) {
    $fichajesRaw = getFichajesEmpleado($pdo, $empleadoId, $fechaDesde, $fechaHasta);
    $bloques = procesarFichajesEnBloques($fichajesRaw);
    return formatearFichajesParaTabla($bloques);
}

/**
 * Obtiene la URL del avatar de un empleado
 * @param array $empleado Datos del empleado
 * @param array $config Configuración global
 * @return string URL del avatar
 */
function obtenerAvatarURL($empleado, $config) {
    if (!empty($empleado['avatar'])) {
        $avatarFisica = rtrim($config['UPLOADS_DIR'], '/\\') . '/' . ltrim($empleado['avatar'], '/\\');
        $avatarWeb    = rtrim($config['UPLOADS_URL'], '/\\') . '/' . ltrim($empleado['avatar'], '/\\');
        
        if (@file_exists($avatarFisica)) {
            return $avatarWeb . '?v=' . time();
        }
    }
    
    return $config['ASSET_URL'] . 'img/avatar-default.jpg';
}

// ========================================
// FUNCIONES PARA SISTEMA DE INFORMES
// ========================================

/**
 * Obtiene informe de fichajes por período para un empleado
 * @param PDO $pdo Conexión a base de datos
 * @param int $empleadoId ID del empleado
 * @param string $fechaInicio Fecha inicio (Y-m-d)
 * @param string $fechaFin Fecha fin (Y-m-d)
 * @return array Fichajes agrupados por día
 */
function obtenerInformeFichajes($pdo, $empleadoId, $fechaInicio, $fechaFin) {
    $stmt = $pdo->prepare("
        SELECT 
            DATE(hora) as fecha,
            tipo,
            TIME(hora) as hora,
            hora as fecha_completa
        FROM fichajes 
        WHERE empleado_id = ? 
        AND DATE(hora) BETWEEN ? AND ? 
        ORDER BY fecha DESC, hora ASC
    ");
    $stmt->execute([$empleadoId, $fechaInicio, $fechaFin]);
    
    $fichajes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Agrupar por fecha
    $fichajesPorDia = [];
    foreach ($fichajes as $fichaje) {
        $fecha = $fichaje['fecha'];
        if (!isset($fichajesPorDia[$fecha])) {
            $fichajesPorDia[$fecha] = [
                'fecha' => $fecha,
                'entradas' => [],
                'salidas' => [],
                'pausas' => [],
                'resumen' => []
            ];
        }
        
        if ($fichaje['tipo'] === 'entrada') {
            $fichajesPorDia[$fecha]['entradas'][] = $fichaje;
        } elseif ($fichaje['tipo'] === 'salida') {
            $fichajesPorDia[$fecha]['salidas'][] = $fichaje;
        } elseif ($fichaje['tipo'] === 'pausa') {
            $fichajesPorDia[$fecha]['pausas'][] = $fichaje;
        }
    }
    
    // Calcular horas trabajadas por día
    foreach ($fichajesPorDia as &$dia) {
        $dia['horas_trabajadas'] = calcularHorasTrabajadas($dia);
        $dia['primera_entrada'] = !empty($dia['entradas']) ? min(array_column($dia['entradas'], 'hora')) : null;
        $dia['ultima_salida'] = !empty($dia['salidas']) ? max(array_column($dia['salidas'], 'hora')) : null;
    }
    
    return $fichajesPorDia;
}

/**
 * Obtiene informe de solicitudes por período para un empleado
 * @param PDO $pdo Conexión a base de datos
 * @param int $empleadoId ID del empleado
 * @param string $fechaInicio Fecha inicio (Y-m-d)
 * @param string $fechaFin Fecha fin (Y-m-d)
 * @return array Lista de solicitudes
 */
function obtenerInformeSolicitudes($pdo, $empleadoId, $fechaInicio, $fechaFin) {
    $stmt = $pdo->prepare("
        SELECT 
            s.*,
            DATE(s.fecha_inicio) as fecha_inicio_solo,
            DATE(s.fecha_fin) as fecha_fin_solo
        FROM solicitudes s
        WHERE s.empleado_id = ? 
        AND (
            DATE(s.fecha_inicio) BETWEEN ? AND ? 
            OR DATE(s.fecha_fin) BETWEEN ? AND ?
            OR (DATE(s.fecha_inicio) <= ? AND DATE(s.fecha_fin) >= ?)
        )
        ORDER BY s.fecha_inicio DESC
    ");
    $stmt->execute([$empleadoId, $fechaInicio, $fechaFin, $fechaInicio, $fechaFin, $fechaInicio, $fechaFin]);
    
    $solicitudes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Calcular días laborables para cada solicitud
    foreach ($solicitudes as &$sol) {
        if ($sol['tipo'] === 'extra') {
            // Horas extra solo cuenta como 1 evento, no días
            $sol['dias_solicitados'] = 1;
        } else {
            // Contar solo días laborables para vacaciones, permisos, bajas y ausencias
            $sol['dias_solicitados'] = contarDiasLaborables($sol['fecha_inicio_solo'], $sol['fecha_fin_solo'], $pdo);
        }
    }
    
    return $solicitudes;
}

/**
 * Obtiene informe de vacaciones aprobadas por período para un empleado
 * @param PDO $pdo Conexión a base de datos
 * @param int $empleadoId ID del empleado
 * @param string $fechaInicio Fecha inicio (Y-m-d)
 * @param string $fechaFin Fecha fin (Y-m-d)
 * @return array Lista de vacaciones aprobadas
 */
function obtenerInformeVacaciones($pdo, $empleadoId, $fechaInicio, $fechaFin) {
    // Primero intentar buscar vacaciones aprobadas
    $stmt = $pdo->prepare("
        SELECT 
            s.*,
            DATE(s.fecha_inicio) as fecha_inicio_solo,
            DATE(s.fecha_fin) as fecha_fin_solo
        FROM solicitudes s
        WHERE s.empleado_id = ? 
        AND s.tipo IN ('vacaciones', 'permiso', 'libre')
        AND s.estado IN ('aprobada', 'aprobado')
        AND (
            DATE(s.fecha_inicio) BETWEEN ? AND ? 
            OR DATE(s.fecha_fin) BETWEEN ? AND ?
            OR (DATE(s.fecha_inicio) <= ? AND DATE(s.fecha_fin) >= ?)
        )
        ORDER BY s.fecha_inicio DESC
    ");
    $stmt->execute([$empleadoId, $fechaInicio, $fechaFin, $fechaInicio, $fechaFin, $fechaInicio, $fechaFin]);
    $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Si no hay vacaciones aprobadas, buscar todas las vacaciones
    if (empty($resultados)) {
        $stmt = $pdo->prepare("
            SELECT 
                s.*,
                DATE(s.fecha_inicio) as fecha_inicio_solo,
                DATE(s.fecha_fin) as fecha_fin_solo
            FROM solicitudes s
            WHERE s.empleado_id = ? 
            AND s.tipo IN ('vacaciones', 'permiso', 'libre')
            AND (
                DATE(s.fecha_inicio) BETWEEN ? AND ? 
                OR DATE(s.fecha_fin) BETWEEN ? AND ?
                OR (DATE(s.fecha_inicio) <= ? AND DATE(s.fecha_fin) >= ?)
            )
            ORDER BY s.fecha_inicio DESC
        ");
        $stmt->execute([$empleadoId, $fechaInicio, $fechaFin, $fechaInicio, $fechaFin, $fechaInicio, $fechaFin]);
        $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Log para debug
        error_log("Debug vacaciones - Usuario $empleadoId, período $fechaInicio a $fechaFin, encontradas: " . count($resultados));
        if (!empty($resultados)) {
            error_log("Debug vacaciones - Estados encontrados: " . implode(', ', array_unique(array_column($resultados, 'estado'))));
        }
    }
    
    // Calcular días laborables para cada solicitud
    foreach ($resultados as &$sol) {
        $sol['dias_solicitados'] = contarDiasLaborables($sol['fecha_inicio_solo'], $sol['fecha_fin_solo'], $pdo);
    }
    
    return $resultados;
}

/**
 * Obtiene resumen completo combinando todos los tipos de informes
 * @param PDO $pdo Conexión a base de datos
 * @param int $empleadoId ID del empleado
 * @param string $fechaInicio Fecha inicio (Y-m-d)
 * @param string $fechaFin Fecha fin (Y-m-d)
 * @return array Datos combinados de todos los informes
 */
function obtenerInformeResumen($pdo, $empleadoId, $fechaInicio, $fechaFin) {
    return [
        'fichajes' => obtenerInformeFichajes($pdo, $empleadoId, $fechaInicio, $fechaFin),
        'solicitudes' => obtenerInformeSolicitudes($pdo, $empleadoId, $fechaInicio, $fechaFin),
        'vacaciones' => obtenerInformeVacaciones($pdo, $empleadoId, $fechaInicio, $fechaFin)
    ];
}

/**
 * Calcula las horas trabajadas en un día basado en entradas y salidas
 * @param array $dia Datos del día con entradas y salidas
 * @return string Horas trabajadas en formato HH:MM:SS
 */
function calcularHorasTrabajadas($dia) {
    if (empty($dia['entradas']) || empty($dia['salidas'])) {
        return '00:00:00';
    }
    
    $totalSegundos = 0;
    $entradas = array_column($dia['entradas'], 'hora');
    $salidas = array_column($dia['salidas'], 'hora');
    
    $minEntradas = min(count($entradas), count($salidas));
    
    for ($i = 0; $i < $minEntradas; $i++) {
        $entrada = DateTime::createFromFormat('H:i:s', $entradas[$i]);
        $salida = DateTime::createFromFormat('H:i:s', $salidas[$i]);
        
        if ($entrada && $salida) {
            $diff = $salida->diff($entrada);
            $totalSegundos += $diff->h * 3600 + $diff->i * 60 + $diff->s;
        }
    }
    
    $horas = floor($totalSegundos / 3600);
    $minutos = floor(($totalSegundos % 3600) / 60);
    $segundos = $totalSegundos % 60;
    
    return sprintf('%02d:%02d:%02d', $horas, $minutos, $segundos);
}

/**
 * Calcula estadísticas de fichajes
 * @param array $datos Datos de fichajes
 * @return array Estadísticas calculadas
 */
function calcularEstadisticasFichajes($datos) {
    $totalDias = count($datos);
    $diasTrabajados = 0;
    $totalHoras = 0;
    $promedioEntrada = [];
    $promedioSalida = [];
    
    foreach ($datos as $dia) {
        if (!empty($dia['entradas']) && !empty($dia['salidas'])) {
            $diasTrabajados++;
        }
        
        if ($dia['primera_entrada']) {
            $promedioEntrada[] = $dia['primera_entrada'];
        }
        
        if ($dia['ultima_salida']) {
            $promedioSalida[] = $dia['ultima_salida'];
        }
        
        // Convertir horas trabajadas a decimal
        if ($dia['horas_trabajadas'] !== '00:00:00') {
            $partes = explode(':', $dia['horas_trabajadas']);
            $totalHoras += $partes[0] + ($partes[1] / 60);
        }
    }
    
    return [
        'total_dias' => $totalDias,
        'dias_trabajados' => $diasTrabajados,
        'dias_ausentes' => $totalDias - $diasTrabajados,
        'total_horas' => round($totalHoras, 2),
        'promedio_horas_dia' => $diasTrabajados > 0 ? round($totalHoras / $diasTrabajados, 2) : 0,
        'promedio_entrada' => !empty($promedioEntrada) ? calcularPromedioHora($promedioEntrada) : null,
        'promedio_salida' => !empty($promedioSalida) ? calcularPromedioHora($promedioSalida) : null
    ];
}

/**
 * Calcula estadísticas de solicitudes
 * @param array $datos Datos de solicitudes
 * @return array Estadísticas calculadas
 */
function calcularEstadisticasSolicitudes($datos) {
    $totalSolicitudes = count($datos);
    $aprobadas = 0;
    $pendientes = 0;
    $rechazadas = 0;
    $diasSolicitados = 0;
    $horasSolicitadas = 0;
    
    // Contadores por tipo
    $tiposSolicitudes = [
        'vacaciones' => 0,
        'permiso' => 0,
        'baja' => 0,
        'extra' => 0,
        'ausencia' => 0
    ];
    
    foreach ($datos as $solicitud) {
        // Contar por tipo
        $tipo = strtolower($solicitud['tipo']);
        if (isset($tiposSolicitudes[$tipo])) {
            $tiposSolicitudes[$tipo]++;
        }
        
        // Contar por estado
        switch ($solicitud['estado']) {
            case 'aprobada':
            case 'aprobado':
                $aprobadas++;
                // Para horas extras, sumar horas
                if ($tipo === 'extra') {
                    $horasSolicitadas += floatval($solicitud['horas'] ?? $solicitud['duracion'] ?? 0);
                } else {
                    // Para otros tipos, sumar días
                    $diasSolicitados += $solicitud['dias_solicitados'] ?? 1;
                }
                break;
            case 'pendiente':
                $pendientes++;
                break;
            case 'rechazada':
            case 'rechazado':
                $rechazadas++;
                break;
        }
    }
    
    return [
        'total_solicitudes' => $totalSolicitudes,
        'aprobadas' => $aprobadas,
        'pendientes' => $pendientes,
        'rechazadas' => $rechazadas,
        'dias_solicitados' => $diasSolicitados,
        'horas_solicitadas' => $horasSolicitadas,
        'tipos_solicitudes' => $tiposSolicitudes,
        'tasa_aprobacion' => $totalSolicitudes > 0 ? round(($aprobadas / $totalSolicitudes) * 100, 1) : 0
    ];
}

/**
 * Calcula estadísticas de vacaciones
 * @param array $datos Datos de vacaciones
 * @return array Estadísticas calculadas
 */
function calcularEstadisticasVacaciones($datos) {
    $totalVacaciones = count($datos);
    $diasVacaciones = 0;
    
    foreach ($datos as $vacacion) {
        $diasVacaciones += $vacacion['dias_solicitados'];
    }
    
    return [
        'total_vacaciones' => $totalVacaciones,
        'dias_vacaciones' => $diasVacaciones,
        'promedio_duracion' => $totalVacaciones > 0 ? round($diasVacaciones / $totalVacaciones, 1) : 0
    ];
}

/**
 * Calcula estadísticas del resumen completo
 * @param array $datos Datos del resumen
 * @return array Estadísticas calculadas
 */
function calcularEstadisticasResumen($datos) {
    return [
        'fichajes' => calcularEstadisticasFichajes($datos['fichajes']),
        'solicitudes' => calcularEstadisticasSolicitudes($datos['solicitudes']),
        'vacaciones' => calcularEstadisticasVacaciones($datos['vacaciones'])
    ];
}

/**
 * Calcula el promedio de un array de horas
 * @param array $horas Array de horas en formato H:i:s
 * @return string|null Hora promedio en formato HH:MM o null si no hay datos
 */
function calcularPromedioHora($horas) {
    $totalSegundos = 0;
    $count = 0;
    
    foreach ($horas as $hora) {
        $partes = explode(':', $hora);
        if (count($partes) >= 2) {
            $totalSegundos += ($partes[0] * 3600) + ($partes[1] * 60) + (isset($partes[2]) ? $partes[2] : 0);
            $count++;
        }
    }
    
    if ($count === 0) return null;
    
    $promedioSegundos = $totalSegundos / $count;
    $horas = floor($promedioSegundos / 3600);
    $minutos = floor(($promedioSegundos % 3600) / 60);
    
    return sprintf('%02d:%02d', $horas, $minutos);
}

/**
 * Formatea una fecha en español
 * @param string $fecha Fecha en formato Y-m-d
 * @return string Fecha formateada en español
 */
function formatearFechaEspanol($fecha) {
    if (empty($fecha) || $fecha === '0000-00-00') {
        return 'Fecha no disponible';
    }
    
    $meses = [
        '01' => 'enero', '02' => 'febrero', '03' => 'marzo', '04' => 'abril',
        '05' => 'mayo', '06' => 'junio', '07' => 'julio', '08' => 'agosto',
        '09' => 'septiembre', '10' => 'octubre', '11' => 'noviembre', '12' => 'diciembre'
    ];
    
    try {
        // Intentar crear objeto DateTime para validar la fecha
        $fechaObj = new DateTime($fecha);
        $dia = $fechaObj->format('j');
        $mes = $meses[$fechaObj->format('m')] ?? $fechaObj->format('m');
        $año = $fechaObj->format('Y');
        return "{$dia} de {$mes} de {$año}";
    } catch (Exception $e) {
        // Si falla, intentar con explode como fallback
        $partes = explode('-', $fecha);
        if (count($partes) === 3) {
            $dia = intval($partes[2]);
            $mes = $meses[$partes[1]] ?? $partes[1];
            $año = $partes[0];
            return "{$dia} de {$mes} de {$año}";
        }
        
        return $fecha;
    }
}

/**
 * Obtiene informe de solicitudes por tipo específico
 * @param PDO $pdo Conexión a la base de datos
 * @param int $empleadoId ID del empleado
 * @param string $tipo Tipo de solicitud (vacaciones, permiso, baja, extra, ausencia)
 * @param string $fechaInicio Fecha de inicio en formato Y-m-d
 * @param string $fechaFin Fecha de fin en formato Y-m-d
 * @return array Datos de las solicitudes del tipo especificado
 */
function obtenerInformeSolicitudesPorTipo($pdo, $empleadoId, $tipo, $fechaInicio, $fechaFin) {
    try {
        $sql = "SELECT s.*, e.nombre as empleado_nombre, e.apellidos as empleado_apellidos
                FROM solicitudes s 
                LEFT JOIN empleados e ON s.empleado_id = e.id
                WHERE s.empleado_id = ? AND s.tipo = ?
                AND DATE(s.fecha_inicio) >= ? AND DATE(s.fecha_inicio) <= ?
                ORDER BY s.fecha_creacion DESC";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$empleadoId, $tipo, $fechaInicio, $fechaFin]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
        
    } catch (Exception $e) {
        error_log("Error obteniendo informe de solicitudes por tipo: " . $e->getMessage());
        return [];
    }
}

/**
 * Calcula estadísticas específicas para un tipo de solicitud
 * @param array $datos Datos de las solicitudes
 * @param string $tipo Tipo de solicitud para cálculos específicos
 * @return array Estadísticas calculadas
 */
function calcularEstadisticasSolicitudesTipo($datos, $tipo) {
    if (empty($datos)) {
        return [
            'total_solicitudes' => 0,
            'tasa_aprobacion' => 0,
            'pendientes' => 0,
            'aprobadas' => 0,
            'rechazadas' => 0
        ];
    }
    
    $estadisticas = [
        'total_solicitudes' => count($datos),
        'pendientes' => 0,
        'aprobadas' => 0,
        'rechazadas' => 0,
        'dias_solicitados' => 0,
        'dias_aprobados' => 0,
        'horas_solicitadas' => 0,
        'horas_aprobadas' => 0
    ];
    
    foreach ($datos as $solicitud) {
        // Contar por estado
        switch ($solicitud['estado']) {
            case 'pendiente':
                $estadisticas['pendientes']++;
                break;
            case 'aprobada':
                $estadisticas['aprobadas']++;
                break;
            case 'rechazada':
                $estadisticas['rechazadas']++;
                break;
        }
        
        // Calcular días/horas según el tipo
        if ($tipo === 'vacaciones' || $tipo === 'bajas' || $tipo === 'ausencias') {
            // Para solicitudes que manejan días
            if (!empty($solicitud['fecha_inicio']) && !empty($solicitud['fecha_fin'])) {
                $fechaInicio = new DateTime($solicitud['fecha_inicio']);
                $fechaFin = new DateTime($solicitud['fecha_fin']);
                $diasSolicitados = $fechaInicio->diff($fechaFin)->days + 1;
                
                $estadisticas['dias_solicitados'] += $diasSolicitados;
                
                if ($solicitud['estado'] === 'aprobada') {
                    $estadisticas['dias_aprobados'] += $diasSolicitados;
                }
            }
        } elseif ($tipo === 'permisos' || $tipo === 'extras') {
            // Para solicitudes que manejan horas
            if (!empty($solicitud['duracion'])) {
                $horasSolicitadas = floatval($solicitud['duracion']);
                $estadisticas['horas_solicitadas'] += $horasSolicitadas;
                
                if ($solicitud['estado'] === 'aprobada') {
                    $estadisticas['horas_aprobadas'] += $horasSolicitadas;
                }
            }
        }
    }
    
    // Calcular tasa de aprobación
    if ($estadisticas['total_solicitudes'] > 0) {
        $estadisticas['tasa_aprobacion'] = round(
            ($estadisticas['aprobadas'] / $estadisticas['total_solicitudes']) * 100, 
            1
        );
    } else {
        $estadisticas['tasa_aprobacion'] = 0;
    }
    
    return $estadisticas;
}

/**
 * Registrar un log de auditoría en la tabla existente 'auditoria'
 * 
 * @param string $accion Descripción de la acción realizada
 * @param string $detalle Detalles adicionales (opcional)
 * @param int|null $usuarioId ID del empleado (null para acciones del sistema)
 */
function registrarAuditoria($accion, $detalle = null, $usuarioId = null) {
    global $pdo;
    
    try {
        // Si no se especifica usuario, usar el logueado actualmente
        if ($usuarioId === null && isLoggedIn()) {
            $usuarioId = getEmpleadoId();
        }
        
        // Si no hay detalle, crear uno básico con IP y User Agent
        if ($detalle === null) {
            $ipAddress = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? 
                        $_SERVER['HTTP_X_REAL_IP'] ?? 
                        $_SERVER['REMOTE_ADDR'] ?? 
                        'unknown';
            
            // Si viene de un proxy, tomar la primera IP
            if (strpos($ipAddress, ',') !== false) {
                $ipAddress = trim(explode(',', $ipAddress)[0]);
            }
            
            $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
            $detalle = "IP: $ipAddress | UserAgent: $userAgent";
        }
        
        // Insertar log en la tabla auditoria
        $stmt = $pdo->prepare("
            INSERT INTO auditoria 
            (usuario_id, accion, detalle, fecha_hora) 
            VALUES (?, ?, ?, NOW())
        ");
        
        $stmt->execute([
            $usuarioId,
            $accion,
            $detalle
        ]);
        
        return true;
        
    } catch (Exception $e) {
        // Log del error pero no interrumpir el flujo de la aplicación
        error_log("Error al registrar auditoría: " . $e->getMessage());
        return false;
    }
}

/**
 * Registrar login exitoso
 */
function registrarLoginExitoso($empleadoId = null) {
    $empleadoId = $empleadoId ?: getEmpleadoId();
    $empleado = getEmpleado();
    $nombreUsuario = $empleado ? $empleado['nombre'] : 'Usuario desconocido';
    
    registrarAuditoria(
        'login_exitoso',
        "Usuario: {$nombreUsuario} ingresó al sistema correctamente",
        $empleadoId
    );
}

/**
 * Registrar login fallido
 */
function registrarLoginFallido($usuario, $motivo = 'Credenciales incorrectas') {
    registrarAuditoria(
        'login_fallido',
        "Usuario: {$usuario} - Motivo: {$motivo}",
        null
    );
}

/**
 * Registrar logout
 */
function registrarLogout($empleadoId = null) {
    $empleadoId = $empleadoId ?: getEmpleadoId();
    $empleado = getEmpleado();
    $nombreUsuario = $empleado ? $empleado['nombre'] : 'Usuario desconocido';
    
    registrarAuditoria(
        'logout',
        "Usuario: {$nombreUsuario} cerró sesión",
        $empleadoId
    );
}

/**
 * Registrar cambio de contraseña
 */
function registrarCambioContrasena($empleadoObjetivo, $empleadoAdmin = null) {
    $adminId = $empleadoAdmin ?: getEmpleadoId();
    $admin = getEmpleado();
    $adminNombre = $admin ? $admin['nombre'] : 'Administrador';
    
    $detalle = ($adminId == $empleadoObjetivo) ? 
        "Usuario cambió su propia contraseña" : 
        "Administrador: {$adminNombre} cambió la contraseña del usuario";
    
    registrarAuditoria(
        'cambio_contrasena',
        $detalle,
        $adminId
    );
}

/**
 * Registrar acceso a página administrativa
 */
function registrarAccesoAdmin($pagina) {
    $empleado = getEmpleado();
    $nombreUsuario = $empleado ? $empleado['nombre'] : 'Administrador';
    
    registrarAuditoria(
        'acceso_admin',
        "Usuario: {$nombreUsuario} accedió a página: {$pagina}"
    );
}

/**
 * Registrar acción del sistema
 */
function registrarAccionSistema($accion, $detalle = null) {
    registrarAuditoria(
        'sistema_' . $accion,
        $detalle ?: "Acción del sistema: {$accion}",
        null
    );
}

// Mantener compatibilidad con el nombre anterior
function registrarLogSeguridad($accion, $detalles = null, $resultado = 'info', $empleadoId = null, $usuarioSistema = null) {
    $detalle = $detalles;
    if ($usuarioSistema) {
        $detalle = "Usuario del sistema: {$usuarioSistema} - " . ($detalles ?: $accion);
    }
    
    return registrarAuditoria($accion, $detalle, $empleadoId);
}


/**
 * Geolocalización
 */
function db_fetch_one_assoc(string $sql, array $params = []): ?array {
    global $pdo;
    $st = $pdo->prepare($sql);
    $st->execute($params);
    $row = $st->fetch(PDO::FETCH_ASSOC);
    return $row === false ? null : $row;
}

function db_fetch_all_assoc(string $sql, array $params = []): array {
    global $pdo;
    $st = $pdo->prepare($sql);
    $st->execute($params);
    return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
}

/**
 * Configuración de geolocalización para un empleado
 */
function obtenerGeoConfigEmpleado(int $empleadoId): array {
    $sql = 'SELECT *
            FROM configuracion_geolocalizacion
            WHERE empleado_id = ? OR empleado_id IS NULL
            ORDER BY empleado_id DESC
            LIMIT 1';

    $row = db_fetch_one_assoc($sql, [$empleadoId]);

    if (!$row) {
        $row = [
            'latitud_oficina'           => 40.4168,
            'longitud_oficina'          => -3.7038,
            'radio_permitido'           => 100,
            'nombre_ubicacion'          => 'Oficina Central',
            'geolocalizacion_requerida' => 1,
        ];
    }

    // Normaliza tipos
    $row['latitud_oficina']            = (float) $row['latitud_oficina'];
    $row['longitud_oficina']           = (float) $row['longitud_oficina'];
    $row['radio_permitido']            = (int)   ($row['radio_permitido'] ?? 100);
    $row['geolocalizacion_requerida']  = (int)   ($row['geolocalizacion_requerida'] ?? 1);
    $row['nombre_ubicacion']           = (string)($row['nombre_ubicacion'] ?? 'Oficina');

    return $row;
}

/**
 * Historial de fichajes con geolocalización (entrada/salida).
 */
function obtenerHistorialFichajes(int $empleadoId, int $limit = 15): array {
    $limit = max(1, (int)$limit);

    $sql = 'SELECT tipo, latitud, longitud,
                   DATE_FORMAT(hora, "%d/%m/%Y %H:%i") AS fecha_formato,
                   hora
            FROM fichajes
            WHERE empleado_id = ?
              AND latitud IS NOT NULL
              AND tipo IN ("entrada", "salida")
            ORDER BY hora DESC
            LIMIT ' . $limit;

    return db_fetch_all_assoc($sql, [$empleadoId]);
}


// informes.php

/**
 * Calcula el rango de fechas (inicio y fin) según el período especificado
 * 
 * @param string $periodo Tipo de período: 'hoy', 'semana_actual', 'mes_actual', 
 *                       'trimestre_actual', 'año_actual', 'personalizado'
 * @param string|null $fiCustom Fecha inicio personalizada (solo para 'personalizado')
 * @param string|null $ffCustom Fecha fin personalizada (solo para 'personalizado')
 * @return array [fecha_inicio, fecha_fin] en formato 'YYYY-MM-DD'
 */
function rangoFechasDesdePeriodo(string $periodo, ?string $fiCustom = null, ?string $ffCustom = null): array {
    switch ($periodo) {
        case 'hoy':
            $fi = date('Y-m-d');
            $ff = date('Y-m-d');
            break;
        case 'semana_actual':
            $fi = date('Y-m-d', strtotime('monday this week'));
            $ff = date('Y-m-d', strtotime('sunday this week'));
            break;
        case 'trimestre_actual':
            $mesActual = (int)date('n');
            $trimestreInicio = (int)(floor(($mesActual - 1) / 3) * 3 + 1);
            $fi = date('Y-' . sprintf('%02d', $trimestreInicio) . '-01');
            $ff = date('Y-m-t', strtotime($fi . ' +2 months'));
            break;
        case 'año_actual':
            $fi = date('Y-01-01');
            $ff = date('Y-12-31');
            break;
        case 'personalizado':
            $fi = $fiCustom ?: date('Y-m-01');
            $ff = $ffCustom ?: date('Y-m-t');
            break;
        case 'mes_actual':
        default:
            $fi = date('Y-m-01');
            $ff = date('Y-m-t');
            break;
    }
    return [$fi, $ff];
}

/**
 * Obtiene resumen diario de fichajes (entradas/salidas) para un empleado
 * 
 * @param int $empleadoId ID del empleado
 * @param string $fechaInicio Fecha inicio (YYYY-MM-DD)
 * @param string $fechaFin Fecha fin (YYYY-MM-DD)
 * @return array Resumen por día con primera entrada, última salida y contadores
 */
function obtenerResumenDiarioFichajes(int $empleadoId, string $fechaInicio, string $fechaFin): array {
    global $pdo;
    $sql = "SELECT DATE(hora) AS fecha,
                   MIN(CASE WHEN tipo = 'entrada' THEN TIME(hora) END) AS primera_entrada,
                   MAX(CASE WHEN tipo = 'salida'  THEN TIME(hora) END) AS ultima_salida,
                   COUNT(CASE WHEN tipo = 'entrada' THEN 1 END) AS entradas,
                   COUNT(CASE WHEN tipo = 'salida'  THEN 1 END) AS salidas
            FROM fichajes
            WHERE empleado_id = ? AND DATE(hora) BETWEEN ? AND ?
            GROUP BY DATE(hora)
            ORDER BY fecha DESC";
    $st = $pdo->prepare($sql);
    $st->execute([$empleadoId, $fechaInicio, $fechaFin]);
    return $st->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Obtiene descansos y pausas agrupados por día para un empleado
 * 
 * @param int $empleadoId ID del empleado
 * @param string $fechaInicio Fecha inicio (YYYY-MM-DD)
 * @param string $fechaFin Fecha fin (YYYY-MM-DD)
 * @return array [fecha => 'tipo:hora,tipo:hora,...']
 */
function obtenerDescansosRawPorDia(int $empleadoId, string $fechaInicio, string $fechaFin): array {
    global $pdo;
    // Consulta para obtener descansos agrupados por día
    $sql = "SELECT DATE(hora) AS fecha,
                   GROUP_CONCAT(CONCAT(tipo, ':', TIME(hora)) ORDER BY hora) AS descansos_raw
            FROM fichajes
            WHERE empleado_id = ? AND DATE(hora) BETWEEN ? AND ?
              AND tipo IN ('descanso_inicio', 'descanso_fin', 'pausa_inicio', 'pausa_fin')
            GROUP BY DATE(hora)";
    $st = $pdo->prepare($sql);
    $st->execute([$empleadoId, $fechaInicio, $fechaFin]);
    return $st->fetchAll(PDO::FETCH_KEY_PAIR) ?: [];
}

/**
 * Calcula el total de minutos de descansos a partir de un string raw
 * 
 * @param string|null $descansosRaw Cadena con formato "tipo:hora,tipo:hora,..."
 * @return int Total de minutos de descansos
 */
function calcularDescansosDia(?string $descansosRaw): int {
    if (empty($descansosRaw)) return 0;
     // Separar los registros de descansos
    $descansos = explode(',', $descansosRaw);
    $inicios = [];
    $fines   = [];
    foreach ($descansos as $desc) {
        $partes = explode(':', $desc, 2);
        if (count($partes) < 2) continue;
        $tipo = $partes[0];
        $hora = $partes[1];
        if (in_array($tipo, ['descanso_inicio','pausa_inicio'], true)) {
            $inicios[] = $hora;
        } elseif (in_array($tipo, ['descanso_fin','pausa_fin'], true)) {
            $fines[] = $hora;
        }
    }
     // Calcular tiempo total por pares (inicio-fin)
    $pares = min(count($inicios), count($fines));
    $minTotal = 0;
    for ($i = 0; $i < $pares; $i++) {
        try {
            $ini = new DateTime($inicios[$i]);
            $fin = new DateTime($fines[$i]);
            if ($fin > $ini) {
                $diff = $fin->diff($ini);
                $minTotal += ($diff->h * 60) + $diff->i;
            }
        } catch (Throwable $e) {
        }
    }
    return $minTotal;
}

/**
 * Calcula el promedio simple de un array de horas en formato HH:MM
 * 
 * @param array $horas Array de horas en formato ['HH:MM', 'HH:MM', ...]
 * @return string Hora promedio en formato 'HH:MM'. Retorna '09:00' si no hay datos válidos
 */
function calcularPromedioHoraSimple(array $horas): string {
    if (empty($horas)) return '09:00';
    $seg = 0; $n = 0;
    foreach ($horas as $h) {
        $p = explode(':', $h);
        if (count($p) >= 2 && is_numeric($p[0]) && is_numeric($p[1])) {
            $seg += ((int)$p[0]) * 3600 + ((int)$p[1]) * 60;
            $n++;
        }
    }
    if ($n === 0) return '09:00';
    $avg = (int)round($seg / $n);
    $hh = floor($avg / 3600);
    $mm = floor(($avg % 3600) / 60);
    return sprintf('%02d:%02d', $hh, $mm);
}

/**
 * Construye el detalle de fichajes con cálculos de horas y estado por día
 * 
 * @param array $resumen Array con resumen de fichajes por día
 * @param array $descansosRawPorDia Array de descansos raw por fecha
 * @return array [
 *     array $detalle,
 *     int $totalDias,
 *     float $totalHoras,
 *     array $entradaArr,
 *     array $salidaArr,
 *     float $horasExtras
 * ]
 */
function construirFichajesDetalle(array $resumen, array $descansosRawPorDia): array {
    $detalle = [];
    $totalDias = 0;
    $totalHoras = 0.0;
    $entradaArr = [];
    $salidaArr  = [];
    $horasExtras = 0.0;

    foreach ($resumen as $row) {
        $fecha = $row['fecha'];
        $prim = $row['primera_entrada'] ?? null;
        $ult  = $row['ultima_salida'] ?? null;
        $minDescanso = isset($descansosRawPorDia[$fecha]) ? calcularDescansosDia($descansosRawPorDia[$fecha]) : 0;
        $horasDescansoFmt = sprintf('%d:%02d', intdiv($minDescanso,60), $minDescanso % 60);

        $estado = 'Sin fichaje';
        $total_horas_fmt = '0:00';

        if ($prim && $ult) {
            $tIn  = new DateTime($prim);
            $tOut = new DateTime($ult);
            $diff = $tOut->diff($tIn);
            $minTrabajoTotal = $diff->h * 60 + $diff->i;
            $minNeto = max(0, $minTrabajoTotal - $minDescanso);
            $hDec = $minNeto / 60.0;
            $h = (int)floor($hDec);
            $m = (int)round(($hDec - $h) * 60);
            if ($m === 60) { $h++; $m = 0; }
            $total_horas_fmt = sprintf('%d:%02d', $h, $m);

            $totalHoras += $hDec;
            $totalDias++;
            $entradaArr[] = $prim;
            $salidaArr[]  = $ult;

            if ((int)$row['entradas'] > 0 && (int)$row['salidas'] > 0) {
                if ($hDec >= 7.5)      $estado = 'Completo';
                elseif ($hDec >= 4.0)  $estado = 'Parcial';
                elseif ($hDec > 0.0)   $estado = 'Incompleto';
                else                   $estado = 'Sin tiempo válido';
            } elseif ((int)$row['entradas'] > 0 && (int)$row['salidas'] == 0) {
                $estado = 'Sin salida';
            } elseif ((int)$row['entradas'] == 0 && (int)$row['salidas'] > 0) {
                $estado = 'Sin entrada';
            }

            // horas extra (sobre 8h/día)
            if ($hDec > 8.0) {
                $horasExtras += ($hDec - 8.0);
            }
        } else {
            if ((int)$row['entradas'] > 0 || (int)$row['salidas'] > 0) {
                $estado = ((int)$row['entradas'] > 0 && (int)$row['salidas'] == 0) ? 'Sin salida' : 'Fichaje incompleto';
            }
        }

        $detalle[] = [
            'fecha'            => $fecha,
            'primera_entrada'  => $prim,
            'ultima_salida'    => $ult,
            'total_horas'      => $total_horas_fmt,
            'descansos'        => $horasDescansoFmt,
            'estado'           => $estado,
        ];
    }

    return [$detalle, $totalDias, $totalHoras, $entradaArr, $salidaArr, $horasExtras];
}

/**
 * Conteos de solicitudes por estado/tipo en período.
 * Devuelve array de filas- estado, tipo, total
 */
function obtenerConteoSolicitudesPorEstadoTipo(int $empleadoId, string $fechaInicio, string $fechaFin): array {
    global $pdo;
    $sql = "SELECT 
                estado, 
                tipo, 
                COUNT(*) AS total,
                SUM(DATEDIFF(fecha_fin, fecha_inicio) + 1) AS dias_totales
            FROM solicitudes
            WHERE empleado_id = ?
              AND DATE(fecha_solicitud) BETWEEN ? AND ?
            GROUP BY estado, tipo";
    $st = $pdo->prepare($sql);
    $st->execute([$empleadoId, $fechaInicio, $fechaFin]);
    return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
}

// Calcula el tiempo trabajado y pausado usando la misma lógica que el servidor
function calcularTiemposHoy($pdo, $empleadoId) {
    $hoyIni = date('Y-m-d 00:00:00');
    $hoyFin = date('Y-m-d 23:59:59');
    
    $sqlEstado = "SELECT tipo, hora FROM fichajes WHERE empleado_id = ? AND hora BETWEEN ? AND ? ORDER BY hora ASC";
    $reg = $pdo->prepare($sqlEstado);
    $reg->execute([$empleadoId, $hoyIni, $hoyFin]);
    $rows = $reg->fetchAll(PDO::FETCH_ASSOC);
    
    $state = 'none';
    $workSec = 0;
    $pauseSec = 0;
    $ultimoTipo = null;
    $ultimoTst = null;
    
    foreach ($rows as $row) {
        $actualTst = strtotime($row['hora']);
        
        if ($row['tipo'] === 'entrada') {
            $state = 'working';
            $ultimoTipo = 'entrada';
            $ultimoTst = $actualTst;
        }
        elseif ($row['tipo'] === 'pausa_inicio' && ($ultimoTipo === 'entrada' || $ultimoTipo === 'pausa_fin')) {
            $workSec += $actualTst - $ultimoTst;
            $state = 'paused';
            $ultimoTipo = 'pausa_inicio';
            $ultimoTst = $actualTst;
        }
        elseif ($row['tipo'] === 'pausa_fin' && $ultimoTipo === 'pausa_inicio') {
            $pauseSec += $actualTst - $ultimoTst;
            $state = 'working';
            $ultimoTipo = 'pausa_fin';
            $ultimoTst = $actualTst;
        }
        elseif ($row['tipo'] === 'salida') {
            if ($ultimoTipo === 'entrada') {
                $workSec += $actualTst - $ultimoTst;
            } elseif ($ultimoTipo === 'pausa_inicio') {
                $pauseSec += $actualTst - $ultimoTst;
            } elseif ($ultimoTipo === 'pausa_fin') {
                $workSec += $actualTst - $ultimoTst;
            }
            $state = 'none';
            $ultimoTipo = null;
            $ultimoTst = null;
        }
    }
    
    // Si sigue en trabajo o pausa, sumar hasta ahora
    if ($state === 'working' && $ultimoTst !== null) {
        $workSec += time() - $ultimoTst;
    }
    if ($state === 'paused' && $ultimoTst !== null) {
        $pauseSec += time() - $ultimoTst;
    }
    
    return [
        'state' => $state,
        'workSec' => $workSec,
        'pauseSec' => $pauseSec
    ];
}

/* FUNCIONES PARA ADMIN - GESTIÓN DE SOLICITUDES */

/**
 * Renderiza un badge HTML con el estado de una solicitud
 * @param string $estado Estado de la solicitud (pendiente, aprobado, rechazado)
 * @return string HTML del badge
 */
function renderEstadoBadge($estado) {
    $estadoConfig = [
        'pendiente' => ['bg' => 'bg-warning text-dark', 'icon' => 'ti-clock'],
        'aprobado' => ['bg' => 'bg-success', 'icon' => 'ti-check'],
        'rechazado' => ['bg' => 'bg-danger', 'icon' => 'ti-x']
    ];
    
    $config = $estadoConfig[$estado] ?? $estadoConfig['pendiente'];
    return sprintf(
        '<span class="badge text-nowrap %s"><i class="ti %s me-1"></i>%s</span>',
        $config['bg'],
        $config['icon'],
        ucfirst($estado)
    );
}

/**
 * Obtiene solicitudes con filtros aplicados
 * @param PDO $pdo Conexión a BD
 * @param array $filtros Array con filtros (estado, tipo, empleado, fecha_desde, fecha_hasta)
 * @return array Array de solicitudes
 */
function obtenerSolicitudesConFiltros($pdo, $filtros = []) {
    $whereConditions = [];
    $params = [];
    
    // Filtro por estado
    if (!empty($filtros['estado'])) {
        $whereConditions[] = "s.estado = ?";
        $params[] = $filtros['estado'];
    }
    
    // Filtro por tipo
    if (!empty($filtros['tipo'])) {
        $whereConditions[] = "s.tipo = ?";
        $params[] = $filtros['tipo'];
    }
    
    // Filtro por empleado (nombre o apellidos)
    if (!empty($filtros['empleado'])) {
        $whereConditions[] = "(e.nombre LIKE ? OR e.apellidos LIKE ?)";
        $params[] = "%{$filtros['empleado']}%";
        $params[] = "%{$filtros['empleado']}%";
    }
    
    // Filtro por fecha desde
    if (!empty($filtros['fecha_desde'])) {
        $whereConditions[] = "DATE(s.fecha_solicitud) >= ?";
        $params[] = $filtros['fecha_desde'];
    }
    
    // Filtro por fecha hasta
    if (!empty($filtros['fecha_hasta'])) {
        $whereConditions[] = "DATE(s.fecha_solicitud) <= ?";
        $params[] = $filtros['fecha_hasta'];
    }
    
    $whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';
    
    try {
        $sql = "
            SELECT s.*, e.nombre, e.apellidos, e.email, e.avatar,
                   CASE 
                       WHEN s.supervisor_id IS NOT NULL THEN 
                           CONCAT(a.nombre, ' ', a.apellidos)
                       ELSE NULL 
                   END as aprobador_nombre
            FROM solicitudes s
            JOIN empleados e ON e.id = s.empleado_id
            LEFT JOIN empleados a ON a.id = s.supervisor_id
            $whereClause
            ORDER BY s.fecha_solicitud DESC
        ";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        return [];
    }
}

/**
 * Obtiene estadísticas de solicitudes con filtros
 * @param PDO $pdo Conexión a BD
 * @param array $filtros Array con filtros
 * @return array Array con estadísticas (total, pendientes, aprobadas, rechazadas)
 */
function obtenerEstadisticasSolicitudes($pdo, $filtros = []) {
    $whereConditions = [];
    $params = [];
    
    // Aplicar los mismos filtros
    if (!empty($filtros['estado'])) {
        $whereConditions[] = "s.estado = ?";
        $params[] = $filtros['estado'];
    }
    if (!empty($filtros['tipo'])) {
        $whereConditions[] = "s.tipo = ?";
        $params[] = $filtros['tipo'];
    }
    if (!empty($filtros['empleado'])) {
        $whereConditions[] = "(e.nombre LIKE ? OR e.apellidos LIKE ?)";
        $params[] = "%{$filtros['empleado']}%";
        $params[] = "%{$filtros['empleado']}%";
    }
    if (!empty($filtros['fecha_desde'])) {
        $whereConditions[] = "DATE(s.fecha_solicitud) >= ?";
        $params[] = $filtros['fecha_desde'];
    }
    if (!empty($filtros['fecha_hasta'])) {
        $whereConditions[] = "DATE(s.fecha_solicitud) <= ?";
        $params[] = $filtros['fecha_hasta'];
    }
    
    $whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';
    
    try {
        $sql = "
            SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN estado = 'pendiente' THEN 1 ELSE 0 END) as pendientes,
                SUM(CASE WHEN estado = 'aprobado' THEN 1 ELSE 0 END) as aprobadas,
                SUM(CASE WHEN estado = 'rechazado' THEN 1 ELSE 0 END) as rechazadas
            FROM solicitudes s
            JOIN empleados e ON e.id = s.empleado_id
            $whereClause
        ";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?? ['total' => 0, 'pendientes' => 0, 'aprobadas' => 0, 'rechazadas' => 0];
    } catch (Exception $e) {
        return ['total' => 0, 'pendientes' => 0, 'aprobadas' => 0, 'rechazadas' => 0];
    }
}
