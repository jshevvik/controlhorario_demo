<?php
require_once __DIR__ . '/../../includes/init.php';
requireAdmin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  header('Location: '.$config['ruta_absoluta'].'admin/crear-empleado.php');
  exit;
}

$nombre    = trim($_POST['nombre']);
$apellidos = trim($_POST['apellidos']);
$usuario   = trim($_POST['usuario']);
$email     = trim($_POST['email']);
$clave     = $_POST['clave'];
$rol       = $_POST['rol'];

if (!$nombre || !$apellidos || !$usuario || !$email || !$clave || !$rol) {
  die('Faltan datos obligatorios');
}

$hash = password_hash($clave, PASSWORD_DEFAULT);

try {

  $pdo->beginTransaction();


  $stmt = $pdo->prepare("
    INSERT INTO empleados
      (nombre, apellidos, usuario, email, clave, rol, fecha_alta)
    VALUES (?, ?, ?, ?, ?, ?, CURDATE())
  ");
  $stmt->execute([$nombre, $apellidos, $usuario, $email, $hash, $rol]);


  $empleadoId = $pdo->lastInsertId();


  $inicios = $_POST['hora_inicio'] ?? [];
  $fines   = $_POST['hora_fin']   ?? [];

  $stmtH = $pdo->prepare("
    INSERT INTO horarios_empleados
      (empleado_id, dia, hora_inicio, hora_fin)
    VALUES (?, ?, ?, ?)
  ");

  foreach ($inicios as $dia => $horaIni) {
    $horaFin = $fines[$dia] ?? null;
    if (empty($horaIni) && empty($horaFin)) {
      continue;
    }
    $stmtH->execute([
      $empleadoId,
      $dia,
      $horaIni ?: null,
      $horaFin ?: null
    ]);
  }


  $pdo->commit();


  header('Location: '.$config['ruta_absoluta'].'admin/empleados?ok=1');
  exit;

} catch (Exception $e) {

  $pdo->rollBack();

  die("Error al crear empleado y su horario: " . $e->getMessage());
}
