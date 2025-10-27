<?php
// Verificar que el usuario esté autenticado
if (!isset($_SESSION['empleado_id'])) {
    header('Location: ' . $config['ruta_absoluta'] . 'login');
    exit;
}

// Verificar que el usuario sea solo administrador
$empleado = getEmpleado();
if (!$empleado || $empleado['rol'] !== 'admin') {
    header('Location: ' . $config['ruta_absoluta'] . 'dashboard');
    exit;
}

// Obtener configuración de la empresa desde geolocalización
$configEmpresa = obtenerGeoConfigEmpleado($_SESSION['empleado_id']);

// Funciones auxiliares para notificaciones
function contarNotificaciones($pdo, $leidas = null) {
    $sql = "SELECT COUNT(*) as total FROM notificaciones";
    $params = [];
    
    if ($leidas !== null) {
        $sql .= " WHERE leido = ?";
        $params[] = $leidas ? 1 : 0;
    }
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return $result['total'] ?? 0;
}

function obtenerFechaMasAntigua($pdo) {
    $stmt = $pdo->query("SELECT MIN(fecha) as fecha_antigua FROM notificaciones");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return $result['fecha_antigua'] ? date('d/m/Y', strtotime($result['fecha_antigua'])) : 'N/A';
}

// Función para obtener valores de configuración
function getConfigValue($pdo, $clave, $valorPorDefecto = '') {
    try {
        $stmt = $pdo->prepare("SELECT valor FROM configuracion WHERE clave = ?");
        $stmt->execute([$clave]);
        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
        return $resultado ? $resultado['valor'] : $valorPorDefecto;
    } catch (Exception $e) {
        return $valorPorDefecto;
    }
}

