<?php
session_start();
require_once __DIR__ . '/../includes/init.php';

if (!empty($_SESSION['empleado_id'])) {
    header('Location: ' . $config['ruta_absoluta'] . 'dashboard');
    exit;
}

$error = $_SESSION['error'] ?? '';
unset($_SESSION['error']);
?>
<!doctype html>
<html lang="es">


<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Control de Horas – jshevvik</title>
  <link rel="shortcut icon" type="image/png" href="<?= $config['ASSET_URL'] ?>img/favicon.png" />
  <link rel="stylesheet" href="<?= $config['ASSET_URL'] ?>css/styles.min.css" />
  <link rel="stylesheet" href="<?= $config['ASSET_URL'] ?>css/styles.css" />
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css"
/>
</head>

<body>
  <div class="page-wrapper" id="main-wrapper"
       data-layout="vertical"
       data-navbarbg="skin6"
       data-sidebartype="full"
       data-sidebar-position="fixed"
       data-header-position="fixed">

    <div class="position-relative overflow-hidden text-bg-light d-flex align-items-center justify-content-center">
      <div class="d-flex align-items-center justify-content-center w-100">
        <div class="row justify-content-center w-100">
          <div class="col-md-8 col-lg-6 col-xxl-3">
            <div class="card mb-0">
              <div class="card-body">

                <!-- Logo -->
                <a href="<?= $config['ruta_absoluta'] ?>login" class="text-nowrap logo-img text-center d-block py-3 w-100">
                  <img src="<?= $config['ASSET_URL'] ?>img/logo.png" alt="jshevvik">
                </a>

                <p class="text-center">Iniciar sesión</p>

                <!-- Error -->
                <?php if($error): ?>
                  <div class="alert alert-danger" role="alert">
                    <?= htmlspecialchars($error) ?>
                  </div>
                <?php endif; ?>

                <!-- Formulario -->
                <form action="<?= $config['ruta_absoluta'] ?>acciones/procesar-login.php" method="post">
                  <div class="mb-3">
                    <label for="usuario" class="form-label">Nombre de usuario</label>
                    <input
                      type="text"
                      class="form-control"
                      id="usuario"
                      name="usuario"
                      placeholder="Tu usuario"
                      required
                    >
                  </div>
                  <div class="mb-4 position-relative">
                    <label for="password" class="form-label">Contraseña</label>
                    <input
                      type="password"
                      class="form-control"
                      id="password"
                      name="password"
                      placeholder="••••••••"
                      required
                    >
                    <!-- Icono para mostrar/ocultar -->
                    <span
                      id="togglePassword"
                      style="position:absolute; top:38px; right:12px; cursor:pointer;"
                      title="Mostrar / Ocultar contraseña"
                    >
                      <i class="bi bi-eye"></i>
                    </span>
                  </div>

                  <!--<div class="d-flex align-items-center justify-content-center mb-4">
                    
                    <a class="text-primary fw-bold" href="#">Olvidé mi contraseña</a>
                  </div>-->

                  <button type="submit" class="btn btn-primary w-100 py-2 fs-5 mb-2">
                    Iniciar sesión
                  </button>
                </form>

              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <footer class="app-footer text-center lighten-4">
    &copy; <?= date('Y') ?> ·  jshevvik
  </footer>

  <!-- Scripts -->
  <script src="<?= $config['ASSET_URL'] ?>libs/jquery/dist/jquery.min.js"></script>
  <script src="<?= $config['ASSET_URL'] ?>libs/bootstrap/dist/js/bootstrap.bundle.min.js"></script>
  <script src="<?= $config['ASSET_URL'] ?>js/main.js"></script>
</body>
</html>
