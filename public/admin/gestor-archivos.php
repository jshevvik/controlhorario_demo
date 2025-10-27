<?php
/**
 * Gestor de Archivos - Panel de Administración
 * 
 * Sistema de gestión y exploración de archivos subidos al sistema.
 * Permite visualizar, organizar, descargar y eliminar archivos
 * de las diferentes carpetas del sistema de control horario.
 * 
 * Funcionalidades:
 * - Exploración de directorios de uploads
 * - Vista previa de archivos (imágenes, PDFs)
 * - Descarga y eliminación de archivos
 * - Información detallada de archivos
 * - Gestión de espacio de almacenamiento
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

// Directorio base de uploads
$uploadsBase = __DIR__ . '/../../uploads';
$carpetaActual = $_GET['carpeta'] ?? '';
$mensaje = '';

// Validar carpeta solicitada
$carpetasPermitidas = ['', 'solicitudes', 'usuarios'];
if (!in_array($carpetaActual, $carpetasPermitidas)) {
    $carpetaActual = '';
}

// Procesar acciones
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['accion']) && $_POST['accion'] === 'eliminar' && isset($_POST['archivo'])) {
        $archivoEliminar = $_POST['archivo'];
        $rutaCompleta = $uploadsBase . '/' . $archivoEliminar;
        
        // Verificar que el archivo existe y está dentro del directorio permitido
        if (file_exists($rutaCompleta) && strpos(realpath($rutaCompleta), realpath($uploadsBase)) === 0) {
            if (unlink($rutaCompleta)) {
                $mensaje = "Archivo eliminado correctamente.";
            } else {
                $mensaje = "Error al eliminar el archivo.";
            }
        } else {
            $mensaje = "Archivo no encontrado.";
        }
    }
}

// Obtener archivos de la carpeta actual
function obtenerArchivos($directorio, $carpeta = '') {
    $archivos = [];
    $rutaCompleta = $directorio . ($carpeta ? '/' . $carpeta : '');
    
    if (is_dir($rutaCompleta)) {
        $items = scandir($rutaCompleta);
        foreach ($items as $item) {
            if ($item !== '.' && $item !== '..') {
                $rutaItem = $rutaCompleta . '/' . $item;
                if (is_file($rutaItem)) {
                    $archivos[] = [
                        'nombre' => $item,
                        'ruta_relativa' => ($carpeta ? $carpeta . '/' : '') . $item,
                        'tamaño' => filesize($rutaItem),
                        'fecha_modificacion' => filemtime($rutaItem),
                        'extension' => strtolower(pathinfo($item, PATHINFO_EXTENSION)),
                        'tipo_mime' => mime_content_type($rutaItem)
                    ];
                }
            }
        }
    }
    
    // Ordenar por fecha de modificación descendente
    usort($archivos, function($a, $b) {
        return $b['fecha_modificacion'] - $a['fecha_modificacion'];
    });
    
    return $archivos;
}

$archivos = obtenerArchivos($uploadsBase, $carpetaActual);

// Función para formatear tamaño de archivo
function formatearTamaño($bytes) {
    $unidades = ['B', 'KB', 'MB', 'GB'];
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($unidades) - 1);
    
    $bytes /= pow(1024, $pow);
    
    return round($bytes, 2) . ' ' . $unidades[$pow];
}

// Función para obtener icono según extensión
function obtenerIconoArchivo($extension) {
    return match($extension) {
        'pdf' => 'ti-file-text text-danger',
        'jpg', 'jpeg', 'png', 'gif', 'webp' => 'ti-photo text-primary',
        'doc', 'docx' => 'ti-file-text text-info',
        'xls', 'xlsx' => 'ti-file-spreadsheet text-success',
        'zip', 'rar', '7z' => 'ti-archive text-warning',
        default => 'ti-file text-secondary'
    };
}
?>

<div class="mb-3 overflow-hidden position-relative">
    <div class="px-3">
        <h4 class="fs-6 mb-0 mt-2">Gestor de Archivos</h4>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item">
                    <a href="<?= $config['ruta_absoluta'] ?>dashboard">Inicio</a>
                </li>
                <li class="breadcrumb-item">
                    <a href="<?= $config['ruta_absoluta'] ?>admin/contenido">Administración de Contenido</a>
                </li>
                <li class="breadcrumb-item active" aria-current="page">Gestor de Archivos</li>
            </ol>
        </nav>
    </div>
</div>

<div class="container-fluid py-4">
    <!-- Mensajes -->
    <?php if ($mensaje): ?>
        <div class="alert alert-info alert-dismissible fade show" role="alert">
            <i class="ti ti-info-circle me-2"></i>
            <?= htmlspecialchars($mensaje) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="row">
        <div class="col-12">
            <!-- Navegación de carpetas -->
            <div class="card mb-4">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">
                            <i class="ti ti-folder me-2"></i>
                            Explorar Archivos
                        </h5>
                        <div class="btn-group" role="group">
                            <a href="?carpeta=" class="btn btn-<?= $carpetaActual === '' ? 'primary' : 'outline-primary' ?> btn-sm">
                                <i class="ti ti-home me-1"></i>Raíz
                            </a>
                            <a href="?carpeta=solicitudes" class="btn btn-<?= $carpetaActual === 'solicitudes' ? 'primary' : 'outline-primary' ?> btn-sm">
                                <i class="ti ti-file-text me-1"></i>Solicitudes
                            </a>
                            <a href="?carpeta=usuarios" class="btn btn-<?= $carpetaActual === 'usuarios' ? 'primary' : 'outline-primary' ?> btn-sm">
                                <i class="ti ti-users me-1"></i>Usuarios
                            </a>
                        </div>
                    </div>
                    
                    <div class="mt-2">
                        <small class="text-muted">
                            <i class="ti ti-map-pin me-1"></i>
                            /uploads<?= $carpetaActual ? '/' . $carpetaActual : '' ?>
                        </small>
                    </div>
                </div>
            </div>

            <!-- Lista de archivos -->
            <div class="card">
                <div class="card-body">
                    <?php if (empty($archivos)): ?>
                        <div class="text-center py-5">
                            <i class="ti ti-folder-off display-4 text-muted"></i>
                            <h4 class="text-muted mt-3">Carpeta vacía</h4>
                            <p class="text-muted">No hay archivos en esta carpeta.</p>
                        </div>
                    <?php else: ?>
                        <!-- Vista de tabla para pantallas grandes -->
                        <div class="table-responsive d-none d-lg-block">
                            <table class="table table-striped table-hover">
                                <thead class="bg-info text-white">
                                    <tr>
                                        <th>Archivo</th>
                                        <th>Tamaño</th>
                                        <th>Tipo</th>
                                        <th>Fecha modificación</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($archivos as $archivo): ?>
                                        <tr>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <i class="ti <?= obtenerIconoArchivo($archivo['extension']) ?> fs-4 me-2"></i>
                                                    <div>
                                                        <div class="fw-bold"><?= htmlspecialchars($archivo['nombre']) ?></div>
                                                        <small class="text-muted"><?= htmlspecialchars($archivo['ruta_relativa']) ?></small>
                                                    </div>
                                                </div>
                                            </td>
                                            <td><?= formatearTamaño($archivo['tamaño']) ?></td>
                                            <td>
                                                <span class="badge bg-secondary">
                                                    <?= strtoupper($archivo['extension']) ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?= date('d/m/Y H:i', $archivo['fecha_modificacion']) ?>
                                            </td>
                                            <td>
                                                <div class="btn-group btn-group-sm">
                                                    <?php if (in_array($archivo['extension'], ['jpg', 'jpeg', 'png', 'gif', 'webp'])): ?>
                                                        <button class="btn btn-outline-primary" onclick="mostrarVistaPrevia('<?= htmlspecialchars($archivo['ruta_relativa']) ?>', 'imagen')">
                                                            <i class="ti ti-eye"></i>
                                                        </button>
                                                    <?php elseif ($archivo['extension'] === 'pdf'): ?>
                                                        <button class="btn btn-outline-primary" onclick="mostrarVistaPrevia('<?= htmlspecialchars($archivo['ruta_relativa']) ?>', 'pdf')">
                                                            <i class="ti ti-eye"></i>
                                                        </button>
                                                    <?php endif; ?>
                                                    
                                                    <a href="<?= $config['ruta_absoluta'] ?>acciones/descargar-archivo?archivo=<?= urlencode($archivo['ruta_relativa']) ?>" 
                                                       class="btn btn-outline-success" download>
                                                        <i class="ti ti-download"></i>
                                                    </a>
                                                    
                                                    <button class="btn btn-outline-danger" 
                                                            onclick="eliminarArchivo('<?= htmlspecialchars($archivo['ruta_relativa']) ?>', '<?= htmlspecialchars($archivo['nombre']) ?>')">
                                                        <i class="ti ti-trash"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <!-- Vista de tarjetas para móviles -->
                        <div class="d-lg-none">
                            <?php foreach ($archivos as $archivo): ?>
                                <div class="card mb-3">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-start mb-2">
                                            <div class="d-flex align-items-center">
                                                <i class="ti <?= obtenerIconoArchivo($archivo['extension']) ?> fs-4 me-2"></i>
                                                <div>
                                                    <div class="fw-bold"><?= htmlspecialchars($archivo['nombre']) ?></div>
                                                    <small class="text-muted"><?= formatearTamaño($archivo['tamaño']) ?></small>
                                                </div>
                                            </div>
                                            <span class="badge bg-secondary">
                                                <?= strtoupper($archivo['extension']) ?>
                                            </span>
                                        </div>
                                        
                                        <small class="text-muted d-block mb-3">
                                            <i class="ti ti-clock me-1"></i>
                                            <?= date('d/m/Y H:i', $archivo['fecha_modificacion']) ?>
                                        </small>
                                        
                                        <div class="btn-group btn-group-sm w-100">
                                            <?php if (in_array($archivo['extension'], ['jpg', 'jpeg', 'png', 'gif', 'webp', 'pdf'])): ?>
                                                <button class="btn btn-outline-primary" onclick="mostrarVistaPrevia('<?= htmlspecialchars($archivo['ruta_relativa']) ?>', '<?= in_array($archivo['extension'], ['jpg', 'jpeg', 'png', 'gif', 'webp']) ? 'imagen' : 'pdf' ?>')">
                                                    <i class="ti ti-eye me-1"></i>Ver
                                                </button>
                                            <?php endif; ?>
                                            
                                            <a href="<?= $config['ruta_absoluta'] ?>acciones/descargar-archivo?archivo=<?= urlencode($archivo['ruta_relativa']) ?>" 
                                               class="btn btn-outline-success" download>
                                                <i class="ti ti-download me-1"></i>Descargar
                                            </a>
                                            
                                            <button class="btn btn-outline-danger" 
                                                    onclick="eliminarArchivo('<?= htmlspecialchars($archivo['ruta_relativa']) ?>', '<?= htmlspecialchars($archivo['nombre']) ?>')">
                                                <i class="ti ti-trash me-1"></i>Eliminar
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <!-- Información de resumen -->
                        <div class="alert alert-light mt-4">
                            <div class="row text-center">
                                <div class="col-4">
                                    <strong><?= count($archivos) ?></strong>
                                    <br><small class="text-muted">Archivos</small>
                                </div>
                                <div class="col-4">
                                    <strong><?= formatearTamaño(array_sum(array_column($archivos, 'tamaño'))) ?></strong>
                                    <br><small class="text-muted">Tamaño total</small>
                                </div>
                                <div class="col-4">
                                    <strong><?= $carpetaActual ?: 'Todas' ?></strong>
                                    <br><small class="text-muted">Carpeta</small>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal para vista previa -->
<div class="modal fade" id="modalVistaPrevia" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Vista previa</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center" id="contenidoVistaPrevia">
                <!-- Se llena dinámicamente -->
            </div>
        </div>
    </div>
</div>

<!-- Modal para confirmar eliminación -->
<div class="modal fade" id="modalEliminar" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="ti ti-trash me-2"></i>
                    Confirmar eliminación
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>¿Está seguro de que desea eliminar el archivo <strong id="nombreArchivoEliminar"></strong>?</p>
                <div class="alert alert-warning">
                    <i class="ti ti-alert-triangle me-2"></i>
                    Esta acción no se puede deshacer.
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <form method="post" class="d-inline" id="formEliminar">
                    <input type="hidden" name="accion" value="eliminar">
                    <input type="hidden" name="archivo" id="archivoEliminar">
                    <button type="submit" class="btn btn-danger">
                        <i class="ti ti-trash me-1"></i>Eliminar
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function mostrarVistaPrevia(rutaArchivo, tipo) {
    const modal = new bootstrap.Modal(document.getElementById('modalVistaPrevia'));
    const contenido = document.getElementById('contenidoVistaPrevia');
    
    if (tipo === 'imagen') {
        contenido.innerHTML = `<img src="${BASE_URL}uploads/${rutaArchivo}" class="img-fluid" style="max-height: 500px;">`;
    } else if (tipo === 'pdf') {
        contenido.innerHTML = `<embed src="${BASE_URL}uploads/${rutaArchivo}" type="application/pdf" width="100%" height="500px">`;
    }
    
    modal.show();
}

function eliminarArchivo(rutaArchivo, nombreArchivo) {
    document.getElementById('nombreArchivoEliminar').textContent = nombreArchivo;
    document.getElementById('archivoEliminar').value = rutaArchivo;
    
    const modal = new bootstrap.Modal(document.getElementById('modalEliminar'));
    modal.show();
}
</script>

<?php
// Definir BASE_URL para el JavaScript
echo "<script>var BASE_URL = '" . $config['ruta_absoluta'] . "';</script>";
?>
