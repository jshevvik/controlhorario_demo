<?php
require_once __DIR__ . '/../../includes/init.php';

// Verificar que el usuario esté autenticado
if (!isset($_SESSION['empleado_id'])) {
    header('Location: ' . $config['ruta_absoluta'] . 'login');
    exit;
}

// Verificar que el usuario sea administrador o supervisor
$empleado = getEmpleado();
if (!$empleado || !in_array($empleado['rol'], ['admin', 'supervisor'])) {
    header('Location: ' . $config['ruta_absoluta'] . 'dashboard');
    exit;
}



// Parámetros de filtrado simplificados
$filtroNombre = $_GET['nombre'] ?? '';
$filtroRol = $_GET['rol'] ?? '';

// Construir consulta con filtros
$whereConditions = [];
$params = [];

if ($filtroNombre) {
    $whereConditions[] = "(nombre LIKE ? OR apellidos LIKE ?)";
    $params[] = "%$filtroNombre%";
    $params[] = "%$filtroNombre%";
}

if ($filtroRol) {
    $whereConditions[] = "rol = ?";
    $params[] = $filtroRol;
}

$whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';

try {
    // Consulta principal con información de fichaje de hoy
    $stmt = $pdo->prepare("
        SELECT e.*,
               f_entrada.hora as entrada_hoy,
               f_salida.hora as salida_hoy,
               CASE 
                   WHEN f_entrada.hora IS NOT NULL AND f_salida.hora IS NULL THEN 'Trabajando'
                   WHEN f_entrada.hora IS NOT NULL AND f_salida.hora IS NOT NULL THEN 'Completado'
                   ELSE 'Sin fichar'
               END as estado_fichaje
        FROM empleados e
        LEFT JOIN (
            SELECT empleado_id, MIN(hora) as hora
            FROM fichajes 
            WHERE DATE(hora) = CURDATE() AND tipo = 'entrada'
            GROUP BY empleado_id
        ) f_entrada ON e.id = f_entrada.empleado_id
        LEFT JOIN (
            SELECT empleado_id, MAX(hora) as hora
            FROM fichajes 
            WHERE DATE(hora) = CURDATE() AND tipo = 'salida'
            GROUP BY empleado_id
        ) f_salida ON e.id = f_salida.empleado_id
        $whereClause
        ORDER BY nombre, apellidos
    ");
    
    $stmt->execute($params);
    $empleados = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Obtener estadísticas
    $statsStmt = $pdo->prepare("
        SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN rol = 'admin' THEN 1 ELSE 0 END) as admin_count,
            SUM(CASE WHEN rol = 'supervisor' THEN 1 ELSE 0 END) as supervisor_count,
            SUM(CASE WHEN rol = 'empleado' THEN 1 ELSE 0 END) as empleado_count
        FROM empleados
        $whereClause
    ");
    
    $statsStmt->execute($params);
    $stats = $statsStmt->fetch(PDO::FETCH_ASSOC);

} catch (Exception $e) {
    $empleados = [];
    $stats = ['total' => 0, 'admin_count' => 0, 'supervisor_count' => 0, 'empleado_count' => 0];
    error_log("Error en empleados.php: " . $e->getMessage());
}

?>

<div class="mb-3 overflow-hidden position-relative">
    <div class="px-3">
        <h4 class="fs-6 mb-0 mt-2">Gestión de Empleados</h4>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item">
                    <a href="<?= $config['ruta_absoluta'] ?>dashboard">Inicio</a>
                </li>
                <li class="breadcrumb-item">
                    <a href="<?= $config['ruta_absoluta'] ?>administracion">Administración</a>
                </li>
                <li class="breadcrumb-item active" aria-current="page">Empleados</li>
            </ol>
        </nav>
    </div>
</div>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <!-- Estadísticas -->
            <div class="row mb-4">
                <div class="col-lg-3 col-md-6 col-sm-6">
                    <div class="card bg-primary-subtle">
                        <div class="card-body text-center">
                            <div class="d-flex align-items-center justify-content-center">
                                
                                <div>
                                    <h3 class="fw-semibold mb-0 text-primary"><?= number_format($stats['total']) ?></h3>
                                    <p class="mb-0 text-primary">
                                        Total empleados
                                    </p>
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
                                    <h3 class="fw-semibold mb-0 text-success"><?= number_format($stats['admin_count']) ?></h3>
                                    <p class="mb-0 text-success">
                                        Administradores
                                    </p>
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
                                    <h3 class="fw-semibold mb-0 text-warning"><?= number_format($stats['supervisor_count']) ?></h3>
                                    <p class="mb-0 text-warning">
                                        Supervisores
                                    </p>
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
                                    <h3 class="fw-semibold mb-0 text-info"><?= number_format($stats['empleado_count']) ?></h3>
                                    <p class="mb-0 text-info">
                                        Empleados
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Botón de acción (solo admin) -->
            <?php if (canManageEmployees()): ?>
            <div class="d-flex justify-content-end mb-3">
                <a href="<?= $config['ruta_absoluta'] ?>admin/crear-empleado" class="btn btn-primary">
                    <i class="ti ti-plus me-1"></i>Nuevo empleado
                </a>
            </div>
            <?php endif; ?>

            <!-- Filtros -->
            <div class="card mb-4">
                <div class="card-body">
                    <div class="mb-3">
                        <h5 class="card-title mb-0">
                            Filtros
                        </h5>
                    </div>
                    
                    <form method="GET" id="filtrosEmpleados">
                        <div class="row g-3">
                            <div class="col-12 col-sm-6 col-lg-4">
                                <label for="nombre" class="form-label">Buscar empleado</label>
                                <input type="text" class="form-control" id="nombre" name="nombre" 
                                       value="<?= htmlspecialchars($filtroNombre) ?>" placeholder="Buscar por nombre o apellidos...">
                            </div>
                            
                            <div class="col-12 col-sm-6 col-lg-3">
                                <label for="rol" class="form-label">Filtrar por rol</label>
                                <select class="form-select" id="rol" name="rol">
                                    <option value="">Todos los roles</option>
                                    <option value="admin" <?= $filtroRol === 'admin' ? 'selected' : '' ?>>Administrador</option>
                                    <option value="supervisor" <?= $filtroRol === 'supervisor' ? 'selected' : '' ?>>Supervisor</option>
                                    <option value="empleado" <?= $filtroRol === 'empleado' ? 'selected' : '' ?>>Empleado</option>
                                </select>
                            </div>
                            
                            <div class="col-12 col-lg-5 d-flex align-items-end">
                                <div class="w-100">
                                    <!-- Botones responsive -->
                                    <div class="d-flex gap-2 justify-content-center">
                                        <button type="submit" class="btn btn-sm btn-primary">
                                            <i class="ti ti-search"></i>
                                            <span class="d-none d-lg-inline">Filtrar</span>
                                            <span class="d-lg-none"></span>
                                        </button>
                                        <button type="button" class="btn btn-danger btn-sm" id="btnLimpiarFiltros">
                                            <i class="ti ti-x"></i>
                                            <span class="d-none d-lg-inline">Limpiar</span>
                                            <span class="d-lg-none"></span>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Tabla de empleados -->
            <div class="card">
                <div class="card-body">
                    <?php if (empty($empleados)): ?>
                        <div class="text-center py-4">
                            <i class="ti ti-users display-4 text-muted"></i>
                            <h4 class="text-muted mt-3">No hay empleados</h4>
                            <p class="text-muted">No se encontraron empleados con los filtros aplicados.</p>
                        </div>
                    <?php else: ?>
                        <!-- Vista de tabla para pantallas grandes -->
                        <div class="table-responsive d-none d-lg-block">
                            <table class="table table-striped table-hover" id="tabla-empleados">
                                <thead class="bg-info text-white">
                                    <tr>
                                        <th>Empleado</th>
                                        <th>Rol</th>
                                        <th>Email</th>
                                        <th>Fichaje Hoy</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($empleados as $emp): ?>
                                        <?php
                                        $fullName = $emp['nombre'] . ' ' . $emp['apellidos'];
                                        $avatarURL = obtenerAvatarEmpleado($emp, $config);
                                        ?>
                                        <tr>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <div class="avatar-sm bg-light rounded-circle d-flex align-items-center justify-content-center me-2 overflow-hidden" style="width: 40px; height: 40px;">
                                                        <img src="<?= htmlspecialchars($avatarURL) ?>" 
                                                             alt="<?= htmlspecialchars($fullName) ?>" 
                                                             class="rounded-circle" 
                                                             style="width: 40px; height: 40px; object-fit: cover;"
                                                             loading="lazy"
                                                             onerror="this.src='https://www.gravatar.com/avatar/<?= md5(strtolower(trim($emp['email']))) ?>?s=40&d=identicon'">
                                                    </div>
                                                    <div>
                                                        <div class="fw-semibold"><?= htmlspecialchars($fullName) ?></div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <?php
                                                $badgeClass = '';
                                                $rolText = '';
                                                switch($emp['rol']) {
                                                    case 'admin':
                                                        $badgeClass = 'bg-danger';
                                                        $rolText = 'Administrador';
                                                        break;
                                                    case 'supervisor':
                                                        $badgeClass = 'bg-warning';
                                                        $rolText = 'Supervisor';
                                                        break;
                                                    case 'empleado':
                                                        $badgeClass = 'bg-info';
                                                        $rolText = 'Empleado';
                                                        break;
                                                    default:
                                                        $badgeClass = 'bg-secondary';
                                                        $rolText = ucfirst($emp['rol']);
                                                }
                                                ?>
                                                <span class="badge <?= $badgeClass ?>"><?= $rolText ?></span>
                                            </td>
                                            <td>
                                                <span class="fw-medium"><?= htmlspecialchars($emp['email']) ?></span>
                                            </td>
                                            <td>
                                                <?php
                                                // Mostrar información de fichaje de hoy
                                                $estadoFichaje = $emp['estado_fichaje'];
                                                $entradaHoy = $emp['entrada_hoy'];
                                                $salidaHoy = $emp['salida_hoy'];
                                                
                                                if ($estadoFichaje === 'Trabajando'): ?>
                                                    <div>
                                                        <span class="badge bg-success mb-1">Trabajando</span>
                                                        <small class="d-block text-date">
                                                            Entrada: <?= date('H:i', strtotime($entradaHoy)) ?>
                                                        </small>
                                                    </div>
                                                <?php elseif ($estadoFichaje === 'Completado'): ?>
                                                    <div>
                                                        <span class="badge bg-info mb-1">Completado</span>
                                                        <small class="d-block text-date">
                                                            <?= date('H:i', strtotime($entradaHoy)) ?> - <?= date('H:i', strtotime($salidaHoy)) ?>
                                                        </small>
                                                    </div>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary">Sin fichar</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <div class="d-flex gap-1">
                                                    <a href="<?= $config['ruta_absoluta'] ?>admin/ver-empleado?id=<?= $emp['id'] ?>"
                                                       class="btn btn-sm btn-info" title="Ver detalles">
                                                        <i class="ti ti-eye"></i>
                                                    </a>
                                                    <?php 
                                                    // No mostrar editar si es super admin (excepto para sí mismo)
                                                    $puedeEditar = (isAdmin() || (isSupervisor() && $emp['rol'] !== 'admin')) 
                                                                   && (empty($emp['es_super_admin']) || $emp['id'] === $_SESSION['empleado_id']);
                                                    if ($puedeEditar): 
                                                    ?>
                                                    <a href="<?= $config['ruta_absoluta'] ?>admin/editar-empleado?id=<?= $emp['id'] ?>"
                                                       class="btn btn-sm btn-warning" title="Editar">
                                                        <i class="ti ti-edit"></i>
                                                    </a>
                                                    <?php endif; ?>
                                                    <?php 
                                                    // No mostrar eliminar si es super admin
                                                    if (isAdmin() && empty($emp['es_super_admin'])): 
                                                    ?>
                                                    <a href="<?= $config['ruta_absoluta'] ?>admin/borrar-empleado.php?id=<?= $emp['id'] ?>"
                                                       class="btn btn-sm btn-danger" title="Eliminar"
                                                       onclick='return confirm(<?= json_encode("¿Seguro que deseas eliminar a $fullName?") ?>);'>
                                                        <i class="ti ti-trash"></i>
                                                    </a>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <!-- Vista de tarjetas para pantallas medianas y pequeñas -->
                        <div class="d-lg-none">
                            <?php foreach($empleados as $emp): ?>
                                <?php
                                $fullName = $emp['nombre'] . ' ' . $emp['apellidos'];
                                $avatarURL = obtenerAvatarEmpleado($emp, $config);
                                $estadoFichaje = $emp['estado_fichaje'];
                                $entradaHoy = $emp['entrada_hoy'];
                                $salidaHoy = $emp['salida_hoy'];
                                
                                // Definir variables para el badge de rol
                                $badgeClass = '';
                                $rolText = '';
                                switch($emp['rol']) {
                                    case 'admin':
                                        $badgeClass = 'bg-danger';
                                        $rolText = 'Admin';
                                        break;
                                    case 'supervisor':
                                        $badgeClass = 'bg-warning text-dark';
                                        $rolText = 'Supervisor';
                                        break;
                                    case 'empleado':
                                        $badgeClass = 'bg-info';
                                        $rolText = 'Empleado';
                                        break;
                                    default:
                                        $badgeClass = 'bg-secondary';
                                        $rolText = ucfirst($emp['rol']);
                                }
                                ?>
                                <div class="card mb-3 border-0 shadow-sm">
                                    <div class="card-body">
                                        <div class="row">
                                            <!-- Header con empleado y acciones -->
                                            <div class="col-12 mb-3">
                                                <div class="d-flex justify-content-between align-items-start">
                                                    <div class="d-flex align-items-center">
                                                        <div class="avatar-sm bg-light rounded-circle d-flex align-items-center justify-content-center me-3 overflow-hidden" style="width: 40px; height: 40px;">
                                                            <img src="<?= htmlspecialchars($avatarURL) ?>" 
                                                                 alt="<?= htmlspecialchars($fullName) ?>" 
                                                                 class="rounded-circle" 
                                                                 style="width: 40px; height: 40px; object-fit: cover;"
                                                                 loading="lazy"
                                                                 onerror="this.src='https://www.gravatar.com/avatar/<?= md5(strtolower(trim($emp['email']))) ?>?s=40&d=identicon'">
                                                        </div>
                                                        <div>
                                                            <div class="fw-semibold"><?= htmlspecialchars($fullName) ?></div>
                                                            <span class="badge <?= $badgeClass ?>"><?= $rolText ?></span>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Estado de fichaje -->
                                            <div class="col-12 mb-3">
                                                <div class="border-start border-4 ps-3" style="border-color: 
                                                    <?= $estadoFichaje === 'Trabajando' ? '#198754' : 
                                                        ($estadoFichaje === 'Completado' ? '#0dcaf0' : '#6c757d') ?> !important;">
                                                    <div class="d-flex align-items-center justify-content-between">
                                                        <div>
                                                            <small class="fw-bold d-block">Estado de fichaje hoy</small>
                                                            <?php if ($estadoFichaje === 'Trabajando'): ?>
                                                                <span class="badge bg-success">
                                                                    <i class="ti ti-clock me-1"></i>Trabajando
                                                                </span>
                                                                <div class="mt-1">
                                                                    <small class="text-dark">
                                                                        Entrada: <strong><?= date('H:i', strtotime($entradaHoy)) ?></strong>
                                                                    </small>
                                                                </div>
                                                            <?php elseif ($estadoFichaje === 'Completado'): ?>
                                                                <span class="badge bg-info">
                                                                    <i class="ti ti-check-circle me-1"></i>Completado
                                                                </span>
                                                                <div class="mt-1">
                                                                    <small class="text-dark">
                                                                        <strong><?= date('H:i', strtotime($entradaHoy)) ?></strong> 
                                                                        - 
                                                                        <strong><?= date('H:i', strtotime($salidaHoy)) ?></strong>
                                                                    </small>
                                                                </div>
                                                            <?php else: ?>
                                                                <span class="badge bg-secondary">
                                                                    <i class="ti ti-clock-off me-1"></i>Sin fichar
                                                                </span>
                                                                
                                                            <?php endif; ?>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Acciones -->
                                            <div class="col-12">
                                                <div class="d-flex gap-1">
                                                    <a href="<?= $config['ruta_absoluta'] ?>admin/ver-empleado?id=<?= $emp['id'] ?>"
                                                       class="btn btn-info btn-sm" title="Ver detalles">
                                                        <i class="ti ti-eye"></i>
                                                    </a>
                                                    <?php 
                                                    // No mostrar editar si es super admin (excepto para sí mismo)
                                                    $puedeEditar = (isAdmin() || (isSupervisor() && $emp['rol'] !== 'admin')) 
                                                                   && (empty($emp['es_super_admin']) || $emp['id'] === $_SESSION['empleado_id']);
                                                    if ($puedeEditar): 
                                                    ?>
                                                    <a href="<?= $config['ruta_absoluta'] ?>admin/editar-empleado?id=<?= $emp['id'] ?>"
                                                       class="btn btn-warning btn-sm" title="Editar">
                                                        <i class="ti ti-edit"></i>
                                                    </a>
                                                    <?php endif; ?>
                                                    <?php 
                                                    // No mostrar eliminar si es super admin
                                                    if (isAdmin() && empty($emp['es_super_admin'])): 
                                                    ?>
                                                    <a href="<?= $config['ruta_absoluta'] ?>admin/borrar-empleado.php?id=<?= $emp['id'] ?>"
                                                       class="btn btn-danger btn-sm" title="Eliminar"
                                                       onclick='return confirm(<?= json_encode("¿Seguro que deseas eliminar a $fullName?") ?>);'>
                                                        <i class="ti ti-trash"></i>
                                                    </a>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php if (isset($_GET['delete']) && $_GET['delete'] === 'ok'): ?>
<div class="position-fixed top-0 end-0 p-3" style="z-index: 1100;">
    <div class="toast show" role="alert">
        <div class="toast-header bg-success text-white">
            <i class="ti ti-check-circle me-2"></i>
            <strong class="me-auto">Éxito</strong>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast"></button>
        </div>
        <div class="toast-body">
            Empleado eliminado correctamente.
        </div>
    </div>
</div>
<?php endif; ?>

<?php if (isset($_GET['error']) && $_GET['error'] === 'sin_permisos'): ?>
<div class="position-fixed top-0 end-0 p-3" style="z-index: 1100;">
    <div class="toast show" role="alert">
        <div class="toast-header bg-danger text-white">
            <i class="ti ti-alert-circle me-2"></i>
            <strong class="me-auto">Error</strong>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast"></button>
        </div>
        <div class="toast-body">
            No tienes permisos para editar o eliminar administradores.
        </div>
    </div>
</div>
<?php endif; ?>


<script>
    const BASE_URL = "<?= $config['ruta_absoluta'] ?>";
    document.addEventListener('DOMContentLoaded', function() {
        initAdminFilters('empleados');
    });
</script>

