# 🕒 Control Horario — Sistema de Fichajes y Asistencia

[![PHP](https://img.shields.io/badge/PHP-8.2-777BB4?logo=php&logoColor=white)](https://www.php.net/)
[![MySQL](https://img.shields.io/badge/MySQL-8.0+-4479A1?logo=mysql&logoColor=white)](https://www.mysql.com/)
[![License: MIT](https://img.shields.io/badge/License-MIT-green.svg)](LICENSE)

Aplicación para **gestión de horarios laborales**, fichaje con **geolocalización**, **solicitudes** (vacaciones/permiso/baja), **informes** en PDF y **panel administrativo**.

**Demo:** https://controlhorario-demo.onrender.com

---

## 📑 Índice
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
| Base de datos | MySQL 8.0+ |
| Frontend | HTML5, Bootstrap 5, JS |
| Mapas | Leaflet 1.9.4 |
| PDF | mPDF 8.2 |
| Deploy | Docker + Render |
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

1. Conecta el repo en https://render.com  
2. Render detecta el **Dockerfile** automáticamente  
3. Configura variables de entorno:

```env
DB_HOST=tu-host
DB_NAME=control_horario
DB_USER=tu_usuario
DB_PASS=tu_contraseña
DB_PORT=3306
BASE_URL=https://tu-app.onrender.com/
UPLOADS_DIR=/var/www/html/public/uploads/usuarios/
APP_ENV=production
```

> **Nota:** La BD en producción puede ser gestionada (p. ej., Railway).

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
```php
require_once __DIR__ . '/../includes/init.php';
requireLogin();
requireAdminOrSupervisor();
getEmpleado();
obtenerGeoConfigEmpleado($id);
registrarLogin($usuario, $id, true);
getNotificaciones($id);
```

---

## 🧯 Troubleshooting

**404 en rutas**
```bash
a2enmod rewrite && systemctl reload apache2
```

**Conexión BD**
Verifica variables de entorno o config.php

**Permisos uploads**
```bash
chmod 755 public/uploads public/uploads/usuarios
chown -R www-data:www-data public/uploads
```

**Geolocalización**
```bash
php bin/configurar-geolocalizacion.php
```

---

## 📜 Licencia
Licencia **MIT**. Ver [LICENSE](LICENSE).

---

## 👤 Autor
**jshevvik** — https://github.com/jshevvik  
Versión **1.0.0** · Noviembre 2025
