<?php

require_once __DIR__ . '/../../includes/init.php';

// Verificar que el usuario esté autenticado y sea administrador o supervisor
if (!isAdminOrSupervisor()) {
    header('Location: ' . $config['ruta_absoluta'] . 'dashboard');
    exit;
}

// Endpoint AJAX para obtener estadísticas
if (isset($_GET['action']) && $_GET['action'] === 'stats') {
    header('Content-Type: application/json');
    
    $filtros = [
        'estado' => $_GET['estado'] ?? '',
        'tipo' => $_GET['tipo'] ?? '',
        'empleado' => $_GET['empleado'] ?? '',
        'fecha_desde' => $_GET['fecha_desde'] ?? '',
        'fecha_hasta' => $_GET['fecha_hasta'] ?? ''
    ];
    
    try {
        $stats = obtenerEstadisticasSolicitudes($pdo, $filtros);
        echo json_encode(['success' => true, 'stats' => $stats]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => 'Error al obtener estadísticas']);
    }
    exit;
}

// Endpoint AJAX para obtener empleados
if (isset($_GET['action']) && $_GET['action'] === 'empleados') {
    header('Content-Type: application/json');
    
    try {
        $stmt = $pdo->prepare('SELECT id, nombre, apellidos, email FROM empleados WHERE activo = 1 ORDER BY nombre, apellidos');
        $stmt->execute();
        $empleados = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode(['success' => true, 'empleados' => $empleados]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => 'Error al obtener empleados']);
    }
    exit;
}

// Parámetros de filtrado
$filtroEstado = $_GET['estado'] ?? '';
$filtroTipo = $_GET['tipo'] ?? '';
$filtroEmpleado = $_GET['empleado'] ?? '';
$fechaDesde = $_GET['fecha_desde'] ?? '';
$fechaHasta = $_GET['fecha_hasta'] ?? '';

// Compilar filtros
$filtros = [
    'estado' => $filtroEstado,
    'tipo' => $filtroTipo,
    'empleado' => $filtroEmpleado,
    'fecha_desde' => $fechaDesde,
    'fecha_hasta' => $fechaHasta
];

// Obtener solicitudes y estadísticas
try {
    $solicitudes = obtenerSolicitudesConFiltros($pdo, $filtros);
    $stats = obtenerEstadisticasSolicitudes($pdo, $filtros);
} catch (Exception $e) {
    $solicitudes = [];
    $stats = ['total' => 0, 'pendientes' => 0, 'aprobadas' => 0, 'rechazadas' => 0];
}


?>
<div class="mb-3 overflow-hidden position-relative">
  <div class="px-3">
    <h4 class="fs-6 mb-0 mt-2">Ver Solicitudes</h4>
    <nav aria-label="breadcrumb">
      <ol class="breadcrumb mb-0">
        <li class="breadcrumb-item">
            <a href="<?= $config['ruta_absoluta'] ?>dashboard">Inicio</a>
        </li>
        <li class="breadcrumb-item">
            <a href="<?= $config['ruta_absoluta'] ?>administracion">Administración</a>
        </li>
        <li class="breadcrumb-item active" aria-current="page">Solicitudes</li>
      </ol>
    </nav>
  </div>
