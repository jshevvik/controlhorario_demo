<?php
/**
 * Descarga de Archivos - Sistema Control Horario
 * 
 * Controlador para la descarga segura de archivos del sistema.
 * Maneja tanto archivos de solicitudes como archivos generales
 * del gestor de archivos con verificaciones de seguridad.
 * 
 * @author    Sistema Control Horario  
 * @version   2.0
 * @since     2025-08-02
 */

require_once __DIR__ . '/../../includes/init.php';

// Verificar que el usuario esté autenticado
if (!isset($_SESSION['empleado_id'])) {
    http_response_code(401);
    echo "No autorizado";
    exit;
}

// Verificar que el usuario sea administrador o supervisor para gestor de archivos
// O empleado normal para sus propias solicitudes
$empleado = getEmpleado();
if (!$empleado) {
    http_response_code(403);
    echo "Sin permisos";
    exit;
}

// Modo de descarga: solicitudes (específico) o archivos (general del gestor)
$modo = isset($_GET['solicitud_id']) ? 'solicitudes' : 'archivos';

if ($modo === 'solicitudes') {
    // Lógica original para descargar archivos de solicitudes
    if (!in_array($empleado['rol'], ['admin', 'supervisor'])) {
        http_response_code(403);
        echo "Sin permisos para descargar archivos de solicitudes";
        exit;
    }

    // Verificar parámetros
    if (!isset($_GET['solicitud_id']) || !isset($_GET['archivo'])) {
        http_response_code(400);
        echo "Parámetros requeridos: solicitud_id y archivo";
        exit;
    }

    $solicitudId = $_GET['solicitud_id'];
    $nombreArchivo = $_GET['archivo'];

    // Validar ID de solicitud
    if (!is_numeric($solicitudId)) {
        http_response_code(400);
        echo "ID de solicitud inválido";
        exit;
    }

    try {
        // Verificar que la solicitud existe y el archivo coincide
        $stmt = $pdo->prepare("SELECT archivo FROM solicitudes WHERE id = ?");
        $stmt->execute([$solicitudId]);
        $solicitud = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$solicitud) {
            http_response_code(404);
            echo "Solicitud no encontrada";
            exit;
        }
        
        // Verificar que el archivo solicitado coincide con el de la solicitud
        if ($solicitud['archivo'] !== $nombreArchivo) {
            http_response_code(400);
            echo "Archivo no coincide con la solicitud";
            exit;
        }
        
        // Construir ruta del archivo
        $rutaArchivo = __DIR__ . '/../../uploads/solicitudes/' . $nombreArchivo;
        
        // Verificar que el archivo existe
        if (!file_exists($rutaArchivo)) {
            http_response_code(404);
            echo "Archivo no encontrado en el servidor";
            exit;
        }
        
        // Verificar que es un archivo válido (no directorio)
        if (!is_file($rutaArchivo)) {
            http_response_code(400);
            echo "Ruta no válida";
            exit;
        }
        
        // Obtener información del archivo
        $extension = strtolower(pathinfo($nombreArchivo, PATHINFO_EXTENSION));
        $tamaño = filesize($rutaArchivo);
        
        // Generar nombre amigable para descarga
        $nombreDescarga = "solicitud_{$solicitudId}_" . date('YmdHis') . "." . $extension;
        
        // Procesar descarga
        procesarDescarga($rutaArchivo, $nombreDescarga);
        
        // Log de descarga
        error_log("Archivo descargado: {$nombreArchivo} por usuario ID: {$_SESSION['empleado_id']} para solicitud ID: {$solicitudId}");
        
    } catch (PDOException $e) {
        error_log("Error de BD al descargar archivo: " . $e->getMessage());
        http_response_code(500);
        echo "Error de base de datos";
    } catch (Exception $e) {
        error_log("Error general al descargar archivo: " . $e->getMessage());
        http_response_code(500);
        echo "Error interno del servidor";
    }
    
} else {
    // Lógica para gestor de archivos (nuevo)
    if (!in_array($empleado['rol'], ['admin', 'supervisor'])) {
        http_response_code(403);
        echo "Sin permisos para descargar archivos del gestor";
        exit;
    }

    // Verificar parámetro archivo
    if (!isset($_GET['archivo'])) {
        http_response_code(400);
        echo "Parámetro requerido: archivo";
        exit;
    }

    $rutaRelativaArchivo = $_GET['archivo'];
    
    // Validar que la ruta es segura (no contiene ../ ni rutas absolutas)
    if (strpos($rutaRelativaArchivo, '../') !== false || 
        strpos($rutaRelativaArchivo, '..\\') !== false || 
        substr($rutaRelativaArchivo, 0, 1) === '/' ||
        substr($rutaRelativaArchivo, 1, 1) === ':') {
        http_response_code(400);
        echo "Ruta de archivo no válida";
        exit;
    }
    
    // Validar que está dentro de carpetas permitidas
    $carpetasPermitidas = ['solicitudes', 'usuarios', 'documentos'];
    $partesRuta = explode('/', $rutaRelativaArchivo);
    $carpetaPrincipal = $partesRuta[0] ?? '';
    
    if (!in_array($carpetaPrincipal, $carpetasPermitidas)) {
        http_response_code(400);
        echo "Carpeta no permitida";
        exit;
    }
    
    try {
        // Construir ruta completa del archivo
        $rutaCompleta = __DIR__ . '/../../uploads/' . $rutaRelativaArchivo;
        
        // Verificar que el archivo existe
        if (!file_exists($rutaCompleta)) {
            http_response_code(404);
            echo "Archivo no encontrado";
            exit;
        }
        
        // Verificar que es un archivo válido
        if (!is_file($rutaCompleta)) {
            http_response_code(400);
            echo "Ruta no válida";
            exit;
        }
        
        // Verificar que está dentro del directorio uploads (seguridad adicional)
        $uploadsBase = realpath(__DIR__ . '/../../uploads');
        $archivoReal = realpath($rutaCompleta);
        
        if (strpos($archivoReal, $uploadsBase) !== 0) {
            http_response_code(400);
            echo "Archivo fuera del directorio permitido";
            exit;
        }
        
        // Obtener nombre original del archivo
        $nombreOriginal = basename($rutaRelativaArchivo);
        
        // Procesar descarga
        procesarDescarga($rutaCompleta, $nombreOriginal);
        
        // Log de descarga
        error_log("Archivo descargado del gestor: {$rutaRelativaArchivo} por usuario ID: {$_SESSION['empleado_id']}");
        
    } catch (Exception $e) {
        error_log("Error al descargar archivo del gestor: " . $e->getMessage());
        http_response_code(500);
        echo "Error interno del servidor";
    }
}

/**
 * Función auxiliar para procesar la descarga de archivos
 */
function procesarDescarga($rutaArchivo, $nombreDescarga) {
    // Obtener información del archivo
    $extension = strtolower(pathinfo($rutaArchivo, PATHINFO_EXTENSION));
    $tamaño = filesize($rutaArchivo);
    
    // Definir tipos MIME
    $tiposMime = [
        'pdf' => 'application/pdf',
        'doc' => 'application/msword',
        'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'xls' => 'application/vnd.ms-excel',
        'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png' => 'image/png',
        'gif' => 'image/gif',
        'webp' => 'image/webp',
        'txt' => 'text/plain',
        'csv' => 'text/csv'
    ];
    
    $tipoMime = $tiposMime[$extension] ?? 'application/octet-stream';
    
    // Configurar headers para descarga
    header('Content-Type: ' . $tipoMime);
    header('Content-Disposition: attachment; filename="' . $nombreDescarga . '"');
    header('Content-Length: ' . $tamaño);
    header('Cache-Control: must-revalidate');
    header('Pragma: public');
    
    // Limpiar buffer de salida
    ob_clean();
    flush();
    
    // Enviar archivo
    readfile($rutaArchivo);
}

?>
