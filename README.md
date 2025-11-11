# 🕒 Control Horario — Sistema de Fichajes y Asistencia

[![PHP](https://img.shields.io/badge/PHP-8.2-777BB4?logo=php&logoColor=white)](https://www.php.net/)
[![MySQL](https://img.shields.io/badge/MySQL-8.0+-4479A1?logo=mysql&logoColor=white)](https://www.mysql.com/)
[![Railway](https://img.shields.io/badge/Railway-Database-0B0D0E?logo=railway&logoColor=white)](https://railway.app/)
[![Render](https://img.shields.io/badge/Render-Deploy-46E3B7?logo=render&logoColor=white)](https://render.com/)
[![License: MIT](https://img.shields.io/badge/License-MIT-green.svg)](LICENSE)

Aplicación para **gestión de horarios laborales**, fichaje con **geolocalización**, **solicitudes** (vacaciones/permiso/baja), **informes** en PDF y **panel administrativo**.

**🌐 Demo en vivo:** https://controlhorario-demo.onrender.com  
**💾 Base de datos:** MySQL 8.0+ en Railway

---

## 🔑 Credenciales de Prueba

Para probar la aplicación en la demo, usa estas credenciales:

| Rol | Usuario | Contraseña | Descripción |
|-----|---------|------------|-------------|
| 👑 **Admin** | `admin` | `admin123` | Acceso completo al sistema |
| 👥 **Supervisor** | `supervisor` | `supervisor123` | Gestión de empleados y aprobaciones |
| 👤 **Empleado** | `empleado` | `empleado123` | Fichaje y solicitudes |

> ⚠️ **Nota:** Estas son credenciales de demostración. En producción, cambia todas las contraseñas.

---

## 📸 Capturas de Pantalla

### 🏠 Dashboard
![Dashboard](screenshots/dashboard.png)
*Vista principal con resumen de fichajes y accesos rápidos*

### 👥 Gestión de Empleados
![Empleados](screenshots/empleados.png)
*Panel de administración de empleados con búsqueda y filtros*

### 📍 Fichaje con Geolocalización
![Fichaje](screenshots/fichaje.png)
*Sistema de fichaje con validación de ubicación mediante mapa*

### 📋 Solicitudes
![Solicitudes](screenshots/solicitudes.png)
*Gestión de vacaciones, permisos y bajas con aprobación*

### 📊 Informes PDF
![Informes](screenshots/informes.png)
*Generación de informes de fichajes en PDF*

---

## 📑 Índice
- [Credenciales de Prueba](#-credenciales-de-prueba)
- [Capturas de Pantalla](#-capturas-de-pantalla)
- [Características](#-características)
- [Stack](#-stack)
- [Estructura](#-estructura)
- [Instalación Local](#-instalación-local)
- [Despliegue en Render](#-despliegue-en-render)
- [Uso](#-uso)
- [Seguridad](#-seguridad)
- [Desarrollo](#-desarrollo)
- [Troubleshooting](#-troubleshooting)
- [Licencia](#-licencia)
- [Autor](#-autor)

---

## ✨ Características
- Fichaje **Entrada/Salida** con validación de **ubicación (Leaflet)**  
- Gestión de **empleados**, **roles** (Admin / Supervisor / Empleado)  
- Solicitudes: **vacaciones, permisos, bajas**, con **aprobación**  
- **Informes** y exportación **PDF** (mPDF)  
- **Notificaciones** internas  
- **Dashboard** con resumen  
- **UI responsive** (Bootstrap 5)  
- Preparado para **Docker + Render**

---

## 🧰 Stack
| Componente | Tecnología |
|-----------|------------|
| Backend | PHP 8.2 + Apache |
| Base de datos | MySQL 8.0+ (Railway) |
| Frontend | HTML5, Bootstrap 5, JS |
| Mapas | Leaflet 1.9.4 |
| PDF | mPDF 8.2 |
| Deploy App | Docker + Render |
| Deploy DB | Railway |
| Dependencias | Composer |

---

## 📦 Estructura
```
controlhorario_demo/
├── public/
│   ├── index.php
│   ├── login.php
│   ├── dashboard.php
│   ├── admin/
│   ├── acciones/
│   ├── fichaje/
│   ├── notificaciones/
│   ├── assets/
│   └── uploads/
├── includes/
│   ├── init.php
│   └── funciones.php
├── bin/
│   ├── configurar-sistema.php
│   ├── configurar-geolocalizacion.php
│   └── update-holidays.php
├── config.example.php
├── composer.json
├── Dockerfile
└── README.md
```

---

## 💻 Instalación Local

**Requisitos:** PHP 8.2+, MySQL 8.0+, Composer, Git

```bash
git clone https://github.com/jshevvik/controlhorario_demo.git
cd controlhorario_demo
composer install
cp config.example.php config.php
nano config.php
php bin/configurar-sistema.php
php bin/configurar-geolocalizacion.php
php -S localhost:8000 -t public
```

Abrir: http://localhost:8000

---

## 🌐 Despliegue en Render

### Base de Datos en Railway

1. Crea cuenta en https://railway.app
2. **New Project** → **Provision MySQL**
3. Obtén las credenciales en **Variables**:
   - `MYSQLHOST`
   - `MYSQLPORT` (normalmente 3306)
   - `MYSQLDATABASE`
   - `MYSQLUSER`
   - `MYSQLPASSWORD`

4. Importa el esquema de base de datos:
```bash
mysql -h MYSQLHOST -P MYSQLPORT -u MYSQLUSER -p MYSQLDATABASE < bin/crear-configuracion.sql
```

### Aplicación en Render

1. Conecta el repo en https://render.com  
2. Render detecta el **Dockerfile** automáticamente  
3. Configura variables de entorno con las credenciales de Railway:

```env
DB_HOST=tu-host-railway.railway.app
DB_NAME=railway
DB_USER=root
DB_PASS=tu_contraseña_railway
DB_PORT=3306
BASE_URL=https://tu-app.onrender.com/
UPLOADS_DIR=/var/www/html/public/uploads/usuarios/
APP_ENV=production
```

4. Ejecuta scripts de configuración inicial:
```bash
php bin/configurar-sistema.php
php bin/configurar-geolocalizacion.php
```

---

## 🧭 Uso

- `/login` → Inicio de sesión  
- Dashboard → Fichar entrada/salida  
- Solicitudes → Vacaciones, permisos, bajas  
- Informes → Generación PDF

---

## 🔐 Seguridad
- Contraseñas con **bcrypt**
- **PDO** + prepared statements
- Cookies **SameSite** y HTTPS en producción
- Configuración **CSP** y headers protectores
- Variables sensibles via **entorno** (no commitear)

---

## 🛠️ Desarrollo

### Funciones principales
```php
require_once __DIR__ . '/../includes/init.php';
requireLogin();
requireAdminOrSupervisor();
getEmpleado();
obtenerGeoConfigEmpleado($id);
registrarLogin($usuario, $id, true);
getNotificaciones($id);
```

### Respaldo de Base de Datos (Railway)
```bash
# Exportar base de datos
mysqldump -h MYSQLHOST -P MYSQLPORT -u MYSQLUSER -p MYSQLDATABASE > backup.sql

# Importar base de datos
mysql -h MYSQLHOST -P MYSQLPORT -u MYSQLUSER -p MYSQLDATABASE < backup.sql
```

### Scripts de mantenimiento
```bash
# Actualizar festivos
php bin/update-holidays.php

# Configurar geolocalización
php bin/configurar-geolocalizacion.php

# Configurar sistema
php bin/configurar-sistema.php
```

---

## 🧯 Troubleshooting

**404 en rutas**
```bash
a2enmod rewrite && systemctl reload apache2
```

**Conexión BD**
- Verifica variables de entorno en Render
- Confirma que Railway DB esté activo
- Verifica que el IP de Render esté permitido en Railway
- Prueba conexión: `php -r "new PDO('mysql:host=HOST;port=3306;dbname=DB', 'USER', 'PASS');"`

**Permisos uploads**
```bash
chmod 755 public/uploads public/uploads/usuarios
chown -R www-data:www-data public/uploads
```

**Geolocalización**
```bash
php bin/configurar-geolocalizacion.php
```

**Railway Database timeout**
- Railway puede suspender la BD por inactividad (plan gratuito)
- Solución: Acceder a Railway Dashboard para despertar la BD
- Considera plan de pago para BD siempre activa

---

## 📜 Licencia
Licencia **MIT**. Ver [LICENSE](LICENSE).

---

## 👤 Autor
**jshevvik** — https://github.com/jshevvik  
Versión **1.0.0** · Noviembre 2025
