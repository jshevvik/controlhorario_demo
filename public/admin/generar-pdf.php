<?php
// Habilitar reporte de errores para debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../../includes/init.php';
require_once __DIR__ . '/../../vendor/autoload.php';

// Obtener y sanear ID del empleado
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id <= 0) {
    die('Error: ID de empleado inválido.');
}

// Filtros de fecha
$whereFecha = '';
$params     = [ $id ];
if (!empty($_GET['desde'])) {
    $whereFecha   .= " AND DATE(hora) >= ?";
    $params[]      = $_GET['desde'];
}
if (!empty($_GET['hasta'])) {
    $whereFecha   .= " AND DATE(hora) <= ?";
    $params[]      = $_GET['hasta'];
}

// Cargar datos del empleado
$stmtEmp = $pdo->prepare("
    SELECT nombre, apellidos, email
    FROM empleados
    WHERE id = ?
");
$stmtEmp->execute([ $id ]);
$empleado = $stmtEmp->fetch(PDO::FETCH_ASSOC);

if (!$empleado) {
    die('Error: Empleado no encontrado.');
}


// Consultar los fichajes del empleado en el periodo seleccionado
$fichajes = $pdo->prepare("
    SELECT *
      FROM fichajes
     WHERE empleado_id = ?
       $whereFecha
  ORDER BY hora ASC
");
// Ejecutar la consulta con los parámetros (id y fechas)
$fichajes->execute($params);
// Obtener todos los resultados como array
$fichajesRaw = $fichajes->fetchAll(PDO::FETCH_ASSOC);

// Agrupar en bloques (entrada / pausa / salida)
$bloques       = [];
$bloqueActual  = null;
foreach ($fichajesRaw as $f) {
    $tipo  = $f['tipo'];
    $hora  = $f['hora'];
    $fecha = substr($hora, 0, 10);

    if ($tipo === 'entrada') {
        if ($bloqueActual) {
            $bloques[] = $bloqueActual;
        }
        $bloqueActual = [
            'fecha'   => $fecha,
            'entrada' => $hora,
            'salida'  => null,
            'pausas'  => []
        ];
    }
    elseif ($tipo === 'salida') {
        if ($bloqueActual) {
            $bloqueActual['salida'] = $hora;

            // Cerrar pausas abiertas con la hora de salida
            if (count($bloqueActual['pausas'])) {
                for ($i = count($bloqueActual['pausas']) - 1; $i >= 0; $i--) {
                    if ($bloqueActual['pausas'][$i]['fin'] === null) {
                        $bloqueActual['pausas'][$i]['fin'] = $hora;
                    }
                }
            }
        

            $bloques[]    = $bloqueActual;
            $bloqueActual = null;
        }
    }

    elseif ($tipo === 'pausa_inicio') {
        if ($bloqueActual) {
            $bloqueActual['pausas'][] = [
                'inicio' => $hora,
                'fin'    => null
            ];
        }
    }
    elseif ($tipo === 'pausa_fin') {
        if ($bloqueActual && count($bloqueActual['pausas'])) {
            // Buscar la última pausa sin fin y asignarle el fin actual
            for ($i = count($bloqueActual['pausas']) - 1; $i >= 0; $i--) {
                if ($bloqueActual['pausas'][$i]['fin'] === null) {
                    $bloqueActual['pausas'][$i]['fin'] = $hora;
                    break;
                }
            }
        }
    }
}
// Si hay un bloque abierto al final, lo cerramos
if ($bloqueActual) {
    // Si el bloque termina sin salida, cerramos pausas con la última hora conocida (entrada o pausa_inicio)
    $lastHora = $bloqueActual['entrada'];
    if (count($bloqueActual['pausas'])) {
        for ($i = count($bloqueActual['pausas']) - 1; $i >= 0; $i--) {
            if ($bloqueActual['pausas'][$i]['fin'] === null) {
                // Si hay salida, la ponemos, si no, dejamos la última hora del bloque
                $bloqueActual['pausas'][$i]['fin'] = $bloqueActual['salida'] ?? $lastHora;
            }
        }
    }
    $bloques[] = $bloqueActual;
}


// Calcular resumen diario y totales
$resumen = [];
$totales = [
    'minTrabajo'  => 0,
    'minDescanso' => 0,
];

foreach ($bloques as $b) {
    // calcular minutos de descanso en este bloque
    $minDescanso = 0;
    foreach ($b['pausas'] as $p) {
        if (!empty($p['inicio']) && !empty($p['fin'])) {
            $tIni = strtotime($p['inicio']);
            $tFin = strtotime($p['fin']);
            if ($tFin > $tIni) {
                $minDescanso += ($tFin - $tIni) / 60;
            }
        }
    }

    // calcular minutos de trabajo neto
    if (!empty($b['entrada']) && !empty($b['salida'])) {
        $tEnt     = strtotime($b['entrada']);
        $tSal     = strtotime($b['salida']);
        $minTotal = max(0, ($tSal - $tEnt) / 60);
        $minTrabajo = $minTotal - $minDescanso;
    } else {
        $minTrabajo = 0;
    }

    // acumular en el día correspondiente
    $fecha = $b['fecha'];
    if (!isset($resumen[$fecha])) {
        $resumen[$fecha] = [
            'minTrabajo'  => 0,
            'minDescanso' => 0,
        ];
    }
    $resumen[$fecha]['minTrabajo']  += $minTrabajo;
    $resumen[$fecha]['minDescanso'] += $minDescanso;

    // acumular totales generales
    $totales['minTrabajo']  += $minTrabajo;
    $totales['minDescanso'] += $minDescanso;
}

// Preparar array para la tabla
$fichajesTabla = [];
foreach ($resumen as $fecha => $vals) {
    $hT = floor($vals['minTrabajo'] / 60);
    $mT = $vals['minTrabajo'] % 60;
    $hD = floor($vals['minDescanso'] / 60);
    $mD = $vals['minDescanso'] % 60;

    $fichajesTabla[] = [
        'fecha'    => date('d/m/Y', strtotime($fecha)),
        'trabajo'  => "{$hT}h {$mT}min",
        'descanso' => "{$hD}h {$mD}min",
    ];
}

$periodo = '';
if (!empty($_GET['desde']) && !empty($_GET['hasta'])) {
    $periodo = 'Periodo: ' . date('d/m/Y', strtotime($_GET['desde'])) . ' - ' . date('d/m/Y', strtotime($_GET['hasta']));
} elseif (!empty($_GET['desde'])) {
    $periodo = 'Desde: ' . date('d/m/Y', strtotime($_GET['desde']));
} elseif (!empty($_GET['hasta'])) {
    $periodo = 'Hasta: ' . date('d/m/Y', strtotime($_GET['hasta']));
} else {
    $periodo = 'Periodo completo (sin filtro de fechas)';
}

// Generar HTML para mPDF
$logoPath = __DIR__ . '/../assets/img/logo.png';
$logoUrl = file_exists($logoPath) ? $config['ASSET_URL'] . 'img/logo.png' : '';

$html = '
<style>
  table { border-collapse: collapse; width: 100%; font-size: 12px; }
  th, td { border: 1px solid #aaa; padding: 6px; text-align: center; }
  th { background: #f0f0f0; }
  h2, h3 { color: #0a5fa0; margin-bottom: 4px; }
  .ficha { margin-bottom: 16px; }
  .ficha p { margin: 2px 0; }
  .logo { text-align: center; margin-bottom: 12px; }
</style>';

if ($logoUrl) {
    $html .= '
<div class="logo">
  <img src="' . htmlspecialchars($logoUrl) . '" style="height:50px;">
</div>';
}

$html .= '
<div class="ficha">
  <h2>Fichajes de trabajador</h2>
  <p><strong>Nombre:</strong> ' . htmlspecialchars($empleado['nombre']) . '</p>
  <p><strong>Apellidos:</strong> ' . htmlspecialchars($empleado['apellidos']) . '</p>
  <p><strong>Email:</strong> ' . htmlspecialchars($empleado['email']) . '</p>
  <h3>' . htmlspecialchars($periodo) . '</h3>
</div>

<table>
  <thead>
    <tr>
      <th>Fecha</th>
      <th>Horas trabajadas</th>
      <th>Descansos</th>
    </tr>
  </thead>
  <tbody>';

foreach ($fichajesTabla as $row) {
    $html .= '
    <tr>
      <td>' . $row['fecha'] . '</td>
      <td>' . $row['trabajo'] . '</td>
      <td>' . $row['descanso'] . '</td>
    </tr>';
}

// fila de totales
$hTtot = floor($totales['minTrabajo'] / 60);
$mTtot = $totales['minTrabajo'] % 60;
$hDtot = floor($totales['minDescanso'] / 60);
$mDtot = $totales['minDescanso'] % 60;

$html .= '
    <tr style="font-weight:bold;">
      <td>Total</td>
      <td>' . $hTtot . 'h ' . $mTtot . 'min</td>
      <td>' . $hDtot . 'h ' . $mDtot . 'min</td>
    </tr>
  </tbody>
</table>';

// Nombre de fichero dinámico: nombre_apellidos_mes_año.pdf
$fechaRef   = !empty($_GET['desde']) ? $_GET['desde'] : date('Y-m-d');
$mesNum     = date('n',   strtotime($fechaRef));
$anio       = date('Y',   strtotime($fechaRef));
$meses      = [
    1=>'enero','febrero','marzo','abril','mayo','junio',
    'julio','agosto','septiembre','octubre','noviembre','diciembre'
];
$mesNombre  = ucfirst($meses[$mesNum]);
$nombreComp = $empleado['nombre'] . '_' . $empleado['apellidos'];
$filename   = sprintf('%s_%s_%s.pdf',
    preg_replace('/\s+/', '_', $nombreComp),
    strtolower($mesNombre),
    $anio
);

// Generar y forzar descarga del PDF con mPDF
try {
    // Configurar directorio temporal
    $tempDir = sys_get_temp_dir();
    if (!is_writable($tempDir)) {
        $tempDir = __DIR__ . '/../../tmp';
        if (!is_dir($tempDir)) {
            mkdir($tempDir, 0755, true);
        }
    }
    
    $mpdf = new \Mpdf\Mpdf([
        'tempDir' => $tempDir,
        'mode' => 'utf-8',
        'format' => 'A4',
        'margin_left' => 15,
        'margin_right' => 15,
        'margin_top' => 16,
        'margin_bottom' => 16,
        'margin_header' => 9,
        'margin_footer' => 9
    ]);
    
    $mpdf->WriteHTML($html);
    $mpdf->Output($filename, 'D');
    exit;
} catch (Exception $e) {
    error_log('Error al generar PDF: ' . $e->getMessage());
    error_log('Trace: ' . $e->getTraceAsString());
    http_response_code(500);
    die('<h1>Error al generar el PDF</h1><p>' . htmlspecialchars($e->getMessage()) . '</p>');
}
