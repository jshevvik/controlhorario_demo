# Control Horario - Sistema de Control de Fichajes y Asistencia [+ ⏱️ Control de Horario — Demo



![License: MIT](https://img.shields.io/badge/License-MIT-blue.svg)[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](https://opensource.org/licenses/MIT)

![PHP](https://img.shields.io/badge/PHP-8.2-777BB4?logo=php&logoColor=white)[![PHP](https://img.shields.io/badge/PHP-8.2+-777BB4?logo=php&logoColor=white)](https://www.php.net/)

![MySQL](https://img.shields.io/badge/MySQL-8.0+-4479A1?logo=mysql&logoColor=white)[![MySQL](https://img.shields.io/badge/MySQL-8.0+-4479A1?logo=mysql&logoColor=white)](https://www.mysql.com/)

![Bootstrap](https://img.shields.io/badge/Bootstrap-5.3-7952B3?logo=bootstrap&logoColor=white)[![Leaflet](https://img.shields.io/badge/Leaflet-1.9.4-3b9fff.svg)](https://leafletjs.com/)



Sistema completo de gestión de fichajes, control de asistencia laboral y gestión de permisos/vacaciones para empresas.[Aplicación demo para control horario con fichaje geolocalizado, solicitudes, gestión de empleados y panel administrativo.



## CaracterísticasDemo (ejemplo): https://controlhorario-demo.onrender.com



### Gestión de Fichajes## Índice

- Sistema de entrada/salida con control horario

- Validación de geolocalización para fichajes1. [Características](#características)

- Registro automático de ubicación y hora2. [Stack tecnológico](#stack-tecnológico)

- Historial completo de fichajes por empleado3. [Instalación local](#instalación-local)

- Alertas de fichajes irregulares4. [Despliegue en Render (nota sobre Railway)](#despliegue-en-render)

5. [Estructura del proyecto](#estructura-del-proyecto)

### Administración de Empleados6. [Capturas / Screenshots](#capturas--screenshots)

- CRUD completo de empleados7. [Contribuir](#contribuir)

- Gestión de perfiles y roles8. [Licencia](#licencia)

- Asignación de horarios personalizados

- Control de permisos de acceso## Características

- Subida de archivos y documentación

- Fichaje (entrada / salida) con verificación geográfica (Leaflet)

### Solicitudes y Permisos- Gestión de empleados y roles (Admin / Supervisor / Empleado)

- Gestión de vacaciones, bajas y permisos- Solicitudes (vacaciones, permisos) con workflow de aprobación

- Sistema de aprobación por administradores- Informes y exportación a PDF (mPDF)

- Verificación automática de conflictos- Panel administrativo para gestión y reportes

- Notificaciones en tiempo real

- Historial de solicitudes## Stack tecnológico



### Informes y Reportes- Backend: PHP 8.2 + Apache

- Generación de informes en PDF- Base de datos: MySQL 8.0+ (en producción puede usarse Railway)

- Estadísticas de asistencia- Frontend: HTML5, Bootstrap 5, JavaScript

- Reportes por empleado o departamento- Mapas: Leaflet 1.9.4

- Exportación de datos- PDFs: mPDF 8.2 (via Composer)

- Contenedor: Docker (preparado para Deploy en Render)

### Seguridad

- Autenticación segura con bcrypt## Instalación local

- Control de sesiones

- Validación de geolocalizaciónRequisitos mínimos:

- Gestión de permisos por rol

- Protección contra inyección SQL (PDO)- PHP 8.2

- Composer

## Stack Tecnológico- MySQL 8+



- **Backend**: PHP 8.2Pasos rápidos:

- **Base de Datos**: MySQL 8.0+ (Railway)

- **Frontend**: Bootstrap 5.31. Clona el repositorio:

- **Mapas**: Leaflet.js 1.9.4

- **PDF**: mPDF 8.2   git clone https://github.com/jshevvik/controlhorario_demo.git

- **JavaScript**: jQuery   cd controlhorario_demo

- **Servidor Web**: Apache con mod_rewrite

- **Despliegue**: Docker (Render)2. Instala dependencias:



## Requisitos   composer install



- PHP >= 8.23. Crea copia de configuración y añade credenciales (local):

- MySQL >= 8.0

- Apache con mod_rewrite habilitado   cp config.example.php config.php

- Composer   (editar config.php con las credenciales de la BD)

- Extensiones PHP:

  - PDO4. Inicializa la base de datos (scripts incluidos):

  - pdo_mysql

  - mbstring   php bin/configurar-sistema.php

  - gd   php bin/configurar-geolocalizacion.php

  - zip

5. Ejecuta en local (modo desarrollo):

## Instalación

   php -S localhost:8000 -t public

### 1. Clonar el Repositorio

6. Abre http://localhost:8000

```bash

git clone https://github.com/jshevvik/controlhorario_demo.git## Despliegue en Render (nota sobre Railway)

cd controlhorario_demo

```Este repositorio contiene un Dockerfile preparado para ejecutarse en Render. En producción es habitual usar una BD gestionada (por ejemplo, Railway). Asegúrate de configurar las variables de entorno en el panel de Render:



### 2. Instalar Dependencias- DB_HOST

- DB_NAME

```bash- DB_USER

composer install- DB_PASS

```- DB_PORT (3306 por defecto)

- BASE_URL (p. ej. https://tu-app.onrender.com/)

### 3. Configurar Base de Datos- UPLOADS_DIR (p. ej. /var/www/html/public/uploads/usuarios/)



Crea una base de datos MySQL y ejecuta los scripts SQL:Notas:

- Si usas Railway, copia las credenciales de la base de datos desde Railway y pégalas en las variables de entorno de Render.

```bash- No incluyas credenciales en el repositorio. Añade `config.php` y `.env` a `.gitignore`.

mysql -u usuario -p nombre_bd < bin/crear-configuracion.sql

```## Estructura del proyecto



### 4. Configurar el Sistema- public/ — DocumentRoot (páginas web, rutas, assets)

- includes/ — Inicialización, funciones, helpers

Copia el archivo de configuración de ejemplo:- bin/ — Scripts CLI (configuración, limpieza, etc.)

- uploads/ — Archivos subidos por usuarios

```bash- vendor/ — Dependencias Composer

cp config.example.php public/config.php

```## Capturas / Screenshots



Edita `public/config.php` con tus credenciales:Si quieres añadir capturas, sube las imágenes a `assets/img/screenshots/` y referencia las rutas directamente en este README. Ejemplo:



```php![Dashboard](assets/img/screenshots/dashboard-01.png)

<?php

define('DB_HOST', 'tu-host');Si quieres que las incluya yo, sube las imágenes al repositorio o indícame los archivos y las agregaré al README.

define('DB_NAME', 'tu-base-datos');

define('DB_USER', 'tu-usuario');## Contribuir

define('DB_PASS', 'tu-contraseña');

define('BASE_URL', 'https://tu-dominio.com/');Si vas a contribuir:

define('APP_ENV', 'production');

```1. Crea un branch para tu cambio

2. Haz commits atómicos y descriptivos

### 5. Configurar Geolocalización3. Abre un pull request con descripción clara



Ejecuta el script de configuración:## Licencia



```bashEste proyecto se publica bajo la licencia MIT. Consulta el fichero LICENSE para más detalles.

php bin/configurar-geolocalizacion.php

```---



### 6. Configurar SistemaSi deseas, puedo añadir las capturas al README y comprobar en Render que el despliegue detecta correctamente las variables de Railway. También puedo revisar cualquier otra aparición de branding que quieras cambiar.



```bash[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](https://opensource.org/licenses/MIT)

php bin/configurar-sistema.php

```[![PHP](https://img.shields.io/badge/PHP-8.2+-777BB4?logo=php&logoColor=white)](https://www.php.net/)



## Despliegue en Render[![MySQL](https://img.shields.io/badge/MySQL-8.0+-4479A1?logo=mysql&logoColor=white)](https://www.mysql.com/)[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](https://opensource.org/licenses/MIT)



El proyecto incluye un `Dockerfile` para despliegue en Render:[![Bootstrap](https://img.shields.io/badge/Bootstrap-5.3-7952B3?logo=bootstrap&logoColor=white)](https://getbootstrap.com/)



1. Conecta tu repositorio de GitHub con Render[![PHP](https://img.shields.io/badge/PHP-8.2+-777BB4?logo=php&logoColor=white)](https://www.php.net/)

2. Crea un nuevo Web Service

3. Configura las variables de entorno:Aplicación completa de **gestión de horarios laborales** y control de asistencia con fichaje geolocalizado, solicitudes de permisos, informes y panel administrativo.

   - `DB_HOST`

   - `DB_NAME`[![MySQL](https://img.shields.io/badge/MySQL-8.0+-4479A1?logo=mysql&logoColor=white)](https://www.mysql.com/)[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](https://opensource.org/licenses/MIT)

   - `DB_USER`

   - `DB_PASS`**Demo:** [controlhorario-demo.onrender.com](https://controlhorario-demo.onrender.com)

   - `BASE_URL`

   - `APP_ENV=production`[![Bootstrap](https://img.shields.io/badge/Bootstrap-5.3-7952B3?logo=bootstrap&logoColor=white)](https://getbootstrap.com/)

4. Render detectará automáticamente el Dockerfile

---

## Uso

[![PHP](https://img.shields.io/badge/PHP-8.2+-777BB4?logo=php&logoColor=white)](https://www.php.net/)

### Panel de Administración

## 📖 Índice

Accede a `/admin` para:

- Gestionar empleadosAplicación completa de **gestión de horarios laborales** y control de asistencia con características avanzadas como fichaje geolocalizado, solicitudes de permisos, informes, y panel administrativo.

- Ver fichajes

- Aprobar solicitudes1. [Características](#características)

- Configurar el sistema

- Generar reportes2. [Stack Tecnológico](#stack-tecnológico)[![MySQL](https://img.shields.io/badge/MySQL-8.0+-4479A1?logo=mysql&logoColor=white)](https://www.mysql.com/)[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](https://opensource.org/licenses/MIT)



### Panel de Empleado3. [Estructura](#estructura)



Los empleados pueden:4. [Instalación](#instalación-local)**Demo en vivo:** [controlhorario-demo.onrender.com](https://controlhorario-demo.onrender.com)

- Fichar entrada/salida

- Solicitar permisos y vacaciones5. [Despliegue](#despliegue-render)

- Ver su historial de fichajes

- Editar su perfil6. [Seguridad](#seguridad)[![Bootstrap](https://img.shields.io/badge/Bootstrap-5.3-7952B3?logo=bootstrap&logoColor=white)](https://getbootstrap.com/)



### Fichaje7. [Uso](#guía-de-uso)



El sistema valida la geolocalización del empleado antes de permitir el fichaje. La ubicación debe estar dentro del radio configurado.8. [Desarrollo](#desarrollo)---



## Estructura del Proyecto9. [Ayuda](#soporte)



```[![PHP](https://img.shields.io/badge/PHP-8.2+-777BB4?logo=php&logoColor=white)](https://www.php.net/)

controlhorario_demo/

├── bin/                          # Scripts CLI---

│   ├── configurar-geolocalizacion.php

│   ├── configurar-sistema.php## 📖 Tabla de Contenidos

│   └── update-holidays.php

├── includes/                     # Librerías compartidas## ✨ Características

│   ├── funciones.php

│   └── init.phpAplicación completa de **gestión de horarios laborales** y control de asistencia con características avanzadas como fichaje geolocalizado, solicitudes de permisos, informes, y panel administrativo.

├── public/                       # Directorio público

│   ├── index.php                # Controlador principal### 👥 Gestión de Empleados

│   ├── login.php                # Página de login

│   ├── dashboard.php            # Panel principal- Crear, editar y eliminar empleados1. [Características](#características-principales)

│   ├── acciones/                # Controladores de acciones

│   ├── admin/                   # Panel de administración- Asignación de roles (Admin, Supervisor, Empleado)

│   ├── assets/                  # CSS, JS, imágenes

│   ├── fichaje/                 # Sistema de fichajes- Gestión de permisos granulares2. [Stack Tecnológico](#stack-tecnológico)[![MySQL](https://img.shields.io/badge/MySQL-8.0+-4479A1?logo=mysql&logoColor=white)](https://www.mysql.com/)[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](https://opensource.org/licenses/MIT)Aplicación PHP para gestión de control horario. Este repositorio incluye los archivos necesarios para ejecutar en local y desplegar una **demo** en Render.

│   └── notificaciones/          # Sistema de notificaciones

├── vendor/                       # Dependencias Composer- Perfiles personalizables con avatares

├── composer.json                # Configuración Composer

├── Dockerfile                   # Configuración Docker3. [Estructura](#estructura-del-proyecto)

└── README.md                    # Este archivo

```### ⏰ Fichajes



## Scripts de Mantenimiento- Entrada/salida manual4. [Instalación Local](#instalación-local)**Demo en vivo:** [controlhorario-demo.onrender.com](https://controlhorario-demo.onrender.com)



### Actualizar Festivos- Cronómetro en tiempo real



```bash- Historial de fichajes5. [Despliegue Render](#despliegue-en-render)

php bin/update-holidays.php

```- Cálculo automático de horas



### Limpiar Logs- Validación de horarios6. [Seguridad](#configuración-de-seguridad)[![Bootstrap](https://img.shields.io/badge/Bootstrap-5.3-7952B3?logo=bootstrap&logoColor=white)](https://getbootstrap.com/)



```bash

php bin/limpiar-logs.php

```### 📍 Geolocalización7. [Guía de Uso](#guía-de-uso)



## Seguridad- Fichaje con verificación GPS



- Las contraseñas se almacenan con bcrypt- Radio de cobertura configurable8. [Desarrollo](#desarrollo)---

- Todas las consultas usan prepared statements (PDO)

- Validación de sesiones en todas las páginas- Historial de ubicaciones

- Protección contra path traversal

- Validación de geolocalización para fichajes- Mapa interactivo con Leaflet 1.9.49. [Troubleshooting](#troubleshooting)

- Control de permisos basado en roles



## Contribuir

### 📋 Solicitudes10. [Soporte](#soporte)[![PHP](https://img.shields.io/badge/PHP-8.2+-777BB4?logo=php&logoColor=white)](https://www.php.net/)

Las contribuciones son bienvenidas. Por favor:

- Vacaciones, permisos y bajas

1. Fork el proyecto

2. Crea una rama para tu feature (`git checkout -b feature/NuevaFuncionalidad`)- Gestión de ausencias

3. Commit tus cambios (`git commit -m 'Añadir nueva funcionalidad'`)

4. Push a la rama (`git push origin feature/NuevaFuncionalidad`)- Workflow de aprobación

5. Abre un Pull Request

- Notificaciones integradas---## 📖 Tabla de Contenidos

## Licencia



Este proyecto está bajo la Licencia MIT. Ver el archivo `LICENSE` para más detalles.

### 📊 Reportes

## Soporte

- Generación de PDF

Para reportar problemas o solicitar funcionalidades, abre un issue en GitHub.

- Filtrado avanzado## ✨ Características PrincipalesAplicación completa de **gestión de horarios laborales** y control de asistencia con características avanzadas como fichaje geolocalizado, solicitudes de permisos, informes, y panel administrativo.

## Demo

- Exportación de datos

- **Demo en vivo**: https://controlhorario-demo.onrender.com

- **Base de datos**: Railway.com- Gráficas y estadísticas



## Autor



jshevvik### 🔒 Seguridad### 👥 Gestión de Empleados- [✨ Características](#características-principales)


- Autenticación bcrypt

- Sistema de roles granular- ✅ Crear, editar y eliminar empleados

- Auditoría de acciones

- Sesiones seguras- ✅ Asignación de roles (Admin, Supervisor, Empleado)- [🧰 Stack Tecnológico](#stack-tecnológico)[![MySQL](https://img.shields.io/badge/MySQL-8.0+-4479A1?logo=mysql&logoColor=white)](https://www.mysql.com/)## 🧰 Tecnologías

- Protección CSRF

- ✅ Gestión de permisos granulares

---

- ✅ Perfiles personalizables con avatares- [📦 Estructura](#estructura-del-proyecto)

## 🧰 Stack Tecnológico



| Componente | Tecnología |

|-----------|-----------|### ⏰ Control de Horarios- [🚀 Instalación Local](#instalación-local)**Demo en vivo:** [controlhorario-demo.onrender.com](https://controlhorario-demo.onrender.com)

| **Backend** | PHP 8.2 + Apache |

| **BD** | MySQL 8.0+ (Railway) |- ✅ Fichaje de entrada/salida manual

| **Frontend** | HTML5 + Bootstrap 5 + JavaScript |

| **Mapas** | Leaflet 1.9.4 |- ✅ Cronómetro integrado en tiempo real- [🚀 Despliegue Render](#despliegue-en-render)

| **PDF** | mPDF 8.2 |

| **Server** | Docker + Render |- ✅ Historial completo de fichajes

| **Gestor** | Composer |

- ✅ Cálculo automático de horas trabajadas- [🔐 Seguridad](#configuración-de-seguridad)[![Bootstrap](https://img.shields.io/badge/Bootstrap-5.3-7952B3?logo=bootstrap&logoColor=white)](https://getbootstrap.com/)- PHP 8.2 + Apache

---

- ✅ Validación de horarios laborales

## 📦 Estructura

- [📖 Guía de Uso](#guía-de-uso)

```

controlhorario_demo/### 📍 Geolocalización Avanzada

├── public/                 # DocumentRoot

│   ├── index.php          # Router- ✅ Fichaje con verificación de ubicación GPS- [🛠️ Desarrollo](#desarrollo)---

│   ├── login.php

│   ├── dashboard.php- ✅ Radio de cobertura configurable por empleado

│   ├── fichajes.php

│   ├── solicitudes.php- ✅ Historial detallado de ubicaciones- [🐛 Troubleshooting](#troubleshooting)

│   ├── informes.php

│   ├── geolocalizacion.php- ✅ Mapa interactivo con Leaflet 1.9.4

│   ├── admin/             # Módulo admin

│   ├── acciones/          # AJAX endpoints- [📞 Soporte](#soporte)- Composer (autoloader y dependencias)

│   ├── fichaje/

│   ├── notificaciones/### 📋 Solicitudes Administrativas

│   ├── assets/            # CSS, JS, imgs

│   └── uploads/           # Avatares, docs- ✅ Solicitudes de vacaciones, permisos y bajas

├── includes/

│   ├── init.php           # Inicialización- ✅ Gestión de ausencias

│   └── funciones.php      # Funciones

├── bin/                   # Scripts CLI- ✅ Workflow de aprobación con notificaciones---## 📖 Tabla de Contenidos

├── config.example.php

├── composer.json- ✅ Historial completo de solicitudes

├── Dockerfile

└── README.md

```

### 📊 Informes y Reportes

---

- ✅ Generación de reportes en PDF## ✨ Características PrincipalesUna aplicación completa de **gestión de horarios laborales** y control de asistencia con características avanzadas como fichaje geolocalizado, solicitudes de permisos, informes, y panel administrativo.- MySQL (externo/gestionado en producción)

## 🚀 Instalación Local

- ✅ Filtrado avanzado por empleado, fecha, tipo

### Requisitos

- PHP 8.2+- ✅ Exportación de datos

- MySQL 8.0+

- Composer- ✅ Gráficas y estadísticas

- Git

- ✅ Dashboard con resúmenes ejecutivos### 👥 Gestión de Empleados- [✨ Características](#características-principales)

### Pasos



```bash

git clone https://github.com/jshevvik/controlhorario_demo.git### 🔒 Seguridad Robusta- ✅ Crear, editar y eliminar empleados

cd controlhorario_demo

- ✅ Autenticación con contraseñas hasheadas (bcrypt)

composer install

cp config.example.php config.php- ✅ Sistema granular de roles y permisos- ✅ Asignación de roles (Admin, Supervisor, Empleado)- [🧰 Stack Tecnológico](#stack-tecnológico)- .htaccess para rutas amigables



# Editar credenciales BD en config.php- ✅ Auditoría de acciones administrativas

nano config.php

- ✅ Gestión segura de sesiones- ✅ Gestión de permisos granulares

# Crear BD

# mysql> CREATE DATABASE control_horario;- ✅ Protección CSRF



# Ejecutar- ✅ Sanitización de inputs- ✅ Perfiles personalizables con avatares- [📦 Estructura del Proyecto](#estructura-del-proyecto)

php -S localhost:8000 -t public



# Abrir: http://localhost:8000

```### 📱 Interfaz Responsive



---- ✅ Compatible con dispositivos móviles



## 🚀 Despliegue Render- ✅ UI moderna con Bootstrap 5### ⏰ Control de Horarios- [🚀 Instalación Local](#instalación-local)**Demo en vivo:** [controlhorario-demo.onrender.com](https://controlhorario-demo.onrender.com)- Docker (Render construye la imagen automáticamente)



### 1. Base de Datos en Railway- ✅ Componentes interactivos



1. Ir a [railway.com](https://railway.com)- ✅ Iconos profesionales con Iconify- ✅ Fichaje de entrada/salida manual

2. Crear BD MySQL

3. Copiar credenciales



### 2. App en Render---- ✅ Cronómetro integrado en tiempo real- [🚀 Despliegue en Render](#despliegue-en-render)



1. [render.com](https://render.com) → Conectar GitHub

2. New → Web Service

3. Seleccionar `controlhorario_demo`## 🧰 Stack Tecnológico- ✅ Historial completo de fichajes



**Configuración:**

- Build: `composer install`

- Start: (vacío)| Componente | Tecnología |- ✅ Cálculo automático de horas trabajadas- [🔐 Seguridad](#configuración-de-seguridad)

- Environment: Docker

|-----------|-----------|

### 3. Variables Render

| **Backend** | PHP 8.2 + Apache |- ✅ Validación de horarios laborales

```env

DB_HOST=tu-railway-host| **Base de Datos** | MySQL 8.0+ (Railway) |

DB_NAME=control_horario

DB_USER=usuario| **Frontend** | HTML5 + Bootstrap 5 + JavaScript |- [📖 Guía de Uso](#guía-de-uso)

DB_PASS=contraseña

DB_PORT=3306| **Mapas** | Leaflet 1.9.4 |

BASE_URL=https://tu-app.onrender.com/

UPLOADS_DIR=/var/www/html/public/uploads/usuarios/| **Reportes PDF** | mPDF 8.2 |### 📍 Geolocalización Avanzada

```

| **Servidor** | Docker + Render |

### 4. Inicializar BD

| **Dependencias** | Composer |- ✅ Fichaje con verificación de ubicación GPS- [🛠️ Desarrollo](#desarrollo)---## 📦 Estructura

```bash

php /var/www/html/bin/configurar-sistema.php

php /var/www/html/bin/configurar-geolocalizacion.php

```---- ✅ Radio de cobertura configurable por empleado



---



## 🔒 Seguridad## 📦 Estructura del Proyecto- ✅ Historial detallado de ubicaciones- [🐛 Troubleshooting](#troubleshooting)



### No Commitear Datos

```bash

echo "config.php" >> .gitignore```- ✅ Mapa interactivo con Leaflet.js

echo ".env" >> .gitignore

```controlhorario_demo/



### Usar Variables de Entorno├── public/                     # DocumentRoot (carpeta visible)public/ # DocumentRoot

```env

DB_HOST=localhost│   ├── index.php              # Router principal

DB_NAME=control_horario

DB_USER=root│   ├── login.php              # Página de login### 📋 Solicitudes Administrativas

DB_PASS=contraseña

BASE_URL=https://tu-dominio.com/│   ├── dashboard.php          # Dashboard

```

│   ├── fichajes.php           # Control de fichajes- ✅ Solicitudes de vacaciones, permisos y bajas---

### Headers Incluidos

- HTTPS en producción│   ├── solicitudes.php        # Gestión de solicitudes

- Sesiones SameSite

- CSP│   ├── informes.php           # Reportes- ✅ Gestión de ausencias

- Protección clickjacking

- Hashing bcrypt│   ├── geolocalizacion.php    # Configuración GPS



---│   ├── admin/                 # Módulo administrativo- ✅ Workflow de aprobación con notificaciones## 📸 Capturas de Pantalla.htaccess



## 📖 Guía de Uso│   ├── acciones/              # Endpoints AJAX



### Login│   ├── fichaje/               # Procesamiento de fichajes- ✅ Historial completo de solicitudes

URL: `https://controlhorario-demo.onrender.com/login`

│   ├── notificaciones/        # Sistema de notificaciones

### Fichajar

Dashboard → Fichajar → Entrada/Salida → Confirmar│   ├── assets/                # CSS, JS, imágenes## ✨ Características Principales



### Solicitudes│   └── uploads/               # Avatares y documentos

Solicitudes → Nueva → Tipo → Fechas → Enviar

├── includes/### 📊 Informes y Reportes

### Admin

- **Empleados:** CRUD de personal│   ├── init.php               # Inicialización

- **Solicitudes:** Aprobar/rechazar

- **Seguridad:** Roles y permisos│   └── funciones.php          # Funciones reutilizables- ✅ Generación de reportes en PDFindex.php

- **Configuración:** Ajustes

├── bin/                       # Scripts CLI

### Informes

Informes → Período → Empleados → Generar PDF├── config.example.php         # Configuración ejemplo- ✅ Filtrado avanzado por empleado, fecha, tipo



---├── composer.json              # Dependencias



## 🛠️ Desarrollo├── Dockerfile                 # Docker- ✅ Exportación de datos### 👥 Gestión de Empleados



### Autenticación└── README.md                  # Este archivo



```php```- ✅ Gráficas y estadísticas

<?php

require_once __DIR__ . '/../includes/init.php';



requireLogin();              # Usuario logueado---- ✅ Crear, editar y eliminar empleados### Dashboard Principaladmin/

requireAdmin();              # Solo admin

requireAdminOrSupervisor();  # Admin o supervisor

```

## 🚀 Instalación Local### 🔒 Seguridad Robusta

### Base de Datos



```php

$stmt = $pdo->prepare("SELECT * FROM empleados WHERE id = ?");### Requisitos- ✅ Autenticación con contraseñas hasheadas (bcrypt)- ✅ Asignación de roles (Admin, Supervisor, Empleado)

$stmt->execute([$id]);

$emp = $stmt->fetch(PDO::FETCH_ASSOC);- PHP 8.2+

```

- MySQL 8.0+- ✅ Sistema granular de roles y permisos

### Funciones Útiles

- Composer

```php

getEmpleado()                       # Usuario actual- Git- ✅ Auditoría de acciones administrativas- ✅ Gestión de permisos granulares![Dashboard Principal](./docs/screenshots/dashboard.png "Vista principal del dashboard")acciones/

obtenerGeoConfigEmpleado($empId)   # Config GPS

registrarLogin($user, $id, $ok)    # Log acceso

getNotificaciones($empId)           # Notificaciones

```### Pasos- ✅ Gestión segura de sesiones



### Rutas Amigables



``````bash- ✅ Protección CSRF- ✅ Perfiles personalizables con avatares

/dashboard          → public/dashboard.php

/admin/empleados    → public/admin/empleados.php# 1. Clonar

/fichajar           → public/fichaje/procesar-fichaje.php

```git clone https://github.com/jshevvik/controlhorario_demo.git- ✅ Sanitización de inputs



---cd controlhorario_demo



## 🐛 Troubleshooting*Panel de bienvenida con resumen de fichajes, solicitudes y accesos rápidos*fichaje/



**Error 404 en admin:**# 2. Instalar dependencias

```bash

a2enmod rewritecomposer install---

systemctl reload apache2

```



**BD no conecta:**# 3. Configurar### ⏰ Control de Horarios

```bash

echo $DB_HOSTcp config.example.php config.php

echo $DB_USER

```nano config.php  # Editar credenciales BD## 🧰 Stack Tecnológico



**Permisos uploads:**

```bash

chmod 755 public/uploads# 4. Crear BD (MySQL)- ✅ Fichaje de entrada/salida manualnotificaciones/

chmod 755 public/uploads/usuarios

chown -R www-data:www-data public/uploads# mysql> CREATE DATABASE control_horario;

```

| Componente | Tecnología |

**GPS no funciona:**

```bash# 5. Iniciar servidor

php bin/configurar-geolocalizacion.php

```php -S localhost:8000 -t public|-----------|-----------|- ✅ Cronómetro integrado en tiempo real



---



## 📊 Números# 6. Abrir navegador| **Backend** | PHP 8.2 + Apache |



- ~15,000 líneas PHP# http://localhost:8000

- 100+ funciones

- 15+ tablas BD```| **Base de Datos** | MySQL 8.0+ (Railway) |- ✅ Historial completo de fichajes### Gestión de Fichajes404.php, login.php, ...

- 30+ endpoints

- 25+ páginas



------| **Frontend** | HTML5 + Bootstrap 5 + JavaScript |



## 📝 Licencia



MIT - Ver [LICENSE](LICENSE)## 🚀 Despliegue en Render| **Mapas** | Leaflet.js 1.9.4 |- ✅ Cálculo automático de horas trabajadas



---



## 👨‍💻 Autor### Paso 1: Base de Datos en Railway| **Reportes PDF** | mPDF 8.2 |



**jshevvik** - [GitHub](https://github.com/jshevvik)



Noviembre 2025 | v1.0.01. Ir a [railway.com](https://railway.com)| **Servidor** | Docker + Render |- ✅ Validación de horarios laborales![Fichajes](./docs/screenshots/fichajes.png "Panel de fichajes y control horario")uploads/ # Subidas de usuarios (no se versiona)



---2. Crear base de datos MySQL



## 🤝 Contribuir3. Copiar credenciales de conexión| **Dependencias** | Composer |



1. Fork

2. `git checkout -b feature/MiFeature`

3. `git commit -m 'Add: descripción'`### Paso 2: Conectar GitHub a Render

4. `git push origin feature/MiFeature`

5. Pull Request



---1. Ir a [render.com](https://render.com)---



## 📞 Soporte2. Crear cuenta y conectar GitHub



- 🐛 [Issues](https://github.com/jshevvik/controlhorario_demo/issues)3. Seleccionar repositorio### 📍 Geolocalización Avanzada*Control de entrada/salida con cronómetro en tiempo real*includes/

- 💡 [Discussions](https://github.com/jshevvik/controlhorario_demo/discussions)



---

### Paso 3: Crear Servicio Web## 📦 Estructura del Proyecto

## 🔗 Enlaces



- [Demo](https://controlhorario-demo.onrender.com)

- [Bootstrap](https://getbootstrap.com)1. Click "New" → "Web Service"- ✅ Fichaje con verificación de ubicación GPS

- [Leaflet](https://leafletjs.com)

- [Railway](https://railway.com)2. Seleccionar `controlhorario_demo`

- [Render](https://render.com)

- [PHP](https://www.php.net/manual)3. Configurar:```

- [MySQL](https://dev.mysql.com/doc/)

   - **Build:** `composer install`

---

   - **Start:** (vacío)controlhorario_demo/- ✅ Radio de cobertura configurable por empleadovendor/ # Generado por Composer

**Última actualización:** Noviembre 2025

   - **Environment:** Docker

├── public/                     # DocumentRoot (carpeta visible)

### Paso 4: Variables de Entorno

│   ├── index.php              # Router principal- ✅ Historial detallado de ubicaciones

Agregar en Render:

│   ├── login.php              # Página de login

```env

DB_HOST=tu-railway-host│   ├── dashboard.php          # Dashboard- ✅ Mapa interactivo con Leaflet.js### Panel Administrativoconfig.example.php

DB_NAME=control_horario

DB_USER=tu_usuario│   ├── fichajes.php           # Control de fichajes

DB_PASS=tu_contraseña

DB_PORT=3306│   ├── solicitudes.php        # Gestión de solicitudes

BASE_URL=https://tu-app.onrender.com/

UPLOADS_DIR=/var/www/html/public/uploads/usuarios/│   ├── informes.php           # Reportes

```

│   ├── geolocalizacion.php    # Configuración GPS### 📋 Solicitudes Administrativas![Administración](./docs/screenshots/administracion.png "Panel de administración")composer.json

### Paso 5: Inicializar BD

│   ├── admin/                 # Módulo administrativo

```bash

php /var/www/html/bin/configurar-sistema.php│   ├── acciones/              # Endpoints AJAX- ✅ Solicitudes de vacaciones

php /var/www/html/bin/configurar-geolocalizacion.php

```│   ├── fichaje/               # Procesamiento de fichajes



---│   ├── notificaciones/        # Sistema de notificaciones- ✅ Solicitudes de permisos*Centro administrativo con acceso a empleados, solicitudes y configuración*Dockerfile



## 🔐 Configuración de Seguridad│   ├── assets/                # CSS, JS, imágenes



### No Commitear Datos Sensibles│   └── uploads/               # Avatares y documentos- ✅ Solicitudes de bajas médicas

```bash

echo "config.php" >> .gitignore├── includes/

echo ".env" >> .gitignore

```│   ├── init.php               # Inicialización- ✅ Gestión de ausencias



### Variables de Entorno│   └── funciones.php          # Funciones reutilizables

```env

DB_HOST=localhost├── bin/                       # Scripts CLI- ✅ Workflow de aprobación con notificaciones

DB_NAME=control_horario

DB_USER=root├── config.example.php         # Configuración ejemplo

DB_PASS=tu_contraseña

BASE_URL=https://tu-dominio.com/├── composer.json              # Dependencias- ✅ Historial completo de solicitudes### Solicitudes de Vacaciones

```

├── Dockerfile                 # Docker

### Headers de Seguridad

- ✅ HTTPS obligatorio en producción└── README.md                  # Este archivo

- ✅ Sesiones seguras con SameSite

- ✅ Content Security Policy```

- ✅ Protección clickjacking

- ✅ Hashing bcrypt### 📊 Informes y Reportes![Solicitudes](./docs/screenshots/solicitudes.png "Gestión de solicitudes de vacaciones y permisos")## 🔐 Seguridad



------



## 📖 Guía de Uso- ✅ Generación de reportes en PDF



### Acceder## 🚀 Instalación Local

- URL: `https://controlhorario-demo.onrender.com/login`

- Cambiar contraseña en "Mi Perfil"- ✅ Filtrado avanzado por empleado, fecha, tipo*Workflow de solicitudes con aprobación multinivel*- No subir `config.php`, contraseñas ni datos reales.



### Fichajar### Requisitos

1. Dashboard → "Fichajar"

2. Seleccionar: Entrada o Salida- PHP 8.2+- ✅ Exportación de datos

3. Confirmar ubicación (si está habilitada)

4. Click "Confirmar Fichaje"- MySQL 8.0+



### Solicitar Permisos- Composer- ✅ Gráficas y estadísticas- En Render usar variables de entorno: `BASE_URL`, `DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASS`, `UPLOADS_DIR` (opcional).

1. "Solicitudes" → "Nueva Solicitud"

2. Tipo: Vacaciones, Permiso, Baja, Ausencia- Git

3. Elegir fechas

4. Enviar- ✅ Dashboard con resúmenes ejecutivos



### Panel Admin### Pasos

- **Empleados:** Crear, editar, eliminar

- **Solicitudes:** Aprobar/rechazar---

- **Seguridad:** Roles y permisos

- **Configuración:** Ajustes del sistema```bash



### Informes# 1. Clonar### 🔒 Seguridad Robusta

1. "Informes" → Seleccionar período

2. Elegir empleadosgit clone https://github.com/jshevvik/controlhorario_demo.git

3. "Generar PDF"

cd controlhorario_demo- ✅ Autenticación con contraseñas hasheadas (bcrypt)## 🖥️ Ejecución local

---



## 🛠️ Desarrollo

# 2. Instalar dependencias- ✅ Sistema granular de roles y permisos

### Autenticación

composer install

```php

<?php- ✅ Auditoría de acciones administrativas## ✨ Características Principales```bash

require_once __DIR__ . '/../includes/init.php';

# 3. Configurar

// Verificar login

requireLogin();cp config.example.php config.php- ✅ Gestión segura de sesiones



// Verificar adminnano config.php  # Editar credenciales BD

requireAdmin();

- ✅ Protección CSRFcomposer install

// Verificar admin o supervisor

requireAdminOrSupervisor();# 4. Crear BD (MySQL)

```

# mysql> CREATE DATABASE control_horario;- ✅ Sanitización de inputs

### Base de Datos



```php

// Consulta preparada# 5. Iniciar servidor### 👥 Gestión de Empleadoscp config.example.php config.php

$stmt = $pdo->prepare("SELECT * FROM empleados WHERE id = ?");

$stmt->execute([$id]);php -S localhost:8000 -t public

$emp = $stmt->fetch(PDO::FETCH_ASSOC);

```### 📱 Interfaz Responsive



### Funciones Útiles# 6. Abrir navegador



```php# http://localhost:8000- ✅ Compatible con dispositivos móviles- ✅ Crear, editar y eliminar empleados# Ajusta DB_* si hace falta

getEmpleado()                       # Usuario actual

obtenerGeoConfigEmpleado($empId)   # Config GPS```

registrarLogin($usuario, $id, $ok) # Log acceso

getNotificaciones($empId)           # Notificaciones- ✅ UI moderna con Bootstrap 5

```

---

### Rutas

- ✅ Componentes interactivos- ✅ Asignación de roles (Admin, Supervisor, Empleado)php -S localhost:8000 -t public

URLs amigables con `.htaccess`:

```## 🚀 Despliegue en Render

/dashboard          → public/dashboard.php

/admin/empleados    → public/admin/empleados.php- ✅ Iconos profesionales con Iconify

/fichajar           → public/fichaje/procesar-fichaje.php

```### Paso 1: Base de Datos en Railway



---- ✅ Gestión de permisos granulares# Ir a http://localhost:8000



## 🐛 Troubleshooting1. Ir a [railway.com](https://railway.com)



### Error 404 en admin2. Crear base de datos MySQL---

```bash

a2enmod rewrite3. Copiar credenciales de conexión

systemctl reload apache2

```- ✅ Perfiles personalizables con avatares



### Conexión BD fallida### Paso 2: Conectar GitHub a Render

```bash

echo $DB_HOST## 🧰 Stack Tecnológico

echo $DB_USER

```1. Ir a [render.com](https://render.com)



### Permisos uploads2. Crear cuenta y conectar GitHub### ⏰ Control de Horarios

```bash

chmod 755 public/uploads3. Seleccionar repositorio

chmod 755 public/uploads/usuarios

chown -R www-data:www-data public/uploads| Componente | Tecnología |- ✅ Fichaje de entrada/salida manual

```

### Paso 3: Crear Servicio Web

### Geolocalización no funciona

```bash|-----------|-----------|- ✅ Cronómetro integrado en tiempo real

php bin/configurar-geolocalizacion.php

```1. Click "New" → "Web Service"



---2. Seleccionar `controlhorario_demo`| **Backend** | PHP 8.2 + Apache |- ✅ Historial completo de fichajes



## 📊 Estadísticas3. Configurar:



- **Líneas PHP:** ~15,000+   - **Build:** `composer install`| **Base de Datos** | MySQL 8.0+ |- ✅ Cálculo automático de horas trabajadas

- **Funciones:** 100+

- **Tablas BD:** 15+   - **Start:** (vacío)

- **Endpoints:** 30+

- **Páginas:** 25+   - **Environment:** Docker| **Frontend** | HTML5 + Bootstrap 5 + JavaScript |- ✅ Validación de horarios laborales



---



## 📝 Licencia### Paso 4: Variables de Entorno| **Mapas** | Leaflet 1.9.4 |



Licencia **MIT**. Ver [LICENSE](LICENSE)



---Agregar en Render:| **Reportes PDF** | mPDF 8.2 |### 📍 Geolocalización Avanzada



## 👨‍💻 Autor



**jshevvik** - [GitHub](https://github.com/jshevvik)```env| **Servidor** | Docker + Render |- ✅ Fichaje con verificación de ubicación GPS



Noviembre 2025 | v1.0.0DB_HOST=tu-railway-host



---DB_NAME=control_horario| **Gestor de Dependencias** | Composer |- ✅ Radio de cobertura configurable por empleado



## 🤝 ContribucionesDB_USER=tu_usuario



1. ForkDB_PASS=tu_contraseña- ✅ Historial detallado de ubicaciones

2. Crear rama: `git checkout -b feature/MiFeature`

3. Commit: `git commit -m 'Add: descripción'`DB_PORT=3306

4. Push: `git push origin feature/MiFeature`

5. Pull RequestBASE_URL=https://tu-app.onrender.com/---- ✅ Mapa interactivo con Leaflet.js



---UPLOADS_DIR=/var/www/html/public/uploads/usuarios/



## 📞 Soporte```



- 🐛 [Issues](https://github.com/jshevvik/controlhorario_demo/issues)

- 💡 [Discussions](https://github.com/jshevvik/controlhorario_demo/discussions)

### Paso 5: Inicializar BD## 📦 Estructura del Proyecto### 📋 Solicitudes Administrativas

---



## 🔗 Enlaces Útiles

```bash- ✅ Solicitudes de vacaciones

- [Demo en vivo](https://controlhorario-demo.onrender.com)

- [Bootstrap 5](https://getbootstrap.com)php /var/www/html/bin/configurar-sistema.php

- [Leaflet 1.9.4](https://leafletjs.com)

- [Railway BD](https://railway.com)php /var/www/html/bin/configurar-geolocalizacion.php```- ✅ Solicitudes de permisos

- [Render](https://render.com)

- [PHP Manual](https://www.php.net/manual)```

- [MySQL Docs](https://dev.mysql.com/doc/)

controlhorario_demo/- ✅ Solicitudes de bajas médicas

---

---

**Última actualización:** Noviembre 2025

├── public/                          # DocumentRoot (carpeta visible)- ✅ Gestión de ausencias

## 🔐 Configuración de Seguridad

│   ├── index.php                   # Router principal- ✅ Workflow de aprobación con notificaciones

### No Commitear Datos Sensibles

```bash│   ├── login.php                   # Página de login- ✅ Historial completo de solicitudes

echo "config.php" >> .gitignore

echo ".env" >> .gitignore│   ├── dashboard.php               # Dashboard principal

```

│   ├── fichajes.php                # Control de fichajes### 📊 Informes y Reportes

### Variables de Entorno

```env│   ├── solicitudes.php             # Gestión de solicitudes- ✅ Generación de reportes en PDF

DB_HOST=localhost

DB_NAME=control_horario│   ├── informes.php                # Generación de informes- ✅ Filtrado avanzado por empleado, fecha, tipo

DB_USER=root

DB_PASS=tu_contraseña│   ├── geolocalizacion.php         # Configuración GPS- ✅ Exportación de datos

BASE_URL=https://tu-dominio.com/

```│   ├── administracion.php          # Panel admin- ✅ Gráficas y estadísticas



### Headers de Seguridad│   ├── admin/                      # Módulo administrativo- ✅ Dashboard con resúmenes ejecutivos

- ✅ HTTPS obligatorio en producción

- ✅ Sesiones seguras con SameSite│   │   ├── empleados.php

- ✅ Content Security Policy

- ✅ Protección clickjacking│   │   ├── configuracion.php### 🔒 Seguridad Robusta

- ✅ Hashing bcrypt

│   │   ├── seguridad.php- ✅ Autenticación con contraseñas hasheadas (bcrypt)

---

│   │   └── ...- ✅ Sistema granular de roles y permisos

## 📖 Guía de Uso

│   ├── acciones/                   # Endpoints AJAX/formularios- ✅ Auditoría de acciones administrativas

### Acceder

- URL: `https://controlhorario-demo.onrender.com/login`│   ├── fichaje/                    # Procesamiento de fichajes- ✅ Gestión segura de sesiones

- Cambiar contraseña en "Mi Perfil"

│   ├── notificaciones/             # Sistema de notificaciones- ✅ Protección CSRF

### Fichajar

1. Dashboard → "Fichajar"│   ├── assets/                     # CSS, JS, imágenes- ✅ Sanitización de inputs

2. Seleccionar: Entrada o Salida

3. Confirmar ubicación (si está habilitada)│   └── uploads/                    # Avatares y documentos

4. Click "Confirmar Fichaje"

├── includes/### 📱 Interfaz Responsive

### Solicitar Permisos

1. "Solicitudes" → "Nueva Solicitud"│   ├── init.php                    # Inicialización- ✅ Compatible con dispositivos móviles

2. Tipo: Vacaciones, Permiso, Baja, Ausencia

3. Elegir fechas│   └── funciones.php               # Funciones reutilizables- ✅ UI moderna con Bootstrap 5

4. Enviar

├── bin/                            # Scripts CLI- ✅ Componentes interactivos

### Panel Admin

- **Empleados:** Crear, editar, eliminar│   ├── configurar-sistema.php- ✅ Iconos profesionales con Iconify

- **Solicitudes:** Aprobar/rechazar

- **Seguridad:** Roles y permisos│   ├── configurar-geolocalizacion.php

- **Configuración:** Ajustes del sistema

│   └── update-holidays.php---

### Informes

1. "Informes" → Seleccionar período├── config.example.php              # Ejemplo de configuración

2. Elegir empleados

3. "Generar PDF"├── composer.json                   # Dependencias## 🧰 Stack Tecnológico



---├── Dockerfile                      # Configuración Docker



## 🛠️ Desarrollo└── README.md                       # Este archivo| Componente | Tecnología |



### Autenticación```|-----------|-----------|



```php| **Backend** | PHP 8.2 + Apache |

<?php

require_once __DIR__ . '/../includes/init.php';---| **Base de Datos** | MySQL 8.0+ |



// Verificar login| **Frontend** | HTML5 + Bootstrap 5 + JavaScript Vanilla |

requireLogin();

## 🚀 Instalación Local| **Mapas** | Leaflet 1.9.4 |

// Verificar admin

requireAdmin();| **Reportes PDF** | mPDF 8.2 |



// Verificar admin o supervisor### Requisitos Previos| **Servidor** | Docker + Render |

requireAdminOrSupervisor();

```- PHP 8.2 o superior| **Gestor de Dependencias** | Composer |



### Base de Datos- MySQL 8.0 o superior



```php- Composer---

// Consulta preparada

$stmt = $pdo->prepare("SELECT * FROM empleados WHERE id = ?");- Git

$stmt->execute([$id]);

$emp = $stmt->fetch(PDO::FETCH_ASSOC);## 📦 Estructura del Proyecto

```

### Pasos

### Funciones Útiles

```

```php

getEmpleado()                       # Usuario actual```bashcontrolhorario_demo/

obtenerGeoConfigEmpleado($empId)   # Config GPS

registrarLogin($usuario, $id, $ok) # Log acceso# 1. Clonar el repositorio├── public/                          # DocumentRoot (carpeta visible)

getNotificaciones($empId)           # Notificaciones

```git clone https://github.com/jshevvik/controlhorario_demo.git│   ├── index.php                   # Router principal



### Rutascd controlhorario_demo│   ├── login.php                   # Página de login



URLs amigables con `.htaccess`:│   ├── dashboard.php               # Dashboard principal

```

/dashboard          → public/dashboard.php# 2. Instalar dependencias PHP│   ├── fichajes.php                # Control de fichajes

/admin/empleados    → public/admin/empleados.php

/fichajar           → public/fichaje/procesar-fichaje.phpcomposer install│   ├── solicitudes.php             # Gestión de solicitudes

```

│   ├── informes.php                # Generación de informes

---

# 3. Copiar archivo de configuración│   ├── geolocalizacion.php         # Configuración GPS

## 🐛 Troubleshooting

cp config.example.php config.php│   ├── administracion.php          # Panel admin

### Error 404 en admin

```bash│   ├── admin/                      # Módulo administrativo

a2enmod rewrite

systemctl reload apache2# 4. Editar credenciales de base de datos│   │   ├── empleados.php           # Gestión de empleados

```

nano config.php│   │   ├── configuracion.php       # Configuración del sistema

### Conexión BD fallida

```bash# Modificar: DB_HOST, DB_NAME, DB_USER, DB_PASS│   │   ├── seguridad.php           # Gestión de seguridad

echo $DB_HOST

echo $DB_USER│   │   ├── ver-solicitudes.php     # Aprobación de solicitudes

```

# 5. Crear base de datos en MySQL│   │   └── ...

### Permisos uploads

```bash# mysql> CREATE DATABASE control_horario;│   ├── acciones/                   # Endpoints AJAX/formularios

chmod 755 public/uploads

chmod 755 public/uploads/usuarios│   ├── fichaje/                    # Procesamiento de fichajes

chown -R www-data:www-data public/uploads

```# 6. Iniciar servidor PHP│   ├── notificaciones/             # Sistema de notificaciones



### Geolocalización no funcionaphp -S localhost:8000 -t public│   ├── assets/                     # CSS, JS, imágenes

```bash

php bin/configurar-geolocalizacion.php│   │   ├── css/                    # Estilos personalizados

```

# 7. Abrir navegador│   │   ├── js/                     # Scripts del cliente

---

# http://localhost:8000│   │   └── img/                    # Imágenes

## 📊 Estadísticas

```│   └── uploads/                    # Avatares y documentos

- **Líneas PHP:** ~15,000+

- **Funciones:** 100+├── includes/                        # Código PHP (fuera de web root)

- **Tablas BD:** 15+

- **Endpoints:** 30+---│   ├── init.php                    # Inicialización de la app

- **Páginas:** 25+

│   └── funciones.php               # Funciones reutilizables

---

## 🚀 Despliegue en Render├── bin/                            # Scripts CLI

## 📝 Licencia

│   ├── configurar-sistema.php      # Instalación inicial

Licencia **MIT**. Ver [LICENSE](LICENSE)

### Paso 1: Conectar GitHub a Render│   ├── configurar-geolocalizacion.php

---

1. Ir a [render.com](https://render.com)│   └── update-holidays.php

## 👨‍💻 Autor

2. Crear cuenta gratuita├── config.example.php              # Ejemplo de configuración

**jshevvik** - [GitHub](https://github.com/jshevvik)

3. Conectar repositorio de GitHub├── composer.json                   # Dependencias PHP

Noviembre 2025 | v1.0.0

├── Dockerfile                      # Configuración Docker

---

### Paso 2: Crear Servicio Web└── README.md                       # Este archivo

## 🤝 Contribuciones

1. Click en "New" → "Web Service"```

1. Fork

2. Crear rama: `git checkout -b feature/MiFeature`2. Seleccionar repositorio `controlhorario_demo`

3. Commit: `git commit -m 'Add: descripción'`

4. Push: `git push origin feature/MiFeature`3. Configurar:---

5. Pull Request

   - **Build Command:** `composer install`

---

   - **Start Command:** (dejar vacío)## 🚀 Instalación y Uso

## 📞 Soporte

   - **Environment:** Docker

- 🐛 [Issues](https://github.com/jshevvik/controlhorario_demo/issues)

- 💡 [Discussions](https://github.com/jshevvik/controlhorario_demo/discussions)### Requisitos Previos



---### Paso 3: Variables de Entorno- PHP 8.2 o superior



## 🔗 Enlaces ÚtilesAgregar en el panel de Render:- MySQL 8.0 o superior



- [Demo en vivo](https://controlhorario-demo.onrender.com)- Composer

- [Bootstrap](https://getbootstrap.com)

- [Leaflet.js](https://leafletjs.com)```env- Git

- [Railway](https://railway.com)

- [Render](https://render.com)DB_HOST=tu-mysql-host.render.com

- [PHP Manual](https://www.php.net/manual)

- [MySQL Docs](https://dev.mysql.com/doc/)DB_NAME=control_horario### 1️⃣ Instalación Local



---DB_USER=tu_usuario



**Última actualización:** Noviembre 2025DB_PASS=tu_contraseña_segura```bash


DB_PORT=3306# Clonar el repositorio

BASE_URL=https://tu-app.onrender.com/git clone https://github.com/jshevvik/controlhorario_demo.git

UPLOADS_DIR=/var/www/html/public/uploads/usuarios/cd controlhorario_demo

```

# Instalar dependencias PHP

### Paso 4: Inicializar Base de Datoscomposer install

Desde la consola de Render:

# Copiar archivo de configuración

```bashcp config.example.php config.php

php /var/www/html/bin/configurar-sistema.php

php /var/www/html/bin/configurar-geolocalizacion.php# Editar credenciales de base de datos

```nano config.php

# Modificar DB_HOST, DB_NAME, DB_USER, DB_PASS

---

# Iniciar servidor PHP de desarrollo

## 🔐 Configuración de Seguridadphp -S localhost:8000 -t public



### No Commitear Datos Sensibles# Acceder a la aplicación

```bash# Abrir navegador en: http://localhost:8000

# Agregar a .gitignore```

echo "config.php" >> .gitignore

echo ".env" >> .gitignore### 2️⃣ Despliegue en Render (Producción)

```

#### Paso 1: Conectar GitHub a Render

### Variables de Entorno1. Ir a [render.com](https://render.com) y crear cuenta gratuita

```env2. Conectar tu cuenta de GitHub

# Usar variables de entorno, nunca hardcodear3. Seleccionar el repositorio `controlhorario_demo`

DB_HOST=localhost

DB_NAME=control_horario#### Paso 2: Crear Servicio Web

DB_USER=root1. Crear nuevo "Web Service"

DB_PASS=tu_contraseña2. Configurar:

BASE_URL=https://tu-dominio.com/   - **Build Command:** `composer install`

```   - **Start Command:** (dejar vacío - Apache maneja todo)

   - **Environment:** Docker

### Headers de Seguridad Incluidos

- ✅ HTTPS obligatorio en producción#### Paso 3: Variables de Entorno

- ✅ Sesiones seguras con SameSiteAgregar en el panel de Render → Environment:

- ✅ Content Security Policy (CSP)

- ✅ Protección contra clickjacking```env

- ✅ Hashing seguro de contraseñas (bcrypt)DB_HOST=tu-mysql-host.render.com

DB_NAME=control_horario

---DB_USER=tu_usuario_bd

DB_PASS=tu_contraseña_segura

## 📖 Guía de UsoDB_PORT=3306

BASE_URL=https://tu-app.onrender.com/

### Acceder a la AplicaciónUPLOADS_DIR=/var/www/html/public/uploads/usuarios/

1. Ir a `https://controlhorario-demo.onrender.com/login````

2. Usar credenciales proporcionadas

3. Cambiar contraseña en "Mi Perfil"#### Paso 4: Inicializar Base de Datos

Desde la consola de Render:

### Registrar Fichajes

1. Dashboard → **"Fichajar"**```bash

2. Seleccionar: **Entrada** o **Salida**# Crear tabla de configuración

3. Confirmar ubicación (si está habilitada)php /var/www/html/bin/configurar-sistema.php

4. Click en **"Confirmar Fichaje"**

# Configurar geolocalización

### Solicitar Permisosphp /var/www/html/bin/configurar-geolocalizacion.php

1. **"Solicitudes"** → **"Nueva Solicitud"**```

2. Tipo: Vacaciones, Permiso, Baja, Ausencia

3. Elegir fechas---

4. Enviar para aprobación

## 🔐 Configuración de Seguridad

### Panel Administrativo

**"Administración"** con opciones:### Protección de Datos

- **Empleados:** Crear, editar, eliminar personal```bash

- **Solicitudes:** Aprobar/rechazar solicitudes# NO commitear archivos con datos reales

- **Seguridad:** Gestionar roles y permisosecho "config.php" >> .gitignore

- **Configuración:** Ajustes del sistemaecho ".env" >> .gitignore

```

### Generar Informes

1. **"Informes"** → Seleccionar período### Estructura de Directorios Segura

2. Elegir empleados```

3. **"Generar PDF"**/var/www/html/              # Raíz del proyecto

├── public/                 # ✅ Visible públicamente (DocumentRoot)

---└── includes/               # ✅ Protegida, fuera de web root

```

## 🛠️ Desarrollo

### Variables de Entorno (Never Commit!)

### Autenticación y Autorización```bash

# Usar .env (no versionar)

```phpDB_HOST=localhost

<?phpDB_NAME=control_horario

require_once __DIR__ . '/../includes/init.php';DB_USER=root

DB_PASS=tu_contraseña

// Verificar que esté logueadoBASE_URL=https://tu-dominio.com/

requireLogin();```



// Verificar permisos admin### Headers de Seguridad Incluidos

requireAdmin();- ✅ HTTPS obligatorio en producción

- ✅ Sesiones seguras con SameSite

// Verificar admin o supervisor- ✅ Content Security Policy (CSP)

requireAdminOrSupervisor();- ✅ Protección contra clickjacking (X-Frame-Options)

```- ✅ Hashing seguro de contraseñas



### Acceso a Base de Datos---



```php## 📖 Guía de Uso

// Consulta preparada (segura)

$stmt = $pdo->prepare("SELECT * FROM empleados WHERE id = ?");### Primer Acceso

$stmt->execute([$id]);1. Ir a `https://tu-app.onrender.com/login`

$empleado = $stmt->fetch(PDO::FETCH_ASSOC);2. Usar credenciales proporcionadas

3. **Cambiar contraseña** en "Mi Perfil" → "Cambiar Contraseña"

// Insertar datos

$stmt = $pdo->prepare("INSERT INTO empleados (nombre, email) VALUES (?, ?)");### Registrar Fichajes

$stmt->execute([$nombre, $email]);1. Dashboard → **"Fichajar"**

```2. Seleccionar tipo: **Entrada** o **Salida**

3. Confirmar ubicación (si está habilitada)

### Funciones Útiles4. Hacer clic en **"Confirmar Fichaje"**



```php### Solicitar Permisos

getEmpleado()                          # Usuario actual1. Ir a **"Solicitudes"** → **"Nueva Solicitud"**

obtenerGeoConfigEmpleado($empId)      # Config GPS2. Seleccionar tipo: Vacaciones, Permiso, Baja, etc.

registrarLogin($usuario, $empId, $ok) # Log acceso3. Elegir fechas del período

getNotificaciones($empId)              # Notificaciones4. Agregar motivo/comentarios (opcional)

```5. Enviar para aprobación



### Enrutamiento### Panel Administrativo

1. **Administración** → Seleccionar módulo

URLs amigables mediante `.htaccess`:   - **Empleados:** Crear, editar, eliminar personal

```   - **Solicitudes:** Aprobar/rechazar solicitudes

/dashboard              → public/dashboard.php   - **Seguridad:** Gestionar roles y permisos

/admin/empleados        → public/admin/empleados.php   - **Configuración:** Ajustes del sistema

/fichajar               → public/fichaje/procesar-fichaje.php

/solicitudes            → public/solicitudes.php### Generar Informes

```1. **Informes** → Seleccionar período

2. Elegir empleados (o todos)

---3. **Generar PDF**



## 📊 Estadísticas---



- **Líneas de código PHP:** ~15,000+## 🛠️ Desarrollo

- **Funciones:** 100+

- **Tablas de BD:** 15+### Autenticación y Autorización

- **Endpoints API:** 30+

- **Páginas:** 25+```php

<?php

---require_once __DIR__ . '/../includes/init.php';



## 🐛 Troubleshooting// Verificar que el usuario esté logueado

requireLogin();

### Error 404 en rutas admin

**Solución:** Verificar módulo rewrite en Apache// Verificar permisos administrativos

```bashrequireAdmin();

a2enmod rewrite

systemctl reload apache2// Verificar admin o supervisor

```requireAdminOrSupervisor();

```

### Conexión a BD fallida

**Solución:** Verificar variables de entorno### Acceso a Base de Datos

```bash

echo $DB_HOST```php

echo $DB_USER// Consulta preparada (segura contra SQL injection)

```$stmt = $pdo->prepare("SELECT * FROM empleados WHERE id = ?");

$stmt->execute([$id]);

### Permisos de carpeta uploads$empleado = $stmt->fetch(PDO::FETCH_ASSOC);

**Solución:** Ajustar permisos

```bash// Insertar datos

chmod 755 public/uploads$stmt = $pdo->prepare("INSERT INTO empleados (nombre, email) VALUES (?, ?)");

chmod 755 public/uploads/usuarios$stmt->execute([$nombre, $email]);

chown -R www-data:www-data public/uploads```

```

### Funciones Útiles

### Geolocalización no funciona

**Solución:** Ejecutar script```php

```bash// Obtener usuario actual

php bin/configurar-geolocalizacion.php$emp = getEmpleado();

```

// Obtener configuración de geolocalización

---$geoConfig = obtenerGeoConfigEmpleado($empId);



## 📝 Licencia// Registrar acciones administrativas

registrarLogin($usuario, $empId, true);

Licencia **MIT**. Ver [LICENSE](LICENSE) para detalles.

// Obtener notificaciones

---$notifs = getNotificaciones($empId);

```

## 👨‍💻 Autor

### Enrutamiento

**jshevvik** - [GitHub](https://github.com/jshevvik)

El archivo `.htaccess` proporciona URLs amigables:

Noviembre 2025 | Versión 1.0.0

```

---/dashboard              → public/dashboard.php

/admin/empleados        → public/admin/empleados.php

## 🤝 Contribuciones/fichajar               → public/fichaje/procesar-fichaje.php

/solicitudes            → public/solicitudes.php

Las contribuciones son bienvenidas:```



1. Fork el proyecto---

2. Crea rama: `git checkout -b feature/MiFeature`

3. Commit: `git commit -m 'Add: descripción'`## 📊 Estadísticas del Proyecto

4. Push: `git push origin feature/MiFeature`

5. Abre Pull Request- **Líneas de código PHP:** ~15,000+

- **Funciones implementadas:** 100+

---- **Tablas de BD:** 15+

- **Endpoints API/AJAX:** 30+

## 📞 Soporte- **Páginas y vistas:** 25+

- **Dependencias de Composer:** 2

- 🐛 **Reportar bugs:** [Issues en GitHub](https://github.com/jshevvik/controlhorario_demo/issues)

- 💡 **Sugerencias:** [Discussions](https://github.com/jshevvik/controlhorario_demo/discussions)---



---## 🐛 Troubleshooting



## 🔗 Enlaces Útiles### Error 404 en rutas administrativas

**Solución:** Verificar que `.htaccess` y mod_rewrite estén habilitados

- 🌐 [Demo en vivo](https://controlhorario-demo.onrender.com)```bash

- 📚 [Bootstrap](https://getbootstrap.com)a2enmod rewrite

- 🗺️ [Leaflet.js](https://leafletjs.com)systemctl reload apache2

- 🐬 [MySQL Docs](https://dev.mysql.com/doc/)```

- 🐘 [PHP Manual](https://www.php.net/manual)

- 🐳 [Docker Docs](https://docs.docker.com)### Conexión a BD fallida

- 🚀 [Render Docs](https://render.com/docs)**Solución:** Verificar variables de entorno

```bash

---echo $DB_HOST

echo $DB_USER

**Última actualización:** Noviembre 2025php -S localhost:8000 -t public  # Ver errores

```

### Permisos de carpeta de uploads
**Solución:** Ajustar permisos
```bash
chmod 755 public/uploads
chmod 755 public/uploads/usuarios
chown -R www-data:www-data public/uploads
```

### Geolocalización no funciona
**Solución:** Ejecutar script de configuración
```bash
php bin/configurar-geolocalizacion.php
```

---

## 📝 Licencia

Este proyecto está bajo licencia **MIT**. Ver el archivo [LICENSE](LICENSE) para más detalles.

---

## 👨‍💻 Autor

**jshevvik** - [GitHub Profile](https://github.com/jshevvik)

Proyecto iniciado en **Noviembre 2025**

---

## 🤝 Contribuciones

Las contribuciones son bienvenidas. Por favor:

1. **Fork** el proyecto
2. Crea una rama para tu feature: `git checkout -b feature/MiFeature`
3. Commit tus cambios: `git commit -m 'Add: descripción del cambio'`
4. Push a la rama: `git push origin feature/MiFeature`
5. Abre un **Pull Request**

---

## 📞 Soporte y Contacto

### Reportar Bugs
- Crear un [Issue en GitHub](https://github.com/jshevvik/controlhorario_demo/issues)
- Describir el problema con detalles
- Incluir pasos para reproducir

### Sugerencias de Mejora
- Discusiones en [GitHub Discussions](https://github.com/jshevvik/controlhorario_demo/discussions)
- Proponer nuevas características

---

## 🔗 Enlaces Útiles

| Recurso | Enlace |
|---------|--------|
| 🌐 Demo en vivo | https://controlhorario-demo.onrender.com |
| 📚 Bootstrap | https://getbootstrap.com |
| 🗺️ Leaflet.js | https://leafletjs.com |
| 🐬 MySQL Docs | https://dev.mysql.com/doc/ |
| 🐘 PHP Manual | https://www.php.net/manual |
| 🐳 Docker Docs | https://docs.docker.com |
| 🚀 Render Docs | https://render.com/docs |

---

## ✅ Roadmap Futuro

- [ ] Autenticación con OAuth2/SSO
- [ ] API REST completa
- [ ] Aplicación móvil nativa
- [ ] Integración con calendario externo
- [ ] Exportación a Excel mejorada
- [ ] Sistema de turnos rotativos
- [ ] Análisis predictivo de horas
- [ ] Biometría para fichaje

---

**Última actualización:** Noviembre 2025 | **Versión:** 1.0.0
