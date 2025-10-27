document.addEventListener('DOMContentLoaded', () => {
  
  /**/

  // Próximos eventos
  /*fetch('proximos-eventos.php')
    .then(r => r.json())
    .then(eventos => {
      const ul = document.querySelector('#proximosEventosList');
      ul.innerHTML = '';
      eventos.forEach(ev => {
        const li = document.createElement('li');
        li.innerHTML = `<span class="me-2 text-${ev.color.startsWith('#dc')?'danger':'success'}">${ev.label}</span> — ${ev.title}`;
        ul.appendChild(li);
      });
    });


  fetch('fichaje/ultimo-fichaje.php')
    .then(r=>r.json())
    .then(data=>{
      document.getElementById('todayElapsed').textContent = `${Math.floor(data.diffMin/60)} h ${data.diffMin%60} min`;
      document.getElementById('todayStart').textContent = data.hora;
    });*/

// Configurar el botón Volver
  const volverBtn = document.querySelector('.position-relative .btn.btn-link.ps-0');
  if (volverBtn) {
    volverBtn.onclick = goBackToParent;
  }

   // Fichaje: contador con pausas

  (() => {
  
    const btn      = document.getElementById('ficharBtn');
    const timerEl  = document.getElementById('fichajeTimer');
    const liEnter  = document.querySelector('[data-action="enter"]') ?.parentElement;
    const liPause  = document.querySelector('[data-action="pause"]') ?.parentElement;
    const liResume = document.querySelector('[data-action="resume"]')?.parentElement;
    const liExit   = document.querySelector('[data-action="exit"]') ?.parentElement;
    if (!btn || !timerEl) return;

    /*   helpers localStorage   */
    const save = () => localStorage.setItem('fichaje', JSON.stringify({
        estado, tInicio, tPausa, workSec, pauseSec,
        day: (new Date).toISOString().slice(0,10)
    }));
    const load = () => JSON.parse(localStorage.getItem('fichaje') || '{}');

    /*   Restaurar estado local  */
    let { estado='none', tInicio=0, tPausa=0, workSec=0, pauseSec=0, day='' } = load();
    const hoy = (new Date).toISOString().slice(0,10);
    if (day !== hoy) { estado='none'; tInicio=tPausa=workSec=pauseSec=0; }

    /*   Sincronizar con el servidor  */
    fetch(BASE_URL +'fichaje/estado-fichaje.php')
      .then(r => r.json())
      .then(({state, workSec:ws, pauseSec:ps}) => {
          estado   = state;
          workSec  = ws;
          pauseSec = ps;
          // Solo resetear el timestamp del estado actual
          if (estado === 'working') {
              tInicio = Date.now();
          } else if (estado === 'paused') {
              tPausa = Date.now();
          }
          pintar(); ui();
          if (estado!=='none') runTimer();
          save();
      }).catch(console.error);

    /*   utilidades UI / timer  */
    let intId;
    const fmt = n => String(n).padStart(2,'0');
    function pintar () {
        const sWork  = workSec  + (estado==='working' ? ((Date.now()-tInicio)/1000|0) : 0);
        const sPause = pauseSec + (estado==='paused'  ? ((Date.now()-tPausa )/1000|0) : 0);
        timerEl.innerHTML =
          `<span class="text-primary">${fmt(sWork/3600|0)}:${fmt(sWork/60%60|0)}:${fmt(sWork%60)}</span>` +
          (estado==='paused'
            ? ` <small class="text-muted">(pausa ${fmt(sPause/3600|0)}:${fmt(sPause/60%60|0)}:${fmt(sPause%60)})</small>`
            : '');
    }
    function ui(){
        if (liEnter ) liEnter .style.display = (estado==='none'   )?'':'none';
        if (liPause ) liPause .style.display = (estado==='working')?'':'none';
        if (liResume) liResume.style.display = (estado==='paused' )?'':'none';
        if (liExit  ) liExit  .style.display = (estado!=='none'   )?'':'none';
        btn.textContent =
          estado==='none'    ? 'Entrar'         :
          estado==='working' ? 'Pausar / Salir' :
                              'Reanudar / Salir';
    }

    function runTimer(){ 
      clearInterval(intId); 
      pintar(); 
      intId = setInterval(pintar,1000); 
    }

    /*   peticiones al backend con geolocalización  */
    const api = async (tipo) => {
        try {
            // Intentar obtener geolocalización
            const geoData = await obtenerGeolocalización();
            
            const formData = new URLSearchParams({tipo});
            
            // Añadir datos de geolocalización si están disponibles
            if (geoData) {
                formData.append('lat', geoData.lat);
                formData.append('lng', geoData.lng);
                formData.append('acc', geoData.accuracy);

            } else {

            }

            return fetch(BASE_URL + 'fichaje/procesar-fichaje.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: formData
            });
        } catch (error) {
            console.error('Error en fichaje:', error);
            // Enviar fichaje sin geolocalización como fallback
            return fetch(BASE_URL + 'fichaje/procesar-fichaje.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: new URLSearchParams({tipo})
            });
        }
    };

    // Función para obtener geolocalización de forma no bloqueante
    function obtenerGeolocalización() {
        return new Promise((resolve) => {
            if (!navigator.geolocation) {
                resolve(null);
                return;
            }

            const timeout = setTimeout(() => {
                resolve(null);
            }, 5000); // Timeout de 5 segundos

            navigator.geolocation.getCurrentPosition(
                (position) => {
                    clearTimeout(timeout);
                    resolve({
                        lat: position.coords.latitude,
                        lng: position.coords.longitude,
                        accuracy: position.coords.accuracy
                    });
                },
                (error) => {
                    clearTimeout(timeout);

                    resolve(null);
                },
                {
                    enableHighAccuracy: true,
                    timeout: 4000,
                    maximumAge: 30000 // Cache por 30 segundos
                }
            );
        });
    }

    /*   transiciones de estado  */
    function startWork(){ 
      estado='working'; 
      tInicio=Date.now(); 
      runTimer(); 
      ui(); 
      save(); 
      api('entrada').then(r => r.json()).then(data => {
        if (data.error === 'duplicado') {
          mostrarNotificacion('⚠️ Ya has entrado. No se puede registrar entrada duplicada.', 'warning');
          estado='none'; ui(); save(); pintar();
        } else {
          mostrarNotificacion('✅ Entrada registrada. ¡Bienvenido!', 'success');
        }
      }).catch(err => {
        console.error('Error en entrada:', err);
        mostrarNotificacion('❌ Error al registrar entrada', 'danger');
        estado='none'; ui(); save(); pintar();
      });
    }

    function pauseWork(){ 
      estado='paused';  
      workSec+=((Date.now()-tInicio)/1000|0); 
      tPausa=Date.now(); 
      runTimer(); 
      ui(); 
      save(); 
      api('pausa_inicio').then(r => r.json()).then(data => {
        if (data.error === 'duplicado') {
          mostrarNotificacion('⚠️ Ya estás en pausa. No se puede pausar de nuevo.', 'warning');
          estado='working'; ui(); save(); pintar();
        } else {
          mostrarNotificacion('⏸️ Pausa iniciada. Descansa un poco...', 'info');
        }
      }).catch(err => {
        console.error('Error en pausa:', err);
        mostrarNotificacion('❌ Error al pausar', 'danger');
        estado='working'; ui(); save(); pintar();
      });
    }

    function resumeWork(){
      estado='working';
      pauseSec+=((Date.now()-tPausa )/1000|0); 
      tInicio=Date.now(); 
      runTimer(); 
      ui(); 
      save(); 
      api('pausa_fin').then(r => r.json()).then(data => {
        if (data.error === 'duplicado') {
          mostrarNotificacion('⚠️ No estás en pausa. No se puede reanudar.', 'warning');
          estado='paused'; ui(); save(); pintar();
        } else {
          mostrarNotificacion('▶️ Trabajo reanudado. ¡Vamos!', 'success');
        }
      }).catch(err => {
        console.error('Error en reanudación:', err);
        mostrarNotificacion('❌ Error al reanudar', 'danger');
        estado='paused'; ui(); save(); pintar();
      });
    }

    function exitWork(){
        // Acumular el tiempo pendiente ANTES de cambiar estado
        if (estado==='working') workSec+=((Date.now()-tInicio)/1000|0);
        if (estado==='paused' ) pauseSec+=((Date.now()-tPausa )/1000|0);
        
        clearInterval(intId); 
        
        // Enviar salida y esperar respuesta del servidor para sincronizar
        api('salida').then(r => r.json()).then(data => {
            if (data.error === 'duplicado') {
                mostrarNotificacion('⚠️ Ya has salido. No se puede registrar salida duplicada.', 'warning');
                estado = 'none'; ui(); save(); pintar();
            } else {
                // Sincronizar con los valores del servidor después de salida
                estado = data.state || 'none';
                workSec = data.workSec || 0;
                pauseSec = data.pauseSec || 0;
                save();
                ui();
                pintar();
                mostrarNotificacion('👋 Salida registrada. ¡Hasta luego!', 'success');
            }
        }).catch(err => {
            console.error('Error en salida:', err);
            mostrarNotificacion('❌ Error al registrar salida', 'danger');
            estado='none'; 
            ui(); 
            save();
            pintar();
        });
        
        /* reset automático a medianoche */
        const ahora=new Date();
        const ms = (24-ahora.getHours())*3600000 - ahora.getMinutes()*60000 -
                  ahora.getSeconds()*1000 - ahora.getMilliseconds();
        setTimeout(()=>{ 
          workSec=pauseSec=0; 
          save(); 
          pintar(); 
        }, 
        ms);
    }

    /*   Función para mostrar notificaciones  */
    function mostrarNotificacion(mensaje, tipo = 'info') {
        // Crear elemento de notificación
        const notif = document.createElement('div');
        notif.className = `alert alert-${tipo} alert-dismissible fade show position-fixed`;
        notif.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px; box-shadow: 0 2px 8px rgba(0,0,0,0.15);';
        notif.innerHTML = `
            ${mensaje}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        `;
        document.body.appendChild(notif);
        
        // Auto-cerrar después de 4 segundos
        setTimeout(() => {
            notif.remove();
        }, 4000);
    }

    /*   Listeners  */
    btn.addEventListener('click', e => { 
      if (estado==='none'){ 
        e.preventDefault(); 
        btn.disabled = true; // Deshabilitar botón temporalmente
        startWork(); 
        setTimeout(() => { btn.disabled = false; }, 500); // Reabilitar después de 500ms
      } 
    });

    let procesando = false; // Flag para evitar clics duplicados
    document.querySelectorAll('.dropdown-item[data-action]')
            .forEach(a=>a.addEventListener('click', e=>{
                e.preventDefault();
                if (procesando) return; // Evitar clics duplicados
                procesando = true;
                const act = a.dataset.action;
                if      (act==='pause')  pauseWork();
                else if (act==='resume') resumeWork();
                else if (act==='exit')   exitWork();
                bootstrap.Dropdown.getInstance(btn)?.hide();
                setTimeout(() => { procesando = false; }, 1000); // Permitir siguiente acción después de 1s
            }));

    /*   Arranque  */
    ui(); 
    pintar();
  })();


});

