<?php
//require_once __DIR__ . '/../includes/init.php';

if (!isset($_SESSION['empleado_id'])) {
    header('Location: ' . $config['ruta_absoluta'] . 'login');
    exit;
}


$empleadoId = getEmpleadoId();
$empleado   = getEmpleado();

// Filtros de período 
$periodoSeleccionado = $_POST['periodo']       ?? $_GET['periodo']       ?? 'mes_actual';
$fechaInicioCustom   = $_POST['fecha_inicio']  ?? $_GET['fecha_inicio']  ?? '';
$fechaFinCustom      = $_POST['fecha_fin']     ?? $_GET['fecha_fin']     ?? '';

[$fechaInicio, $fechaFin] = rangoFechasDesdePeriodo($periodoSeleccionado, $fechaInicioCustom, $fechaFinCustom);

// Datos desde funciones centralizadas
$resumenDiario = obtenerResumenDiarioFichajes((int)$empleadoId, $fechaInicio, $fechaFin);
$descansosRaw  = obtenerDescansosRawPorDia((int)$empleadoId, $fechaInicio, $fechaFin);

// Construir detalle y métricas
[$fichajesDetalle, $totalDias, $totalHorasDec, $horasEntradaArr, $horasSalidaArr, $horasExtrasDec]
    = construirFichajesDetalle($resumenDiario, $descansosRaw);

// Solicitudes agregadas
$solRows = obtenerConteoSolicitudesPorEstadoTipo((int)$empleadoId, $fechaInicio, $fechaFin);

// Agregar contadores
$totalSolicitudes = 0;
$aprobadas = $pendientes = $rechazadas = 0;
$vacaciones = $bajasMedicas = $ausencias = 0;

foreach ($solRows as $r) {
    $totalSolicitudes += (int)$r['total'];
    $estado = strtolower((string)$r['estado']);
    $tipo   = strtolower((string)$r['tipo']);

    if (in_array($estado, ['aprobada','aprobado'], true)) $aprobadas += (int)$r['total'];
    elseif ($estado === 'pendiente')                       $pendientes += (int)$r['total'];
    elseif (in_array($estado, ['rechazada','rechazado'], true)) $rechazadas += (int)$r['total'];

    if (in_array($estado, ['aprobada','aprobado'], true)) {
        // Para vacaciones, bajas y ausencias, sumar días totales, no cantidad de solicitudes
        if (in_array($tipo, ['vacaciones','vacacion'], true)) {
            $vacaciones += (int)($r['dias_totales'] ?? 0);
        }
        elseif (in_array($tipo, ['baja medica','baja_medica','enfermedad'], true)) {
            $bajasMedicas += (int)($r['dias_totales'] ?? 0);
        }
        elseif (in_array($tipo, ['ausencias','ausencia'], true)) {
            $ausencias += (int)($r['dias_totales'] ?? 0);
        }
    }
}

// Stats para la vista
$stats = [
    'dias_trabajados'     => $totalDias,
    'total_horas'         => round($totalHorasDec, 1),
    'promedio_horas_dia'  => $totalDias > 0 ? round($totalHorasDec / $totalDias, 1) : 0,
    'promedio_entrada'    => calcularPromedioHoraSimple($horasEntradaArr),
    'promedio_salida'     => calcularPromedioHoraSimple($horasSalidaArr),
    'total_solicitudes'   => $totalSolicitudes,
    'aprobadas'           => $aprobadas,
    'pendientes'          => $pendientes,
    'rechazadas'          => $rechazadas,
    'dias_vacaciones'     => $vacaciones,
    'horas_extras'        => round($horasExtrasDec, 1),
    'tasa_aprobacion'     => $totalSolicitudes > 0 ? round(($aprobadas / $totalSolicitudes) * 100) : 0,
    'bajas_medicas'       => $bajasMedicas,
    'ausencias'           => $ausencias,
];

