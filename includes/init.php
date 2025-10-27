<?php
// Errores: verbose en local, silencioso en prod
$__isLocal = in_array($_SERVER['SERVER_NAME'] ?? '', ['localhost','127.0.0.1','192.168.0.100']);
if ($__isLocal) { error_reporting(E_ALL); ini_set('display_errors','1'); }
else { error_reporting(E_ALL & ~E_NOTICE & ~E_STRICT & ~E_DEPRECATED); ini_set('display_errors','0'); }

// Sesión con cookies seguras (HTTPS/proxy)
if (session_status() === PHP_SESSION_NONE) {
    $proto = $_SERVER['HTTP_X_FORWARDED_PROTO'] ?? null;
    $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (is_string($proto) && stripos($proto,'https')!==false);
    session_set_cookie_params(['lifetime'=>0,'path'=>'/','domain'=>'','secure'=>$isHttps,'httponly'=>true,'samesite'=>'Lax']);
    session_start();
}

// Cargar config local si existe; en prod usar ENV
$config = $config ?? [];
if (file_exists(__DIR__ . '/../config.php')) {
    require_once __DIR__ . '/../config.php';
} else {
    $scheme = $_SERVER['HTTP_X_FORWARDED_PROTO'] ?? ($_SERVER['REQUEST_SCHEME'] ?? 'https');
    $host   = $_SERVER['HTTP_X_FORWARDED_HOST']  ?? ($_SERVER['HTTP_HOST'] ?? 'localhost');
    $scriptDir = rtrim(str_replace('\\','/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/');
    $pathPart  = ($scriptDir === '' || $scriptDir === '/') ? '' : $scriptDir;
    $baseUrlEnv = getenv('BASE_URL') ?: ($scheme . '://' . $host . $pathPart . '/');

    $config['ruta_absoluta'] = rtrim($baseUrlEnv,'/') . '/';
    $config['carpeta']       = rtrim(str_replace('\\','/', dirname(__DIR__)), '/') . '/';
    $config['ruta_server']   = $config['carpeta'] . 'public/';
    $config['ASSET_URL']     = $config['ruta_absoluta'] . 'assets/';
    $config['UPLOADS_URL']   = $config['ruta_absoluta'] . 'uploads/usuarios/';
    $config['UPLOADS_DIR']   = getenv('UPLOADS_DIR') ?: ($config['carpeta'] . 'uploads/usuarios/');
    $config['DB_HOST']       = getenv('DB_HOST') ?: 'localhost';
    $config['DB_NAME']       = getenv('DB_NAME') ?: '';
    $config['DB_USER']       = getenv('DB_USER') ?: '';
    $config['DB_PASS']       = getenv('DB_PASS') ?: '';
    $config['DB_PORT']       = getenv('DB_PORT') ?: '3306';
}

// Zona horaria y charset
if (!ini_get('date.timezone')) { date_default_timezone_set('Europe/Madrid'); }
ini_set('default_charset','UTF-8');

// BASE_URL global
$baseUrl = $config['ruta_absoluta'] ?? '/';
if (!defined('BASE_URL')) { define('BASE_URL', rtrim($baseUrl,'/').'/'); }

// Conexión PDO (si no viene desde config.php)
if (!isset($pdo) || !($pdo instanceof PDO)) {
    $dsn = "mysql:host={$config['DB_HOST']};dbname={$config['DB_NAME']};charset=utf8mb4;port=" . ($config['DB_PORT'] ?? '3306');
    try {
        $pdo = new PDO($dsn, $config['DB_USER'], $config['DB_PASS'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
        ]);
    } catch (PDOException $e) {
        die("Error de conexión a la base de datos: " . $e->getMessage());
    }
}

// Funciones comunes
require_once __DIR__ . '/funciones.php';

// Usuario actual (si hay sesión)
$emp = isset($_SESSION['empleado_id']) ? getEmpleado() : null;

// Notificaciones no leídas
function getNumNotificacionesNoLeidas($empleadoId) {
    global $pdo;
    $sql = "SELECT COUNT(*) FROM notificaciones WHERE (empleado_id IS NULL OR empleado_id = ?) AND leido = 0";
    $st = $pdo->prepare($sql);
    $st->execute([$empleadoId]);
    return (int)$st->fetchColumn();
}
$numNoLeidas = $emp ? getNumNotificacionesNoLeidas($emp['id']) : 0;