// Subida AJAX de avatar y cabecera en perfil de usuario con modal flotante
function mostrarModalImagen(mensaje, tipo) {
  var $modal = $('#modalMensajeImagen');
  var $body = $('#modalMensajeImagenBody');
  $body.html('<span class="text-' + (tipo === 'ok' ? 'success' : (tipo === 'info' ? 'info' : 'danger')) + '">' + mensaje + '</span>');
  var modal = bootstrap.Modal.getOrCreateInstance(document.getElementById('modalMensajeImagen'));
  modal.show();
}

$(function() {
  
  $('#form-subir-imagen input[type="file"]').on('change', function() {
    $('#form-subir-imagen').submit();
  });

  // Subida AJAX del avatar
  $('#form-subir-imagen').on('submit', function(e) {
    e.preventDefault();
    var form = this;
    var formData = new FormData(form);
    mostrarModalImagen('Subiendo imagen...', 'info');
    $.ajax({
      url: BASE_URL +'acciones/subir-imagen.php',
      type: 'POST',
      data: formData,
      processData: false,
      contentType: false,
      success: function(resp) {
        if (resp.trim() === 'OK') {
          mostrarModalImagen('Imagen subida correctamente.', 'ok');
          setTimeout(function() { location.reload(); }, 1000);
        } else {
          mostrarModalImagen(resp, 'error');
        }
      },
      error: function() {
        mostrarModalImagen('Error al subir la imagen.', 'error');
      }
    });
  });


  // Header AJAX
  $('#form-subir-header input[type=file]').on('change', function() {
    var form = $('#form-subir-header')[0];
    var formData = new FormData(form);
    mostrarModalImagen('Subiendo imagen...', 'info');
    $.ajax({
      url: BASE_URL +'acciones/subir-imagen.php',
      type: 'POST',
      data: formData,
      processData: false,
      contentType: false,
      success: function(resp) {
        if (resp.trim() === 'OK') {
          mostrarModalImagen('Imagen subida correctamente.', 'ok');
          setTimeout(function() { location.reload(); }, 1000);
        } else {
          mostrarModalImagen(resp, 'error');
        }
      },
      error: function() {
        mostrarModalImagen('Error al subir la imagen.', 'error');
      }
    });
  });

});

