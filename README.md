# ⏱️ Control de Horario - Sistema de Gestión Laboral# ⏱️ Control de Horario - Sistema de Gestión Laboral# Control de Horario (Demo)



[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](https://opensource.org/licenses/MIT)

[![PHP](https://img.shields.io/badge/PHP-8.2+-777BB4?logo=php&logoColor=white)](https://www.php.net/)

[![MySQL](https://img.shields.io/badge/MySQL-8.0+-4479A1?logo=mysql&logoColor=white)](https://www.mysql.com/)[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](https://opensource.org/licenses/MIT)

[![Bootstrap](https://img.shields.io/badge/Bootstrap-5.3-7952B3?logo=bootstrap&logoColor=white)](https://getbootstrap.com/)

[![PHP](https://img.shields.io/badge/PHP-8.2+-777BB4?logo=php&logoColor=white)](https://www.php.net/)

Aplicación completa de **gestión de horarios laborales** y control de asistencia con características avanzadas como fichaje geolocalizado, solicitudes de permisos, informes, y panel administrativo.

[![MySQL](https://img.shields.io/badge/MySQL-8.0+-4479A1?logo=mysql&logoColor=white)](https://www.mysql.com/)[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](https://opensource.org/licenses/MIT)Aplicación PHP para gestión de control horario. Este repositorio incluye los archivos necesarios para ejecutar en local y desplegar una **demo** en Render.

**Demo en vivo:** [controlhorario-demo.onrender.com](https://controlhorario-demo.onrender.com)

[![Bootstrap](https://img.shields.io/badge/Bootstrap-5.3-7952B3?logo=bootstrap&logoColor=white)](https://getbootstrap.com/)

---

[![PHP](https://img.shields.io/badge/PHP-8.2+-777BB4?logo=php&logoColor=white)](https://www.php.net/)

## 📖 Tabla de Contenidos

Aplicación completa de **gestión de horarios laborales** y control de asistencia con características avanzadas como fichaje geolocalizado, solicitudes de permisos, informes, y panel administrativo.

- [✨ Características](#características-principales)

- [🧰 Stack Tecnológico](#stack-tecnológico)[![MySQL](https://img.shields.io/badge/MySQL-8.0+-4479A1?logo=mysql&logoColor=white)](https://www.mysql.com/)## 🧰 Tecnologías

- [📦 Estructura](#estructura-del-proyecto)

- [🚀 Instalación Local](#instalación-local)**Demo en vivo:** [controlhorario-demo.onrender.com](https://controlhorario-demo.onrender.com)

- [🚀 Despliegue Render](#despliegue-en-render)

- [🔐 Seguridad](#configuración-de-seguridad)[![Bootstrap](https://img.shields.io/badge/Bootstrap-5.3-7952B3?logo=bootstrap&logoColor=white)](https://getbootstrap.com/)- PHP 8.2 + Apache

- [📖 Guía de Uso](#guía-de-uso)

- [🛠️ Desarrollo](#desarrollo)---

- [🐛 Troubleshooting](#troubleshooting)

- [📞 Soporte](#soporte)- Composer (autoloader y dependencias)



---## 📖 Tabla de Contenidos



## ✨ Características PrincipalesUna aplicación completa de **gestión de horarios laborales** y control de asistencia con características avanzadas como fichaje geolocalizado, solicitudes de permisos, informes, y panel administrativo.- MySQL (externo/gestionado en producción)



### 👥 Gestión de Empleados- [✨ Características](#características-principales)

- ✅ Crear, editar y eliminar empleados

- ✅ Asignación de roles (Admin, Supervisor, Empleado)- [🧰 Stack Tecnológico](#stack-tecnológico)- .htaccess para rutas amigables

- ✅ Gestión de permisos granulares

- ✅ Perfiles personalizables con avatares- [📦 Estructura del Proyecto](#estructura-del-proyecto)



### ⏰ Control de Horarios- [🚀 Instalación Local](#instalación-local)**Demo en vivo:** [controlhorario-demo.onrender.com](https://controlhorario-demo.onrender.com)- Docker (Render construye la imagen automáticamente)

- ✅ Fichaje de entrada/salida manual

- ✅ Cronómetro integrado en tiempo real- [🚀 Despliegue en Render](#despliegue-en-render)

- ✅ Historial completo de fichajes

- ✅ Cálculo automático de horas trabajadas- [🔐 Seguridad](#configuración-de-seguridad)

- ✅ Validación de horarios laborales

- [📖 Guía de Uso](#guía-de-uso)

### 📍 Geolocalización Avanzada

- ✅ Fichaje con verificación de ubicación GPS- [🛠️ Desarrollo](#desarrollo)---## 📦 Estructura

- ✅ Radio de cobertura configurable por empleado

- ✅ Historial detallado de ubicaciones- [🐛 Troubleshooting](#troubleshooting)

- ✅ Mapa interactivo con Leaflet.js

public/ # DocumentRoot

### 📋 Solicitudes Administrativas

- ✅ Solicitudes de vacaciones, permisos y bajas---

- ✅ Gestión de ausencias

- ✅ Workflow de aprobación con notificaciones## 📸 Capturas de Pantalla.htaccess

- ✅ Historial completo de solicitudes

## ✨ Características Principales

### 📊 Informes y Reportes

- ✅ Generación de reportes en PDFindex.php

- ✅ Filtrado avanzado por empleado, fecha, tipo

- ✅ Exportación de datos### 👥 Gestión de Empleados

- ✅ Gráficas y estadísticas

- ✅ Crear, editar y eliminar empleados### Dashboard Principaladmin/

### 🔒 Seguridad Robusta

- ✅ Autenticación con contraseñas hasheadas (bcrypt)- ✅ Asignación de roles (Admin, Supervisor, Empleado)

- ✅ Sistema granular de roles y permisos

- ✅ Auditoría de acciones administrativas- ✅ Gestión de permisos granulares![Dashboard Principal](./docs/screenshots/dashboard.png "Vista principal del dashboard")acciones/

- ✅ Gestión segura de sesiones

- ✅ Protección CSRF- ✅ Perfiles personalizables con avatares

- ✅ Sanitización de inputs

*Panel de bienvenida con resumen de fichajes, solicitudes y accesos rápidos*fichaje/

---

### ⏰ Control de Horarios

## 🧰 Stack Tecnológico

- ✅ Fichaje de entrada/salida manualnotificaciones/

| Componente | Tecnología |

|-----------|-----------|- ✅ Cronómetro integrado en tiempo real

| **Backend** | PHP 8.2 + Apache |

| **Base de Datos** | MySQL 8.0+ (Railway) |- ✅ Historial completo de fichajes### Gestión de Fichajes404.php, login.php, ...

| **Frontend** | HTML5 + Bootstrap 5 + JavaScript |

| **Mapas** | Leaflet.js 1.9.4 |- ✅ Cálculo automático de horas trabajadas

| **Reportes PDF** | mPDF 8.2 |

| **Servidor** | Docker + Render |- ✅ Validación de horarios laborales![Fichajes](./docs/screenshots/fichajes.png "Panel de fichajes y control horario")uploads/ # Subidas de usuarios (no se versiona)

| **Dependencias** | Composer |



---

### 📍 Geolocalización Avanzada*Control de entrada/salida con cronómetro en tiempo real*includes/

## 📦 Estructura del Proyecto

- ✅ Fichaje con verificación de ubicación GPS

```

controlhorario_demo/- ✅ Radio de cobertura configurable por empleadovendor/ # Generado por Composer

├── public/                     # DocumentRoot (carpeta visible)

│   ├── index.php              # Router principal- ✅ Historial detallado de ubicaciones

│   ├── login.php              # Página de login

│   ├── dashboard.php          # Dashboard- ✅ Mapa interactivo con Leaflet.js### Panel Administrativoconfig.example.php

│   ├── fichajes.php           # Control de fichajes

│   ├── solicitudes.php        # Gestión de solicitudes

│   ├── informes.php           # Reportes

│   ├── geolocalizacion.php    # Configuración GPS### 📋 Solicitudes Administrativas![Administración](./docs/screenshots/administracion.png "Panel de administración")composer.json

│   ├── admin/                 # Módulo administrativo

│   ├── acciones/              # Endpoints AJAX- ✅ Solicitudes de vacaciones

│   ├── fichaje/               # Procesamiento de fichajes

│   ├── notificaciones/        # Sistema de notificaciones- ✅ Solicitudes de permisos*Centro administrativo con acceso a empleados, solicitudes y configuración*Dockerfile

│   ├── assets/                # CSS, JS, imágenes

│   └── uploads/               # Avatares y documentos- ✅ Solicitudes de bajas médicas

├── includes/

│   ├── init.php               # Inicialización- ✅ Gestión de ausencias

│   └── funciones.php          # Funciones reutilizables

├── bin/                       # Scripts CLI- ✅ Workflow de aprobación con notificaciones

├── config.example.php         # Configuración ejemplo

├── composer.json              # Dependencias- ✅ Historial completo de solicitudes### Solicitudes de Vacaciones

├── Dockerfile                 # Docker

└── README.md                  # Este archivo

```

### 📊 Informes y Reportes![Solicitudes](./docs/screenshots/solicitudes.png "Gestión de solicitudes de vacaciones y permisos")## 🔐 Seguridad

---

- ✅ Generación de reportes en PDF

## 🚀 Instalación Local

- ✅ Filtrado avanzado por empleado, fecha, tipo*Workflow de solicitudes con aprobación multinivel*- No subir `config.php`, contraseñas ni datos reales.

### Requisitos

- PHP 8.2+- ✅ Exportación de datos

- MySQL 8.0+

- Composer- ✅ Gráficas y estadísticas- En Render usar variables de entorno: `BASE_URL`, `DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASS`, `UPLOADS_DIR` (opcional).

- Git

- ✅ Dashboard con resúmenes ejecutivos

### Pasos

---

```bash

# 1. Clonar### 🔒 Seguridad Robusta

git clone https://github.com/jshevvik/controlhorario_demo.git

cd controlhorario_demo- ✅ Autenticación con contraseñas hasheadas (bcrypt)## 🖥️ Ejecución local



# 2. Instalar dependencias- ✅ Sistema granular de roles y permisos

composer install

- ✅ Auditoría de acciones administrativas## ✨ Características Principales```bash

# 3. Configurar

cp config.example.php config.php- ✅ Gestión segura de sesiones

nano config.php  # Editar credenciales BD

- ✅ Protección CSRFcomposer install

# 4. Crear BD (MySQL)

# mysql> CREATE DATABASE control_horario;- ✅ Sanitización de inputs



# 5. Iniciar servidor### 👥 Gestión de Empleadoscp config.example.php config.php

php -S localhost:8000 -t public

### 📱 Interfaz Responsive

# 6. Abrir navegador

# http://localhost:8000- ✅ Compatible con dispositivos móviles- ✅ Crear, editar y eliminar empleados# Ajusta DB_* si hace falta

```

- ✅ UI moderna con Bootstrap 5

---

- ✅ Componentes interactivos- ✅ Asignación de roles (Admin, Supervisor, Empleado)php -S localhost:8000 -t public

## 🚀 Despliegue en Render

- ✅ Iconos profesionales con Iconify

### Paso 1: Base de Datos en Railway

- ✅ Gestión de permisos granulares# Ir a http://localhost:8000

1. Ir a [railway.com](https://railway.com)

2. Crear base de datos MySQL---

3. Copiar credenciales de conexión

- ✅ Perfiles personalizables con avatares

### Paso 2: Conectar GitHub a Render

## 🧰 Stack Tecnológico

1. Ir a [render.com](https://render.com)

2. Crear cuenta y conectar GitHub### ⏰ Control de Horarios

3. Seleccionar repositorio

| Componente | Tecnología |- ✅ Fichaje de entrada/salida manual

### Paso 3: Crear Servicio Web

|-----------|-----------|- ✅ Cronómetro integrado en tiempo real

1. Click "New" → "Web Service"

2. Seleccionar `controlhorario_demo`| **Backend** | PHP 8.2 + Apache |- ✅ Historial completo de fichajes

3. Configurar:

   - **Build:** `composer install`| **Base de Datos** | MySQL 8.0+ |- ✅ Cálculo automático de horas trabajadas

   - **Start:** (vacío)

   - **Environment:** Docker| **Frontend** | HTML5 + Bootstrap 5 + JavaScript |- ✅ Validación de horarios laborales



### Paso 4: Variables de Entorno| **Mapas** | Leaflet 1.9.4 |



Agregar en Render:| **Reportes PDF** | mPDF 8.2 |### 📍 Geolocalización Avanzada



```env| **Servidor** | Docker + Render |- ✅ Fichaje con verificación de ubicación GPS

DB_HOST=tu-railway-host

DB_NAME=control_horario| **Gestor de Dependencias** | Composer |- ✅ Radio de cobertura configurable por empleado

DB_USER=tu_usuario

DB_PASS=tu_contraseña- ✅ Historial detallado de ubicaciones

DB_PORT=3306

BASE_URL=https://tu-app.onrender.com/---- ✅ Mapa interactivo con Leaflet.js

UPLOADS_DIR=/var/www/html/public/uploads/usuarios/

```



### Paso 5: Inicializar BD## 📦 Estructura del Proyecto### 📋 Solicitudes Administrativas



```bash- ✅ Solicitudes de vacaciones

php /var/www/html/bin/configurar-sistema.php

php /var/www/html/bin/configurar-geolocalizacion.php```- ✅ Solicitudes de permisos

```

controlhorario_demo/- ✅ Solicitudes de bajas médicas

---

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
