<?php
session_start();
require_once __DIR__ . '/../../includes/init.php';
requireLogin();
$empId  = $_SESSION['empleado_id'];
$hoyIni = date('Y-m-d 00:00:00');
$hoyFin = date('Y-m-d 23:59:59');

$sql = "SELECT tipo, hora FROM fichajes WHERE empleado_id = ? AND hora BETWEEN ? AND ? ORDER BY hora ASC";
$st  = $pdo->prepare($sql);
$st->execute([$empId, $hoyIni, $hoyFin]);
$rows = $st->fetchAll(PDO::FETCH_ASSOC);

$state = 'none'; 
$workSec = 0; 
$pauseSec = 0;
$ultimoTipo = null;
$ultimoTst = null; 
foreach ($rows as $r) {
    $ts = strtotime($r['hora']);
    if ($r['tipo']==='entrada') {
        // Set state to working without resetting counters
        $state = 'working';
        $ultimoTipo = 'entrada';
        $ultimoTst = $ts;
    }

    elseif ($r['tipo']==='pausa_inicio' && ($ultimoTipo==='entrada' || $ultimoTipo==='pausa_fin')) {
        $workSec += $ts - $ultimoTst;
        $state = 'paused';
        $ultimoTipo = 'pausa_inicio';
        $ultimoTst = $ts;
    }
    elseif ($r['tipo']==='pausa_fin' && $ultimoTipo==='pausa_inicio') {
        $pauseSec += $ts - $ultimoTst;
        $state = 'working';
        $ultimoTipo = 'pausa_fin';
        $ultimoTst = $ts;
    }
    elseif ($r['tipo']==='salida') {
        // Sumar tiempo según el último estado
        if ($ultimoTipo==='entrada') {
            // Salimos directamente desde entrada
            $workSec += $ts - $ultimoTst;
        } elseif ($ultimoTipo==='pausa_inicio') {
            // Salimos desde pausa sin reanudar
            $pauseSec += $ts - $ultimoTst;
        } elseif ($ultimoTipo==='pausa_fin') {
            // Salimos después de reanudar, sumar el tiempo de trabajo desde la reanudación
            $workSec += $ts - $ultimoTst;
        }
        
        $state = 'none';
        $ultimoTipo = null;
        $ultimoTst = null;
    }
}

//if ($state==='working' && $ultimoTipo==='entrada') $workSec += time() - $ultimoTst;
//if ($state==='paused'  && $ultimoTipo==='pausa_inicio') $pauseSec+= time() - $ultimoTst;

// Tramo abierto: sumar hasta ahora sin depender de ultimoTipo
if ($state === 'working' && $ultimoTst !== null) {
    $workSec += time() - $ultimoTst;
}
if ($state === 'paused' && $ultimoTst !== null) {
    $pauseSec += time() - $ultimoTst;
}

header('Content-Type: application/json');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
echo json_encode([
    'state'    => $state,  
    'workSec'  => $workSec, 
    'pauseSec' => $pauseSec 
]);