// Función global para volver a la página padre
function goBackToParent() {
  const breadcrumbs = document.querySelectorAll('.breadcrumb-item');
  let parentPageUrl = null;
  
  // Buscar el último breadcrumb no activo (padre)
  for (let i = breadcrumbs.length - 2; i >= 0; i--) {
    const crumb = breadcrumbs[i];
    if (!crumb.classList.contains('active')) {
      const link = crumb.querySelector('a');
      if (link) {
        parentPageUrl = link.href;
        break;
      }
    }
  }

  // Redireccionar o usar fallback
  if (parentPageUrl) {
    window.location.href = parentPageUrl;
  } else {
    if (history.length > 1) {
      history.back();
    } else {
      window.location.href = "<?= $config['ruta_absoluta'] ?>dashboard";
    }
  }
}
// Funciones de copiar horario como en “Editar Horario”
function copiarLunes() {
  const ini = document.querySelector('input[name="hora_inicio[Lunes]"]').value;
  const fin = document.querySelector('input[name="hora_fin[Lunes]"]').value;
  ['Martes','Miércoles','Jueves','Viernes','Sábado','Domingo'].forEach(d => {
    document.querySelector(`input[name="hora_inicio[${d}]"]`).value = ini;
    document.querySelector(`input[name="hora_fin[${d}]"]`).value = fin;
  });
}

function copiarDia(dia) {
  var inicio = document.querySelector('input[name="hora_inicio[Lunes]"]').value;
  var fin = document.querySelector('input[name="hora_fin[Lunes]"]').value;
  document.querySelector('input[name="hora_inicio['+dia+']"]').value = inicio;
  document.querySelector('input[name="hora_fin['+dia+']"]').value = fin;
}

// Función para manejar los detalles de fichajes en ver-empleado.php
function initDetalleFichajes(detalleUrl, empleadoId) {
  // Delegación de clic sobre todo el body
  document.body.addEventListener('click', async (e) => {
    const btn = e.target.closest('.expand-btn');
    if (!btn) return;
    e.preventDefault();

    // Verificar si es vista móvil o desktop
    const card = btn.closest('.card[data-fecha]');
    const mainTr = btn.closest('tr[data-fecha]');
    
    if (card) {
      // Vista móvil
      const fecha = card.dataset.fecha;
      const detailDiv = document.getElementById(`detalle-mobile-${fecha}`);
      if (!detailDiv) return;

      const content = detailDiv.querySelector(`#content-mobile-${fecha}`);
      const spinner = detailDiv.querySelector(`#spinner-mobile-${fecha}`);
      const icon = btn.querySelector('i');

      // Toggle de visibilidad
      const opening = !detailDiv.style.display || detailDiv.style.display === 'none';
      if (opening) {
        detailDiv.style.display = 'block';
        icon.classList.replace('bi-chevron-down','bi-chevron-up');
        btn.setAttribute('aria-expanded', 'true');

        // Solo lanzamos la petición la primera vez
        if (content.children.length === 0) {
          spinner.classList.remove('d-none');
          await loadFichajeDetails(detalleUrl, empleadoId, fecha, content, spinner);
        }
      } else {
        detailDiv.style.display = 'none';
        icon.classList.replace('bi-chevron-up','bi-chevron-down');
        btn.setAttribute('aria-expanded', 'false');
      }
    } else if (mainTr) {
      // Vista desktop (tabla)
      const fecha = mainTr.dataset.fecha;
      const detTr = document.getElementById(`detalle-${fecha}`);
      if (!detTr) return;

      const content = detTr.querySelector(`#content-${fecha}`);
      const spinner = detTr.querySelector(`#spinner-${fecha}`);
      const icon = btn.querySelector('i');

      // Toggle de visibilidad - mantener iconos + y - para desktop
      const opening = !detTr.style.display || detTr.style.display === 'none';
      if (opening) {
        detTr.style.display = 'table-row';
        icon.classList.replace('bi-plus','bi-dash');
        btn.setAttribute('aria-expanded', 'true');

        // Solo lanzamos la petición la primera vez
        if (content.children.length === 0) {
          spinner.classList.remove('d-none');
          await loadFichajeDetails(detalleUrl, empleadoId, fecha, content, spinner);
        }
      } else {
        detTr.style.display = 'none';
        icon.classList.replace('bi-dash','bi-plus');
        btn.setAttribute('aria-expanded', 'false');
      }
    }
  });
}

