<?php
/**
 * ========================================================================
 * CONFIGURADOR DE GEOLOCALIZACIÓN - SISTEMA CONTROL HORARIO
 * ========================================================================
 * 
 * Script de configuración inicial para el módulo de geolocalización.
 * Este archivo configura la base de datos y las tablas necesarias para
 * el funcionamiento del sistema de tracking GPS en fichajes.
 * 
 * @author Sistema Control Horario
 * @version 1.0
 * @date 2025
 * 
 * FUNCIONALIDADES:
 * - Crear tabla de configuración de geolocalización
 * - Insertar configuración global por defecto
 * - Agregar columnas GPS a la tabla de fichajes
 * - Verificar integridad de la configuración
 * 
 * EJECUCIÓN:
 * php bin/configurar-geolocalizacion.php
 * 
 * DEPENDENCIAS:
 * - config.php (configuración de base de datos)
 * - Tabla 'fichajes' existente
 * - Permisos de ALTER TABLE en la base de datos
 * 
 * NOTAS IMPORTANTES:
 * - Se ejecuta solo una vez para la configuración inicial
 * - Es seguro ejecutar múltiples veces (verifica antes de crear)
 * - No afecta datos existentes en fichajes
 * ========================================================================
 */

// Configurar variables de servidor para CLI
$_SERVER['SERVER_NAME'] = 'localhost';

require_once __DIR__ . '/../config.php';

