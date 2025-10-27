<?php
/**
 * Página de Gestión de Notificaciones - Panel de Administración
 * 
 * Sistema para visualizar y gestionar todas las notificaciones del sistema.
 * Permite ver el historial completo, filtrar por tipo y estado, y realizar
 * acciones de administración sobre las notificaciones existentes.
 * 
 * Funcionalidades:
 * - Listado completo de notificaciones con paginación
 * - Filtros por tipo, estado de lectura y fecha
 * - Estadísticas de notificaciones
 * - Eliminación masiva de notificaciones antiguas
 * - Vista responsive adaptada a móviles y tablets
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

// Parámetros de filtrado
$filtroTipo = $_GET['tipo'] ?? '';
$filtroLeido = $_GET['leido'] ?? '';
$filtroFecha = $_GET['fecha'] ?? '';
$pagina = intval($_GET['pagina'] ?? 1);
$porPagina = 20;
$offset = ($pagina - 1) * $porPagina;

// Construcción de filtros
$whereConditions = [];
$params = [];

if ($filtroTipo) {
    $whereConditions[] = "tipo = ?";
    $params[] = $filtroTipo;
}

if ($filtroLeido !== '') {
    $whereConditions[] = "leido = ?";
    $params[] = $filtroLeido;
}

if ($filtroFecha) {
    $whereConditions[] = "DATE(fecha) = ?";
    $params[] = $filtroFecha;
}

$whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';

try {
    // Obtener estadísticas generales
    $statsStmt = $pdo->prepare("
        SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN leido = 0 THEN 1 ELSE 0 END) as no_leidas,
            SUM(CASE WHEN tipo = 'alerta' THEN 1 ELSE 0 END) as alertas,
            SUM(CASE WHEN tipo = 'solicitud' THEN 1 ELSE 0 END) as solicitudes,
            SUM(CASE WHEN tipo = 'aprobacion' THEN 1 ELSE 0 END) as aprobaciones,
            SUM(CASE WHEN tipo = 'info' THEN 1 ELSE 0 END) as informativas
        FROM notificaciones
        $whereClause
    ");
    $statsStmt->execute($params);
    $stats = $statsStmt->fetch(PDO::FETCH_ASSOC);

    // Contar total para paginación
    $countStmt = $pdo->prepare("SELECT COUNT(*) as total FROM notificaciones $whereClause");
    $countStmt->execute($params);
    $totalRegistros = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
    $totalPaginas = ceil($totalRegistros / $porPagina);

    // Obtener notificaciones con información del empleado
    $stmt = $pdo->prepare("
        SELECT n.*, 
               CASE 
                   WHEN n.empleado_id IS NULL THEN 'Global'
                   ELSE CONCAT(e.nombre, ' ', e.apellidos)
               END as destinatario,
               e.rol
        FROM notificaciones n
        LEFT JOIN empleados e ON n.empleado_id = e.id
        $whereClause
        ORDER BY n.fecha DESC
        LIMIT ? OFFSET ?
    ");
    
    $paramsConLimit = array_merge($params, [$porPagina, $offset]);
    $stmt->execute($paramsConLimit);
    $notificaciones = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (Exception $e) {
    $notificaciones = [];
    $stats = ['total' => 0, 'no_leidas' => 0, 'alertas' => 0, 'solicitudes' => 0, 'aprobaciones' => 0, 'informativas' => 0];
    $totalRegistros = 0;
    $totalPaginas = 0;
    error_log("Error en ver-notificaciones.php: " . $e->getMessage());
}

// Procesamiento de acciones
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['accion'])) {
        switch ($_POST['accion']) {
            case 'eliminar_antiguas':
                try {
                    $diasAtras = intval($_POST['dias_atras'] ?? 30);
                    $stmt = $pdo->prepare("DELETE FROM notificaciones WHERE fecha < DATE_SUB(NOW(), INTERVAL ? DAY)");
                    $stmt->execute([$diasAtras]);
                    $mensaje_exito = "Se eliminaron las notificaciones anteriores a $diasAtras días.";
                } catch (Exception $e) {
                    $mensaje_error = "Error al eliminar notificaciones: " . $e->getMessage();
                }
                break;

            case 'marcar_todas_leidas':
                try {
                    $stmt = $pdo->prepare("UPDATE notificaciones SET leido = 1 WHERE leido = 0");
                    $stmt->execute();
                    $mensaje_exito = "Todas las notificaciones han sido marcadas como leídas.";
                } catch (Exception $e) {
                    $mensaje_error = "Error al marcar notificaciones: " . $e->getMessage();
                }
                break;
        }
        
        // Recargar la página para mostrar cambios
        header("Location: " . $_SERVER['REQUEST_URI']);
        exit;
    }
}
?>

<div class="mb-3 overflow-hidden position-relative">
    <div class="px-3">
        <h4 class="fs-6 mb-0 mt-2">Gestión de Notificaciones</h4>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item">
                    <a href="<?= $config['ruta_absoluta'] ?>dashboard">Inicio</a>
                </li>
                <li class="breadcrumb-item">
                    <a href="<?= $config['ruta_absoluta'] ?>admin/empleados">Administración</a>
                </li>
                <li class="breadcrumb-item active" aria-current="page">Ver Notificaciones</li>
            </ol>
        </nav>
    </div>
</div>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <!-- Mensajes de estado -->
            <?php if (isset($mensaje_exito)): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="ti ti-check-circle me-2"></i>
                    <?= htmlspecialchars($mensaje_exito) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if (isset($mensaje_error)): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="ti ti-exclamation-triangle me-2"></i>
                    <?= htmlspecialchars($mensaje_error) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <!-- Estadísticas -->
            <div class="row mb-4">
                <div class="col-lg-3 col-md-6 col-sm-6">
                    <div class="card bg-primary-subtle">
                        <div class="card-body text-center">
                            <div class="d-flex align-items-center justify-content-center">
                                <div>
                                    <h3 class="fw-semibold mb-0 text-primary"><?= number_format($stats['total']) ?></h3>
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
                                    <h3 class="fw-semibold mb-0 text-warning"><?= number_format($stats['no_leidas']) ?></h3>
                                    <p class="mb-0 text-warning">No leídas</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 col-sm-6">
                    <div class="card bg-danger-subtle">
                        <div class="card-body text-center">
                            <div class="d-flex align-items-center justify-content-center">
                                <div>
                                    <h3 class="fw-semibold mb-0 text-danger"><?= number_format($stats['alertas']) ?></h3>
                                    <p class="mb-0 text-danger">Alertas</p>
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
                                    <h3 class="fw-semibold mb-0 text-info"><?= number_format($stats['solicitudes']) ?></h3>
                                    <p class="mb-0 text-info">Solicitudes</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Botones de acción -->
            <div class="d-flex justify-content-between mb-3 flex-wrap gap-2">
                <div class="d-flex gap-2 flex-wrap">
                    <a href="<?= $config['ruta_absoluta'] ?>admin/notificaciones" class="btn btn-primary">
                        <i class="ti ti-plus me-1"></i>Nueva notificación
                    </a>
                    
                    <!-- Botón de acciones masivas -->
                    <div class="dropdown">
                        <button class="btn btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                            <i class="ti ti-settings me-1"></i>Acciones
                        </button>
                        <ul class="dropdown-menu">
                            <li>
                                <button class="dropdown-item" data-bs-toggle="modal" data-bs-target="#modalEliminarAntiguas">
                                    <i class="ti ti-trash me-2"></i>Eliminar antiguas
                                </button>
                            </li>
                            <li>
                                <form method="post" class="d-inline">
                                    <input type="hidden" name="accion" value="marcar_todas_leidas">
                                    <button type="submit" class="dropdown-item" onclick="return confirm('¿Marcar todas como leídas?')">
                                        <i class="ti ti-check-circle me-2"></i>Marcar todas leídas
                                    </button>
                                </form>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Filtros -->
            <div class="card mb-4">
                <div class="card-body">
                    <h5 class="card-title mb-3">
                        <i class="ti ti-filter me-2"></i>Filtros
                    </h5>
                    
                    <form method="GET" id="filtrosNotificaciones">
                        <div class="row g-3">
                            <div class="col-12 col-sm-6 col-lg-3">
                                <label for="tipo" class="form-label">Tipo</label>
                                <select class="form-select" id="tipo" name="tipo">
                                    <option value="">Todos los tipos</option>
                                    <option value="info" <?= $filtroTipo === 'info' ? 'selected' : '' ?>>📘 Información</option>
                                    <option value="solicitud" <?= $filtroTipo === 'solicitud' ? 'selected' : '' ?>>🔔 Solicitud</option>
                                    <option value="aprobacion" <?= $filtroTipo === 'aprobacion' ? 'selected' : '' ?>>✅ Aprobación</option>
                                    <option value="alerta" <?= $filtroTipo === 'alerta' ? 'selected' : '' ?>>⚠️ Alerta</option>
                                </select>
                            </div>
                            
                            <div class="col-12 col-sm-6 col-lg-3">
                                <label for="leido" class="form-label">Estado</label>
                                <select class="form-select" id="leido" name="leido">
                                    <option value="">Todos los estados</option>
                                    <option value="0" <?= $filtroLeido === '0' ? 'selected' : '' ?>>No leídas</option>
                                    <option value="1" <?= $filtroLeido === '1' ? 'selected' : '' ?>>Leídas</option>
                                </select>
                            </div>

                            <div class="col-12 col-sm-6 col-lg-3">
                                <label for="fecha" class="form-label">Fecha</label>
                                <input type="date" class="form-control" id="fecha" name="fecha" 
                                       value="<?= htmlspecialchars($filtroFecha) ?>">
                            </div>
                            
                            <div class="col-12 col-sm-6 col-lg-3 d-flex align-items-end">
                                <div class="w-100 d-flex gap-2">
                                    <button type="submit" class="btn btn-primary flex-fill">
                                        <i class="ti ti-search me-1"></i>Filtrar
                                    </button>
                                    <button type="button" class="btn btn-outline-secondary" id="btnLimpiarFiltros">
                                        <i class="ti ti-x"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Lista de notificaciones -->
            <div class="card">
                <div class="card-body">
                    <?php if (empty($notificaciones)): ?>
                        <div class="text-center py-4">
                            <i class="ti ti-bell-off display-4 text-muted"></i>
                            <h4 class="text-muted mt-3">No hay notificaciones</h4>
                            <p class="text-muted">No se encontraron notificaciones con los filtros aplicados.</p>
                        </div>
                    <?php else: ?>
                        <!-- Vista de tabla para pantallas grandes -->
                        <div class="table-responsive d-none d-lg-block">
                            <table class="table table-striped table-hover">
                                <thead class="bg-primary text-white">
                                    <tr>
                                        <th>Tipo</th>
                                        <th>Mensaje</th>
                                        <th>Destinatario</th>
                                        <th>Fecha</th>
                                        <th>Estado</th>
                                        <th>URL</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($notificaciones as $notif): ?>
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
                                        <tr class="<?= !$notif['leido'] ? 'fw-bold' : '' ?>">
                                            <td>
                                                <span class="badge bg-<?= $colorTipo ?>">
                                                    <?= $iconoTipo ?> <?= ucfirst($notif['tipo']) ?>
                                                </span>
                                            </td>
                                            <td>
                                                <div style="max-width: 300px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                                    <?= htmlspecialchars($notif['mensaje']) ?>
                                                </div>
                                            </td>
                                            <td>
                                                <?= htmlspecialchars($notif['destinatario']) ?>
                                                <?php if ($notif['rol']): ?>
                                                    <small class="text-muted d-block"><?= ucfirst($notif['rol']) ?></small>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <small>
                                                    <?= date('d/m/Y', strtotime($notif['fecha'])) ?><br>
                                                    <?= date('H:i', strtotime($notif['fecha'])) ?>
                                                </small>
                                            </td>
                                            <td>
                                                <?php if ($notif['leido']): ?>
                                                    <span class="badge bg-success">Leída</span>
                                                <?php else: ?>
                                                    <span class="badge bg-warning">Nueva</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($notif['url']): ?>
                                                    <a href="<?= htmlspecialchars($notif['url']) ?>" target="_blank" class="btn btn-sm btn-outline-primary">
                                                        <i class="ti ti-external-link"></i>
                                                    </a>
                                                <?php else: ?>
                                                    <span class="text-muted">-</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <button type="button" class="btn btn-sm btn-outline-danger btn-eliminar-notif-admin" data-notification-id="<?= $notif['id'] ?>" title="Eliminar notificación">
                                                    <i class="ti ti-trash"></i>
                                                </button>
                                            </td>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <!-- Vista de tarjetas para móviles -->
                        <div class="d-lg-none">
                            <?php foreach ($notificaciones as $notif): ?>
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
                                <div class="card mb-3 <?= !$notif['leido'] ? 'border-warning' : '' ?>">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-start mb-2">
                                            <span class="badge bg-<?= $colorTipo ?>">
                                                <?= $iconoTipo ?> <?= ucfirst($notif['tipo']) ?>
                                            </span>
                                            <?php if ($notif['leido']): ?>
                                                <span class="badge bg-success">Leída</span>
                                            <?php else: ?>
                                                <span class="badge bg-warning">Nueva</span>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <p class="card-text <?= !$notif['leido'] ? 'fw-bold' : '' ?>">
                                            <?= htmlspecialchars($notif['mensaje']) ?>
                                        </p>
                                        
                                        <div class="d-flex justify-content-between align-items-center">
                                            <small class="text-muted">
                                                <i class="ti ti-user me-1"></i>
                                                <?= htmlspecialchars($notif['destinatario']) ?>
                                            </small>
                                            <small class="text-muted">
                                                <?= date('d/m/Y H:i', strtotime($notif['fecha'])) ?>
                                            </small>
                                        </div>
                                        
                                        <div class="d-flex gap-2 mt-2">
                                            <?php if ($notif['url']): ?>
                                                <a href="<?= htmlspecialchars($notif['url']) ?>" target="_blank" class="btn btn-sm btn-outline-primary">
                                                    <i class="ti ti-external-link me-1"></i>Ver enlace
                                                </a>
                                            <?php endif; ?>
                                            <button type="button" class="btn btn-sm btn-outline-danger btn-eliminar-notif-admin" data-notification-id="<?= $notif['id'] ?>" title="Eliminar notificación">
                                                <i class="ti ti-trash me-1"></i>Eliminar
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <!-- Paginación -->
                        <?php if ($totalPaginas > 1): ?>
                            <nav aria-label="Paginación de notificaciones" class="mt-4">
                                <ul class="pagination justify-content-center">
                                    <li class="page-item <?= $pagina <= 1 ? 'disabled' : '' ?>">
                                        <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['pagina' => $pagina - 1])) ?>">
                                            <i class="ti ti-chevron-left"></i>
                                        </a>
                                    </li>
                                    
                                    <?php
                                    $inicio = max(1, $pagina - 2);
                                    $fin = min($totalPaginas, $pagina + 2);
                                    
                                    for ($i = $inicio; $i <= $fin; $i++):
                                    ?>
                                        <li class="page-item <?= $i === $pagina ? 'active' : '' ?>">
                                            <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['pagina' => $i])) ?>">
                                                <?= $i ?>
                                            </a>
                                        </li>
                                    <?php endfor; ?>
                                    
                                    <li class="page-item <?= $pagina >= $totalPaginas ? 'disabled' : '' ?>">
                                        <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['pagina' => $pagina + 1])) ?>">
                                            <i class="ti ti-chevron-right"></i>
                                        </a>
                                    </li>
                                </ul>
                            </nav>
                            
                            <div class="text-center text-muted">
                                Mostrando <?= count($notificaciones) ?> de <?= $totalRegistros ?> notificaciones
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal para eliminar notificaciones antiguas -->
<div class="modal fade" id="modalEliminarAntiguas" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="ti ti-trash me-2"></i>
                    Eliminar notificaciones antiguas
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="post">
                <div class="modal-body">
                    <input type="hidden" name="accion" value="eliminar_antiguas">
                    <p>¿Cuántos días atrás desea conservar las notificaciones?</p>
                    <div class="mb-3">
                        <label for="dias_atras" class="form-label">Días</label>
                        <select name="dias_atras" id="dias_atras" class="form-select">
                            <option value="7">7 días (última semana)</option>
                            <option value="30" selected>30 días (último mes)</option>
                            <option value="90">90 días (últimos 3 meses)</option>
                            <option value="365">365 días (último año)</option>
                        </select>
                    </div>
                    <div class="alert alert-warning">
                        <i class="ti ti-alert-triangle me-2"></i>
                        Esta acción no se puede deshacer. Se eliminarán permanentemente todas las notificaciones anteriores al período seleccionado.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-danger">
                        <i class="ti ti-trash me-1"></i>Eliminar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Limpiar filtros
    document.getElementById('btnLimpiarFiltros').addEventListener('click', function() {
        window.location.href = window.location.pathname;
    });
    
    // Eliminar notificación individual
    document.querySelectorAll('.btn-eliminar-notif-admin').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const notifId = this.getAttribute('data-notification-id');
            
            if (confirm('¿Estás seguro de que deseas eliminar esta notificación?')) {
                fetch(BASE_URL + 'notificaciones/eliminar-notificacion.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'notificacion_id=' + encodeURIComponent(notifId)
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Buscar la fila o tarjeta y eliminarla
                        const row = document.querySelector(`tr:has([data-notification-id="${notifId}"])`);
                        const card = document.querySelector(`[data-notification-id="${notifId}"]`)?.closest('.card');
                        
                        if (row) row.remove();
                        if (card) card.remove();
                        
                        // Recargar la página para actualizar contadores y paginación
                        setTimeout(() => {
                            location.reload();
                        }, 500);
                    } else {
                        alert('Error al eliminar la notificación: ' + (data.error || 'Error desconocido'));
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error al eliminar la notificación');
                });
            }
        });
    });
});
</script>

<?php
// Definir BASE_URL para el JavaScript
echo "<script>var BASE_URL = '" . $config['ruta_absoluta'] . "';</script>";
?>
