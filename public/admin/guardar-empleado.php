<?php
require_once __DIR__ . '/../../includes/init.php'; 


error_reporting(E_ALL);


if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . $config['ruta_absoluta'] . 'admin/empleados?error=method');
    exit;
}

$id = intval($_POST['id'] ?? 0);
$nombre = trim($_POST['nombre'] ?? '');
$apellidos = trim($_POST['apellidos'] ?? '');
$usuario = trim($_POST['usuario'] ?? '');
$email = trim($_POST['email'] ?? '');
$clave = $_POST['clave'] ?? '';
$rol = $_POST['rol'] ?? 'empleado';

$hora_inicio = $_POST['hora_inicio'] ?? [];
$hora_fin = $_POST['hora_fin'] ?? [];

if (!$id || !$nombre || !$apellidos || !$usuario || !$email) {
    header('Location: ' . $config['ruta_absoluta'] . 'admin/editar-empleado?id='.$id.'&error=1');
    exit;
}

// Obtener datos actuales del empleado
$stmt = $pdo->prepare("SELECT rol FROM empleados WHERE id = ?");
$stmt->execute([$id]);
$empleadoActual = $stmt->fetch(PDO::FETCH_ASSOC);

// Supervisor no puede editar admin ni asignar rol admin
if (isSupervisor()) {
    if ($empleadoActual['rol'] === 'admin' || $rol === 'admin') {
        header('Location: ' . $config['ruta_absoluta'] . 'admin/empleados?error=sin_permisos');
        exit;
    }
}

// Actualizar datos básicos
$params = [$nombre, $apellidos, $usuario, $email, $rol, $id];

if (!empty($clave)) {
    $hash = password_hash($clave, PASSWORD_DEFAULT);
    $sql = "UPDATE empleados SET nombre=?, apellidos=?, usuario=?, email=?, rol=?, clave=? WHERE id=?";
    $params = [$nombre, $apellidos, $usuario, $email, $rol, $hash, $id];
} else {
    $sql = "UPDATE empleados SET nombre=?, apellidos=?, usuario=?, email=?, rol=? WHERE id=?";
}

$stmt = $pdo->prepare($sql);
$stmt->execute($params);

// Guardar horario
$pdo->prepare("DELETE FROM horarios_empleados WHERE empleado_id = ?")->execute([$id]);
foreach ($hora_inicio as $dia => $inicio) {
    $fin = $hora_fin[$dia] ?? null;
    if ($inicio && $fin) {
        $pdo->prepare("INSERT INTO horarios_empleados (empleado_id, dia, hora_inicio, hora_fin) VALUES (?, ?, ?, ?)")
            ->execute([$id, $dia, $inicio, $fin]);
    }
}


header('Location: ' . $config['ruta_absoluta'] . 'admin/editar-empleado?id=' . $id . '&ok=1');
exit;