try {
    echo "========================================\n";
    echo "  CONFIGURADOR DE GEOLOCALIZACIÓN\n";
    echo "========================================\n\n";
    
    // ====================================================================
    // PASO 1: VERIFICAR Y CREAR TABLA DE CONFIGURACIÓN
    // ====================================================================
    
    echo "1. Verificando tabla de configuración de geolocalización...\n";
    
    // Verificar si ya existe configuración global
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM configuracion_geolocalizacion WHERE empleado_id IS NULL");
    $stmt->execute();
    $existe = $stmt->fetchColumn();
    
    if ($existe == 0) {
        echo "   → Insertando configuración global por defecto...\n";
        
        // Insertar configuración global por defecto
        // empleado_id = NULL significa configuración para toda la empresa
        $stmt = $pdo->prepare("
            INSERT INTO configuracion_geolocalizacion 
            (empleado_id, latitud_oficina, longitud_oficina, radio_permitido, geolocalizacion_requerida, nombre_ubicacion) 
            VALUES (NULL, 40.4168, -3.7038, 100, 1, 'Oficina Central')
        ");
        $stmt->execute();
        echo "   ✅ Configuración de geolocalización creada correctamente\n";
        
        // Mostrar detalles de la configuración insertada
        echo "   📍 Ubicación: Oficina Central\n";
        echo "   🌍 Coordenadas: 40.4168, -3.7038 (Madrid, España)\n";
        echo "   📏 Radio permitido: 100 metros\n";
        echo "   🔒 Geolocalización: Requerida\n";
    } else {
        echo "   ✅ La configuración ya existe\n";
        
        // Mostrar configuración actual
        $stmt = $pdo->prepare("SELECT * FROM configuracion_geolocalizacion WHERE empleado_id IS NULL");
        $stmt->execute();
        $config = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($config) {
            echo "   📍 Ubicación actual: {$config['nombre_ubicacion']}\n";
            echo "   🌍 Coordenadas: {$config['latitud_oficina']}, {$config['longitud_oficina']}\n";
            echo "   📏 Radio: {$config['radio_permitido']} metros\n";
        }
    }
    
    echo "\n";
    
    // ====================================================================
    // PASO 2: VERIFICAR Y AGREGAR COLUMNAS GPS A FICHAJES
    // ====================================================================
    
    echo "2. Verificando columnas de geolocalización en tabla fichajes...\n";
    
    // Verificar si existen las columnas de geolocalización en la tabla fichajes
    $stmt = $pdo->prepare("SHOW COLUMNS FROM fichajes LIKE 'latitud'");
    $stmt->execute();
    $existeLatitud = $stmt->fetch();
    
    if (!$existeLatitud) {
        echo "   → Agregando columnas de geolocalización a la tabla fichajes...\n";
        
        // Agregar columnas necesarias para geolocalización
        $columnas = [
            "latitud DECIMAL(10, 8) NULL COMMENT 'Latitud GPS del fichaje'",
            "longitud DECIMAL(11, 8) NULL COMMENT 'Longitud GPS del fichaje'", 
            "precision_gps FLOAT NULL COMMENT 'Precisión del GPS en metros'",
            "ubicacion VARCHAR(255) NULL COMMENT 'Dirección obtenida por geocoding'",
            "distancia_oficina INT NULL COMMENT 'Distancia a la oficina en metros'"
        ];
        
        foreach ($columnas as $i => $columna) {
            $nombreColumna = explode(' ', $columna)[0];
            echo "   → Agregando columna: {$nombreColumna}...\n";
            $pdo->exec("ALTER TABLE fichajes ADD COLUMN {$columna}");
        }
        
        echo "   ✅ Columnas agregadas correctamente\n";
        echo "   📊 Se pueden almacenar datos GPS en fichajes\n";
    } else {
        echo "   ✅ Las columnas de geolocalización ya existen\n";
        
        // Verificar qué columnas GPS existen
        $columnasGPS = ['latitud', 'longitud', 'precision_gps', 'ubicacion', 'distancia_oficina'];
        $existentes = [];
        
        foreach ($columnasGPS as $col) {
            $stmt = $pdo->prepare("SHOW COLUMNS FROM fichajes LIKE ?");
            $stmt->execute([$col]);
            if ($stmt->fetch()) {
                $existentes[] = $col;
            }
        }
        
        echo "   📊 Columnas GPS disponibles: " . implode(', ', $existentes) . "\n";
    }
    
    echo "\n";
    
    // ====================================================================
    // PASO 3: VERIFICACIÓN FINAL Y RESUMEN
    // ====================================================================
    
    echo "3. Verificación final del sistema...\n";
    
    // Verificar integridad de la configuración
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM configuracion_geolocalizacion");
    $stmt->execute();
    $totalConfigs = $stmt->fetchColumn();
    
    $stmt = $pdo->prepare("SHOW TABLES LIKE 'configuracion_geolocalizacion'");
    $stmt->execute();
    $tablaExiste = $stmt->fetch() ? true : false;
    
    $stmt = $pdo->prepare("SHOW COLUMNS FROM fichajes LIKE 'latitud'");
    $stmt->execute();
    $columnasExisten = $stmt->fetch() ? true : false;
    
    echo "   📋 Estado del sistema:\n";
    echo "   " . ($tablaExiste ? "✅" : "❌") . " Tabla configuracion_geolocalizacion\n";
    echo "   " . ($columnasExisten ? "✅" : "❌") . " Columnas GPS en fichajes\n";
    echo "   📊 Configuraciones totales: {$totalConfigs}\n";
    
    echo "\n========================================\n";
    
    if ($tablaExiste && $columnasExisten) {
        echo "🎉 CONFIGURACIÓN COMPLETADA EXITOSAMENTE\n";
        echo "========================================\n";
        echo "✅ La página de geolocalización está lista para usar\n";
        echo "🌍 Los fichajes pueden registrar ubicación GPS\n";
        echo "📱 La aplicación móvil funcionará correctamente\n";
        echo "🔧 Para personalizar: modificar configuracion_geolocalizacion\n";
    } else {
        echo "❌ ERROR EN LA CONFIGURACIÓN\n";
        echo "================================\n";
        echo "Por favor, revise los errores anteriores\n";
    }
    
    echo "\n";
    
} catch (Exception $e) {
    echo "\n❌ ERROR CRÍTICO\n";
    echo "=================\n";
    echo "Error: " . $e->getMessage() . "\n";
    echo "Archivo: " . $e->getFile() . "\n";
    echo "Línea: " . $e->getLine() . "\n";
    echo "\nPosibles soluciones:\n";
    echo "1. Verificar que la base de datos esté ejecutándose\n";
    echo "2. Comprobar permisos de la base de datos\n";
    echo "3. Verificar la configuración en config.php\n";
    echo "4. Asegurar que la tabla 'fichajes' existe\n";
    
    exit(1);
}
    
?>