// Procesar formularios si se envían
$mensaje = '';
$tipoMensaje = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'agregar_festivo':
                // Lógica para añadir festivo
                $fecha = $_POST['fecha_festivo'] ?? '';
                $nombre = $_POST['nombre_festivo'] ?? '';
                $alcance = $_POST['alcance_festivo'] ?? 'nacional';
                $region = $_POST['region_festivo'] ?? '';
                
                if ($fecha && $nombre) {
                    try {
                        $stmt = $pdo->prepare("INSERT INTO festivos (fecha, nombre, alcance, region) VALUES (?, ?, ?, ?)");
                        $stmt->execute([$fecha, $nombre, $alcance, $region]);
                        $mensaje = "Festivo añadido correctamente";
                        $tipoMensaje = "success";
                    } catch (Exception $e) {
                        $mensaje = "Error al añadir festivo: " . $e->getMessage();
                        $tipoMensaje = "danger";
                    }
                } else {
                    $mensaje = "Fecha y nombre son obligatorios";
                    $tipoMensaje = "warning";
                }
                break;

            case 'eliminar_notificaciones':
                // Lógica para eliminar notificaciones antiguas
                $dias = (int)($_POST['dias_antiguedad'] ?? 30);
                $soloLeidas = isset($_POST['solo_leidas']);
                
                try {
                    $sql = "DELETE FROM notificaciones WHERE fecha < DATE_SUB(NOW(), INTERVAL ? DAY)";
                    $params = [$dias];
                    
                    if ($soloLeidas) {
                        $sql .= " AND leido = 1";
                    }
                    
                    // Filtrar por tipo si se especifica
                    $tipo = $_POST['tipo_notificacion'] ?? '';
                    if ($tipo) {
                        $sql .= " AND tipo = ?";
                        $params[] = $tipo;
                    }
                    
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute($params);
                    
                    $eliminadas = $stmt->rowCount();
                    $mensaje = "Se eliminaron {$eliminadas} notificaciones";
                    if ($soloLeidas) {
                        $mensaje .= " leídas";
                    }
                    $mensaje .= " anteriores a {$dias} días.";
                    $tipoMensaje = "success";
                } catch (Exception $e) {
                    $mensaje = "Error al eliminar notificaciones: " . $e->getMessage();
                    $tipoMensaje = "danger";
                }
                break;

            case 'editar_festivo':
                // Lógica para editar festivo
                $id = $_POST['id_festivo'] ?? '';
                $fecha = $_POST['fecha_festivo_edit'] ?? '';
                $nombre = $_POST['nombre_festivo_edit'] ?? '';
                $alcance = $_POST['alcance_festivo_edit'] ?? 'nacional';
                break;

            case 'actualizar_empresa':
                // Lógica para actualizar datos de la empresa
                $nombre_empresa = $_POST['nombre_empresa'] ?? '';
                $direccion_empresa = $_POST['direccion_empresa'] ?? '';
                $latitud = $_POST['latitud'] ?? '';
                $longitud = $_POST['longitud'] ?? '';
                $radio = $_POST['radio'] ?? '';
                $geolocalizacion_requerida = isset($_POST['geolocalizacion_requerida']) ? 1 : 0;
                
                if ($nombre_empresa && $latitud !== '' && $longitud !== '' && $radio !== '') {
                    try {
                        // Actualizar datos básicos
                        $stmt = $pdo->prepare("UPDATE configuracion SET valor = ? WHERE clave = 'nombre_empresa'");
                        $stmt->execute([$nombre_empresa]);
                        
                        $stmt = $pdo->prepare("UPDATE configuracion SET valor = ? WHERE clave = 'direccion_empresa'");
                        $stmt->execute([$direccion_empresa]);
                        
                        // Actualizar configuración de geolocalización
                        $stmt = $pdo->prepare("UPDATE configuracion_geolocalizacion SET 
                            nombre_ubicacion = ?,
                            latitud_oficina = ?,
                            longitud_oficina = ?,
                            radio_permitido = ?,
                            geolocalizacion_requerida = ?
                            WHERE empleado_id IS NULL");
                        $stmt->execute([
                            $nombre_empresa,
                            $latitud,
                            $longitud,
                            $radio,
                            $geolocalizacion_requerida
                        ]);

                        // Si no se actualizó ninguna fila, insertar nuevo registro
                        if ($stmt->rowCount() === 0) {
                            $stmt = $pdo->prepare("INSERT INTO configuracion_geolocalizacion 
                                (nombre_ubicacion, latitud_oficina, longitud_oficina, radio_permitido, geolocalizacion_requerida)
                                VALUES (?, ?, ?, ?, ?)");
                            $stmt->execute([
                                $nombre_empresa,
                                $latitud,
                                $longitud,
                                $radio,
                                $geolocalizacion_requerida
                            ]);
                        }
                        
                        $mensaje = "Datos de la empresa actualizados correctamente";
                        $tipoMensaje = "success";
                    } catch (Exception $e) {
                        $mensaje = "Error al actualizar datos de la empresa: " . $e->getMessage();
                        $tipoMensaje = "danger";
                    }
                } else {
                    $mensaje = "Todos los campos marcados con * son obligatorios";
                    $tipoMensaje = "warning";
                }
                $region = $_POST['region_festivo_edit'] ?? '';
                
                if ($id && $fecha && $nombre) {
                    try {
                        $stmt = $pdo->prepare("UPDATE festivos SET fecha = ?, nombre = ?, alcance = ?, region = ? WHERE id = ?");
                        $stmt->execute([$fecha, $nombre, $alcance, $region, $id]);
                        $mensaje = "Festivo actualizado correctamente";
                        $tipoMensaje = "success";
                    } catch (Exception $e) {
                        $mensaje = "Error al actualizar festivo: " . $e->getMessage();
                        $tipoMensaje = "danger";
                    }
                } else {
                    $mensaje = "Todos los campos son obligatorios";
                    $tipoMensaje = "warning";
                }
                break;
                
            case 'agregar_evento':
                // Lógica para añadir evento
                $fecha_inicio = $_POST['fecha_inicio_evento'] ?? '';
                $fecha_fin = $_POST['fecha_fin_evento'] ?? '';
                $titulo = $_POST['titulo_evento'] ?? '';
                $descripcion = $_POST['descripcion_evento'] ?? '';
                $color = $_POST['color_evento'] ?? '#007bff';
                $empleado_id = $_POST['empleado_evento'] ?? null;
                
                // Si empleado_id es 'todos', se guarda como NULL para indicar que es para todos
                if ($empleado_id === 'todos') {
                    $empleado_id = null;
                }
                
                if ($fecha_inicio && $fecha_fin && $titulo) {
                    try {
                        $stmt = $pdo->prepare("INSERT INTO eventos_calendario (fecha_inicio, fecha_fin, titulo, descripcion, color, tipo, empleado_id) VALUES (?, ?, ?, ?, ?, 'evento', ?)");
                        $stmt->execute([$fecha_inicio, $fecha_fin, $titulo, $descripcion, $color, $empleado_id]);
                        $mensaje = "Evento añadido correctamente";
                        $tipoMensaje = "success";
                    } catch (Exception $e) {
                        $mensaje = "Error al añadir evento: " . $e->getMessage();
                        $tipoMensaje = "danger";
                    }
                } else {
                    $mensaje = "Fechas y título son obligatorios";
                    $tipoMensaje = "warning";
                }
                break;
        }
    }
}

// Obtener festivos existentes
try {
    $festivos = $pdo->query("SELECT * FROM festivos ORDER BY fecha DESC")->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $festivos = [];
}

