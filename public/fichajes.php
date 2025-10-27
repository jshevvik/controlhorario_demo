<?php


//   CONFIGURACIONES   //
// Días de la semana en español
$dias = ['Lunes','Martes','Miércoles','Jueves','Viernes','Sábado','Domingo'];
$horario = [];
foreach ($dias as $d) {
    $horario[$d] = ''; 
}

// Consulta la BD
$stmt = $pdo->prepare("SELECT dia, hora_inicio, hora_fin, hora_inicio_tarde, hora_fin_tarde, horario_partido FROM horarios_empleados WHERE empleado_id = ?");
$stmt->execute([$emp['id']]);
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $dia = $row['dia'];
    if (in_array($dia, $dias)) {
        if ($row['hora_inicio'] && $row['hora_fin']) {
            if ($row['horario_partido'] == 1 && $row['hora_inicio_tarde'] && $row['hora_fin_tarde']) {
                $horario[$dia] = date('H:i', strtotime($row['hora_inicio'])) . ' - ' . date('H:i', strtotime($row['hora_fin'])) . '  |  ' . 
                               date('H:i', strtotime($row['hora_inicio_tarde'])) . ' - ' . date('H:i', strtotime($row['hora_fin_tarde']));
            } else {
                $horario[$dia] = date('H:i', strtotime($row['hora_inicio'])) . ' - ' . date('H:i', strtotime($row['hora_fin']));
            }
        }
    }
}


// Definiciones de estados y estilos
$estados = [
    'entrada' => ['texto' => 'Estás trabajando', 'badge' => 'bg-success'],
    'salida' => ['texto' => 'Jornada finalizada', 'badge' => 'bg-danger'],
    'pausa_inicio' => ['texto' => 'Descanso', 'badge' => 'bg-info'],
    'pausa_fin' => ['texto' => 'Estás trabajando', 'badge' => 'bg-primary']
];

$tiposFichaje = [
    'entrada' => ['icono' => 'box-arrow-in-right', 'texto' => 'Entrada: '],
    'salida' => ['icono' => 'box-arrow-left', 'texto' => 'Salida: '],
    'pausa_inicio' => ['icono' => 'cup', 'texto' => 'Descanso: '],
    'pausa_fin' => ['icono' => 'cup-fill', 'texto' => 'Fin descanso: ']
];


// Obtener fichajes del día actual
$empId = $emp['id'];
$sql = "SELECT * FROM fichajes 
        WHERE empleado_id = ? AND DATE(hora) = CURDATE() 
        ORDER BY hora ASC";
$stmt = $pdo->prepare($sql);
$stmt->execute([$empId]);
$fichajesHoy = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Procesar fichajes
$horaEntrada = null;
$horaSalida = null;
$pausas = [];
$ultimoFichaje = end($fichajesHoy);
$ultimoTipo = $ultimoFichaje['tipo'] ?? '';


if ($ultimoFichaje) reset($fichajesHoy);

foreach ($fichajesHoy as $f) {
    switch ($f['tipo']) {
        case 'entrada':
            if (!$horaEntrada) $horaEntrada = $f['hora'];
            break;
        case 'salida':
            $horaSalida = $f['hora'];
            break;
        case 'pausa_inicio':
            $pausas[] = ['inicio' => $f['hora'], 'fin' => null];
            break;
        case 'pausa_fin':
            if (!empty($pausas)) {
                $ultimaPausa = end($pausas);
                $clave = key($pausas);
                if ($ultimaPausa['fin'] === null) {
                    $pausas[$clave]['fin'] = $f['hora'];
                }
            }
            break;
    }
}

// Cálculo de duraciones usando la misma lógica que el servidor
$tiempos = calcularTiemposHoy($pdo, $empId);
$workSec = $tiempos['workSec'];
$pauseSec = $tiempos['pauseSec'];

// Convertir segundos a horas y minutos
$horas = intdiv($workSec, 3600);
$minutos = intdiv($workSec % 3600, 60);
$trabajado = $horas > 0 || $minutos > 0 ? "{$horas}h {$minutos}m" : "—";

$minutospausa = intdiv($pauseSec, 60);
$minutosPausa = $minutospausa;

// Determinar estado actual
$estadoActual = 'Sin fichaje hoy';
if ($ultimoFichaje) {
    $estadoActual = $estados[$ultimoTipo]['texto'] ?? 'Estado desconocido';
}
?>

<div class="mb-3 overflow-hidden position-relative">
  <div class="px-3">
    <h4 class="fs-6 mb-0">Fichajes</h4>
    <nav aria-label="breadcrumb">
      <ol class="breadcrumb mb-0">
        <li class="breadcrumb-item">
          <a href="<?= $config['ruta_absoluta'] ?>dashboard">Inicio</a>
        </li>
        <li class="breadcrumb-item active" aria-current="page">Fichajes</li>
      </ol>
    </nav>
  </div>