// Métricas auxiliares del período
$diasPeriodo   = (new DateTime($fechaInicio))->diff(new DateTime($fechaFin))->days + 1;
// Aproximación: quitar fines de semana
$diasLaborales = max(1, $diasPeriodo - (int)ceil($diasPeriodo * 2 / 7));
$porcentajeDiasTrabajados = $totalDias > 0 ? (int)round(($totalDias / $diasLaborales) * 100) : 0;
$horasObjetivo  = $diasLaborales * 8; // objetivo 8h/día
$porcentajeHoras = $horasObjetivo > 0 ? (int)round(($totalHorasDec / $horasObjetivo) * 100) : 0;

?>

<div class="container-fluid page-informes">
    <!-- Breadcrumb -->
    <div class="row">
        <div class="col-12">
            <nav aria-label="breadcrumb" class="mb-4">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item">
                        <a href="<?= $config['ruta_absoluta'] ?>dashboard" class="text-decoration-none">
                            Inicio
                        </a>
                    </li>
                    <li class="breadcrumb-item active" aria-current="page">
                        Informes y Estadísticas
                    </li>
                </ol>
            </nav>
        </div>
    </div>

    <!-- Header con filtros -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-3">
                        <div class="mb-3 mb-md-0">
                            <h3 class="mb-1 fw-bold text-dark">Estadísticas</h3>
                            <p class="text-muted mb-0">Período: <?= date('d/m/Y', strtotime($fechaInicio)) ?> - <?= date('d/m/Y', strtotime($fechaFin)) ?></p>
                            
                        </div>
                        <button onclick="generarPDF()" class="btn btn-sm btn-outline-primary w-100" style="max-width: 150px;">
                            <i class="bi bi-file-earmark-pdf me-2"></i>Exportar PDF
                        </button>
                    </div>
                    
                    <!-- Filtros de período -->
                    <form method="POST" class="row g-3" id="filtroForm">
                        <div class="col-12 col-md-4">
                            <label class="form-label fw-semibold">Período</label>
                            <select name="periodo" class="form-select" id="periodoSelect">
                                <option value="hoy" <?= $periodoSeleccionado === 'hoy' ? 'selected' : '' ?>>Hoy</option>
                                <option value="semana_actual" <?= $periodoSeleccionado === 'semana_actual' ? 'selected' : '' ?>>Semana Actual</option>
                                <option value="mes_actual" <?= $periodoSeleccionado === 'mes_actual' ? 'selected' : '' ?>>Mes Actual</option>
                                <option value="trimestre_actual" <?= $periodoSeleccionado === 'trimestre_actual' ? 'selected' : '' ?>>Trimestre Actual</option>
                                <option value="año_actual" <?= $periodoSeleccionado === 'año_actual' ? 'selected' : '' ?>>Año Actual</option>
                                <option value="personalizado" <?= $periodoSeleccionado === 'personalizado' ? 'selected' : '' ?>>Personalizado</option>
                            </select>
                        </div>
                        
                        <div class="col-12 col-md-3" id="fechaInicio" style="display: <?= $periodoSeleccionado === 'personalizado' ? 'block' : 'none' ?>;">
                            <label class="form-label fw-semibold">Fecha Inicio</label>
                            <input type="date" name="fecha_inicio" class="form-control" value="<?= $fechaInicioCustom ?>">
                        </div>
                        
                        <div class="col-12 col-md-3" id="fechaFin" style="display: <?= $periodoSeleccionado === 'personalizado' ? 'block' : 'none' ?>;">
                            <label class="form-label fw-semibold">Fecha Fin</label>
                            <input type="date" name="fecha_fin" class="form-control" value="<?= $fechaFinCustom ?>">
                        </div>
                        
                        <div class="col-12 col-md-2 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="bi bi-funnel me-2 d-none d-md-inline"></i>Filtrar
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Métricas principales estilo infográfico -->
    <div class="row g-3 g-md-4 mb-4 mb-md-5">
        <!-- Días trabajados -->
        <div class="col-6 col-md-6 col-lg-3">
            <div class="text-center p-3 p-md-4 bg-white rounded-3 rounded-md-4 shadow-sm border-0 h-100">
                <div class="mb-2 mb-md-3">
                    <div class="d-inline-flex align-items-center justify-content-center rounded-circle bg-primary-subtle bg-opacity-10" style="width: 60px; height: 60px;">
                        <i class="bi bi-calendar-check text-primary" style="font-size: 1.5rem;"></i>
                    </div>
                </div>
                <div class="display-4 fw-bold text-primary mb-1 mb-md-2"><?= $stats['dias_trabajados'] ?></div>
                <h6 class="text-muted mb-2 mb-md-3 fw-semibold">Días Trabajados</h6>
                <div class="progress mx-auto mb-1 mb-md-2" style="width: 80%; height: 4px;">
                    <div class="progress-bar text-bg-primary rounded-pill" style="width: <?= $porcentajeDiasTrabajados ?>%"></div>
                </div>
                <small class="text-muted"><?= $porcentajeDiasTrabajados ?>% del período</small>
            </div>
        </div>

        <!-- Horas totales -->
        <div class="col-6 col-md-6 col-lg-3">
            <div class="text-center p-3 p-md-4 bg-white rounded-3 rounded-md-4 shadow-sm border-0 h-100">
                <div class="mb-2 mb-md-3">
                    <div class="d-inline-flex align-items-center justify-content-center rounded-circle bg-success-subtle bg-opacity-10" style="width: 60px; height: 60px;">
                        <i class="bi bi-clock text-success" style="font-size: 1.5rem;"></i>
                    </div>
                </div>
                <div class="display-4 fw-bold text-success mb-1 mb-md-2"><?= number_format($stats['total_horas'], 1) ?></div>
                <h6 class="text-muted mb-2 mb-md-3 fw-semibold">Horas Totales</h6>
                <div class="progress mx-auto mb-1 mb-md-2" style="width: 80%; height: 4px;">
                    <div class="progress-bar text-bg-success rounded-pill" style="width: <?= min($porcentajeHoras, 100) ?>%"></div>
                </div>
                <small class="text-muted"><?= min($porcentajeHoras, 100) ?>% del objetivo</small>
            </div>
        </div>

        <!-- Solicitudes totales -->
        <div class="col-6 col-md-6 col-lg-3">
            <div class="text-center p-3 p-md-4 bg-white rounded-3 rounded-md-4 shadow-sm border-0 h-100">
                <div class="mb-2 mb-md-3">
                    <div class="d-inline-flex align-items-center justify-content-center rounded-circle bg-danger-subtle bg-opacity-10" style="width: 60px; height: 60px;">
                        <i class="bi bi-file-earmark-text text-danger" style="font-size: 1.5rem;"></i>
                    </div>
                </div>
                <div class="display-4 fw-bold text-danger mb-1 mb-md-2"><?= $stats['total_solicitudes'] ?></div>
                <h6 class="text-muted mb-2 mb-md-3 fw-semibold">Solicitudes Totales</h6>
                <div class="progress mx-auto mb-1 mb-md-2" style="width: 80%; height: 4px;">
                    <div class="progress-bar text-bg-danger rounded-pill" style="width: <?= $stats['tasa_aprobacion'] ?>%"></div>
                </div>
                <small class="text-muted"><?= $stats['tasa_aprobacion'] ?>% aprobadas</small>
            </div>
        </div>

        <!-- Promedio diario -->
        <div class="col-6 col-md-6 col-lg-3">
            <div class="text-center p-3 p-md-4 bg-white rounded-3 rounded-md-4 shadow-sm border-0 h-100">
                <div class="mb-2 mb-md-3">
                    <div class="d-inline-flex align-items-center justify-content-center rounded-circle bg-warning-subtle bg-opacity-10" style="width: 60px; height: 60px;">
                        <i class="bi bi-graph-up text-warning" style="font-size: 1.5rem;"></i>
                    </div>
                </div>
                <div class="display-4 fw-bold text-warning mb-1 mb-md-2"><?= number_format($stats['promedio_horas_dia'], 1) ?></div>
                <h6 class="text-muted mb-2 mb-md-3 fw-semibold">Promedio Diario</h6>
                <div class="progress mx-auto mb-1 mb-md-2" style="width: 80%; height: 4px;">
                    <div class="progress-bar text-bg-warning rounded-pill" style="width: <?= min(($stats['promedio_horas_dia'] / 8) * 100, 100) ?>%"></div>
                </div>
                <small class="text-muted">de 8h objetivo</small>
            </div>
        </div>
    </div>

    <!-- Métricas secundarias -->
    <div class="row g-3 g-md-4 mb-3 mb-md-4">
        <div class="col-12 col-lg-8">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-0 pb-0">
                    <h5 class="fw-bold text-dark mb-0">Estadísticas Detalladas</h5>
                </div>
                <div class="card-body">
                    <div class="row g-2 g-md-3">
                        <div class="col-12 col-sm-6 col-md-6">
                            <div class="d-flex align-items-center p-2 p-md-3 text-bg-light rounded">
                                <div class="shrink-0">
                                    <i class="bi bi-door-open text-warning fs-3"></i>
                                </div>
                                <div class="flex-grow-1 ms-2 ms-md-3">
                                    <div class="fw-bold text-dark"><?= $stats['promedio_entrada'] ?></div>
                                    <small class="text-muted">Entrada Promedio</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-12 col-sm-6 col-md-6">
                            <div class="d-flex align-items-center p-2 p-md-3 text-bg-light rounded">
                                <div class="shrink-0">
                                    <i class="bi bi-door-closed text-danger fs-3"></i>
                                </div>
                                <div class="flex-grow-1 ms-2 ms-md-3">
                                    <div class="fw-bold text-dark"><?= $stats['promedio_salida'] ?></div>
                                    <small class="text-muted">Salida Promedio</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-12 col-sm-6 col-md-6">
                            <div class="d-flex align-items-center p-2 p-md-3 text-bg-light rounded">
                                <div class="shrink-0">
                                    <i class="bi bi-calendar-plus text-success fs-3"></i>
                                </div>
                                <div class="flex-grow-1 ms-2 ms-md-3">
                                    <div class="fw-bold text-dark"><?= $stats['horas_extras'] ?>h</div>
                                    <small class="text-muted">Horas Extras</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-12 col-sm-6 col-md-6">
                            <div class="d-flex align-items-center p-2 p-md-3 text-bg-light rounded">
                                <div class="shrink-0">
                                    <i class="bi bi-airplane text-primary fs-3"></i>
                                </div>
                                <div class="flex-grow-1 ms-2 ms-md-3">
                                    <div class="fw-bold text-dark"><?= $stats['dias_vacaciones'] ?></div>
                                    <small class="text-muted">Días de Vacaciones</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-12 col-sm-6 col-md-6">
                            <div class="d-flex align-items-center p-2 p-md-3 text-bg-light rounded">
                                <div class="shrink-0">
                                    <i class="bi bi-heart-pulse text-danger fs-3"></i>
                                </div>
                                <div class="flex-grow-1 ms-2 ms-md-3">
                                    <div class="fw-bold text-dark"><?= $stats['bajas_medicas'] ?></div>
                                    <small class="text-muted">Bajas Médicas</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-12 col-sm-6 col-md-6">
                            <div class="d-flex align-items-center p-2 p-md-3 text-bg-light rounded">
                                <div class="shrink-0">
                                    <i class="bi bi-exclamation-circle text-warning fs-3"></i>
                                </div>
                                <div class="flex-grow-1 ms-2 ms-md-3">
                                    <div class="fw-bold text-dark"><?= $stats['ausencias'] ?></div>
                                    <small class="text-muted">Días de Ausencias</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-lg-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-0 pb-0">
                    <h5 class="fw-bold text-dark mb-0">Estado de Solicitudes</h5>
                </div>
                <div class="card-body">
                    <!-- Distribución visual de solicitudes -->
                    <div class="row g-2 mb-3 mb-md-4">
                        <div class="col-6 col-sm-4 col-lg-6">
                            <div class="text-center p-2 p-md-3 rounded-3 bg-primary-subtle text-primary">
                                <div class="h4 fw-bold mb-0"><?= $stats['aprobadas'] ?></div>
                                <small>Aprobadas</small>
                            </div>
                        </div>
                        <div class="col-6 col-sm-4 col-lg-6">
                            <div class="text-center p-2 p-md-3 rounded-3 bg-warning-subtle text-warning">
                                <div class="h4 fw-bold mb-0"><?= $stats['pendientes'] ?></div>
                                <small>Pendientes</small>
                            </div>
                        </div>
                        <div class="col-6 col-sm-4 col-lg-6">
                            <div class="text-center p-2 p-md-3 rounded-3 bg-danger-subtle text-danger">
                                <div class="h4 fw-bold mb-0"><?= $stats['rechazadas'] ?></div>
                                <small>Rechazadas</small>
                            </div>
                        </div>
                        <div class="col-6 col-sm-4 col-lg-6">
                            <div class="text-center p-2 p-md-3 rounded-3 bg-success-subtle text-success">
                                <div class="h4 fw-bold mb-0"><?= $stats['dias_vacaciones'] ?></div>
                                <small>Vacaciones</small>
                            </div>
                        </div>
                        <div class="col-6 col-sm-4 col-lg-6">
                            <div class="text-center p-2 p-md-3 rounded-3 bg-secondary-subtle text-secondary">
                                <div class="h4 fw-bold mb-0"><?= $stats['bajas_medicas'] ?></div>
                                <small>Bajas Médicas</small>
                            </div>
                        </div>
                        <div class="col-6 col-sm-4 col-lg-6">
                            <div class="text-center p-2 p-md-3 rounded-3 bg-warning-subtle text-warning">
                                <div class="h4 fw-bold mb-0"><?= $stats['ausencias'] ?></div>
                                <small>Días de Ausencias</small>
                            </div>
                        </div>
                    </div>

                    <!-- Indicador de rendimiento -->
                    <div class="text-center">
                        <!-- Versión móvil: Barra lineal -->
                        <div class="d-block d-md-none">
                            <div class="mb-2">
                                <span class="fw-bold text-success"><?= $stats['tasa_aprobacion'] ?>% Éxito</span>
                            </div>
                            <div class="progress" style="height: 8px;">
                                <div class="progress-bar bg-success" role="progressbar" 
                                     style="width: <?= $stats['tasa_aprobacion'] ?>%" 
                                     aria-valuenow="<?= $stats['tasa_aprobacion'] ?>" 
                                     aria-valuemin="0" aria-valuemax="100"></div>
                            </div>
                        </div>
                        
                        <!-- Versión desktop: Círculo -->
                        <div class="d-none d-md-block">
                            <div class="position-relative d-inline-block">
                                <svg width="120" height="120">
                                    <circle cx="60" cy="60" r="50" fill="none" stroke="#e9ecef" stroke-width="8"></circle>
                                    <circle cx="60" cy="60" r="50" fill="none" stroke="#28a745" stroke-width="8" 
                                            stroke-dasharray="<?= ($stats['tasa_aprobacion'] * 314) / 100 ?> 314" 
                                            stroke-linecap="round" transform="rotate(-90 60 60)"></circle>
                                </svg>
                                <div class="position-absolute top-50 start-50 translate-middle text-center">
                                    <div class="h5 fw-bold text-success mb-0"><?= $stats['tasa_aprobacion'] ?>%</div>
                                    <small class="text-muted">Éxito</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabla de fichajes detallados -->
    <?php if (!empty($fichajesDetalle)): ?>
    <div class="row">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-0">
                    <h5 class="fw-bold text-dark mb-0">📋 Fichajes Detallados</h5>
                </div>
                <div class="card-body p-2 p-md-3">
                    <!-- Vista móvil: Cards -->
                    <div class="d-block d-md-none">
                        <?php foreach ($fichajesDetalle as $fichaje): ?>
                        <div class="card mb-3 border-0 shadow-sm">
                            <div class="card-body p-3">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <div class="fw-bold text-dark"><?= date('d/m/Y', strtotime($fichaje['fecha'])) ?></div>
                                    <div>
                                        <?php 
                                        switch ($fichaje['estado']) {
                                            case 'Completo':
                                                echo '<span class="badge bg-success">Completo</span>';
                                                break;
                                            case 'Parcial':
                                                echo '<span class="badge bg-warning">Parcial</span>';
                                                break;
                                            case 'Sin fichaje':
                                                echo '<span class="badge bg-info">Sin fichaje</span>';
                                                break;
                                            case 'Sin salida':
                                                echo '<span class="badge bg-danger">Sin salida</span>';
                                                break;
                                            case 'Sin entrada':
                                                echo '<span class="badge bg-danger">Sin entrada</span>';
                                                break;
                                            case 'Fichaje incompleto':
                                                echo '<span class="badge bg-info">Fichaje incompleto</span>';
                                                break;
                                            default:
                                                echo '<span class="badge bg-info">Incompleto</span>';
                                        }
                                        ?>
                                    </div>
                                </div>
                                
                                <div class="row g-2">
                                    <div class="col-6">
                                        <div class="d-flex align-items-center">
                                            <i class="bi bi-box-arrow-in-right text-success me-2"></i>
                                            <div>
                                                <small class="text-muted d-block">Entrada</small>
                                                <span class="badge bg-success"><?= $fichaje['primera_entrada'] ?? '-' ?></span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="d-flex align-items-center">
                                            <i class="bi bi-box-arrow-right text-danger me-2"></i>
                                            <div>
                                                <small class="text-muted d-block">Salida</small>
                                                <span class="badge bg-danger"><?= $fichaje['ultima_salida'] ?? '-' ?></span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="d-flex align-items-center">
                                            <i class="bi bi-clock text-primary me-2"></i>
                                            <div>
                                                <small class="text-muted d-block">Horas</small>
                                                <span class="fw-bold text-primary"><?= $fichaje['total_horas'] ?? '0:00' ?></span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="d-flex align-items-center">
                                            <i class="bi bi-pause-circle text-secondary me-2"></i>
                                            <div>
                                                <small class="text-muted d-block">Descansos</small>
                                                <span class="badge bg-secondary"><?= $fichaje['descansos'] ?? '0:00' ?></span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                        
                        <!-- Paginación móvil -->
                        <nav aria-label="Paginación fichajes móvil" class="mt-3">
                            <ul class="pagination pagination-sm justify-content-center">
                                <li class="page-item disabled">
                                    <span class="page-link">Anterior</span>
                                </li>
                                <li class="page-item active">
                                    <span class="page-link">1</span>
                                </li>
                                <li class="page-item">
                                    <a class="page-link" href="#">2</a>
                                </li>
                                <li class="page-item">
                                    <a class="page-link" href="#">3</a>
                                </li>
                                <li class="page-item">
                                    <a class="page-link" href="#">Siguiente</a>
                                </li>
                            </ul>
                        </nav>
                    </div>
                    
                    <!-- Vista desktop: Tabla -->
                    <div class="d-none d-md-block">
                        <div class="table-responsive">
                            <table class="table table-hover table-sm" id="tablaFichajes">
                                <thead class="table-light">
                                    <tr>
                                        <th class="text-nowrap">Fecha</th>
                                        <th class="text-nowrap">Entrada</th>
                                        <th class="text-nowrap">Salida</th>
                                        <th class="text-nowrap">Descansos</th>
                                        <th class="text-nowrap">Horas</th>
                                        <th class="text-nowrap">Estado</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($fichajesDetalle as $fichaje): ?>
                                    <tr>
                                        <td class="text-nowrap"><?= date('d/m/Y', strtotime($fichaje['fecha'])) ?></td>
                                        <td>
                                            <span class="badge bg-success"><?= $fichaje['primera_entrada'] ?? '-' ?></span>
                                        </td>
                                        <td>
                                            <span class="badge bg-danger"><?= $fichaje['ultima_salida'] ?? '-' ?></span>
                                        </td>
                                        <td>
                                            <span class="badge bg-secondary"><?= $fichaje['descansos'] ?? '0:00' ?></span>
                                        </td>
                                        <td>
                                            <span class="fw-bold text-primary small"><?= $fichaje['total_horas'] ?? '0:00' ?></span>
                                        </td>
                                        <td>
                                            <?php 
                                            switch ($fichaje['estado']) {
                                                case 'Completo':
                                                    echo '<span class="badge bg-success">Completo</span>';
                                                    break;
                                                case 'Parcial':
                                                    echo '<span class="badge bg-warning">Parcial</span>';
                                                    break;
                                                case 'Sin fichaje':
                                                    echo '<span class="badge bg-info">Sin fichaje</span>';
                                                    break;
                                                case 'Sin salida':
                                                    echo '<span class="badge bg-danger">Sin salida</span>';
                                                    break;
                                                case 'Sin entrada':
                                                    echo '<span class="badge bg-danger">Sin entrada</span>';
                                                    break;
                                                case 'Fichaje incompleto':
                                                    echo '<span class="badge bg-info">Fichaje incompleto</span>';
                                                    break;
                                                default:
                                                    echo '<span class="badge bg-info">Incompleto</span>';
                                            }
                                            ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<script>
