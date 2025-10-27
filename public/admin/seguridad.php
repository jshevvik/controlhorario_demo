<?php
requireAdmin(); // Solo admins

$pageTitle = 'Seguridad';
$empleados = getTodosLosEmpleados();
?>

<!-- Breadcrumb -->
<div class="mb-3 overflow-hidden position-relative">
  <div class="px-3">
    <h4 class="fs-6 mb-0 mt-2">Seguridad</h4>
    <nav aria-label="breadcrumb">
      <ol class="breadcrumb mb-0">
        <li class="breadcrumb-item">
            <a href="<?= $config['ruta_absoluta'] ?>dashboard">Inicio</a>
        </li>
        <li class="breadcrumb-item">
            <a href="<?= $config['ruta_absoluta'] ?>administracion">Administración</a>
        </li>
        <li class="breadcrumb-item active" aria-current="page">Seguridad</li>
      </ol>
    </nav>
  </div>
</div>

<div class="container-fluid py-3 py-md-4">
  <!-- Header Welcome Card -->
  <div class="row mb-4">
    <div class="col-12">
      <div class="card border-0 shadow-sm" style="border-radius: 15px;">
        <div class="card-body p-3 p-md-4">
          <div class="row align-items-center">
            <div class="col-8 col-md-8">
              <h3 class="fw-bold mb-1 mb-md-2 text-dark fs-5 fs-4-md">Panel de Seguridad</h3>
              <p class="mb-0 text-muted small">Gestiona la seguridad del sistema</p>
            </div>
            <div class="col-4 col-md-4 text-end">
              <div class="d-inline-flex align-items-center justify-content-center rounded-circle bg-primary-subtle" style="width: 50px; height: 50px;">
                <i class="bi bi-shield-check text-primary" style="font-size: 1.5rem;"></i>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Security Stats -->
  <div class="row g-3 g-lg-4 mb-4">
    <div class="col-6 col-lg-3">
      <div class="text-center p-2 p-md-4 bg-white rounded-3 shadow-sm border-0 h-100">
        <div class="mb-2 mb-md-3">
          <div class="d-inline-flex align-items-center justify-content-center rounded-circle bg-primary-subtle" style="width: 40px; height: 40px;">
            <i class="bi bi-people-fill text-primary" style="font-size: 1rem;"></i>
          </div>
        </div>
        <div class="h4 h3-md fw-bold text-primary mb-1 mb-md-2"><?= count($empleados) ?></div>
        <h6 class="text-muted mb-2 mb-md-3 fw-semibold fs-7 fs-6-md">Usuarios Totales</h6>
        <div class="progress mx-auto mb-1 mb-md-2" style="width: 80%; height: 3px;">
          <div class="progress-bar bg-primary rounded-pill" style="width: 100%"></div>
        </div>
        <small class="text-muted d-none d-md-block">Sistema activo</small>
      </div>
    </div>
    
    <div class="col-6 col-lg-3">
      <div class="text-center p-2 p-md-4 bg-white rounded-3 shadow-sm border-0 h-100">
        <div class="mb-2 mb-md-3">
          <div class="d-inline-flex align-items-center justify-content-center rounded-circle bg-success-subtle" style="width: 40px; height: 40px;">
            <i class="bi bi-shield-check text-success" style="font-size: 1rem;"></i>
          </div>
        </div>
        <div class="h4 h3-md fw-bold text-success mb-1 mb-md-2">100%</div>
        <h6 class="text-muted mb-2 mb-md-3 fw-semibold fs-7 fs-6-md">Seguridad Activa</h6>
        <div class="progress mx-auto mb-1 mb-md-2" style="width: 80%; height: 3px;">
          <div class="progress-bar bg-success rounded-pill" style="width: 100%"></div>
        </div>
        <small class="text-muted d-none d-md-block">Sistema protegido</small>
      </div>
    </div>
    
    <div class="col-6 col-lg-3">
      <div class="text-center p-2 p-md-4 bg-white rounded-3 shadow-sm border-0 h-100">
        <div class="mb-2 mb-md-3">
          <div class="d-inline-flex align-items-center justify-content-center rounded-circle bg-warning-subtle" style="width: 40px; height: 40px;">
            <i class="bi bi-person-badge-fill text-warning" style="font-size: 1rem;"></i>
          </div>
        </div>
        <div class="h4 h3-md fw-bold text-warning mb-1 mb-md-2"><?= count(array_filter($empleados, function($emp) { return $emp['es_admin'] == 1; })) ?></div>
        <h6 class="text-muted mb-2 mb-md-3 fw-semibold fs-7 fs-6-md">Administradores</h6>
        <div class="progress mx-auto mb-1 mb-md-2" style="width: 80%; height: 3px;">
          <div class="progress-bar bg-warning rounded-pill" style="width: <?= round((count(array_filter($empleados, function($emp) { return $emp['es_admin'] == 1; })) / count($empleados)) * 100) ?>%"></div>
        </div>
        <small class="text-muted d-none d-md-block"><?= round((count(array_filter($empleados, function($emp) { return $emp['es_admin'] == 1; })) / count($empleados)) * 100) ?>% del total</small>
      </div>
    </div>
    
    <div class="col-6 col-lg-3">
      <div class="text-center p-2 p-md-4 bg-white rounded-3 shadow-sm border-0 h-100">
        <div class="mb-2 mb-md-3">
          <div class="d-inline-flex align-items-center justify-content-center rounded-circle bg-info-subtle" style="width: 40px; height: 40px;">
            <i class="bi bi-people text-info" style="font-size: 1rem;"></i>
          </div>
        </div>
        <div class="h4 h3-md fw-bold text-info mb-1 mb-md-2"><?= count(array_filter($empleados, function($emp) { return $emp['es_admin'] == 0; })) ?></div>
        <h6 class="text-muted mb-2 mb-md-3 fw-semibold fs-7 fs-6-md">Usuarios Estándar</h6>
        <div class="progress mx-auto mb-1 mb-md-2" style="width: 80%; height: 3px;">
          <div class="progress-bar bg-info rounded-pill" style="width: <?= round((count(array_filter($empleados, function($emp) { return $emp['es_admin'] == 0; })) / count($empleados)) * 100) ?>%"></div>
        </div>
        <small class="text-muted d-none d-md-block"><?= round((count(array_filter($empleados, function($emp) { return $emp['es_admin'] == 0; })) / count($empleados)) * 100) ?>% del total</small>
      </div>
    </div>
  </div>


 

  <!-- Logs Section -->
  <!-- COMENTADO: Sección de logs de seguridad
  <div class="row mb-4">
    <div class="col-12">
      <div class="card">
        <div class="card-header d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-2">
          <h5 class="mb-0"><i class="bi bi-list-ul me-2"></i>Registro de Actividad</h5>
          <button class="btn btn-sm btn-outline-primary" onclick="actualizarLogs()">
            <i class="bi bi-arrow-clockwise me-1" id="refreshIcon"></i>
            <span class="d-none d-md-inline">Actualizar</span>
          </button>
        </div>
        <div class="card-body p-0 p-md-3">
          <!- Vista móvil --
          <div class="d-block d-md-none">
            <div class="list-group list-group-flush">
              <div class="list-group-item border-0 py-3">
                <div class="d-flex justify-content-between align-items-start">
                  <div class="grow">
                    <div class="fw-bold text-dark mb-1">Admin Principal</div>
                    <div class="text-muted small mb-2">Cambio de contraseña - Usuario: Juan Pérez</div>
                    <div class="d-flex align-items-center gap-2">
                      <small class="text-muted">2025-01-08 14:30:15</small>
                      <code class="small">192.168.1.100</code>
                    </div>
                  </div>
                  <span class="badge bg-success">Exitoso</span>
                </div>
              </div>
              <div class="list-group-item border-0 py-3">
                <div class="d-flex justify-content-between align-items-start">
                  <div class="grow">
                    <div class="fw-bold text-dark mb-1">Admin Principal</div>
                    <div class="text-muted small mb-2">Acceso al panel de seguridad</div>
                    <div class="d-flex align-items-center gap-2">
                      <small class="text-muted">2025-01-08 14:25:42</small>
                      <code class="small">192.168.1.100</code>
                    </div>
                  </div>
                  <span class="badge bg-success">Exitoso</span>
                </div>
              </div>
              <div class="list-group-item border-0 py-3">
                <div class="d-flex justify-content-between align-items-start">
                  <div class="grow">
                    <div class="fw-bold text-dark mb-1">Sistema</div>
                    <div class="text-muted small mb-2">Verificación de integridad completada</div>
                    <div class="d-flex align-items-center gap-2">
                      <small class="text-muted">2025-01-08 14:20:18</small>
                      <code class="small">localhost</code>
                    </div>
                  </div>
                  <span class="badge bg-info">Info</span>
                </div>
              </div>
              <div class="list-group-item border-0 py-3">
                <div class="d-flex justify-content-between align-items-start">
                  <div class="grow">
                    <div class="fw-bold text-dark mb-1">Admin Principal</div>
                    <div class="text-muted small mb-2">Login exitoso</div>
                    <div class="d-flex align-items-center gap-2">
                      <small class="text-muted">2025-01-08 14:15:03</small>
                      <code class="small">192.168.1.100</code>
                    </div>
                  </div>
                  <span class="badge bg-success">Exitoso</span>
                </div>
              </div>
              <div class="list-group-item border-0 py-3">
                <div class="d-flex justify-content-between align-items-start">
                  <div class="grow">
                    <div class="fw-bold text-dark mb-1">Usuario123</div>
                    <div class="text-muted small mb-2">Intento de acceso denegado - Permisos insuficientes</div>
                    <div class="d-flex align-items-center gap-2">
                      <small class="text-muted">2025-08-14 14:12:55</small>
                      <code class="small">192.168.1.105</code>
                    </div>
                  </div>
                  <span class="badge bg-warning">Advertencia</span>
                </div>
              </div>
            </div>
          </div>
          
          <-- Vista desktop --
          <div class="d-none d-md-block">
            <div class="table-responsive">
              <table class="table table-hover mb-0">
                <thead class="table-light">
                  <tr>
                    <th>Fecha/Hora</th>
                    <th>Usuario</th>
                    <th>Acción</th>
                    <th>IP</th>
                    <th>Estado</th>
                  </tr>
                </thead>
                <tbody>
                  <tr>
                    <td><small class="text-muted">2025-01-08 14:30:15</small></td>
                    <td>Admin Principal</td>
                    <td>Cambio de contraseña - Usuario: Juan Pérez</td>
                    <td><code>192.168.1.100</code></td>
                    <td><span class="badge bg-success">Exitoso</span></td>
                  </tr>
                  <tr>
                    <td><small class="text-muted">2025-01-08 14:25:42</small></td>
                    <td>Admin Principal</td>
                    <td>Acceso al panel de seguridad</td>
                    <td><code>192.168.1.100</code></td>
                    <td><span class="badge bg-success">Exitoso</span></td>
                  </tr>
                  <tr>
                    <td><small class="text-muted">2025-01-08 14:20:18</small></td>
                    <td>Sistema</td>
                    <td>Verificación de integridad completada</td>
                    <td><code>localhost</code></td>
                    <td><span class="badge bg-info">Info</span></td>
                  </tr>
                  <tr>
                    <td><small class="text-muted">2025-01-08 14:15:03</small></td>
                    <td>Admin Principal</td>
                    <td>Login exitoso</td>
                    <td><code>192.168.1.100</code></td>
                    <td><span class="badge bg-success">Exitoso</span></td>
                  </tr>
                  <tr>
                    <td><small class="text-muted">2025-01-08 14:12:55</small></td>
                    <td>Usuario123</td>
                    <td>Intento de acceso denegado - Permisos insuficientes</td>
                    <td><code>192.168.1.105</code></td>
                    <td><span class="badge bg-warning">Advertencia</span></td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
          
          <-- Paginación responsive --
          <div class="d-flex flex-column flex-md-row justify-content-between align-items-center mt-3 px-3">
            <small class="text-muted mb-2 mb-md-0" id="recordsInfo">Mostrando 5 de 127 registros</small>
            <nav>
              <ul class="pagination pagination-sm mb-0">
                <li class="page-item" id="prevPage">
                  <a class="page-link" href="#" onclick="changePage(currentPage - 1)">Anterior</a>
                </li>
                <li class="page-item active" id="page1">
                  <a class="page-link" href="#" onclick="changePage(1)">1</a>
                </li>
                <li class="page-item" id="page2">
                  <a class="page-link" href="#" onclick="changePage(2)">2</a>
                </li>
                <li class="page-item" id="page3">
                  <a class="page-link" href="#" onclick="changePage(3)">3</a>
                </li>
                <li class="page-item" id="nextPage">
                  <a class="page-link" href="#" onclick="changePage(currentPage + 1)">Siguiente</a>
                </li>
              </ul>
            </nav>
          </div>
        </div>
      </div>
    </div>
  </div>
  -->

  <!-- Main Content Area -->
  <div class="row g-3 g-lg-4">
    <!-- Password Change Section -->
    <div class="col-12 col-lg-8">
      <div class="card border-0 shadow-sm" style="border-radius: 15px;">
        <div class="card-header bg-white border-0 pb-0">
          <div class="d-flex align-items-center">
            <div class="d-inline-flex align-items-center justify-content-center rounded-circle bg-success-subtle me-3" style="width: 40px; height: 40px;">
              <i class="bi bi-key text-success" style="font-size: 1rem;"></i>
            </div>
            <div>
              <h5 class="mb-0 fw-bold text-dark fs-6">Cambiar Contraseña</h5>
              <p class="text-muted mb-0 small d-none d-md-block">Gestiona las contraseñas de los usuarios del sistema</p>
            </div>
          </div>
        </div>
        <div class="card-body">
          <div id="password-alert" class="alert" style="display: none;"></div>
          <form id="passwordForm" autocomplete="off">
            <div class="mb-3 mb-md-4">
              <label for="usuario_id" class="form-label fw-semibold">Seleccionar usuario</label>
              <select id="usuario_id" name="usuario_id" class="form-select" required>
                <option value="" disabled selected>Elige usuario</option>
                <?php
                foreach ($empleados as $empleado) {
                  $esElMismo = ($empleado['id'] == $_SESSION['empleado_id']) ? ' (Tú)' : '';
                  echo '<option value="' . htmlspecialchars($empleado['id']) . '">' . htmlspecialchars($empleado['nombre']) . ' (' . htmlspecialchars($empleado['usuario']) . ')' . $esElMismo . '</option>';
                }
                ?>
              </select>
              <div class="mt-2 p-2 p-md-3 rounded bg-primary-subtle bg-opacity-10">
                <small class="text-primary fw-medium">
                  <i class="bi bi-info-circle me-1"></i> 
                  Como administrador, puedes cambiar contraseñas sin conocer la actual
                </small>
              </div>
            </div>
            
            <div class="mb-3 mb-md-4" id="clave-actual-container">
              <label for="clave_actual" class="form-label fw-semibold">Contraseña actual</label>
              <div class="input-group">
                <input type="password" class="form-control" id="clave_actual" name="clave_actual">
                <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('clave_actual')">
                  <i class="bi bi-eye" id="icon-clave_actual"></i>
                </button>
              </div>
              <small class="text-muted mt-1 d-block" id="clave-actual-help">
                Solo necesaria cuando cambias tu propia contraseña
              </small>
            </div>
            
            <div class="mb-3 mb-md-4">
              <label for="nueva_clave" class="form-label fw-semibold">Nueva contraseña</label>
              <div class="input-group">
                <input type="password" class="form-control" id="nueva_clave" name="nueva_clave" required 
                       oninput="checkPasswordStrength()">
                <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('nueva_clave')">
                  <i class="bi bi-eye" id="icon-nueva_clave"></i>
                </button>
              </div>
              <div class="password-strength mt-2" style="height: 4px; border-radius: 3px;">
                <div class="password-strength-bar" id="strengthBar" style="border-radius: 3px;"></div>
              </div>
              <small id="strengthText" class="text-muted mt-1 d-block"></small>
            </div>
            
            <div class="mb-3 mb-md-4">
              <label for="confirmar_clave" class="form-label fw-semibold">Confirmar nueva contraseña</label>
              <div class="input-group">
                <input type="password" class="form-control" id="confirmar_clave" name="confirmar_clave" required>
                <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('confirmar_clave')">
                  <i class="bi bi-eye" id="icon-confirmar_clave"></i>
                </button>
              </div>
              <small id="matchText" class="text-muted mt-1 d-block"></small>
            </div>
            
            <div class="d-flex flex-column flex-md-row gap-2 mt-3 mt-md-4">
              <button type="submit" class="btn btn-primary order-1 order-md-0" id="submitBtn">
                <span class="spinner-border spinner-border-sm me-2" style="display: none;" id="spinner"></span>
                <i class="bi bi-check2-circle me-2"></i>
                Cambiar Contraseña
              </button>
              <a href="<?= $config['ruta_absoluta'] ?>dashboard" class="btn btn-secondary order-0 order-md-1">
                <i class="bi bi-arrow-left me-2"></i>
                Volver
              </a>
            </div>
          </form>
        </div>
      </div>
    </div>
    
    <!-- Side Panel -->
    <div class="col-12 col-lg-4">
      <div class="card border-0 shadow-sm h-100" style="border-radius: 15px;">
        <div class="card-header bg-white border-0 pb-0">
          <h5 class="mb-0 fw-bold text-dark fs-6">
            <i class="bi bi-person-badge text-warning me-2"></i>
            Gestión de Roles
          </h5>
        </div>
        <div class="card-body text-center">
          <div class="mb-3 mb-md-4">
            <div class="d-inline-flex align-items-center justify-content-center rounded-circle bg-warning-subtle mx-auto mb-3" style="width: 60px; height: 60px;">
              <i class="bi bi-people-fill text-warning" style="font-size: 1.5rem;"></i>
            </div>
            <h6 class="fw-bold text-dark">Roles y Permisos</h6>
            <p class="text-muted small">Administra los roles de los empleados y sus permisos de acceso.</p>
          </div>
          
          <div class="row g-2 mb-3 mb-md-4">
            <div class="col-6">
              <div class="d-flex align-items-center p-2 bg-light rounded">
                <div class="shrink-0">
                  <i class="bi bi-person-badge text-warning fs-5"></i>
                </div>
                <div class="flex-grow-1 ms-2 text-start">
                  <div class="fw-bold text-dark small"><?= count(array_filter($empleados, function($emp) { return $emp['es_admin'] == 1; })) ?></div>
                  <small class="text-muted">Admins</small>
                </div>
              </div>
            </div>
            <div class="col-6">
              <div class="d-flex align-items-center p-2 bg-light rounded">
                <div class="shrink-0">
                  <i class="bi bi-people text-info fs-5"></i>
                </div>
                <div class="flex-grow-1 ms-2 text-start">
                  <div class="fw-bold text-dark small"><?= count(array_filter($empleados, function($emp) { return $emp['es_admin'] == 0; })) ?></div>
                  <small class="text-muted">Usuarios</small>
                </div>
              </div>
            </div>
          </div>
          
          <button class="btn btn-outline-primary btn-sm disabled">
            <i class="bi bi-gear me-2"></i>
            Próximamente
          </button>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Scripts -->
