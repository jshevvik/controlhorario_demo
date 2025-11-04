<?php

error_reporting(E_ALL);

session_start();
require_once __DIR__ . '/../../includes/init.php';


$userId = $_SESSION['empleado_id'] ?? null;
$tipo = $_POST['tipo'] ?? '';
$carpetaDestino = rtrim($config['UPLOADS_DIR'], '/\\') . '/';

// Crear carpeta si no existe
if (!is_dir($carpetaDestino)) {
    mkdir($carpetaDestino, 0755, true);
}



if (!$userId) {
    exit('Error: usuario no autenticado.');
}

if ($tipo === 'avatar' && isset($_FILES['avatar_image'])) {
    $fichero = $_FILES['avatar_image'];
    $campoBD = 'avatar';
    $nombre = 'avatar_' . $userId . '.jpg';
} elseif ($tipo === 'header' && isset($_FILES['header_image'])) {
    $fichero = $_FILES['header_image'];
    $campoBD = 'header_img';
    $nombre = 'header_' . $userId . '.jpg';
} else {
    exit('No se ha seleccionado archivo.');
}

if (isset($fichero)) {
    // Validar formato
    $permitidos = [
        'image/jpeg',
        'image/png',
        'image/gif',
    ];
    if (!in_array($fichero['type'], $permitidos)) {
        exit('Formato de imagen no permitido.');
    }

    // Validar extensión
    $extensiones = ['jpg','jpeg','png','gif'];
    $extension = strtolower(pathinfo($fichero['name'], PATHINFO_EXTENSION));
    if (!in_array($extension, $extensiones)) {
        exit('Extensión de archivo no permitida.');
    }

    // Validar tamaño máximo: 2 MB
    $maxSize = 2 * 1024 * 1024;
    if ($fichero['size'] > $maxSize) {
        exit('El archivo es demasiado grande. Tamaño máximo: 2 MB.');
    }

    // Comprobar permisos de carpeta
    if (!is_writable($carpetaDestino)) {
        exit('La carpeta de destino no tiene permisos de escritura.');
    }

    // Guardar archivo
    $ruta = $carpetaDestino . $nombre;
    if (move_uploaded_file($fichero['tmp_name'], $ruta)) {
        $rutaBD = $nombre; // Solo el nombre
        try {
            $sql = "UPDATE empleados SET $campoBD = ? WHERE id = ?";
            $st = $pdo->prepare($sql);
            $st->execute([$rutaBD, $userId]);
            exit('OK');
        } catch (PDOException $e) {
            exit('Error de base de datos: ' . $e->getMessage());
        }
    } else {
        exit('Error al subir la imagen.');
    }
}

exit('Error desconocido.');