// Obtener empleados para el selector
try {
    // Intentar diferentes variantes de la consulta para empleados activos
    $sql_empleados = "SELECT id, nombre, apellidos FROM empleados";
    
    // Verificar si existe columna 'activo'
    $columns = $pdo->query("SHOW COLUMNS FROM empleados")->fetchAll(PDO::FETCH_COLUMN);
    if (in_array('activo', $columns)) {
        $sql_empleados .= " WHERE activo = 1";
    } elseif (in_array('estado', $columns)) {
        $sql_empleados .= " WHERE estado = 'activo'";
    }
    
    $sql_empleados .= " ORDER BY nombre, apellidos";
    
    $empleados = $pdo->query($sql_empleados)->fetchAll(PDO::FETCH_ASSOC);
    
    // Debug: verificar si hay empleados
    if (empty($empleados)) {
        error_log("No se encontraron empleados en la base de datos. SQL usado: " . $sql_empleados);
    }
} catch (Exception $e) {
    error_log("Error al obtener empleados: " . $e->getMessage());
    $empleados = [];
}

// Obtener eventos existentes con información del empleado
try {
    $eventos = $pdo->query("
        SELECT e.*, 
               CASE 
                   WHEN e.empleado_id IS NULL THEN 'Todos los empleados'
                   ELSE CONCAT(emp.nombre, ' ', emp.apellidos)
               END as empleado_nombre
        FROM eventos_calendario e 
        LEFT JOIN empleados emp ON e.empleado_id = emp.id 
        WHERE e.tipo = 'evento' 
        ORDER BY e.fecha_inicio DESC
    ")->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $eventos = [];
}
?>

<div class="container-fluid py-4 admin-configuracion">
    <!-- Breadcrumb -->
    <div class="mb-3 overflow-hidden">
        <div class="px-3">
            <h4 class="fs-6 mb-0">Configuración</h4>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item">
                        <a href="<?= $config['ruta_absoluta'] ?>dashboard">Inicio</a>
                    </li>
                    <li class="breadcrumb-item">
                        <a href="<?= $config['ruta_absoluta'] ?>administracion">Administración</a>
                    </li>
                    <li class="breadcrumb-item active" aria-current="page">Configuración</li>
                </ol>
            </nav>
        </div>
    </div>

    <!-- Header -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-4">
        
    </div>

    <!-- Mensaje de estado -->
    <?php if ($mensaje): ?>
    <div class="alert alert-<?= $tipoMensaje ?> alert-dismissible fade show" role="alert">
        <?= htmlspecialchars($mensaje) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php endif; ?>

    <!-- Tabs de navegación -->
    <ul class="nav nav-tabs mb-4 nav-tabs-responsive" id="configTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="festivos-tab" data-bs-toggle="tab" data-bs-target="#festivos" type="button" role="tab">
                <i class="bi bi-calendar-event me-1 me-md-2"></i>
                <span class="d-none d-sm-inline">Festivos</span>
                <span class="d-sm-none">F</span>
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="eventos-tab" data-bs-toggle="tab" data-bs-target="#eventos" type="button" role="tab">
                <i class="bi bi-calendar-plus me-1 me-md-2"></i>
                <span class="d-none d-sm-inline">Eventos</span>
                <span class="d-sm-none">E</span>
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="general-tab" data-bs-toggle="tab" data-bs-target="#general" type="button" role="tab">
                <i class="bi bi-gear me-1 me-md-2"></i>
                <span class="d-none d-sm-inline">General</span>
                <span class="d-sm-none">G</span>
            </button>
        </li>
    </ul>

    <!-- Contenido de tabs -->
    <div class="tab-content" id="configTabContent">
        
        <!-- Tab Festivos -->
        <div class="tab-pane fade show active" id="festivos" role="tabpanel">
            <div class="row g-3 g-lg-4">
                
                <!-- Formulario añadir festivo -->
                <div class="col-12 col-xl-5 order-2 order-xl-1">
                    <div class="card shadow-sm">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0 fs-6 fs-md-5">
                                <i class="bi bi-plus-circle me-2"></i>
                                Añadir Festivo
                            </h5>
                        </div>
                        <div class="card-body p-3 p-md-4">
                            <form method="POST">
                                <input type="hidden" name="action" value="agregar_festivo">
                                
                                <div class="mb-3">
                                    <label for="fecha_festivo" class="form-label">Fecha <span class="text-danger">*</span></label>
                                    <input type="date" class="form-control form-control-lg" id="fecha_festivo" name="fecha_festivo" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="nombre_festivo" class="form-label">Nombre <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control form-control-lg" id="nombre_festivo" name="nombre_festivo" 
                                           placeholder="Ej: Día de Año Nuevo" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="alcance_festivo" class="form-label">Alcance <span class="text-danger">*</span></label>
                                    <select class="form-select form-select-lg" id="alcance_festivo" name="alcance_festivo" required>
                                        <option value="nacional" selected>Nacional</option>
                                        <option value="autonomico">Autonómico</option>
                                        <option value="local">Local</option>
                                    </select>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="region_festivo" class="form-label">Región</label>
                                    <input type="text" class="form-control form-control-lg" id="region_festivo" name="region_festivo" 
                                           placeholder="Ej: Andalucía, Madrid, Valencia..." 
                                           title="Especifica la región para festivos autonómicos o locales">
                                    <div class="form-text">Solo necesario para festivos autonómicos o locales.</div>
                                </div>
                                
                                <button type="submit" class="btn btn-primary w-100 btn-lg">
                                    <i class="bi bi-check-circle me-2"></i>
                                    Añadir Festivo
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
                
                <!-- Lista de festivos -->
                <div class="col-12 col-xl-7 order-1 order-xl-2">
                    <div class="card shadow-sm">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0 fs-6 fs-md-5">
                                <i class="bi bi-list me-2"></i>
                                Festivos Registrados
                                <?php if (!empty($festivos)): ?>
                                    <span class="badge bg-light text-primary ms-2"><?= count($festivos) ?></span>
                                <?php endif; ?>
                            </h5>
                        </div>
                        <div class="card-body p-2 p-md-4">
                            <?php if (empty($festivos)): ?>
                            <div class="text-center py-4">
                                <i class="bi bi-calendar-x display-6 display-md-4 text-muted"></i>
                                <h4 class="text-muted mt-3 fs-5 fs-md-4">No hay festivos registrados</h4>
                                <p class="text-muted">Añade festivos usando el formulario.</p>
                            </div>
                            <?php else: ?>
                            
                            <!-- Vista móvil (cards) -->
                            <div class="d-md-none">
                                <?php foreach ($festivos as $festivo): ?>
                                <div class="card mb-3 border-start border-4 border-primary">
                                    <div class="card-body p-3">
                                        <div class="d-flex justify-content-between align-items-start mb-2">
                                            <h6 class="card-title mb-1"><?= htmlspecialchars($festivo['nombre']) ?></h6>
                                            <div class="dropdown">
                                                <button class="btn btn-sm btn-outline-secondary" type="button" data-bs-toggle="dropdown">
                                                    <i class="bi bi-three-dots-vertical"></i>
                                                </button>
                                                <ul class="dropdown-menu">
                                                    <li>
                                                        <a class="dropdown-item" href="#" onclick="editarFestivo(<?= $festivo['id'] ?>, '<?= $festivo['fecha'] ?>', '<?= addslashes($festivo['nombre']) ?>', '<?= $festivo['alcance'] ?>', '<?= addslashes($festivo['region'] ?? '') ?>')">
                                                            <i class="bi bi-pencil me-2"></i>Editar
                                                        </a>
                                                    </li>
                                                    <li>
                                                        <a class="dropdown-item text-danger" href="#" onclick="eliminarFestivo(<?= $festivo['id'] ?>)">
                                                            <i class="bi bi-trash me-2"></i>Eliminar
                                                        </a>
                                                    </li>
                                                </ul>
                                            </div>
                                        </div>
                                        <p class="card-text mb-2">
                                            <strong>📅 <?= date('d/m/Y', strtotime($festivo['fecha'])) ?></strong>
                                        </p>
                                        <div class="d-flex justify-content-between align-items-center">
                                            <span class="badge <?= $festivo['alcance'] === 'nacional' ? 'bg-primary' : ($festivo['alcance'] === 'autonomico' ? 'bg-info' : 'bg-secondary') ?>">
                                                <?= ucfirst($festivo['alcance']) ?>
                                            </span>
                                            <?php if (!empty($festivo['region'])): ?>
                                                <small class="text-muted"><?= htmlspecialchars($festivo['region']) ?></small>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            
                            <!-- Vista desktop (tabla) -->
                            <div class="d-none d-md-block">
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>Fecha</th>
                                                <th>Nombre</th>
                                                <th>Alcance</th>
                                                <th>Región</th>
                                                <th width="120">Acciones</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                        <?php foreach ($festivos as $festivo): ?>
                                        <tr>
                                            <td><?= date('d/m/Y', strtotime($festivo['fecha'])) ?></td>
                                            <td><?= htmlspecialchars($festivo['nombre']) ?></td>
                                            <td>
                                                <span class="badge <?= $festivo['alcance'] === 'nacional' ? 'bg-primary' : ($festivo['alcance'] === 'autonomico' ? 'bg-info' : 'bg-secondary') ?>">
                                                    <?= ucfirst($festivo['alcance']) ?>
                                                </span>
                                            </td>
                                            <td><?= htmlspecialchars($festivo['region'] ?? '-') ?></td>
                                            <td>
                                                <div class="btn-group" role="group">
                                                    <button class="btn btn-sm btn-primary" onclick="editarFestivo(<?= $festivo['id'] ?>, '<?= $festivo['fecha'] ?>', '<?= addslashes($festivo['nombre']) ?>', '<?= $festivo['alcance'] ?>', '<?= addslashes($festivo['region'] ?? '') ?>')" title="Editar">
                                                        <i class="bi bi-pencil"></i>
                                                    </button>
                                                    <button class="btn btn-sm btn-danger" onclick="eliminarFestivo(<?= $festivo['id'] ?>)" title="Eliminar">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Tab Eventos -->
        <div class="tab-pane fade" id="eventos" role="tabpanel">
            <div class="row g-3 g-lg-4">

                <!-- Formulario añadir evento -->
                <div class="col-12 col-xl-5 order-2 order-xl-1">
                    <div class="card shadow-sm">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0 fs-6 fs-md-5">
                                <i class="bi bi-plus-circle me-2"></i>
                                Añadir Evento
                            </h5>
                        </div>
                        <div class="card-body p-3 p-md-4">
                            <form method="POST">
                                <input type="hidden" name="action" value="agregar_evento">
                                
                                <div class="mb-3">
                                    <label for="fecha_inicio_evento" class="form-label">Fecha Inicio <span class="text-danger">*</span></label>
                                    <input type="date" class="form-control form-control-lg" id="fecha_inicio_evento" name="fecha_inicio_evento" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="fecha_fin_evento" class="form-label">Fecha Fin <span class="text-danger">*</span></label>
                                    <input type="date" class="form-control form-control-lg" id="fecha_fin_evento" name="fecha_fin_evento" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="titulo_evento" class="form-label">Título <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control form-control-lg" id="titulo_evento" name="titulo_evento" 
                                           placeholder="Ej: Mantenimiento del sistema" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="descripcion_evento" class="form-label">Descripción</label>
                                    <textarea class="form-control form-control-lg" id="descripcion_evento" name="descripcion_evento" 
                                              rows="3" placeholder="Descripción del evento"></textarea>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="empleado_evento" class="form-label">Asignar a</label>
                                    <select class="form-select form-select-lg" id="empleado_evento" name="empleado_evento">
                                        <option value="todos" selected>Todos los empleados</option>
                                        <?php if (!empty($empleados)): ?>
                                            <?php foreach ($empleados as $emp): ?>
                                            <option value="<?= $emp['id'] ?>"><?= htmlspecialchars($emp['nombre'] . ' ' . $emp['apellidos']) ?></option>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <option disabled>No hay empleados disponibles</option>
                                        <?php endif; ?>
                                    </select>
                                    <div class="form-text">
                                        Selecciona si el evento es para un empleado específico o para todos.
                                        <?php if (empty($empleados)): ?>
                                            <br><small class="text-warning">⚠️ No se encontraron empleados activos.</small>
                                        <?php else: ?>
                                            <br><small class="text-muted"><?= count($empleados) ?> empleado(s) disponible(s).</small>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="color_evento" class="form-label">Color</label>
                                    <input type="color" class="form-control form-control-color form-control-lg" id="color_evento" name="color_evento" value="#007bff">
                                </div>
                                
                                <button type="submit" class="btn btn-primary w-100 btn-lg">
                                    <i class="bi bi-check-circle me-2"></i>
                                    Añadir Evento
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
                
                <!-- Lista de eventos -->
                <div class="col-12 col-xl-7 order-1 order-xl-2">
                    <div class="card shadow-sm">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0 fs-6 fs-md-5">
                                <i class="bi bi-list me-2"></i>
                                Eventos Registrados
                                <?php if (!empty($eventos)): ?>
                                    <span class="badge bg-light text-primary ms-2"><?= count($eventos) ?></span>
                                <?php endif; ?>
                            </h5>
                        </div>
                        <div class="card-body p-2 p-md-4">
                            <?php if (empty($eventos)): ?>
                            <div class="text-center py-4">
                                <i class="bi bi-calendar-plus display-6 display-md-4 text-muted"></i>
                                <h4 class="text-muted mt-3 fs-5 fs-md-4">No hay eventos registrados</h4>
                                <p class="text-muted">Añade eventos usando el formulario.</p>
                            </div>
                            <?php else: ?>
                            
                            <!-- Vista móvil (cards) -->
                            <div class="d-lg-none">
                                <?php foreach ($eventos as $evento): ?>
                                <div class="card mb-3 border-start border-4" style="border-color: <?= htmlspecialchars($evento['color']) ?> !important;">
                                    <div class="card-body p-3">
                                        <div class="d-flex justify-content-between align-items-start mb-2">
                                            <h6 class="card-title mb-1"><?= htmlspecialchars($evento['titulo']) ?></h6>
                                            <button class="btn btn-sm btn-outline-danger" onclick="eliminarEvento(<?= $evento['id'] ?>)" title="Eliminar">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </div>
                                        
                                        <p class="card-text mb-2">
                                            <strong>📅 
                                                <?= date('d/m/Y', strtotime($evento['fecha_inicio'])) ?>
                                                <?php if ($evento['fecha_inicio'] !== $evento['fecha_fin']): ?>
                                                    - <?= date('d/m/Y', strtotime($evento['fecha_fin'])) ?>
                                                <?php endif; ?>
                                            </strong>
                                        </p>
                                        
                                        <?php if (!empty($evento['descripcion'])): ?>
                                            <p class="card-text small text-muted mb-2">
                                                <?= htmlspecialchars($evento['descripcion']) ?>
                                            </p>
                                        <?php endif; ?>
                                        
                                        <div class="d-flex justify-content-between align-items-center">
                                            <span class="badge <?= $evento['empleado_id'] ? 'bg-info' : 'bg-success' ?>">
                                                <?= htmlspecialchars($evento['empleado_nombre']) ?>
                                            </span>
                                            <div class="color-indicator rounded-circle" style="width: 20px; height: 20px; background-color: <?= htmlspecialchars($evento['color']) ?>;"></div>
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            
                            <!-- Vista desktop (tabla) -->
                            <div class="d-none d-lg-block">
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>Fechas</th>
                                                <th>Título</th>
                                                <th>Descripción</th>
                                                <th>Asignado a</th>
                                                <th width="120">Acciones</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                        <?php foreach ($eventos as $evento): ?>
                                        <tr>
                                            <td>
                                                <?= date('d/m/Y', strtotime($evento['fecha_inicio'])) ?>
                                                <?php if ($evento['fecha_inicio'] !== $evento['fecha_fin']): ?>
                                                    - <?= date('d/m/Y', strtotime($evento['fecha_fin'])) ?>
                                                <?php endif; ?>
                                            </td>
                                            <td><?= htmlspecialchars($evento['titulo']) ?></td>
                                            <td>
                                                <span class="d-inline-block text-truncate" style="max-width: 200px;" title="<?= htmlspecialchars($evento['descripcion']) ?>">
                                                    <?= htmlspecialchars($evento['descripcion']) ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge <?= $evento['empleado_id'] ? 'bg-info' : 'bg-success' ?>">
                                                    <?= htmlspecialchars($evento['empleado_nombre']) ?>
                                                </span>
                                            </td>
                                            <td>
                                                <button class="btn btn-sm btn-danger" onclick="eliminarEvento(<?= $evento['id'] ?>)" title="Eliminar">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Tab General -->
        <div class="tab-pane fade" id="general" role="tabpanel">
            <div class="row g-4">
                <div class="col-12">
                    <div class="card shadow-sm">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0"><i class="bi bi-gear me-2"></i>Configuración General</h5>
                        </div>
                        <div class="card-body">
                            <div class="alert alert-info">
                                <i class="bi bi-info-circle me-2"></i>
                                Esta sección estará disponible en futuras versiones del sistema.
                            </div>
                            
                            <!-- Formulario de datos de la empresa -->
                            <div class="card shadow-sm mb-4">
                                <div class="card-header bg-primary text-white">
                                    <h6 class="mb-0"><i class="bi bi-building me-2"></i>Datos de la Empresa</h6>
                                </div>
                                <div class="card-body">
                                    <form method="POST">
                                        <input type="hidden" name="action" value="actualizar_empresa">
                                        
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label for="nombre_empresa" class="form-label">Nombre de la Empresa <span class="text-danger">*</span></label>
                                                    <input type="text" class="form-control" id="nombre_empresa" name="nombre_empresa" 
                                                           value="<?= htmlspecialchars(getConfigValue($pdo, 'nombre_empresa', $configEmpresa['nombre_ubicacion'])) ?>" required>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label for="direccion_empresa" class="form-label">Dirección <span class="text-danger">*</span></label>
                                                    <textarea class="form-control" id="direccion_empresa" name="direccion_empresa" 
                                                            rows="3" required><?= htmlspecialchars(getConfigValue($pdo, 'direccion_empresa', '')) ?></textarea>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="row">
                                            <div class="col-md-4">
                                                <div class="mb-3">
                                                    <label for="latitud" class="form-label">Latitud <span class="text-danger">*</span></label>
                                                    <input type="number" step="any" class="form-control" id="latitud" name="latitud" 
                                                           value="<?= htmlspecialchars($configEmpresa['latitud_oficina']) ?>" required>
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="mb-3">
                                                    <label for="longitud" class="form-label">Longitud <span class="text-danger">*</span></label>
                                                    <input type="number" step="any" class="form-control" id="longitud" name="longitud" 
                                                           value="<?= htmlspecialchars($configEmpresa['longitud_oficina']) ?>" required>
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="mb-3">
                                                    <label for="radio" class="form-label">Radio permitido (metros) <span class="text-danger">*</span></label>
                                                    <input type="number" class="form-control" id="radio" name="radio" 
                                                           value="<?= htmlspecialchars($configEmpresa['radio_permitido']) ?>" required>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="form-check mb-3">
                                            <input class="form-check-input" type="checkbox" id="geolocalizacion_requerida" name="geolocalizacion_requerida" 
                                                   value="1" <?= $configEmpresa['geolocalizacion_requerida'] ? 'checked' : '' ?>>
                                            <label class="form-check-label" for="geolocalizacion_requerida">
                                                Requerir geolocalización para fichar
                                            </label>
                                        </div>
                                        
                                        <div class="text-end">
                                            <button type="submit" class="btn btn-primary">
                                                <i class="bi bi-save me-2"></i>Guardar Cambios
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>

                            <!-- Gestión de Notificaciones -->
                            <div class="card shadow-sm mb-4">
                                <div class="card-header bg-primary text-white">
                                    <h6 class="mb-0"><i class="bi bi-bell me-2"></i>Gestión de Notificaciones</h6>
                                </div>
                                <div class="card-body">
                                    <form method="POST" onsubmit="return confirm('¿Estás seguro de que deseas eliminar las notificaciones seleccionadas? Esta acción no se puede deshacer.')">
                                        <input type="hidden" name="action" value="eliminar_notificaciones">
                                        
                                        <div class="row g-3">
                                            <div class="col-md-4">
                                                <label for="dias_antiguedad" class="form-label">Eliminar notificaciones anteriores a</label>
                                                <select class="form-select" id="dias_antiguedad" name="dias_antiguedad" required>
                                                    <option value="7">7 días</option>
                                                    <option value="15">15 días</option>
                                                    <option value="30">30 días</option>
                                                    <option value="60">60 días</option>
                                                    <option value="90">90 días</option>
                                                    <option value="180">6 meses</option>
                                                    <option value="365">1 año</option>
                                                </select>
                                            </div>
                                            <div class="col-md-4">
                                                <label for="tipo_notificacion" class="form-label">Tipo de notificación</label>
                                                <select class="form-select" id="tipo_notificacion" name="tipo_notificacion">
                                                    <option value="">Todos los tipos</option>
                                                    <option value="info">Información</option>
                                                    <option value="solicitud">Solicitudes</option>
                                                    <option value="aprobacion">Aprobaciones</option>
                                                    <option value="alerta">Alertas</option>
                                                </select>
                                            </div>
                                            <div class="col-md-4">
                                                <label class="form-label d-block">Opciones adicionales</label>
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" id="solo_leidas" name="solo_leidas" value="1" checked>
                                                    <label class="form-check-label" for="solo_leidas">
                                                        Solo eliminar notificaciones leídas
                                                    </label>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="alert alert-info mt-4">
                                            <h6 class="alert-heading mb-2"><i class="bi bi-info-circle me-2"></i>Resumen de notificaciones</h6>
                                            <div class="row g-2 small">
                                                <div class="col-md-3">
                                                    <strong>Total:</strong> <?= contarNotificaciones($pdo) ?>
                                                </div>
                                                <div class="col-md-3">
                                                    <strong>Leídas:</strong> <?= contarNotificaciones($pdo, true) ?>
                                                </div>
                                                <div class="col-md-3">
                                                    <strong>No leídas:</strong> <?= contarNotificaciones($pdo, false) ?>
                                                </div>
                                                <div class="col-md-3">
                                                    <strong>Más antiguas:</strong> <?= obtenerFechaMasAntigua($pdo) ?>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="alert alert-warning mt-3">
                                            <i class="bi bi-exclamation-triangle me-2"></i>
                                            Esta acción eliminará permanentemente las notificaciones seleccionadas. Asegúrese de que ya no necesita esta información.
                                        </div>

                                        <div class="text-end">
                                            <button type="submit" class="btn btn-danger">
                                                <i class="bi bi-trash me-2"></i>Eliminar Notificaciones
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>

                            <!-- Otras configuraciones -->
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="card shadow-sm">
                                        <div class="card-body">
                                            <h6 class="fw-bold">Notificaciones</h6>
                                            <p class="text-muted">Configurar alertas y notificaciones del sistema</p>
                                            <small class="text-muted">Disponible en futuras versiones</small>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="card shadow-sm">
                                        <div class="card-body">
                                            <h6 class="fw-bold">Apariencia</h6>
                                            <p class="text-muted">Personalizar colores y tema del sistema</p>
                                            <small class="text-muted">Disponible en futuras versiones</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal para editar festivo -->
<div class="modal fade" id="editarFestivoModal" tabindex="-1" aria-labelledby="editarFestivoModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fs-6 fs-md-5" id="editarFestivoModalLabel">
                    <i class="bi bi-pencil-square me-2"></i>
                    Editar Festivo
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" id="editarFestivoForm">
                <div class="modal-body p-3 p-md-4">
                    <input type="hidden" name="action" value="editar_festivo">
                    <input type="hidden" name="id_festivo" id="id_festivo_edit">
                    
                    <div class="mb-3">
                        <label for="fecha_festivo_edit" class="form-label">Fecha <span class="text-danger">*</span></label>
                        <input type="date" class="form-control form-control-lg" id="fecha_festivo_edit" name="fecha_festivo_edit" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="nombre_festivo_edit" class="form-label">Nombre <span class="text-danger">*</span></label>
                        <input type="text" class="form-control form-control-lg" id="nombre_festivo_edit" name="nombre_festivo_edit" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="alcance_festivo_edit" class="form-label">Alcance <span class="text-danger">*</span></label>
                        <select class="form-select form-select-lg" id="alcance_festivo_edit" name="alcance_festivo_edit" required>
                            <option value="nacional">Nacional</option>
                            <option value="autonomico">Autonómico</option>
                            <option value="local">Local</option>
                        </select>
                    </div>
                    
                    <div class="mb-3" id="region_container_edit">
                        <label for="region_festivo_edit" class="form-label">Región</label>
                        <input type="text" class="form-control form-control-lg" id="region_festivo_edit" name="region_festivo_edit" 
                               placeholder="Ej: Andalucía, Madrid, Valencia...">
                        <div class="form-text">Solo necesario para festivos autonómicos o locales.</div>
                    </div>
                </div>
                <div class="modal-footer p-3 p-md-4">
                    <button type="button" class="btn btn-secondary btn-lg flex-fill me-2" data-bs-dismiss="modal">
                        <i class="bi bi-x-circle me-2"></i>
                        Cancelar
                    </button>
                    <button type="submit" class="btn btn-primary btn-lg flex-fill">
                        <i class="bi bi-check-circle me-2"></i>
                        Guardar Cambios
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function eliminarFestivo(id) {
    if (confirm('¿Estás seguro de que deseas eliminar este festivo?')) {
        // Implementar AJAX para eliminar festivo
        fetch('<?= $config['ruta_absoluta'] ?>acciones/eliminar-festivo.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({id: id})
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Error al eliminar festivo: ' + data.error);
            }
        })
        .catch(error => {
            alert('Error: ' + error);
        });
    }
}