<script src="<?= $config['ruta_absoluta'] ?>assets/js/security.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Pasar datos de PHP a JavaScript
    window.adminEmpleadoId = '<?= $_SESSION['empleado_id'] ?? 0 ?>';
    
    // Inicializar funcionalidad de seguridad
    if (typeof initSecurityFunctions === 'function') {
        initSecurityFunctions();
    }

    // Inicializar eventos del formulario
    initPasswordFormEvents();
});

function initPasswordFormEvents() {
    const form = document.getElementById('passwordForm');
    const userSelect = document.getElementById('usuario_id');
    const currentPasswordContainer = document.getElementById('clave-actual-container');
    
    // Mostrar/ocultar campo de contraseña actual según el usuario seleccionado
    userSelect.addEventListener('change', function() {
        const isCurrentUser = this.value === window.adminEmpleadoId;
        currentPasswordContainer.style.display = isCurrentUser ? 'block' : 'none';
        document.getElementById('clave_actual').required = isCurrentUser;
    });
    
    // Manejar envío del formulario
    form.addEventListener('submit', async function(e) {
        e.preventDefault();
        // Aquí iría la lógica de cambio de contraseña
        const formData = new FormData(this);
        try {
            // Lógica de envío
        } catch (error) {
            console.error('Error:', error);
        }
    });
}

