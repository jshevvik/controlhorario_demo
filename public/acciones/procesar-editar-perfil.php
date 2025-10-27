<?php
require_once __DIR__ . '/../../includes/init.php';


if (empty($_SESSION['empleado_id'])) {
  header('Location: index.php?page=login');
  exit;
}

$id = $_SESSION['empleado_id'];

$nombre = trim($_POST['nombre']);
$apellidos = trim($_POST['apellidos']);
$email = trim($_POST['email']);
$usuario = trim($_POST['usuario']);
$descripcion = trim($_POST['descripcion'] ?? '');
$password = $_POST['password'] ?? '';

if (!$nombre || !$apellidos || !$email || !$usuario) {
  header("Location: editar-perfil.php?error=Faltan campos obligatorios");
  exit;
}

try {
  $sql = "UPDATE empleados SET nombre=?, apellidos=?, email=?, usuario=?, descripcion=?";
  $params = [$nombre, $apellidos, $email, $usuario, $descripcion];

  if (!empty($password)) {
    $sql .= ", clave=?";
    $params[] = password_hash($password, PASSWORD_DEFAULT);
  }
  $sql .= " WHERE id=?";
  $params[] = $id;

  $stmt = $pdo->prepare($sql);
  $stmt->execute($params);

 
  $_SESSION['nombre'] = $nombre;

  header("Location: index.php?page=miperfil&ok=1");
  exit;
} catch (Exception $e) {
  header("Location: editar-perfil.php?error=" . urlencode('Error al guardar: ' . $e->getMessage()));
  exit;
}