// Función auxiliar para cargar los detalles de fichajes
async function loadFichajeDetails(detalleUrl, empleadoId, fecha, content, spinner) {
  try {
    const resp = await fetch(detalleUrl, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ fecha, empleado_id: empleadoId })
    });
    const data = await resp.json();

    if (!data.success) {
      content.innerHTML = `<div class="alert alert-danger">${data.error || 'Error desconocido'}</div>`;
      console.warn('DEBUG detalle-fichajes:', data.debug);
    } else {
      // Ordenar cronológicamente
      const fichajes = data.fichajes;
      fichajes.sort((a, b) =>
        a.fecha_hora_completa.localeCompare(b.fecha_hora_completa)
      );

      if (fichajes.length === 0) {
        content.innerHTML = `
          <div class="fichaje-no-data">
            <i class="bi bi-calendar-x"></i>
            <h6 class="mb-0">Sin registros de fichaje</h6>
            <small class="text-muted">No se encontraron fichajes para este día</small>
          </div>`;
      } else {
        // Detectar si es vista móvil
        const isMobile = content.id.includes('mobile') || window.innerWidth <= 768;
        
        if (isMobile) {
          // Vista móvil - tarjetas compactas
          let html = '<div class="mobile-fichajes-list">';
          
          fichajes.forEach((f, index) => {
            let badgeClass = 'bg-secondary';
            let icon = '';
            let tipoClass = '';
            
            if (f.tipo === 'Entrada') {
              badgeClass = 'bg-success';
              icon = 'bi-box-arrow-in-right';
              tipoClass = 'tipo-entrada';
            } else if (f.tipo === 'Salida') {
              badgeClass = 'bg-danger';
              icon = 'bi-box-arrow-left';
              tipoClass = 'tipo-salida';
            } else if (f.tipo === 'Inicio pausa') {
              badgeClass = 'bg-warning text-dark';
              icon = 'bi-pause-circle';
              tipoClass = 'tipo-pausa-inicio';
            } else if (f.tipo === 'Fin pausa') {
              badgeClass = 'bg-info';
              icon = 'bi-play-circle';
              tipoClass = 'tipo-pausa-fin';
            }
            
            html += `
              <div class="mobile-fichaje-item ${tipoClass} ${index > 0 ? 'mt-2' : ''}">
                <div class="text-center">
                  <div class="mb-2">
                    <span class="fw-bold text-primary fs-5">${f.hora}</span>
                  </div>
                  <div class="mb-2">
                    <span class="badge ${badgeClass}">
                      <i class="bi ${icon} me-1"></i>
                      ${f.tipo}
                    </span>
                  </div>
                  <div class="mb-1">
                    <span class="text-muted small fw-semibold">${f.duracion}</span>
                  </div>
                </div>
                <div class="mt-2 text-center">
                  <small class="text-muted">${f.contexto}</small>
                </div>
              </div>`;
          });
          
          html += '</div>';
          content.innerHTML = html;
        } else {
        // Construir tabla interna con mejor diseño
        let html = '<div class="detalle-fichajes-table">'
                 + '<div class="table-responsive">'
                 + '<table class="table table-sm mb-0">'
                 + '<thead>'
                 + '<tr>'
                 + '<th class="col-hora"><i class="bi bi-clock me-1"></i>Hora</th>'
                 + '<th class="col-accion"><i class="bi bi-tag me-1"></i>Acción</th>'
                 + '<th class="col-duracion"><i class="bi bi-stopwatch me-1"></i>Duración</th>'
                 + '<th class="col-descripcion"><i class="bi bi-info-circle me-1"></i>Descripción</th>'
                 + '</tr>'
                 + '</thead>'
                 + '<tbody>';

        fichajes.forEach((f, index) => {
          let badge = 'bg-secondary';
          let icon = '';
          
          if (f.tipo === 'Entrada') {
            badge = 'bg-success';
            icon = 'bi-box-arrow-in-right';
          } else if (f.tipo === 'Salida') {
            badge = 'bg-danger';
            icon = 'bi-box-arrow-left';
          } else if (f.tipo === 'Inicio pausa') {
            badge = 'bg-warning text-dark';
            icon = 'bi-pause-circle';
          } else if (f.tipo === 'Fin pausa') {
            badge = 'bg-info';
            icon = 'bi-play-circle';
          }
          
          html += `<tr>
            <td class="text-primary fw-bold">${f.hora}</td>
            <td>
              <span class="badge ${badge} d-inline-flex align-items-center">
                <i class="bi ${icon} me-1"></i>
                ${f.tipo}
              </span>
            </td>
            <td class="fw-semibold">${f.duracion}</td>
            <td class="text-muted">${f.contexto}</td>
          </tr>`;
        });

        html += '</tbody></table></div></div>';
        content.innerHTML = html;
        }
      }
    }
  } catch (err) {
    console.error('Fetch detalle-fichajes error:', err);
    content.innerHTML = `<div class="alert alert-danger">Error cargando detalles: ${err.message}</div>`;
  } finally {
    spinner.classList.add('d-none');
  }
}