function generarPDF() {
    window.print();
}

// Filtros interactivos
document.getElementById('periodoSelect').addEventListener('change', function() {
    const fechaInicio = document.getElementById('fechaInicio');
    const fechaFin = document.getElementById('fechaFin');
    
    if (this.value === 'personalizado') {
        fechaInicio.style.display = 'block';
        fechaFin.style.display = 'block';
    } else {
        fechaInicio.style.display = 'none';
        fechaFin.style.display = 'none';
        // Auto-submit para períodos predefinidos
        document.getElementById('filtroForm').submit();
    }
});

// Animaciones de entrada mejoradas
document.addEventListener('DOMContentLoaded', function() {
    // Animar elementos con retraso escalonado
    const animElements = document.querySelectorAll('.card, .rounded-circle');
    animElements.forEach((element, index) => {
        element.style.opacity = '0';
        element.style.transform = 'translateY(30px)';
        
        setTimeout(() => {
            element.style.transition = 'all 0.8s cubic-bezier(0.4, 0, 0.2, 1)';
            element.style.opacity = '1';
            element.style.transform = 'translateY(0)';
        }, index * 150);
    });
    
    // Animar números grandes - corregir selector
    const bigNumbers = document.querySelectorAll('.display-4');
    bigNumbers.forEach(number => {
        const finalValue = parseFloat(number.textContent);
        let currentValue = 0;
        const increment = finalValue / 30;
        
        setTimeout(() => {
            const timer = setInterval(() => {
                currentValue += increment;
                if (currentValue >= finalValue) {
                    currentValue = finalValue;
                    clearInterval(timer);
                }
                number.textContent = finalValue % 1 === 0 ? Math.floor(currentValue) : currentValue.toFixed(1);
            }, 50);
        }, 1000);
    });
    
    // Animar barras de progreso
    setTimeout(() => {
        const progressBars = document.querySelectorAll('.progress-bar');
        progressBars.forEach(bar => {
            const width = bar.style.width;
            bar.style.width = '0%';
            bar.style.transition = 'width 2s ease-in-out';
            setTimeout(() => {
                bar.style.width = width;
            }, 100);
        });
    }, 1500);

    // Inicializar DataTable solo en desktop
    if (document.getElementById('tablaFichajes') && window.innerWidth >= 768) {
        $('#tablaFichajes').DataTable({
            language: {
                url: 'https://cdn.datatables.net/plug-ins/1.11.5/i18n/es-ES.json'
            },
            order: [[0, 'desc']],
            pageLength: 10,
            responsive: true
        });
    }
    
    // Paginación manual para móvil
    setupMobilePagination();
});

