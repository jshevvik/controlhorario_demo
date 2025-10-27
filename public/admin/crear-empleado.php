<?php

?>

<div class="mb-3 overflow-hidden position-relative">
  <div class="px-3">
    <h4 class="fs-6 mb-0 mt-2">Crear Empleado</h4>
    <nav aria-label="breadcrumb">
      <ol class="breadcrumb mb-0">
        <li class="breadcrumb-item">
          <a href="<?= $config['ruta_absoluta'] ?>dashboard">Inicio</a>
        </li>
        <li class="breadcrumb-item">
          <a href="<?= $config['ruta_absoluta'] ?>admin/empleados">Empleados</a>
        </li>
        <li class="breadcrumb-item active" aria-current="page">Crear Empleado</li>
      </ol>
    </nav>
  </div>
</div>

<div class="container py-4">
  <div class="row justify-content-center">
    <div class="col-lg-7">
      <div class="card shadow">
  
        <div class="card-header bg-primary text-white">
          <h4 class="mb-0"> Crear nuevo empleado</h4>
        </div>
        <div class="card-body">
          <?php if (isset($_GET['ok']) && $_GET['ok'] == 1): ?>
            <div class="alert alert-success">¡Empleado creado correctamente!</div>
          <?php endif; ?>

          <form action="<?= $config['ruta_absoluta'] ?>admin/procesar-crear-empleado.php" method="post" autocomplete="off">
            <!-- Campos básicos -->
            <div class="mb-3">
              <label for="nombre" class="form-label">Nombre</label>
              <input id="nombre" name="nombre" type="text" class="form-control" required>
            </div>
            <div class="mb-3">
              <label for="apellidos" class="form-label">Apellidos</label>
              <input id="apellidos" name="apellidos" type="text" class="form-control" required>
            </div>
            <div class="mb-3">
              <label for="usuario" class="form-label">Usuario (login)</label>
              <input id="usuario" name="usuario" type="text" class="form-control" required>
            </div>
            <div class="mb-3">
              <label for="email" class="form-label">Email</label>
              <input id="email" name="email" type="email" class="form-control" required>
            </div>
            <div class="mb-3">
              <label for="clave" class="form-label">Contraseña</label>
              <input id="clave" name="clave" type="password" class="form-control" required>
            </div>
            <div class="mb-4">
              <label for="rol" class="form-label">Rol</label>
              <select id="rol" name="rol" class="form-select" required>
                <option value="" disabled selected>Elige rol</option>
                <option value="empleado">Empleado</option>
                <option value="supervisor">Supervisor</option>
                <option value="admin">Administrador</option>
              </select>
            </div>

            

            <!-- Botón de envío -->
            <div class="d-flex justify-content-center gap-3 mt-3">
              <button type="submit" class="btn btn-primary px-4" style="width: 120px;">
                Guardar 
              </button>
              <a href="<?= $config['ruta_absoluta'] ?>admin/empleados" class="btn btn-secondary" style="width: 120px;">
                    Volver
                  </a>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</div>