// ========================================
// FUNCIONES PARA SISTEMA DE INFORMES
// ========================================

/**
 * Establece fechas rápidas en el formulario de informes
 * @param {string} periodo - Período a establecer (hoy, semana, mes, trimestre, año)
 */
function setFechaRapida(periodo) {
    const fechaInicio = document.getElementById('fecha_inicio');
    const fechaFin = document.getElementById('fecha_fin');
    const hoy = new Date();
    
    switch (periodo) {
        case 'hoy':
            const hoyStr = hoy.toISOString().split('T')[0];
            fechaInicio.value = hoyStr;
            fechaFin.value = hoyStr;
            break;
            
        case 'semana':
            const inicioSemana = new Date(hoy);
            inicioSemana.setDate(hoy.getDate() - hoy.getDay() + 1); // Lunes
            const finSemana = new Date(inicioSemana);
            finSemana.setDate(inicioSemana.getDate() + 6); // Domingo
            
            fechaInicio.value = inicioSemana.toISOString().split('T')[0];
            fechaFin.value = finSemana.toISOString().split('T')[0];
            break;
            
        case 'mes':
            const inicioMes = new Date(hoy.getFullYear(), hoy.getMonth(), 1);
            const finMes = new Date(hoy.getFullYear(), hoy.getMonth() + 1, 0);
            
            fechaInicio.value = inicioMes.toISOString().split('T')[0];
            fechaFin.value = finMes.toISOString().split('T')[0];
            break;
            
        case 'trimestre':
            const mesActual = hoy.getMonth();
            const inicioTrimestre = new Date(hoy.getFullYear(), Math.floor(mesActual / 3) * 3, 1);
            const finTrimestre = new Date(hoy.getFullYear(), Math.floor(mesActual / 3) * 3 + 3, 0);
            
            fechaInicio.value = inicioTrimestre.toISOString().split('T')[0];
            fechaFin.value = finTrimestre.toISOString().split('T')[0];
            break;
            
        case 'año':
            const inicioAño = new Date(hoy.getFullYear(), 0, 1);
            const finAño = new Date(hoy.getFullYear(), 11, 31);
            
            fechaInicio.value = inicioAño.toISOString().split('T')[0];
            fechaFin.value = finAño.toISOString().split('T')[0];
            break;
    }
}

/**
 * Imprime el informe actual
 */
function imprimirInforme() {
    window.print();
}

/**
 * Exporta el informe actual a PDF
 */
function exportarPDF() {
    const params = new URLSearchParams(window.location.search);
    params.set('formato', 'pdf');
    window.open(window.location.pathname + '?' + params.toString(), '_blank');
}

/**
 * Exporta el informe actual a Excel
 */
function exportarExcel() {
    const params = new URLSearchParams(window.location.search);
    params.set('formato', 'excel');
    window.location.href = window.location.pathname + '?' + params.toString();
}

/**
 * Inicializa las validaciones de fechas para el formulario de informes
 */
function inicializarValidacionesFechas() {
    const fechaInicio = document.getElementById('fecha_inicio');
    const fechaFin = document.getElementById('fecha_fin');
    
    if (fechaInicio) {
        fechaInicio.addEventListener('change', function() {
            const fechaInicioVal = this.value;
            const fechaFinVal = fechaFin ? fechaFin.value : '';
            
            if (fechaInicioVal && fechaFinVal && fechaInicioVal > fechaFinVal) {
                fechaFin.value = fechaInicioVal;
            }
        });
    }
    
    if (fechaFin) {
        fechaFin.addEventListener('change', function() {
            const fechaInicioVal = fechaInicio ? fechaInicio.value : '';
            const fechaFinVal = this.value;
            
            if (fechaInicioVal && fechaFinVal && fechaFinVal < fechaInicioVal) {
                fechaInicio.value = fechaFinVal;
            }
        });
    }
}

// Inicializar validaciones de fechas cuando se carga la página
document.addEventListener('DOMContentLoaded', function() {
    inicializarValidacionesFechas();
});

// ========================================
// FUNCIONES DE SEGURIDAD Y PAGINACIÓN
// ========================================

// Variables globales para paginación
let currentPage = 1;
let currentFilters = {};
let totalRecords = 0;
let recordsPerPage = 5;
let totalPages = 1;

// Manejar cambio de usuario para mostrar/ocultar contraseña actual
function handleUsuarioChange() {
    const usuarioSelect = document.getElementById('usuario_id');
    const claveActualContainer = document.getElementById('clave-actual-container');
    const claveActualInput = document.getElementById('clave_actual');
    const claveActualHelp = document.getElementById('clave-actual-help');
    
    if (!usuarioSelect || !claveActualContainer) return;
    
    // Obtener el empleado_id del admin logueado (se debe pasar desde PHP)
    const adminEmpleadoId = window.adminEmpleadoId || '0';
    const usuarioSeleccionado = usuarioSelect.value;
    
    if (usuarioSeleccionado === adminEmpleadoId) {
        // Es el mismo admin, mostrar campo de contraseña actual
        claveActualContainer.style.display = 'block';
        claveActualInput.required = true;
        claveActualHelp.textContent = 'Necesaria para cambiar tu propia contraseña';
        claveActualHelp.className = 'text-warning';
    } else {
        // Es otro usuario, ocultar campo de contraseña actual
        claveActualContainer.style.display = 'none';
        claveActualInput.required = false;
        claveActualInput.value = '';
        claveActualHelp.textContent = 'Como administrador, puedes cambiar contraseñas de otros usuarios sin conocer la actual';
        claveActualHelp.className = 'text-info';
    }
}