function editarFestivo(id, fecha, nombre, alcance, region) {
    // Llenar el modal con los datos del festivo
    document.getElementById('id_festivo_edit').value = id;
    document.getElementById('fecha_festivo_edit').value = fecha;
    document.getElementById('nombre_festivo_edit').value = nombre;
    document.getElementById('alcance_festivo_edit').value = alcance;
    document.getElementById('region_festivo_edit').value = region;
    
    // Mostrar/ocultar campo de región según el alcance
    const regionContainer = document.getElementById('region_container_edit');
    const regionField = document.getElementById('region_festivo_edit');
    
    if (alcance === 'nacional') {
        regionContainer.style.display = 'none';
        regionField.removeAttribute('required');
    } else {
        regionContainer.style.display = 'block';
        regionField.setAttribute('required', 'required');
    }
    
    // Mostrar el modal
    const modal = new bootstrap.Modal(document.getElementById('editarFestivoModal'));
    modal.show();
}

function eliminarEvento(id) {
    if (confirm('¿Estás seguro de que deseas eliminar este evento?')) {
        // Implementar AJAX para eliminar evento
        fetch('<?= $config['ruta_absoluta'] ?>acciones/eliminar-evento.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({id: id})
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Error al eliminar evento: ' + data.error);
            }
        })
        .catch(error => {
            alert('Error: ' + error);
        });
    }
}

