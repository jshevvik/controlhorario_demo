<?php
require_once __DIR__ . '/../../includes/init.php';
requireAdmin();

$id = intval($_GET['id'] ?? 0);

if (!$id) {
    die('ID inválido');
}

// Usar la función centralizada para eliminar empleado
$resultado = eliminarEmpleado($id, $_SESSION['empleado_id']);

if ($resultado['success']) {
    header("Location: " . $config['ruta_absoluta'] . "admin/empleados?delete=ok");
    exit;
} else {
    die('Error: ' . htmlspecialchars($resultado['message']));
}

