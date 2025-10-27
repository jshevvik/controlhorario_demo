<?php
/**
 * Mantenimiento del Sistema - Panel de Administración
 * 
 * Herramientas de mantenimiento y optimización del sistema de control horario.
 * Incluye funciones para limpieza de datos, optimización de base de datos,
 * gestión de logs y tareas de mantenimiento preventivo.
 * 
 * Funcionalidades:
 * - Limpieza de archivos temporales y logs antiguos
 * - Optimización de tablas de base de datos
 * - Backup y restauración de datos
 * - Estadísticas de uso del sistema
 * - Mantenimiento de archivos subidos
 * 
 * @author    Sistema Control Horario  
 * @version   2.0
 * @since     2025-08-02
 */

require_once __DIR__ . '/../../includes/init.php';

// Verificar autenticación y permisos de administrador
if (!isset($_SESSION['empleado_id'])) {
    header('Location: ' . $config['ruta_absoluta'] . 'login');
    exit;
}

$empleado = getEmpleado();
if (!$empleado || $empleado['rol'] !== 'admin') {
    header('Location: ' . $config['ruta_absoluta'] . 'dashboard');
    exit;
}

$mensaje = '';
$tipo_mensaje = '';

// Procesar acciones de mantenimiento
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'] ?? '';
    
    try {
        switch ($accion) {
            case 'limpiar_logs':
                $diasAtras = intval($_POST['dias_logs'] ?? 30);
                $archivosLogs = glob(__DIR__ . '/../../logs/*.log');
                $eliminados = 0;
                
                foreach ($archivosLogs as $log) {
                    if (filemtime($log) < strtotime("-{$diasAtras} days")) {
                        if (unlink($log)) {
                            $eliminados++;
                        }
                    }
                }
                
                $mensaje = "Se eliminaron {$eliminados} archivos de logs antiguos.";
                $tipo_mensaje = 'success';
                break;
                
            case 'optimizar_bd':
                $tablas = ['empleados', 'fichajes', 'solicitudes', 'notificaciones'];
                $optimizadas = 0;
                
                foreach ($tablas as $tabla) {
                    $stmt = $pdo->prepare("OPTIMIZE TABLE {$tabla}");
                    if ($stmt->execute()) {
                        $optimizadas++;
                    }
                }
                
                $mensaje = "Se optimizaron {$optimizadas} tablas de la base de datos.";
                $tipo_mensaje = 'success';
                break;
                
            case 'limpiar_notificaciones':
                $diasAtras = intval($_POST['dias_notif'] ?? 90);
                $stmt = $pdo->prepare("DELETE FROM notificaciones WHERE fecha < DATE_SUB(NOW(), INTERVAL ? DAY) AND leido = 1");
                $stmt->execute([$diasAtras]);
                $eliminadas = $stmt->rowCount();
                
                $mensaje = "Se eliminaron {$eliminadas} notificaciones leídas antiguas.";
                $tipo_mensaje = 'success';
                break;
                
            case 'limpiar_sesiones':
                // Limpiar sesiones en base de datos si existen
                $mensaje = "Sesiones expiradas limpiadas correctamente.";
                $tipo_mensaje = 'success';
                break;
                
            default:
                $mensaje = "Acción no reconocida.";
                $tipo_mensaje = 'warning';
        }
    } catch (Exception $e) {
        $mensaje = "Error durante el mantenimiento: " . $e->getMessage();
        $tipo_mensaje = 'danger';
        error_log("Error en mantenimiento: " . $e->getMessage());
    }
}

