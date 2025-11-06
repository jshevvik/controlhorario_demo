<?php
require_once __DIR__ . '/../includes/init.php';

// Determinar la página solicitada
$page = $_GET['page'] ?? 'dashboard';
// NO usar basename() aquí - queremos preservar las rutas como 'admin/empleados'
// Solo sanitizar para evitar ataques de path traversal
$page = str_replace('..', '', $page);
$page = trim($page, '/');

// Lista blanca de páginas permitidas
$allowed = [
    'dashboard','fichajes','solicitudes','permisos','bajas','horas-extras',
    'ausencias','informes','geolocalizacion','administracion','miperfil',
    'editar-perfil','login','logout',
    // Admin pages
    'admin/crear-empleado',
    'admin/empleados',
    'admin/editar-empleado',
    'admin/ver-empleado',
    'admin/editar-horario',
    'admin/ver-solicitudes',
    'admin/configuracion',
    'admin/seguridad',
    'admin/contenido',
    'admin/borrar-empleado',
    'admin/detalle-fichajes',
    'admin/generar-pdf',
    'admin/gestor-archivos',
    'admin/mantenimiento',
    'admin/notificaciones',
    'admin/ver-notificaciones',
    'admin/editar-permisos',
    'admin/historial-solicitud'

];

// Si la página no está permitida → 404
if (!in_array($page, $allowed)) {
    http_response_code(404);
    $page = '404';
}

// --- Página login SIN layout (sin sidebar/navbar) ---
if ($page === 'login') {
    include __DIR__ . '/login.php';
    exit;
}

// --- Página logout (puede accederse sin estar logeado) ---
if ($page === 'logout') {
    include __DIR__ . '/logout.php';
    exit;
}

// --- PROTECCIÓN: Si no está logeado → redirigir a login ---
if (empty($_SESSION['empleado_id'])) {
    header('Location: ' . $config['ruta_absoluta'] . 'login');
    exit;
}

// Avatar para UI (sidebar/navbar)
$avatar = !empty($emp['avatar']) ? $emp['avatar'] : null;

if ($avatar) {
    $avatarFisica = rtrim($config['UPLOADS_DIR'], '/') . '/' . ltrim($avatar, '/');
    $avatarWeb    = rtrim($config['UPLOADS_URL'], '/') . '/' . ltrim($avatar, '/');
    $avatarExiste = @file_exists($avatarFisica);
} else {
    $avatarExiste = false;
}

$sidebarAvatar = $avatarExiste
    ? $avatarWeb . '?v=' . time()
    : $config['ASSET_URL'] . 'img/avatar-default.jpg';

$fotoPerfil = $avatarExiste
    ? $avatarWeb . '?v=' . time()
    : 'https://ui-avatars.com/api/?name='
        . urlencode($emp['nombre'].' '.$emp['apellidos'])
        . '&background=0D8ABC&color=fff&size=80';

// Archivo físico a incluir dentro del <main>
$archivo = __DIR__ . '/' . $page . '.php';
?>



<!doctype html>
<html lang="es">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Panel – Control de Horas</title>
  <link rel="shortcut icon" type="image/png" href="<?= $config['ASSET_URL'] ?>img/favicon.png" />
  <link href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css" rel="stylesheet"/>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">


  <link href="https://cdn.jsdelivr.net/npm/bootstrap-year-calendar@1.1.1/css/bootstrap-year-calendar.min.css" rel="stylesheet"/>


  <!-- DateRangePicker CSS -->
  <link rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css">

  <!-- Leaflet CSS -->
  <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" 
      integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" 
      crossorigin="">


  <link rel="stylesheet" href="<?= $config['ASSET_URL'] ?>css/styles.min.css" />
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/simplebar@6.2.6/dist/simplebar.min.css">
  <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
  <link rel="stylesheet" href="<?= appendCacheBuster($config['ASSET_URL'] . 'css/request.css') ?>">
  <link rel="stylesheet" href="<?= appendCacheBuster($config['ASSET_URL'] . 'css/styles.css') ?>" />
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css"/>
  
  

