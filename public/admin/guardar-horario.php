<?php
require_once __DIR__ . '/../../includes/init.php';

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$id = isset($_POST['empleado_id']) ? intval($_POST['empleado_id']) : 0;
$inicio = $_POST['hora_inicio'] ?? [];
$fin = $_POST['hora_fin'] ?? [];
$inicio_tarde = $_POST['hora_inicio_tarde'] ?? [];
$fin_tarde = $_POST['hora_fin_tarde'] ?? [];
$horario_partido = $_POST['horario_partido'] ?? [];



if (!$id || empty($inicio)) {
    header("Location: " . $config['ruta_absoluta'] . "admin/editar-horario.php?id=$id&error=1");
    exit;
}


 //print_r($inicio); print_r($fin);


$stmt = $pdo->prepare("DELETE FROM horarios_empleados WHERE empleado_id = ?");
$stmt->execute([$id]);


foreach ($inicio as $dia => $hora_inicio) {
    $hora_fin = $fin[$dia] ?? null;
    $hora_inicio_tarde = $inicio_tarde[$dia] ?? null;
    $hora_fin_tarde = $fin_tarde[$dia] ?? null;
    $es_horario_partido = isset($horario_partido[$dia]) ? 1 : 0;


    $dia = ucfirst(mb_strtolower(trim($dia), 'UTF-8'));

    if (!empty($hora_inicio) && !empty($hora_fin)) {
        try {
            $stmt = $pdo->prepare("INSERT INTO horarios_empleados (empleado_id, dia, hora_inicio, hora_fin, hora_inicio_tarde, hora_fin_tarde, horario_partido)
                                   VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$id, $dia, $hora_inicio, $hora_fin, $hora_inicio_tarde, $hora_fin_tarde, $es_horario_partido]);
        } catch (PDOException $e) {
            echo "Error insertando $dia: " . $e->getMessage();
        }
    }
}

header("Location: " . $config['ruta_absoluta'] . "admin/ver-empleado?id=$id&horario=ok");
exit;
