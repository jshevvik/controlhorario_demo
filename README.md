# ⏱️ Control de Horario - Sistema de Gestión Laboral# ⏱️ Control de Horario - Sistema de Gestión Laboral# Control de Horario (Demo)



[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](https://opensource.org/licenses/MIT)

[![PHP](https://img.shields.io/badge/PHP-8.2+-777BB4?logo=php&logoColor=white)](https://www.php.net/)

[![MySQL](https://img.shields.io/badge/MySQL-8.0+-4479A1?logo=mysql&logoColor=white)](https://www.mysql.com/)[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](https://opensource.org/licenses/MIT)Aplicación PHP para gestión de control horario. Este repositorio incluye los archivos necesarios para ejecutar en local y desplegar una **demo** en Render.

[![Bootstrap](https://img.shields.io/badge/Bootstrap-5.3-7952B3?logo=bootstrap&logoColor=white)](https://getbootstrap.com/)

[![PHP](https://img.shields.io/badge/PHP-8.2+-777BB4?logo=php&logoColor=white)](https://www.php.net/)

Aplicación completa de **gestión de horarios laborales** y control de asistencia con características avanzadas como fichaje geolocalizado, solicitudes de permisos, informes, y panel administrativo.

[![MySQL](https://img.shields.io/badge/MySQL-8.0+-4479A1?logo=mysql&logoColor=white)](https://www.mysql.com/)## 🧰 Tecnologías

**Demo en vivo:** [controlhorario-demo.onrender.com](https://controlhorario-demo.onrender.com)

[![Bootstrap](https://img.shields.io/badge/Bootstrap-5.3-7952B3?logo=bootstrap&logoColor=white)](https://getbootstrap.com/)- PHP 8.2 + Apache

---

- Composer (autoloader y dependencias)

## 📖 Tabla de Contenidos

Una aplicación completa de **gestión de horarios laborales** y control de asistencia con características avanzadas como fichaje geolocalizado, solicitudes de permisos, informes, y panel administrativo.- MySQL (externo/gestionado en producción)

- [✨ Características](#características-principales)

- [🧰 Stack Tecnológico](#stack-tecnológico)- .htaccess para rutas amigables

- [📦 Estructura del Proyecto](#estructura-del-proyecto)

- [🚀 Instalación Local](#instalación-local)**Demo en vivo:** [controlhorario-demo.onrender.com](https://controlhorario-demo.onrender.com)- Docker (Render construye la imagen automáticamente)

- [🚀 Despliegue en Render](#despliegue-en-render)

- [🔐 Seguridad](#configuración-de-seguridad)

- [📖 Guía de Uso](#guía-de-uso)

- [🛠️ Desarrollo](#desarrollo)---## 📦 Estructura

- [🐛 Troubleshooting](#troubleshooting)

public/ # DocumentRoot

---

## 📸 Capturas de Pantalla.htaccess

## ✨ Características Principales

index.php

### 👥 Gestión de Empleados

- ✅ Crear, editar y eliminar empleados### Dashboard Principaladmin/

- ✅ Asignación de roles (Admin, Supervisor, Empleado)

- ✅ Gestión de permisos granulares![Dashboard Principal](./docs/screenshots/dashboard.png "Vista principal del dashboard")acciones/

- ✅ Perfiles personalizables con avatares

*Panel de bienvenida con resumen de fichajes, solicitudes y accesos rápidos*fichaje/

### ⏰ Control de Horarios

- ✅ Fichaje de entrada/salida manualnotificaciones/

- ✅ Cronómetro integrado en tiempo real

- ✅ Historial completo de fichajes### Gestión de Fichajes404.php, login.php, ...

- ✅ Cálculo automático de horas trabajadas

- ✅ Validación de horarios laborales![Fichajes](./docs/screenshots/fichajes.png "Panel de fichajes y control horario")uploads/ # Subidas de usuarios (no se versiona)



### 📍 Geolocalización Avanzada*Control de entrada/salida con cronómetro en tiempo real*includes/

- ✅ Fichaje con verificación de ubicación GPS

- ✅ Radio de cobertura configurable por empleadovendor/ # Generado por Composer

- ✅ Historial detallado de ubicaciones

- ✅ Mapa interactivo con Leaflet.js### Panel Administrativoconfig.example.php



### 📋 Solicitudes Administrativas![Administración](./docs/screenshots/administracion.png "Panel de administración")composer.json

- ✅ Solicitudes de vacaciones

- ✅ Solicitudes de permisos*Centro administrativo con acceso a empleados, solicitudes y configuración*Dockerfile

- ✅ Solicitudes de bajas médicas

- ✅ Gestión de ausencias

- ✅ Workflow de aprobación con notificaciones

- ✅ Historial completo de solicitudes### Solicitudes de Vacaciones



### 📊 Informes y Reportes![Solicitudes](./docs/screenshots/solicitudes.png "Gestión de solicitudes de vacaciones y permisos")## 🔐 Seguridad

- ✅ Generación de reportes en PDF

- ✅ Filtrado avanzado por empleado, fecha, tipo*Workflow de solicitudes con aprobación multinivel*- No subir `config.php`, contraseñas ni datos reales.

- ✅ Exportación de datos

- ✅ Gráficas y estadísticas- En Render usar variables de entorno: `BASE_URL`, `DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASS`, `UPLOADS_DIR` (opcional).

- ✅ Dashboard con resúmenes ejecutivos

---

### 🔒 Seguridad Robusta

- ✅ Autenticación con contraseñas hasheadas (bcrypt)## 🖥️ Ejecución local

- ✅ Sistema granular de roles y permisos

- ✅ Auditoría de acciones administrativas## ✨ Características Principales```bash

- ✅ Gestión segura de sesiones

- ✅ Protección CSRFcomposer install

- ✅ Sanitización de inputs

### 👥 Gestión de Empleadoscp config.example.php config.php

### 📱 Interfaz Responsive

- ✅ Compatible con dispositivos móviles- ✅ Crear, editar y eliminar empleados# Ajusta DB_* si hace falta

- ✅ UI moderna con Bootstrap 5

- ✅ Componentes interactivos- ✅ Asignación de roles (Admin, Supervisor, Empleado)php -S localhost:8000 -t public

- ✅ Iconos profesionales con Iconify

- ✅ Gestión de permisos granulares# Ir a http://localhost:8000

---

- ✅ Perfiles personalizables con avatares

## 🧰 Stack Tecnológico

### ⏰ Control de Horarios

| Componente | Tecnología |- ✅ Fichaje de entrada/salida manual

|-----------|-----------|- ✅ Cronómetro integrado en tiempo real

| **Backend** | PHP 8.2 + Apache |- ✅ Historial completo de fichajes

| **Base de Datos** | MySQL 8.0+ |- ✅ Cálculo automático de horas trabajadas

| **Frontend** | HTML5 + Bootstrap 5 + JavaScript |- ✅ Validación de horarios laborales

| **Mapas** | Leaflet 1.9.4 |

| **Reportes PDF** | mPDF 8.2 |### 📍 Geolocalización Avanzada

| **Servidor** | Docker + Render |- ✅ Fichaje con verificación de ubicación GPS

| **Gestor de Dependencias** | Composer |- ✅ Radio de cobertura configurable por empleado

- ✅ Historial detallado de ubicaciones

---- ✅ Mapa interactivo con Leaflet.js



## 📦 Estructura del Proyecto### 📋 Solicitudes Administrativas

- ✅ Solicitudes de vacaciones

```- ✅ Solicitudes de permisos

controlhorario_demo/- ✅ Solicitudes de bajas médicas

├── public/                          # DocumentRoot (carpeta visible)- ✅ Gestión de ausencias

│   ├── index.php                   # Router principal- ✅ Workflow de aprobación con notificaciones

│   ├── login.php                   # Página de login- ✅ Historial completo de solicitudes

│   ├── dashboard.php               # Dashboard principal

│   ├── fichajes.php                # Control de fichajes### 📊 Informes y Reportes

│   ├── solicitudes.php             # Gestión de solicitudes- ✅ Generación de reportes en PDF

│   ├── informes.php                # Generación de informes- ✅ Filtrado avanzado por empleado, fecha, tipo

│   ├── geolocalizacion.php         # Configuración GPS- ✅ Exportación de datos

│   ├── administracion.php          # Panel admin- ✅ Gráficas y estadísticas

│   ├── admin/                      # Módulo administrativo- ✅ Dashboard con resúmenes ejecutivos

│   │   ├── empleados.php

│   │   ├── configuracion.php### 🔒 Seguridad Robusta

│   │   ├── seguridad.php- ✅ Autenticación con contraseñas hasheadas (bcrypt)

│   │   └── ...- ✅ Sistema granular de roles y permisos

│   ├── acciones/                   # Endpoints AJAX/formularios- ✅ Auditoría de acciones administrativas

│   ├── fichaje/                    # Procesamiento de fichajes- ✅ Gestión segura de sesiones

│   ├── notificaciones/             # Sistema de notificaciones- ✅ Protección CSRF

│   ├── assets/                     # CSS, JS, imágenes- ✅ Sanitización de inputs

│   └── uploads/                    # Avatares y documentos

├── includes/### 📱 Interfaz Responsive

│   ├── init.php                    # Inicialización- ✅ Compatible con dispositivos móviles

│   └── funciones.php               # Funciones reutilizables- ✅ UI moderna con Bootstrap 5

├── bin/                            # Scripts CLI- ✅ Componentes interactivos

│   ├── configurar-sistema.php- ✅ Iconos profesionales con Iconify

│   ├── configurar-geolocalizacion.php

│   └── update-holidays.php---

├── config.example.php              # Ejemplo de configuración

├── composer.json                   # Dependencias## 🧰 Stack Tecnológico

├── Dockerfile                      # Configuración Docker

└── README.md                       # Este archivo| Componente | Tecnología |

```|-----------|-----------|

| **Backend** | PHP 8.2 + Apache |

---| **Base de Datos** | MySQL 8.0+ |

| **Frontend** | HTML5 + Bootstrap 5 + JavaScript Vanilla |

## 🚀 Instalación Local| **Mapas** | Leaflet 1.9.4 |

| **Reportes PDF** | mPDF 8.2 |

### Requisitos Previos| **Servidor** | Docker + Render |

- PHP 8.2 o superior| **Gestor de Dependencias** | Composer |

- MySQL 8.0 o superior

- Composer---

- Git

## 📦 Estructura del Proyecto

### Pasos

```

```bashcontrolhorario_demo/

# 1. Clonar el repositorio├── public/                          # DocumentRoot (carpeta visible)

git clone https://github.com/jshevvik/controlhorario_demo.git│   ├── index.php                   # Router principal

cd controlhorario_demo│   ├── login.php                   # Página de login

│   ├── dashboard.php               # Dashboard principal

# 2. Instalar dependencias PHP│   ├── fichajes.php                # Control de fichajes

composer install│   ├── solicitudes.php             # Gestión de solicitudes

│   ├── informes.php                # Generación de informes

# 3. Copiar archivo de configuración│   ├── geolocalizacion.php         # Configuración GPS

cp config.example.php config.php│   ├── administracion.php          # Panel admin

│   ├── admin/                      # Módulo administrativo

# 4. Editar credenciales de base de datos│   │   ├── empleados.php           # Gestión de empleados

nano config.php│   │   ├── configuracion.php       # Configuración del sistema

# Modificar: DB_HOST, DB_NAME, DB_USER, DB_PASS│   │   ├── seguridad.php           # Gestión de seguridad

│   │   ├── ver-solicitudes.php     # Aprobación de solicitudes

# 5. Crear base de datos en MySQL│   │   └── ...

# mysql> CREATE DATABASE control_horario;│   ├── acciones/                   # Endpoints AJAX/formularios

│   ├── fichaje/                    # Procesamiento de fichajes

# 6. Iniciar servidor PHP│   ├── notificaciones/             # Sistema de notificaciones

php -S localhost:8000 -t public│   ├── assets/                     # CSS, JS, imágenes

│   │   ├── css/                    # Estilos personalizados

# 7. Abrir navegador│   │   ├── js/                     # Scripts del cliente

# http://localhost:8000│   │   └── img/                    # Imágenes

```│   └── uploads/                    # Avatares y documentos

├── includes/                        # Código PHP (fuera de web root)

---│   ├── init.php                    # Inicialización de la app

│   └── funciones.php               # Funciones reutilizables

## 🚀 Despliegue en Render├── bin/                            # Scripts CLI

│   ├── configurar-sistema.php      # Instalación inicial

### Paso 1: Conectar GitHub a Render│   ├── configurar-geolocalizacion.php

1. Ir a [render.com](https://render.com)│   └── update-holidays.php

2. Crear cuenta gratuita├── config.example.php              # Ejemplo de configuración

3. Conectar repositorio de GitHub├── composer.json                   # Dependencias PHP

├── Dockerfile                      # Configuración Docker

### Paso 2: Crear Servicio Web└── README.md                       # Este archivo

1. Click en "New" → "Web Service"```

2. Seleccionar repositorio `controlhorario_demo`

3. Configurar:---

   - **Build Command:** `composer install`

   - **Start Command:** (dejar vacío)## 🚀 Instalación y Uso

   - **Environment:** Docker

### Requisitos Previos

### Paso 3: Variables de Entorno- PHP 8.2 o superior

Agregar en el panel de Render:- MySQL 8.0 o superior

- Composer

```env- Git

DB_HOST=tu-mysql-host.render.com

DB_NAME=control_horario### 1️⃣ Instalación Local

DB_USER=tu_usuario

DB_PASS=tu_contraseña_segura```bash

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