// Función para mostrar/ocultar contraseñas
function togglePassword(fieldId) {
    const field = document.getElementById(fieldId);
    const icon = document.getElementById('icon-' + fieldId);
    
    if (!field || !icon) return;
    
    if (field.type === 'password') {
        field.type = 'text';
        icon.className = 'bi bi-eye-slash';
    } else {
        field.type = 'password';
        icon.className = 'bi bi-eye';
    }
}

// Verificar fortaleza de contraseña
function checkPasswordStrength() {
    const password = document.getElementById('nueva_clave')?.value || '';
    const strengthBar = document.getElementById('strengthBar');
    const strengthText = document.getElementById('strengthText');
    
    if (!strengthBar || !strengthText) return;
    
    let score = 0;
    let feedback = [];
    
    // Criterios de fortaleza
    if (password.length >= 8) score += 20; else feedback.push('al menos 8 caracteres');
    if (/[a-z]/.test(password)) score += 20; else feedback.push('minúsculas');
    if (/[A-Z]/.test(password)) score += 20; else feedback.push('mayúsculas');
    if (/[0-9]/.test(password)) score += 20; else feedback.push('números');
    if (/[^A-Za-z0-9]/.test(password)) score += 20; else feedback.push('símbolos');
    
    // Actualizar barra visual
    strengthBar.style.width = score + '%';
    
    if (score < 40) {
        strengthBar.style.backgroundColor = '#dc3545';
        strengthText.textContent = 'Débil - Agrega: ' + feedback.join(', ');
        strengthText.className = 'text-danger';
    } else if (score < 80) {
        strengthBar.style.backgroundColor = '#ffc107';
        strengthText.textContent = 'Media - Mejora: ' + feedback.join(', ');
        strengthText.className = 'text-warning';
    } else {
        strengthBar.style.backgroundColor = '#28a745';
        strengthText.textContent = '¡Contraseña segura!';
        strengthText.className = 'text-success';
    }
}

// Verificar coincidencia de contraseñas
function checkPasswordMatch() {
    const nueva = document.getElementById('nueva_clave')?.value || '';
    const confirmar = document.getElementById('confirmar_clave')?.value || '';
    const matchText = document.getElementById('matchText');
    
    if (!matchText) return;
    
    if (confirmar.length > 0) {
        if (nueva === confirmar) {
            matchText.textContent = '✓ Las contraseñas coinciden';
            matchText.className = 'text-success';
        } else {
            matchText.textContent = '✗ Las contraseñas no coinciden';
            matchText.className = 'text-danger';
        }
    } else {
        matchText.textContent = '';
    }
}

// Cambiar contraseña
async function cambiarContrasena(e) {
    e.preventDefault();
    
    const submitBtn = document.getElementById('submitBtn');
    const spinner = document.getElementById('spinner');
    const alertDiv = document.getElementById('password-alert');
    
    if (!submitBtn || !spinner || !alertDiv) return;
    
    // Validaciones
    const nueva = document.getElementById('nueva_clave')?.value || '';
    const confirmar = document.getElementById('confirmar_clave')?.value || '';
    const usuarioId = document.getElementById('usuario_id')?.value || '';
    const claveActual = document.getElementById('clave_actual')?.value || '';
    
    if (!usuarioId) {
        mostrarAlerta('Debes seleccionar un usuario', 'danger');
        return;
    }
    
    if (nueva !== confirmar) {
        mostrarAlerta('Las contraseñas no coinciden', 'danger');
        return;
    }
    
    if (nueva.length < 6) {
        mostrarAlerta('La contraseña debe tener al menos 6 caracteres', 'danger');
        return;
    }
    
    // Verificar si es cambio propio y necesita contraseña actual
    const adminEmpleadoId = window.adminEmpleadoId || '0';
    const esCambioPropio = (usuarioId === adminEmpleadoId);
    
    if (esCambioPropio && !claveActual) {
        mostrarAlerta('Debes proporcionar tu contraseña actual', 'danger');
        return;
    }
    
    // Mostrar spinner
    submitBtn.disabled = true;
    spinner.style.display = 'inline-block';
    
    try {
        const formData = new FormData(document.getElementById('passwordForm'));
        
        const response = await fetch('procesar-cambio-clave.php', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            mostrarAlerta(result.message, 'success');
            document.getElementById('passwordForm').reset();
            document.getElementById('strengthBar').style.width = '0%';
            document.getElementById('strengthText').textContent = '';
            document.getElementById('matchText').textContent = '';
            
            // Resetear visibilidad de contraseña actual
            document.getElementById('clave-actual-container').style.display = 'block';
            document.getElementById('clave_actual').required = false;
        } else {
            mostrarAlerta(result.message, 'danger');
        }
    } catch (error) {
        mostrarAlerta('Error al procesar la solicitud', 'danger');
        console.error('Error:', error);
    } finally {
        submitBtn.disabled = false;
        spinner.style.display = 'none';
    }
}

// Mostrar alertas
function mostrarAlerta(mensaje, tipo) {
    const alertDiv = document.getElementById('password-alert');
    if (!alertDiv) return;
    
    alertDiv.className = `alert alert-${tipo}`;
    alertDiv.textContent = mensaje;
    alertDiv.style.display = 'block';
    
    // Auto-ocultar después de 5 segundos
    setTimeout(() => {
        alertDiv.style.display = 'none';
    }, 5000);
}

// Función para cambiar página
async function changePage(page) {
    // Validar página
    if (page < 1 || page > totalPages) return;
    
    currentPage = page;
    
    // COMENTADO: Cargar datos de la página de logs
    // await loadLogsData();
}