</div>
<div class="container-fluid py-2 py-md-4 admin-solicitudes">
    <div class="row">
        <div class="col-12">
            <!-- Header de la página -->
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-3 mb-md-4 gap-2">
                <h2 class="mb-0 h4 h3-md">
                    <span class="d-none d-sm-inline">Gestión de </span>Solicitudes
                </h2>
                <div class="d-flex align-items-center gap-2 flex-wrap">
                    <div class="badge-group d-flex flex-wrap gap-1">
                        <span class="badge bg-secondary" id="stat-total">Total: <?= $stats['total'] ?></span>
                        <span class="badge bg-warning" id="stat-pendientes">Pendientes: <?= $stats['pendientes'] ?></span>
                        <span class="badge bg-success" id="stat-aprobadas">Aprobadas: <?= $stats['aprobadas'] ?></span>
                        <span class="badge bg-danger" id="stat-rechazadas">Rechazadas: <?= $stats['rechazadas'] ?></span>
                    </div>
                    <!-- Botón de emergencia para cerrar modales -->
                    <button type="button" class="btn btn-outline-danger btn-sm" id="cerrarTodosModales" title="Cerrar todos los modales">
                        <i class="ti ti-x"></i>
                    </button>
                </div>
            </div>

            <!-- Filtros -->
            <div class="card mb-3 mb-md-4">
                <div class="card-body p-3">
                    <h5 class="card-title mb-2 mb-md-3 h6">
                        <i class="ti ti-filter me-2"></i>Filtros
                    </h5>
                    <form method="GET" class="row g-2 g-md-3">
                        <!-- Campo oculto para mantener el parámetro page -->
                        <input type="hidden" name="page" value="<?= htmlspecialchars($_GET['page'] ?? '') ?>">
                        
                        <div class="col-6 col-sm-4 col-md-3 col-lg-2">
                            <label class="form-label small">Estado</label>
                            <select name="estado" class="form-select form-select-sm">
                                <option value="">Todos</option>
                                <option value="pendiente" <?= $filtroEstado === 'pendiente' ? 'selected' : '' ?>>Pendiente</option>
                                <option value="aprobado" <?= $filtroEstado === 'aprobado' ? 'selected' : '' ?>>Aprobado</option>
                                <option value="rechazado" <?= $filtroEstado === 'rechazado' ? 'selected' : '' ?>>Rechazado</option>
                            </select>
                        </div>
                        <div class="col-6 col-sm-4 col-md-3 col-lg-2">
                            <label class="form-label small">Tipo</label>
                            <select name="tipo" class="form-select form-select-sm">
                                <option value="">Todos</option>
                                <option value="vacaciones" <?= $filtroTipo === 'vacaciones' ? 'selected' : '' ?>>Vacaciones</option>
                                <option value="permiso" <?= $filtroTipo === 'permiso' ? 'selected' : '' ?>>Permiso</option>
                                <option value="baja" <?= $filtroTipo === 'baja' ? 'selected' : '' ?>>Baja médica</option>
                                <option value="extra" <?= $filtroTipo === 'extra' ? 'selected' : '' ?>>Horas extra</option>
                                <option value="ausencia" <?= $filtroTipo === 'ausencia' ? 'selected' : '' ?>>Ausencia</option>
                            </select>
                        </div>
                        <div class="col-12 col-sm-4 col-md-3 col-lg-2">
                            <label class="form-label small">Empleado</label>
                            <input type="text" name="empleado" class="form-control form-control-sm" 
                                   placeholder="Buscar por nombre" value="<?= htmlspecialchars($filtroEmpleado) ?>">
                        </div>
                        <div class="col-6 col-md-3 col-lg-2">
                            <label class="form-label small">Desde</label>
                            <input type="date" name="fecha_desde" class="form-control form-control-sm" value="<?= $fechaDesde ?>">
                        </div>
                        <div class="col-6 col-md-3 col-lg-2">
                            <label class="form-label small">Hasta</label>
                            <input type="date" name="fecha_hasta" class="form-control form-control-sm" value="<?= $fechaHasta ?>">
                        </div>
                        <div class="col-12 col-lg-2 d-flex align-items-end">
                            <div class="w-100">
                                <!-- Botones responsive -->
                                <div class="d-flex gap-1 flex-column flex-sm-row justify-content-center">
                                    <button type="submit" class="btn btn-sm btn-primary">
                                        <i class="ti ti-search"></i>
                                        <span class="d-none d-lg-inline ms-1">Filtrar</span>
                                    </button>
                                    <button type="button" class="btn btn-outline-secondary btn-sm" id="btnLimpiarFiltros">
                                        <i class="ti ti-refresh"></i>
                                        <span class="d-none d-lg-inline ms-1">Limpiar</span>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Tabla de solicitudes -->
            <div class="card">
                <div class="card-body p-2 p-md-3">
                    <!-- Botón para crear solicitud -->
                    <div class="mb-3 mb-md-4">
                        <button type="button" class="btn btn-success" id="btnCrearSolicitud" title="Crear solicitud">
                            <i class="ti ti-plus me-2"></i>Crear Solicitud
                        </button>
                    </div>
                    
                    <?php if (empty($solicitudes)): ?>
                        <div class="text-center py-4">
                            <i class="ti ti-inbox display-4 text-muted"></i>
                            <h4 class="text-muted mt-3">No hay solicitudes</h4>
                            <p class="text-muted">No se encontraron solicitudes con los filtros aplicados.</p>
                        </div>
                    <?php else: ?>
                        <!-- Vista de tabla para pantallas grandes -->
                        <div class="table-responsive d-none d-lg-block">
                            <table class="table table-striped table-hover mb-0" id="tabla-solicitudes">
                                <thead class="bg-info text-white">
                                    <tr>
                                        <th>Empleado</th>
                                        <th>Tipo</th>
                                        <th>Período</th>
                                        <th class="d-none d-xl-table-cell">Horas</th>
                                        <th>Estado</th>
                                        <th class="d-none d-xl-table-cell">Solicitado</th>
                                        <th class="d-none d-xl-table-cell">Supervisor</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($solicitudes as $sol): ?>
                                        <tr data-solicitud-id="<?= $sol['id'] ?>">
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <div class="avatar-sm bg-light rounded-circle d-flex align-items-center justify-content-center me-2 overflow-hidden" style="width: 32px; height: 32px;">
                                                        <img src="<?= obtenerAvatarEmpleado($sol, $config) ?>" 
                                                             alt="<?= htmlspecialchars($sol['nombre'].' '.$sol['apellidos']) ?>" 
                                                             class="rounded-circle" 
                                                             style="width: 32px; height: 32px; object-fit: cover;"
                                                             loading="lazy"
                                                             onerror="this.src='https://www.gravatar.com/avatar/<?= md5(strtolower(trim($sol['email']))) ?>?s=32&d=identicon'">
                                                    </div>
                                                    <div style="min-width: 0;">
                                                        <div class="fw-semibold text-truncate" style="max-width: 140px;"><?= htmlspecialchars($sol['nombre'].' '.$sol['apellidos']) ?></div>
                                                        <div class="d-block d-xl-none">
                                                            <?= renderEstadoBadge($sol['estado']) ?>
                                                        </div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <?php
                                                    $tiposColores = [
                                                        'vacaciones' => 'primary',
                                                        'baja' => 'success',
                                                        'permiso' => 'warning',
                                                        'extra' => 'warning',
                                                        'ausencia' => 'danger'
                                                    ];
                                                    $colorBadge = $tiposColores[$sol['tipo']] ?? 'info';
                                                ?>
                                                <span class="badge bg-<?= $colorBadge ?> text-nowrap"><?= formatearTipo($sol['tipo']) ?></span>
                                            </td>
                                            <td>
                                                <?php if ($sol['fecha_inicio']): ?>
                                                    <div class="text-nowrap"><?= formatearFecha($sol['fecha_inicio']) ?></div>
                                                    <?php if ($sol['fecha_fin'] && $sol['fecha_fin'] !== $sol['fecha_inicio']): ?>
                                                        <small class="text-date d-block">hasta <?= formatearFecha($sol['fecha_fin']) ?></small>
                                                    <?php endif; ?>
                                                    <?php if ($sol['medio_dia']): ?>
                                                        <small class="badge bg-warning text-dark">1/2 día</small>
                                                    <?php endif; ?>
                                                <?php else: ?>
                                                    <span class="text-date">-</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="d-none d-xl-table-cell">
                                                <?php if ($sol['horas'] && $sol['horas'] > 0): ?>
                                                    <div><?= number_format($sol['horas'], 1) ?>h</div>
                                                    <?php if ($sol['hora_inicio'] && $sol['hora_fin']): ?>
                                                        <small class="text-date"><?= substr($sol['hora_inicio'], 0, 5) ?>-<?= substr($sol['hora_fin'], 0, 5) ?></small>
                                                    <?php endif; ?>
                                                <?php else: ?>
                                                    <span class="text-date">-</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?= renderEstadoBadge($sol['estado']) ?>
                                            </td>
                                            <td class="d-none d-xl-table-cell">
                                                <div class="text-nowrap"><?= formatearFechaHora($sol['fecha_solicitud']) ?></div>
                                                <?php if ($sol['fecha_respuesta']): ?>
                                                    <small class="text-date d-block">Resp: <?= formatearFechaHora($sol['fecha_respuesta']) ?></small>
                                                <?php endif; ?>
                                            </td>
                                            <td class="d-none d-xl-table-cell">
                                                <span class="text-truncate" style="max-width: 100px;" title="<?= htmlspecialchars($sol['aprobador_nombre'] ?? '') ?>">
                                                    <?= $sol['aprobador_nombre'] ? htmlspecialchars($sol['aprobador_nombre']) : '-' ?>
                                                </span>
                                            </td>
                                            <td>
                                                <div class="d-flex gap-1">
                                                    <?php if($sol['estado']=='pendiente'): ?>
                                                        <button class="btn btn-success btn-sm aprobar-btn" 
                                                                data-id="<?= $sol['id'] ?>" 
                                                                title="Aprobar">
                                                            <i class="ti ti-check"></i>
                                                        </button>
                                                        <button class="btn btn-warning btn-sm rechazar-btn" 
                                                                data-id="<?= $sol['id'] ?>"
                                                                title="Rechazar">
                                                            <i class="ti ti-x"></i>
                                                        </button>
                                                    <?php endif; ?>
                                                    <button class="btn btn-info btn-sm ver-btn" 
                                                            data-id="<?= $sol['id'] ?>"
                                                            title="Ver detalles">
                                                        <i class="ti ti-eye"></i>
                                                    </button>
                                                    <?php if ($sol['estado'] === 'aprobado'): ?>
                                                    <button class="btn btn-warning btn-sm editar-btn" 
                                                            data-id="<?= $sol['id'] ?>"
                                                            title="Editar solicitud">
                                                        <i class="ti ti-edit"></i>
                                                    </button>
                                                    <?php endif; ?>
                                                    <?php if (isAdmin()): ?>
                                                    <button class="btn btn-danger btn-sm eliminar-btn" 
                                                            data-id="<?= $sol['id'] ?>"
                                                            title="Eliminar">
                                                        <i class="ti ti-trash"></i>
                                                    </button>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <!-- Vista de tarjetas para pantallas pequeñas y medianas -->
                        <div class="d-lg-none">
                            <?php foreach($solicitudes as $sol): ?>
                                <div class="card mb-2 mb-md-3 border-0 shadow-sm" data-solicitud-card="<?= $sol['id'] ?>">
                                    <div class="card-body p-2 p-md-3">
                                        <div class="row g-2">
                                            <!-- Header de la tarjeta con empleado y estado -->
                                            <div class="col-12 mb-2">
                                                <div class="d-flex justify-content-between align-items-start">
                                                    <div class="d-flex align-items-center flex-grow-1 me-2">
                                                        <div class="avatar-sm bg-light rounded-circle d-flex align-items-center justify-content-center me-2 shrink-0">
                                                            <img src="<?= obtenerAvatarEmpleado($sol, $config) ?>" 
                                                                 alt="<?= htmlspecialchars($sol['nombre'].' '.$sol['apellidos']) ?>" 
                                                                 class="rounded-circle w-100 h-100" 
                                                                 style="object-fit: cover;"
                                                                 loading="lazy"
                                                                 onerror="this.src='https://www.gravatar.com/avatar/<?= md5(strtolower(trim($sol['email']))) ?>?s=40&d=identicon'">
                                                        </div>
                                                        <div class="grow min-width-0">
                                                            <div class="fw-semibold text-truncate small"><?= htmlspecialchars($sol['nombre'].' '.$sol['apellidos']) ?></div>
                                                            <?php
                                                                $tiposColores = [
                                                                    'vacaciones' => 'primary',
                                                                    'baja' => 'success',
                                                                    'permiso' => 'warning',
                                                                    'extra' => 'warning',
                                                                    'ausencia' => 'danger'
                                                                ];
                                                                $colorBadge = $tiposColores[$sol['tipo']] ?? 'info';
                                                            ?>
                                                            <span class="badge bg-<?= $colorBadge ?> text-nowrap small"><?= formatearTipo($sol['tipo']) ?></span>
                                                        </div>
                                                    </div>
                                                    <?php echo str_replace('text-nowrap', 'shrink-0', renderEstadoBadge($sol['estado'])); ?>
                                                </div>
                                            </div>

                                            <!-- Información principal -->
                                            <div class="col-12 col-sm-6 mb-2">
                                                <?php if ($sol['fecha_inicio']): ?>
                                                    <div class="mb-2">
                                                        <small class="text-muted d-block">Período</small>
                                                        <div class="small"><?= formatearFecha($sol['fecha_inicio']) ?></div>
                                                        <?php if ($sol['fecha_fin'] && $sol['fecha_fin'] !== $sol['fecha_inicio']): ?>
                                                            <small class="text-muted">hasta <?= formatearFecha($sol['fecha_fin']) ?></small>
                                                        <?php endif; ?>
                                                        <?php if ($sol['medio_dia']): ?>
                                                            <span class="badge bg-warning text-dark ms-1">1/2 día</span>
                                                        <?php endif; ?>
                                                    </div>
                                                <?php endif; ?>

                                                <?php if ($sol['horas'] && $sol['horas'] > 0): ?>
                                                    <div class="mb-2">
                                                        <small class="text-muted d-block">Horas</small>
                                                        <div class="small"><?= number_format($sol['horas'], 1) ?>h</div>
                                                        <?php if ($sol['hora_inicio'] && $sol['hora_fin']): ?>
                                                            <small class="text-muted"><?= substr($sol['hora_inicio'], 0, 5) ?> - <?= substr($sol['hora_fin'], 0, 5) ?></small>
                                                        <?php endif; ?>
                                                    </div>
                                                <?php endif; ?>
                                            </div>

                                            <!-- Información adicional -->
                                            <div class="col-12 col-sm-6 mb-2">
                                                <div class="mb-2">
                                                    <small class="text-muted d-block">Solicitado</small>
                                                    <div class="small"><?= formatearFechaHora($sol['fecha_solicitud']) ?></div>
                                                    <?php if ($sol['fecha_respuesta']): ?>
                                                        <small class="text-muted">Resp: <?= formatearFechaHora($sol['fecha_respuesta']) ?></small>
                                                    <?php endif; ?>
                                                </div>

                                                <?php if ($sol['aprobador_nombre']): ?>
                                                    <div class="mb-2">
                                                        <small class="text-muted d-block">Supervisor</small>
                                                        <span class="small"><?= htmlspecialchars($sol['aprobador_nombre']) ?></span>
                                                    </div>
                                                <?php endif; ?>
                                            </div>

                                            <!-- Acciones -->
                                            <div class="col-12">
                                                <div class="d-flex gap-2 flex-wrap justify-content-start">
                                                    <?php if($sol['estado']=='pendiente'): ?>
                                                        <button class="btn btn-success btn-sm aprobar-btn" 
                                                                data-id="<?= $sol['id'] ?>" 
                                                                title="Aprobar">
                                                            <i class="ti ti-check"></i>
                                                        </button>
                                                        <button class="btn btn-danger btn-sm rechazar-btn" 
                                                                data-id="<?= $sol['id'] ?>"
                                                                title="Rechazar">
                                                            <i class="ti ti-x"></i>
                                                        </button>
                                                    <?php endif; ?>
                                                    <button class="btn btn-info btn-sm ver-btn" 
                                                            data-id="<?= $sol['id'] ?>"
                                                            title="Ver detalles">
                                                        <i class="ti ti-eye"></i>
                                                    </button>
                                                    <?php if ($sol['estado'] === 'aprobado'): ?>
                                                    <button class="btn btn-warning btn-sm editar-btn" 
                                                            data-id="<?= $sol['id'] ?>"
                                                            title="Editar solicitud">
                                                        <i class="ti ti-edit"></i>
                                                    </button>
                                                    <?php endif; ?>
                                                    <?php if (isAdmin()): ?>
                                                    <button class="btn btn-outline-danger btn-sm eliminar-btn" 
                                                            data-id="<?= $sol['id'] ?>"
                                                            title="Eliminar">
                                                        <i class="ti ti-trash"></i>
                                                    </button>
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

