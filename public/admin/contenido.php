<?php
/**
 * Panel de Administración de Contenido - Sistema Control Horario
 * 
 * Centraliza la gestión de contenido del sistema incluyendo:
 * - Gestión de notificaciones del sistema
 * - Administración de archivos subidos
 * - Gestión de páginas y contenido estático
 * - Configuración de medios y recursos
 * 
 * Este panel proporciona acceso unificado a todas las herramientas
 * de gestión de contenido para administradores y supervisores.
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
if (!$empleado || !in_array($empleado['rol'], ['admin', 'supervisor'])) {
    header('Location: ' . $config['ruta_absoluta'] . 'dashboard');
    exit;
}

// Obtener estadísticas del sistema
try {
    // Estadísticas de notificaciones
    $notifStmt = $pdo->prepare("
        SELECT 
            COUNT(*) as total_notificaciones,
            SUM(CASE WHEN leido = 0 THEN 1 ELSE 0 END) as notificaciones_no_leidas,
            SUM(CASE WHEN tipo = 'alerta' THEN 1 ELSE 0 END) as alertas_activas
        FROM notificaciones
    ");
    $notifStmt->execute();
    $statsNotificaciones = $notifStmt->fetch(PDO::FETCH_ASSOC);

    // Estadísticas de archivos
    $uploadsDir = __DIR__ . '/../../uploads';
    $archivosSolicitudes = glob($uploadsDir . '/solicitudes/*');
    $archivosUsuarios = glob($uploadsDir . '/usuarios/*');
    
    $totalArchivos = count($archivosSolicitudes) + count($archivosUsuarios);
    $pesoTotal = 0;
    
    foreach (array_merge($archivosSolicitudes, $archivosUsuarios) as $archivo) {
        if (is_file($archivo)) {
            $pesoTotal += filesize($archivo);
        }
    }
    
    $pesoTotalMB = round($pesoTotal / 1024 / 1024, 2);

    // Estadísticas de empleados
    $empStmt = $pdo->prepare("SELECT COUNT(*) as total_empleados FROM empleados");
    $empStmt->execute();
    $totalEmpleados = $empStmt->fetch(PDO::FETCH_ASSOC)['total_empleados'];

} catch (Exception $e) {
    $statsNotificaciones = ['total_notificaciones' => 0, 'notificaciones_no_leidas' => 0, 'alertas_activas' => 0];
    $totalArchivos = 0;
    $pesoTotalMB = 0;
    $totalEmpleados = 0;
    error_log("Error obteniendo estadísticas: " . $e->getMessage());
}

// Obtener últimas notificaciones para el resumen
try {
    $ultimasNotifStmt = $pdo->prepare("
        SELECT n.*, 
               CASE 
                   WHEN n.empleado_id IS NULL THEN 'Global'
                   ELSE CONCAT(e.nombre, ' ', e.apellidos)
               END as destinatario
        FROM notificaciones n
        LEFT JOIN empleados e ON n.empleado_id = e.id
        ORDER BY n.fecha DESC
        LIMIT 5
    ");
    $ultimasNotifStmt->execute();
    $ultimasNotificaciones = $ultimasNotifStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $ultimasNotificaciones = [];
    error_log("Error obteniendo últimas notificaciones: " . $e->getMessage());
}
?>

<div class="mb-3 overflow-hidden position-relative">
    <div class="px-3">
        <h4 class="fs-6 mb-0 mt-2">Administración de Contenido</h4>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item">
                    <a href="<?= $config['ruta_absoluta'] ?>dashboard">Inicio</a>
                </li>
                <li class="breadcrumb-item active" aria-current="page">Administración de Contenido</li>
            </ol>
        </nav>
    </div>
</div>

<div class="container-fluid py-4">
    <!-- Estadísticas generales -->
    <div class="row mb-4">
        <div class="col-lg-3 col-md-6 col-sm-6">
            <div class="card bg-primary-subtle">
                <div class="card-body text-center">
                    <div class="d-flex align-items-center justify-content-center">
                        <div>
                            <h3 class="fw-semibold mb-0 text-primary"><?= number_format($statsNotificaciones['total_notificaciones']) ?></h3>
                            <p class="mb-0 text-primary">Total notificaciones</p>
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
                            <h3 class="fw-semibold mb-0 text-warning"><?= number_format($statsNotificaciones['notificaciones_no_leidas']) ?></h3>
                            <p class="mb-0 text-warning">No leídas</p>
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
                            <h3 class="fw-semibold mb-0 text-info"><?= number_format($totalArchivos) ?></h3>
                            <p class="mb-0 text-info">Archivos subidos</p>
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
                            <h3 class="fw-semibold mb-0 text-success"><?= $pesoTotalMB ?> MB</h3>
                            <p class="mb-0 text-success">Espacio utilizado</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Panel principal de acciones -->
        <div class="col-lg-8">
            <!-- Gestión de Notificaciones -->
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">
                        <i class="ti ti-bell me-2"></i>
                        Gestión de Notificaciones
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <a href="<?= $config['ruta_absoluta'] ?>admin/notificaciones" class="btn btn-primary w-100 py-3">
                                <i class="ti ti-plus fs-4 d-block mb-2"></i>
                                <strong>Crear Notificación</strong>
                                <br><small>Enviar nueva notificación a empleados</small>
                            </a>
                        </div>
                        <div class="col-md-6">
                            <a href="<?= $config['ruta_absoluta'] ?>admin/ver-notificaciones" class="btn btn-outline-primary w-100 py-3">
                                <i class="ti ti-list fs-4 d-block mb-2"></i>
                                <strong>Ver Notificaciones</strong>
                                <br><small>Gestionar notificaciones existentes</small>
                            </a>
                        </div>
                    </div>
                    
                    <!-- Resumen rápido -->
                    <div class="mt-3 p-3 bg-light rounded">
                        <div class="row text-center">
                            <div class="col-4">
                                <h6 class="text-primary mb-1"><?= $statsNotificaciones['total_notificaciones'] ?></h6>
                                <small class="text-muted">Total</small>
                            </div>
                            <div class="col-4">
                                <h6 class="text-warning mb-1"><?= $statsNotificaciones['notificaciones_no_leidas'] ?></h6>
                                <small class="text-muted">No leídas</small>
                            </div>
                            <div class="col-4">
                                <h6 class="text-danger mb-1"><?= $statsNotificaciones['alertas_activas'] ?></h6>
                                <small class="text-muted">Alertas</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Gestión de Archivos -->
            <div class="card mb-4">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0">
                        <i class="ti ti-folder me-2"></i>
                        Gestión de Archivos
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <a href="<?= $config['ruta_absoluta'] ?>admin/gestor-archivos" class="btn btn-info w-100 py-3">
                                <i class="ti ti-files fs-4 d-block mb-2"></i>
                                <strong>Explorar Archivos</strong>
                                <br><small>Ver y gestionar archivos subidos</small>
                            </a>
                        </div>
                        <div class="col-md-6">
                            <a href="<?= $config['ruta_absoluta'] ?>admin/subir-archivos" class="btn btn-outline-info w-100 py-3">
                                <i class="ti ti-upload fs-4 d-block mb-2"></i>
                                <strong>Subir Archivos</strong>
                                <br><small>Cargar nuevos archivos al sistema</small>
                            </a>
                        </div>
                    </div>
                    
                    <!-- Información de almacenamiento -->
                    <div class="mt-3 p-3 bg-light rounded">
                        <div class="row text-center">
                            <div class="col-4">
                                <h6 class="text-info mb-1"><?= count($archivosSolicitudes) ?></h6>
                                <small class="text-muted">Solicitudes</small>
                            </div>
                            <div class="col-4">
                                <h6 class="text-info mb-1"><?= count($archivosUsuarios) ?></h6>
                                <small class="text-muted">Usuarios</small>
                            </div>
                            <div class="col-4">
                                <h6 class="text-info mb-1"><?= $pesoTotalMB ?> MB</h6>
                                <small class="text-muted">Tamaño total</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Gestión de Páginas -->
            <div class="card mb-4">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0">
                        <i class="ti ti-layout me-2"></i>
                        Gestión de Páginas
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <a href="<?= $config['ruta_absoluta'] ?>admin/configuracion" class="btn btn-success w-100 py-3">
                                <i class="ti ti-settings fs-4 d-block mb-2"></i>
                                <strong>Configuración</strong>
                                <br><small>Ajustes del sistema</small>
                            </a>
                        </div>
                        <div class="col-md-4">
                            <a href="<?= $config['ruta_absoluta'] ?>admin/seguridad" class="btn btn-outline-success w-100 py-3">
                                <i class="ti ti-shield fs-4 d-block mb-2"></i>
                                <strong>Seguridad</strong>
                                <br><small>Configuración de seguridad</small>
                            </a>
                        </div>
                        <div class="col-md-4">
                            <a href="<?= $config['ruta_absoluta'] ?>admin/mantenimiento" class="btn btn-outline-success w-100 py-3">
                                <i class="ti ti-tools fs-4 d-block mb-2"></i>
                                <strong>Mantenimiento</strong>
                                <br><small>Tareas de mantenimiento</small>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Panel lateral de información -->
        <div class="col-lg-4">
            <!-- Últimas notificaciones -->
            <div class="card mb-4">
                <div class="card-header">
                    <h6 class="mb-0">
                        <i class="ti ti-bell me-2"></i>
                        Últimas Notificaciones
                    </h6>
                </div>
                <div class="card-body">
                    <?php if (empty($ultimasNotificaciones)): ?>
                        <div class="text-center text-muted py-3">
                            <i class="ti ti-bell-off fs-2"></i>
                            <p class="mb-0 mt-2">Sin notificaciones</p>
                        </div>
                    <?php else: ?>
                        <div style="max-height: 300px; overflow-y: auto;">
                            <?php foreach ($ultimasNotificaciones as $notif): ?>
                                <?php
                                $colorTipo = match($notif['tipo']) {
                                    'alerta' => 'danger',
                                    'aprobacion' => 'success',
                                    'solicitud' => 'info',
                                    default => 'primary'
                                };
                                
                                $iconoTipo = match($notif['tipo']) {
                                    'alerta' => '⚠️',
                                    'aprobacion' => '✅',
                                    'solicitud' => '🔔',
                                    default => '📘'
                                };
                                ?>
                                <div class="border-bottom py-2 <?= !$notif['leido'] ? 'bg-light' : '' ?>">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <span class="badge bg-<?= $colorTipo ?> badge-sm">
                                            <?= $iconoTipo ?>
                                        </span>
                                        <?php if (!$notif['leido']): ?>
                                            <span class="badge bg-warning badge-sm">Nuevo</span>
                                        <?php endif; ?>
                                    </div>
                                    <p class="mb-1 mt-2" style="font-size: 0.9em;">
                                        <?= htmlspecialchars(substr($notif['mensaje'], 0, 80)) ?>
                                        <?= strlen($notif['mensaje']) > 80 ? '...' : '' ?>
                                    </p>
                                    <small class="text-muted">
                                        <i class="ti ti-user me-1"></i><?= htmlspecialchars($notif['destinatario']) ?>
                                        <span class="ms-2">
                                            <i class="ti ti-clock me-1"></i><?= date('d/m H:i', strtotime($notif['fecha'])) ?>
                                        </span>
                                    </small>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <div class="text-center mt-3">
                            <a href="<?= $config['ruta_absoluta'] ?>admin/ver-notificaciones" class="btn btn-sm btn-outline-primary">
                                Ver todas las notificaciones
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Accesos rápidos -->
            <div class="card mb-4">
                <div class="card-header">
                    <h6 class="mb-0">
                        <i class="ti ti-bolt me-2"></i>
                        Accesos Rápidos
                    </h6>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <a href="<?= $config['ruta_absoluta'] ?>admin/empleados" class="btn btn-outline-secondary btn-sm">
                            <i class="ti ti-users me-2"></i>Gestionar Empleados
                        </a>
                        <a href="<?= $config['ruta_absoluta'] ?>admin/ver-solicitudes" class="btn btn-outline-secondary btn-sm">
                            <i class="ti ti-file-text me-2"></i>Ver Solicitudes
                        </a>
                        <a href="<?= $config['ruta_absoluta'] ?>dashboard" class="btn btn-outline-secondary btn-sm">
                            <i class="ti ti-dashboard me-2"></i>Panel Principal
                        </a>
                        <a href="<?= $config['ruta_absoluta'] ?>admin/detalle-fichajes" class="btn btn-outline-secondary btn-sm">
                            <i class="ti ti-clock me-2"></i>Detalle Fichajes
                        </a>
                    </div>
                </div>
            </div>

            <!-- Información del sistema -->
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0">
                        <i class="ti ti-info-circle me-2"></i>
                        Información del Sistema
                    </h6>
                </div>
                <div class="card-body">
                    <ul class="list-unstyled mb-0">
                        <li class="mb-2">
                            <strong>Empleados registrados:</strong>
                            <span class="float-end"><?= $totalEmpleados ?></span>
                        </li>
                        <li class="mb-2">
                            <strong>Archivos subidos:</strong>
                            <span class="float-end"><?= $totalArchivos ?></span>
                        </li>
                        <li class="mb-2">
                            <strong>Espacio utilizado:</strong>
                            <span class="float-end"><?= $pesoTotalMB ?> MB</span>
                        </li>
                        <li class="mb-2">
                            <strong>Última actualización:</strong>
                            <span class="float-end"><?= date('d/m/Y H:i') ?></span>
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
