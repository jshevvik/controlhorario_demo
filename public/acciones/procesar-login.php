<?php


error_reporting(E_ALL);

session_start();
require_once __DIR__ . '/../../includes/init.php';


if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . $config['ruta_absoluta'] . 'login');
    exit;
}

$usuario = trim($_POST['usuario'] ?? '');
$clave   = $_POST['password'] ?? '';

if ($usuario === '' || $clave === '') {
    $_SESSION['error'] = 'Debes indicar usuario y contraseña';
    header('Location: ' . $config['ruta_absoluta'] . 'login');
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT id, clave FROM empleados WHERE usuario = ?");
    $stmt->execute([$usuario]);
    $empleado = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($empleado && password_verify($clave, $empleado['clave'])) {
        $_SESSION['empleado_id'] = $empleado['id'];

        // Registrar login exitoso en el nuevo sistema de logs
        registrarLogin($usuario, $empleado['id'], true);
        
        // Mantener registro en auditoria (legacy)
        $log = $pdo->prepare(
          "INSERT INTO auditoria (usuario_id, accion, detalle) VALUES (?, 'login_exitoso', ?)"
        );
        $log->execute([$empleado['id'], $_SERVER['REMOTE_ADDR']]);
        
        header('Location: ' . $config['ruta_absoluta'] . 'dashboard');
        exit;
    } else {
        $_SESSION['error'] = 'Usuario o contraseña incorrectos';
        
        // Registrar login fallido en el nuevo sistema de logs
        registrarLogin($usuario, $empleado['id'] ?? null, false);
        
        if ($empleado) {
            $log = $pdo->prepare(
              "INSERT INTO auditoria (usuario_id, accion, detalle) VALUES (?, 'login_fallido', ?)"
            );
            $log->execute([$empleado['id'], $_SERVER['REMOTE_ADDR']]);
        }
        header('Location: ' . $config['ruta_absoluta'] . 'login');
        exit;
    }
} catch (PDOException $e) {
    die("Error al consultar la base de datos: " . $e->getMessage());
}
