<?php
// Verificar que el usuario tenga permisos de administración
requireAdminOrSupervisor();

// Obtener el rol del usuario para mostrar diferentes opciones
$esAdmin = isAdmin();
$esSupervisor = isSupervisor();

// Obtener ruta del avatar personalizado o el predeterminado
if (!empty($emp['avatar'])) {
    $avatarPath = $config['UPLOADS_URL'] . $emp['avatar'];
} else {
    $avatarPath = $config['ASSET_URL'] . "img/avatar-default.jpg";
}
?>


<div class="text-center mb-5">
   <img src="<?= htmlspecialchars(appendCacheBuster($avatarPath)) ?>"
      class="rounded-circle mb-3" alt="Avatar" style="width:180px;height:180px;object-fit:cover;">
  <h2>Hola, <?= htmlspecialchars($emp['nombre']) ?></h2>
  <p class="text-muted">Hoy es <?= getFechaActual() ?></p>
  
  <!-- GRID DE TARJETAS AJUSTADO -->
  <div class="container-fluid">
    <div class="row">
      <div class="col-12">
        <div class="row mt-4">
          <div class="col-md-4 d-flex align-items-stretch mb-4">
            <div class="card w-100 border-primary">
              <div class="card-body d-flex flex-column text-center">
              <i class="bi bi-people text-primary fs-5 mb-3"></i>
              <h4 class="card-title">Empleados</h4>
              <p class="card-subtitle mb-3 text-muted">Crear, Listar, Editar Empleados</p>
              <a href="<?= $config['ruta_absoluta'] ?>admin/empleados"
                class="btn d-block w-100 fw-medium bg-primary-subtle text-primary block-card px-4 mt-auto">
                Ver detalles
              </a>
              </div>
            </div>
          </div>
          <div class="col-md-4 d-flex align-items-stretch mb-4">
            <div class="card w-100 border-success">
              <div class="card-body d-flex flex-column text-center">
                  <i class="bi bi-calendar-check text-success fs-5 mb-3"></i>
                  <h4 class="card-title">Gestion de Vacaciones, Permisos, Bajas y Ausencias</h4>
                  <p class="card-subtitle mb-3 text-muted">Gestiona tus días de descanso</p>
                  <a href="<?= $config['ruta_absoluta'] ?>admin/ver-solicitudes"
                    class="btn d-block w-100 fw-medium bg-success-subtle text-success block-card px-4 mt-auto">
                    Gestionar
                  </a>
              </div>
            </div>
          </div>
          
          <!--<div class="col-md-4 d-flex align-items-stretch mb-4">
            <div class="card w-100 border-warning">
              <div class="card-body d-flex flex-column text-center">
                  <i class="bi bi-bar-chart text-warning fs-5 mb-3"></i>
                  <h4 class="card-title">Informes</h4>
                  <p class="card-subtitle mb-3 text-muted">Ver Reportes, Exportar, Estadisticas</p>
                  <a href="informes.php"
                    class="btn d-block w-100 fw-medium bg-warning-subtle text-warning block-card px-4 mt-auto">
                    Generar
                  </a>
              </div>
            </div>
          </div>-->

            <!--
            <div class="col-md-4 d-flex align-items-stretch mb-4">
            <div class="card w-100 border-warning">
              <div class="card-body d-flex flex-column text-center">
                <i class="bi bi-upload text-danger fs-5 mb-3"></i>
                <h4 class="card-title">Contenido</h4>
                <p class="card-subtitle mb-3 text-muted">Gestionar Páginas, Subir/Gestionar Archivos, Notificaciones</p>
                <a href="<?= $config['ruta_absoluta'] ?>admin/contenido"
                class="btn d-block w-100 fw-medium bg-danger-subtle text-danger block-card px-4 mt-auto">
                Crear
                </a>
              </div>
            </div>
            </div>
            -->

          <?php if ($esAdmin): ?>
          <div class="col-md-4 d-flex align-items-stretch mb-4">
            <div class="card w-100 border-warning">
              <div class="card-body d-flex flex-column text-center">
                  <i class="bi bi-shield-lock text-info fs-5 mb-3"></i>
                  <h4 class="card-title">Seguridad</h4>
                  <p class="card-subtitle mb-3 text-muted">Gestión Roles y Permisos, Revisar Logs, Cambiar contraseña</p>
                  <a href="<?= $config['ruta_absoluta'] ?>admin/seguridad"
                    class="btn d-block w-100 fw-medium bg-info-subtle text-info block-card px-4 mt-auto">
                    Ver detalles
                  </a>
              </div>
            </div>
          </div>

          <div class="col-md-4 d-flex align-items-stretch mb-4">
            <div class="card w-100 border-warning">
              <div class="card-body d-flex flex-column text-center">
                  <i class="bi bi-gear text-success fs-5 mb-3"></i>
                  <h4 class="card-title">Configuración</h4>
                  <p class="card-subtitle mb-3 text-muted">Ajustes del Sistema, Gestión de Festivos y Eventos, Configurar Notificaciones, Personalizar Apariencia</p>
                  <a href="<?= $config['ruta_absoluta'] ?>admin/configuracion"
                    class="btn d-block w-100 fw-medium bg-success-subtle text-success block-card px-4 mt-auto">
                    Ver detalles
                  </a>
              </div>
            </div>
          </div>
          <?php endif; ?>



        </div>
        <div id="calendar" style="display:none"></div>
      </div>
  </div>