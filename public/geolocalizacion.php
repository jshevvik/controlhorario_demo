<?php
/**
 * Geolocalización y Fichaje
 * Página que se incluye en el sistema de plantillas de index.php
 */

// Obtener datos del empleado actual (ya disponible desde init.php)
$empleadoId = (int) $_SESSION['empleado_id'];

// Verificar que tenemos los datos del empleado
if (!$emp) {
    echo '<div class="alert alert-danger">Error: Datos de empleado no encontrados.</div>';
    return;
}

$geoConfig     = obtenerGeoConfigEmpleado($empleadoId);
$historial     = obtenerHistorialFichajes($empleadoId, 15);
$ultimoFichaje = getUltimoFichajeHoy($empleadoId);
?>

<!-- Breadcrumb -->
<div class="mb-3 overflow-hidden position-relative">
  <div class="px-3">
    <h4 class="fs-6 mb-0">Geolocalización</h4>
    <nav aria-label="breadcrumb">
      <ol class="breadcrumb mb-0">
        <li class="breadcrumb-item">
          <a href="<?= $config['ruta_absoluta'] ?>dashboard">Inicio</a>
        </li>
        <li class="breadcrumb-item active" aria-current="page">Geolocalización</li>
      </ol>
    </nav>
  </div>
</div>

<!-- Header de página -->
<div class="container-fluid py-4 px-1">
<div class="row mb-4">
    <div class="col-12">
        <div class="d-flex align-items-center justify-content-between flex-wrap">
            <div class="mb-2 mb-md-0">
                <p class="text-muted mb-0">
                    <span class="status-indicator status-online"></span>
                    Sistema activo - <?= date('d/m/Y H:i') ?>
                </p>
            </div>
            <button class="btn btn-outline-primary btn-sm" onclick="location.reload()">
                <i class="bi bi-arrow-clockwise d-sm-inline me-1"></i>
                <span class="d-none d-sm-inline">Actualizar</span>
            </button>
        </div>
    </div>
</div>

