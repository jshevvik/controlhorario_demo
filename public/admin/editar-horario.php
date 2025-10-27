<?php

error_reporting(E_ALL);

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$empleado = $pdo->query("SELECT * FROM empleados WHERE id = $id")->fetch(PDO::FETCH_ASSOC);

if (!$empleado) {
    echo "<div class='alert alert-danger'>Empleado no encontrado.</div>";
    exit;
}

$dias_semana = ['Lunes','Martes','Miércoles','Jueves','Viernes','Sabado','Domingo'];

$stmt = $pdo->prepare("
  SELECT dia, hora_inicio, hora_fin, hora_inicio_tarde, hora_fin_tarde, horario_partido
  FROM horarios_empleados
  WHERE empleado_id = ?
");
$stmt->execute([$id]);
$horariosData = $stmt->fetchAll(PDO::FETCH_ASSOC);


$horarios_map = [];
foreach ($horariosData as $row) {

  $row['hora_inicio'] = $row['hora_inicio'] ? substr($row['hora_inicio'], 0, 5) : '';
  $row['hora_fin']    = $row['hora_fin']    ? substr($row['hora_fin'],    0, 5) : '';
  $row['hora_inicio_tarde'] = $row['hora_inicio_tarde'] ? substr($row['hora_inicio_tarde'], 0, 5) : '';
  $row['hora_fin_tarde']    = $row['hora_fin_tarde']    ? substr($row['hora_fin_tarde'],    0, 5) : '';
  $horarios_map[$row['dia']] = $row;
}

?>
<?php
// Asume que $pdo, $config, $id, $empleado, $dias_semana y $horarios_map ya están definidos arriba
?>
<div class="mb-3 overflow-hidden position-relative">
  <div class="px-3">
    <h4 class="fs-6 mb-0 mt-2">Editar Horario</h4>
    <nav aria-label="breadcrumb">
      <ol class="breadcrumb mb-0">
        <li class="breadcrumb-item">
          <a href="<?= $config['ruta_absoluta'] ?>dashboard">Inicio</a>
        </li>
        <li class="breadcrumb-item">
          <a href="<?= $config['ruta_absoluta'] ?>admin/empleados">Empleados</a>
        </li>
        <li class="breadcrumb-item active" aria-current="page">Editar Horario</li>
      </ol>
    </nav>
  </div>
</div>

<div class="container my-4">
  <form action="<?= $config['ruta_absoluta'] ?>admin/guardar-horario.php?id=<?= $id ?>" method="POST">
    <input type="hidden" name="empleado_id" value="<?= htmlspecialchars($empleado['id']) ?>">

    <div class="card shadow">
      <div class="card-header bg-primary text-white">
        <h5 class="mb-0"><i class="bi bi-clock"></i> Editar Horario del Empleado</h5>
      </div>
      <div class="card-body">
        <div class="table-responsive">
          <table class="table align-middle text-center">
            <thead>
              <tr>
                <th>Día</th>
                <th>Mañana Inicio</th>
                <th>Mañana Fin</th>
                <th>Tarde Inicio</th>
                <th>Tarde Fin</th>
                <th>Horario Partido</th>
                <th>
                  <div class="d-flex gap-2 justify-content-center">
                    <button id="btnCopiarLunes" type="button" class="btn btn-primary btn-sm">
                      Copiar lunes a todos
                    </button>
                    <button id="btnEliminarTodos" type="button" class="btn btn-danger btn-sm" title="Eliminar todos los horarios">
                      <i class="bi bi-trash-fill"></i>
                    </button>
                  </div>
                </th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($dias_semana as $dia):
                $inicio        = $horarios_map[$dia]['hora_inicio'] ?? '';
                $fin           = $horarios_map[$dia]['hora_fin']    ?? '';
                $inicio_tarde  = $horarios_map[$dia]['hora_inicio_tarde'] ?? '';
                $fin_tarde     = $horarios_map[$dia]['hora_fin_tarde']    ?? '';
                $partido       = !empty($horarios_map[$dia]['horario_partido']);
              ?>
              <tr data-dia="<?= htmlspecialchars($dia) ?>">
                <td><b><?= htmlspecialchars($dia) ?></b></td>
                <td>
                  <input type="time"
                         class="form-control"
                         name="hora_inicio[<?= htmlspecialchars($dia) ?>]"
                         value="<?= htmlspecialchars($inicio) ?>">
                </td>
                <td>
                  <input type="time"
                         class="form-control"
                         name="hora_fin[<?= htmlspecialchars($dia) ?>]"
                         value="<?= htmlspecialchars($fin) ?>">
                </td>
                <td>
                  <input type="time"
                         class="form-control horario-tarde"
                         name="hora_inicio_tarde[<?= htmlspecialchars($dia) ?>]"
                         value="<?= htmlspecialchars($inicio_tarde) ?>"
                         style="display: <?= $partido ? 'block' : 'none' ?>">
                </td>
                <td>
                  <input type="time"
                         class="form-control horario-tarde"
                         name="hora_fin_tarde[<?= htmlspecialchars($dia) ?>]"
                         value="<?= htmlspecialchars($fin_tarde) ?>"
                         style="display: <?= $partido ? 'block' : 'none' ?>">
                </td>
                <td>
                  <div class="form-check form-switch d-flex justify-content-center">
                    <input class="form-check-input horario-partido-switch"
                           type="checkbox"
                           name="horario_partido[<?= htmlspecialchars($dia) ?>]"
                           value="1"
                           data-dia="<?= htmlspecialchars($dia) ?>"
                           <?= $partido ? 'checked' : '' ?>>
                  </div>
                </td>
                <td>
                  <div class="d-flex gap-1 justify-content-center">
                    <?php if ($dia !== 'Lunes'): ?>
                    <button type="button"
                            class="btn btn-primary btn-sm btnCopiarDia"
                            data-destino="<?= htmlspecialchars($dia) ?>"
                            title="Copiar Lunes a este día">
                      <i class="bi bi-clipboard-fill"></i>
                    </button>
                    <?php endif; ?>
                    <button type="button"
                            class="btn btn-danger btn-sm btnEliminarDia"
                            data-dia="<?= htmlspecialchars($dia) ?>"
                            title="Eliminar horario de <?= htmlspecialchars($dia) ?>">
                      <i class="bi bi-trash-fill"></i>
                    </button>
                  </div>
                </td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
        <div class="d-flex justify-content-center gap-3 mt-3">
          <button type="submit" class="btn btn-primary px-4" style="width: 120px;">
            Guardar 
          </button>
          <a href="<?= $config['ruta_absoluta'] ?>admin/ver-empleado?id=<?= $id ?>" class="btn btn-danger" style="width: 120px;">
                Volver
              </a>
        </div>
      </div>
    </div>
  </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function(){
  // Función para mostrar/ocultar campos de tarde
  document.querySelectorAll('.horario-partido-switch').forEach(sw => {
    const toggleTarde = () => {
      const fila = sw.closest('tr');
      fila.querySelectorAll('.horario-tarde').forEach(input => {
        if (sw.checked) {
          input.style.display = 'block';
          input.required = true;
        } else {
          input.style.display = 'none';
          input.required = false;
          input.value = '';
        }
      });
    };
    sw.addEventListener('change', toggleTarde);
    toggleTarde();
  });

  // Helper para obtener la fila por día
  function filaDe(dia) {
    return document.querySelector(`tr[data-dia="${dia}"]`);
  }

  // Función genérica para copiar horario de Lunes a destino
  function copiarHorario(diaDestino){
    const origen = filaDe('Lunes');
    const destino = filaDe(diaDestino);
    if (!origen || !destino) return alert('No se encontró la fila de ' + diaDestino);

    const inicioM = origen.querySelector('input[name="hora_inicio[Lunes]"]').value;
    const finM    = origen.querySelector('input[name="hora_fin[Lunes]"]').value;
    const inicioT = origen.querySelector('input[name="hora_inicio_tarde[Lunes]"]').value;
    const finT    = origen.querySelector('input[name="hora_fin_tarde[Lunes]"]').value;
    const partido = origen.querySelector('input[name="horario_partido[Lunes]"]').checked;

    // Mañana
    destino.querySelector(`input[name="hora_inicio[${diaDestino}]"]`).value = inicioM;
    destino.querySelector(`input[name="hora_fin[${diaDestino}]"]`).value    = finM;

    // Switch y toggle de tarde
    const sw = destino.querySelector(`input[name="horario_partido[${diaDestino}]"]`);
    sw.checked = partido;
    sw.dispatchEvent(new Event('change'));

    // Copiar tarde tras pequeño delay
    setTimeout(() => {
      destino.querySelector(`input[name="hora_inicio_tarde[${diaDestino}]"]`).value = inicioT;
      destino.querySelector(`input[name="hora_fin_tarde[${diaDestino}]"]`).value    = finT;
    }, 10);
  }

  // Botón "Copiar lunes a todos"
  document.getElementById('btnCopiarLunes').addEventListener('click', function(){
    ['Martes','Miércoles','Jueves','Viernes','Sabado','Domingo'].forEach(copiarHorario);
    alert('Horario de Lunes copiado a todos los días');
  });

  // Botón eliminar todos los horarios
  document.getElementById('btnEliminarTodos').addEventListener('click', function(){
    // Borrar todos los campos
    document.querySelectorAll('input[type="time"]').forEach(input => {
      input.value = '';
    });
    
    // Desactivar todos los switches de horario partido
    document.querySelectorAll('.horario-partido-switch').forEach(sw => {
      sw.checked = false;
      sw.dispatchEvent(new Event('change'));
    });
  });

  // Botones individuales "Copiar a este día"
  document.querySelectorAll('.btnCopiarDia').forEach(btn => {
    btn.addEventListener('click', function(){
      copiarHorario(this.dataset.destino);
      
    });
  });

  // Botones individuales "Eliminar día"
  document.querySelectorAll('.btnEliminarDia').forEach(btn => {
    btn.addEventListener('click', function(){
      const dia = this.dataset.dia;
      const fila = filaDe(dia);
      if (!fila) return;

      // Borrar los campos de tiempo de este día
      fila.querySelectorAll('input[type="time"]').forEach(input => {
        input.value = '';
      });
      
      // Desactivar el switch de horario partido
      const sw = fila.querySelector('.horario-partido-switch');
      if (sw) {
        sw.checked = false;
        sw.dispatchEvent(new Event('change'));
      }
    });
  });
});
</script>
