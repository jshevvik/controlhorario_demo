<?php
// Administradores y supervisores pueden editar empleados
requireAdminOrSupervisor();

$empId = intval($_GET['id'] ?? 0);
if (!$empId) {
    echo "<div class='alert alert-danger'>Empleado no válido.</div>";
    exit;
}
$stmt = $pdo->prepare("SELECT * FROM empleados WHERE id = ?");
$stmt->execute([$empId]);
$emp = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$emp) {
    echo "<div class='alert alert-danger'>Empleado no encontrado.</div>";
    exit;
}


$dias_semana = ['Lunes','Martes','Miércoles','Jueves','Viernes','Sabado','Domingo'];

$stmt = $pdo->prepare("
  SELECT dia, hora_inicio, hora_fin
  FROM horarios_empleados
  WHERE empleado_id = ?
");
$stmt->execute([$empId]);
$horariosData = $stmt->fetchAll(PDO::FETCH_ASSOC);


$horarios_map = [];
foreach ($horariosData as $row) {

  $row['hora_inicio'] = $row['hora_inicio'] ? substr($row['hora_inicio'], 0, 5) : '';
  $row['hora_fin']    = $row['hora_fin']    ? substr($row['hora_fin'],    0, 5) : '';
  $horarios_map[$row['dia']] = $row;
}
?>

<div class="mb-3 overflow-hidden position-relative">
  <div class="px-3">
    <h4 class="fs-6 mb-0 mt-2">Editar Empleado</h4>
    <nav aria-label="breadcrumb">
      <ol class="breadcrumb mb-0">
        <li class="breadcrumb-item">
          <a href="<?= $config['ruta_absoluta'] ?>dashboard">Inicio</a>
        </li>
        <li class="breadcrumb-item">
          <a href="<?= $config['ruta_absoluta'] ?>admin/empleados">Empleados</a>
        </li>
        <li class="breadcrumb-item active" aria-current="page">Editar Empleado</li>
      </ol>
    </nav>
  </div>
</div>

<div class="container py-4">
  <div class="row justify-content-center">
    <div class="col-lg-7">
      <div class="card shadow">

        <!-- Cabecera -->
        <div class="card-header bg-primary text-white">
          <h4 class="mb-0">
            <i class="bi bi-person-gear-fill me-1"></i>
            Editar empleado
          </h4>
        </div>

        <div class="card-body">
          <?php if (isset($_GET['ok']) && $_GET['ok'] == 1): ?>
            <div class="alert alert-success">¡Cambios guardados correctamente!</div>
          <?php endif; ?>

          <form
            action="<?= $config['ruta_absoluta'] ?>admin/guardar-empleado.php"
            method="post"
            autocomplete="off"
          >
            <input type="hidden" name="id" value="<?= $empId ?>">

            <!-- Campos básicos -->
            <div class="mb-3">
              <label for="nombre" class="form-label">Nombre</label>
              <input
                id="nombre"
                name="nombre"
                type="text"
                class="form-control"
                required
                value="<?= htmlspecialchars($emp['nombre']) ?>"
              >
            </div>

            <div class="mb-3">
              <label for="apellidos" class="form-label">Apellidos</label>
              <input
                id="apellidos"
                name="apellidos"
                type="text"
                class="form-control"
                required
                value="<?= htmlspecialchars($emp['apellidos']) ?>"
              >
            </div>

            <div class="mb-3">
              <label for="usuario" class="form-label">Usuario (login)</label>
              <input
                id="usuario"
                name="usuario"
                type="text"
                class="form-control"
                required
                value="<?= htmlspecialchars($emp['usuario']) ?>"
              >
            </div>

            <div class="mb-3">
              <label for="email" class="form-label">Email</label>
              <input
                id="email"
                name="email"
                type="email"
                class="form-control"
                required
                value="<?= htmlspecialchars($emp['email']) ?>"
              >
            </div>

            <!-- Contraseña opcional -->
            <div class="mb-3">
              <label for="clave" class="form-label">Contraseña</label>
              <input
                id="clave"
                name="clave"
                type="password"
                class="form-control"
                placeholder="Deja vacío para no cambiarla"
              >
            </div>

            <div class="mb-4">
              <label for="rol" class="form-label">Rol</label>
              <select id="rol" name="rol" class="form-select" required>
                <option value="empleado"   <?= $emp['rol']==='empleado'   ?'selected':'' ?>>Empleado</option>
                <option value="supervisor" <?= $emp['rol']==='supervisor'?'selected':'' ?>>Supervisor</option>
                <option value="admin"      <?= $emp['rol']==='admin'      ?'selected':'' ?>>Administrador</option>
              </select>
            </div>


            <!-- Botón de envío centrado -->
            <div class="button-group d-flex justify-content-center gap-3 mt-4">
              
              <button type="submit" class="btn btn-primary" style="width: 120px;">
               Guardar
              </button>
              <a href="<?= $config['ruta_absoluta'] ?>admin/ver-empleado?id=<?= $empId ?>" class="btn btn-danger" style="width: 120px;">
                Volver
              </a>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</div>
