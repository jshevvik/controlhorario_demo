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

if (!empty($emp['header_img'])) {
    $headerPath = appendCacheBuster($config['UPLOADS_URL'] . $emp['header_img']);
} else {
    $headerPath = $config['ASSET_URL'] . "img/profilebg.jpg";
}
?>


<div class="mb-3 overflow-hidden position-relative">
  <div class="px-3">
    <h4 class="fs-6 mb-0">Perfil de usuario</h4>
    <nav aria-label="breadcrumb">
      <ol class="breadcrumb mb-0">
        <li class="breadcrumb-item">
          <a href="<?= $config['ruta_absoluta'] ?>dashboard">Inicio</a>
        </li>
        <li class="breadcrumb-item active" aria-current="page">Perfil de usuario</li>
      </ol>
    </nav>
  </div>
</div>
<div class="position-relative overflow-hidden">
  <div class="position-relative overflow-hidden rounded-3" style="max-height:180px;">
    <img src="<?= htmlspecialchars($headerPath) ?>" alt="bg-img" class="w-100" style="object-fit:cover;max-height:180px;">
    <form id="form-subir-header" enctype="multipart/form-data" method="post" class="position-absolute top-0 end-0 m-2" style="z-index:2;">
        <input type="hidden" name="tipo" value="header">
        <label class="btn btn-light btn-sm rounded-circle shadow" style="padding:7px 10px;cursor:pointer;">
            <i class="bi bi-camera"></i>
            <input type="file" name="header_image" accept=".jpg,.jpeg,.png,.gif" style="display:none;">
        </label>
    </form>
  </div>
  <div class="card mx-4 mx-md-9 mt-n5 shadow-lg">
    <div class="card-body pb-0">
      <div class="d-md-flex align-items-center justify-content-between text-center text-md-start">
        <div class="d-md-flex align-items-center">
          <div class="rounded-circle position-relative mb-4 mb-md-0 d-inline-block" style="width:100px;height:100px;">
            <img src="<?= htmlspecialchars($avatarPath) ?>" alt="avatar" class="img-fluid rounded-circle border border-3" width="100" height="100">
            <form id="form-subir-imagen" enctype="multipart/form-data" method="post" class="position-absolute bottom-0 end-0 mb-1 me-1" style="z-index:2;">
                <input type="hidden" name="tipo" value="avatar">
                <label class="bg-primary rounded-circle text-white d-flex align-items-center justify-content-center p-2 border border-2 border-white" style="cursor:pointer; width:36px; height:36px;">
                <i class="bi bi-pencil"></i>
                <input type="file" name="avatar_image" accept=".jpg,.jpeg,.png,.gif" style="display:none;">
                </label>
            </form>
          </div>

          <div class="ms-0 ms-md-3 mb-4 mb-md-0">
            <div class="d-flex align-items-center justify-content-center justify-content-md-start mb-1">
              <h4 class="me-2 mb-0 fs-5"><?= htmlspecialchars($emp['nombre'].' '.$emp['apellidos']) ?></h4>
              <span class="badge fs-6 fw-bold rounded-pill bg-primary-subtle text-primary border-primary border ms-2">
                <?= htmlspecialchars($emp['rol'] ?? 'Empleado') ?>
              </span>
            </div>
            <p class="fs-6 mb-1"><?= htmlspecialchars($emp['email']) ?></p>
            <div class="d-flex align-items-center justify-content-center justify-content-md-start">
              <span class="bg-success p-1 rounded-circle"></span>
              <h6 class="mb-0 ms-2">Activo</h6>
            </div>
          </div>
        </div>
        <a href="<?= $config['ruta_absoluta'] ?>editar-perfil" class="btn btn-primary px-3 shadow-none">
          Editar perfil
        </a>
      </div>
      <ul class="nav nav-pills user-profile-tab mt-4 justify-content-center justify-content-md-start" id="pills-tab" role="tablist">
        <li class="nav-item me-2 me-md-3" role="presentation">
          <button class="nav-link position-relative rounded-0 active d-flex align-items-center justify-content-center bg-transparent py-2" id="pills-profile-tab" data-bs-toggle="pill" data-bs-target="#pills-profile" type="button" role="tab" aria-controls="pills-profile" aria-selected="true">
            <i class="bi bi-person-circle me-0 me-md-2 fs-6"></i>
            <span class="d-none d-md-block">Mi perfil</span>
          </button>
        </li>
      </ul>
    </div>
  </div>
</div>

<div class="tab-content mx-4 mx-md-10" id="pills-tabContent">
  <div class="tab-pane fade show active" id="pills-profile" role="tabpanel" aria-labelledby="pills-profile-tab" tabindex="0">
    <div class="row mt-4">
      <div class="col-lg-4">
        <div class="card">
          <div class="card-body p-4">
            <h4 class="fs-6 mb-3">Sobre mí</h4>
            <p class="mb-0 pb-3 text-dark">
              <?= htmlspecialchars($emp['descripcion'] ?? 'Aquí puedes añadir una breve descripción personal') ?>
            </p>
            <div class="py-3 border-top">
              <h5 class="mb-3">Contacto</h5>
              <div class="d-flex align-items-center mb-3">
                <div class="bg-primary-subtle text-primary fs-14 rounded-circle d-flex align-items-center justify-content-center" style="width:38px;height:38px;">
                  <i class="bi bi-envelope"></i>
                </div>
                <div class="ms-3">
                  <h6 class="mb-1">Email</h6>
                  <p class="mb-0"><?= htmlspecialchars($emp['email']) ?></p>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
      <div class="col-lg-8">
        <div class="card">
          <div class="card-body p-4">
            <h5 class="mb-4">Datos de usuario</h5>
            <ul class="list-group list-group-flush">
              <li class="list-group-item">
                <b>Nombre:</b> <?= htmlspecialchars($emp['nombre'].' '.$emp['apellidos']) ?>
              </li>
              <li class="list-group-item">
                <b>Email:</b> <?= htmlspecialchars($emp['email']) ?>
              </li>
              <li class="list-group-item">
                <b>Usuario:</b> <?= htmlspecialchars($emp['usuario']) ?>
              </li>
              <li class="list-group-item">
                <b>Rol:</b> <?= htmlspecialchars($emp['rol']) ?>
              </li>
            </ul>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Modal para mensajes de subida de imagen -->
<div class="modal fade" id="modalMensajeImagen" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Subida de imagen</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>
      <div class="modal-body" id="modalMensajeImagenBody">
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Cerrar</button>
      </div>
    </div>
  </div>
</div>