<div class="row g-4">
    <!-- Primera fila: Estado de Ubicación + Información de Oficina -->
    <div class="col-12 col-lg-6">
        <!-- Estado de Ubicación -->
        <div class="card geo-card h-100">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">
                    <i class="bi bi-crosshair me-2"></i>
                    Estado de Ubicación
                </h5>
            </div>
            <div class="card-body" id="panelUbicacion">
                <div class="text-center py-5">
                    <div class="spinner-border text-primary loading-spinner mb-3" role="status">
                        <span class="visually-hidden">Cargando...</span>
                    </div>
                    <h6 class="text-muted">Obteniendo ubicación...</h6>
                    <p class="small text-muted mb-0">
                        Asegúrate de permitir el acceso a la ubicación cuando te lo solicite el navegador
                    </p>
                </div>
            </div>
        </div>
    </div>

    <div class="col-12 col-lg-6">
        <!-- Información de Oficina -->
        <div class="card geo-card h-100">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">
                    <i class="bi bi-building me-2"></i>
                    Información de Oficina
                </h5>
            </div>
            <div class="card-body">
                <h5 class="fw-bold text-primary mb-3">
                    <?= htmlspecialchars($geoConfig['nombre_ubicacion']) ?>
                </h5>
                <div class="mb-3">
                    <div class="info-metric">
                        <div class="metric-icon text-primary">
                            <i class="bi bi-geo"></i>
                        </div>
                        <div class="small text-muted">Dirección</div>
                        <div class="fw-bold small" id="direccionOficina">
                            <div class="spinner-border spinner-border-sm me-2" role="status">
                                <span class="visually-hidden">Cargando...</span>
                            </div>
                            Obteniendo dirección...
                        </div>
                    </div>
                </div>
                <div class="row g-2 mb-2">
                    <div class="col-6">
                        <div class="info-metric">
                            <div class="metric-icon text-info">
                                <i class="bi bi-geo-alt"></i>
                            </div>
                            <div class="small text-muted">Latitud</div>
                            <div class="fw-bold small"><?= number_format($geoConfig['latitud_oficina'], 6) ?></div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="info-metric">
                            <div class="metric-icon text-warning">
                                <i class="bi bi-geo"></i>
                            </div>
                            <div class="small text-muted">Longitud</div>
                            <div class="fw-bold small"><?= number_format($geoConfig['longitud_oficina'], 6) ?></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-4 mt-1">
    <!-- Segunda fila: Mapa + Historial -->
    <div class="col-12 col-lg-8">
        <!-- Mapa -->
        <div class="card geo-card h-100">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">
                    <i class="bi bi-map me-2"></i>
                    Mapa
                </h5>
            </div>
            <div class="card-body p-0">
                <div id="mapa" style="height: 400px; width: 100%;"></div>
            </div>
        </div>
    </div>

    <div class="col-12 col-lg-4">
        <!-- Historial -->
        <div class="card geo-card h-100">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">
                    <i class="bi bi-clock-history me-2"></i>
                    Historial Reciente
                </h5>
            </div>
            <div class="card-body p-0">
                <div style="height: 400px; overflow-y: auto;">
                    <?php if (empty($historial)): ?>
                        <div class="text-center text-muted py-4">
                            <i class="bi bi-inbox fs-1 mb-2"></i>
                            <p class="mb-0 small">Sin registros de geolocalización</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($historial as $item): ?>
                            <div class="historial-item p-3 border-bottom">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div class="flex-grow-1">
                                        <span class="badge bg-<?= $item['tipo'] === 'entrada' ? 'success' : 'danger' ?> mb-2">
                                            <i class="bi bi-<?= $item['tipo'] === 'entrada' ? 'box-arrow-in-right' : 'box-arrow-right' ?> me-1"></i>
                                            <?= ucfirst($item['tipo']) ?>
                                        </span>
                                        <div class="small text-muted mb-1">
                                            <i class="bi bi-calendar me-1"></i>
                                            <span class="d-inline d-sm-none"><?= date('d/m H:i', strtotime($item['hora'])) ?></span>
                                            <span class="d-none d-sm-inline"><?= $item['fecha_formato'] ?></span>
                                        </div>
                                        <div class="small text-muted text-truncate" title="<?= number_format($item['latitud'], 6) ?>, <?= number_format($item['longitud'], 6) ?>">
                                            <i class="bi bi-geo-alt me-1"></i>
                                            <span class="d-inline d-sm-none"><?= number_format($item['latitud'], 4) ?>, <?= number_format($item['longitud'], 4) ?></span>
                                            <span class="d-none d-sm-inline"><?= number_format($item['latitud'], 6) ?>, <?= number_format($item['longitud'], 6) ?></span>
                                        </div>
                                    </div>
                                    <a href="https://www.google.com/maps?q=<?= $item['latitud'] ?>,<?= $item['longitud'] ?>" 
                                       target="_blank" 
                                       class="btn btn-sm btn-outline-primary ms-2 shrink-0"
                                       title="Ver en Google Maps">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
</div>

<!-- Leaflet JS -->
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" 
        integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" 
        crossorigin=""></script>

<!-- Configuración para JavaScript -->
<script>
    window.GEO_CONFIG = <?= json_encode([
        'oficina' => [
            'lat' => (float)$geoConfig['latitud_oficina'],
            'lng' => (float)$geoConfig['longitud_oficina'],
            'nombre' => $geoConfig['nombre_ubicacion']
        ]
    ], JSON_NUMERIC_CHECK | JSON_UNESCAPED_UNICODE) ?>;

    // Variables PHP para JavaScript
    var empleadoId = <?= json_encode($empleadoId) ?>;
    var emp = <?= json_encode($emp) ?>;
    var geoConfig = <?= json_encode($geoConfig) ?>;
    var ultimoFichaje = <?= json_encode($ultimoFichaje) ?>;
    var config = <?= json_encode($config) ?>;
</script>

<!-- Script de geolocalización -->
<script src="<?= $config['ruta_absoluta'] ?>assets/js/geolocalizacion.js"></script>