/* COMENTADO: Funciones relacionadas con logs de seguridad
// Cargar datos de logs desde la base de datos
async function loadLogsData() {
    try {
        // Mostrar indicador de carga
        showLoadingState();
        
        const response = await fetch('obtener-logs-seguridad.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                page: currentPage,
                perPage: recordsPerPage,
                filters: currentFilters
            })
        });
        
        if (!response.ok) {
            throw new Error('Error en la respuesta del servidor');
        }
        
        const result = await response.json();
        
        if (result.success) {
            // Actualizar variables globales
            totalRecords = result.total;
            totalPages = Math.ceil(totalRecords / recordsPerPage);
            
            // Actualizar la tabla con los datos
            updateLogsTable(result.data);
            
            // Actualizar paginación visual
            updatePagination();
            
            // Actualizar información de registros
            updateRecordsInfo();
        } else {
            throw new Error(result.message || 'Error al cargar los logs');
        }
    } catch (error) {
        console.error('Error al cargar logs:', error);
        showErrorState(error.message);
    } finally {
        hideLoadingState();
    }
}

// Mostrar estado de carga
function showLoadingState() {
    // Vista móvil
    const mobileView = document.querySelector('.d-block.d-md-none .list-group');
    if (mobileView) {
        mobileView.innerHTML = `
            <div class="text-center py-4">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Cargando...</span>
                </div>
                <div class="mt-2 text-muted">Cargando logs...</div>
            </div>
        `;
    }
    
    // Vista desktop
    const desktopView = document.querySelector('.d-none.d-md-block tbody');
    if (desktopView) {
        desktopView.innerHTML = `
            <tr>
                <td colspan="5" class="text-center py-4">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Cargando...</span>
                    </div>
                    <div class="mt-2 text-muted">Cargando logs...</div>
                </td>
            </tr>
        `;
    }
}

// Ocultar estado de carga
function hideLoadingState() {
    // Esta función se ejecuta automáticamente al actualizar la tabla
}

// Mostrar estado de error
function showErrorState(message) {
    // Vista móvil
    const mobileView = document.querySelector('.d-block.d-md-none .list-group');
    if (mobileView) {
        mobileView.innerHTML = `
            <div class="text-center py-4">
                <i class="bi bi-exclamation-triangle text-warning fs-1 mb-3"></i>
                <div class="text-muted">Error al cargar logs: ${message}</div>
                <button class="btn btn-sm btn-outline-primary mt-2" onclick="loadLogsData()">
                    <i class="bi bi-arrow-clockwise me-1"></i>
                    Reintentar
                </button>
            </div>
        `;
    }
    
    // Vista desktop
    const desktopView = document.querySelector('.d-none.d-md-block tbody');
    if (desktopView) {
        desktopView.innerHTML = `
            <tr>
                <td colspan="5" class="text-center py-4">
                    <i class="bi bi-exclamation-triangle text-warning fs-1 mb-3"></i>
                    <div class="text-muted">Error al cargar logs: ${message}</div>
                    <button class="btn btn-sm btn-outline-primary mt-2" onclick="loadLogsData()">
                        <i class="bi bi-arrow-clockwise me-1"></i>
                        Reintentar
                    </button>
                </td>
            </tr>
        `;
    }
}

// Actualizar tabla de logs con datos dinámicos
function updateLogsTable(logsData = []) {
    if (!logsData || logsData.length === 0) {
        showEmptyState();
        return;
    }
    
    // Actualizar vista móvil
    const mobileView = document.querySelector('.d-block.d-md-none .list-group');
    if (mobileView) {
        mobileView.innerHTML = logsData.map(log => `
            <div class="list-group-item border-0 py-3">
                <div class="d-flex justify-content-between align-items-start">
                    <div class="flex-grow-1">
                        <div class="fw-bold text-dark mb-1">${escapeHtml(log.usuario || 'Usuario desconocido')}</div>
                        <div class="text-muted small mb-2">${escapeHtml(log.accion || 'Acción no especificada')}</div>
                        <div class="d-flex align-items-center gap-2">
                            <small class="text-muted">${formatDate(log.fecha)}</small>
                            <code class="small">${escapeHtml(log.ip || 'N/A')}</code>
                        </div>
                    </div>
                    <span class="badge bg-${getStatusClass(log.estado)}">${getStatusText(log.estado)}</span>
                </div>
            </div>
        `).join('');
    }
    
    // Actualizar vista desktop
    const desktopView = document.querySelector('.d-none.d-md-block tbody');
    if (desktopView) {
        desktopView.innerHTML = logsData.map(log => `
            <tr>
                <td><small class="text-muted">${formatDate(log.fecha)}</small></td>
                <td>${escapeHtml(log.usuario || 'Usuario desconocido')}</td>
                <td>${escapeHtml(log.accion || 'Acción no especificada')}</td>
                <td><code>${escapeHtml(log.ip || 'N/A')}</code></td>
                <td><span class="badge bg-${getStatusClass(log.estado)}">${getStatusText(log.estado)}</span></td>
            </tr>
        `).join('');
    }
}

// Mostrar estado vacío
function showEmptyState() {
    // Vista móvil
    const mobileView = document.querySelector('.d-block.d-md-none .list-group');
    if (mobileView) {
        mobileView.innerHTML = `
            <div class="text-center py-4">
                <i class="bi bi-inbox text-muted fs-1 mb-3"></i>
                <div class="text-muted">No hay logs de seguridad disponibles</div>
            </div>
        `;
    }
    
    // Vista desktop
    const desktopView = document.querySelector('.d-none.d-md-block tbody');
    if (desktopView) {
        desktopView.innerHTML = `
            <tr>
                <td colspan="5" class="text-center py-4">
                    <i class="bi bi-inbox text-muted fs-1 mb-3"></i>
                    <div class="text-muted">No hay logs de seguridad disponibles</div>
                </td>
            </tr>
        `;
    }
}
*/

