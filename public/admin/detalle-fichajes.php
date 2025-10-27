<?php

require_once __DIR__ . '/../../includes/init.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit(json_encode(['success' => false, 'error' => 'Método no permitido']));
}

$input      = json_decode(file_get_contents('php://input'), true) ?: [];
$fechaInput = $input['fecha']        ?? '';
$empleadoId = isset($input['empleado_id']) ? (int)$input['empleado_id'] : 0;

try {
    $fecha = (new DateTime($fechaInput))->format('Y-m-d');
} catch (Exception $e) {
    $fecha = '';
}

if (!$fecha || !$empleadoId) {
    exit(json_encode([
        'success' => false,
        'error'   => 'Faltan parámetros',
        'debug'   => ['fecha' => $fecha, 'empleado_id' => $empleadoId]
    ]));
}

try {
    // Detectar campo de fecha
    $cols       = $pdo->query('SHOW COLUMNS FROM fichajes')->fetchAll(PDO::FETCH_COLUMN);
    $campoFecha = in_array('fecha_hora', $cols, true) ? 'fecha_hora' : 'hora';

    // Preparar y ejecutar SQL
    $sql = "
      SELECT
        DATE_FORMAT(`$campoFecha`, '%H:%i') AS hora,
        tipo,
        `$campoFecha` AS fecha_hora_completa
      FROM fichajes
      WHERE empleado_id = :empleado_id
        AND DATE(`$campoFecha`) = :fecha
      ORDER BY `$campoFecha` ASC
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['empleado_id' => $empleadoId, 'fecha' => $fecha]);
    $fichajes = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Log para servidor
    error_log("DETALLE_FICHAJES v3 – SQL: $sql – filas: " . count($fichajes));

    // Si no hay filas
    if (empty($fichajes)) {
        exit(json_encode([
            'success' => true,
            'fichajes' => [],
            'debug'   => ['sql' => $sql, 'count' => 0]
        ]));
    }

  
    $proc = [];
    foreach ($fichajes as $i => $f) {
        $o = $f;
     
        $tiposTexto = [
            'entrada' => 'Entrada',
            'salida' => 'Salida',
            'pausa_inicio' => 'Inicio descanso',
            'pausa_fin' => 'Fin descanso'
        ];
        
        $o['tipo'] = $tiposTexto[$f['tipo']] ?? ucfirst($f['tipo']);
        $o['duracion'] = '-';
        $o['contexto'] = 'Inicio de jornada';

        if ($i > 0) {
            $d1 = new DateTime($fichajes[$i - 1]['fecha_hora_completa']);
            $d2 = new DateTime($f['fecha_hora_completa']);
            $diff = $d1->diff($d2);
            $h = $diff->h + $diff->days * 24;
            $m = $diff->i;
            
            // Formato de duración
            if ($h > 0) {
                $o['duracion'] = sprintf('%dh %02dm', $h, $m);
            } else {
                $o['duracion'] = sprintf('%dm', $m);
            }

            $prev = $fichajes[$i - 1]['tipo'];
            $curr = $f['tipo'];
            
          
            if ($prev === 'entrada' && $curr === 'salida') {
                $o['contexto'] = 'Tiempo de trabajo';
            } elseif ($prev === 'salida' && $curr === 'entrada') {
                $o['contexto'] = 'Tiempo fuera del trabajo';
            } elseif ($prev === 'entrada' && $curr === 'pausa_inicio') {
                $o['contexto'] = 'Tiempo trabajado antes de descanso';
            } elseif ($prev === 'pausa_inicio' && $curr === 'pausa_fin') {
                $o['contexto'] = 'Tiempo de descanso';
            } elseif ($prev === 'pausa_fin' && $curr === 'salida') {
                $o['contexto'] = 'Tiempo trabajado después de descanso';
            } elseif ($prev === 'pausa_fin' && $curr === 'pausa_inicio') {
                $o['contexto'] = 'Tiempo trabajado entre descansos';
            } else {
                $o['contexto'] = 'Intervalo entre fichajes';
            }
        }

        $proc[] = $o;
    }

    // Respuesta final con debug
    exit(json_encode([
        'success'  => true,
        'total'    => count($proc),
        'fichajes' => $proc,
        'debug'    => [
            'sql'   => $sql,
            'count' => count($proc),
        ]
    ]));

} catch (Exception $e) {
    error_log('DETALLE_FICHAJES v3 – Exception: ' . $e->getMessage());
    http_response_code(500);
    exit(json_encode([
        'success' => false,
        'error'   => 'Error interno',
        'debug'   => ['exception' => $e->getMessage()]
    ]));
}
