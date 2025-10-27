<?php
/**
 * Verificar conflictos de solicitudes
 * 
 * Detecta si hay solicitudes aprobadas que se solapan con las fechas
 * de la solicitud que se intenta crear.
 * 
 * RULES:
 * - Si el tipo es 'baja' y ya existe otra 'baja' aprobada en esas fechas: BLOQUEAR
 * - Si el tipo es 'vacaciones' y ya existe otra 'vacaciones' aprobada en esas fechas: BLOQUEAR
 * - Si el tipo es 'baja' y existen 'vacaciones' aprobadas: PERMITIR pero marcar para eliminar
 * 
 * @author Agente AI
 * @version 1.0
 */

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../includes/init.php';

try {
    // Verificar que sea POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Método no permitido');
    }
    
    // Obtener datos
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($data['empleado_id']) || !isset($data['tipo']) || 
        !isset($data['fecha_inicio']) || !isset($data['fecha_fin'])) {
        throw new Exception('Datos incompletos');
    }
    
    $empleado_id = intval($data['empleado_id']);
    $tipo = $data['tipo'];
    $fecha_inicio = $data['fecha_inicio'];
    $fecha_fin = $data['fecha_fin'];
    
    // Validar tipo válido
    $tipos_validos = ['vacaciones', 'permiso', 'baja', 'extra', 'ausencia'];
    if (!in_array($tipo, $tipos_validos)) {
        throw new Exception('Tipo de solicitud inválido');
    }
    
    // Convertir fechas a objetos DateTime para comparación
    $inicio = new DateTime($fecha_inicio);
    $fin = new DateTime($fecha_fin);
    
    // Validar que inicio <= fin
    if ($inicio > $fin) {
        throw new Exception('Fecha inicio no puede ser posterior a fecha fin');
    }
    
    $response = [
        'hasConflict' => false,
        'message' => '',
        'conflictType' => null,
        'conflictingSolicitudes' => []
    ];
    
    // Consultar solicitudes aprobadas del empleado en el rango de fechas
    // Usamos <= para las comparaciones porque queremos incluir solicitudes que se solapan
    $stmt = $pdo->prepare("
        SELECT id, tipo, fecha_inicio, fecha_fin, estado, medio_dia, horas
        FROM solicitudes
        WHERE empleado_id = :empleado_id
        AND estado = 'aprobado'
        AND (
            (fecha_inicio <= :fecha_fin AND fecha_fin >= :fecha_inicio)
        )
        ORDER BY fecha_inicio ASC
    ");
    
    $stmt->execute([
        ':empleado_id' => $empleado_id,
        ':fecha_inicio' => $fecha_inicio,
        ':fecha_fin' => $fecha_fin
    ]);
    
    $solicitudes_existentes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Analizar conflictos
    foreach ($solicitudes_existentes as $existente) {
        $tipo_existente = $existente['tipo'];
        
        // REGLA 1: Si intento crear una BAJA y ya existe BAJA aprobada en esas fechas
        if ($tipo === 'baja' && $tipo_existente === 'baja') {
            $response['hasConflict'] = true;
            $response['message'] = 'Ya existe una baja médica aprobada en estas fechas. No se puede crear otra baja médica en el mismo período.';
            $response['conflictType'] = 'duplicate_baja';
            $response['conflictingSolicitudes'][] = $existente;
            break;
        }
        
        // REGLA 2: Si intento crear VACACIONES y ya existe VACACIONES aprobada en esas fechas
        if ($tipo === 'vacaciones' && $tipo_existente === 'vacaciones') {
            $response['hasConflict'] = true;
            $response['message'] = 'Ya existe una solicitud de vacaciones aprobada en estas fechas. No se puede crear otra solicitud de vacaciones en el mismo período.';
            $response['conflictType'] = 'duplicate_vacaciones';
            $response['conflictingSolicitudes'][] = $existente;
            break;
        }
        
        // REGLA 3: Si intento crear BAJA y existe VACACIONES en esas fechas
        // Se permitirá pero se marcará para eliminación
        if ($tipo === 'baja' && $tipo_existente === 'vacaciones') {
            // No es un conflicto bloqueante, pero registramos que hay vacaciones a eliminar
            $response['conflictingSolicitudes'][] = [
                'id' => $existente['id'],
                'tipo' => $tipo_existente,
                'action' => 'delete'
            ];
        }
    }
    
    echo json_encode($response);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'error' => true,
        'message' => $e->getMessage()
    ]);
}
?>
