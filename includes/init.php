<?php
error_reporting(E_ALL);

// Arranca la sesión SOLO si no existe
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Carga configuración y funciones
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/funciones.php';

// Configura la zona horaria
if (!ini_get('date.timezone')) {
    date_default_timezone_set('Europe/Madrid');
}

// BASE_URL dinámica (URL visible en navegador)
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https://" : "http://";
$host = $_SERVER['HTTP_HOST'];
$scriptName = dirname($_SERVER['SCRIPT_NAME']);
$base_url = $protocol . $host . rtrim($scriptName, '/\\') . '/';

// Define la constante para usar en todo el proyecto
if (!defined('BASE_URL')) {
    define('BASE_URL', $base_url);
}


// Usuario actual (si está logueado)
$emp = isset($_SESSION['empleado_id']) ? getEmpleado() : null;

// Función para contar notificaciones no leídas
function getNumNotificacionesNoLeidas($empleadoId) {
    global $pdo;
    $sql = "SELECT COUNT(*) FROM notificaciones WHERE (empleado_id IS NULL OR empleado_id = ?) AND leido = 0";
    $st = $pdo->prepare($sql);
    $st->execute([$empleadoId]);
    return $st->fetchColumn();
}

// Notificaciones no leídas globales
$numNoLeidas = $emp ? getNumNotificacionesNoLeidas($emp['id']) : 0;
