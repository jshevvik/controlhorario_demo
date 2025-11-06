-- Tabla de permisos para empleados
CREATE TABLE IF NOT EXISTS permisos_empleados (
    id INT AUTO_INCREMENT PRIMARY KEY,
    empleado_id INT NOT NULL,
    
    -- Permisos generales
    puede_ver_dashboard TINYINT(1) DEFAULT 1,
    puede_fichaje TINYINT(1) DEFAULT 1,
    puede_crear_solicitudes TINYINT(1) DEFAULT 1,
    puede_ver_informes TINYINT(1) DEFAULT 1,
    
    -- Permisos de gestión (supervisor)
    puede_aprobar_solicitudes TINYINT(1) DEFAULT 0,
    puede_rechazar_solicitudes TINYINT(1) DEFAULT 0,
    puede_editar_solicitudes TINYINT(1) DEFAULT 0,
    puede_gestionar_empleados TINYINT(1) DEFAULT 0,
    puede_ver_empleados TINYINT(1) DEFAULT 0,
    puede_editar_horarios TINYINT(1) DEFAULT 0,
    puede_crear_contenido TINYINT(1) DEFAULT 0,
    puede_ver_fichajes_otros TINYINT(1) DEFAULT 0,
    
    -- Metadata
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_modificacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (empleado_id) REFERENCES empleados(id) ON DELETE CASCADE,
    UNIQUE KEY unique_empleado (empleado_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla de log de cambios en solicitudes
CREATE TABLE IF NOT EXISTS solicitudes_historial (
    id INT AUTO_INCREMENT PRIMARY KEY,
    solicitud_id INT NOT NULL,
    empleado_id INT NOT NULL,
    accion VARCHAR(50) NOT NULL, -- 'crear', 'editar', 'aprobar', 'rechazar', 'eliminar'
    campo_modificado VARCHAR(100),
    valor_anterior TEXT,
    valor_nuevo TEXT,
    comentario TEXT,
    fecha TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    ip_address VARCHAR(45),
    
    FOREIGN KEY (solicitud_id) REFERENCES solicitudes(id) ON DELETE CASCADE,
    FOREIGN KEY (empleado_id) REFERENCES empleados(id) ON DELETE CASCADE,
    INDEX idx_solicitud (solicitud_id),
    INDEX idx_empleado (empleado_id),
    INDEX idx_fecha (fecha)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insertar permisos por defecto para empleados existentes basado en rol
INSERT INTO permisos_empleados (
    empleado_id,
    puede_aprobar_solicitudes,
    puede_rechazar_solicitudes,
    puede_editar_solicitudes,
    puede_gestionar_empleados,
    puede_ver_empleados,
    puede_editar_horarios,
    puede_crear_contenido,
    puede_ver_fichajes_otros
)
SELECT 
    id,
    CASE WHEN rol IN ('admin', 'supervisor') THEN 1 ELSE 0 END,
    CASE WHEN rol IN ('admin', 'supervisor') THEN 1 ELSE 0 END,
    CASE WHEN rol IN ('admin', 'supervisor') THEN 1 ELSE 0 END,
    CASE WHEN rol IN ('admin', 'supervisor') THEN 1 ELSE 0 END,
    CASE WHEN rol IN ('admin', 'supervisor') THEN 1 ELSE 0 END,
    CASE WHEN rol IN ('admin', 'supervisor') THEN 1 ELSE 0 END,
    CASE WHEN rol = 'admin' THEN 1 ELSE 0 END,
    CASE WHEN rol IN ('admin', 'supervisor') THEN 1 ELSE 0 END
FROM empleados
WHERE NOT EXISTS (
    SELECT 1 FROM permisos_empleados WHERE permisos_empleados.empleado_id = empleados.id
);
