<?php
/**
 * Página de Creación de Notificaciones - Panel de Administración
 * 
 * Sistema de gestión para envío de notificaciones a empleados del sistema.
 * Permite crear notificaciones individuales o globales con diferentes tipos
 * y configuraciones de redirección.
 * 
 * Funcionalidades:
 * - Creación de notificaciones globales (todos los empleados)
 * - Notificaciones individuales por empleado específico
 * - Tipos: información, alertas, aprobaciones y solicitudes
 * - URLs de redirección opcionales
 * - Validación y sanitización de datos
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

// Variables de control de formulario
$mensaje_exito = '';
$mensaje_error = '';

// Obtener lista de empleados para el selector
try {
    $stmt = $pdo->prepare("SELECT id, nombre, apellidos, rol FROM empleados ORDER BY nombre, apellidos");
    $stmt->execute();
    $empleados = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $empleados = [];
    error_log("Error obteniendo empleados: " . $e->getMessage());
}

// Procesamiento del formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $mensaje = trim($_POST['mensaje'] ?? '');
    $tipo = $_POST['tipo'] ?? 'info';
    $url = trim($_POST['url'] ?? '') ?: null;
    $empleado_id = !empty($_POST['empleado_id']) ? intval($_POST['empleado_id']) : null;
    
    // Validaciones
    if (empty($mensaje)) {
        $mensaje_error = 'El mensaje es obligatorio.';
    } elseif (strlen($mensaje) > 500) {
        $mensaje_error = 'El mensaje no puede exceder 500 caracteres.';
    } elseif (!in_array($tipo, ['info', 'alerta', 'aprobacion', 'solicitud'])) {
        $mensaje_error = 'Tipo de notificación no válido.';
    } else {
        // Intentar crear la notificación
        if (crearNotificacion($mensaje, $empleado_id, $tipo, $url)) {
            $destinatario = $empleado_id ? 'empleado específico' : 'todos los empleados';
            $mensaje_exito = "Notificación enviada correctamente a {$destinatario}.";
            
            // Limpiar campos del formulario después del éxito
            $_POST = [];
        } else {
            $mensaje_error = 'Error al crear la notificación. Inténtelo de nuevo.';
        }
    }
}
?>

<div class="mb-3 overflow-hidden position-relative">
    <div class="px-3">
        <h4 class="fs-6 mb-0 mt-2">Crear Notificación</h4>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item">
                    <a href="<?= $config['ruta_absoluta'] ?>dashboard">Inicio</a>
                </li>
                <li class="breadcrumb-item">
                    <a href="<?= $config['ruta_absoluta'] ?>admin/empleados">Administración</a>
                </li>
                <li class="breadcrumb-item active" aria-current="page">Crear Notificación</li>
            </ol>
        </nav>
    </div>
</div>

<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card shadow">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">
                        <i class="ti ti-bell me-2"></i>
                        Enviar Nueva Notificación
                    </h4>
                </div>
                <div class="card-body">
                    <!-- Mensajes de estado -->
                    <?php if ($mensaje_exito): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="ti ti-check-circle me-2"></i>
                            <?= htmlspecialchars($mensaje_exito) ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <?php if ($mensaje_error): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="ti ti-exclamation-triangle me-2"></i>
                            <?= htmlspecialchars($mensaje_error) ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <!-- Formulario de creación de notificación -->
                    <form method="post" autocomplete="off">
                        <div class="row">
                            <!-- Tipo de notificación -->
                            <div class="col-md-6 mb-3">
                                <label for="tipo" class="form-label">
                                    <i class="ti ti-category me-1"></i>
                                    Tipo de notificación
                                </label>
                                <select id="tipo" name="tipo" class="form-select" required>
                                    <option value="info" <?= ($_POST['tipo'] ?? '') === 'info' ? 'selected' : '' ?>>
                                        📘 Información general
                                    </option>
                                    <option value="solicitud" <?= ($_POST['tipo'] ?? '') === 'solicitud' ? 'selected' : '' ?>>
                                        🔔 Solicitud/Petición  
                                    </option>
                                    <option value="aprobacion" <?= ($_POST['tipo'] ?? '') === 'aprobacion' ? 'selected' : '' ?>>
                                        ✅ Aprobación/Confirmación
                                    </option>
                                    <option value="alerta" <?= ($_POST['tipo'] ?? '') === 'alerta' ? 'selected' : '' ?>>
                                        ⚠️ Alerta/Urgente
                                    </option>
                                </select>
                            </div>

                            <!-- Destinatario -->
                            <div class="col-md-6 mb-3">
                                <label for="empleado_id" class="form-label">
                                    <i class="ti ti-users me-1"></i>
                                    Destinatario
                                </label>
                                <select id="empleado_id" name="empleado_id" class="form-select">
                                    <option value="">🌐 Todos los empleados (Global)</option>
                                    <optgroup label="Empleados individuales">
                                        <?php foreach ($empleados as $emp): ?>
                                            <option value="<?= $emp['id'] ?>" 
                                                    <?= ($_POST['empleado_id'] ?? '') == $emp['id'] ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($emp['nombre'] . ' ' . $emp['apellidos']) ?> 
                                                (<?= ucfirst($emp['rol']) ?>)
                                            </option>
                                        <?php endforeach; ?>
                                    </optgroup>
                                </select>
                                <div class="form-text">
                                    Dejar en "Todos los empleados" para enviar notificación global
                                </div>
                            </div>
                        </div>

                        <!-- Mensaje -->
                        <div class="mb-3">
                            <label for="mensaje" class="form-label">
                                <i class="ti ti-message me-1"></i>
                                Mensaje de la notificación *
                            </label>
                            <textarea id="mensaje" name="mensaje" class="form-control" rows="4" 
                                      maxlength="500" required 
                                      placeholder="Escriba aquí el mensaje de la notificación..."><?= htmlspecialchars($_POST['mensaje'] ?? '') ?></textarea>
                            <div class="form-text">
                                <span id="contadorCaracteres">0</span>/500 caracteres
                            </div>
                        </div>

                        <!-- URL de redirección -->
                        <div class="mb-4">
                            <label for="url" class="form-label">
                                <i class="ti ti-link me-1"></i>
                                URL de redirección (opcional)
                            </label>
                            <input type="url" id="url" name="url" class="form-control" 
                                   value="<?= htmlspecialchars($_POST['url'] ?? '') ?>"
                                   placeholder="https://ejemplo.com o solicitudes o admin/empleados">
                            <div class="form-text">
                                Puede ser una URL completa (http://) o relativa (solicitudes, admin/empleados)
                            </div>
                        </div>

                        <!-- Vista previa -->
                        <div class="card bg-light mb-4" id="previsualizacion" style="display: none;">
                            <div class="card-header">
                                <h6 class="mb-0">
                                    <i class="ti ti-eye me-1"></i>
                                    Vista previa de la notificación
                                </h6>
                            </div>
                            <div class="card-body">
                                <div id="preview-content">
                                    <!-- Se llena dinámicamente con JavaScript -->
                                </div>
                            </div>
                        </div>

                        <!-- Botones de acción -->
                        <div class="d-flex justify-content-between gap-3">
                            <button type="button" class="btn btn-secondary" onclick="window.history.back()">
                                <i class="ti ti-arrow-left me-1"></i>
                                Volver
                            </button>
                            
                            <div class="d-flex gap-2">
                                <button type="button" class="btn btn-outline-primary" id="btnPreview">
                                    <i class="ti ti-eye me-1"></i>
                                    Vista previa
                                </button>
                                <button type="submit" class="btn btn-primary">
                                    <i class="ti ti-send me-1"></i>
                                    Enviar notificación
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Información adicional -->
            <div class="card mt-4">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="ti ti-info-circle me-2"></i>
                        Información sobre notificaciones
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h6>Tipos de notificación:</h6>
                            <ul class="list-unstyled">
                                <li class="mb-1"><span class="badge bg-primary">📘 Información</span> - Avisos generales y comunicaciones</li>
                                <li class="mb-1"><span class="badge bg-info">🔔 Solicitud</span> - Peticiones que requieren atención</li>
                                <li class="mb-1"><span class="badge bg-success">✅ Aprobación</span> - Confirmaciones y aprobaciones</li>
                                <li class="mb-1"><span class="badge bg-danger">⚠️ Alerta</span> - Situaciones urgentes o críticas</li>
                            </ul>
                        </div>
                        <div class="col-md-6">
                            <h6>Configuración de URLs:</h6>
                            <ul class="list-unstyled">
                                <li class="mb-1"><code>solicitudes</code> - Página de solicitudes</li>
                                <li class="mb-1"><code>admin/empleados</code> - Gestión de empleados</li>
                                <li class="mb-1"><code>dashboard</code> - Panel principal</li>
                                <li class="mb-1"><code>http://...</code> - URL externa completa</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const mensaje = document.getElementById('mensaje');
    const contador = document.getElementById('contadorCaracteres');
    const btnPreview = document.getElementById('btnPreview');
    const previsualizacion = document.getElementById('previsualizacion');
    const previewContent = document.getElementById('preview-content');

    // Contador de caracteres
    function actualizarContador() {
        const longitud = mensaje.value.length;
        contador.textContent = longitud;
        contador.className = longitud > 450 ? 'text-warning' : longitud > 500 ? 'text-danger' : 'text-muted';
    }

    mensaje.addEventListener('input', actualizarContador);
    actualizarContador();

    // Vista previa
    btnPreview.addEventListener('click', function() {
        const tipo = document.getElementById('tipo').value;
        const mensajeTexto = mensaje.value.trim();
        const url = document.getElementById('url').value.trim();
        const empleadoId = document.getElementById('empleado_id').value;
        
        if (!mensajeTexto) {
            alert('Escriba un mensaje para ver la vista previa');
            return;
        }

        // Mapeo de colores
        const colores = {
            'info': 'primary',
            'solicitud': 'info', 
            'aprobacion': 'success',
            'alerta': 'danger'
        };

        // Mapeo de iconos
        const iconos = {
            'info': 'bi-info-circle-fill',
            'solicitud': 'bi-bell-fill',
            'aprobacion': 'bi-check-circle-fill', 
            'alerta': 'bi-exclamation-triangle-fill'
        };

        const color = colores[tipo];
        const icono = iconos[tipo];
        const destinatario = empleadoId ? 'empleado específico' : 'todos los empleados';

        previewContent.innerHTML = `
            <div class="alert alert-${color} bg-${color}-subtle text-${color}">
                <div class="d-flex align-items-center">
                    <i class="bi ${icono} fs-5 me-2"></i>
                    <div>
                        ${url ? 
                            `<a href="#" class="text-${color} fw-bold">${mensajeTexto}</a>` : 
                            `<span class="text-${color} fw-bold">${mensajeTexto}</span>`
                        }
                        <small class="text-muted ms-2">${new Date().toLocaleString('es-ES')}</small>
                        <span class="badge bg-warning text-dark ms-2">Nuevo</span>
                    </div>
                </div>
            </div>
            <div class="text-muted">
                <small>
                    <i class="ti ti-info-circle me-1"></i>
                    Esta notificación se enviará a: <strong>${destinatario}</strong>
                </small>
            </div>
        `;

        previsualizacion.style.display = 'block';
        previsualizacion.scrollIntoView({ behavior: 'smooth' });
    });
});
</script><?php
// Definir BASE_URL para el JavaScript
echo "<script>var BASE_URL = '" . $config['ruta_absoluta'] . "';</script>";
?>
