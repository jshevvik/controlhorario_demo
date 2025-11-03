# ⏱️ Control de Horario - Sistema de Gestión Laboral# Control de Horario (Demo)



[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](https://opensource.org/licenses/MIT)Aplicación PHP para gestión de control horario. Este repositorio incluye los archivos necesarios para ejecutar en local y desplegar una **demo** en Render.

[![PHP](https://img.shields.io/badge/PHP-8.2+-777BB4?logo=php&logoColor=white)](https://www.php.net/)

[![MySQL](https://img.shields.io/badge/MySQL-8.0+-4479A1?logo=mysql&logoColor=white)](https://www.mysql.com/)## 🧰 Tecnologías

[![Bootstrap](https://img.shields.io/badge/Bootstrap-5.3-7952B3?logo=bootstrap&logoColor=white)](https://getbootstrap.com/)- PHP 8.2 + Apache

- Composer (autoloader y dependencias)

Una aplicación completa de **gestión de horarios laborales** y control de asistencia con características avanzadas como fichaje geolocalizado, solicitudes de permisos, informes, y panel administrativo.- MySQL (externo/gestionado en producción)

- .htaccess para rutas amigables

**Demo en vivo:** [controlhorario-demo.onrender.com](https://controlhorario-demo.onrender.com)- Docker (Render construye la imagen automáticamente)



---## 📦 Estructura

public/ # DocumentRoot

## 📸 Capturas de Pantalla.htaccess

index.php

### Dashboard Principaladmin/

![Dashboard Principal](./docs/screenshots/dashboard.png "Vista principal del dashboard")acciones/

*Panel de bienvenida con resumen de fichajes, solicitudes y accesos rápidos*fichaje/

notificaciones/

### Gestión de Fichajes404.php, login.php, ...

![Fichajes](./docs/screenshots/fichajes.png "Panel de fichajes y control horario")uploads/ # Subidas de usuarios (no se versiona)

*Control de entrada/salida con cronómetro en tiempo real*includes/

vendor/ # Generado por Composer

### Panel Administrativoconfig.example.php

![Administración](./docs/screenshots/administracion.png "Panel de administración")composer.json

*Centro administrativo con acceso a empleados, solicitudes y configuración*Dockerfile



### Solicitudes de Vacaciones

![Solicitudes](./docs/screenshots/solicitudes.png "Gestión de solicitudes de vacaciones y permisos")## 🔐 Seguridad

*Workflow de solicitudes con aprobación multinivel*- No subir `config.php`, contraseñas ni datos reales.

- En Render usar variables de entorno: `BASE_URL`, `DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASS`, `UPLOADS_DIR` (opcional).

---

## 🖥️ Ejecución local

## ✨ Características Principales```bash

composer install

### 👥 Gestión de Empleadoscp config.example.php config.php

- ✅ Crear, editar y eliminar empleados# Ajusta DB_* si hace falta

- ✅ Asignación de roles (Admin, Supervisor, Empleado)php -S localhost:8000 -t public

- ✅ Gestión de permisos granulares# Ir a http://localhost:8000

- ✅ Perfiles personalizables con avatares

### ⏰ Control de Horarios
- ✅ Fichaje de entrada/salida manual
- ✅ Cronómetro integrado en tiempo real
- ✅ Historial completo de fichajes
- ✅ Cálculo automático de horas trabajadas
- ✅ Validación de horarios laborales

### 📍 Geolocalización Avanzada
- ✅ Fichaje con verificación de ubicación GPS
- ✅ Radio de cobertura configurable por empleado
- ✅ Historial detallado de ubicaciones
- ✅ Mapa interactivo con Leaflet.js

### 📋 Solicitudes Administrativas
- ✅ Solicitudes de vacaciones
- ✅ Solicitudes de permisos
- ✅ Solicitudes de bajas médicas
- ✅ Gestión de ausencias
- ✅ Workflow de aprobación con notificaciones
- ✅ Historial completo de solicitudes

### 📊 Informes y Reportes
- ✅ Generación de reportes en PDF
- ✅ Filtrado avanzado por empleado, fecha, tipo
- ✅ Exportación de datos
- ✅ Gráficas y estadísticas
- ✅ Dashboard con resúmenes ejecutivos

### 🔒 Seguridad Robusta
- ✅ Autenticación con contraseñas hasheadas (bcrypt)
- ✅ Sistema granular de roles y permisos
- ✅ Auditoría de acciones administrativas
- ✅ Gestión segura de sesiones
- ✅ Protección CSRF
- ✅ Sanitización de inputs

### 📱 Interfaz Responsive
- ✅ Compatible con dispositivos móviles
- ✅ UI moderna con Bootstrap 5
- ✅ Componentes interactivos
- ✅ Iconos profesionales con Iconify

---

## 🧰 Stack Tecnológico

| Componente | Tecnología |
|-----------|-----------|
| **Backend** | PHP 8.2 + Apache |
| **Base de Datos** | MySQL 8.0+ |
| **Frontend** | HTML5 + Bootstrap 5 + JavaScript Vanilla |
| **Mapas** | Leaflet 1.9.4 |
| **Reportes PDF** | mPDF 8.2 |
| **Servidor** | Docker + Render |
| **Gestor de Dependencias** | Composer |

---

## 📦 Estructura del Proyecto

```
controlhorario_demo/
├── public/                          # DocumentRoot (carpeta visible)
│   ├── index.php                   # Router principal
│   ├── login.php                   # Página de login
│   ├── dashboard.php               # Dashboard principal
│   ├── fichajes.php                # Control de fichajes
│   ├── solicitudes.php             # Gestión de solicitudes
│   ├── informes.php                # Generación de informes
│   ├── geolocalizacion.php         # Configuración GPS
│   ├── administracion.php          # Panel admin
│   ├── admin/                      # Módulo administrativo
│   │   ├── empleados.php           # Gestión de empleados
│   │   ├── configuracion.php       # Configuración del sistema
│   │   ├── seguridad.php           # Gestión de seguridad
│   │   ├── ver-solicitudes.php     # Aprobación de solicitudes
│   │   └── ...
│   ├── acciones/                   # Endpoints AJAX/formularios
│   ├── fichaje/                    # Procesamiento de fichajes
│   ├── notificaciones/             # Sistema de notificaciones
│   ├── assets/                     # CSS, JS, imágenes
│   │   ├── css/                    # Estilos personalizados
│   │   ├── js/                     # Scripts del cliente
│   │   └── img/                    # Imágenes
│   └── uploads/                    # Avatares y documentos
├── includes/                        # Código PHP (fuera de web root)
│   ├── init.php                    # Inicialización de la app
│   └── funciones.php               # Funciones reutilizables
├── bin/                            # Scripts CLI
│   ├── configurar-sistema.php      # Instalación inicial
│   ├── configurar-geolocalizacion.php
│   └── update-holidays.php
├── config.example.php              # Ejemplo de configuración
├── composer.json                   # Dependencias PHP
├── Dockerfile                      # Configuración Docker
└── README.md                       # Este archivo
```

---

## 🚀 Instalación y Uso

### Requisitos Previos
- PHP 8.2 o superior
- MySQL 8.0 o superior
- Composer
- Git

### 1️⃣ Instalación Local

```bash
# Clonar el repositorio
git clone https://github.com/jshevvik/controlhorario_demo.git
cd controlhorario_demo

# Instalar dependencias PHP
composer install

# Copiar archivo de configuración
cp config.example.php config.php

# Editar credenciales de base de datos
nano config.php
# Modificar DB_HOST, DB_NAME, DB_USER, DB_PASS

# Iniciar servidor PHP de desarrollo
php -S localhost:8000 -t public

# Acceder a la aplicación
# Abrir navegador en: http://localhost:8000
```

### 2️⃣ Despliegue en Render (Producción)

#### Paso 1: Conectar GitHub a Render
1. Ir a [render.com](https://render.com) y crear cuenta gratuita
2. Conectar tu cuenta de GitHub
3. Seleccionar el repositorio `controlhorario_demo`

#### Paso 2: Crear Servicio Web
1. Crear nuevo "Web Service"
2. Configurar:
   - **Build Command:** `composer install`
   - **Start Command:** (dejar vacío - Apache maneja todo)
   - **Environment:** Docker

#### Paso 3: Variables de Entorno
Agregar en el panel de Render → Environment:

```env
DB_HOST=tu-mysql-host.render.com
DB_NAME=control_horario
DB_USER=tu_usuario_bd
DB_PASS=tu_contraseña_segura
DB_PORT=3306
BASE_URL=https://tu-app.onrender.com/
UPLOADS_DIR=/var/www/html/public/uploads/usuarios/
```

#### Paso 4: Inicializar Base de Datos
Desde la consola de Render:

```bash
# Crear tabla de configuración
php /var/www/html/bin/configurar-sistema.php

# Configurar geolocalización
php /var/www/html/bin/configurar-geolocalizacion.php
```

---

## 🔐 Configuración de Seguridad

### Protección de Datos
```bash
# NO commitear archivos con datos reales
echo "config.php" >> .gitignore
echo ".env" >> .gitignore
```

### Estructura de Directorios Segura
```
/var/www/html/              # Raíz del proyecto
├── public/                 # ✅ Visible públicamente (DocumentRoot)
└── includes/               # ✅ Protegida, fuera de web root
```

### Variables de Entorno (Never Commit!)
```bash
# Usar .env (no versionar)
DB_HOST=localhost
DB_NAME=control_horario
DB_USER=root
DB_PASS=tu_contraseña
BASE_URL=https://tu-dominio.com/
```

### Headers de Seguridad Incluidos
- ✅ HTTPS obligatorio en producción
- ✅ Sesiones seguras con SameSite
- ✅ Content Security Policy (CSP)
- ✅ Protección contra clickjacking (X-Frame-Options)
- ✅ Hashing seguro de contraseñas

---

## 📖 Guía de Uso

### Primer Acceso
1. Ir a `https://tu-app.onrender.com/login`
2. Usar credenciales proporcionadas
3. **Cambiar contraseña** en "Mi Perfil" → "Cambiar Contraseña"

### Registrar Fichajes
1. Dashboard → **"Fichajar"**
2. Seleccionar tipo: **Entrada** o **Salida**
3. Confirmar ubicación (si está habilitada)
4. Hacer clic en **"Confirmar Fichaje"**

### Solicitar Permisos
1. Ir a **"Solicitudes"** → **"Nueva Solicitud"**
2. Seleccionar tipo: Vacaciones, Permiso, Baja, etc.
3. Elegir fechas del período
4. Agregar motivo/comentarios (opcional)
5. Enviar para aprobación

### Panel Administrativo
1. **Administración** → Seleccionar módulo
   - **Empleados:** Crear, editar, eliminar personal
   - **Solicitudes:** Aprobar/rechazar solicitudes
   - **Seguridad:** Gestionar roles y permisos
   - **Configuración:** Ajustes del sistema

### Generar Informes
1. **Informes** → Seleccionar período
2. Elegir empleados (o todos)
3. **Generar PDF**

---

## 🛠️ Desarrollo

### Autenticación y Autorización

```php
<?php
require_once __DIR__ . '/../includes/init.php';

// Verificar que el usuario esté logueado
requireLogin();

// Verificar permisos administrativos
requireAdmin();

// Verificar admin o supervisor
requireAdminOrSupervisor();
```

### Acceso a Base de Datos

```php
// Consulta preparada (segura contra SQL injection)
$stmt = $pdo->prepare("SELECT * FROM empleados WHERE id = ?");
$stmt->execute([$id]);
$empleado = $stmt->fetch(PDO::FETCH_ASSOC);

// Insertar datos
$stmt = $pdo->prepare("INSERT INTO empleados (nombre, email) VALUES (?, ?)");
$stmt->execute([$nombre, $email]);
```

### Funciones Útiles

```php
// Obtener usuario actual
$emp = getEmpleado();

// Obtener configuración de geolocalización
$geoConfig = obtenerGeoConfigEmpleado($empId);

// Registrar acciones administrativas
registrarLogin($usuario, $empId, true);

// Obtener notificaciones
$notifs = getNotificaciones($empId);
```

### Enrutamiento

El archivo `.htaccess` proporciona URLs amigables:

```
/dashboard              → public/dashboard.php
/admin/empleados        → public/admin/empleados.php
/fichajar               → public/fichaje/procesar-fichaje.php
/solicitudes            → public/solicitudes.php
```

---

## 📊 Estadísticas del Proyecto

- **Líneas de código PHP:** ~15,000+
- **Funciones implementadas:** 100+
- **Tablas de BD:** 15+
- **Endpoints API/AJAX:** 30+
- **Páginas y vistas:** 25+
- **Dependencias de Composer:** 2

---

## 🐛 Troubleshooting

### Error 404 en rutas administrativas
**Solución:** Verificar que `.htaccess` y mod_rewrite estén habilitados
```bash
a2enmod rewrite
systemctl reload apache2
```

### Conexión a BD fallida
**Solución:** Verificar variables de entorno
```bash
echo $DB_HOST
echo $DB_USER
php -S localhost:8000 -t public  # Ver errores
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
