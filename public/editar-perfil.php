<div class="mb-3 overflow-hidden position-relative">
  <div class="px-3">
    <h4 class="fs-6 mb-0 mt-2">Editar Perfil</h4>
    <nav aria-label="breadcrumb">
      <ol class="breadcrumb mb-0">
        <li class="breadcrumb-item">
          <a href="<?= $config['ruta_absoluta'] ?>dashboard">Inicio</a>
        </li>
        <li class="breadcrumb-item">
          <a href="<?= $config['ruta_absoluta'] ?>miperfil">Mi perfil</a>
        </li>
        <li class="breadcrumb-item active" aria-current="page">Editar Perfil</li>
      </ol>
    </nav>
  </div>
<div class="container py-4">
  <div class="row justify-content-center">
    <div class="col-lg-7">
      <div class="card shadow">
        <div class="card-header bg-primary text-white">
          <h4 class="mb-0">Editar perfil</h4>
        </div>
        <div class="card-body">

          <?php if (!empty($_GET['ok'])): ?>
            <div class="alert alert-success">¡Perfil actualizado correctamente!</div>
          <?php elseif (!empty($_GET['error'])): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($_GET['error']) ?></div>
          <?php endif; ?>

          <form method="post" action="<?= $config['ruta_absoluta'] ?>acciones/procesar-editar-perfil.php" autocomplete="off">
            <div class="mb-3">
              <label for="nombre" class="form-label">Nombre</label>
              <input type="text" class="form-control" id="nombre" name="nombre" required value="<?= htmlspecialchars($emp['nombre']) ?>">
            </div>
            <div class="mb-3">
              <label for="apellidos" class="form-label">Apellidos</label>
              <input type="text" class="form-control" id="apellidos" name="apellidos" required value="<?= htmlspecialchars($emp['apellidos']) ?>">
            </div>
            <div class="mb-3">
              <label for="email" class="form-label">Email</label>
              <input type="email" class="form-control" id="email" name="email" required value="<?= htmlspecialchars($emp['email']) ?>">
            </div>
            <div class="mb-3">
              <label for="usuario" class="form-label">Usuario</label>
              <input type="text" class="form-control" id="usuario" name="usuario" required value="<?= htmlspecialchars($emp['usuario']) ?>">
            </div>
            <div class="mb-3">
              <label for="descripcion" class="form-label">Descripción</label>
              <textarea class="form-control" id="descripcion" name="descripcion" rows="3"><?= htmlspecialchars($emp['descripcion'] ?? '') ?></textarea>
            </div>
            <div class="mb-3">
              <label for="password" class="form-label">Nueva contraseña</label>
              <input type="password" class="form-control" id="password" name="password" placeholder="Deja en blanco para no cambiar">
            </div>
            <div class="row">
            <div class="col-12 col-md-8 offset-md-2">
                <div class="d-flex justify-content-center gap-4">
                    <button type="submit" class="btn btn-primary flex-fill">Guardar</button>
                    <a href="<?= $config['ruta_absoluta'] ?>miperfil" class="btn btn-primary flex-fill">Cancelar</a>
                    </div>
                </div>
            </div>
            <input type="hidden" name="id" value="<?= htmlspecialchars($emp['id']) ?>">
          </form>

        </div>
      </div>
    </div>
  </div>
</div>
