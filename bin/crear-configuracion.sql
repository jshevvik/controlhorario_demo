-- Tabla de configuración general del sistema
CREATE TABLE IF NOT EXISTS `configuracion` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `clave` varchar(100) NOT NULL UNIQUE,
  `valor` text,
  `descripcion` varchar(255),
  `tipo` varchar(50) DEFAULT 'text',
  `creado_en` timestamp DEFAULT CURRENT_TIMESTAMP,
  `actualizado_en` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `clave` (`clave`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insertar valores por defecto si no existen
INSERT IGNORE INTO `configuracion` (`clave`, `valor`, `descripcion`, `tipo`) VALUES
('nombre_empresa', 'jshevvik', 'Nombre de la empresa', 'text'),
('direccion_empresa', 'Oficina Central', 'Dirección de la empresa', 'text'),
('email_soporte', 'noreply@miempresa.com', 'Email de soporte', 'email'),
('telefono_soporte', '', 'Teléfono de soporte', 'text'),
('zona_horaria', 'Europe/Madrid', 'Zona horaria del sistema', 'text'),
('dias_retencion_logs', '90', 'Días de retención de logs', 'number'),
('horas_minimas_diarias', '8', 'Horas mínimas diarias de trabajo', 'number'),
('hora_inicio_laboral', '08:00', 'Hora de inicio de jornada laboral', 'time'),
('hora_fin_laboral', '17:00', 'Hora de fin de jornada laboral', 'time');