// Sincronizar fecha fin con fecha inicio
document.getElementById('fecha_inicio_evento').addEventListener('change', function() {
    const fechaFin = document.getElementById('fecha_fin_evento');
    if (!fechaFin.value || fechaFin.value < this.value) {
        fechaFin.value = this.value;
    }
});

// Mostrar/ocultar campo de región según el alcance seleccionado
document.getElementById('alcance_festivo').addEventListener('change', function() {
    const regionField = document.getElementById('region_festivo');
    const regionContainer = regionField.closest('.mb-3');
    
    if (this.value === 'nacional') {
        regionField.value = '';
        regionField.removeAttribute('required');
        regionContainer.style.display = 'none';
    } else {
        regionField.setAttribute('required', 'required');
        regionContainer.style.display = 'block';
    }
});

// Mostrar/ocultar campo de región en el modal de edición
document.getElementById('alcance_festivo_edit').addEventListener('change', function() {
    const regionField = document.getElementById('region_festivo_edit');
    const regionContainer = document.getElementById('region_container_edit');
    
    if (this.value === 'nacional') {
        regionField.value = '';
        regionField.removeAttribute('required');
        regionContainer.style.display = 'none';
    } else {
        regionField.setAttribute('required', 'required');
        regionContainer.style.display = 'block';
    }
});

// Configuración inicial
document.addEventListener('DOMContentLoaded', function() {
    document.getElementById('alcance_festivo').dispatchEvent(new Event('change'));
});
</script>

