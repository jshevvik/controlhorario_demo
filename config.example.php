<?php
$config = [];

$isLocal = in_array($_SERVER['SERVER_NAME'] ?? '', ['localhost','127.0.0.1','192.168.0.100']);

/** Rutas base del proyecto */
$projectRoot = rtrim(str_replace('\\','/', dirname(__FILE__)), '/') . '/';
$publicDir   = $projectRoot . 'public/';

/** URL base del proyecto */
if ($isLocal) {
    $baseUrl = '//localhost/controlhorario_demo/';
} else {
    $scheme  = $_SERVER['HTTP_X_FORWARDED_PROTO'] ?? $_SERVER['REQUEST_SCHEME'] ?? 'https';
    $host    = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $baseUrl = rtrim(getenv('BASE_URL') ?: "$scheme://$host/", '/') . '/';
}

$config['ruta_absoluta'] = $baseUrl;
$config['ruta_server']   = $publicDir;
$config['carpeta']       = $projectRoot;


$config['ASSET_URL']   = $config['ruta_absoluta'] . 'assets/';
$config['UPLOADS_URL'] = $config['ruta_absoluta'] . 'uploads/usuarios/';


$config['UPLOADS_DIR'] = $isLocal
  ? $config['carpeta'] . 'uploads/usuarios/'
  : (getenv('UPLOADS_DIR') ?: ($publicDir . 'uploads/usuarios/'));  // <- cambio clave

/** Configuración de Base de Datos */
if ($isLocal) {
    $config['DB_HOST'] = 'localhost';
    $config['DB_NAME'] = 'control_horario';
    $config['DB_USER'] = 'root';
    $config['DB_PASS'] = '';
    $config['DB_PORT'] = '3306';
} else {
    $config['DB_HOST'] = getenv('DB_HOST') ?: 'localhost';
    $config['DB_NAME'] = getenv('DB_NAME') ?: '';
    $config['DB_USER'] = getenv('DB_USER') ?: '';
    $config['DB_PASS'] = getenv('DB_PASS') ?: '';
    $config['DB_PORT'] = getenv('DB_PORT') ?: '3306'; 
}

/** Conexión PDO (con puerto explícito) */
try {
    $dsn = "mysql:host={$config['DB_HOST']};port={$config['DB_PORT']};dbname={$config['DB_NAME']};charset=utf8mb4";
    $pdo = new PDO(
        $dsn,
        $config['DB_USER'],
        $config['DB_PASS'],
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
        ]
    );
} catch (PDOException $e) {
    die("Error de conexión a la base de datos: " . $e->getMessage());
}

/** Zona horaria */
if (!ini_get('date.timezone')) {
    date_default_timezone_set('Europe/Madrid');
}
