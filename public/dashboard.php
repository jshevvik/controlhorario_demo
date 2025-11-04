<?php
$avatar = !empty($emp['avatar']) ? $emp['avatar'] : null;

if ($avatar) {
    $avatarFisica = rtrim($config['UPLOADS_DIR'], '/') . '/' . ltrim($avatar, '/');
    $avatarWeb    = rtrim($config['UPLOADS_URL'], '/') . '/' . ltrim($avatar, '/');
    $avatarExiste = @file_exists($avatarFisica);
    $avatarPath   = $avatarExiste ? ($avatarWeb . '?v=' . time()) : $config['ASSET_URL'] . "img/avatar-default.jpg";
} else {
    $avatarPath = $config['ASSET_URL'] . "img/avatar-default.jpg";
}
?>

<div class="text-center mb-5">
  <a href="index.php?page=miperfil" style="display:inline-block;">
   <img src="<?= htmlspecialchars($avatarPath) ?>"
      class="rounded-circle mb-3" alt="Avatar" style="width:180px;height:180px;object-fit:cover;">
  </a>
  <h2>Hola, <?= htmlspecialchars($emp['nombre']) ?></h2>
  <p class="text-muted">Hoy es <?= getFechaActual() ?></p>
  
  <!-- Cronómetro / Fichaje -->
  <div class="fichaje-pill d-inline-flex align-items-center shadow-sm bg-white mb-4">
    <i class="bi bi-clock-history fs-4 text-secondary me-3"></i>
    <i class="bi bi-geo-alt fs-4 text-secondary me-3"></i>
    <span id="fichajeTimer" class="me-4" style="font-family:monospace;font-size:1.1rem">
      00:00:00
    </span>
    <div class="dropdown">
      <button
        id="ficharBtn"
        class="btn btn-primary dropdown-toggle"
        type="button"
        data-bs-toggle="dropdown"
        aria-expanded="false"
      >
        Entrar
      </button>
      <ul class="dropdown-menu">
        <li id="liEnter" ><a class="dropdown-item" href="#" data-action="enter" >Entrar  </a></li>
        <li id="liPause" ><a class="dropdown-item" href="#" data-action="pause" >Pausar  </a></li>
        <li id="liResume"><a class="dropdown-item" href="#" data-action="resume">Reanudar</a></li>
        <li id="liExit"  ><a class="dropdown-item" href="#" data-action="exit"  >Salir   </a></li>
      </ul>     
    </div>
  </div>
  <!-- FIN Cronómetro -->
  <!-- GRID DE TARJETAS AJUSTADO -->
  <div class="container-fluid">
    <div class="row mt-4 g-4">
      <div class="col-12 col-md-4 d-flex align-items-stretch">
        <div class="card w-100 border-primary h-100 d-flex flex-column">
          <div class="card-body d-flex flex-column">
            <div class="form-group text-center flex-grow-1 d-flex flex-column">
              <i class="bi bi-clock-history text-primary fs-5 mb-3"></i>
              <h4 class="card-title">Fichajes</h4>
              <p class="card-subtitle mb-3 text-muted">Controla tus fichajes</p>
              <div class="flex-grow-1"></div>
              <a href="<?= $config['ruta_absoluta'] ?>fichajes"
                class="btn d-block w-100 fw-medium bg-primary-subtle text-primary block-card mt-auto">
                Ver detalles
              </a>
            </div>
          </div>
        </div>
      </div>
      <div class="col-12 col-md-4 d-flex align-items-stretch">
        <div class="card w-100 border-success h-100 d-flex flex-column">
          <div class="card-body d-flex flex-column">
            <div class="form-group text-center flex-grow-1 d-flex flex-column">
              <i class="bi bi-calendar-check text-success fs-5 mb-3"></i>
              <h4 class="card-title">Solicitud de Vacaciones</h4>
              <p class="card-subtitle mb-3 text-muted">Gestiona tus días de descanso</p>
              <div class="flex-grow-1"></div>
              <a href="<?= $config['ruta_absoluta'] ?>solicitudes"
                class="btn d-block w-100 fw-medium bg-success-subtle text-success block-card mt-auto">
                Solicitar
              </a>
            </div>
          </div>
        </div>
      </div>
      <div class="col-12 col-md-4 d-flex align-items-stretch">
        <div class="card w-100 border-warning h-100 d-flex flex-column">
          <div class="card-body d-flex flex-column">
            <div class="form-group text-center flex-grow-1 d-flex flex-column">
              <i class="bi bi-file-earmark-text text-warning fs-5 mb-3"></i>
              <h4 class="card-title">Informes</h4>
              <p class="card-subtitle mb-3 text-muted">Consulta tus registros laborales</p>
              <div class="flex-grow-1"></div>
              <a href="<?= $config['ruta_absoluta'] ?>informes"
                class="btn d-block w-100 fw-medium bg-warning-subtle text-warning block-card mt-auto">
                Informes
              </a>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>



        <div id="calendar" style="display:none"></div>
      </div>
  </div>
 </div>