function togglePassword(inputId) {
    const input = document.getElementById(inputId);
    const icon = document.getElementById('icon-' + inputId);
    
    if (input.type === 'password') {
        input.type = 'text';
        icon.classList.replace('bi-eye', 'bi-eye-slash');
    } else {
        input.type = 'password';
        icon.classList.replace('bi-eye-slash', 'bi-eye');
    }
}

function checkPasswordStrength() {
    const password = document.getElementById('nueva_clave').value;
    const strengthBar = document.getElementById('passwordStrength');
    const strengthText = document.getElementById('strengthText');
    
    if (!password) {
        strengthBar.style.width = '0%';
        strengthBar.className = 'progress-bar';
        strengthText.textContent = '';
        return;
    }
    
    let strength = 0;
    let feedback = [];
    
    // Longitud mínima
    if (password.length >= 8) {
        strength += 25;
        feedback.push('Longitud adecuada');
    }
    
    // Letras mayúsculas y minúsculas
    if (/[a-z]/.test(password) && /[A-Z]/.test(password)) {
        strength += 25;
        feedback.push('Combinación de mayúsculas y minúsculas');
    }
    
    // Números
    if (/\d/.test(password)) {
        strength += 25;
        feedback.push('Incluye números');
    }
    
    // Caracteres especiales
    if (/[^A-Za-z0-9]/.test(password)) {
        strength += 25;
        feedback.push('Incluye caracteres especiales');
    }
    
    // Actualizar UI
    strengthBar.style.width = strength + '%';
    strengthBar.className = 'progress-bar bg-' + (
        strength <= 25 ? 'danger' :
        strength <= 50 ? 'warning' :
        strength <= 75 ? 'info' : 'success'
    );
    
    strengthText.textContent = feedback.join(' • ');
}

function checkPasswordMatch() {
    const password = document.getElementById('nueva_clave').value;
    const confirm = document.getElementById('confirmar_clave').value;
    const matchText = document.getElementById('matchText');
    
    if (confirm) {
        if (password === confirm) {
            matchText.textContent = 'Las contraseñas coinciden';
            matchText.className = 'text-success mt-1 d-block';
        } else {
            matchText.textContent = 'Las contraseñas no coinciden';
            matchText.className = 'text-danger mt-1 d-block';
        }
    } else {
        matchText.textContent = '';
    }
}
</script>
