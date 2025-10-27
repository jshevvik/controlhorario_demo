<?php
require_once __DIR__ . '/../../includes/init.php';
$empId = $_SESSION['empleado_id'] ?? null;
if ($empId) {

    $sql = "UPDATE notificaciones SET leido = 1 WHERE (empleado_id = ? OR empleado_id IS NULL)";
    $st = $pdo->prepare($sql);
    $st->execute([$empId]);
}
http_response_code(204); 
