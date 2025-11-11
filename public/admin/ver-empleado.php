<?php
session_start();
require_once __DIR__ . '/../../includes/init.php';

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$empleado = $pdo->query("SELECT * FROM empleados WHERE id = $id")->fetch(PDO::FETCH_ASSOC);
if (!$empleado) {
    echo "<div class='alert alert-danger'>Empleado no encontrado.</div>";
    exit;
}

// Obtener avatar URL
$avatarURL = obtenerAvatarURL($empleado, $config);

// Obtener horarios del empleado
$horariosData = getHorariosEmpleado($pdo, $empleado['id']);

// Obtener fichajes procesados para la tabla
$fechaDesde = $_GET['desde'] ?? date('Y-m-01'); // Primer día del mes actual por defecto
$fechaHasta = $_GET['hasta'] ?? date('Y-m-t'); // Último día del mes actual por defecto
$tablaDias = getFichajesTabla($pdo, $empleado['id'], $fechaDesde, $fechaHasta);

// Obtener estadísticas del año actual
$resumenSolicitudes = getResumenSolicitudes($pdo, $empleado['id']);
$horasExtra = getHorasExtra($pdo, $empleado['id']);

$fullName = $empleado['nombre'].' '.$empleado['apellidos'];

?>
<div class="mb-3 overflow-hidden position-relative">
  <div class="px-3">
    <h4 class="fs-6 mb-0 mt-2">Datos del empleado</h4>
    <nav aria-label="breadcrumb">
      <ol class="breadcrumb mb-0">
        <li class="breadcrumb-item">
          <a href="<?= $config['ruta_absoluta'] ?>dashboard">Inicio</a>
        </li>
        <li class="breadcrumb-item">
          <a href="<?= $config['ruta_absoluta'] ?>admin/empleados">Empleados</a>
        </li>
        <li class="breadcrumb-item active" aria-current="page">Datos del empleado</li>
      </ol>
    </nav>
  </div>
</div>

