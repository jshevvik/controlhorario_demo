<?php
/**
 * Subir Archivos - Panel de Administración
 * 
 * Sistema para cargar y gestionar archivos en el servidor.
 * Permite subir múltiples archivos con validación de tipos,
 * tamaños y organización en carpetas específicas.
 * 
 * Funcionalidades:
 * - Subida múltiple de archivos con drag & drop
 * - Validación de tipos de archivo permitidos
 * - Control de tamaño máximo por archivo
 * - Organización automática en carpetas
 * - Vista previa antes de la subida
 * - Progreso de subida visual
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

// Configuración de subida
$carpetasPermitidas = [
    'usuarios' => 'Usuarios (avatares, fotos de perfil)',
    'solicitudes' => 'Solicitudes (documentos adjuntos)',
    'documentos' => 'Documentos generales'
];

$tiposPermitidos = [
    'jpg', 'jpeg', 'png', 'gif', 'webp', // Imágenes
    'pdf', 'doc', 'docx', // Documentos
    'xls', 'xlsx', // Hojas de cálculo
    'txt', 'csv' // Texto plano
];

$tamañoMaximo = 5 * 1024 * 1024; // 5MB

$mensajes = [];
$archivosSubidos = [];

// Procesar subida de archivos
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_FILES['archivos']['name'][0])) {
    $carpetaDestino = $_POST['carpeta'] ?? 'documentos';
    
    // Validar carpeta
    if (!array_key_exists($carpetaDestino, $carpetasPermitidas)) {
        $carpetaDestino = 'documentos';
    }
    
    // Crear directorio si no existe
    $rutaDestino = __DIR__ . '/../../uploads/' . $carpetaDestino;
    if (!is_dir($rutaDestino)) {
        mkdir($rutaDestino, 0755, true);
    }
    
    // Procesar cada archivo
    $totalArchivos = count($_FILES['archivos']['name']);
    
    for ($i = 0; $i < $totalArchivos; $i++) {
        if ($_FILES['archivos']['error'][$i] === UPLOAD_ERR_OK) {
            $nombreOriginal = $_FILES['archivos']['name'][$i];
            $archivoTemporal = $_FILES['archivos']['tmp_name'][$i];
            $tamañoArchivo = $_FILES['archivos']['size'][$i];
            
            // Validar extensión
            $extension = strtolower(pathinfo($nombreOriginal, PATHINFO_EXTENSION));
            if (!in_array($extension, $tiposPermitidos)) {
                $mensajes[] = "❌ {$nombreOriginal}: Tipo de archivo no permitido.";
                continue;
            }
            
            // Validar tamaño
            if ($tamañoArchivo > $tamañoMaximo) {
                $mensajes[] = "❌ {$nombreOriginal}: Archivo demasiado grande (máximo 5MB).";
                continue;
            }
            
            // Generar nombre único
            $nombreArchivo = date('YmdHis') . '_' . uniqid() . '.' . $extension;
            $rutaCompleta = $rutaDestino . '/' . $nombreArchivo;
            
            // Mover archivo
            if (move_uploaded_file($archivoTemporal, $rutaCompleta)) {
                $archivosSubidos[] = [
                    'nombre_original' => $nombreOriginal,
                    'nombre_archivo' => $nombreArchivo,
                    'carpeta' => $carpetaDestino,
                    'tamaño' => $tamañoArchivo
                ];
                $mensajes[] = "✅ {$nombreOriginal}: Subido correctamente.";
            } else {
                $mensajes[] = "❌ {$nombreOriginal}: Error al subir el archivo.";
            }
        } else {
            $nombreOriginal = $_FILES['archivos']['name'][$i];
            $mensajes[] = "❌ {$nombreOriginal}: Error en la subida.";
        }
    }
}

// Función para formatear tamaño
function formatearTamaño($bytes) {
    $unidades = ['B', 'KB', 'MB', 'GB'];
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($unidades) - 1);
    
    $bytes /= pow(1024, $pow);
    
    return round($bytes, 2) . ' ' . $unidades[$pow];
}
?>

<div class="mb-3 overflow-hidden position-relative">
    <div class="px-3">
        <h4 class="fs-6 mb-0 mt-2">Subir Archivos</h4>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item">
                    <a href="<?= $config['ruta_absoluta'] ?>dashboard">Inicio</a>
                </li>
                <li class="breadcrumb-item">
                    <a href="<?= $config['ruta_absoluta'] ?>admin/contenido">Administración de Contenido</a>
                </li>
                <li class="breadcrumb-item active" aria-current="page">Subir Archivos</li>
            </ol>
        </nav>
    </div>
</div>

<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <!-- Mensajes de resultado -->
            <?php if (!empty($mensajes)): ?>
                <div class="card mb-4">
                    <div class="card-header bg-info text-white">
                        <h5 class="mb-0">
                            <i class="ti ti-info-circle me-2"></i>
                            Resultado de la subida
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php foreach ($mensajes as $mensaje): ?>
                            <div class="mb-1"><?= htmlspecialchars($mensaje) ?></div>
                        <?php endforeach; ?>
                        
                        <?php if (!empty($archivosSubidos)): ?>
                            <div class="mt-3">
                                <a href="<?= $config['ruta_absoluta'] ?>admin/gestor-archivos" class="btn btn-primary">
                                    <i class="ti ti-folder me-1"></i>
                                    Ver archivos subidos
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Formulario de subida -->
            <div class="card shadow">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">
                        <i class="ti ti-upload me-2"></i>
                        Subir Archivos al Sistema
                    </h4>
                </div>
                <div class="card-body">
                    <form method="post" enctype="multipart/form-data" id="formSubida">
                        <!-- Selección de carpeta -->
                        <div class="mb-4">
                            <label for="carpeta" class="form-label">
                                <i class="ti ti-folder me-1"></i>
                                Carpeta de destino
                            </label>
                            <select id="carpeta" name="carpeta" class="form-select" required>
                                <?php foreach ($carpetasPermitidas as $valor => $descripcion): ?>
                                    <option value="<?= $valor ?>"><?= htmlspecialchars($descripcion) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <div class="form-text">
                                Seleccione la carpeta donde se guardarán los archivos
                            </div>
                        </div>

                        <!-- Zona de subida -->
                        <div class="mb-4">
                            <label class="form-label">
                                <i class="ti ti-files me-1"></i>
                                Seleccionar archivos
                            </label>
                            <div class="border border-2 border-dashed border-primary rounded p-4 text-center" 
                                 id="zonaSubida"
                                 ondrop="manejarDrop(event)" 
                                 ondragover="manejarDragOver(event)"
                                 ondragleave="manejarDragLeave(event)">
                                <i class="ti ti-cloud-upload display-4 text-primary mb-3"></i>
                                <h5 class="text-primary">Arrastra archivos aquí o haz clic para seleccionar</h5>
                                <p class="text-muted mb-3">
                                    Tipos permitidos: <?= implode(', ', array_map('strtoupper', $tiposPermitidos)) ?>
                                    <br>
                                    Tamaño máximo por archivo: <?= formatearTamaño($tamañoMaximo) ?>
                                </p>
                                
                                <input type="file" 
                                       id="archivos" 
                                       name="archivos[]" 
                                       multiple 
                                       accept=".<?= implode(',.', $tiposPermitidos) ?>"
                                       class="form-control d-none"
                                       onchange="mostrarArchivosSeleccionados()">
                                
                                <button type="button" class="btn btn-outline-primary" onclick="document.getElementById('archivos').click()">
                                    <i class="ti ti-file-plus me-1"></i>
                                    Seleccionar archivos
                                </button>
                            </div>
                        </div>

                        <!-- Lista de archivos seleccionados -->
                        <div id="listaArchivos" class="mb-4" style="display: none;">
                            <h6 class="mb-3">
                                <i class="ti ti-list me-2"></i>
                                Archivos seleccionados
                            </h6>
                            <div id="contenidoLista"></div>
                        </div>

                        <!-- Botones -->
                        <div class="d-flex justify-content-between gap-3">
                            <a href="<?= $config['ruta_absoluta'] ?>admin/contenido" class="btn btn-secondary">
                                <i class="ti ti-arrow-left me-1"></i>
                                Volver
                            </a>
                            
                            <div class="d-flex gap-2">
                                <button type="button" class="btn btn-outline-primary" onclick="limpiarSeleccion()">
                                    <i class="ti ti-refresh me-1"></i>
                                    Limpiar
                                </button>
                                <button type="submit" class="btn btn-primary" id="btnSubir" disabled>
                                    <i class="ti ti-upload me-1"></i>
                                    Subir archivos
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
                        Información sobre la subida de archivos
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h6>Tipos de archivo permitidos:</h6>
                            <ul class="list-unstyled">
                                <li><span class="badge bg-primary">Imágenes</span> JPG, PNG, GIF, WebP</li>
                                <li><span class="badge bg-info">Documentos</span> PDF, DOC, DOCX</li>
                                <li><span class="badge bg-success">Hojas de cálculo</span> XLS, XLSX</li>
                                <li><span class="badge bg-secondary">Texto</span> TXT, CSV</li>
                            </ul>
                        </div>
                        <div class="col-md-6">
                            <h6>Carpetas de destino:</h6>
                            <ul class="list-unstyled">
                                <li><strong>Usuarios:</strong> Avatares y fotos de perfil</li>
                                <li><strong>Solicitudes:</strong> Documentos adjuntos a solicitudes</li>
                                <li><strong>Documentos:</strong> Archivos generales del sistema</li>
                            </ul>
                        </div>
                    </div>
                    
                    <div class="alert alert-info mt-3">
                        <i class="ti ti-lightbulb me-2"></i>
                        <strong>Consejos:</strong>
                        <ul class="mb-0 mt-2">
                            <li>Puede subir múltiples archivos a la vez</li>
                            <li>Los archivos se renombrarán automáticamente para evitar conflictos</li>
                            <li>Tamaño máximo por archivo: <?= formatearTamaño($tamañoMaximo) ?></li>
                            <li>Use la función de arrastrar y soltar para mayor comodidad</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
let archivosSeleccionados = [];

function mostrarArchivosSeleccionados() {
    const input = document.getElementById('archivos');
    const lista = document.getElementById('listaArchivos');
    const contenido = document.getElementById('contenidoLista');
    const btnSubir = document.getElementById('btnSubir');
    
    archivosSeleccionados = Array.from(input.files);
    
    if (archivosSeleccionados.length > 0) {
        let html = '';
        archivosSeleccionados.forEach((archivo, index) => {
            const tamaño = formatearTamañoJS(archivo.size);
            const valido = validarArchivo(archivo);
            
            html += `
                <div class="d-flex justify-content-between align-items-center p-2 border rounded mb-2 ${valido ? 'border-success' : 'border-danger'}">
                    <div class="d-flex align-items-center">
                        <i class="ti ti-file me-2 ${valido ? 'text-success' : 'text-danger'}"></i>
                        <div>
                            <div class="fw-bold">${archivo.name}</div>
                            <small class="text-muted">${tamaño}</small>
                        </div>
                    </div>
                    <div class="d-flex align-items-center">
                        ${valido ? 
                            '<span class="badge bg-success me-2">Válido</span>' : 
                            '<span class="badge bg-danger me-2">Error</span>'
                        }
                        <button type="button" class="btn btn-sm btn-outline-danger" onclick="eliminarArchivo(${index})">
                            <i class="ti ti-x"></i>
                        </button>
                    </div>
                </div>
            `;
        });
        
        contenido.innerHTML = html;
        lista.style.display = 'block';
        
        // Habilitar botón si hay archivos válidos
        const archivosValidos = archivosSeleccionados.filter(validarArchivo);
        btnSubir.disabled = archivosValidos.length === 0;
    } else {
        lista.style.display = 'none';
        btnSubir.disabled = true;
    }
}

function validarArchivo(archivo) {
    const tiposPermitidos = <?= json_encode($tiposPermitidos) ?>;
    const tamañoMaximo = <?= $tamañoMaximo ?>;
    
    const extension = archivo.name.split('.').pop().toLowerCase();
    return tiposPermitidos.includes(extension) && archivo.size <= tamañoMaximo;
}

function formatearTamañoJS(bytes) {
    const unidades = ['B', 'KB', 'MB', 'GB'];
    let unidad = 0;
    
    while (bytes >= 1024 && unidad < unidades.length - 1) {
        bytes /= 1024;
        unidad++;
    }
    
    return Math.round(bytes * 100) / 100 + ' ' + unidades[unidad];
}

function eliminarArchivo(index) {
    archivosSeleccionados.splice(index, 1);
    
    // Recrear el input con los archivos restantes
    const input = document.getElementById('archivos');
    const dt = new DataTransfer();
    
    archivosSeleccionados.forEach(archivo => {
        dt.items.add(archivo);
    });
    
    input.files = dt.files;
    mostrarArchivosSeleccionados();
}

function limpiarSeleccion() {
    document.getElementById('archivos').value = '';
    archivosSeleccionados = [];
    document.getElementById('listaArchivos').style.display = 'none';
    document.getElementById('btnSubir').disabled = true;
}

// Funciones para drag & drop
function manejarDragOver(e) {
    e.preventDefault();
    e.stopPropagation();
    document.getElementById('zonaSubida').classList.add('border-success', 'bg-light');
}

function manejarDragLeave(e) {
    e.preventDefault();
    e.stopPropagation();
    document.getElementById('zonaSubida').classList.remove('border-success', 'bg-light');
}

function manejarDrop(e) {
    e.preventDefault();
    e.stopPropagation();
    
    const zona = document.getElementById('zonaSubida');
    zona.classList.remove('border-success', 'bg-light');
    
    const archivos = e.dataTransfer.files;
    if (archivos.length > 0) {
        document.getElementById('archivos').files = archivos;
        mostrarArchivosSeleccionados();
    }
}

// Hacer que la zona sea clickeable
document.getElementById('zonaSubida').addEventListener('click', function() {
    document.getElementById('archivos').click();
});
</script>

<?php
// Definir BASE_URL para el JavaScript
echo "<script>var BASE_URL = '" . $config['ruta_absoluta'] . "';</script>";
?>
