<?php
require_once __DIR__ . '/../../includes/init.php';
requireAdmin(); // Solo admins pueden gestionar permisos

$empleadoId = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$empleadoId) {
    header('Location: ' . $config['ruta_absoluta'] . 'admin/empleados');
    exit;
}

// Obtener datos del empleado
$stmt = $pdo->prepare("SELECT * FROM empleados WHERE id = ?");
$stmt->execute([$empleadoId]);
$empleado = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$empleado) {
    header('Location: ' . $config['ruta_absoluta'] . 'admin/empleados');
    exit;
}

// Obtener permisos actuales
$permisos = getPermisosEmpleado($empleadoId);

// Si no existen permisos, crear por defecto
if (!$permisos) {
    $pdo->prepare("INSERT INTO permisos_empleados (empleado_id) VALUES (?)")->execute([$empleadoId]);
    $permisos = getPermisosEmpleado($empleadoId);
}

// Procesar formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $campos = [
        'puede_ver_dashboard',
        'puede_fichaje',
        'puede_crear_solicitudes',
        'puede_ver_informes',
        'puede_aprobar_solicitudes',
        'puede_rechazar_solicitudes',
        'puede_editar_solicitudes',
        'puede_gestionar_empleados',
        'puede_ver_empleados',
        'puede_editar_horarios',
        'puede_crear_contenido',
        'puede_ver_fichajes_otros'
    ];
    
    $valores = [];
    foreach ($campos as $campo) {
        $valores[$campo] = isset($_POST[$campo]) ? 1 : 0;
    }
    
    // Actualizar permisos
    $sql = "UPDATE permisos_empleados SET " . 
           implode(' = ?, ', $campos) . " = ? WHERE empleado_id = ?";
    
    $params = array_values($valores);
    $params[] = $empleadoId;
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    
    header('Location: ' . $config['ruta_absoluta'] . 'admin/editar-permisos?id=' . $empleadoId . '&success=1');
    exit;
}

$fullName = $empleado['nombre'] . ' ' . $empleado['apellidos'];
?>

<div class="mb-3 overflow-hidden position-relative">
  <div class="px-3">
    <h4 class="fs-6 mb-0 mt-2">Gestionar Permisos</h4>
    <nav aria-label="breadcrumb">
      <ol class="breadcrumb mb-0">
        <li class="breadcrumb-item">
          <a href="<?= $config['ruta_absoluta'] ?>dashboard">Inicio</a>
        </li>
        <li class="breadcrumb-item">
          <a href="<?= $config['ruta_absoluta'] ?>admin/empleados">Empleados</a>
        </li>
        <li class="breadcrumb-item">
          <a href="<?= $config['ruta_absoluta'] ?>admin/ver-empleado?id=<?= $empleadoId ?>"><?= htmlspecialchars($fullName) ?></a>
        </li>
        <li class="breadcrumb-item active" aria-current="page">Permisos</li>
      </ol>
    </nav>
  </div>
</div>

