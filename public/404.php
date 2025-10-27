<?php
require_once __DIR__ . '/../includes/init.php';

http_response_code(404);
?>

<!doctype html>
<html lang="es">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Panel – Control de Horas</title>
  <link rel="shortcut icon" type="image/png" href="<?= $config['ASSET_URL'] ?>img/favicon.png" />
  <link rel="stylesheet" href="<?= $config['ASSET_URL'] ?>css/styles.min.css" />
  <link rel="stylesheet" href="<?= appendCacheBuster($config['ASSET_URL'] . 'css/styles.css') ?>" />
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css"/>
  <link href="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.css" rel="stylesheet">

</head>
<body >
  <!-- Body Wrapper -->
  <div class="page-wrapper" id="main-wrapper" data-layout="vertical" data-navbarbg="skin6" data-sidebartype="full">
 <div class="container py-5 text-center">
        <img src="<?= $config['ASSET_URL'] ?>img/404.jpg" alt="error-img" class="img-fluid mb-4" style="max-width:350px;">
        <h1 class="fw-semibold mb-3">¡Vaya!</h1>
        <h4 class="fw-semibold mb-4">La página que buscas no se pudo encontrar...</h4>
        <a class="btn btn-primary" href="index.php?page=dashboard" role="button">Volver al inicio</a>
    </div>
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

  <!-- Scripts -->
  <script src="<?= $config['ASSET_URL'] ?>libs/jquery/dist/jquery.min.js"></script>
  <script src="<?= $config['ASSET_URL'] ?>libs/bootstrap/dist/js/bootstrap.bundle.min.js"></script>
  <script src="<?= $config['ASSET_URL'] ?>js/sidebarmenu.js"></script>
  <script src="<?= $config['ASSET_URL'] ?>js/app.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/iconify-icon@1.0.8/dist/iconify-icon.min.js"></script>
  
<!-- FullCalendar -->
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/locales/es.min.js"></script>

<script src="<?= $config['ASSET_URL'] ?>js/app.init.js"></script>
<script src="<?= $config['ASSET_URL'] ?>js/theme.js"></script>
<script src="<?= $config['ASSET_URL'] ?>js/vendor.min.js"></script>
<script src="<?= $config['ASSET_URL'] ?>js/feather.min.js"></script>
<script src="<?= $config['ASSET_URL'] ?>js/highlight.min.js"></script>
<script src="<?= $config['ASSET_URL'] ?>js/dashboard.js"></script>
<script src="<?= $config['ASSET_URL'] ?>js/sidebarmenu.js"></script>

</body>
</html>