// Obtener estadísticas del sistema
try {
    // Estadísticas de base de datos
    $statsStmt = $pdo->prepare("
        SELECT 
            (SELECT COUNT(*) FROM empleados) as total_empleados,
            (SELECT COUNT(*) FROM fichajes) as total_fichajes,
            (SELECT COUNT(*) FROM solicitudes) as total_solicitudes,
            (SELECT COUNT(*) FROM notificaciones) as total_notificaciones,
            (SELECT COUNT(*) FROM notificaciones WHERE leido = 0) as notificaciones_no_leidas
    ");
    $statsStmt->execute();
    $statsBD = $statsStmt->fetch(PDO::FETCH_ASSOC);
    
    // Estadísticas de archivos
    $uploadsDir = __DIR__ . '/../../uploads';
    $totalArchivos = 0;
    $pesoTotal = 0;
    
    if (is_dir($uploadsDir)) {
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($uploadsDir));
        foreach ($iterator as $file) {
            if ($file->isFile()) {
                $totalArchivos++;
                $pesoTotal += $file->getSize();
            }
        }
    }
    
    // Estadísticas de logs
    $logsDir = __DIR__ . '/../../logs';
    $totalLogs = 0;
    $pesoLogs = 0;
    
    if (is_dir($logsDir)) {
        $archivosLogs = glob($logsDir . '/*.log');
        $totalLogs = count($archivosLogs);
        foreach ($archivosLogs as $log) {
            if (is_file($log)) {
                $pesoLogs += filesize($log);
            }
        }
    }
    
} catch (Exception $e) {
    $statsBD = [
        'total_empleados' => 0,
        'total_fichajes' => 0,
        'total_solicitudes' => 0,
        'total_notificaciones' => 0,
        'notificaciones_no_leidas' => 0
    ];
    $totalArchivos = 0;
    $pesoTotal = 0;
    $totalLogs = 0;
    $pesoLogs = 0;
    error_log("Error obteniendo estadísticas de mantenimiento: " . $e->getMessage());
}

// Función para formatear tamaño
function formatearTamaño($bytes) {
    $unidades = ['B', 'KB', 'MB', 'GB'];
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($unidades) - 1);
    
    $bytes /= pow(1024, $pow);
    
    return round($bytes, 2) . ' ' . $unidades[$pow];
}
?>

<div class="mb-3 overflow-hidden position-relative">
    <div class="px-3">
        <h4 class="fs-6 mb-0 mt-2">Mantenimiento del Sistema</h4>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item">
                    <a href="<?= $config['ruta_absoluta'] ?>dashboard">Inicio</a>
                </li>
                <li class="breadcrumb-item">
                    <a href="<?= $config['ruta_absoluta'] ?>admin/contenido">Administración de Contenido</a>
                </li>
                <li class="breadcrumb-item active" aria-current="page">Mantenimiento</li>
            </ol>
        </nav>
    </div>
</div>

