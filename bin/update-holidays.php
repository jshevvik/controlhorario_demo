
<?php
/* ——— ejecutar sólo en CLI ——— */
if (PHP_SAPI !== 'cli') { exit("Solo CLI\n"); }

/* Fake super-globales necesarias para config/init */
$_SERVER['SERVER_NAME'] = $_SERVER['SERVER_NAME'] ?? 'localhost';
$_SERVER['HTTP_HOST']   = $_SERVER['HTTP_HOST']   ?? 'localhost';
$_SERVER['HTTPS']       = $_SERVER['HTTPS']       ?? 'off';

/* Carga init → incluye config.php + funciones + $pdo */
require_once __DIR__.'/../includes/init.php';

/* Año a importar */
$year   = $argv[1] ?? date('Y');
$apiKey = 'K3Aw1FZBR6riDUZpfyzknPDHMBjwoCzp';

/* Descarga nacionales */
$url = "https://calendarific.com/api/v2/holidays?api_key={$apiKey}&country=ES&year={$year}";
$data = json_decode(file_get_contents($url), true)['response']['holidays'] ?? [];

/* Función para traducir nombres de festivos a español */
function traducirFestivo($nombreIngles) {
    $traducciones = [
        'New Year\'s Day' => 'Año Nuevo',
        'Epiphany' => 'Día de Reyes',
        'Good Friday' => 'Viernes Santo',
        'Easter Monday' => 'Lunes de Pascua',
        'Labor Day / May Day' => 'Día del Trabajador',
        'Assumption of Mary' => 'Asunción de la Virgen',
        'Hispanic Day' => 'Día de la Hispanidad',
        'Hispanic Day observed' => 'Día de la Hispanidad (observado)',
        'All Saints\' Day' => 'Día de Todos los Santos',
        'Constitution Day' => 'Día de la Constitución',
        'Immaculate Conception' => 'Inmaculada Concepción',
        'Christmas Day' => 'Navidad',
        'Boxing Day' => 'San Esteban',
        'Holy Thursday' => 'Jueves Santo',
        'Easter Sunday' => 'Domingo de Resurrección',
        'Corpus Christi' => 'Corpus Christi',
        'Saint James' => 'Santiago Apóstol',
        'National Day' => 'Fiesta Nacional',
        'Independence Day' => 'Día de la Independencia',
        'Christmas Eve' => 'Nochebuena',
        'New Year\'s Eve' => 'Nochevieja',
        // Variaciones observadas
        'National Holiday' => 'Fiesta Nacional',
        'Public Holiday' => 'Día Festivo',
        'Bank Holiday' => 'Día Festivo'
    ];
    
    return $traducciones[$nombreIngles] ?? $nombreIngles;
}

/* Insert / Update */
$sql = 'INSERT INTO festivos (fecha,nombre,alcance,region)
        VALUES (?,?,?,?)
        ON DUPLICATE KEY UPDATE nombre = VALUES(nombre)';
$ins = $pdo->prepare($sql);

foreach ($data as $h) {
    if ($h['type'][0] !== 'National holiday') continue;
    
    // Traducir el nombre del festivo al español
    $nombreEspanol = traducirFestivo($h['name']);
    
    $ins->execute([$h['date']['iso'], $nombreEspanol, 'nacional', null]);
    echo "Agregado: {$h['date']['iso']} - $nombreEspanol\n";
}
