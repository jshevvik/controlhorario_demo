<?php
/**
 * Script temporal para instalar permisos en Railway
 * ELIMINAR DESPUÉS DE USAR
 */

// Contraseña de seguridad temporal
define('INSTALL_PASSWORD', 'instalar2024');

$password = $_POST['password'] ?? $_GET['password'] ?? '';

if ($password !== INSTALL_PASSWORD) {
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>Instalar Permisos - Railway</title>
        <meta charset="utf-8">
        <style>
            body { font-family: Arial; max-width: 600px; margin: 50px auto; padding: 20px; }
            input, button { padding: 10px; margin: 10px 0; }
            button { background: #5469d4; color: white; border: none; cursor: pointer; }
        </style>
    </head>
    <body>
        <h1>🔒 Instalar Sistema de Permisos</h1>
        <p>Este script creará las tablas de permisos en la base de datos de Railway.</p>
        <form method="post">
            <input type="password" name="password" placeholder="Contraseña de instalación" required>
            <button type="submit">Instalar</button>
        </form>
    </body>
    </html>
    <?php
    exit;
}

// Cargar configuración
require_once __DIR__ . '/../../includes/init.php';

?>
<!DOCTYPE html>
<html>
<head>
    <title>Instalando Permisos</title>
    <meta charset="utf-8">
    <style>
        body { 
            font-family: 'Courier New', monospace; 
            background: #1e1e1e; 
            color: #00ff00; 
            padding: 20px;
            max-width: 900px;
            margin: 0 auto;
        }
        .success { color: #00ff00; }
        .error { color: #ff4444; }
        .info { color: #4CAF50; }
        pre { background: #000; padding: 15px; border-radius: 5px; overflow-x: auto; }
        h1 { color: #ffffff; }
    </style>
</head>
<body>
<h1>⚙️ Instalador de Sistema de Permisos - Railway</h1>

<?php

try {
    echo "<pre>";
    echo "=================================\n";
    echo "Instalador de Sistema de Permisos\n";
    echo "=================================\n\n";
    
    // Verificar conexión
    echo "<span class='info'>✓ Conectado a la base de datos: {$config['DB_NAME']}</span>\n";
    echo "  Host: {$config['DB_HOST']}\n";
    echo "  Usuario: {$config['DB_USER']}\n\n";
    
    // Leer SQL
    $sqlFile = __DIR__ . '/../../bin/crear-permisos.sql';
    if (!file_exists($sqlFile)) {
        throw new Exception("Archivo SQL no encontrado: $sqlFile");
    }
    
    $sql = file_get_contents($sqlFile);
    echo "<span class='info'>✓ Archivo SQL cargado: " . strlen($sql) . " bytes</span>\n\n";
    
    // Ejecutar cada statement
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Dividir por ";" pero mantener CREATE TABLE completo
    $statements = [];
    $buffer = '';
    $lines = explode("\n", $sql);
    
    foreach ($lines as $line) {
        $trimmed = trim($line);
        
        // Ignorar comentarios y líneas vacías
        if (empty($trimmed) || str_starts_with($trimmed, '--')) {
            continue;
        }
        
        $buffer .= $line . "\n";
        
        // Si termina en ";" ejecutar
        if (str_ends_with($trimmed, ';')) {
            $statements[] = trim($buffer);
            $buffer = '';
        }
    }
    
    echo "Ejecutando " . count($statements) . " consultas SQL...\n\n";
    
    foreach ($statements as $index => $statement) {
        if (empty(trim($statement))) continue;
        
        try {
            echo ($index + 1) . ". Ejecutando consulta...\n";
            
            // Mostrar primeras líneas de la consulta
            $preview = substr($statement, 0, 80);
            echo "   <span class='info'>" . htmlspecialchars($preview) . "...</span>\n";
            
            $pdo->exec($statement);
            echo "   <span class='success'>✓ Éxito</span>\n\n";
            
        } catch (PDOException $e) {
            // Si la tabla ya existe, continuar
            if (strpos($e->getMessage(), 'already exists') !== false || 
                strpos($e->getMessage(), 'Duplicate entry') !== false) {
                echo "   <span class='info'>⚠ Ya existe (omitiendo)</span>\n\n";
            } else {
                throw $e;
            }
        }
    }
    
    echo "\n=================================\n";
    echo "<span class='success'>✓ Instalación completada con éxito</span>\n";
    echo "=================================\n\n";
    
    // Verificar tablas creadas
    echo "Verificando tablas...\n\n";
    
    $stmt = $pdo->query("SHOW TABLES LIKE 'permisos_empleados'");
    if ($stmt->rowCount() > 0) {
        echo "<span class='success'>✓ Tabla 'permisos_empleados' creada</span>\n";
        
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM permisos_empleados");
        $count = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "  -> {$count['total']} permisos insertados\n\n";
        
        // Mostrar permisos
        $stmt = $pdo->query("
            SELECT e.nombre, e.apellidos, e.rol, 
                   p.puede_aprobar_solicitudes, 
                   p.puede_editar_solicitudes,
                   p.puede_gestionar_empleados
            FROM permisos_empleados p 
            INNER JOIN empleados e ON p.empleado_id = e.id
        ");
        
        echo "Permisos asignados:\n";
        while ($row = $stmt->fetch()) {
            echo sprintf(
                "  - %s %s (%s): Aprobar=%d, Editar=%d, Gestionar=%d\n",
                $row['nombre'],
                $row['apellidos'],
                $row['rol'],
                $row['puede_aprobar_solicitudes'],
                $row['puede_editar_solicitudes'],
                $row['puede_gestionar_empleados']
            );
        }
        echo "\n";
    }
    
    $stmt = $pdo->query("SHOW TABLES LIKE 'solicitudes_historial'");
    if ($stmt->rowCount() > 0) {
        echo "<span class='success'>✓ Tabla 'solicitudes_historial' creada</span>\n";
    }
    
    echo "\n<span class='success'>¡Sistema de permisos instalado correctamente en Railway!</span>\n";
    echo "\n<span class='error'>⚠️ IMPORTANTE: Elimina este archivo después de usarlo:</span>\n";
    echo "   public/admin/instalar-permisos-railway.php\n";
    
    echo "</pre>";
    
} catch (Exception $e) {
    echo "<pre>";
    echo "\n<span class='error'>✗ ERROR: " . htmlspecialchars($e->getMessage()) . "</span>\n";
    echo "\nDetalles:\n";
    echo htmlspecialchars($e->getTraceAsString()) . "\n";
    echo "</pre>";
}

?>

<br><br>
<a href="../../dashboard.php" style="color: #4CAF50;">← Volver al Dashboard</a>

</body>
</html>