<!-- Modal para ver detalles -->
<div class="modal fade" id="modalDetalle" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="ti ti-eye me-2"></i>Detalles de la Solicitud
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="modalDetalleBody">
                <!-- Contenido cargado via AJAX -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="ti ti-x me-1"></i>Cerrar
                </button>
                <a type="button" class="btn btn-success" id="btnDescargarArchivo" style="display: none;" href="#" download>
                    <i class="ti ti-download me-1"></i>Descargar Archivo
                </a>
                <button type="button" class="btn btn-warning" id="btnEditarSolicitud" style="display: none;">
                    <i class="ti ti-edit me-1"></i>Editar
                </button>
                <button type="button" class="btn btn-primary" id="btnAprobarSolicitud" style="display: none;">
                    <i class="ti ti-check me-1"></i>Aprobar
                </button>
                <button type="button" class="btn btn-danger" id="btnRechazarSolicitud" style="display: none;">
                    <i class="ti ti-x me-1"></i>Rechazar
                </button>
                <button type="button" class="btn btn-danger" id="btnEliminarSolicitud" style="display: none;">
                    <i class="ti ti-trash me-1"></i>Eliminar
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal para aprobar/rechazar -->
<div class="modal fade" id="modalAprobacion" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalAprobacionTitle">Aprobar Solicitud</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="formAprobacion" onsubmit="return false;">
                    <input type="hidden" id="solicitudId" name="solicitud_id">
                    <input type="hidden" id="accionType" name="accion">
                    
                    <div class="mb-3">
                        <label for="comentarioAdmin" class="form-label">Comentario</label>
                        <textarea class="form-control" id="comentarioAdmin" name="comentario" rows="3" 
                                  placeholder="Agregar un comentario (opcional)"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn" id="btnConfirmarAccion">Confirmar</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal para confirmar eliminación -->