</head>
<body >
  <!-- Body Wrapper -->
  <div class="page-wrapper" id="main-wrapper" data-layout="vertical" data-navbarbg="skin6" data-sidebartype="full">

    <!-- Sidebar Start -->
    <aside class="left-sidebar">
      <div>
        <div class="brand-logo d-flex align-items-center justify-content-between mb-0" style="min-height: 56px;">
          <a href="<?= $config['ruta_absoluta'] ?>fichajes" class="text-nowrap logo-img text-center">
            <img src="<?= $config['ASSET_URL'] ?>img/logo.png" alt="Logo" style="max-width:120px; height:auto; display:block; margin:0 auto;" />
          </a>
          <div class="close-btn d-xl-none d-block sidebartoggler cursor-pointer">
            <i class="ti ti-x fs-8"></i>
          </div>
        </div>
        <!-- Sidebar navigation-->
        <nav class="sidebar-nav scroll-sidebar pt-0" data-simplebar="">
          <ul id="sidebarnav">
            <li class="nav-small-cap">
              <iconify-icon icon="solar:menu-dots-linear" class="nav-small-cap-icon fs-4"></iconify-icon>
            </li>

            <!-- Resumen arriba de todo -->
            <li class="sidebar-item<?= $page==='dashboard' ? ' active' : '' ?>">
              <a href="<?= $config['ruta_absoluta'] ?>dashboard" class="sidebar-link sidebar-blue">
                <i class="bi bi-house-door-fill"></i>
                <span class="hide-menu">Resumen</span>
              </a>
            </li>

            <!-- Fichaje -->
            <li class="sidebar-item<?= $page==='fichajes' ? ' active' : '' ?>">
            <a href="<?= $config['ruta_absoluta'] ?>fichajes" class="sidebar-link sidebar-green">
                <i class="bi bi-clock-history"></i>
                <span class="hide-menu">Fichajes</span>
            </a>

            </li>

            <li class="sidebar-item<?= $page==='solicitudes' ? ' active' : '' ?>">
            <a href="<?= $config['ruta_absoluta'] ?>solicitudes" class="sidebar-link sidebar-orange">
                <i class="bi bi-calendar-week"></i>
                <span class="hide-menu">Solicitudes</span>
            </a>
            </li>

            <!--  <li class="sidebar-item<?= $page==='permisos' ? ' active' : '' ?>">
            <a href="<?= $config['ruta_absoluta'] ?>permisos" class="sidebar-link sidebar-violet">
                <i class="bi bi-shield-lock"></i>
                <span class="hide-menu">Permisos</span>
            </a>
            </li>

            <li class="sidebar-item<?= $page==='bajas' ? ' active' : '' ?>">
            <a href="<?= $config['ruta_absoluta'] ?>bajas" class="sidebar-link sidebar-red">
                <i class="bi bi-file-earmark-minus"></i>
                <span class="hide-menu">Bajas</span>
            </a>
            </li>

            <li class="sidebar-item<?= $page==='horas-extras' ? ' active' : '' ?>">
            <a href="<?= $config['ruta_absoluta'] ?>horas-extras" class="sidebar-link sidebar-pink">
                <i class="bi bi-plus-square"></i>
                <span class="hide-menu">Horas Extras</span>
            </a>
            </li>

            <li class="sidebar-item<?= $page==='ausencias' ? ' active' : '' ?>">
            <a href="<?= $config['ruta_absoluta'] ?>ausencias" class="sidebar-link primary-hover-bg"><i class="bi bi-person-dash"></i>
                <span class="hide-menu">Ausencias</span>
            </a>
            </li>-->

            <li class="sidebar-item<?= $page==='informes' ? ' active' : '' ?>">
            <a href="<?= $config['ruta_absoluta'] ?>informes" class="sidebar-link sidebar-violet"><i class="bi bi-bar-chart"></i>
                <span class="hide-menu">Informes</span>
            </a>
            </li>

            <li class="sidebar-item<?= $page==='geolocalizacion' ? ' active' : '' ?>">
            <a href="<?= $config['ruta_absoluta'] ?>geolocalizacion" class="sidebar-link sidebar-pink"><i class="bi bi-geo-alt"></i>
                <span class="hide-menu">Geolocalización</span>
            </a>
            </li>


            <!-- Administración para admins y supervisores -->
            <?php if (isAdminOrSupervisor()): ?>
            <li class="sidebar-item<?= (str_starts_with($page, 'admin/') || $page === 'administracion') ? ' active' : '' ?>">
              <a class="sidebar-link sidebar-red" href="<?= $config['ruta_absoluta'] ?>administracion" aria-expanded="false">
                <i class="bi bi-lock"></i>
                <span class="hide-menu">Administración</span>
              </a>
            </li>
            <?php endif; ?>

          </ul>
        </nav>
    <!-- Perfil abajo, solo baja si hay suficiente contenido -->
    <div class="fixed-profile mx-3 mt-3 mb-3">
      <div class="card bg-primary-subtle mb-0 shadow-none">
      <div class="card-body p-3">
        <div class="d-flex align-items-center justify-content-between gap-3">
        <div class="d-flex align-items-center gap-3">
          <img src="<?= htmlspecialchars($sidebarAvatar) ?>" width="45" height="45" class="img-fluid rounded-circle" alt="avatar-usuario">
          <div>
          <h5 class="mb-1" style="font-size:1rem;"><?= htmlspecialchars($emp['nombre']) ?></h5>
          <p class="mb-0" style="font-size:0.9rem;"><?= htmlspecialchars($emp['rol'] ?? 'Usuario') ?></p>
          </div>
        </div>
        <a href="<?= $config['ruta_absoluta'] ?>logout" class="position-relative" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-title="Cerrar sesión">
          <i class="bi bi-box-arrow-right fs-5 text-danger"></i>
        </a>
        </div>
      </div>
      </div>
    </div>
    </aside>
    <!-- Sidebar End -->

    <!-- Main wrapper -->
    <div class="body-wrapper">
      <div class="body-wrapper-inner">
        <div class="container-fluid">

          <!-- Header Start -->
          <header class="topbar sticky-top bg-white shadow-sm">
            <div class="container-fluid">
              <nav class="navbar navbar-expand-lg navbar-light px-3 d-flex align-items-center justify-content-between">

                <ul class="navbar-nav">
                  <li class="nav-item d-block d-lg-none">
                    <button id="sidebarCollapse" class="nav-link sidebartoggler border-0 bg-transparent d-block d-lg-none" type="button">
                      <i class="bi bi-list fs-4"></i>
                    </button>
                  </li>
                </ul>

            <!-- Espacio flexible -->
            <div class="flex-grow-1"></div>

                <!-- Nav derecha -->
                <ul class="navbar-nav flex-row ms-auto align-items-center justify-content-center gap-1">
                  <!-- Notificaciones -->
                  <li class="nav-item dropdown nav-icon-hover-bg rounded-circle">
                    <a class="nav-link position-relative p-2" href="#" id="notificationsDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                      <i class="bi bi-bell fs-5"></i>
                      <?php if($numNoLeidas > 0): ?>
                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                          <?= $numNoLeidas ?>
                          <span class="visually-hidden">notificaciones no leídas</span>
                        </span>
                      <?php endif; ?>
                    </a>
                    <div class="dropdown-menu dropdown-menu-end dropdown-menu-animate-up py-0" aria-labelledby="notificationsDropdown" style="min-width: 320px;">
                      <div class="d-flex align-items-center justify-content-between p-3 border-bottom">
                        <h6 class="mb-0">Notificaciones</h6>
                      </div>
                      <div class="p-3">
                        <div id="notificaciones-resumen">Cargando...</div>
                      </div>
                      <div class="py-2 px-3 border-top d-flex justify-content-center">
                        <a class="btn waves-effect waves-light btn-primary w-100" href="#" id="ver-todas-notificaciones">
                          Ver todas
                        </a>
                      </div>
                    </div>
                  </li>

                  <!-- Perfil Usuario -->
                    <li class="nav-item dropdown">
                    <a class="nav-link p-0" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                      <div class="d-flex align-items-center gap-2">
                      <div class="user-profile position-relative">
                        <img src="<?= htmlspecialchars($fotoPerfil) ?>" alt="Avatar" class="rounded-circle shadow" style="width: 40px; height: 40px; object-fit: cover;">
                        <span class="position-absolute bottom-0 end-0 translate-middle p-1 bg-success border border-light rounded-circle" style="width:12px;height:12px;"></span>
                      </div>
                      <div class="d-none d-lg-block text-start">
                        <span class="fw-semibold"><?= htmlspecialchars($emp['nombre'].' '.$emp['apellidos']) ?></span>
                      </div>
                      <i class="bi bi-chevron-down d-none d-lg-block"></i>
                      </div>
                    </a>
                    <?php
                    // Obtener la foto de perfil si existe, si no usar por defecto
                    $avatarFisica = $config['UPLOADS_DIR'] . $emp['avatar'];
                    $fotoPerfil = (!empty($emp['avatar']) && file_exists($avatarFisica))
                        ? appendCacheBuster($config['UPLOADS_URL'] . $emp['avatar'])
                        : 'https://ui-avatars.com/api/?name=' . urlencode($emp['nombre'].' '.$emp['apellidos']) . '&background=0D8ABC&color=fff&size=80';
                    ?>

                    <div class="dropdown-menu dropdown-menu-end dropdown-menu-animate-up py-0 shadow" aria-labelledby="userDropdown" style="min-width: 220px;">
                      <div class="d-flex align-items-center p-3 border-bottom bg-light">
                        <img src="<?= htmlspecialchars($fotoPerfil) ?>" alt="Avatar" class="rounded-circle me-3" style="width: 50px; height: 50px; object-fit: cover;">
                        <div>
                          <div class="fw-bold"><?= htmlspecialchars($emp['nombre'].' '.$emp['apellidos']) ?></div>
                          <div class="text-muted small"><?= htmlspecialchars($emp['rol'] ?? 'Usuario') ?></div>
                        </div>
                      </div>
                      <div class="py-2">
                      <a class="dropdown-item d-flex align-items-center py-2" href="<?= $config['ruta_absoluta'] ?>miperfil">
                        <i class="bi bi-person-lines-fill me-3 fs-5 text-primary"></i>
                        <span>Mi perfil</span>
                      </a>
                      <hr class="dropdown-divider my-1">
                      <a class="dropdown-item d-flex align-items-center py-2" href="<?= $config['ruta_absoluta'] ?>logout">
                        <i class="bi bi-box-arrow-right me-3 fs-5 text-danger"></i>
                        <span>Cerrar sesión</span>
                      </a>
                      </div>
                    </div>
                  </li>
                </ul>
              </nav>
            </div>
          </header>
          <!-- Header End -->


          <!-- Main content -->

            <main class="container-fluid py-4">

                <?php 
            
                include __DIR__ . "/$page.php";
                ?>
            </main>

          <!-- Footer -->
          <footer class="footer-modern">
              <div class="footer-content">
                <p class="mb-0">&copy; <?= date('Y') ?> jshevvik</p>
            </div>
        </footer>

        </div>
      </div>
    </div>
  </div>

   <script> const BASE_URL = "<?= $config['ruta_absoluta'] ?>"; </script>
  <!-- Scripts -->
  <!-- jQuery -->
  <script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"></script>

  <!-- Bootstrap JS  -->
  <script src="<?= $config['ASSET_URL'] ?>libs/bootstrap/dist/js/bootstrap.bundle.min.js"></script>

  <!-- App JS base -->
  <script src="<?= $config['ASSET_URL'] ?>js/app.min.js"></script>

  <!-- Otros plugins -->
  <script src="https://cdn.jsdelivr.net/npm/iconify-icon@1.0.8/dist/iconify-icon.min.js"></script>
  <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
  <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>

  <!-- Moment.js -->
  <script src="https://cdn.jsdelivr.net/npm/moment@2.29.4/min/moment.min.js"></script>

  <!-- DateRangePicker -->
  <script src="https://cdn.jsdelivr.net/npm/daterangepicker@3.1/daterangepicker.min.js"></script>

  <!-- FullCalendar -->
  <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/index.global.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/locales-all.global.min.js"></script>


  <!-- Más JS personalizado y componentes de tu app -->
  <script src="<?= $config['ASSET_URL'] ?>js/app.init.js"></script>
  <script src="<?= $config['ASSET_URL'] ?>js/theme.js"></script>
  <script src="<?= $config['ASSET_URL'] ?>js/vendor.min.js"></script>
  <script src="<?= $config['ASSET_URL'] ?>js/feather.min.js"></script>
  <script src="<?= $config['ASSET_URL'] ?>js/highlight.min.js"></script>
  <script src="<?= $config['ASSET_URL'] ?>js/notificaciones.js"></script>


  <script src="<?= $config['ASSET_URL'] ?>js/calendar.js"></script>
  <!-- request.js -->
  <script src="<?= appendCacheBuster($config['ruta_absoluta'] . 'assets/js/request.js') ?>"></script>

  <?php if ($page === 'geolocalizacion'): ?>
    <script src="<?= appendCacheBuster($config['ASSET_URL'] . 'js/geolocalizacion.js') ?>"></script>
  <?php endif; ?>
  <script src="<?= appendCacheBuster($config['ASSET_URL'] . 'js/dashboard.js') ?>"></script>
  <script src="<?= $config['ASSET_URL'] ?>js/sidebarmenu.js"></script>


  <!-- Modal de notificaciones -->
  <div class="modal fade" id="notificacionesModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Mis notificaciones</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
        </div>
        <div class="modal-body" id="notificacionesModalBody">
        </div>
      </div>
    </div>
  </div>

  <!-- Clic en esta capa = cerrar sidebar en móviles -->
  <div class="dark-transparent sidebartoggler d-none"></div>


</body>
</html>