// Hover effects para círculos
document.addEventListener('DOMContentLoaded', function() {
    const circles = document.querySelectorAll('.rounded-circle');
    circles.forEach(circle => {
        circle.addEventListener('mouseenter', function() {
            this.style.transform = 'scale(1.1)';
            this.style.transition = 'transform 0.3s ease';
        });
        
        circle.addEventListener('mouseleave', function() {
            this.style.transform = 'scale(1)';
        });
    });
});

// Función para manejar paginación móvil
function setupMobilePagination() {
    const itemsPerPage = 5;
    const mobileCards = document.querySelectorAll('.d-block.d-md-none .card');
    const totalItems = mobileCards.length;
    const totalPages = Math.ceil(totalItems / itemsPerPage);
    let currentPage = 1;
    
    function showPage(page) {
        mobileCards.forEach((card, index) => {
            const startIndex = (page - 1) * itemsPerPage;
            const endIndex = startIndex + itemsPerPage;
            
            if (index >= startIndex && index < endIndex) {
                card.style.display = 'block';
                card.style.animation = 'fadeInUp 0.5s ease forwards';
            } else {
                card.style.display = 'none';
            }
        });
        
        updatePagination(page);
    }
    
    function updatePagination(page) {
        const pagination = document.querySelector('.pagination');
        if (!pagination) return;
        
        pagination.innerHTML = '';
        
        // Botón anterior
        const prevLi = document.createElement('li');
        prevLi.className = `page-item ${page === 1 ? 'disabled' : ''}`;
        prevLi.innerHTML = page === 1 
            ? '<span class="page-link">Anterior</span>'
            : '<a class="page-link" href="#" onclick="changePage(' + (page - 1) + ')">Anterior</a>';
        pagination.appendChild(prevLi);
        
        // Números de página
        for (let i = 1; i <= totalPages; i++) {
            const li = document.createElement('li');
            li.className = `page-item ${i === page ? 'active' : ''}`;
            li.innerHTML = i === page
                ? '<span class="page-link">' + i + '</span>'
                : '<a class="page-link" href="#" onclick="changePage(' + i + ')">' + i + '</a>';
            pagination.appendChild(li);
        }
        
        // Botón siguiente
        const nextLi = document.createElement('li');
        nextLi.className = `page-item ${page === totalPages ? 'disabled' : ''}`;
        nextLi.innerHTML = page === totalPages
            ? '<span class="page-link">Siguiente</span>'
            : '<a class="page-link" href="#" onclick="changePage(' + (page + 1) + ')">Siguiente</a>';
        pagination.appendChild(nextLi);
    }
    
    // Mostrar primera página
    if (totalItems > 0) {
        showPage(1);
    }
    
    // Función global para cambiar página
    window.changePage = function(page) {
        currentPage = page;
        showPage(page);
        
        // Scroll suave a la tabla
        document.querySelector('.d-block.d-md-none').scrollIntoView({
            behavior: 'smooth',
            block: 'start'
        });
    };
}

// Animación CSS para fadeInUp
const style = document.createElement('style');
style.textContent = `
    @keyframes fadeInUp {
        from {
            opacity: 0;
            transform: translateY(30px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    
    /* Mejoras para la vista móvil */
    @media (max-width: 767px) {
        .btn.w-100.w-md-auto {
            font-size: 0.875rem;
            padding: 0.5rem 1rem;
        }
        
        .card-body .row.g-2 {
            margin-top: 0.5rem;
        }
        
        .pagination-sm .page-link {
            padding: 0.25rem 0.5rem;
            font-size: 0.875rem;
        }
    }
`;
document.head.appendChild(style);
</script>