/* COMENTADO: Funciones de paginación y actualización de logs
// Actualizar paginación visual
function updatePagination() {
    // Actualizar estado de botones anterior/siguiente
    const prevBtn = document.getElementById('prevPage');
    const nextBtn = document.getElementById('nextPage');
    
    if (prevBtn) {
        if (currentPage <= 1) {
            prevBtn.classList.add('disabled');
            prevBtn.querySelector('a').onclick = null;
        } else {
            prevBtn.classList.remove('disabled');
            prevBtn.querySelector('a').onclick = () => changePage(currentPage - 1);
        }
    }
    
    if (nextBtn) {
        if (currentPage >= totalPages) {
            nextBtn.classList.add('disabled');
            nextBtn.querySelector('a').onclick = null;
        } else {
            nextBtn.classList.remove('disabled');
            nextBtn.querySelector('a').onclick = () => changePage(currentPage + 1);
        }
    }
    
    // Actualizar páginas numeradas
    for (let i = 1; i <= 3; i++) {
        const pageBtn = document.getElementById(`page${i}`);
        if (pageBtn) {
            if (i === currentPage) {
                pageBtn.classList.add('active');
            } else {
                pageBtn.classList.remove('active');
            }
        }
    }
}

// Actualizar información de registros
function updateRecordsInfo() {
    const recordsInfo = document.getElementById('recordsInfo');
    if (recordsInfo) {
        const start = ((currentPage - 1) * recordsPerPage) + 1;
        const end = Math.min(currentPage * recordsPerPage, totalRecords);
        recordsInfo.textContent = `Mostrando ${start}-${end} de ${totalRecords} registros`;
    }
}

// Función para actualizar logs
async function actualizarLogs() {
    const refreshIcon = document.getElementById('refreshIcon');
    
    if (!refreshIcon) return;
    
    // Animar icono
    refreshIcon.classList.add('spinning');
    refreshIcon.style.transform = 'rotate(360deg)';
    refreshIcon.style.transition = 'transform 0.5s ease';
    
    try {
        // Recargar datos desde la base de datos
        await loadLogsData();
        
        // Mostrar notificación de éxito
        showSuccessNotification('Logs actualizados correctamente');
    } catch (error) {
        // Mostrar notificación de error
        showErrorNotification('Error al actualizar logs: ' + error.message);
    } finally {
        // Detener animación
        refreshIcon.style.transform = 'rotate(0deg)';
        refreshIcon.classList.remove('spinning');
    }
}
*/

// Funciones helper
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function formatDate(dateString) {
    if (!dateString) return 'N/A';
    
    try {
        const date = new Date(dateString);
        return date.toLocaleString('es-ES', {
            year: 'numeric',
            month: '2-digit',
            day: '2-digit',
            hour: '2-digit',
            minute: '2-digit',
            second: '2-digit'
        });
    } catch (error) {
        return dateString;
    }
}

function getStatusClass(status) {
    const statusMap = {
        'success': 'success',
        'exitoso': 'success',
        'info': 'info',
        'warning': 'warning',
        'advertencia': 'warning',
        'error': 'danger',
        'danger': 'danger',
        'failed': 'danger',
        'fallido': 'danger'
    };
    
    return statusMap[status?.toLowerCase()] || 'secondary';
}

function getStatusText(status) {
    const statusTextMap = {
        'success': 'Exitoso',
        'exitoso': 'Exitoso',
        'info': 'Info',
        'warning': 'Advertencia',
        'advertencia': 'Advertencia',
        'error': 'Error',
        'danger': 'Error',
        'failed': 'Error',
        'fallido': 'Error'
    };
    
    return statusTextMap[status?.toLowerCase()] || status || 'Desconocido';
}

function showSuccessNotification(message) {
    showNotification(message, 'success', 'bi-check-circle');
}

function showErrorNotification(message) {
    showNotification(message, 'danger', 'bi-exclamation-triangle');
}

function showNotification(message, type, icon) {
    // Remover notificaciones existentes
    const existingNotifications = document.querySelectorAll('.custom-notification');
    existingNotifications.forEach(notif => notif.remove());
    
    // Crear nueva notificación
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type} alert-dismissible fade show position-fixed custom-notification`;
    alertDiv.style.cssText = 'top: 20px; right: 20px; z-index: 1050; max-width: 300px;';
    alertDiv.innerHTML = `
        <i class="bi ${icon} me-2"></i>
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    document.body.appendChild(alertDiv);
    
    // Auto-remover después de 3 segundos
    setTimeout(() => {
        if (alertDiv.parentNode) {
            alertDiv.remove();
        }
    }, 3000);
}

// Inicializar funciones de seguridad
function initSecurityFunctions() {
    // Configurar formulario de cambio de contraseña
    const passwordForm = document.getElementById('passwordForm');
    if (passwordForm) {
        passwordForm.addEventListener('submit', cambiarContrasena);
    }
    
    // Validar coincidencia de contraseñas
    const confirmarClave = document.getElementById('confirmar_clave');
    if (confirmarClave) {
        confirmarClave.addEventListener('input', checkPasswordMatch);
    }
    
    // Manejar cambio de usuario seleccionado
    const usuarioSelect = document.getElementById('usuario_id');
    if (usuarioSelect) {
        usuarioSelect.addEventListener('change', handleUsuarioChange);
    }
    
    // COMENTADO: Inicializar logs si existen elementos
    // if (document.getElementById('recordsInfo')) {
    //     loadLogsData(); // Cargar datos iniciales desde la base de datos
    // }
}