<div class="modal fade" id="modalEliminar" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title text-danger">
                    <i class="ti ti-alert-triangle me-2"></i>Confirmar Eliminación
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-warning" role="alert">
                    <i class="ti ti-alert-triangle me-2"></i>
                    <strong>¡Atención!</strong> Esta acción no se puede deshacer.
                </div>
                <p>¿Estás seguro de que deseas eliminar esta solicitud permanentemente?</p>
                <p class="text-muted small">Se eliminará toda la información relacionada con la solicitud.</p>
                <input type="hidden" id="solicitudEliminarId">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-danger" id="btnConfirmarEliminar">
                    <i class="ti ti-trash me-1"></i>Eliminar
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal para editar vacaciones/bajas médicas -->
<div class="modal fade" id="modalEditarSolicitud" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="ti ti-edit me-2"></i>Editar Solicitud
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="formEditarSolicitud" onsubmit="return false;">
                    <input type="hidden" id="editarSolicitudId" name="id">
                    
                    <div class="mb-3">
                        <label for="editarTipo" class="form-label">Tipo</label>
                        <input type="text" class="form-control" id="editarTipo" disabled>
                        <small class="text-muted">No se puede cambiar el tipo</small>
                    </div>
                    
                    <div class="mb-3">
                        <label for="editarEmpleado" class="form-label">Empleado</label>
                        <input type="text" class="form-control" id="editarEmpleado" disabled>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="editarFechaInicio" class="form-label">Fecha Inicio</label>
                            <input type="date" class="form-control" id="editarFechaInicio" name="fecha_inicio" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="editarFechaFin" class="form-label">Fecha Fin</label>
                            <input type="date" class="form-control" id="editarFechaFin" name="fecha_fin" required>
                        </div>
                    </div>
                    
                    <div class="mb-3" id="grupoMedioDia">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="editarMedioDia" name="medio_dia">
                            <label class="form-check-label" for="editarMedioDia">
                                Medio día
                            </label>
                        </div>
                    </div>
                    
                    <div class="mb-3" id="grupoHoras" style="display:none;">
                        <label for="editarHoras" class="form-label">Horas</label>
                        <input type="number" class="form-control" id="editarHoras" name="horas" step="0.5" min="0">
                    </div>
                    
                    <div class="mb-3">
                        <label for="editarComentario" class="form-label">Comentario Administrativo</label>
                        <textarea class="form-control" id="editarComentario" name="comentario_admin" rows="3" 
                                  placeholder="Agregar notas sobre los cambios realizados"></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label for="editarArchivo" class="form-label">Adjuntar documento (opcional)</label>
                        <input type="file" class="form-control" id="editarArchivo" name="archivo" 
                               accept=".pdf,.doc,.docx,.jpg,.jpeg,.png,.gif">
                        <small class="text-muted">Máximo 5MB. Formatos: PDF, DOC, DOCX, JPG, PNG, GIF</small>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" id="btnGuardarEdicion">
                    <i class="ti ti-save me-1"></i>Guardar Cambios
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal para crear solicitud -->
<div class="modal fade" id="modalCrearBajaMedica" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="ti ti-plus me-2"></i>Crear Solicitud
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="formCrearBajaMedica" onsubmit="return false;">
                    <div class="mb-3">
                        <label for="crearTipoSolicitud" class="form-label">Tipo de Solicitud <span class="text-danger">*</span></label>
                        <select class="form-select" id="crearTipoSolicitud" name="tipo" required>
                            <option value="">-- Seleccionar tipo --</option>
                            <option value="baja">Baja Médica</option>
                            <option value="vacaciones">Vacaciones</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="crearEmpleadoId" class="form-label">Empleado <span class="text-danger">*</span></label>
                        <select class="form-select" id="crearEmpleadoId" name="empleado_id" required>
                            <option value="">-- Seleccionar empleado --</option>
                        </select>
                        <small id="crearEmpleadoError" class="text-danger" style="display:none;">Por favor selecciona un empleado</small>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="crearFechaInicio" class="form-label">Fecha Inicio <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" id="crearFechaInicio" name="fecha_inicio" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="crearFechaFin" class="form-label">Fecha Fin <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" id="crearFechaFin" name="fecha_fin" required>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="crearMedioDia" name="medio_dia">
                            <label class="form-check-label" for="crearMedioDia">
                                Medio día
                            </label>
                        </div>
                    </div>
                    
                    <div class="mb-3" id="crearGrupoHoras" style="display:none;">
                        <label for="crearHoras" class="form-label">Horas</label>
                        <input type="number" class="form-control" id="crearHoras" name="horas" step="0.5" min="0">
                    </div>
                    
                    <div class="mb-3">
                        <label for="crearComentario" class="form-label">Comentario Administrativo</label>
                        <textarea class="form-control" id="crearComentario" name="comentario_admin" rows="3" 
                                  placeholder="Motivo o notas adicionales"></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label for="crearArchivo" class="form-label">Adjuntar documento (opcional)</label>
                        <input type="file" class="form-control" id="crearArchivo" name="archivo" 
                               accept=".pdf,.doc,.docx,.jpg,.jpeg,.png,.gif">
                        <small class="text-muted">Máximo 5MB. Formatos: PDF, DOC, DOCX, JPG, PNG, GIF</small>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-success" id="btnGuardarBajaMedica">
                    <i class="ti ti-check me-1"></i>Crear Solicitud
                </button>
            </div>
        </div>
    </div>
</div>

<script>
const BASE_URL = "<?= $config['ruta_absoluta'] ?>";

// Script simplificado - toda la lógica está en request.js
document.addEventListener('DOMContentLoaded', function() {

    
   
    setTimeout(function() {
        if (window.initVerSolicitudes && typeof window.initVerSolicitudes === 'function') {

            window.initVerSolicitudes();
        } else {
            console.error('initVerSolicitudes no disponible en request.js');
        }
    }, 100);
});
</script>







