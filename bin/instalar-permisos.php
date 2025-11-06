<?php
/**
 * Script para crear tablas de permisos
 * Ejecutar una sola vez: php bin/instalar-permisos.php
 */

echo "=================================\n";
echo "Instalador de Sistema de Permisos\n";
echo "=================================\n\n";

// Detectar entorno
$isLocal = php_sapi_name() === 'cli' || 
           in_array($_SERVER['SERVER_NAME'] ?? 'localhost', ['localhost', '127.0.0.1', '192.168.0.100']);

// Cargar configuración
if ($isLocal && file_exists(__DIR__ . '/../public/config.php')) {
    $_SERVER['SERVER_NAME'] = 'localhost'; // Forzar detección local
    require_once __DIR__ . '/../public/config.php';
} else {
    require_once __DIR__ . '/../includes/init.php';
}

// Crear conexión PDO
try {
    $dsn = "mysql:host={$config['DB_HOST']};dbname={$config['DB_NAME']};port={$config['DB_PORT']};charset=utf8mb4";
    $pdo = new PDO($dsn, $config['DB_USER'], $config['DB_PASS'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false
    ]);
    
    echo "✓ Conectado a la base de datos: {$config['DB_NAME']}\n";
    echo "  Host: {$config['DB_HOST']}\n";
    echo "  Usuario: {$config['DB_USER']}\n\n";
    
} catch (PDOException $e) {
    die("✗ Error de conexión: " . $e->getMessage() . "\n");
}

try {
    // Leer el archivo SQL
    $sqlFile = __DIR__ . '/crear-permisos.sql';
    
    if (!file_exists($sqlFile)) {
        throw new Exception("Archivo SQL no encontrado: $sqlFile");
    }
    
    $sql = file_get_contents($sqlFile);
    echo "✓ Archivo SQL cargado: " . strlen($sql) . " bytes\n\n";
    
    // Ejecutar todo el archivo SQL usando multi_query
    // Dividir manualmente por ";" pero respetando los bloques CREATE TABLE
    $statements = [];
    $buffer = '';
    $inCreateTable = false;
    
    foreach (explode("\n", $sql) as $line) {
        $trimmedLine = trim($line);
        
        // Ignorar comentarios de línea completa
        if (str_starts_with($trimmedLine, '--') || empty($trimmedLine)) {
            continue;
        }
        
        // Detectar inicio de CREATE TABLE
        if (stripos($trimmedLine, 'CREATE TABLE') !== false) {
            $inCreateTable = true;
        }
        
        // Agregar la línea al buffer
        $buffer .= $line . "\n";
        
        // Si encontramos punto y coma y no estamos dentro de CREATE TABLE
        // O si estamos en CREATE TABLE y encontramos el cierre
        if (str_ends_with($trimmedLine, ';')) {
            if (!$inCreateTable || (stripos($trimmedLine, ');') !== false)) {
                $statements[] = trim($buffer);
                $buffer = '';
                $inCreateTable = false;
            }
        }
    }
    
    // Agregar buffer final si hay algo
    if (!empty(trim($buffer))) {
        $statements[] = trim($buffer);
    }
    
    echo "Ejecutando " . count($statements) . " consultas SQL...\n\n";
    
    $pdo->beginTransaction();
    
    foreach ($statements as $index => $statement) {
        if (empty(trim($statement))) continue;
        
        try {
            echo ($index + 1) . ". Ejecutando consulta...\n";
            $pdo->exec($statement);
            echo "   ✓ Éxito\n";
        } catch (PDOException $e) {
            // Si la tabla ya existe, continuar
            if (strpos($e->getMessage(), 'already exists') !== false || 
                strpos($e->getMessage(), 'Duplicate entry') !== false) {
                echo "   ⚠ Ya existe (omitiendo)\n";
            } else {
                throw $e;
            }
        }
    }
    
    $pdo->commit();
    
    echo "\n=================================\n";
    echo "✓ Instalación completada con éxito\n";
    echo "=================================\n\n";
    
    // Verificar tablas creadas
    echo "Verificando tablas...\n";
    
    $stmt = $pdo->query("SHOW TABLES LIKE 'permisos_empleados'");
    if ($stmt->rowCount() > 0) {
        echo "  ✓ Tabla 'permisos_empleados' creada\n";
        
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM permisos_empleados");
        $count = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "    -> {$count['total']} permisos insertados\n";
    }
    
    $stmt = $pdo->query("SHOW TABLES LIKE 'solicitudes_historial'");
    if ($stmt->rowCount() > 0) {
        echo "  ✓ Tabla 'solicitudes_historial' creada\n";
    }
    
    echo "\n¡Sistema de permisos instalado correctamente!\n";
    
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    echo "\n✗ ERROR: " . $e->getMessage() . "\n";
    echo "\nDetalles:\n";
    echo $e->getTraceAsString() . "\n";
    exit(1);
}
