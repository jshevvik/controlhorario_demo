<?php
session_start();
require_once __DIR__ . '/../includes/init.php';

// Endpoint AJAX para datos iniciales
if (isset($_GET['action']) && $_GET['action'] === 'init') {
    header('Content-Type: application/json');
    echo json_encode([
        'balances' => obtenerSaldos($pdo, $_SESSION['empleado_id'] ?? null),
        'holidays' => obtenerFestivos($pdo, 30, $_SESSION['region'] ?? null), // Para el widget lateral
        'festivosCalendario' => obtenerFestivosCalendario($pdo, $_SESSION['region'] ?? null), // Para el calendario
        'proximosEventos' => obtenerProximosEventos($pdo, $_SESSION['empleado_id'] ?? null, 30, $_SESSION['region'] ?? null),
        'history'  => getHistorialSolicitudes($pdo, $_SESSION['empleado_id'] ?? null),
        'eventos'  => getEventosCalendario($pdo, $_SESSION['empleado_id'] ?? null)
    ]);
    exit;
}

// Datos para render
$empleadoId = $_SESSION['empleado_id'] ?? null;
$saldos     = obtenerSaldos($pdo, $empleadoId);
$region     = $_SESSION['region'] ?? null;
$holidays   = obtenerFestivos($pdo, 30, $region); // Para widget lateral (próximos 30 días)
$proximosEventos = obtenerProximosEventos($pdo, $empleadoId, 30, $region);
$eventos    = getEventosCalendario($pdo, $empleadoId);
$horarios   = obtenerHorariosEmpleado($pdo, $empleadoId);

// Obtener todos los festivos para el calendario (todo el año)
$festivosCalendario = obtenerFestivosCalendario($pdo, $region);

// Mapear días de texto a número para FullCalendar
$diaMapa = [
    'lunes'     => 1, 'martes'    => 2, 'miercoles' => 3, 'miércoles' => 3,
    'jueves'    => 4, 'viernes'   => 5, 'sabado'    => 6, 'sábado'    => 6,
    'domingo'   => 0
];
// Eventos recurrentes de horario laboral
$horariosEvents = [];
foreach ($horarios as $h) {
    $dia = strtolower($h['dia']);
    if (!isset($diaMapa[$dia])) continue;
    
    // Horario de mañana (siempre presente)
    $horariosEvents[] = [
        'title'      => 'Horario laboral',
        'daysOfWeek' => [$diaMapa[$dia]],
        'startTime'  => substr($h['hora_inicio'], 0, 5),
        'endTime'    => substr($h['hora_fin'],   0, 5),
        'backgroundColor' => '#e9c6f9ff',
        'borderColor'     => '#e9c6f9ff',
        'textColor'       => '#444',
        'extendedProps' => [
            'esHorario'   => true,
            'hora_inicio' => substr($h['hora_inicio'], 0, 5),
            'hora_fin'    => substr($h['hora_fin'],   0, 5),
        ]
    ];
    
    // Horario de tarde (solo si es horario partido)
    if ($h['horario_partido'] == 1 && $h['hora_inicio_tarde'] && $h['hora_fin_tarde']) {
        $horariosEvents[] = [
            'title'      => 'Horario laboral',
            'daysOfWeek' => [$diaMapa[$dia]],
            'startTime'  => substr($h['hora_inicio_tarde'], 0, 5),
            'endTime'    => substr($h['hora_fin_tarde'],   0, 5),
            'backgroundColor' => '#e9c6f9ff',
            'borderColor'     => '#e9c6f9ff',
            'textColor'       => '#444',
            'extendedProps' => [
                'esHorario'   => true,
                'hora_inicio' => substr($h['hora_inicio_tarde'], 0, 5),
                'hora_fin'    => substr($h['hora_fin_tarde'],   0, 5),
            ]
        ];
    }
}
?>
<script>
  window.eventosPHP        = <?= json_encode($eventos, JSON_UNESCAPED_UNICODE) ?>;
  window.horariosEmpleado  = <?= json_encode($horariosEvents, JSON_UNESCAPED_UNICODE) ?>;
  window.festivosPHP       = <?= json_encode($festivosCalendario, JSON_UNESCAPED_UNICODE) ?>;
