<?php
require_once __DIR__ . '/../includes/init.php';

try {
    // Leer el contenido del archivo SQL
    $sql = file_get_contents(__DIR__ . '/crear-configuracion.sql');
    
    // Ejecutar las sentencias SQL
    $pdo->exec($sql);
    
    echo "✅ Tabla de configuración creada/actualizada correctamente\n";
} catch (PDOException $e) {
    echo "❌ Error al crear/actualizar la tabla de configuración: " . $e->getMessage() . "\n";
    exit(1);
}