<div class="container-fluid py-4">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            
            <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="bi bi-check-circle me-2"></i>
                <strong>¡Éxito!</strong> Los permisos se han actualizado correctamente.
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php endif; ?>

            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">
                        <i class="bi bi-shield-lock me-2"></i>
                        Permisos de <?= htmlspecialchars($fullName) ?>
                    </h5>
                    <small>Rol: <span class="badge bg-light text-dark"><?= htmlspecialchars($empleado['rol']) ?></span></small>
                </div>

                <div class="card-body">
                    <form method="POST">
                        
                        <!-- Permisos Básicos -->
                        <div class="mb-4">
                            <h6 class="border-bottom pb-2 mb-3">
                                <i class="bi bi-person-check text-primary me-2"></i>
                                Permisos Básicos
                            </h6>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" name="puede_ver_dashboard" 
                                               id="puede_ver_dashboard" <?= $permisos['puede_ver_dashboard'] ? 'checked' : '' ?>>
                                        <label class="form-check-label" for="puede_ver_dashboard">
                                            <strong>Ver Dashboard</strong>
                                            <br><small class="text-muted">Acceso al panel principal</small>
                                        </label>
                                    </div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" name="puede_fichaje" 
                                               id="puede_fichaje" <?= $permisos['puede_fichaje'] ? 'checked' : '' ?>>
                                        <label class="form-check-label" for="puede_fichaje">
                                            <strong>Fichar</strong>
                                            <br><small class="text-muted">Registrar entradas y salidas</small>
                                        </label>
                                    </div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" name="puede_crear_solicitudes" 
                                               id="puede_crear_solicitudes" <?= $permisos['puede_crear_solicitudes'] ? 'checked' : '' ?>>
                                        <label class="form-check-label" for="puede_crear_solicitudes">
                                            <strong>Crear Solicitudes</strong>
                                            <br><small class="text-muted">Solicitar vacaciones, permisos, etc.</small>
                                        </label>
                                    </div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" name="puede_ver_informes" 
                                               id="puede_ver_informes" <?= $permisos['puede_ver_informes'] ? 'checked' : '' ?>>
                                        <label class="form-check-label" for="puede_ver_informes">
                                            <strong>Ver Informes</strong>
                                            <br><small class="text-muted">Acceso a informes personales</small>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Permisos de Gestión -->
                        <div class="mb-4">
                            <h6 class="border-bottom pb-2 mb-3">
                                <i class="bi bi-briefcase text-warning me-2"></i>
                                Permisos de Gestión (Supervisor)
                            </h6>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" name="puede_aprobar_solicitudes" 
                                               id="puede_aprobar_solicitudes" <?= $permisos['puede_aprobar_solicitudes'] ? 'checked' : '' ?>>
                                        <label class="form-check-label" for="puede_aprobar_solicitudes">
                                            <strong>Aprobar Solicitudes</strong>
                                            <br><small class="text-muted">Aprobar vacaciones y permisos</small>
                                        </label>
                                    </div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" name="puede_rechazar_solicitudes" 
                                               id="puede_rechazar_solicitudes" <?= $permisos['puede_rechazar_solicitudes'] ? 'checked' : '' ?>>
                                        <label class="form-check-label" for="puede_rechazar_solicitudes">
                                            <strong>Rechazar Solicitudes</strong>
                                            <br><small class="text-muted">Denegar vacaciones y permisos</small>
                                        </label>
                                    </div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" name="puede_editar_solicitudes" 
                                               id="puede_editar_solicitudes" <?= $permisos['puede_editar_solicitudes'] ? 'checked' : '' ?>>
                                        <label class="form-check-label" for="puede_editar_solicitudes">
                                            <strong>Editar Solicitudes</strong>
                                            <br><small class="text-muted">Modificar solicitudes de otros (se registra en log)</small>
                                        </label>
                                    </div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" name="puede_gestionar_empleados" 
                                               id="puede_gestionar_empleados" <?= $permisos['puede_gestionar_empleados'] ? 'checked' : '' ?>>
                                        <label class="form-check-label" for="puede_gestionar_empleados">
                                            <strong>Gestionar Empleados</strong>
                                            <br><small class="text-muted">Crear, editar empleados (excepto admin)</small>
                                        </label>
                                    </div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" name="puede_ver_empleados" 
                                               id="puede_ver_empleados" <?= $permisos['puede_ver_empleados'] ? 'checked' : '' ?>>
                                        <label class="form-check-label" for="puede_ver_empleados">
                                            <strong>Ver Empleados</strong>
                                            <br><small class="text-muted">Acceso a lista de empleados</small>
                                        </label>
                                    </div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" name="puede_editar_horarios" 
                                               id="puede_editar_horarios" <?= $permisos['puede_editar_horarios'] ? 'checked' : '' ?>>
                                        <label class="form-check-label" for="puede_editar_horarios">
                                            <strong>Editar Horarios</strong>
                                            <br><small class="text-muted">Modificar horarios de empleados</small>
                                        </label>
                                    </div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" name="puede_crear_contenido" 
                                               id="puede_crear_contenido" <?= $permisos['puede_crear_contenido'] ? 'checked' : '' ?>>
                                        <label class="form-check-label" for="puede_crear_contenido">
                                            <strong>Crear Contenido</strong>
                                            <br><small class="text-muted">Crear notificaciones y contenido</small>
                                        </label>
                                    </div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" name="puede_ver_fichajes_otros" 
                                               id="puede_ver_fichajes_otros" <?= $permisos['puede_ver_fichajes_otros'] ? 'checked' : '' ?>>
                                        <label class="form-check-label" for="puede_ver_fichajes_otros">
                                            <strong>Ver Fichajes de Otros</strong>
                                            <br><small class="text-muted">Ver fichajes de otros empleados</small>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Botones -->
                        <div class="d-flex justify-content-between mt-4">
                            <a href="<?= $config['ruta_absoluta'] ?>admin/ver-empleado?id=<?= $empleadoId ?>" class="btn btn-secondary">
                                <i class="bi bi-arrow-left me-1"></i> Volver
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-save me-1"></i> Guardar Permisos
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.form-check-input:checked {
    background-color: #0d6efd;
    border-color: #0d6efd;
}
.form-check-label {
    cursor: pointer;
}
</style>