</script>


<div class="page-wrapper">
  <!-- Breadcrumb -->
  <div class="mb-3 overflow-hidden">
    <div class="px-3">
      <h4 class="fs-6 mb-0">Solicitudes</h4>
      <nav aria-label="breadcrumb">
        <ol class="breadcrumb mb-0">
          <li class="breadcrumb-item">
            <a href="<?= $config['ruta_absoluta'] ?>dashboard">Inicio</a>
          </li>
          <li class="breadcrumb-item active" aria-current="page">Mis solicitudes</li>
        </ol>
      </nav>
    </div>
  </div>

  <div class="container-fluid px-lg-4 px-xxl-5 py-4">
    <!-- Primera fila: Mi saldo, Próximos festivos y Nueva solicitud -->
    <div class="row g-3 g-md-4 mb-3 mb-md-4">
      <!-- Mi Saldo -->
      <div class="col-12 col-sm-6 col-lg-3">
        <div class="card shadow rounded-3 border-0 h-100">
          <div class="card-header bg-primary border-bottom">
            <h5 class="mb-0">Mi saldo</h5>
          </div>
          <div class="card-body">
            <ul class="list-group list-group-flush">
              <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                <span><i class="bi bi-umbrella-fill text-primary me-2"></i> Vacaciones</span>
                <span class="badge text-bg-primary rounded-pill" id="bal-vac">
                  <span id="bal-vac-val"><?php 
                    if (is_array($saldos['vacaciones'])) {
                      $restante = $saldos['vacaciones']['max'] - $saldos['vacaciones']['usado'];
                      echo max(0, $restante) . '/' . $saldos['vacaciones']['max'] . ' d.';
                    }
                  ?></span>
                </span>
              </li>
              <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                <span><i class="bi bi-thermometer-half text-success me-2"></i> Bajas médicas</span>
                <span class="badge text-bg-success rounded-pill" id="bal-baja">
                  <span id="bal-baja-val"><?= (is_numeric($saldos['baja']) ? intval($saldos['baja']) : 0) . ' d.' ?></span>
                </span>
              </li>
              <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                <span><i class="bi bi-alarm text-warning me-2"></i> Horas extras</span>
                <span class="badge text-bg-warning rounded-pill" id="bal-extra">
                  <span id="bal-extra-val"><?= (is_numeric($saldos['extra']) ? number_format($saldos['extra'], 1) : 0) . ' h.' ?></span>
                </span>
              </li>
              <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                <span><i class="bi bi-person-dash text-danger me-2"></i> Ausencias</span>
                <span class="badge text-bg-danger rounded-pill" id="bal-aus">
                  <span id="bal-aus-val"><?= (is_numeric($saldos['ausencia']) ? intval($saldos['ausencia']) : 0) . ' d.' ?></span>
                </span>
              </li>
            </ul>
          </div>
        </div>
      </div>

      <!-- Próximos eventos -->
      <div class="col-12 col-sm-6 col-lg-3">
        <div class="card shadow rounded-3 border-0 h-100">
          <div class="card-header bg-primary border-bottom">
            <h5 class="mb-0">Próximos eventos</h5>
          </div>
          <ul class="list-group list-group-flush">
            <?php if (count($proximosEventos)): ?>
              <?php foreach (array_slice($proximosEventos, 0, 4) as $evento): ?>
                <li class="list-group-item d-flex align-items-center py-2 py-md-3 px-3 px-md-4">
                  <div class="me-2 me-md-3 fs-5 fs-md-4 <?= strpos($evento['color'], '#') === 0 ? '' : 'text-' . $evento['color'] ?>" <?= strpos($evento['color'], '#') === 0 ? 'style="color: ' . $evento['color'] . '"' : '' ?>>
                    <i class="<?= $evento['icono'] ?>"></i>
                  </div>
                  <div class="flex-grow-1">
                    <div class="fw-semibold <?= strpos($evento['colorTitulo'], '#') === 0 ? '' : 'text-' . $evento['colorTitulo'] ?>" <?= strpos($evento['colorTitulo'], '#') === 0 ? 'style="color: ' . $evento['colorTitulo'] . '"' : '' ?>><?= htmlspecialchars($evento['titulo']) ?></div>
                    <small class="text-date">
                      <?php if ($evento['fecha_inicio'] === $evento['fecha_fin']): ?>
                        <?= date('d/m/Y', strtotime($evento['fecha_inicio'])) ?>
                      <?php else: ?>
                        <?= date('d/m', strtotime($evento['fecha_inicio'])) ?> – <?= date('d/m/Y', strtotime($evento['fecha_fin'])) ?>
                      <?php endif; ?>
                    </small>
                  </div>
                </li>
              <?php endforeach ?>
            <?php else: ?>
              <li class="list-group-item text-center text-muted py-3">
                No hay eventos en los próximos 30 días.
              </li>
            <?php endif ?>
          </ul>
        </div>
      </div>

      <!-- Nueva solicitud -->
      <div class="col-12 col-lg-6">
        <div class="card shadow-lg rounded-3 border-0 h-100">
          <div class="card-header bg-primary border-bottom">
            <h5 class="mb-0">Nueva solicitud</h5>
          </div>
          <div class="card-body">
            <form id="tramiteForm" class="needs-validation" enctype="multipart/form-data" novalidate>
                <div class="row g-3">
                    <!-- Tipo de solicitud -->
                    <div class="col-md-6">
                      <label for="tramiteType" class="form-label">Tipo de solicitud <span class="text-danger">*</span></label>
                      <select id="tramiteType" name="tipo" class="form-select" required>
                        <option value="" disabled selected>– Selecciona –</option>
                        <option value="vacaciones">Vacaciones</option>
                        <option value="baja">Baja médica</option>
                        <option value="ausencia">Ausencia</option>
                        <option value="extra">Horas extras</option>
                      </select>
                      <div class="invalid-feedback">Selecciona un tipo.</div>
                    </div>

                    <!-- Periodo fechas -->
                    <div class="col-md-6" id="periodoGroup">
                      <label for="daterange" class="form-label">Periodo <span class="text-danger">*</span></label>
                      <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-calendar2-range"></i></span>
                        <input type="text" class="form-control" name="daterange" id="daterange"
                               placeholder="DD/MM/AAAA – DD/MM/AAAA" required>
                      </div>
                      <div class="invalid-feedback">Indica el rango de fechas.</div>
                    </div>

                    <!-- Medio día -->
                    <div class="col-md-3 col-sm-6 d-flex align-items-center" id="halfDayGroup">
                      <div class="form-check form-switch mb-0">
                        <input class="form-check-input" type="checkbox" id="halfDaySwitch" name="half_day">
                        <label class="form-check-label small" for="halfDaySwitch">Medio día</label>
                      </div>
                    </div>

                    <!-- Horas extras: fecha + horas -->
                    <div class="col-12 d-none" id="extraHoursGroup">
                      <div class="row g-3">
                        <div class="col-md-4 col-sm-6">
                          <label for="extraDate" class="form-label">Fecha <span class="text-danger">*</span></label>
                          <div class="input-group input-group-sm">
                            <span class="input-group-text"><i class="bi bi-calendar-date"></i></span>
                            <input type="text" class="form-control" id="extraDate" name="fecha_extra"
                                   placeholder="DD/MM/AAAA" required>
                          </div>
                          <div class="invalid-feedback">Selecciona la fecha.</div>
                        </div>
                        <div class="col-md-3 col-sm-6">
                          <label for="extraStartTime" class="form-label">Hora inicio <span class="text-danger">*</span></label>
                          <input type="time" class="form-control form-control-sm"
                                 id="extraStartTime" name="hora_inicio" required>
                          <div class="invalid-feedback">Indica la hora de inicio.</div>
                        </div>
                        <div class="col-md-3 col-sm-6">
                          <label for="extraEndTime" class="form-label">Hora fin <span class="text-danger">*</span></label>
                          <input type="time" class="form-control form-control-sm"
                                 id="extraEndTime" name="hora_fin" required>
                          <div class="invalid-feedback">Indica la hora de fin.</div>
                        </div>
                        <div class="col-md-2 col-sm-6">
                          <label for="extraHours" class="form-label">Total horas</label>
                          <input type="number" step="0.5" min="0" class="form-control form-control-sm"
                                 id="extraHours" name="horas" readonly>
                          <small class="text-date">Calculado automáticamente</small>
                        </div>
                      </div>
                    </div>

                    <!-- Tipo de ausencia -->
                    <div class="col-12 d-none" id="absenceTypeGroup">
                      <label for="absenceType" class="form-label">Tipo de ausencia <span class="text-danger">*</span></label>
                      <select id="absenceType" name="tipo_ausencia" class="form-select" required>
                        <option value="" disabled selected>– Selecciona el motivo –</option>
                        <option value="cita_medica">Cita médica</option>
                        <option value="cuidado_familiar">Cuidado familiar</option>
                        <option value="fallecimiento_familiar">Fallecimiento de un familiar</option>
                        <option value="accidente_enfermedad_familiar">Accidente o enfermedad grave de un familiar</option>
                        <option value="hospitalizacion_familiar">Hospitalización o intervención quirúrgica de familiar</option>
                        <option value="matrimonio">Matrimonio</option>
                        <option value="nacimiento_hijo">Nacimiento de hijo/a</option>
                        <option value="mudanza">Mudanza o cambio de domicilio</option>
                      </select>
                      <div class="invalid-feedback">Selecciona el tipo de ausencia.</div>
                    </div>

                    <!-- Comentario / Motivo -->
                    <div class="col-12">
                      <div class="form-floating">
                        <textarea class="form-control" id="reason" name="motivo"
                                  placeholder="Comentario / motivo" style="height: 80px"></textarea>
                        <label for="reason">Comentario / motivo</label>
                      </div>
                    </div>

                    <!-- Archivo opcional -->
                    <div class="col-md-6">
                      <label for="file" class="form-label">Adjuntar archivo (opcional)</label>
                      <input class="form-control form-control-sm" type="file" id="file" name="file"
                             accept=".pdf,.doc,.docx,.jpg,.jpeg,.png,.gif">
                      <small class="text-small">Máximo 5MB. Formatos: PDF, DOC, DOCX, JPG, PNG, GIF</small>
                    </div>
                </div>
            </form>
          </div>
          <div class="card-footer text-end bg-white border-top">
            <button type="submit" form="tramiteForm" class="btn btn-primary">
               Enviar solicitud
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Segunda fila: Historial y Calendario -->
    <div class="row g-3 g-md-4">
      <!-- Historial de solicitudes -->
      <div class="col-12 col-sm-6 col-md-5 col-lg-3">
        <div class="card shadow rounded-3 border-0 h-100 request-history-card">
          <div class="card-header bg-primary text-white">
            <h5 class="mb-0">Solicitudes</h5>
          </div>
          <div class="card-body p-0" id="request-history">
            <ul class="list-group list-group-flush">
              <?php 
              $historyData = getHistorialSolicitudes($pdo, $_SESSION['empleado_id'] ?? null) ?? [];
              foreach ($historyData as $item): 
            // Calcula clases y textos
            $tipoText = [
                'vacaciones'=>'Vacaciones',
                'baja'=>'Baja médica','extra'=>'Horas extra','ausencia'=>'Ausencia'
            ][$item['tipo']] ?? ucfirst($item['tipo']);
            $estadoClass = [
                'aprobado'=>'success','pendiente'=>'warning','rechazado'=>'danger'
            ][$item['estado']] ?? 'secondary';
            $fecha = $item['tipo']==='extra'
                    ? ($item['horas'] && $item['horas'] > 0 ? "{$item['horas']} h." . (($item['hora_inicio'] && $item['hora_fin']) ? ' (' . substr($item['hora_inicio'], 0, 5) . '-' . substr($item['hora_fin'], 0, 5) . ')' : '') : '-')
                    : date('d/m',strtotime($item['fecha_inicio']))
                        ." – ".date('d/m',strtotime($item['fecha_fin']));
            ?>
            <li class="list-group-item d-flex align-items-center py-2 py-md-3 px-3 px-md-4">
              <div class="me-2 me-md-3 fs-5 fs-md-4 text-<?= $estadoClass ?>">
                <?php
                
                $icons = [
                    'vacaciones'=>'bi-umbrella-fill',
                    'baja'=>'bi-thermometer-half','extra'=>'bi-alarm',
                    'ausencia'=>'bi-person-dash'
                ];
                echo '<i class="bi '.$icons[$item['tipo']].'"></i>';
                ?>
              </div>
              <div class="flex-grow-1">
                <div class="fw-semibold">
                  <?= $tipoText ?>
                  <?php if (!empty($item['archivo'])): ?>
                    <i class="bi bi-paperclip text-muted ms-1" title="Tiene archivo adjunto"></i>
                  <?php endif; ?>
                </div>
                <small class="text-date"><?= $fecha ?></small>
              </div>
              <div>
                <span class="badge bg-<?= $estadoClass ?>">
                  <?= ucfirst($item['estado']) ?>
                </span>
              </div>
            </li>
            <?php endforeach; ?>
            <?php if (empty($historyData)): ?>
            <li class="list-group-item text-center text-muted py-3">
              No hay solicitudes registradas.
            </li>
            <?php endif; ?>
            </ul>
          </div>
        </div>
      </div>

      <!-- Calendario -->
      <div class="col-12 col-sm-6 col-md-7 col-lg-9">
        <div class="card shadow-lg rounded-3 border-0">
          <div class="card-header bg-primary border-bottom">
            <h5 class="mb-0">Mi calendario</h5>
          </div>
          <div class="card-body">
            <div class="mb-3 d-flex flex-wrap gap-2" id="calendar-filters">
              <button class="btn btn-outline-primary btn-sm active" data-type="all">
                <i class="bi bi-calendar3"></i> <span class="d-none d-sm-inline">Todos</span>
              </button>
              <button class="btn btn-outline-primary btn-sm" data-type="vacaciones">
                <i class="bi bi-umbrella"></i> <span class="d-none d-sm-inline">Vacaciones</span>
              </button>
              <button class="btn btn-outline-success btn-sm" data-type="baja">
                <i class="bi bi-thermometer-half"></i> <span class="d-none d-sm-inline">Baja médica</span>
              </button>
              <button class="btn btn-outline-warning btn-sm" data-type="extra">
                <i class="bi bi-alarm"></i> <span class="d-none d-sm-inline">Horas extra</span>
              </button>
              <button class="btn btn-outline-danger btn-sm" data-type="ausencia">
                <i class="bi bi-person-dash"></i> <span class="d-none d-sm-inline">Ausencia</span>
              </button>
            </div>
            <div id="leave-calendar" class="fc fc-unthemed"></div>
            <small class="text-muted d-block mt-3 d-none d-md-block">* Colores según estado de la solicitud.</small>
          </div>
        </div>
      </div>
    </div>

    
  </div>

  <!-- Modal de confirmación -->
  <div class="modal fade" id="confirmModal" tabindex="-1" aria-labelledby="confirmLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content rounded-3">
        <div class="modal-header">
          <h5 class="modal-title" id="confirmLabel">Confirmación</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
        </div>
        <div class="modal-body">
          ¿Confirmas el envío de la solicitud por <span id="summaryDays"></span>?
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancelar</button>
          <button type="button" class="btn btn-primary" id="confirmSubmit">Enviar</button>
        </div>
      </div>
    </div>
  </div>
</div>
