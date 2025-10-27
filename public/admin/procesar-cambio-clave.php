<?php
require_once '../../includes/init.php';
requireAdmin(); // Solo admins

header('Content-Type: application/json');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Método no permitido');
    }

    // Validar datos recibidos
    $usuario_id = $_POST['usuario_id'] ?? '';
    $clave_actual = $_POST['clave_actual'] ?? '';
    $nueva_clave = $_POST['nueva_clave'] ?? '';
    $confirmar_clave = $_POST['confirmar_clave'] ?? '';

    if (empty($usuario_id) || empty($nueva_clave) || empty($confirmar_clave)) {
        throw new Exception('Usuario, nueva contraseña y confirmación son obligatorios');
    }

    if ($nueva_clave !== $confirmar_clave) {
        throw new Exception('Las contraseñas nuevas no coinciden');
    }

    if (strlen($nueva_clave) < 6) {
        throw new Exception('La nueva contraseña debe tener al menos 6 caracteres');
    }

    // Verificar si es admin cambiando su propia contraseña o la de otro usuario
    $es_cambio_propio = ($usuario_id == $_SESSION['empleado_id']);
    
    // Si es cambio propio, la contraseña actual es obligatoria
    if ($es_cambio_propio && empty($clave_actual)) {
        throw new Exception('Para cambiar tu propia contraseña debes proporcionar la contraseña actual');
    }

    // Obtener datos del usuario a modificar
    $stmt = $pdo->prepare("SELECT id, usuario, clave FROM empleados WHERE id = ?");
    $stmt->execute([$usuario_id]);
    $usuario = $stmt->fetch();

    if (!$usuario) {
        throw new Exception('Usuario no encontrado');
    }

    // Verificar contraseña actual solo si es cambio propio
    if ($es_cambio_propio && !password_verify($clave_actual, $usuario['clave'])) {
        registrarActividadSeguridad(
            'CAMBIO_CLAVE_FALLIDO', 
            $usuario['usuario'], 
            'Contraseña actual incorrecta en cambio propio', 
            $_SESSION['empleado_id']
        );
        throw new Exception('La contraseña actual es incorrecta');
    }

    // Generar hash de la nueva contraseña
    $nueva_clave_hash = password_hash($nueva_clave, PASSWORD_DEFAULT);

    // Actualizar contraseña
    $stmt = $pdo->prepare("UPDATE empleados SET clave = ? WHERE id = ?");
    $result = $stmt->execute([$nueva_clave_hash, $usuario_id]);

    if ($result) {
        $tipo_cambio = $es_cambio_propio ? 'cambio propio' : 'cambio administrativo';
        
        registrarActividadSeguridad(
            'CAMBIO_CLAVE_EXITOSO', 
            $usuario['usuario'], 
            "Contraseña cambiada correctamente ($tipo_cambio)", 
            $_SESSION['empleado_id']
        );

        echo json_encode([
            'success' => true,
            'message' => 'Contraseña cambiada correctamente'
        ]);
    } else {
        throw new Exception('Error al actualizar la contraseña en la base de datos');
    }

} catch (Exception $e) {
    registrarActividadSeguridad(
        'CAMBIO_CLAVE_ERROR', 
        $usuario['usuario'] ?? 'desconocido', 
        $e->getMessage(), 
        $_SESSION['empleado_id'] ?? null  // Cambiado de usuario_id a empleado_id
    );

    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
