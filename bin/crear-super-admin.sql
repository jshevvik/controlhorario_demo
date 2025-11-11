-- Añadir campo es_super_admin a la tabla empleados
-- Un super admin no puede ser editado ni eliminado por otros admins

ALTER TABLE empleados 
ADD COLUMN es_super_admin TINYINT(1) NOT NULL DEFAULT 0 
AFTER rol;

-- Marcar al primer admin como super admin (ajusta el ID si es necesario)
-- Esto marca al usuario con ID 1 como super admin
UPDATE empleados 
SET es_super_admin = 1 
WHERE rol = 'admin' 
ORDER BY id ASC 
LIMIT 1;

-- Si quieres marcar un admin específico por su email o usuario:
-- UPDATE empleados SET es_super_admin = 1 WHERE email = 'tu_email@ejemplo.com';
