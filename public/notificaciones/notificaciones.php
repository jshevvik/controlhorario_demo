<?php
require_once __DIR__ . '/../../includes/init.php';
$empId = $_SESSION['empleado_id'] ?? null;

if (!$empId) { 
    http_response_code(403); 
    exit('Acceso no autorizado'); 
}

if (isset($_GET['resumen'])) {
    $sql = "SELECT * FROM notificaciones WHERE (empleado_id IS NULL OR empleado_id = ?) ORDER BY fecha DESC LIMIT 3";
} elseif (isset($_GET['all'])) {
    $sql = "SELECT * FROM notificaciones WHERE (empleado_id IS NULL OR empleado_id = ?) ORDER BY fecha DESC LIMIT 50";
} else {
    $sql = "SELECT * FROM notificaciones WHERE (empleado_id IS NULL OR empleado_id = ?) AND leido = 0 ORDER BY fecha DESC LIMIT 10";
}

$st = $pdo->prepare($sql);
$st->execute([$empId]);
$notificaciones = $st->fetchAll(PDO::FETCH_ASSOC);

if (isset($_GET['ajax'])):
?>
<?php foreach ($notificaciones as $n): ?>
    <?php
      $color = getColorNotificacion($n['tipo']);
      $icono = getIconoNotificacion($n['tipo']);
    ?>
    <div class="alert customize-alert alert-light-<?= $color ?> bg-<?= $color ?>-subtle text-<?= $color ?> fade show remove-close-icon mb-3 position-relative" role="alert" data-notification-id="<?= $n['id'] ?>">
    <?php if (isset($_GET['all'])): ?>
      <button type="button" class="btn btn-sm btn-outline-<?= $color ?> btn-eliminar-notif position-absolute end-0 top-0 m-2" title="Eliminar notificación" aria-label="Eliminar">
        <i class="bi bi-trash"></i>
      </button>
    <?php else: ?>
      <button type="button" class="btn-close btn-eliminar-notif" title="Eliminar notificación" aria-label="Eliminar"></button>
    <?php endif; ?>
    <div class="d-flex align-items-center">
      <i class="bi <?= $icono ?> fs-5 me-2 text-<?= $color ?>"></i>
      <div>
        <?php if ($n['url']): ?>
          <?php 
          $url = $n['url'];
          if (!preg_match('/^(https?:\/\/|\/)/i', $url)) {
            $url = $config['ruta_absoluta'] . $url;
          }
          ?>
          <a href="<?= htmlspecialchars($url) ?>" class="text-<?= $color ?> fw-bold"><?= htmlspecialchars($n['mensaje']) ?></a>
        <?php else: ?>
          <span class="text-<?= $color ?> fw-bold"><?= htmlspecialchars($n['mensaje']) ?></span>
        <?php endif; ?>
        
        <small class="text-muted ms-2"><?= date('d/m/Y H:i', strtotime($n['fecha'])) ?></small>
        
        <?php if (!$n['leido']): ?>
          <span class="badge bg-warning text-dark ms-2 badge-nuevo" style="font-size:0.85em;vertical-align:middle;">Nuevo</span>
        <?php endif; ?>
      </div>
    </div>
  </div>
  <?php endforeach; ?>
  
  <!-- Estado vacío: mensaje informativo cuando no hay notificaciones -->
  <?php if (!$notificaciones): ?>
  <div class="alert alert-info text-center">
    No tienes notificaciones no leídas
  </div>
<?php endif; ?>
</ul>
<?php exit; endif; ?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Mis notificaciones - Sistema Control Horario</title>
  <link rel="stylesheet" href="../../assets/css/styles.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
</head>
<body>
<div class="container py-4">
  <h1 class="mb-4">Mis notificaciones</h1>
  <?php
  $_GET['all'] = 1;
  $_GET['ajax'] = 1;
  include __FILE__;
  ?>
  <a href="../index.php" class="btn btn-primary mt-3">
    <i class="bi bi-arrow-left me-2"></i>Volver al panel
  </a>
</div>
</body>
</html>
