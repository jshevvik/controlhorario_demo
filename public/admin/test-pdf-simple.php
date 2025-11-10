<?php
// Script de prueba mínimo para diagnosticar mPDF en Render
// Acceder: https://controlhorario-demo.onrender.com/admin/test-pdf-simple.php?test=1

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Test diagnóstico mPDF</h1>";
echo "<pre>";

// 1. Verificar vendor/autoload
echo "1. Verificando vendor/autoload.php...\n";
$autoloadPath = __DIR__ . '/../../vendor/autoload.php';
if (file_exists($autoloadPath)) {
    echo "   ✓ vendor/autoload.php existe\n";
    require_once $autoloadPath;
} else {
    echo "   ✗ ERROR: vendor/autoload.php NO ENCONTRADO\n";
    echo "   Ruta buscada: $autoloadPath\n";
    echo "   SOLUCIÓN: Ejecutar 'composer install' en el servidor\n";
    die();
}

// 2. Verificar clase Mpdf
echo "\n2. Verificando clase Mpdf...\n";
if (class_exists('\\Mpdf\\Mpdf')) {
    echo "   ✓ Clase Mpdf disponible\n";
} else {
    echo "   ✗ ERROR: Clase Mpdf NO ENCONTRADA\n";
    echo "   SOLUCIÓN: Ejecutar 'composer install' para instalar mpdf/mpdf\n";
    die();
}

// 3. Verificar extensión mbstring
echo "\n3. Verificando extensión mbstring...\n";
if (extension_loaded('mbstring')) {
    echo "   ✓ mbstring cargada\n";
} else {
    echo "   ✗ WARNING: mbstring NO cargada (mPDF la necesita)\n";
}

// 4. Verificar directorio temporal
echo "\n4. Verificando directorio temporal...\n";
$tempDir = sys_get_temp_dir();
echo "   sys_get_temp_dir(): $tempDir\n";
if (is_writable($tempDir)) {
    echo "   ✓ Directorio temporal escribible\n";
} else {
    echo "   ✗ WARNING: sys_get_temp_dir() NO escribible\n";
    $tempDir = __DIR__ . '/../../tmp';
    echo "   Intentando fallback: $tempDir\n";
    if (!is_dir($tempDir)) {
        mkdir($tempDir, 0755, true);
        echo "   Directorio tmp creado\n";
    }
    if (is_writable($tempDir)) {
        echo "   ✓ Directorio tmp escribible\n";
    } else {
        echo "   ✗ ERROR: tmp NO escribible\n";
    }
}

// 5. Test de memoria
echo "\n5. Configuración PHP...\n";
echo "   memory_limit: " . ini_get('memory_limit') . "\n";
echo "   max_execution_time: " . ini_get('max_execution_time') . "\n";

// 6. Intentar generar PDF simple
if (isset($_GET['test']) && $_GET['test'] == '1') {
    echo "\n6. Intentando generar PDF de prueba...\n";
    try {
        $mpdf = new \Mpdf\Mpdf([
            'tempDir' => $tempDir,
            'mode' => 'utf-8',
            'format' => 'A4'
        ]);
        
        $html = '<h1>PDF de prueba</h1><p>Si ves esto, mPDF funciona correctamente.</p>';
        $mpdf->WriteHTML($html);
        
        echo "   ✓ mPDF instanciado correctamente\n";
        echo "   ✓ HTML procesado\n";
        echo "\n";
        echo "===========================================\n";
        echo "TODO CORRECTO - mPDF funciona\n";
        echo "===========================================\n";
        echo "\n<a href='?test=1&download=1'>Descargar PDF de prueba</a>\n";
        
        if (isset($_GET['download'])) {
            $mpdf->Output('test.pdf', 'D');
            exit;
        }
        
    } catch (Exception $e) {
        echo "   ✗ ERROR al generar PDF:\n";
        echo "   Mensaje: " . $e->getMessage() . "\n";
        echo "   Archivo: " . $e->getFile() . ":" . $e->getLine() . "\n";
        echo "\n   Traza completa:\n";
        echo $e->getTraceAsString();
    }
} else {
    echo "\n<a href='?test=1'>Ejecutar test de generación PDF</a>\n";
}

echo "</pre>";
