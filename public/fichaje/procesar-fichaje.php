<?php
session_start();
require_once __DIR__ . '/../../includes/init.php';
require_once __DIR__ . '/../../includes/funciones.php';
requireLogin();

// Log de depuración inicial - escribir a archivo específico
$logFile = __DIR__ . '/../../debug_fichaje.log';
$timestamp = date('Y-m-d H:i:s');

$requestId = uniqid('REQ_');
file_put_contents($logFile, "\n\n=== REQUEST $requestId ===", FILE_APPEND);
file_put_contents($logFile, "\n$timestamp - 🚀 FICHAJE INICIADO - Método: " . $_SERVER['REQUEST_METHOD'], FILE_APPEND);
file_put_contents($logFile, "\n$timestamp - 🚀 POST DATA: " . json_encode($_POST), FILE_APPEND);
file_put_contents($logFile, "\n$timestamp - 🚀 SESSION: empleado_id=" . $_SESSION['empleado_id'], FILE_APPEND);

$empId = $_SESSION['empleado_id'];
$tipo = $_POST['tipo'] ?? '';
$isAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

file_put_contents($logFile, "\n$timestamp - 📋 DATOS PROCESADOS: empId=$empId, tipo=$tipo, isAjax=" . ($isAjax ? 'YES' : 'NO'), FILE_APPEND);

// Evitar duplicados: no permitir dos fichajes seguidos del mismo tipo
$ultimo = getUltimoFichajeHoy($empId);
if ($ultimo && $ultimo['tipo'] === $tipo) {
    if ($isAjax) {
        http_response_code(409);
        echo json_encode(['error' => 'duplicado']);
        exit;
    } else {
        header('Location: ../index.php?page=fichajes&fichaje=error&motivo=duplicado');
        exit;
    }
}

if (in_array($tipo, ['entrada', 'salida', 'pausa_inicio', 'pausa_fin'])) {
    try {
        file_put_contents($logFile, "\n$timestamp - ✅ TIPO VÁLIDO: $tipo - INSERTANDO EN BD", FILE_APPEND);
        
        // Obtener datos de geolocalización si están disponibles
        $latitud = isset($_POST['lat']) ? (float)$_POST['lat'] : null;
        $longitud = isset($_POST['lng']) ? (float)$_POST['lng'] : null;
        $precision = isset($_POST['acc']) ? (float)$_POST['acc'] : null;
        
        // Preparar consulta SQL con o sin geolocalización
        if ($latitud && $longitud) {
            $sql = "INSERT INTO fichajes (empleado_id, hora, tipo, latitud, longitud, precision_gps) VALUES (?, NOW(), ?, ?, ?, ?)";
            $st = $pdo->prepare($sql);
            $result = $st->execute([$empId, $tipo, $latitud, $longitud, $precision]);
            file_put_contents($logFile, "\n$timestamp - ✅ INSERT CON GEOLOC - SQL: " . ($result ? 'OK' : 'FALLO'), FILE_APPEND);
        } else {
            $sql = "INSERT INTO fichajes (empleado_id, hora, tipo) VALUES (?, NOW(), ?)";
            $st = $pdo->prepare($sql);
            $result = $st->execute([$empId, $tipo]);
            file_put_contents($logFile, "\n$timestamp - ✅ INSERT SIN GEOLOC - SQL: " . ($result ? 'OK' : 'FALLO'), FILE_APPEND);
        }
        
    } catch (Exception $e) {
        file_put_contents($logFile, "\n$timestamp - ❌ ERROR EN FICHAJE: " . $e->getMessage(), FILE_APPEND);
        if ($isAjax) {
            http_response_code(500);
            echo json_encode(['error' => 'db']);
            exit;
        } else {
            header('Location: ../index.php?page=fichajes&fichaje=error');
            exit;
        }
    }
} else {
    file_put_contents($logFile, "\n$timestamp - ❌ TIPO INVÁLIDO: $tipo", FILE_APPEND);
    if ($isAjax) {
        http_response_code(400);
        echo json_encode(['error' => 'tipo_invalido']);
        exit;
    } else {
        header('Location: ../index.php?page=fichajes&fichaje=error');
        exit;
    }
}

file_put_contents($logFile, "\n$timestamp - 🏁 PROCESO COMPLETADO - isAjax: " . ($isAjax ? 'YES' : 'NO'), FILE_APPEND);


if (!$isAjax) {
    // Deshabilitar caché para asegurar que se recarga con datos frescos
    header('Cache-Control: no-cache, no-store, must-revalidate');
    header('Pragma: no-cache');
    header('Expires: 0');
    header('Location: ../index.php?page=fichajes&fichaje=ok&accion=' . urlencode($tipo));
    exit;
}


$hoyIni = date('Y-m-d 00:00:00');
$hoyFin = date('Y-m-d 23:59:59');
$sqlEstado = "SELECT tipo, hora FROM fichajes WHERE empleado_id = ? AND hora BETWEEN ? AND ? ORDER BY hora ASC";
$reg = $pdo->prepare($sqlEstado);
$reg->execute([$empId, $hoyIni, $hoyFin]);
$rows = $reg->fetchAll(PDO::FETCH_ASSOC);
$state = 'none';  $workSec = 0; $pauseSec = 0; $ultimoTipo = null; $ultimoTst = null;
foreach ($rows as $row) {
  $actualTst = strtotime($row['hora']);
  if ($row['tipo']==='entrada') {
    
    $state = 'working';
    $ultimoTipo = 'entrada';
    $ultimoTst = $actualTst;
  }
  elseif ($row['tipo']==='pausa_inicio' && ($ultimoTipo==='entrada' || $ultimoTipo==='pausa_fin')) {
    $workSec += $actualTst - $ultimoTst;
    $state = 'paused';
    $ultimoTipo = 'pausa_inicio';
    $ultimoTst = $actualTst;
  }
  elseif ($row['tipo']==='pausa_fin' && $ultimoTipo==='pausa_inicio') {
    $pauseSec += $actualTst - $ultimoTst;
    $state = 'working';
    $ultimoTipo = 'pausa_fin';
    $ultimoTst = $actualTst;
  }
  elseif ($row['tipo']==='salida') {
    // Sumar tiempo según el último estado
    if ($ultimoTipo==='entrada') {
      // Salimos directamente desde entrada
      $workSec += $actualTst - $ultimoTst;
    } elseif ($ultimoTipo==='pausa_inicio') {
      // Salimos desde pausa sin reanudar
      $pauseSec += $actualTst - $ultimoTst;
    } elseif ($ultimoTipo==='pausa_fin') {
      // Salimos después de reanudar, sumar el tiempo de trabajo desde la reanudación
      $workSec += $actualTst - $ultimoTst;
    }
   
    $state = 'none';
    $ultimoTipo = null;
    $ultimoTst = null;
  }
}

//if ($state==='working' && $ultimoTipo==='entrada') $workSec += time() - $ultimoTst;
//if ($state==='paused'  && $ultimoTipo==='pausa_inicio') $pauseSec += time() - $ultimoTst;

if ($state === 'working' && $ultimoTst !== null) {
  $workSec += time() - $ultimoTst;
}
if ($state === 'paused' && $ultimoTst !== null) {
  $pauseSec += time() - $ultimoTst;
}


header('Content-Type: application/json');
echo json_encode([
  'state'    => $state,
  'workSec'  => $workSec,
  'pauseSec' => $pauseSec
]);