</div>
<div class="container-fluid py-4 px-1">
  <div class="row g-4">
    <!-- Columna izquierda -->
    <div class="col-12 col-lg-5 mb-4">
      <div class="card shadow-sm mb-4">
        <img src="<?= $config['ASSET_URL'] ?>img/cabecera-fichaje.jpg" class="card-img-top" style="height:110px;object-fit:cover;">
        <div class="card-body pb-2 text-center">
          <!-- Estado actual -->
          <div class="mb-3">
            <span class="badge d-inline-flex align-items-center justify-content-center px-4 py-3 shadow-sm <?= $ultimoFichaje ? $estados[$ultimoTipo]['badge'] : 'bg-secondary' ?>"
                  style="font-size:1.15rem;border-radius:18px;min-width:180px;min-height:48px;letter-spacing:0.5px;box-shadow:0 2px 8px #e3e7f0;">
              <i class="bi bi-person-check me-2" style="font-size:1.3em;"></i>
              <span><?= $estadoActual ?></span>
            </span>
          </div>
          <!-- Tiempo trabajado -->
          <h2 class="fw-bold mb-2 text-success" style="font-size:2.2rem;">
            <i class="bi bi-clock-history me-2"></i><?= $trabajado ?>
          </h2>
          <?php if (!$horaSalida): ?>
            <div class="small text-muted">* Tiempo contado hasta ahora (sin salida)</div>
          <?php endif; ?>

          <!-- Estadísticas -->
          <div class="d-flex flex-column flex-sm-row justify-content-around align-items-stretch gap-2 mb-2 w-100">
            <div class="text-center flex-fill">
              <i class="bi bi-clock fs-5 text-primary"></i><br>
              <small class="text-muted">Trabajado:</small>
              <div class="fw-bold text-primary"><?= $trabajado ?></div>
            </div>
            <div class="text-center flex-fill">
              <i class="bi bi-cup-hot fs-5 text-info"></i><br>
              <small class="text-muted">Descansos:</small>
              <div class="fw-bold text-info"><?= $minutosPausa ?>m</div>
            </div>
            <div class="text-center flex-fill">
              <i class="bi bi-alarm fs-5 text-warning"></i><br>
              <small class="text-muted">Fin jornada:</small>
              <div class="fw-bold text-warning">
                <?= $horaSalida ? date('H:i', strtotime($horaSalida)) : '--:--' ?>
              </div>
            </div>
          </div>


          <!-- Último fichaje -->
          <div class="mt-3 text-center">
            <span class="text-muted small"><i class="bi bi-arrow-repeat"></i> Último fichaje:</span><br>
            <span class="fw-bold text-success" style="font-size:1.5rem;">
              <?php if ($ultimoFichaje): ?>
                <?= $tiposFichaje[$ultimoTipo]['texto'] ?? ucfirst($ultimoTipo) ?>
                <?= date('H:i', strtotime($ultimoFichaje['hora'])) ?>
              <?php else: ?>
                —
              <?php endif; ?>
            </span>
          </div>
        </div>
      </div>
      <!-- Horario semanal -->
      <div class="card shadow-sm">
        <div class="card-header bg-primary text-white">
          <h5 class="mb-0"><i class="bi bi-calendar-week me-2"></i>Horario Semanal</h5>
        </div>
        <div class="card-body p-0">
          <div class="list-group list-group-flush">
            <?php
              $diasSemana = [
                  'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado', 'Domingo'
              ];

              $hayHorarios = false;
              foreach ($diasSemana as $dia) {
                  if (!empty($horario[$dia])) {
                      $hayHorarios = true;
                      break;
                  }
              }

              if (!$hayHorarios): ?>
                <div class="list-group-item text-center py-4">
                  <i class="bi bi-calendar-x text-muted mb-2" style="font-size: 2rem;"></i>
                  <div class="text-muted">
                    <strong>No hay horario asignado</strong><br>
                    <small>Contacta con tu administrador para configurar tu horario de trabajo</small>
                  </div>
                </div>
              <?php else:
                foreach ($diasSemana as $dia):
                    $franja = $horario[$dia] ?? '';
                    
                    // Solo mostrar si hay horario asignado
                    if (empty($franja)) continue;
                    
                    $isToday = ($dia === ucfirst(strtolower(date('l'))));
                    
                    // Iconos para cada día
                    $iconos = [
                      'Lunes' => 'calendar-date',
                      'Martes' => 'calendar-date-fill', 
                      'Miércoles' => 'calendar-week',
                      'Jueves' => 'calendar-week-fill',
                      'Viernes' => 'calendar-check',
                      'Sábado' => 'calendar-heart',
                      'Domingo' => 'calendar-x'
                    ];
                    $icono = $iconos[$dia] ?? 'calendar';
                ?>
                <div class="list-group-item d-flex justify-content-between align-items-center px-3">
                  <span class="<?= $isToday ? 'text-primary' : '' ?>">
                    <i class="bi bi-<?= $icono ?> <?= $isToday ? 'text-primary' : 'text-muted' ?> me-2"></i>
                    <?= $dia ?>
                    <?php if ($isToday): ?>
                      <span class="badge bg-primary ms-2 text-white" style="font-size: 0.7rem;">Hoy</span>
                    <?php endif; ?>
                  </span>
                  <span class="badge bg-success-subtle text-success rounded-pill">
                    <i class="bi bi-clock me-1"></i><?= $franja ?>
                  </span>
                </div>
                <?php endforeach;
              endif; ?>
          </div>
        </div>
      </div>
    </div>
    <!-- Columna derecha -->
    <div class="col-12 col-lg-7 mb-4">
      <!-- Mensaje de feedback -->
      <?php if (isset($_GET['fichaje']) && $_GET['fichaje'] === 'ok'): ?>
        <?php
          $accion = $_GET['accion'] ?? '';
          $mensajes = [
            'entrada'     => ['msg' => '¡Bienvenid@! Tu jornada comienza ahora. <b>Entrada</b> registrada.', 'icon' => 'bi-box-arrow-in-right', 'class' => 'success'],
            'salida'      => ['msg' => '¡Lo hiciste! Completa tu <b>salida</b>. ¡Que disfrutes tu merecido descanso!', 'icon' => 'bi-box-arrow-left', 'class' => 'warning'],
            'pausa_inicio'=> ['msg' => '<b>Pausa activada</b>. Tómate un respiro, ¡te lo mereces! Recarga energías.', 'icon' => 'bi-cup', 'class' => 'info'],
            'pausa_fin'   => ['msg' => '⚡ <b>Descanso terminado</b>. ¡Vuelta a la acción! Vuelve a brillar.', 'icon' => 'bi-cup-fill', 'class' => 'primary'],
          ];
          $m = $mensajes[$accion] ?? ['msg' => '✅ Fichaje realizado correctamente.', 'icon' => 'bi-check-circle-fill', 'class' => 'success'];
        ?>
        <div class="alert alert-<?= $m['class'] ?> alert-dismissible fade show mb-4" role="alert">
          <i class="bi <?= $m['icon'] ?> me-2"></i> <?= $m['msg'] ?>
          <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
        </div>
      <?php elseif (isset($_GET['fichaje']) && $_GET['fichaje'] === 'error' && ($_GET['motivo'] ?? '') === 'duplicado'): ?>
        <div class="alert alert-warning alert-dismissible fade show mb-4" role="alert">
          <i class="bi bi-exclamation-triangle me-2"></i> 🤔 Espera... ¡Acabas de hacer exactamente esto! No se pueden registrar dos veces seguidas la misma acción. ¡Pon un poco de variedad!
          <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
        </div>
      <?php elseif (isset($_GET['fichaje']) && $_GET['fichaje'] === 'error'): ?>
        <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
          <i class="bi bi-x-circle-fill me-2"></i> ❌ ¡Oops! Algo salió mal con tu fichaje. Vuelve a intentarlo, ¡tú puedes!
          <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
        </div>
      <?php endif; ?>
      <!-- Panel de fichaje estilo cronómetro -->
      <div class="card mb-4 resumen-card">
        <div class="card-body text-center py-4">
          <div class="fichaje-pill d-flex flex-column align-items-center justify-content-center bg-white shadow-sm mb-4 px-4 py-3" style="width:100%;max-width:500px;margin:0 auto;">
            <div class="fw-bold text-primary-emphasis mb-2" style="font-size:1.3rem;">Hoy, <?= traducirFecha(date('Y-m-d')) ?></div>
            <div class="fw-bold text-success text-center" style="font-size:6rem; letter-spacing:2px; line-height:1;">
              <span id="fichajeTimer">--:--:--</span>
            </div>
          </div>
          <div class="row justify-content-center g-2 mt-2">
            <div class="col-12 col-md-3">
              <form method="post" action="<?= $config['ruta_absoluta'] ?>fichaje/procesar-fichaje.php" class="d-inline w-100">
                <input type="hidden" name="tipo" value="entrada">
                <button type="submit" class="btn btn-success d-md-inline-block rounded-pill shadow-sm block-card bg-success-subtle text-success w-100 mb-2">
                  <i class="bi bi-box-arrow-in-right"></i> Entrar
                </button>
              </form>
            </div>
            <div class="col-6 col-md-3">
              <form method="post" action="<?= $config['ruta_absoluta'] ?>fichaje/procesar-fichaje.php" class="d-inline w-100">
                <input type="hidden" name="tipo" value="salida">
                <button type="submit" class="btn btn-danger d-md-inline-block rounded-pill shadow-sm block-card bg-danger-subtle text-danger w-100 mb-2">
                  <i class="bi bi-box-arrow-left"></i> Salir
                </button>
              </form>
            </div>
            <div class="col-6 col-md-3">
              <form method="post" action="<?= $config['ruta_absoluta'] ?>fichaje/procesar-fichaje.php" class="d-inline w-100">
                <input type="hidden" name="tipo" value="pausa_inicio">
                <button type="submit" class="btn btn-info d-md-inline-block rounded-pill shadow-sm block-card bg-info-subtle text-info w-100 mb-2">
                  <i class="bi bi-cup"></i> Pausar
                </button>
              </form>
            </div>
            <div class="col-6 col-md-3">
              <form method="post" action="<?= $config['ruta_absoluta'] ?>fichaje/procesar-fichaje.php" class="d-inline w-100">
                <input type="hidden" name="tipo" value="pausa_fin">
                <button type="submit" class="btn btn-primary d-md-inline-block rounded-pill shadow-sm block-card bg-primary-subtle text-primary w-100 mb-2">
                  <i class="bi bi-cup-fill"></i> Reanudar
                </button>
              </form>
            </div>
          </div>
        </div>
      </div>
 
      <!-- Historial del día como tarjetas/cards -->
      <div class="card">
        <div class="card-body p-2">

          <?php
          // Filtrar solo entradas y salidas
          $fichajesFiltrados = array_filter($fichajesHoy, function($f) {
            return $f['tipo'] === 'entrada' || $f['tipo'] === 'salida';
          });
          $diaCorto = [
            'Lunes' => 'Lun',
            'Martes' => 'Mar',
            'Miércoles' => 'Mié',
            'Jueves' => 'Jue',
            'Viernes' => 'Vie',
            'Sábado' => 'Sáb',
            'Domingo' => 'Dom',
          ];
          ?>

          <?php if (empty($fichajesFiltrados)): ?>
            <div class="text-muted text-center py-4">No hay fichajes de entrada/salida hoy.</div>
          <?php else: ?>
            <div class="row g-3">
              <?php foreach ($fichajesFiltrados as $f):
                $tipoData = $tiposFichaje[$f['tipo']] ?? ['icono' => 'circle', 'texto' => ucfirst($f['tipo'])];
                $badgeClass = $f['tipo']==='entrada' ? 'bg-success' : 'bg-danger';
                $diaSemana = traducirFecha($f['hora']);
                $diaLargo = explode(' ', $diaSemana)[0];
                $dia = $diaCorto[$diaLargo] ?? $diaLargo;
              ?>
              <div class="col-12 col-lg-6 col-xl-4">
                <div class="fichaje-mini-card card shadow-sm border-0 h-100">
                  <div class="card-body py-3 px-4 d-flex flex-column gap-2">
                    <div class="d-flex justify-content-between align-items-center">
                      <div class="fw-bold text-primary" style="font-size:1.1em;"><?= $dia ?></div>
                      <span class="badge <?= $badgeClass ?> px-3 py-2 d-flex align-items-center gap-2" >
                        <i class="bi bi-<?= $tipoData['icono'] ?>"></i> <?= $tipoData['texto'] ?>
                      </span>
                    </div>
                    <div><i class="bi bi-calendar me-1 text-info"></i> <b>Fecha:</b> <?= date('d/m/Y', strtotime($f['hora'])) ?></div>
                    <div><i class="bi bi-clock me-1 text-primary"></i> <b>Hora:</b> <?= date('H:i:s', strtotime($f['hora'])) ?></div>
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <i class="bi bi-geo-alt me-1 text-secondary"></i> 
                            <b>Ubicación:</b> 
                            <?php if ($f['latitud'] && $f['longitud']): ?>
                                <?= number_format($f['latitud'], 6) ?>, <?= number_format($f['longitud'], 6) ?>
                                <?php if ($f['precision_gps']): ?>
                                    <small class="text-muted">(±<?= $f['precision_gps'] ?>m)</small>
                                <?php endif; ?>
                            <?php else: ?>
                                <span class="text-muted">---</span>
                            <?php endif; ?>
                        </div>
                        <?php if ($f['latitud'] && $f['longitud']): ?>
                            <a href="https://www.google.com/maps?q=<?= $f['latitud'] ?>,<?= $f['longitud'] ?>" 
                               target="_blank" 
                               class="btn btn-sm btn-outline-primary ms-2"
                               title="Ver en Google Maps">
                                <i class="bi bi-eye"></i>
                            </a>
                        <?php endif; ?>
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

<script src="<?= appendCacheBuster($config['ASSET_URL'] . 'js/fichaje-cronometro.js') ?>"></script>