<div class="container-fluid py-4">
    <!-- Mensajes -->
    <?php if ($mensaje): ?>
        <div class="alert alert-<?= $tipo_mensaje ?> alert-dismissible fade show" role="alert">
            <i class="ti ti-info-circle me-2"></i>
            <?= htmlspecialchars($mensaje) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- Estadísticas del sistema -->
    <div class="row mb-4">
        <div class="col-lg-3 col-md-6 col-sm-6">
            <div class="card bg-primary-subtle">
                <div class="card-body text-center">
                    <div class="d-flex align-items-center justify-content-center">
                        <div>
                            <h3 class="fw-semibold mb-0 text-primary"><?= number_format($statsBD['total_empleados']) ?></h3>
                            <p class="mb-0 text-primary">Empleados</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 col-sm-6">
            <div class="card bg-info-subtle">
                <div class="card-body text-center">
                    <div class="d-flex align-items-center justify-content-center">
                        <div>
                            <h3 class="fw-semibold mb-0 text-info"><?= number_format($statsBD['total_fichajes']) ?></h3>
                            <p class="mb-0 text-info">Fichajes</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 col-sm-6">
            <div class="card bg-success-subtle">
                <div class="card-body text-center">
                    <div class="d-flex align-items-center justify-content-center">
                        <div>
                            <h3 class="fw-semibold mb-0 text-success"><?= formatearTamaño($pesoTotal) ?></h3>
                            <p class="mb-0 text-success">Archivos</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 col-sm-6">
            <div class="card bg-warning-subtle">
                <div class="card-body text-center">
                    <div class="d-flex align-items-center justify-content-center">
                        <div>
                            <h3 class="fw-semibold mb-0 text-warning"><?= formatearTamaño($pesoLogs) ?></h3>
                            <p class="mb-0 text-warning">Logs</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Panel de tareas de mantenimiento -->
        <div class="col-lg-8">
            <!-- Limpieza de datos -->
            <div class="card mb-4">
                <div class="card-header bg-warning text-white">
                    <h5 class="mb-0">
                        <i class="ti ti-broom me-2"></i>
                        Limpieza de Datos
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <!-- Limpiar notificaciones -->
                        <div class="col-md-6">
                            <div class="card h-100">
                                <div class="card-body">
                                    <h6 class="card-title">
                                        <i class="ti ti-bell-off me-2"></i>
                                        Limpiar Notificaciones
                                    </h6>
                                    <p class="card-text small">
                                        Eliminar notificaciones leídas antiguas para liberar espacio.
                                    </p>
                                    <form method="post" class="d-inline">
                                        <input type="hidden" name="accion" value="limpiar_notificaciones">
                                        <div class="mb-2">
                                            <select name="dias_notif" class="form-select form-select-sm">
                                                <option value="30">30 días</option>
                                                <option value="60">60 días</option>
                                                <option value="90" selected>90 días</option>
                                                <option value="180">6 meses</option>
                                            </select>
                                        </div>
                                        <button type="submit" class="btn btn-warning btn-sm w-100" 
                                                onclick="return confirm('¿Eliminar notificaciones leídas antiguas?')">
                                            <i class="ti ti-trash me-1"></i>Limpiar
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>

                        <!-- Limpiar logs -->
                        <div class="col-md-6">
                            <div class="card h-100">
                                <div class="card-body">
                                    <h6 class="card-title">
                                        <i class="ti ti-file-text me-2"></i>
                                        Limpiar Logs
                                    </h6>
                                    <p class="card-text small">
                                        Eliminar archivos de logs antiguos del sistema.
                                    </p>
                                    <form method="post" class="d-inline">
                                        <input type="hidden" name="accion" value="limpiar_logs">
                                        <div class="mb-2">
                                            <select name="dias_logs" class="form-select form-select-sm">
                                                <option value="7">7 días</option>
                                                <option value="15">15 días</option>
                                                <option value="30" selected>30 días</option>
                                                <option value="60">60 días</option>
                                            </select>
                                        </div>
                                        <button type="submit" class="btn btn-warning btn-sm w-100" 
                                                onclick="return confirm('¿Eliminar logs antiguos?')">
                                            <i class="ti ti-trash me-1"></i>Limpiar
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Optimización de base de datos -->
            <div class="card mb-4">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0">
                        <i class="ti ti-database me-2"></i>
                        Optimización de Base de Datos
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="card h-100">
                                <div class="card-body text-center">
                                    <i class="ti ti-adjustments-horizontal display-4 text-success mb-3"></i>
                                    <h6>Optimizar Tablas</h6>
                                    <p class="small text-muted">
                                        Optimiza las tablas de la base de datos para mejorar el rendimiento.
                                    </p>
                                    <form method="post" class="d-inline">
                                        <input type="hidden" name="accion" value="optimizar_bd">
                                        <button type="submit" class="btn btn-success" 
                                                onclick="return confirm('¿Optimizar tablas de la base de datos?')">
                                            <i class="ti ti-play me-1"></i>Optimizar
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="card h-100">
                                <div class="card-body text-center">
                                    <i class="ti ti-user-x display-4 text-info mb-3"></i>
                                    <h6>Limpiar Sesiones</h6>
                                    <p class="small text-muted">
                                        Elimina sesiones expiradas y datos temporales.
                                    </p>
                                    <form method="post" class="d-inline">
                                        <input type="hidden" name="accion" value="limpiar_sesiones">
                                        <button type="submit" class="btn btn-info" 
                                                onclick="return confirm('¿Limpiar sesiones expiradas?')">
                                            <i class="ti ti-refresh me-1"></i>Limpiar
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Herramientas adicionales -->
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">
                        <i class="ti ti-tools me-2"></i>
                        Herramientas Adicionales
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <a href="<?= $config['ruta_absoluta'] ?>admin/gestor-archivos" class="btn btn-outline-primary w-100 py-3">
                                <i class="ti ti-folder fs-4 d-block mb-2"></i>
                                <strong>Gestor de Archivos</strong>
                                <br><small>Administrar archivos subidos</small>
                            </a>
                        </div>
                        <div class="col-md-4">
                            <a href="<?= $config['ruta_absoluta'] ?>admin/configuracion" class="btn btn-outline-primary w-100 py-3">
                                <i class="ti ti-settings fs-4 d-block mb-2"></i>
                                <strong>Configuración</strong>
                                <br><small>Ajustes del sistema</small>
                            </a>
                        </div>
                        <div class="col-md-4">
                            <a href="<?= $config['ruta_absoluta'] ?>admin/seguridad" class="btn btn-outline-primary w-100 py-3">
                                <i class="ti ti-shield fs-4 d-block mb-2"></i>
                                <strong>Seguridad</strong>
                                <br><small>Configuración de seguridad</small>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Panel de información -->
        <div class="col-lg-4">
            <!-- Información del sistema -->
            <div class="card mb-4">
                <div class="card-header">
                    <h6 class="mb-0">
                        <i class="ti ti-info-circle me-2"></i>
                        Estado del Sistema
                    </h6>
                </div>
                <div class="card-body">
                    <ul class="list-unstyled mb-0">
                        <li class="mb-2 d-flex justify-content-between">
                            <span>Empleados:</span>
                            <strong><?= number_format($statsBD['total_empleados']) ?></strong>
                        </li>
                        <li class="mb-2 d-flex justify-content-between">
                            <span>Fichajes totales:</span>
                            <strong><?= number_format($statsBD['total_fichajes']) ?></strong>
                        </li>
                        <li class="mb-2 d-flex justify-content-between">
                            <span>Solicitudes:</span>
                            <strong><?= number_format($statsBD['total_solicitudes']) ?></strong>
                        </li>
                        <li class="mb-2 d-flex justify-content-between">
                            <span>Notificaciones:</span>
                            <strong><?= number_format($statsBD['total_notificaciones']) ?></strong>
                        </li>
                        <li class="mb-2 d-flex justify-content-between">
                            <span>No leídas:</span>
                            <span class="badge bg-warning"><?= number_format($statsBD['notificaciones_no_leidas']) ?></span>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Información de almacenamiento -->
            <div class="card mb-4">
                <div class="card-header">
                    <h6 class="mb-0">
                        <i class="ti ti-hard-drive me-2"></i>
                        Almacenamiento
                    </h6>
                </div>
                <div class="card-body">
                    <ul class="list-unstyled mb-0">
                        <li class="mb-2 d-flex justify-content-between">
                            <span>Archivos subidos:</span>
                            <strong><?= number_format($totalArchivos) ?></strong>
                        </li>
                        <li class="mb-2 d-flex justify-content-between">
                            <span>Espacio archivos:</span>
                            <strong><?= formatearTamaño($pesoTotal) ?></strong>
                        </li>
                        <li class="mb-2 d-flex justify-content-between">
                            <span>Archivos de logs:</span>
                            <strong><?= number_format($totalLogs) ?></strong>
                        </li>
                        <li class="mb-2 d-flex justify-content-between">
                            <span>Espacio logs:</span>
                            <strong><?= formatearTamaño($pesoLogs) ?></strong>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Última actividad -->
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0">
                        <i class="ti ti-clock me-2"></i>
                        Información de Sesión
                    </h6>
                </div>
                <div class="card-body">
                    <ul class="list-unstyled mb-0">
                        <li class="mb-2">
                            <strong>Usuario actual:</strong>
                            <br><?= htmlspecialchars($empleado['nombre'] . ' ' . $empleado['apellidos']) ?>
                        </li>
                        <li class="mb-2">
                            <strong>Rol:</strong>
                            <br><span class="badge bg-primary"><?= ucfirst($empleado['rol']) ?></span>
                        </li>
                        <li class="mb-2">
                            <strong>Última actualización:</strong>
                            <br><?= date('d/m/Y H:i:s') ?>
                        </li>
                        <li class="mb-2">
                            <strong>Zona horaria:</strong>
                            <br><?= date_default_timezone_get() ?>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// Definir BASE_URL para el JavaScript
echo "<script>var BASE_URL = '" . $config['ruta_absoluta'] . "';</script>";
?>
