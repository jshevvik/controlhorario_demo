# Control de Horario (Demo)

Aplicación PHP para gestión de control horario. Este repositorio incluye los archivos necesarios para ejecutar en local y desplegar una **demo** en Render.

## 🧰 Tecnologías
- PHP 8.2 + Apache
- Composer (autoloader y dependencias)
- MySQL (externo/gestionado en producción)
- .htaccess para rutas amigables
- Docker (Render construye la imagen automáticamente)

## 📦 Estructura
public/ # DocumentRoot
.htaccess
index.php
admin/
acciones/
fichaje/
notificaciones/
404.php, login.php, ...
uploads/ # Subidas de usuarios (no se versiona)
includes/
vendor/ # Generado por Composer
config.example.php
composer.json
Dockerfile


## 🔐 Seguridad
- No subir `config.php`, contraseñas ni datos reales.
- En Render usar variables de entorno: `BASE_URL`, `DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASS`, `UPLOADS_DIR` (opcional).

## 🖥️ Ejecución local
```bash
composer install
cp config.example.php config.php
# Ajusta DB_* si hace falta
php -S localhost:8000 -t public
# Ir a http://localhost:8000
