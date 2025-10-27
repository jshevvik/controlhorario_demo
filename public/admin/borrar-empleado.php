<?php
require_once __DIR__ . '/../../includes/init.php';
requireAdmin();

$id = intval($_GET['id'] ?? 0);
if (!$id) {
  die('ID inválido');
}

if ($id === $_SESSION['user_id']) {
  die('No puedes borrar tu propia cuenta.');
}

$pdo->prepare("DELETE FROM empleados WHERE id = ?")
    ->execute([$id]);

header("Location: " . $config['ruta_absoluta'] . "admin/empleados?delete=ok");
