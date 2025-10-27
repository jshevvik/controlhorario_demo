<?php
/**
 * Script para limpiar tablas de logs creadas
 */

try {
    // Conexión directa
    $pdo = new PDO('mysql:host=localhost;dbname=control_horario;charset=utf8mb4', 'root', '', [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    
    echo "=== LIMPIEZA DE TABLAS DE LOGS ===\n\n";
    
    // Verificar si existe la tabla logs_seguridad
    $stmt = $pdo->query("SHOW TABLES LIKE 'logs_seguridad'");
    $tableExists = $stmt->fetch();
    
    if ($tableExists) {
        echo "🗑️  Tabla 'logs_seguridad' encontrada. Eliminando...\n";
        
        // Eliminar la tabla
        $pdo->exec("DROP TABLE logs_seguridad");
        echo "✅ Tabla 'logs_seguridad' eliminada correctamente\n";
    } else {
        echo "ℹ️  Tabla 'logs_seguridad' no existe (ya limpia)\n";
    }
    
    // Verificar otras tablas de logs que puedan haber sido creadas
    $stmt = $pdo->query("SHOW TABLES LIKE '%log%'");
    $logTables = $stmt->fetchAll();
    
    echo "\n📊 Tablas relacionadas con logs en la base de datos:\n";
    if (empty($logTables)) {
        echo "   - Ninguna tabla con 'log' en el nombre\n";
    } else {
        foreach ($logTables as $table) {
            $tableName = current($table);
            echo "   - $tableName\n";
        }
    }
    
    echo "\n✅ Limpieza completada\n";
    echo "📝 La tabla 'auditoria' existente se mantiene intacta\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
?>
