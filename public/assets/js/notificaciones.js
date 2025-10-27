document.addEventListener('DOMContentLoaded', function () {
  // Resumen notificaciones
  function cargarResumenNotificaciones() {
    fetch(BASE_URL + 'notificaciones/notificaciones.php?ajax=1&resumen=1')
      .then(r => r.text())
      .then(html => {
        const resumen = document.getElementById('notificaciones-resumen');
        if (resumen) {
          resumen.innerHTML = html;
          // Agregar event listeners después de cargar el contenido
          agregarEventListenersNotificaciones();
        }
      })
      .catch(error => {
        console.error('Error al cargar notificaciones:', error);
      });
  }
  cargarResumenNotificaciones();

  // Marcar como leídas cuando se hace clic en una notificación específica
  function marcarNotificacionLeida(notificacionId) {
    if (!notificacionId) return;
    

    
    fetch(BASE_URL + 'notificaciones/marcar-notificacion-individual.php', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/x-www-form-urlencoded',
      },
      body: 'notificacion_id=' + encodeURIComponent(notificacionId)
    })
    .then(response => response.json())
    .then(data => {

      if (data.success) {
        // Forzar recarga de notificaciones
        notificacionesCargadas = false;
        cargarResumenNotificaciones();
        
        // Actualizar el contador de notificaciones
        actualizarContadorNotificaciones();
      } else {
        console.error('Error al marcar notificación:', data.error || 'Error desconocido');
      }
    })
    .catch(error => {
      console.error('Error al marcar notificación como leída:', error);
    });
  }

  // Función para eliminar una notificación
  function eliminarNotificacion(notificacionId) {
    if (!notificacionId) return;
    
    fetch(BASE_URL + 'notificaciones/eliminar-notificacion.php', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/x-www-form-urlencoded',
      },
      body: 'notificacion_id=' + encodeURIComponent(notificacionId)
    })
    .then(response => response.json())
    .then(data => {
      if (data.success) {
        // Remover elemento del DOM
        const notificationElement = document.querySelector('[data-notification-id="' + notificacionId + '"]');
        if (notificationElement) {
          notificationElement.remove();
        }
        
        // Recargar resumen y contador
        notificacionesCargadas = false;
        cargarResumenNotificaciones();
        actualizarContadorNotificaciones();
      }
    })
    .catch(error => {
      console.error('Error al eliminar notificación:', error);
    });
  }

  // Función para actualizar el contador de notificaciones no leídas
  function actualizarContadorNotificaciones() {
    fetch(BASE_URL + 'notificaciones/contador-notificaciones.php')
      .then(r => r.json())
      .then(data => {
        const badge = document.getElementById('notificaciones-count');
        if (data.count > 0) {
          if (badge) {
            badge.textContent = data.count;
          } else {
            // Crear el badge si no existe
            const bell = document.getElementById('notificationsDropdown');
            if (bell) {
              const newBadge = document.createElement('span');
              newBadge.id = 'notificaciones-count';
              newBadge.className = 'badge bg-danger rounded-pill position-absolute top-0 start-100 translate-middle';
              newBadge.textContent = data.count;
              bell.appendChild(newBadge);
            }
          }
        } else {
          if (badge) badge.remove();
        }
      })
      .catch(error => {
        console.error('Error al actualizar contador:', error);
      });
  }

  // Event listener para el dropdown de notificaciones
  const bell = document.getElementById('notificationsDropdown');
  if (bell) {
    // No hacer nada especial al hacer clic en el dropdown
    // Las notificaciones se marcan individualmente cuando se hace clic en ellas
  }

  // Ver todas
  const verTodas = document.getElementById('ver-todas-notificaciones');
  if (verTodas) {
    verTodas.addEventListener('click', function (e) {
      e.preventDefault();
      const modal = new bootstrap.Modal(document.getElementById('notificacionesModal'));
      modal.show();
      fetch(BASE_URL +'notificaciones/notificaciones.php?ajax=1&all=1')
        .then(r => r.text())
        .then(html => {
          document.getElementById('notificacionesModalBody').innerHTML = html;
        });
    });
  }

  // Cargar notificaciones nuevas por AJAX al abrir el dropdown
  var notificacionesCargadas = false;
  $('#notificationsDropdown').on('show.bs.dropdown', function () {
    if (notificacionesCargadas) return;
    var $resumen = $('#notificaciones-resumen');
    $resumen.html('<div class="text-center text-muted small">Cargando...</div>');
    $.get(BASE_URL + 'notificaciones/notificaciones.php?ajax=1', function(data) {
      $resumen.html(data);
      notificacionesCargadas = true;
      
      // Agregar event listeners para notificaciones individuales
      agregarEventListenersNotificaciones();
    }).fail(function() {
      $resumen.html('<div class="text-danger text-center small">Error al cargar notificaciones</div>');
    });
  });

  // Función para agregar event listeners a las notificaciones
  function agregarEventListenersNotificaciones() {
    // Esta función ahora solo se usa para marcar como leídas en el click de la notificación
    // La eliminación ya se maneja por delegación de eventos global
    
    document.querySelectorAll('[data-notification-id]').forEach(notificationElement => {
      notificationElement.addEventListener('click', function(e) {
        // Evitar marcar como leída si se hace clic en botones de acción
        if (e.target.classList.contains('btn-close') || e.target.closest('.btn-eliminar-notif')) {
          return;
        }
        
        const notificationId = this.getAttribute('data-notification-id');
        
        if (notificationId) {
          // Marcar como leída siempre que se haga clic en la notificación
          marcarNotificacionLeida(notificationId);
          
          // Remover el badge "Nuevo" si existe para feedback visual inmediato
          const nuevoBadge = this.querySelector('.badge-nuevo');
          if (nuevoBadge) {
            nuevoBadge.remove();
          }
          
          // Si hay un enlace, permitir que funcione normalmente
          const link = this.querySelector('a');
          if (link && e.target.closest('a')) {
            return true;
          }
        }
      });
    });
  }

  // Actualizar contador inicial
  actualizarContadorNotificaciones();

  // Delegación de eventos para botones de eliminar (funciona incluso si se cargan dinámicamente)
  document.addEventListener('click', function(e) {
    if (e.target.closest('.btn-eliminar-notif-admin') || e.target.closest('.btn-eliminar-notif')) {
      e.preventDefault();
      e.stopPropagation();
      
      // Obtener el ID de la notificación
      let notificationElement = e.target.closest('[data-notification-id]');
      if (!notificationElement) {
        notificationElement = e.target.closest('.btn-eliminar-notif-admin')?.closest('[data-notification-id]');
      }
      
      const notifId = notificationElement?.getAttribute('data-notification-id') || e.target.closest('.btn-eliminar-notif-admin')?.getAttribute('data-notification-id');
      
      if (notifId) {
        eliminarNotificacion(notifId);
      }
    }
  }, true);
});
