<?php
/**
 * Instalador web para añadir el campo es_super_admin
 * Acceder via: https://tu-app.onrender.com/admin/instalar-super-admin.php
 * Contraseña: superadmin2025
 */

// Contraseña de protección
$password_correcta = 'superadmin2024';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (($_POST['password'] ?? '') !== $password_correcta) {
        die('Contraseña incorrecta');
    }
    
    // Cargar configuración
    require_once __DIR__ . '/../../includes/init.php';
    
    echo "<h1>Instalando campo es_super_admin...</h1><pre>";
    
    try {
        // Verificar si ya existe el campo
        $stmt = $pdo->query("SHOW COLUMNS FROM empleados LIKE 'es_super_admin'");
        if ($stmt->rowCount() > 0) {
            echo "✓ El campo 'es_super_admin' ya existe.\n\n";
        } else {
            // Añadir el campo
            $pdo->exec("ALTER TABLE empleados ADD COLUMN es_super_admin TINYINT(1) NOT NULL DEFAULT 0 AFTER rol");
            echo "✓ Campo 'es_super_admin' añadido correctamente.\n\n";
        }
        
        // Marcar al primer admin como super admin
        $stmt = $pdo->prepare("SELECT id, nombre, usuario, email FROM empleados WHERE rol = 'admin' ORDER BY id ASC LIMIT 1");
        $stmt->execute();
        $primerAdmin = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($primerAdmin) {
            $pdo->prepare("UPDATE empleados SET es_super_admin = 1 WHERE id = ?")->execute([$primerAdmin['id']]);
            echo "✓ Super Admin configurado:\n";
            echo "  ID: {$primerAdmin['id']}\n";
            echo "  Usuario: {$primerAdmin['usuario']}\n";
            echo "  Email: {$primerAdmin['email']}\n";
            echo "  Nombre: {$primerAdmin['nombre']}\n\n";
        } else {
            echo "⚠ No se encontró ningún admin en la base de datos.\n\n";
        }
        
        echo "\n";
        echo "INSTALACIÓN COMPLETADA EXITOSAMENTE\n";
        echo "----------------------------------------\n\n";
        echo "El super admin ahora está protegido y no puede ser:\n";
        echo "- Editado por otros admins\n";
        echo "- Eliminado por otros admins\n";
        echo "- Degradado de rol admin\n\n";
        
        
    } catch (PDOException $e) {
        echo "✗ ERROR: " . $e->getMessage() . "\n";
    }
    
    echo "</pre>";
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Instalar Super Admin</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 600px; margin: 50px auto; padding: 20px; }
        .form-group { margin: 15px 0; }
        label { display: block; margin-bottom: 5px; font-weight: bold; }
        input[type="password"] { width: 100%; padding: 8px; font-size: 16px; }
        button { background: #007bff; color: white; padding: 10px 20px; border: none; cursor: pointer; font-size: 16px; }
        button:hover { background: #0056b3; }
        .warning { background: #fff3cd; border: 1px solid #ffc107; padding: 15px; margin: 20px 0; border-radius: 5px; }
    </style>
</head>
<body>
    <h1>🔐 Instalador Super Admin</h1>
    
    <div class="warning">
        <strong>⚠️ Advertencia:</strong> Este script añadirá el campo <code>es_super_admin</code> 
        a la tabla empleados y marcará al primer admin como super admin protegido.
    </div>
    
    <form method="POST">
        <div class="form-group">
            <label>Contraseña de instalación:</label>
            <input type="password" name="password" required autofocus placeholder="superadmin2024">
        </div>
        <button type="submit">Instalar Super Admin</button>
    </form>
    
    <hr style="margin: 30px 0;">
    
    <h3>¿Qué hace este instalador?</h3>
    <ol>
        <li>Añade el campo <code>es_super_admin</code> a la tabla empleados</li>
        <li>Marca al primer admin registrado como super admin</li>
        <li>Protege al super admin de edición/eliminación por otros admins</li>
    </ol>
    
    <p><strong>Importante:</strong> Elimina este archivo después de ejecutarlo.</p>
</body>
</html>