<div class="container-fluid py-3 py-md-5 ver-empleado-page">
  <div class="row g-3 g-md-4">
    <!-- Perfil -->
    <div class="col-12 col-lg-4">
      <div class="card p-3 p-md-4 text-center">
        <img src="<?= htmlspecialchars($avatarURL) ?>" alt="Avatar" class="profile-avatar mb-3 mx-auto d-block" style="height: 80px; width: 80px; border-radius: 50%;">
        <div class="profile-name h4 h3-md"><?= htmlspecialchars($empleado['nombre'] . ' ' . $empleado['apellidos']) ?></div>
        <span class="rol-chip mb-2"><?= htmlspecialchars($empleado['rol']) ?></span>        
        <div class="mb-3 mb-md-4 text-muted small"><?= htmlspecialchars($empleado['email']) ?></div>
        <div class="d-flex flex-column flex-sm-row justify-content-center gap-2">
          <?php
          // Permisos: no permitir editar/eliminar al super admin (excepto él mismo)
          $esSuperAdmin = !empty($empleado['es_super_admin']);
          $esSiMismo = $_SESSION['empleado_id'] === $empleado['id'];
          
          $puedeEditar = (isAdmin() || (isSupervisor() && $empleado['rol'] !== 'admin'))
                         && (!$esSuperAdmin || $esSiMismo);
          $puedeEliminar = canManageEmployees() && !$esSuperAdmin;
          ?>
          
          <?php if ($puedeEditar): ?>
          <a href="<?= $config['ruta_absoluta'] ?>admin/editar-empleado?id=<?= $empleado['id'] ?>" class="btn btn-primary btn-sm">
            <i class="bi bi-pencil"></i> Editar
          </a>
          <?php endif; ?>
          
          <?php if (isAdmin() && (!$esSuperAdmin || $esSiMismo)): ?>
          <a href="<?= $config['ruta_absoluta'] ?>admin/editar-permisos?id=<?= $empleado['id'] ?>" class="btn btn-warning btn-sm">
            <i class="bi bi-shield-lock"></i> Permisos
          </a>
          <?php endif; ?>
          
          <?php if ($puedeEliminar): ?>
          <a
            href="<?= $config['ruta_absoluta'] ?>admin/borrar-empleado.php?id=<?= $empleado['id'] ?>"
            class="btn btn-danger btn-sm"
            onclick='return confirm(<?= json_encode("¿Seguro que deseas eliminar a $fullName?") ?>);'
            >
            <i class="bi bi-trash"></i> Eliminar
          </a>
          <?php endif; ?>
        </div>
        <hr class="my-3 my-md-4">
        <div class="text-start small text-muted px-2">
          <div><b>Alta:</b> <?= htmlspecialchars($empleado['fecha_alta'] ?? '-') ?></div>
        </div>
      </div>

      <!-- Solicitudes del Año Actual -->
      <div class="card p-3 p-md-4 mt-3 mt-md-4">
        <h4 class="card-title mb-3 h5 text-primary"><i class="bi bi-calendar-check"></i> Solicitudes <?= date('Y') ?></h4>
        <div class="row g-3">
          <!-- Vacaciones -->
          <div class="col-12 col-sm-6 col-lg-6">
            <div class="bg-success-subtle rounded p-3 text-center h-100">
              <div class="text-success mb-2" style="font-size: 1.5rem;">
                <i class="bi bi-umbrella"></i>
              </div>
              <div class="h6 text-muted mb-1" style="font-size: 0.95rem;">Vacaciones</div>
              <div class="h5 text-success fw-bold mb-1"><?= $resumenSolicitudes['vacaciones']['dias_totales'] ?></div>
              <div class="text-success" style="font-size: 1rem;">días</div>
              <?php if ($resumenSolicitudes['vacaciones']['dias_pendientes'] > 0): ?>
                <div class="small text-warning">
                  <i class="bi bi-exclamation-circle"></i> <?= $resumenSolicitudes['vacaciones']['dias_pendientes'] ?> pendiente<?= $resumenSolicitudes['vacaciones']['dias_pendientes'] !== 1 ? 's' : '' ?>
                </div>
              <?php endif; ?>
            </div>
          </div>

          <!-- Ausencias -->
          <div class="col-12 col-sm-6 col-lg-6">
            <div class="bg-info-subtle rounded p-3 text-center h-100">
              <div class="text-info mb-2" style="font-size: 1.5rem;">
                <i class="bi bi-person-dash"></i>
              </div>
              <div class="h6 text-muted mb-1" style="font-size: 0.95rem;">Ausencias</div>
              <div class="h5 text-info fw-bold mb-1"><?= $resumenSolicitudes['ausencias']['dias_totales'] ?></div>
              <div class="text-info" style="font-size: 1rem;">días</div>
              <?php if ($resumenSolicitudes['ausencias']['dias_pendientes'] > 0): ?>
                <div class="small text-warning">
                  <i class="bi bi-exclamation-circle"></i> <?= $resumenSolicitudes['ausencias']['dias_pendientes'] ?> pendiente<?= $resumenSolicitudes['ausencias']['dias_pendientes'] !== 1 ? 's' : '' ?>
                </div>
              <?php endif; ?>
            </div>
          </div>

          <!-- Bajas -->
          <div class="col-12 col-sm-6 col-lg-6">
            <div class="bg-danger-subtle rounded p-3 text-center h-100">
              <div class="text-danger mb-2" style="font-size: 1.5rem;">
                <i class="bi bi-hospital"></i>
              </div>
              <div class="h6 text-muted mb-1" style="font-size: 0.95rem;">Bajas</div>
              <div class="h5 text-danger fw-bold mb-1"><?= $resumenSolicitudes['bajas']['dias_totales'] ?></div>
              <div class="text-danger" style="font-size: 1rem;">días</div>
              <?php if ($resumenSolicitudes['bajas']['dias_pendientes'] > 0): ?>
                <div class="small text-warning">
                  <i class="bi bi-exclamation-circle"></i> <?= $resumenSolicitudes['bajas']['dias_pendientes'] ?> pendiente<?= $resumenSolicitudes['bajas']['dias_pendientes'] !== 1 ? 's' : '' ?>
                </div>
              <?php endif; ?>
            </div>
          </div>

          <!-- Horas Extra -->
          <div class="col-12 col-sm-6 col-lg-6">
            <div class="bg-primary-subtle rounded p-3 text-center h-100">
              <div class="text-primary mb-2" style="font-size: 1.5rem;">
                <i class="bi bi-hourglass-top"></i>
              </div>
              <div class="h6 text-muted mb-1" style="font-size: 0.95rem;">Horas Extra</div>
              <div class="h5 text-primary fw-bold mb-1"><?= $horasExtra['horas'] ?>h <?= $horasExtra['minutos'] ?>m</div>
              <div class="text-primary" style="font-size: 0.9rem;"><?= number_format($horasExtra['total'], 2) ?>h total</div>
            </div>
          </div>
        </div>
      </div>
    </div>
    <!-- Horario y Fichajes -->
    <div class="col-12 col-lg-8">
      <div class="card p-3 p-md-4 mb-3 mb-md-4">
        <div class="d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center mb-3 gap-2">
          <div>
            <h4 class="card-title mb-2 mb-sm-4 pb-2 text-primary h5"><i class="bi bi-clock"></i> Horario</h4></div>
          <a href="<?= $config['ruta_absoluta'] ?>admin/editar-horario?id=<?= $empleado['id'] ?>" class="btn btn-sm btn-warning">Editar horario</a>
        </div>
        <div class="bg-soft rounded p-3 mb-0">
            <?php if ($horariosData): ?>
                <div class="d-none d-md-block">
                    <ul class="list-group list-group-flush">
                    <?php foreach ($horariosData as $row): ?>
                        <li class="list-group-item d-flex justify-content-between align-items-center px-3">
                            <span>
                                <i class="bi bi-calendar-date text-muted me-2"></i>
                                <?= htmlspecialchars($row['dia']) ?>
                            </span>
                            <span class="badge bg-success-subtle text-success rounded-pill">
                                <i class="bi bi-clock me-1"></i>
                                <?php if ($row['horario_partido'] == 1 && $row['hora_inicio_tarde'] && $row['hora_fin_tarde']): ?>
                                    <?= substr($row['hora_inicio'],0,5) ?> - <?= substr($row['hora_fin'],0,5) ?> | 
                                    <?= substr($row['hora_inicio_tarde'],0,5) ?> - <?= substr($row['hora_fin_tarde'],0,5) ?>
                                <?php else: ?>
                                    <?= substr($row['hora_inicio'],0,5) ?> - <?= substr($row['hora_fin'],0,5) ?>
                                <?php endif; ?>
                            </span>
                        </li>
                    <?php endforeach; ?>
                    </ul>
                </div>
                <!-- Vista móvil compacta -->
                <div class="d-md-none">
                    <div class="horario-mobile-grid">
                        <?php foreach ($horariosData as $row): ?>
                            <div class="horario-mobile-item">
                                <div class="dia-nombre">
                                    <i class="bi bi-calendar-date me-1"></i>
                                    <?= htmlspecialchars($row['dia']) ?>
                                </div>
                                <div class="horario-badges">
                                    <?php if ($row['horario_partido'] == 1 && $row['hora_inicio_tarde'] && $row['hora_fin_tarde']): ?>
                                        <span class="horario-badge">
                                            <i class="bi bi-clock me-1"></i>
                                            <?= substr($row['hora_inicio'],0,5) ?> - <?= substr($row['hora_fin'],0,5) ?>
                                        </span>
                                        <span class="horario-badge">
                                            <i class="bi bi-clock me-1"></i>
                                            <?= substr($row['hora_inicio_tarde'],0,5) ?> - <?= substr($row['hora_fin_tarde'],0,5) ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="horario-badge">
                                            <i class="bi bi-clock me-1"></i>
                                            <?= substr($row['hora_inicio'],0,5) ?> - <?= substr($row['hora_fin'],0,5) ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php else: ?>
                <div class="text-muted">Sin horario asignado.</div>
            <?php endif; ?>
        </div>
      </div>

      <div class="card p-3 p-md-4">
        <div class="card-header d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center gap-2 border-0 bg-transparent p-0 mb-3">
          <span class="card-title h5 mb-2 mb-sm-0 text-primary"><i class="bi bi-calendar-check"></i> Fichajes</span>
          <form method="get" action="<?= $config['ruta_absoluta'] ?>admin/ver-empleado" class="d-flex flex-wrap gap-2">
            <input type="hidden" name="page" value="admin/ver-empleado">
            <input type="hidden" name="id" value="<?= $empleado['id'] ?>">
            <input type="date" name="desde" class="form-control form-control-sm" style="width:auto; min-width:120px;" value="<?= htmlspecialchars($fechaDesde ?? '') ?>">
            <input type="date" name="hasta" class="form-control form-control-sm" style="width:auto; min-width:120px;" value="<?= htmlspecialchars($fechaHasta ?? '') ?>">
            <button type="submit" class="btn btn-success btn-sm"><i class="bi bi-search"></i><span class="d-none d-sm-inline ms-1">Buscar</span></button>
            <button type="submit" class="btn btn-danger btn-sm" formaction="<?= $config['ruta_absoluta'] ?>admin/generar-pdf.php"><i class="bi bi-file-earmark-pdf"></i><span class="d-none d-sm-inline ms-1">PDF</span></button>
          </form>
        </div>
        <div class="card-body p-0">
          <!-- Vista de tabla para pantallas grandes -->
          <div class="table-responsive d-none d-md-block">
            <table class="table table-sm mb-0">
              <thead class="bg-light">
                <tr class="small text-muted">
                  <th width="50"></th>
                  <th>Fecha</th>
                  <th>Entrada</th>
                  <th>Salida</th>
                  <th>Duración</th>
                  <th>Descanso</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach($tablaDias as $d): ?>
                  <?php 
                    // Convertir la fecha d/m/Y a Y-m-d para usar como ID
                    $fechaPartes = explode('/', $d['fecha']);
                    $fechaId = $fechaPartes[2] . '-' . str_pad($fechaPartes[1], 2, '0', STR_PAD_LEFT) . '-' . str_pad($fechaPartes[0], 2, '0', STR_PAD_LEFT);
                  ?>
                  <tr data-fecha="<?= $fechaId ?>">
                    <td>
                      <button class="btn btn-outline-primary btn-sm expand-btn" type="button">
                        <i class="bi bi-plus"></i>
                      </button>
                    </td>
                    <td><?= htmlspecialchars($d['fecha']) ?></td>
                    <td><?= htmlspecialchars($d['entrada']) ?></td>
                    <td><?= htmlspecialchars($d['salida']) ?></td>
                    <td><?= htmlspecialchars($d['trabajo']) ?></td>
                    <td><?= htmlspecialchars($d['descanso']) ?></td>
                  </tr>
                  <tr id="detalle-<?= $fechaId ?>" class="detalle-row" style="display:none">
                    <td colspan="6" class="p-0">
                      <div class="p-3 bg-light">
                        <div class="d-flex justify-content-between mb-2">
                          <strong>Detalle del día <?= htmlspecialchars($d['fecha']) ?></strong>
                          <div class="spinner-border spinner-border-sm d-none" id="spinner-<?= $fechaId ?>"></div>
                        </div>
                        <div id="content-<?= $fechaId ?>"><!-- AJAX aquí --></div>
                      </div>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
          
          <!-- Vista de tarjetas para móviles -->
          <div class="d-md-none">
            <?php foreach($tablaDias as $d): ?>
              <?php 
                // Convertir la fecha d/m/Y a Y-m-d para usar como ID
                $fechaPartes = explode('/', $d['fecha']);
                $fechaId = $fechaPartes[2] . '-' . str_pad($fechaPartes[1], 2, '0', STR_PAD_LEFT) . '-' . str_pad($fechaPartes[0], 2, '0', STR_PAD_LEFT);
              ?>
              <div class="card mb-3 border-0 shadow-sm fichaje-card-mobile" data-fecha="<?= $fechaId ?>">
                <div class="card-body p-0">
                  <!-- Encabezado con fecha -->
                  <div class="fecha-header">
                    <div class="d-flex justify-content-between align-items-center">
                      <span><i class="bi bi-calendar3 me-2"></i><?= htmlspecialchars($d['fecha']) ?></span>
                      <button class="btn expand-btn" type="button" title="Ver detalles">
                        <i class="bi bi-chevron-down"></i>
                      </button>
                    </div>
                  </div>
                  
                  <!-- Información de horario -->
                  <div class="horario-info">
                    <i class="bi bi-clock"></i>
                    <span><?= htmlspecialchars($d['entrada']) ?> - <?= htmlspecialchars($d['salida']) ?></span>
                  </div>
                  
                  <!-- Stats de tiempo -->
                  <div class="stats-container">
                    <div class="row g-3">
                      <div class="col-6">
                        <div class="tiempo-stats trabajado">
                          <div class="label">
                            <i class="bi bi-briefcase"></i>
                            <span>Trabajado</span>
                          </div>
                          <div class="value"><?= htmlspecialchars($d['trabajo']) ?></div>
                        </div>
                      </div>
                      <div class="col-6">
                        <div class="tiempo-stats descanso">
                          <div class="label">
                            <i class="bi bi-cup-hot"></i>
                            <span>Descanso</span>
                          </div>
                          <div class="value"><?= htmlspecialchars($d['descanso']) ?></div>
                        </div>
                      </div>
                    </div>
                  </div>
                  
                  <!-- Detalle expandible -->
                  <div id="detalle-mobile-<?= $fechaId ?>" class="detalle-row-mobile" style="display:none">
                    <div>
                      <div class="detalle-header">
                        <strong>
                          <i class="bi bi-list-ul"></i>
                          <span>Registro de actividad</span>
                        </strong>
                        <div class="spinner-border spinner-border-sm d-none text-primary" id="spinner-mobile-<?= $fechaId ?>"></div>
                      </div>
                      <div id="content-mobile-<?= $fechaId ?>"><!-- AJAX aquí --></div>
                    </div>
                  </div>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        </div>
      </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
  const detalleUrl = '<?= $config['ruta_absoluta'] ?>admin/detalle-fichajes.php';
  const empleadoId = <?= (int)$empleado['id'] ?>;
  
  // Inicializar la funcionalidad de detalle de fichajes
  initDetalleFichajes(detalleUrl, empleadoId);
});
</script>
