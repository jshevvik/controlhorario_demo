/**
 * Sistema de Gestión de Solicitudes de Empleados
 * 
 * Este módulo maneja las solicitudes de tiempo libre, permisos, bajas médicas,
 * horas extra y ausencias de los empleados. Incluye funcionalidades para:
 * - Selección de tipos de solicitud
 * - Selector de rangos de fechas
 * - Validación de formularios
 * - Visualización de calendario
 * - Gestión de saldos disponibles
 * - Historial de solicitudes
 * 
 * @author Iuliia Shevchenko
 * @version 1.0
 */
(function () {
  'use strict';

  // ELEMENTOS DOM Y CONFIGURACIÓN INICIAL


  /**
   * Elementos principales del formulario de solicitudes
   */
  const form              = document.getElementById('tramiteForm');        // Formulario principal
  const tipoSelect        = document.getElementById('tramiteType');        // Selector de tipo de solicitud
  const dateInput         = document.getElementById('daterange');          // Input de rango de fechas
  const halfDaySwitch     = document.getElementById('halfDaySwitch') || null;      // Switch para medio día
  const extraHoursGroup   = document.getElementById('extraHoursGroup') || null;    // Grupo de horas extra
  const extraHoursInput   = document.getElementById('extraHours') || null;         // Input de horas extra
  const extraDateInput    = document.getElementById('extraDate') || null;          // Input de fecha para horas extra
  const extraStartTimeInput = document.getElementById('extraStartTime') || null;   // Input de hora inicio horas extra
  const extraEndTimeInput = document.getElementById('extraEndTime') || null;       // Input de hora fin horas extra
  const periodoGroup      = document.getElementById('periodoGroup') || null;       // Grupo de periodo de fechas
  const halfDayGroup      = document.getElementById('halfDayGroup') || null;       // Grupo de medio día
  const absenceTypeGroup  = document.getElementById('absenceTypeGroup') || null;   // Grupo de tipo de ausencia
  const absenceTypeInput  = document.getElementById('absenceType') || null;        // Input de tipo de ausencia
  const summaryDaysEl     = document.getElementById('summaryDays') || null;        // Resumen de días/horas
  const confirmModalEl    = document.getElementById('confirmModal') || null;       // Modal de confirmación
  const confirmBtn        = document.getElementById('confirmSubmit') || null;      // Botón de confirmación

  /**
   * Elementos de widgets de la barra lateral
   * Muestran los saldos disponibles para cada tipo de solicitud
   */
  const balEls   = {
    vacaciones: document.getElementById('bal-vac') || null,    // Saldo de vacaciones
    permiso:    document.getElementById('bal-perm') || null,   // Saldo de permisos
    baja:       document.getElementById('bal-baja') || null,   // Saldo de bajas médicas
    extra:      document.getElementById('bal-extra') || null,  // Saldo de horas extra
    ausencia:   document.getElementById('bal-aus') || null     // Saldo de ausencias
  };
  const holidaysListEl = document.getElementById('holidays-list') || null;    // Lista de días festivos
  const historyEl      = document.getElementById('request-history') || null;  // Historial de solicitudes

  /**
   * Variables globales para gestión de estado
   */
  let rangoSeleccionado = null;  // Almacena el rango de fechas seleccionado por el usuario
  let calendar;                   // Instancia del calendario FullCalendar
  let saldosActuales = {};        // Almacena los saldos actuales obtenidos del servidor

  /**
   * Verificación de elementos DOM requeridos
   * Valida que todos los elementos necesarios existan antes de continuar
   */
  function verificarElementosDOM() {
    // Solo verificar elementos que son críticos y siempre deberían estar en index.php
    // Otros elementos son opcionales dependiendo de la página
    const elementosCriticos = {
      form,
      tipoSelect,
      confirmModalEl
    };
    
    const faltantes = Object.entries(elementosCriticos)
      .filter(([nombre, elemento]) => !elemento)
      .map(([nombre]) => nombre);
    
    if (faltantes.length > 0) {
      // Solo retornar false si faltan elementos realmente críticos
      return false;
    }
    return true;
  }


  // FUNCIONES DE INICIALIZACIÓN


  /**
   * Función principal de inicialización
   * Se ejecuta cuando el DOM está completamente cargado
   */
  document.addEventListener('DOMContentLoaded', () => {
    // Solo inicializar si estamos en la página principal (dashboard)
    const enDashboard = window.location.pathname.includes('index.php') || 
                        window.location.pathname.endsWith('/public/') ||
                        window.location.pathname.endsWith('/controlhorario/');
    
    // Verificar que todos los elementos DOM necesarios existan
    if (!verificarElementosDOM()) {
      if (enDashboard) {
        console.error('No se puede inicializar: elementos DOM faltantes');
      }
      return;
    }

    // Verificar dependencias externas
    if (!verificarDependencias()) {
      console.error('No se puede inicializar: dependencias faltantes');
      return;
    }

    try {
      initData().then(() => {


      }).catch(err => {
        console.error('Error cargando datos iniciales:', err);
      });
      initRango();        // Inicializar selector de rango de fechas
      initCalendar();     // Inicializar calendario
      
      // Inicializar filtros del calendario después de un pequeño retraso
      setTimeout(() => {
        initCalendarFilters();
      }, 100);

      // Configurar event listeners
      tipoSelect.addEventListener('change', changeType);            // Cambio de tipo de solicitud
      form.addEventListener('submit', validarConfirmar);            // Validación del formulario
      confirmBtn.addEventListener('click', enviar);                 // Confirmación de envío
      halfDaySwitch?.addEventListener('change', refrescarResumen);  // Cambio en switch de medio día
      extraHoursInput?.addEventListener('input', refrescarResumen); // Cambio en horas extra
      extraStartTimeInput?.addEventListener('input', calcularHorasExtra); // Cambio en hora inicio
      extraEndTimeInput?.addEventListener('input', calcularHorasExtra);   // Cambio en hora fin

      console.error('Sistema de solicitudes inicializado correctamente');
    } catch (error) {
      console.error('Error durante la inicialización:', error);
    }
  });

  /**
   * Verifica que las dependencias externas estén disponibles
   * @returns {boolean} true si las dependencias críticas están disponibles
   */
  function verificarDependencias() {
    const dependencias = {
      'jQuery': typeof $ !== 'undefined',
      'Moment.js': typeof moment !== 'undefined',
      'FullCalendar': typeof FullCalendar !== 'undefined',
      'Bootstrap': typeof bootstrap !== 'undefined',
      'Toastr': typeof toastr !== 'undefined'
    };

    const faltantes = Object.entries(dependencias)
      .filter(([nombre, disponible]) => !disponible)
      .map(([nombre]) => nombre);

    if (faltantes.length > 0) {
      console.warn('Dependencias faltantes (no críticas):', faltantes);
      
      // Solo jQuery, Moment.js y FullCalendar son críticas para el funcionamiento básico
      const criticas = ['jQuery', 'Moment.js', 'FullCalendar'];
      const faltantesCriticas = faltantes.filter(dep => criticas.includes(dep));
      
      if (faltantesCriticas.length > 0) {
        console.error('Dependencias críticas faltantes:', faltantesCriticas);
        return false;
      }
    }
    return true;
  }

  /**
   * Carga los datos iniciales de la aplicación
   * Incluye saldos disponibles, días festivos e historial de solicitudes
   * 
   * @description Esta función está comentada, pero está diseñada para hacer
   *              una llamada AJAX al backend para obtener datos iniciales
   */
  function initData() {


    return fetch(BASE_URL + 'solicitudes.php?action=init')
      .then(r => {
        return r.json();
      })
      .then(datos => {


        
        // Guardar saldos actuales para validación
        saldosActuales = datos.balances || {};
        
        // Saldos
        Object.entries(datos.balances || {}).forEach(([tipo, val]) => {
          const el = balEls[tipo];
          if (!el) return;

          let texto;
          if (val && typeof val === 'object' && 'usado' in val && 'max' in val) {
            // Vacaciones y Permisos vienen como { usado, max }
            const used      = Number(val.usado);
            const maximum   = Number(val.max);
            const remaining = maximum - used;
            const unidad    = tipo === 'extra' ? ' h.' : ' d.';
            texto = `${remaining}/${maximum}` + unidad;
          } else {
            // Baja, Extra, Ausencia vienen como número (usado)
            const unidad = tipo === 'extra' ? ' h.' : ' d.';
            texto = `${val}` + unidad;
          }

          el.textContent = texto;
        });

        // Festivos
        if (holidaysListEl) {
          holidaysListEl.innerHTML = (datos.holidays || [])
            .map(f => `<li class="mb-1"><i class="ti ti-gift text-primary me-2"></i>${f.nombre || f.name} – ${moment(f.fecha || f.date).format('DD/MM/YYYY')}</li>`)
            .join('');
        }
        // Historial
        if (historyEl) {
          const historyData = datos.history || [];
          if (historyData.length > 0) {
            const historialHTML = historyData
              .map(renderHistItem)
              .join('');
            historyEl.innerHTML = historialHTML;
          } else {
            historyEl.innerHTML = `<li class="list-group-item text-center text-muted py-3">
              No hay solicitudes registradas.
            </li>`;
          }
        }

        // Actualizar eventos del calendario si se proporcionan
        if (datos.eventos) {


          // Actualizar la variable global de eventos
          window.eventosPHP = datos.eventos;
        }
        
        // Actualizar festivos del calendario si se proporcionan
        if (datos.festivosCalendario) {


          // Actualizar la variable global de festivos para el calendario
          window.festivosPHP = datos.festivosCalendario;
        }
        
        return datos; // Devolver los datos para la cadena de promesas
      })
      .catch(err => {
        console.error('init error', err);
        throw err; // Re-lanzar el error para que lo maneje el llamador
      });
  }

  // FUNCIONES DE UTILIDAD PARA RENDERIZADO
 

  /**
   * Renderiza un elemento del historial de solicitudes
   * @param {Object} item - Objeto con los datos de la solicitud
   * @param {string} item.tipo - Tipo de solicitud (vacaciones, permiso, etc.)
   * @param {string} item.estado - Estado de la solicitud (aprobado, pendiente, rechazado)
   * @param {string} item.start_date - Fecha de inicio
   * @param {string} item.end_date - Fecha de fin
   * @param {number} item.horas - Número de horas (solo para tipo 'extra')
   * @returns {string} HTML del elemento del historial
   */
  function renderHistItem(item) {
    const tipoText = {
      'vacaciones': 'Vacaciones',
      'permiso': 'Permiso', 
      'baja': 'Baja médica',
      'extra': 'Horas extra',
      'ausencia': 'Ausencia'
    }[item.tipo] || item.tipo.charAt(0).toUpperCase() + item.tipo.slice(1);
    
    const estadoClass = {
      'aprobado': 'success',
      'pendiente': 'warning', 
      'rechazado': 'danger'
    }[item.estado] || 'secondary';
    
    const icons = {
      'vacaciones': 'bi-umbrella-fill',
      'permiso': 'bi-clock-history',
      'baja': 'bi-thermometer-half', 
      'extra': 'bi-alarm',
      'ausencia': 'bi-person-dash'
    };
    
    const fecha = item.tipo === 'extra'
      ? (item.horas && item.horas > 0 ? `${item.horas} h.` + ((item.hora_inicio && item.hora_fin) ? 
          ` (${item.hora_inicio.substring(0,5)}-${item.hora_fin.substring(0,5)})` : '') : '-')
      : `${moment(item.fecha_inicio).format('DD/MM')} – ${moment(item.fecha_fin).format('DD/MM')}`;

    return `<li class="list-group-item d-flex align-items-center py-2 py-md-3 px-3 px-md-4">
              <div class="me-2 me-md-3 fs-5 fs-md-4 text-${estadoClass}">
                <i class="bi ${icons[item.tipo]}"></i>
              </div>
              <div class="flex-grow-1">
                <div class="fw-semibold">${tipoText}</div>
                <small class="text-date">${fecha}</small>
              </div>
              <div>
                <span class="badge bg-${estadoClass}">
                  ${item.estado.charAt(0).toUpperCase() + item.estado.slice(1)}
                </span>
              </div>
            </li>`;
  }

  /**
   * Formatea el rango de fechas o las horas para mostrar en el historial
   * @param {Object} it - Objeto con los datos de la solicitud
   * @returns {string} Texto formateado del rango o horas
   */
  function fechaRango(it){
    if(it.tipo==='extra') return (it.horas && it.horas > 0 ? `${it.horas} h.` : '-');
    const startDate = it.fecha_inicio || it.start_date;
    const endDate = it.fecha_fin || it.end_date;
    return `${moment(startDate).format('DD/MM')}–${moment(endDate).format('DD/MM')}`;
  }

  // DÍAS OCUPADOS POR EVENTOS APROBADOS
  function getDiasOcupados(eventosSolicitudes) {
      const ocupados = new Set();
      eventosSolicitudes.forEach(ev => {
          if (ev.estado === 'aprobado' && ['vacaciones', 'permiso', 'baja', 'ausencia'].includes(ev.tipo)) {
              const start = moment(ev.start);
              const end = moment(ev.end);
              let day = start.clone();
              while (day.isSameOrBefore(end)) {
                  ocupados.add(day.format('DD/MM/YYYY'));
                  day.add(1, 'day');
              }
          }
      });
      return ocupados;
  }

  /**
   * Convierte el código del tipo de solicitud a texto legible
   * @param {string} t - Código del tipo de solicitud
   * @returns {string} Texto descriptivo del tipo
   */
  const tituloTipo = t => ({vacaciones:'Vacaciones',permiso:'Permiso',baja:'Baja',extra:'Horas extra',ausencia:'Ausencia'})[t]||t;
  
  /**
   * Convierte el código del estado a texto legible
   * @param {string} s - Código del estado
   * @returns {string} Texto descriptivo del estado
   */
  const estadoTexto = s => ({aprobado:'Aprobado',pendiente:'Pendiente',rechazado:'Rechazado'})[s]||s;

  /**
   * Crea un evento de calendario para una nueva solicitud
   * @param {string} tipo - Tipo de solicitud
   * @param {FormData} formData - Datos del formulario
   * @returns {Object|null} Evento para el calendario o null si hay error
   */
  function crearEventoSolicitud(tipo, formData) {
    try {
      const tipoLabels = {
        vacaciones: 'Vacaciones',
        permiso: 'Permiso',
        baja: 'Baja médica',
        extra: 'Horas extra',
        ausencia: 'Ausencia'
      };

      const tipoColors = {
        vacaciones: '#007bff',
        permiso: '#167eb5',
        baja: '#4bd08b',
        extra: '#f8c076',
        ausencia: '#fb977d'
      };

      // Color para estado pendiente (morado)
      const colorPendiente = '#6e42c1c2';
      
      if (tipo === 'extra') {
        // Para horas extra
        const horas = parseFloat(extraHoursInput.value || 0);
        const fecha = extraDateInput.value;
        
        if (!fecha) return null;
        
        // Convertir fecha DD/MM/YYYY a YYYY-MM-DD
        const fechaParts = fecha.split('/');
        if (fechaParts.length !== 3) return null;
        
        const fechaSQL = `${fechaParts[2]}-${fechaParts[1].padStart(2, '0')}-${fechaParts[0].padStart(2, '0')}`;
        
        let startDateTime = fechaSQL;
        let endDateTime = fechaSQL;
        let isAllDay = true;
        
        // Si hay horarios específicos
        if (extraStartTimeInput.value && extraEndTimeInput.value) {
          startDateTime = `${fechaSQL}T${extraStartTimeInput.value}`;
          endDateTime = `${fechaSQL}T${extraEndTimeInput.value}`;
          isAllDay = false;
        }
        
        return {
          title: `${tipoLabels[tipo]} (${horas}h)`,
          start: startDateTime,
          end: endDateTime,
          allDay: isAllDay,
          backgroundColor: colorPendiente,
          borderColor: colorPendiente,
          textColor: '#fff',
          extendedProps: {
            tipo: tipo,
            estado: 'pendiente',
            esSolicitud: true,
            horas: horas,
            hora_inicio: extraStartTimeInput.value,
            hora_fin: extraEndTimeInput.value
          }
        };
      } else {
        // Para otros tipos de solicitudes
        if (!rangoSeleccionado) return null;
        
        const eventos = [];
        let day = rangoSeleccionado.start.clone();
        
        while (day.isSameOrBefore(rangoSeleccionado.end)) {
          eventos.push({
            title: `${tipoLabels[tipo]} (Pendiente)`,
            start: day.format('YYYY-MM-DD'),
            end: day.clone().add(1, 'day').format('YYYY-MM-DD'),
            allDay: true,
            backgroundColor: colorPendiente,
            borderColor: colorPendiente,
            textColor: '#fff',
            extendedProps: {
              tipo: tipo,
              estado: 'pendiente',
              esSolicitud: true,
              medio_dia: halfDaySwitch.checked
            }
          });
          day.add(1, 'day');
        }
        
        return eventos;
      }
    } catch (error) {
      console.error('Error creando evento de solicitud:', error);
      return null;
    }
  }


  // CONFIGURACIÓN DEL SELECTOR DE RANGO DE FECHAS

  /**
   * Función para determinar si una fecha es válida para selección
   * Solo permite días laborables (lunes a viernes) y excluye festivos
   */
  function isValidDateForSelection(date, tipo) {
    // Aplicar restricciones a vacaciones y ausencias
    if (tipo !== 'vacaciones' && tipo !== 'ausencia') return true; 
    
    const momentDate = moment(date);
    
    // Excluir fines de semana (sábado = 6, domingo = 0)
    const dayOfWeek = momentDate.day();
    if (dayOfWeek === 0 || dayOfWeek === 6) return false;
    
    // Excluir festivos
    const fechaStr = momentDate.format('YYYY-MM-DD');
    const festivos = window.festivosPHP || [];
    return !festivos.some(festivo => festivo.fecha === fechaStr);
  }

  /**
   * Inicializa el componente de selector de rango de fechas
   * Utiliza la librería daterangepicker con configuración en español
   */
  function initRango() {
    if (typeof $ === 'undefined') return;
    
    // Inicializar daterangepicker para el rango principal
    $(dateInput).daterangepicker({
      locale: {
        format: 'DD/MM/YYYY',
        applyLabel: 'Aplicar',
        cancelLabel: 'Cancelar',
        fromLabel: 'De',
        toLabel: 'A',
        customRangeLabel: 'Personalizado',
        weekLabel: 'S',
        daysOfWeek: ['Lun','Mar','Mié','Jue','Vie','Sáb','Dom'],
        monthNames: ['Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'],
        firstDay: 1
      },
      autoUpdateInput: false,
      opens: 'center',
      minDate: moment(),
      isInvalidDate: function(date) {
        const tipoActual = tipoSelect ? tipoSelect.value : '';
        return !isValidDateForSelection(date, tipoActual);
      }
    }, (start, end) => {
      rangoSeleccionado = { start, end };
      dateInput.value = `${start.format('DD/MM/YYYY')} – ${end.format('DD/MM/YYYY')}`;
      refrescarResumen();
    });

    // Inicializar daterangepicker para fecha de horas extra
    if (extraDateInput) {
      $(extraDateInput).daterangepicker({
        locale: {
          format: 'DD/MM/YYYY',
          applyLabel: 'Aplicar',
          cancelLabel: 'Cancelar',
          daysOfWeek: ['Lun','Mar','Mié','Jue','Vie','Sáb','Dom'],
          monthNames: ['Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'],
          firstDay: 1
        },
        singleDatePicker: true,
        autoUpdateInput: false,
        opens: 'center',
        minDate: moment()
      }, (start) => {
        extraDateInput.value = start.format('DD/MM/YYYY');
        refrescarResumen();
      });
    }
  }

  /**
   * Calcula el número de días laborables (lunes-viernes) excluyendo festivos
   * entre dos fechas de moment.js
   */
  function contarDiasLaborables(fechaInicio, fechaFin) {
    let diasLaborables = 0;
    const festivos = window.festivosPHP || [];
    
    // Clonar la fecha de inicio para no modificar la original
    let fecha = fechaInicio.clone();
    
    // Iterar desde la fecha de inicio hasta la de fin (inclusive)
    while (fecha.isBefore(fechaFin) || fecha.isSame(fechaFin, 'day')) {
      const dayOfWeek = fecha.day(); // 0 = domingo, 1 = lunes, 6 = sábado
      const fechaStr = fecha.format('YYYY-MM-DD');
      
      // Contar si es día laborable (lunes=1 a viernes=5) y no es festivo
      if (dayOfWeek >= 1 && dayOfWeek <= 5) {
        const esFestivo = festivos.some(f => f.fecha === fechaStr);
        if (!esFestivo) {
          diasLaborables++;
        }
      }
      
      // Avanzar al siguiente día
      fecha.add(1, 'day');
    }
    
    return diasLaborables;
  }

  /**
   * Actualiza el resumen de días/horas según el tipo de solicitud seleccionado
   * Calcula automáticamente los días del rango o muestra las horas extra
   */
  function refrescarResumen() {
    const tipo = tipoSelect.value;
    let texto = '';
    if (tipo === 'extra') {
      const horas = parseFloat(extraHoursInput.value || 0);
      const fecha = extraDateInput ? extraDateInput.value : '';
      texto = `${horas} h.${fecha ? ' el ' + fecha : ''}`;
    } else if (rangoSeleccionado) {
      // Para vacaciones y ausencias, contar solo días laborables
      const diasLaborables = (tipo === 'vacaciones' || tipo === 'ausencia') 
        ? contarDiasLaborables(rangoSeleccionado.start, rangoSeleccionado.end)
        : rangoSeleccionado.end.diff(rangoSeleccionado.start, 'days') + 1;
      
      texto = diasLaborables + (halfDaySwitch.checked ? ' medio día' : ' días');
    }
    summaryDaysEl.textContent = texto;
  }

  /**
   * Calcula automáticamente las horas extra basándose en la hora de inicio y fin
   * Actualiza el campo de total de horas y refresca el resumen
   */
  function calcularHorasExtra() {
    if (!extraStartTimeInput || !extraEndTimeInput || !extraHoursInput) {
      return;
    }

    const horaInicio = extraStartTimeInput.value;
    const horaFin = extraEndTimeInput.value;

    if (horaInicio && horaFin) {
      // Crear objetos Date para poder calcular la diferencia
      const inicio = new Date(`2000-01-01T${horaInicio}:00`);
      const fin = new Date(`2000-01-01T${horaFin}:00`);

      // Si la hora de fin es menor que la de inicio, asumimos que cruza medianoche
      if (fin < inicio) {
        fin.setDate(fin.getDate() + 1);
      }

      // Calcular diferencia en horas
      const diferenciaMs = fin - inicio;
      const horas = diferenciaMs / (1000 * 60 * 60); // Convertir de ms a horas

      // Actualizar el campo de horas con 2 decimales
      extraHoursInput.value = horas.toFixed(2);
      
      // Refrescar el resumen
      refrescarResumen();
    } else {
      // Si no hay ambas horas, limpiar el campo de total
      extraHoursInput.value = '';
      refrescarResumen();
    }
  }

  // Event listener para actualizar resumen cuando cambian las horas extra
  // NOTA: Este event listener se configura en la función de inicialización principal


  // CONFIGURACIÓN DEL CALENDARIO FULLCALENDAR


  /**
   * Calcula las opciones del calendario según el tamaño de pantalla
   * @returns {Object} Objeto con height e initialView según viewport
   */
  function calcOptions(){
    if (window.innerWidth < 768)  return {height:'auto', initialView:'listWeek'}; 
    if (window.innerWidth < 992)  return {height:'auto', initialView:'dayGridMonth'}; 
    return {height:'auto', initialView:'dayGridMonth'};                             
  }


  function expandirEventoPorDias(ev) {
  const start = moment(ev.start    || ev.fecha_inicio, 'YYYY-MM-DD');
  const end   = moment(ev.end      || ev.fecha_fin,    'YYYY-MM-DD');
  const events = [];
  let day = start.clone();
  while (day.isSameOrBefore(end)) {
    events.push({
      ...ev,
      start: day.format('YYYY-MM-DD'),
      end:   day.clone().add(1, 'day').format('YYYY-MM-DD'),
    });
    day.add(1, 'day');
  }
  return events;
}


  function initCalendar() {
    const el = document.getElementById('leave-calendar');
    if (!el) return;
    if (typeof FullCalendar === 'undefined') return;

    const opt = calcOptions();

    const tipoColors = {
        vacaciones: '#007bff',  // primary (azul)
        permiso:    '#167eb5',  // info (azul personalizado)
        baja:       '#4bd08b',  // success (verde personalizado)
        extra:      '#f8c076',  // warning (amarillo personalizado)
        ausencia:   '#fb977d'   // danger (rojo personalizado)
    };

    function getEventColor(tipo, estado) {
        if (estado === 'rechazado') return '#e93737a5';  // rosa personalizado
        if (estado === 'pendiente') return '#6e42c1c2';  // morado personalizado
        return tipoColors[tipo] || '#6c757d';
    }

    const tipoLabels = {
        vacaciones: 'Vacaciones',
        permiso:    'Permiso',
        baja:       'Baja médica',
        extra:      'Horas extra',
        ausencia:   'Ausencia'
    };
    const estadoLabels = {
        aprobado: 'Aprobado',
        pendiente: 'Pendiente',
        rechazado: 'Rechazado'
    };

    //   Procesar eventos de window.eventosPHP separando solicitudes, festivos y eventos de calendario  
    let eventosSolicitudes = [];
    (window.eventosPHP || []).forEach(ev => {
        const tipo = ev.tipo || ev.extendedProps?.tipo || 'general';
        const estado = ev.estado || ev.extendedProps?.estado || 'pendiente';
        const categoria = ev.categoria || 'general';

        // SOLICITUDES DEL EMPLEADO (vacaciones, permiso, baja, extra, ausencia)
        if (['vacaciones', 'permiso', 'baja', 'extra', 'ausencia'].includes(tipo)) {
            if (tipo === 'extra') {
                // Horas extra: evento con horario específico
                const fechaHora = ev.start || ev.fecha_inicio;
                if (fechaHora) {
                    // Determinar hora de inicio y fin
                    let startDateTime = fechaHora;
                    let endDateTime = fechaHora;
                    let isAllDay = true;
                    
                    // Si hay hora_inicio y hora_fin, usar horario específico
                    if (ev.hora_inicio && ev.hora_fin) {
                        // Construir fecha y hora completas
                        const fecha = moment(fechaHora).format('YYYY-MM-DD');
                        startDateTime = `${fecha}T${ev.hora_inicio}`;
                        endDateTime = `${fecha}T${ev.hora_fin}`;
                        isAllDay = false;
                    }
                    
                    eventosSolicitudes.push({
                        ...ev,
                        title: `${tipoLabels[tipo] || tipo} (${ev.horas || 0}h) - ${estadoLabels[estado] || estado}`,
                        start: startDateTime,
                        end: endDateTime,
                        allDay: isAllDay,
                        backgroundColor: getEventColor(tipo, estado),
                        borderColor: getEventColor(tipo, estado),
                        textColor: '#fff',
                        extendedProps: { tipo, estado, esSolicitud: true, ...ev.extendedProps, ...ev }
                    });
                }
            } else {
                // Vacaciones, permisos, bajas y ausencias - expandir por días
                eventosSolicitudes.push(...expandirEventoPorDias({
                    ...ev,
                    title: `${tipoLabels[tipo] || tipo} - ${estadoLabels[estado] || estado}`,
                    backgroundColor: getEventColor(tipo, estado),
                    borderColor: getEventColor(tipo, estado),
                    textColor: '#fff',
                    extendedProps: { tipo, estado, esSolicitud: true, ...ev.extendedProps, ...ev }
                }));
            }
        }
        // FESTIVOS (ya se procesan por separado desde window.festivosPHP, los ignoramos aquí)
        else if (tipo === 'festivo' || categoria === 'festivo') {
            // No procesamos festivos aquí, se procesan desde window.festivosPHP
            return;
        }
        // EVENTOS DE CALENDARIO (eventos normales creados desde configuración)
        else if (tipo === 'evento' || categoria === 'evento') {
            eventosSolicitudes.push({
                ...ev,
                title: ev.title || ev.titulo || ev.nombre || 'Evento',
                backgroundColor: ev.color || '#007bff',
                borderColor: ev.color || '#007bff',
                textColor: '#fff',
                extendedProps: { 
                    tipo: 'evento_calendario', 
                    esEvento: true, 
                    descripcion: ev.descripcion,
                    para_todos: ev.para_todos,
                    ...ev.extendedProps, 
                    ...ev 
                }
            });
        }
    });

    //   Calcula los días ocupados SOLO después de expandir  
    const diasOcupados = new Set();
    eventosSolicitudes.forEach(ev => {
        const props = ev.extendedProps || {};
        if (
            props.estado === 'aprobado' &&
            ['vacaciones', 'permiso', 'baja', 'ausencia'].includes(props.tipo)
        ) {
            // Evita añadir duplicados, solo días exactos ocupados
            diasOcupados.add(ev.start);
        }
    });

    //   Festivos (primero para usar en filtrado)  
    const eventosFestivos = (window.festivosPHP || []).map(festivo => ({
        title: festivo.nombre,
        start: festivo.fecha,
        allDay: true,
        backgroundColor: '#cc0014ff',  // rojo
        borderColor: '#cc0014ff',      // rojo
        textColor: '#fff',
        extendedProps: { 
            esFestivo: true,
            alcance: festivo.alcance,
            region: festivo.region
        }
    }));

    // Crear set de fechas festivas para filtrado rápido
    const fechasFestivas = new Set(eventosFestivos.map(f => f.start));

    //   Horario laboral filtrado (excluir festivos)  
    const backgroundBarColor = '#b5d5f683';
    const eventosHorarios = (window.horariosEmpleado || []).map(ev => ({
        ...ev,
        title: 'Horario laboral',
        backgroundColor: backgroundBarColor,
        borderColor: backgroundBarColor,
        textColor: '#444',
        extendedProps: { ...(ev.extendedProps||{}), esHorario: true }
    })).filter(horarioEvent => {
        // Filtrar horarios laborales en días festivos
        // Generar fechas para verificar contra festivos
        if (horarioEvent.daysOfWeek) {
            // Para eventos recurrentes, verificamos si el día de la semana coincide con algún festivo
            return true; 
        }
        return true;
    });

    //   Junta todo y ordena eventos  
    const eventos = [...eventosSolicitudes, ...eventosHorarios, ...eventosFestivos];
    
    // Ordenar eventos, especialmente horas extra por horario
    eventos.sort((a, b) => {
        const aProps = a.extendedProps || {};
        const bProps = b.extendedProps || {};
        
        // Si ambos son horas extra, ordenar por hora de inicio
        if (aProps.tipo === 'extra' && bProps.tipo === 'extra') {
            const aStart = moment(a.start);
            const bStart = moment(b.start);
            return aStart.diff(bStart);
        }
        
        // Para otros casos, mantener orden natural
        return 0;
    });

    calendar = new FullCalendar.Calendar(el, {
        themeSystem: 'bootstrap5',
        locale: 'es',
        firstDay: 1,
        height: opt.height,
        // arrancar en listWeek si es pantallas <768
        initialView: window.innerWidth < 768 ? 'listWeek' : 'dayGridMonth',
        headerToolbar: window.innerWidth < 768
          ? { left: 'prev,next', center: 'title', right: 'dayGridMonth,timeGridWeek,timeGridDay,listWeek' }
          : { left: 'prev today next', center: 'title', right: 'dayGridMonth,timeGridWeek,timeGridDay,listWeek' },

        // botón con texto en español
        buttonText: {
          today: 'Hoy',
          month: 'Mes',
          week: 'Semana',
          day: 'Día',
          list: 'Lista',
        },
        // Configuración específica para vistas de tiempo
        slotMinTime: '06:00:00',
        slotMaxTime: '22:00:00',
        slotDuration: '01:00:00',
        slotLabelInterval: '01:00:00',
        slotLabelFormat: {
            hour: 'numeric',
            minute: '2-digit',
            meridiem: false
        },
        // Configuración para vista de lista
        listDayFormat: {
            weekday: 'long',
            day: 'numeric',
            month: 'long'
        },
        // Eventos ordenados por tiempo
        eventOrder: function(a, b) {
            const aProps = a.extendedProps || {};
            const bProps = b.extendedProps || {};
            
            // Prioridad 1: Horarios laborales al fondo
            if (aProps.esHorario && !bProps.esHorario) return 1;
            if (!aProps.esHorario && bProps.esHorario) return -1;
            
            // Prioridad 2: Entre solicitudes, ordenar horas extra por hora
            if (aProps.tipo === 'extra' && bProps.tipo === 'extra') {
                return moment(a.start).diff(moment(b.start));
            }
            
            // Prioridad 3: Horas extra antes que otros tipos de solicitudes
            if (aProps.tipo === 'extra' && bProps.esSolicitud && bProps.tipo !== 'extra') return -1;
            if (aProps.esSolicitud && aProps.tipo !== 'extra' && bProps.tipo === 'extra') return 1;
            
            return 0;
        },
        events: eventos,
        eventDidMount: function(info) {
            const props = info.event.extendedProps || {};
            
            // Ocultar horarios laborales en días festivos
            if (props.esHorario) {
                const fechaEvento = moment(info.event.start).format('YYYY-MM-DD');
                if (fechasFestivas.has(fechaEvento)) {
                    info.el.style.display = 'none';
                    return;
                }
                
                // También verificar si el día está ocupado por solicitudes aprobadas
                if (diasOcupados.has(fechaEvento)) {
                    info.el.style.display = 'none';
                    return;
                }
            }
        },
        eventContent: function(arg) {
            const props = arg.event.extendedProps || {};
            
            // Manejo especial para horarios laborales
            if (props.esHorario) {
                if (arg.view.type === 'dayGridMonth') {
                    return {
                        html: `
                        <div style="
                            display:inline-block;
                            background:#b0ddf7ff;
                            color:#186fbb;
                            border-radius:8px;
                            padding:2px 10px 2px 10px;
                            font-size:0.98em;
                            font-weight:500;
                            margin-top:2px;
                            margin-bottom:1px;
                  
                            ">
                            Horario laboral
                        </div>`
                    };
                } else {
                    let txt = 'Horario laboral';
                    if (props.hora_inicio && props.hora_fin)
                        txt += `<br><small>${props.hora_inicio} - ${props.hora_fin}</small>`;
                    return { html: `<b>${txt}</b>` };
                }
            }

            
            
            // Manejo especial para solicitudes en vista mensual
            if (props.esSolicitud && arg.view.type === 'dayGridMonth') {
                const estado = props.estado || 'pendiente';
                const tipo = props.tipo || 'general';
                const tipoTexto = tipoLabels[tipo] || tipo;
                const estadoTexto = estadoLabels[estado] || estado;
                
                if (tipo === 'extra') {
                    const horas = arg.event.extendedProps.horas || 0;
                    // En vista mensual, crear contenido HTML personalizado manteniendo colores
                    return {
                        html: `
                            <div style="
                                background: ${arg.event.backgroundColor};
                                color: ${arg.event.textColor || '#fff'};
                                border-radius: 4px;
                                padding: 2px 6px;
                                font-size: 0.85em;
                                font-weight: 500;
                                white-space: nowrap;
                                overflow: hidden;
                                text-overflow: ellipsis;
                                border: 1px solid ${arg.event.borderColor};
                            ">
                                Horas extra (${horas}h) - ${estadoTexto}
                            </div>
                        `
                    };
                } else {
                    // Para otros tipos de solicitudes en vista mensual
                    return {
                        html: `
                            <div style="
                                background: ${arg.event.backgroundColor};
                                color: ${arg.event.textColor || '#fff'};
                                border-radius: 4px;
                                padding: 2px 6px;
                                font-size: 0.85em;
                                font-weight: 500;
                                white-space: nowrap;
                                overflow: hidden;
                                text-overflow: ellipsis;
                                border: 1px solid ${arg.event.borderColor};
                            ">
                                ${tipoTexto} - ${estadoTexto}
                            </div>
                        `
                    };
                }
            }
            
            // Manejo especial para horas extra en otras vistas
            if (props.esSolicitud && props.tipo === 'extra') {
                const estado = props.estado || 'pendiente';
                const estadoTexto = estadoLabels[estado] || estado;
                const horas = arg.event.extendedProps.horas || 0;
                
                if (['timeGridWeek', 'timeGridDay'].includes(arg.view.type)) {
                    // Vistas de semana y día: mostrar información completa
                    return {
                        html: `
                            <div class="fw-bold">Horas extra</div>
                            <div><small>${horas}h - ${estadoTexto}</small></div>
                        `
                    };
                } else if (arg.view.type === 'listWeek') {
                    // Vista de lista: mostrar información detallada
                    const horaInicio = arg.event.extendedProps.hora_inicio ? 
                        arg.event.extendedProps.hora_inicio.substring(0,5) : '';
                    const horaFin = arg.event.extendedProps.hora_fin ? 
                        arg.event.extendedProps.hora_fin.substring(0,5) : '';
                    const horario = (horaInicio && horaFin) ? `${horaInicio} - ${horaFin}` : '';
                    
                    return {
                        html: `
                            <div class="fw-bold">Horas extra (${estadoTexto})</div>
                            <div><small>${horas}h${horario ? ' | ' + horario : ''}</small></div>
                        `
                    };
                }
            }
            
            // Manejo especial para eventos de calendario
            if (props.esEvento) {
                if (arg.view.type === 'dayGridMonth') {
                    return {
                        html: `
                            <div style="
                                background: ${arg.event.backgroundColor};
                                color: ${arg.event.textColor || '#fff'};
                                border-radius: 4px;
                                padding: 2px 6px;
                                font-size: 0.85em;
                                font-weight: 500;
                                white-space: nowrap;
                                overflow: hidden;
                                text-overflow: ellipsis;
                                border: 1px solid ${arg.event.borderColor};
                            ">
                                ${arg.event.title}
                            </div>
                        `
                    };
                } else {
                    // Para otras vistas, usar el título directamente
                    return {
                        html: `<div class="fw-bold">${arg.event.title}</div>`
                    };
                }
            }
            
            return true; // Usar contenido por defecto para otros tipos
        },
        eventClick: function(info) {
            const props = info.event.extendedProps || {};
            if (props.esHorario) return;
            
            if (props.esFestivo) {
                alert(
                    `Día Festivo\n` +
                    `${info.event.title}\n` +
                    `Fecha: ${moment(info.event.start).format('DD/MM/YYYY')}\n` +
                    `Alcance: ${props.alcance || 'No especificado'}`
                );
                return;
            }
            
            if (props.esSolicitud) {
                const tipo = props.tipo || 'general';
                const estado = props.estado || 'pendiente';
                const titulo = tipoLabels[tipo] || tipo || 'Evento';
                const estadoTexto = estadoLabels[estado] || estado || 'Sin estado';
                
                let mensaje = `${titulo}\nEstado: ${estadoTexto}\n`;
                
                if (tipo === 'extra') {
                    // Para horas extra, mostrar información específica
                    mensaje += `Fecha: ${moment(info.event.start).format('DD/MM/YYYY')}\n`;
                    if (props.horas) {
                        mensaje += `Horas solicitadas: ${props.horas}h\n`;
                    }
                    if (props.hora_inicio && props.hora_fin) {
                        mensaje += `Horario: ${props.hora_inicio.substring(0,5)} - ${props.hora_fin.substring(0,5)}\n`;
                    }
                } else {
                    // Para otros tipos de solicitudes
                    mensaje += `Fecha: ${moment(info.event.start).format('DD/MM/YYYY')}`;
                    if (info.event.end) {
                        mensaje += ` - ${moment(info.event.end).format('DD/MM/YYYY')}`;
                    }
                    mensaje += '\n';
                }
                
                if (props.motivo) {
                    mensaje += `Motivo: ${props.motivo}`;
                }
                
                alert(mensaje);
                return;
            }
            
            if (props.esEvento) {
                let mensaje = `Evento: ${info.event.title}\n`;
                mensaje += `Fecha: ${moment(info.event.start).format('DD/MM/YYYY')}`;
                if (info.event.end && moment(info.event.end).format('YYYY-MM-DD') !== moment(info.event.start).format('YYYY-MM-DD')) {
                    mensaje += ` - ${moment(info.event.end).format('DD/MM/YYYY')}`;
                }
                mensaje += '\n';
                
                if (props.descripcion) {
                    mensaje += `Descripción: ${props.descripcion}\n`;
                }
                
                if (props.para_todos) {
                    mensaje += `Aplica para: Todos los empleados`;
                } else {
                    mensaje += `Aplica para: Solo para ti`;
                }
                
                alert(mensaje);
                return;
            }
        }
    });

    calendar.render();

    // Ajusta toolbar y vista al redimensionar
    window.addEventListener('resize', () => {
      if (!calendar) return;

      if (window.innerWidth < 768) {
        calendar.changeView('listWeek');
        calendar.setOption('headerToolbar', {
          left: 'prev,next',
          center: 'title',
          right: 'dayGridMonth,timeGridWeek,timeGridDay,listWeek'
        });
      } else {
        calendar.changeView('dayGridMonth');
        calendar.setOption('headerToolbar', {
          left: 'prev today next',
          center: 'title',
          right: 'dayGridMonth,timeGridWeek,timeGridDay,listWeek'
        });
      }
    });

    
    // Log para verificar que los eventos tienen las propiedades correctas
    setTimeout(() => {
      const events = calendar.getEvents();
      events.forEach((event, index) => {
        const props = event.extendedProps || {};
      });
    }, 500);
  }



  // FUNCIONALIDAD DE FILTROS PARA EL CALENDARIO

  /**
   * Inicializa los event listeners para los botones de filtro del calendario
   */
  function initCalendarFilters() {
    const filterButtons = document.querySelectorAll('#calendar-filters .btn');
    
    if (filterButtons.length === 0) {
      console.warn('No se encontraron botones de filtro del calendario');
      return;
    }

    filterButtons.forEach(button => {
      button.addEventListener('click', function() {
        // Remover clase active de todos los botones
        filterButtons.forEach(btn => btn.classList.remove('active'));
        
        // Agregar clase active al botón clickeado
        this.classList.add('active');
        
        // Obtener el tipo de filtro
        const filterType = this.getAttribute('data-type');
        
        // Aplicar filtro al calendario
        filterCalendarEvents(filterType);
      });
    });

    // Activar el primer botón (Todos) por defecto
    if (filterButtons.length > 0) {
      filterButtons[0].classList.add('active');
    }
  }

  /**
   * Filtra los eventos del calendario según el tipo seleccionado
   * @param {string} filterType - Tipo de filtro ('all', 'vacaciones', 'permiso', etc.)
   */
  function filterCalendarEvents(filterType) {
    if (!calendar) {
      console.warn('El calendario no está inicializado');
      return;
    }

    // Obtener todos los eventos
    const allEvents = calendar.getEvents();
    
    let eventosVisibles = 0;
    
    allEvents.forEach(event => {
      const eventProps = event.extendedProps || {};
      const eventType = eventProps.tipo;
      const isRequest = eventProps.esSolicitud;
      const isHoliday = eventProps.esFestivo;
      const isSchedule = eventProps.esHorario;
      
      // Determinar si el evento debe mostrarse
      let shouldShow = true;
      
      if (filterType === 'all') {
        // Mostrar todos los eventos (solicitudes, festivos y horarios)
        shouldShow = true;
      } else {
        // Filtro específico
        if (isRequest) {
          // Para solicitudes, verificar el tipo
          shouldShow = eventType === filterType;
        } else if (isHoliday) {
          // Ocultar festivos cuando se filtra por tipo específico
          shouldShow = false;
        } else if (isSchedule) {
          // Ocultar horarios cuando se filtra por tipo específico
          shouldShow = false;
        } else {
          shouldShow = false;
        }
      }
      
      // Aplicar la visibilidad al evento
      if (shouldShow) {
        event.setProp('display', 'auto');
        eventosVisibles++;
      } else {
        event.setProp('display', 'none');
      }
    });
  }

  window.addEventListener('resize', () => {
    if(!calendar) return;
    const opt = calcOptions();
    calendar.setOption('height', opt.height);
    if(calendar.view.type !== opt.initialView){
      calendar.changeView(opt.initialView);
    }
  });

  function changeType() {
    const tipo = tipoSelect.value;
    const esExtra = tipo === 'extra';
    const esAusencia = tipo === 'ausencia';
    
    // Controlar visibilidad de campos
    periodoGroup.classList.toggle('d-none', esExtra);
    halfDayGroup.classList.toggle('d-none', esExtra);
    extraHoursGroup.classList.toggle('d-none', !esExtra);
    absenceTypeGroup?.classList.toggle('d-none', !esAusencia);
    
    // Controlar campos requeridos
    dateInput.required = !esExtra;
    extraHoursInput.required = false; // Ya no requerido porque se calcula automáticamente
    if (extraDateInput) {
      extraDateInput.required = esExtra;
    }
    if (extraStartTimeInput) {
      extraStartTimeInput.required = esExtra;
    }
    if (extraEndTimeInput) {
      extraEndTimeInput.required = esExtra;
    }
    if (absenceTypeInput) {
      absenceTypeInput.required = esAusencia;
    }
    
    // Limpiar campos según tipo seleccionado
    if (esExtra) {
      if (typeof $ !== 'undefined') {
        $(dateInput).val('');
      } else {
        dateInput.value = '';
      }
      rangoSeleccionado = null;
      if (absenceTypeInput) {
        absenceTypeInput.value = '';
      }
    } else {
      extraHoursInput.value = '';
      if (extraDateInput) {
        extraDateInput.value = '';
        if (typeof $ !== 'undefined') {
          $(extraDateInput).val('');
        }
      }
      if (!esAusencia && absenceTypeInput) {
        absenceTypeInput.value = '';
      }
    }
    
    // Reinicializar daterangepicker con nuevas restricciones
    if (typeof $ !== 'undefined' && !esExtra) {
      $(dateInput).data('daterangepicker').remove();
      $(dateInput).daterangepicker({
        locale: {
          format: 'DD/MM/YYYY',
          applyLabel: 'Aplicar',
          cancelLabel: 'Cancelar',
          fromLabel: 'De',
          toLabel: 'A',
          customRangeLabel: 'Personalizado',
          weekLabel: 'S',
          daysOfWeek: ['Lun','Mar','Mié','Jue','Vie','Sáb','Dom'],
          monthNames: ['Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'],
          firstDay: 1
        },
        autoUpdateInput: false,
        opens: 'center',
        minDate: moment(),
        isInvalidDate: function(date) {
          return !isValidDateForSelection(date, tipo);
        }
      }, (start, end) => {
        rangoSeleccionado = { start, end };
        dateInput.value = `${start.format('DD/MM/YYYY')} – ${end.format('DD/MM/YYYY')}`;
        refrescarResumen();
      });
    }
    
    refrescarResumen();
  }

  /**
   * Valida que haya saldo suficiente para la solicitud
   * Retorna true si hay saldo, false si no y muestra error
   */
  function validarSaldo() {
    const tipo = tipoSelect.value;
    
    // Solo validar para vacaciones, bajas y ausencias (que usan días)
    if (!['vacaciones', 'baja', 'ausencia'].includes(tipo)) {
      return true;
    }
    
    // Obtener días solicitados del resumen
    const resumenText = summaryDaysEl.textContent || '';
    const diasMatch = resumenText.match(/^(\d+)/);
    if (!diasMatch) return true;
    
    const diasSolicitados = parseInt(diasMatch[1]);
    const saldo = saldosActuales[tipo];
    
    if (!saldo) return true;
    
    // Para tipos que tienen estructura { usado, max }
    if (saldo && typeof saldo === 'object' && 'usado' in saldo && 'max' in saldo) {
      const diasDisponibles = saldo.max - saldo.usado;
      
      if (diasSolicitados > diasDisponibles) {
        // Mostrar error amigable
        let mensaje = `<strong>Saldo insuficiente de ${tipo}</strong><br>`;
        mensaje += `Solicitas: <strong>${diasSolicitados} días</strong><br>`;
        mensaje += `Disponibles: <strong>${diasDisponibles} días</strong><br>`;
        mensaje += `Máximo: ${saldo.max} días<br>`;
        mensaje += `<small class="text-muted mt-2">Modifica el rango de fechas para reducir los días solicitados</small>`;
        
        if (typeof toastr !== 'undefined') {
          toastr.error(mensaje, 'Error de saldo', { timeOut: 5000, extendedTimeOut: 2000, allowHtml: true });
        } else {
          alert(`${tipo}: Solicitas ${diasSolicitados} días pero solo tienes ${diasDisponibles} disponibles de ${saldo.max}`);
        }
        
        // Volver a enfocar el selector de rango para que corrija
        if (dateInput) {
          dateInput.focus();
          dateInput.classList.add('is-invalid');
        }
        
        return false;
      }
    }
    
    return true;
  }

  function validarConfirmar(e) {
    e.preventDefault();
    const tipo = tipoSelect.value;
    const esExtra = tipo==='extra';
    const esAusencia = tipo === 'ausencia';
    
    if (!form.checkValidity()) {
      form.classList.add('was-validated');
      return;
    }
    if (!esExtra && !rangoSeleccionado) {
      dateInput.classList.add('is-invalid');
      return;
    } else {
      dateInput.classList.remove('is-invalid');
    }
    
    // Validación específica para horas extra
    if (esExtra && (!extraDateInput || !extraDateInput.value)) {
      if (extraDateInput) {
        extraDateInput.classList.add('is-invalid');
      }
      return;
    } else if (extraDateInput) {
      extraDateInput.classList.remove('is-invalid');
    }
    
    // Validación específica para ausencias
    if (esAusencia && absenceTypeInput && !absenceTypeInput.value) {
      absenceTypeInput.classList.add('is-invalid');
      return;
    } else if (absenceTypeInput) {
      absenceTypeInput.classList.remove('is-invalid');
    }
    
    refrescarResumen();
    
    // Validar saldo disponible ANTES de mostrar el modal de confirmación
    if (!validarSaldo()) {
      return; // Si no hay saldo, no continúa
    }
    
    if (typeof bootstrap !== 'undefined') {
      new bootstrap.Modal(confirmModalEl).show();
    } else {
      if (confirm('¿Confirma que desea enviar la solicitud?')) {
        enviar();
      }
    }
  }

  function enviar() {
    const startTime = performance.now();
    
    const fd = new FormData(form);
    const tipo = tipoSelect.value;

    // Agrega campos manuales si no están en el form
    fd.append('tipo', tipo);

    if (tipo === 'extra') {
      // Para horas extra: agregar número de horas y fecha
      fd.append('horas', parseFloat(extraHoursInput.value || 0));
      if (extraDateInput && extraDateInput.value) {
        // Convertir fecha DD/MM/YYYY a YYYY-MM-DD
        const fechaParts = extraDateInput.value.split('/');
        if (fechaParts.length === 3) {
          const fechaSQL = `${fechaParts[2]}-${fechaParts[1].padStart(2, '0')}-${fechaParts[0].padStart(2, '0')}`;
          fd.append('start_date', fechaSQL);
          fd.append('end_date', fechaSQL); // Para horas extra, inicio y fin son la misma fecha
        }
      }
    } else {
      // Para otros tipos: agregar fechas y medio día
      fd.append('start_date', rangoSeleccionado.start.format('YYYY-MM-DD'));
      fd.append('end_date', rangoSeleccionado.end.format('YYYY-MM-DD'));
      fd.append('half_day', halfDaySwitch.checked ? 1 : 0);
    }

    fetch(BASE_URL + 'acciones/procesar-solicitud.php', { 
      method: 'POST',
      body: fd
    })
    .then(r => {
      const networkTime = performance.now() - startTime;
      return r.text();
    })
    .then(txt => {
      
      try {
        const json = JSON.parse(txt);
        if (json.success) {


          if (typeof toastr !== 'undefined') {
            toastr.success('Solicitud enviada');
          } else {
            alert('Solicitud enviada correctamente');
          }
          
          // Cerrar modal si Bootstrap está disponible
          if (typeof bootstrap !== 'undefined') {
            bootstrap.Modal.getInstance(confirmModalEl).hide();
          }
          
          // Agregar inmediatamente la nueva solicitud al calendario
          if (calendar) {
            const nuevaSolicitud = crearEventoSolicitud(tipo, fd);
            if (nuevaSolicitud) {
              if (Array.isArray(nuevaSolicitud)) {
                // Para solicitudes multi-día (vacaciones, permisos, etc.)
                nuevaSolicitud.forEach(evento => calendar.addEvent(evento));


              } else {
                // Para solicitudes de un solo día (horas extra)
                calendar.addEvent(nuevaSolicitud);


              }
            }
          }
          
          resetForm();
          
          // Refrescar datos del sidebar, historial y calendario con un pequeño delay


          setTimeout(() => {
            initData().then(() => {
              // Después de actualizar los datos, refrescar el calendario
              if (calendar && calendar.refetchEvents) {


                calendar.refetchEvents();
              }
            }).catch(err => {
              console.error('Error al refrescar datos:', err);
              // Si hay error, intentar refrescar el calendario de todas formas
              if (calendar && calendar.refetchEvents) {
                calendar.refetchEvents();
              }
            });
          }, 1000); // Aumentar delay a 1 segundo
        } else {
          if (typeof toastr !== 'undefined') {
            toastr.error(json.error || 'Error al guardar');
          } else {
            alert('Error: ' + (json.error || 'Error al guardar'));
          }
        }
      } catch(e) {
        console.error('Error parseando JSON:', e);
        alert("Error en la respuesta del servidor. Consulta la consola.");
      }
    })
    .catch(err => {
      if (typeof toastr !== 'undefined') {
        toastr.error('Error de red');
      } else {
        alert('Error de red');
      }
      console.error(err);
    });
  }


  function resetForm(){
    form.reset();
    rangoSeleccionado=null;
    dateInput.value='';
    if (absenceTypeInput) {
      absenceTypeInput.value='';
    }
    form.classList.remove('was-validated');
    changeType(); 
  }

})();

$(document).on('click', '.aprobar-btn', function() {
    var id = $(this).data('id');
    if(confirm("¿Aprobar la solicitud?")) {
      $.post(BASE_URL + 'acciones/aprobar-solicitud.php', { id: id }, function(resp){
        if(resp.success){
          location.reload();
        }else{
          alert("Error: " + resp.error);
        }
      }, 'json');
    }
});
$(document).on('click', '.rechazar-btn', function() {
    var id = $(this).data('id');
    var motivo = prompt("¿Motivo de rechazo?");
    if(motivo !== null) {
      $.post(BASE_URL + 'acciones/aprobar-solicitud.php', { id: id, motivo: motivo }, function(resp){
        if(resp.success){
          location.reload();
        }else{
          alert("Error: " + resp.error);
        }
      }, 'json');
    }
});

/**
 * Actualiza las estadísticas de solicitudes sin recargar la página
 * Útil después de eliminar una solicitud para mantener los números actualizados
 */
window.actualizarEstadisticas = function() {
    // Obtener los filtros actuales si existen
    const urlParams = new URLSearchParams(window.location.search);
    const estado = urlParams.get('estado') || '';
    const tipo = urlParams.get('tipo') || '';
    const empleado = urlParams.get('empleado') || '';
    const fechaDesde = urlParams.get('fecha_desde') || '';
    const fechaHasta = urlParams.get('fecha_hasta') || '';
    
    // Construir URL con parámetros
    let url = BASE_URL + 'admin/ver-solicitudes.php?action=stats';
    if (estado) url += '&estado=' + encodeURIComponent(estado);
    if (tipo) url += '&tipo=' + encodeURIComponent(tipo);
    if (empleado) url += '&empleado=' + encodeURIComponent(empleado);
    if (fechaDesde) url += '&fecha_desde=' + encodeURIComponent(fechaDesde);
    if (fechaHasta) url += '&fecha_hasta=' + encodeURIComponent(fechaHasta);
    
    fetch(url)
        .then(response => response.json())
        .then(data => {
            if (data.stats) {
                // Actualizar badges de estadísticas
                const statTotal = document.getElementById('stat-total');
                const statPendientes = document.getElementById('stat-pendientes');
                const statAprobadas = document.getElementById('stat-aprobadas');
                const statRechazadas = document.getElementById('stat-rechazadas');
                
                if (statTotal) statTotal.textContent = 'Total: ' + (data.stats.total || 0);
                if (statPendientes) statPendientes.textContent = 'Pendientes: ' + (data.stats.pendientes || 0);
                if (statAprobadas) statAprobadas.textContent = 'Aprobadas: ' + (data.stats.aprobadas || 0);
                if (statRechazadas) statRechazadas.textContent = 'Rechazadas: ' + (data.stats.rechazadas || 0);
            }
        })
        .catch(error => {
            console.error('Error actualizando estadísticas:', error);
        });
};

// --- MODALES Y FILTROS DE ADMINISTRACIÓN DE SOLICITUDES ---
window.initAdminSolicitudes = function() {
  // Llamar a la función real de inicialización


  if (typeof initAdminSolicitudes_real === 'function') {
    initAdminSolicitudes_real();
  } else {
    console.error('initAdminSolicitudes_real no encontrada');
  }
};

// Función de respaldo para configurar eventos manualmente desde la página
window.configurarEventosManualmente = function() {


    
    // Verificar dependencias
    if (typeof bootstrap === 'undefined') {
        console.error('Bootstrap no disponible');
        return;
    }
    
    if (typeof BASE_URL === 'undefined') {
        console.error('BASE_URL no definido');
        return;
    }
    
    // Botones de aprobar
    document.querySelectorAll('.aprobar-btn').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const id = this.dataset.id;


            
            document.getElementById('solicitudId').value = id;
            document.getElementById('accionType').value = 'aprobar';
            document.getElementById('comentarioAdmin').value = '';
            document.getElementById('modalAprobacionTitle').textContent = 'Aprobar Solicitud';
            
            const btnConfirmar = document.getElementById('btnConfirmarAccion');
            btnConfirmar.className = 'btn btn-success';
            btnConfirmar.textContent = 'Aprobar';
            
            new bootstrap.Modal(document.getElementById('modalAprobacion')).show();
        });
    });
    
    // Botones de rechazar
    document.querySelectorAll('.rechazar-btn').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const id = this.dataset.id;


            
            document.getElementById('solicitudId').value = id;
            document.getElementById('accionType').value = 'rechazar';
            document.getElementById('comentarioAdmin').value = '';
            document.getElementById('modalAprobacionTitle').textContent = 'Rechazar Solicitud';
            
            const btnConfirmar = document.getElementById('btnConfirmarAccion');
            btnConfirmar.className = 'btn btn-danger';
            btnConfirmar.textContent = 'Rechazar';
            
            new bootstrap.Modal(document.getElementById('modalAprobacion')).show();
        });
    });
    
    // Botones de ver - SOLO en solicitudes.php, NO en ver-solicitudes.php
    if (!window.location.pathname.includes('ver-solicitudes.php')) {
        document.querySelectorAll('.ver-btn').forEach(btn => {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                const id = this.dataset.id;


                
                const modalBody = document.getElementById('modalDetalleBody');
                modalBody.innerHTML = '<div class="text-center py-4"><div class="spinner-border"></div><p class="mt-2">Cargando...</p></div>';
                
                const modalDetalle = new bootstrap.Modal(document.getElementById('modalDetalle'));
                modalDetalle.show();
                
                fetch(BASE_URL + 'acciones/obtener-detalle-solicitud.php?id=' + id)
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            modalBody.innerHTML = window.renderDetalleBasico ? window.renderDetalleBasico(data.solicitud) : 'Datos cargados correctamente';
                        } else {
                            // Si la solicitud no existe (fue eliminada), cerrar el modal silenciosamente
                            if (data.error === 'Solicitud no encontrada') {
                                console.warn('La solicitud fue eliminada');
                                // Cerrar el modal sin mostrar error ni notificacion
                                modalDetalle.hide();
                            } else {
                                modalBody.innerHTML = '<div class="alert alert-danger">Error: ' + (data.error || 'Error desconocido') + '</div>';
                            }
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        modalBody.innerHTML = '<div class="alert alert-danger">Error de red</div>';
                    });
            });
        });
    };
    
    // Botones de eliminar - SOLO en solicitudes.php, NO en ver-solicitudes.php
    if (!window.location.pathname.includes('ver-solicitudes.php')) {
        document.querySelectorAll('.eliminar-btn').forEach(btn => {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                const id = this.dataset.id;


                
                document.getElementById('solicitudEliminarId').value = id;
                new bootstrap.Modal(document.getElementById('modalEliminar')).show();
        });
        });
    }
    
    // Botón confirmar acción
    const btnConfirmarAccion = document.getElementById('btnConfirmarAccion');
    if (btnConfirmarAccion) {
        // Remover eventos existentes
        btnConfirmarAccion.replaceWith(btnConfirmarAccion.cloneNode(true));
        const newBtnConfirmarAccion = document.getElementById('btnConfirmarAccion');
        
        newBtnConfirmarAccion.addEventListener('click', function(e) {
            e.preventDefault();
            
            const formData = new FormData(document.getElementById('formAprobacion'));
            
            const modal = bootstrap.Modal.getInstance(document.getElementById('modalAprobacion'));
            if (modal) modal.hide();
            
            fetch(BASE_URL + 'acciones/aprobar-solicitud.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {


                if (data.success) {
                    location.reload();
                } else {
                    alert('Error: ' + (data.error || 'Error desconocido'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error de red');
            });
        });
    }
    
    // Botón confirmar eliminación
    const btnConfirmarEliminar = document.getElementById('btnConfirmarEliminar');
    if (btnConfirmarEliminar) {
        // Remover eventos existentes
        btnConfirmarEliminar.replaceWith(btnConfirmarEliminar.cloneNode(true));
        const newBtnConfirmarEliminar = document.getElementById('btnConfirmarEliminar');
        
        newBtnConfirmarEliminar.addEventListener('click', function(e) {
            e.preventDefault();
            
            const id = document.getElementById('solicitudEliminarId').value;


            
            const modal = bootstrap.Modal.getInstance(document.getElementById('modalEliminar'));
            if (modal) modal.hide();
            
            fetch(BASE_URL + 'acciones/eliminar-solicitud.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id: id })
            })
            .then(response => response.json())
            .then(data => {


                if (data.success) {
                    // Mostrar notificación de éxito
                    if (typeof toastr !== 'undefined') {
                        toastr.success('Solicitud eliminada correctamente');
                    }
                    
                    // Remover la fila de la tabla de forma elegante
                    const filaTabla = document.querySelector(`tr[data-solicitud-id="${id}"]`);
                    if (filaTabla) {
                        filaTabla.style.animation = 'fadeOut 0.3s ease-out';
                        setTimeout(() => filaTabla.remove(), 300);
                    }
                    
                    // Remover tarjeta de móvil si existe
                    const tarjeta = document.querySelector(`[data-solicitud-card="${id}"]`);
                    if (tarjeta) {
                        tarjeta.style.animation = 'fadeOut 0.3s ease-out';
                        setTimeout(() => tarjeta.remove(), 300);
                    }
                    
                    // Cerrar cualquier modal de detalles abierto para evitar "Solicitud no encontrada"
                    const modalDetalle = bootstrap.Modal.getInstance(document.getElementById('modalDetalle'));
                    if (modalDetalle) {
                        modalDetalle.hide();
                    }
                    
                    // Actualizar estadísticas sin recargar la página
                    // Esto evita el error de "Solicitud no encontrada"
                    actualizarEstadisticas();
                    
                } else {
                    // Mostrar error elegante
                    if (typeof toastr !== 'undefined') {
                        toastr.error(data.error || 'Error desconocido');
                    } else {
                        alert('Error: ' + (data.error || 'Error desconocido'));
                    }
                }
            })
            .catch(error => {
                console.error('Error:', error);
                if (typeof toastr !== 'undefined') {
                    toastr.error('Error de conexión al servidor');
                } else {
                    alert('Error de red');
                }
            });
        });
    }
    


};

// Función simple para renderizar detalles
window.renderDetalleBasico = function(solicitud) {
    const formatFecha = (fecha) => fecha ? new Date(fecha).toLocaleDateString('es-ES') : '-';
    const formatFechaHora = (fecha) => fecha ? new Date(fecha).toLocaleString('es-ES') : '-';
    
    return `
        <div class="row g-3">
            <div class="col-12">
                <h6>Empleado</h6>
                <p><strong>${solicitud.empleado_nombre || 'N/A'}</strong><br>
                <small class="text-muted">${solicitud.empleado_email || ''}</small></p>
            </div>
            <div class="col-md-6">
                <h6>Tipo</h6>
                <p><span class="badge bg-info">${solicitud.tipo || 'N/A'}</span></p>
            </div>
            <div class="col-md-6">
                <h6>Estado</h6>
                <p><span class="badge ${solicitud.estado === 'pendiente' ? 'bg-warning text-dark' : solicitud.estado === 'aprobado' ? 'bg-success' : 'bg-danger'}">${solicitud.estado || 'N/A'}</span></p>
            </div>
            <div class="col-md-6">
                <h6>Fecha inicio</h6>
                <p>${formatFecha(solicitud.fecha_inicio)}</p>
            </div>
            <div class="col-md-6">
                <h6>Fecha fin</h6>
                <p>${formatFecha(solicitud.fecha_fin)}</p>
            </div>
            <div class="col-12">
                <h6>Fecha de solicitud</h6>
                <p>${formatFechaHora(solicitud.fecha_solicitud)}</p>
            </div>
            ${solicitud.comentario_empleado ? `
            <div class="col-12">
                <h6>Comentario del empleado</h6>
                <div class="border rounded p-2 bg-light">${solicitud.comentario_empleado}</div>
            </div>` : ''}
            ${solicitud.comentario_admin ? `
            <div class="col-12">
                <h6>Comentario del supervisor</h6>
                <div class="border rounded p-2 bg-light">${solicitud.comentario_admin}</div>
            </div>` : ''}
        </div>
    `;
};

// Función principal de inicialización para ver-solicitudes.php
window.initVerSolicitudes = function() {


    
    // Verificar dependencias
    if (typeof bootstrap === 'undefined') {
        console.error('Bootstrap no disponible');
        return;
    }
    
    if (typeof BASE_URL === 'undefined') {
        console.error('BASE_URL no definido');
        return;
    }
    


    
    // Ejecutar la lógica de administración de solicitudes
    if (window.initAdminSolicitudes_real && typeof window.initAdminSolicitudes_real === 'function') {


        window.initAdminSolicitudes_real();
    }
    
    // Intentar usar las funciones existentes de request.js
    if (window.initAdminSolicitudes && typeof window.initAdminSolicitudes === 'function') {


        window.initAdminSolicitudes();
    } else {


    }
    
    if (window.initModalFixes && typeof window.initModalFixes === 'function') {


        window.initModalFixes();
    } else {


    }
    
    // Verificar que los eventos se hayan configurado
    setTimeout(function() {
        const botonesAprobar = document.querySelectorAll('.aprobar-btn');
        const botonesRechazar = document.querySelectorAll('.rechazar-btn');
        const botonesVer = document.querySelectorAll('.ver-btn');
        const botonesEliminar = document.querySelectorAll('.eliminar-btn');
        
        // Si hay botones pero no eventos, configurar manualmente
        if (botonesAprobar.length > 0) {


            window.configurarEventosManualmente();
        }
    }, 200);
};

window.initModalFixes = function() {
    // Limpiar backdrop y otras cosas cuando se cierra cualquier modal
    const modals = document.querySelectorAll('.modal');
    modals.forEach(modal => {
        modal.addEventListener('hidden.bs.modal', function(event) {
            // Limpiar backdrop manualmente
            const backdrops = document.querySelectorAll('.modal-backdrop');
            backdrops.forEach(backdrop => backdrop.remove());
            // Asegurar que el body no tenga clases de modal
            document.body.classList.remove('modal-open');
            document.body.style.overflow = '';
            document.body.style.paddingRight = '';
            // Limpiar contenido del modal de detalles
            if (modal.id === 'modalDetalle') {
                const modalBody = modal.querySelector('#modalDetalleBody');
                if (modalBody) {
                    modalBody.innerHTML = '';
                }
            }
        });
        // También manejar el evento de mostrar el modal
        modal.addEventListener('show.bs.modal', function(event) {
            // Asegurar que otros modales estén cerrados
            const otherModals = document.querySelectorAll('.modal.show');
            otherModals.forEach(otherModal => {
                if (otherModal !== modal) {
                    const modalInstance = bootstrap.Modal.getInstance(otherModal);
                    if (modalInstance) {
                        modalInstance.hide();
                    }
                }
            });
        });
    });
    // Agregar evento de click en backdrop para cerrar modales
    document.addEventListener('click', function(event) {
        if (event.target.classList.contains('modal-backdrop')) {
            const openModals = document.querySelectorAll('.modal.show');
            openModals.forEach(modal => {
                const modalInstance = bootstrap.Modal.getInstance(modal);
                if (modalInstance) {
                    modalInstance.hide();
                }
            });
        }
    });
    // Manejar tecla Escape para cerrar modales
    document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape') {
            const openModals = document.querySelectorAll('.modal.show');
            openModals.forEach(modal => {
                const modalInstance = bootstrap.Modal.getInstance(modal);
                if (modalInstance) {
                    modalInstance.hide();
                }
            });
        }
    });
    // Funcionalidad del botón limpiar filtros
    const btnLimpiarFiltros = document.getElementById('btnLimpiarFiltros');
    if (btnLimpiarFiltros) {
        btnLimpiarFiltros.addEventListener('click', function() {
            // Limpiar todos los campos del formulario
            const form = this.closest('form');
            const inputs = form.querySelectorAll('input, select');
            inputs.forEach(input => {
                if (input.type === 'hidden' && input.name === 'page') {
                    // Mantener el parámetro page
                    return;
                }
                if (input.name === 'pagina') {
                  input.value = '1'; // Resetear a página 1
                  return;
                }
                if (input.tagName === 'SELECT') {
                    input.selectedIndex = 0;
                } else {
                    input.value = '';
                }
            });
            // Enviar el formulario para aplicar los filtros limpios
            form.submit();
        });
    }
    // Botón de emergencia para cerrar todos los modales
    const btnCerrarTodosModales = document.getElementById('cerrarTodosModales');
    if (btnCerrarTodosModales) {
        btnCerrarTodosModales.addEventListener('click', function() {
            // Cerrar todos los modales usando Bootstrap
            const modales = document.querySelectorAll('.modal.show');
            modales.forEach(modal => {
                const modalInstance = bootstrap.Modal.getInstance(modal);
                if (modalInstance) {
                    modalInstance.hide();
                }
            });
            // Forzar limpieza manual después de un breve retraso
            setTimeout(() => {
                // Eliminar todos los backdrops
                const backdrops = document.querySelectorAll('.modal-backdrop');
                backdrops.forEach(backdrop => {
                    backdrop.remove();
                });
                // Limpiar clases del body
                document.body.classList.remove('modal-open');
                document.body.style.overflow = '';
                document.body.style.paddingRight = '';
                // Ocultar todos los modales
                const todosModales = document.querySelectorAll('.modal');
                todosModales.forEach(modal => {
                    modal.classList.remove('show');
                    modal.style.display = 'none';
                    modal.setAttribute('aria-hidden', 'true');
                    modal.removeAttribute('aria-modal');
                    modal.removeAttribute('role');
                });
            }, 100);
        });
    }
}
// --- FIN MODALES Y FILTROS ---

// FUNCIONALIDAD PARA ADMIN/VER-SOLICITUDES
// Gestión avanzada de solicitudes con modales de confirmación

/**
 * Renderiza los detalles completos de una solicitud para mostrar en el modal
 * @param {Object} solicitud - Datos de la solicitud
 * @returns {string} HTML con los detalles formateados
 */
function renderDetallesSolicitud(solicitud) {
  // Formatear fechas
  const formatearFecha = (fecha) => {
    if (!fecha) return '-';
    return moment(fecha).format('DD/MM/YYYY');
  };
  
  const formatearFechaHora = (fechaHora) => {
    if (!fechaHora) return '-';
    return moment(fechaHora).format('DD/MM/YYYY HH:mm');
  };
  
  // Construir información de período/duración
  let periodoInfo = '';
  if (solicitud.tipo === 'extra') {
    periodoInfo = `
      <div class="col-md-6">
        <strong>Fecha:</strong><br>
        ${formatearFecha(solicitud.fecha_inicio)}
      </div>
      <div class="col-md-6">
        <strong>Horas solicitadas:</strong><br>
        ${(solicitud.horas && solicitud.horas > 0) ? solicitud.horas + ' horas' : '-'}
      </div>
    `;
    if (solicitud.hora_inicio && solicitud.hora_fin) {
      periodoInfo += `
        <div class="col-md-12 mt-2">
          <strong>Horario:</strong><br>
          ${solicitud.hora_inicio.substring(0,5)} - ${solicitud.hora_fin.substring(0,5)}
        </div>
      `;
    }
  } else {
    periodoInfo = `
      <div class="col-md-6">
        <strong>Fecha inicio:</strong><br>
        ${formatearFecha(solicitud.fecha_inicio)}
      </div>
      <div class="col-md-6">
        <strong>Fecha fin:</strong><br>
        ${formatearFecha(solicitud.fecha_fin)}
      </div>
    `;
    if (solicitud.medio_dia) {
      periodoInfo += `
        <div class="col-md-12 mt-2">
          <span class="badge bg-warning text-dark">
            <i class="ti ti-clock me-1"></i>Medio día
          </span>
        </div>
      `;
    }
  }
  
  // Información del aprobador
  let aprobadorInfo = '';
  if (solicitud.aprobador_nombre) {
    aprobadorInfo = `
      <div class="col-md-6">
        <strong>Aprobado/Rechazado por:</strong><br>
        ${solicitud.aprobador_nombre}
        ${solicitud.aprobador_email ? `<br><small class="text-muted">${solicitud.aprobador_email}</small>` : ''}
      </div>
      <div class="col-md-6">
        <strong>Fecha de respuesta:</strong><br>
        ${formatearFechaHora(solicitud.fecha_respuesta)}
      </div>
    `;
  }
  
  // Comentarios
  let comentariosSection = '';
  if (solicitud.comentario_empleado || solicitud.comentario_admin) {
    comentariosSection = `
      <div class="mt-4">
        <h6 class="mb-3">
          <i class="ti ti-message-circle me-2"></i>Comentarios
        </h6>
    `;
    
    if (solicitud.comentario_empleado) {
      comentariosSection += `
        <div class="mb-3">
          <div class="card" style="border-left: 4px solid #0d6efd;">
            <div class="card-body py-2 px-3">
              <small class="text-muted d-block mb-1">
                <i class="ti ti-user me-1"></i>Comentario del empleado:
              </small>
              <p class="mb-0">${solicitud.comentario_empleado}</p>
            </div>
          </div>
        </div>
      `;
    }
    
    if (solicitud.comentario_admin) {
      comentariosSection += `
        <div class="mb-3">
          <div class="card" style="border-left: 4px solid #17a2b8;">
            <div class="card-body py-2 px-3">
              <small class="text-muted d-block mb-1">
                <i class="ti ti-shield me-1"></i>Comentario del administrador:
              </small>
              <p class="mb-0">${solicitud.comentario_admin}</p>
            </div>
          </div>
        </div>
      `;
    }
    
    comentariosSection += '</div>';
  }
  
  // Información adicional para ausencias
  let ausenciaInfo = '';
  if (solicitud.tipo === 'ausencia' && solicitud.tipo_ausencia) {
    ausenciaInfo = `
      <div class="col-md-12 mt-2">
        <strong>Tipo de ausencia:</strong><br>
        <span class="badge bg-info">${solicitud.tipo_ausencia}</span>
      </div>
    `;
  }
  
  // Información de archivo adjunto
  let archivoInfo = '';
  if (solicitud.archivo && solicitud.archivo.existe) {
    const iconoArchivo = {
      'pdf': 'ti ti-file-text',
      'doc': 'ti ti-file-word',
      'docx': 'ti ti-file-word',
      'jpg': 'ti ti-photo',
      'jpeg': 'ti ti-photo',
      'png': 'ti ti-photo',
      'gif': 'ti ti-photo'
    }[solicitud.archivo.extension] || 'ti ti-file';
    
    const tamañoFormateado = (solicitud.archivo.tamaño / 1024).toFixed(1) + ' KB';
    
    archivoInfo = `
      <div class="col-md-12 mt-3">
        <strong>Archivo adjunto:</strong><br>
        <div class="d-flex align-items-center mt-2">
          <i class="${iconoArchivo} me-2 text-primary fs-4"></i>
          <div class="flex-grow-1">
            <div class="fw-semibold">${solicitud.archivo.nombre}</div>
            <small class="text-muted">${tamañoFormateado} • ${solicitud.archivo.extension.toUpperCase()}</small>
          </div>
          <a href="${BASE_URL}acciones/descargar-archivo.php?solicitud_id=${solicitud.id}&archivo=${encodeURIComponent(solicitud.archivo.nombre)}" 
             class="btn btn-outline-primary btn-sm" target="_blank">
            <i class="ti ti-download me-1"></i>Descargar
          </a>
        </div>
      </div>
    `;
  } else if (solicitud.archivo && !solicitud.archivo.existe) {
    archivoInfo = `
      <div class="col-md-12 mt-3">
        <strong>Archivo adjunto:</strong><br>
        <div class="d-flex align-items-center mt-2">
          <i class="ti ti-file-off me-2 text-danger fs-4"></i>
          <div class="flex-grow-1">
            <div class="fw-semibold text-danger">${solicitud.archivo.nombre}</div>
            <small class="text-danger">Archivo no encontrado en el servidor</small>
          </div>
        </div>
      </div>
    `;
  }
  
  return `
    <div class="container-fluid" data-solicitud-id="${solicitud.id}" data-solicitud-tipo="${solicitud.tipo}" data-solicitud-estado="${solicitud.estado}">
      <!-- Header con estado -->
      <div class="d-flex justify-content-between align-items-center mb-4">
        <h5 class="mb-0">
          <i class="ti ti-file-description me-2"></i>
          Solicitud #${solicitud.id}
        </h5>
        <span class="badge bg-${solicitud.estado_class} fs-6">
          <i class="ti ti-${solicitud.estado === 'pendiente' ? 'clock' : (solicitud.estado === 'aprobado' ? 'check' : 'x')} me-1"></i>
          ${solicitud.estado_texto}
        </span>
      </div>
      
      <!-- Información básica -->
      <div class="row mb-4">
        <div class="col-md-6">
          <strong>Tipo de solicitud:</strong><br>
          <span class="badge bg-primary">${solicitud.tipo_texto}</span>
        </div>
        <div class="col-md-6">
          <strong>Empleado:</strong><br>
          ${solicitud.empleado_nombre}<br>
          <small class="text-muted">${solicitud.empleado_email}</small>
        </div>
      </div>
      
      <!-- Período/Duración -->
      <div class="row mb-3">
        ${periodoInfo}
        ${ausenciaInfo}
        ${archivoInfo}
      </div>
      
      <!-- Fechas del proceso -->
      <div class="row mb-3">
        <div class="col-md-6">
          <strong>Fecha de solicitud:</strong><br>
          ${formatearFechaHora(solicitud.fecha_solicitud)}
        </div>
        ${aprobadorInfo}
      </div>
      
      <!-- Comentarios -->
      ${comentariosSection}
    </div>
  `;
}

/**
 * Renderiza el historial de cambios de una solicitud
 */
function renderHistorialSolicitud(historial) {
  if (!historial || historial.length === 0) {
    return '';
  }
  
  const formatearFechaHora = (fechaHora) => {
    if (!fechaHora) return '-';
    return moment(fechaHora).format('DD/MM/YYYY HH:mm');
  };
  
  const getAccionTexto = (accion) => {
    const acciones = {
      'crear': 'Creó la solicitud',
      'aprobar': 'Aprobó la solicitud',
      'rechazar': 'Rechazó la solicitud',
      'editar': 'Editó la solicitud',
      'modificar': 'Modificó la solicitud',
      'eliminar': 'Eliminó la solicitud',
      'comentario': 'Agregó un comentario'
    };
    return acciones[accion] || accion;
  };
  
  const getAccionIcono = (accion) => {
    const iconos = {
      'crear': 'ti ti-plus',
      'aprobar': 'ti ti-check',
      'rechazar': 'ti ti-x',
      'editar': 'ti ti-edit',
      'modificar': 'ti ti-edit',
      'eliminar': 'ti ti-trash',
      'comentario': 'ti ti-message'
    };
    return iconos[accion] || 'ti ti-clock';
  };
  
  const getAccionColor = (accion) => {
    const colores = {
      'crear': 'info',
      'aprobar': 'success',
      'rechazar': 'danger',
      'editar': 'warning',
      'modificar': 'warning',
      'eliminar': 'danger',
      'comentario': 'secondary'
    };
    return colores[accion] || 'secondary';
  };
  
  let historialHTML = `
    <div class="mt-4">
      <h6 class="mb-3">
        <i class="ti ti-history me-2"></i>Historial de Cambios
      </h6>
      <div class="timeline">
  `;
  
  historial.forEach((item, index) => {
    const nombreCompleto = item.nombre && item.apellidos 
      ? `${item.nombre} ${item.apellidos}` 
      : 'Sistema';
    const rol = item.rol ? `(${item.rol})` : '';
    const icono = getAccionIcono(item.accion);
    const color = getAccionColor(item.accion);
    
    let detalles = '';
    if (item.campo_modificado) {
      detalles += `<div class="small text-muted mt-1"><strong>Campo:</strong> ${item.campo_modificado}</div>`;
    }
    if (item.valor_anterior) {
      detalles += `<div class="small text-muted"><strong>Valor anterior:</strong> ${item.valor_anterior}</div>`;
    }
    if (item.valor_nuevo) {
      detalles += `<div class="small text-muted"><strong>Valor nuevo:</strong> ${item.valor_nuevo}</div>`;
    }
    if (item.comentario) {
      detalles += `<div class="small mt-2"><em>"${item.comentario}"</em></div>`;
    }
    
    historialHTML += `
      <div class="timeline-item">
        <div class="timeline-marker bg-${color}">
          <i class="${icono}"></i>
        </div>
        <div class="timeline-content">
          <div class="d-flex justify-content-between align-items-start">
            <div>
              <strong>${nombreCompleto}</strong> ${rol}
              <div class="small text-muted">${getAccionTexto(item.accion)}</div>
              ${detalles}
            </div>
            <small class="text-muted text-nowrap ms-2">${formatearFechaHora(item.fecha)}</small>
          </div>
        </div>
      </div>
    `;
  });
  
  historialHTML += `
      </div>
    </div>
  `;
  
  return historialHTML;
}

/**
 * Inicializa los event listeners para la gestión avanzada de solicitudes
 * Solo se ejecuta si existen los elementos necesarios en la página
 */
function initAdminSolicitudes_real() {


  // Verificar si estamos en la página de administración de solicitudes
  const modalAprobacion = document.getElementById('modalAprobacion');
  const modalEliminar = document.getElementById('modalEliminar');
  
  
  // Si no estamos en ver-solicitudes.php, salir
  const enVerSolicitudes = window.location.pathname.includes('ver-solicitudes.php');
  if (!enVerSolicitudes && (!modalAprobacion || !modalEliminar)) {


    return; // No estamos en la página de ver-solicitudes
  }

  // Solo manejar botones de aprobar/rechazar si estamos fuera de ver-solicitudes (tienen modalAprobacion)
  if (modalAprobacion) {
    // Manejar botones de aprobar con modal
    document.querySelectorAll('.aprobar-btn').forEach(btn => {
      btn.addEventListener('click', function(e) {
        e.preventDefault();
        e.stopPropagation();
        
        const id = this.dataset.id;
        document.getElementById('solicitudId').value = id;
        document.getElementById('accionType').value = 'aprobar';
        document.getElementById('comentarioAdmin').value = ''; // Limpiar comentario
        document.getElementById('modalAprobacionTitle').textContent = 'Aprobar Solicitud';
        document.getElementById('btnConfirmarAccion').className = 'btn btn-success';
        document.getElementById('btnConfirmarAccion').textContent = 'Aprobar';
        new bootstrap.Modal(document.getElementById('modalAprobacion')).show();
      });
    });

    // Manejar botones de rechazar con modal
    document.querySelectorAll('.rechazar-btn').forEach(btn => {
      btn.addEventListener('click', function(e) {
        e.preventDefault();
        e.stopPropagation();
        
        const id = this.dataset.id;
        document.getElementById('solicitudId').value = id;
        document.getElementById('accionType').value = 'rechazar';
        document.getElementById('comentarioAdmin').value = ''; // Limpiar comentario
        document.getElementById('modalAprobacionTitle').textContent = 'Rechazar Solicitud';
        document.getElementById('btnConfirmarAccion').className = 'btn btn-danger';
        document.getElementById('btnConfirmarAccion').textContent = 'Rechazar';
        new bootstrap.Modal(document.getElementById('modalAprobacion')).show();
      });
    });

    // Manejar confirmación de acción (aprobar/rechazar)
    const btnConfirmarAccion = document.getElementById('btnConfirmarAccion');
    if (btnConfirmarAccion) {
      btnConfirmarAccion.addEventListener('click', function(e) {
        e.preventDefault();
        e.stopPropagation();
        
        const formData = new FormData(document.getElementById('formAprobacion'));
        
        // Cerrar el modal antes de enviar
        const modal = bootstrap.Modal.getInstance(document.getElementById('modalAprobacion'));
        if (modal) {
          modal.hide();
        }
        
        fetch(BASE_URL + 'acciones/aprobar-solicitud.php', {
          method: 'POST',
          body: formData
        })
       
        .then(response => response.json())
        .then(data => {
          if (data.success) {
            location.reload();
          } else {
            alert('Error: ' + (data.error || 'Error desconocido'));
          }
        })
        .catch(error => {
          console.error('Error:', error);
          alert('Error de red');
        });
      });
    }
  }

  // Manejar botones de ver detalles (disponible en ambos contextos)
  document.querySelectorAll('.ver-btn').forEach(btn => {
    btn.addEventListener('click', function() {
      const id = this.dataset.id;
      const modalBody = document.getElementById('modalDetalleBody');
      
      // Mostrar loading
      modalBody.innerHTML = `
        <div class="text-center py-4">
          <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">Cargando...</span>
          </div>
          <p class="mt-2">Cargando detalles de la solicitud...</p>
        </div>
      `;
      
      // Mostrar modal
      const modalDetalle = new bootstrap.Modal(document.getElementById('modalDetalle'));
      modalDetalle.show();
      
      // Cargar detalles via AJAX
      fetch(BASE_URL + 'acciones/obtener-detalle-solicitud.php?id=' + id)
        .then(response => response.json())
        .then(data => {
          if (data.success) {
            modalBody.innerHTML = renderDetallesSolicitud(data.solicitud) + renderHistorialSolicitud(data.historial);
            
            // Mostrar/ocultar botón de descargar archivo
            const btnDescargar = document.getElementById('btnDescargarArchivo');
            if (data.solicitud.archivo && data.solicitud.archivo.existe) {
              btnDescargar.href = BASE_URL + 'acciones/descargar-archivo.php?solicitud_id=' + id + '&archivo=' + encodeURIComponent(data.solicitud.archivo.nombre);
              btnDescargar.style.display = 'inline-block';
            } else {
              btnDescargar.style.display = 'none';
            }
          } else {
            // Si la solicitud no existe (fue eliminada), cerrar silenciosamente sin error
            if (data.error === 'Solicitud no encontrada') {
              console.warn('La solicitud fue eliminada');
              modalDetalle.hide();
            } else {
              modalBody.innerHTML = `
                <div class="alert alert-danger">
                  <i class="ti ti-alert-circle me-2"></i>
                  Error al cargar los detalles: ${data.error || 'Error desconocido'}
                </div>
              `;
            }
          }
        })
        .catch(error => {
          console.error('Error:', error);
          modalBody.innerHTML = `
            <div class="alert alert-danger">
              <i class="ti ti-alert-circle me-2"></i>
              Error de red al cargar los detalles de la solicitud
            </div>
          `;
        });
    });
  });

  // Manejar botones de eliminar
  if (modalEliminar) {
    document.querySelectorAll('.eliminar-btn').forEach(btn => {
      btn.addEventListener('click', function(e) {
        e.preventDefault();
        e.stopPropagation();
        
        const id = this.dataset.id;
        document.getElementById('solicitudEliminarId').value = id;
        new bootstrap.Modal(document.getElementById('modalEliminar')).show();
      });
    });

    // Manejar confirmación de eliminación
    const btnConfirmarEliminar = document.getElementById('btnConfirmarEliminar');
    if (btnConfirmarEliminar) {
      const existingListener = btnConfirmarEliminar.cloneNode(true);
      btnConfirmarEliminar.replaceWith(existingListener);
      
      existingListener.addEventListener('click', function(e) {
        e.preventDefault();
        e.stopPropagation();
        
        const solicitudId = document.getElementById('solicitudEliminarId').value;
        
        if (!solicitudId) {
          alert('Error: No se pudo obtener el ID de la solicitud');
          return;
        }
        
        // Cerrar el modal antes de enviar
        const modal = bootstrap.Modal.getInstance(document.getElementById('modalEliminar'));
        if (modal) {
          modal.hide();
        }
        
        // Crear FormData para enviar el ID
        const formData = new FormData();
        formData.append('solicitud_id', solicitudId);
        formData.append('accion', 'eliminar');
        
        fetch(BASE_URL + 'acciones/eliminar-solicitud.php', {
          method: 'POST',
          body: formData
        })
        .then(response => response.json())
        .then(data => {
          if (data.success) {
            // Mostrar mensaje de éxito y recargar página
            if (typeof toastr !== 'undefined') {
              toastr.success('Solicitud eliminada correctamente');
            } else {
              alert('Solicitud eliminada correctamente');
            }
            setTimeout(() => {
              location.reload();
            }, 1000);
          } else {
            alert('Error: ' + (data.error || 'Error desconocido'));
          }
        })
        .catch(error => {
          console.error('Error:', error);
          alert('Error de red al eliminar la solicitud');
        });
      });
    }
  }

  // Manejar botones de editar directamente desde la tabla (SOLO para vacaciones/bajas aprobadas)
  document.querySelectorAll('.editar-btn').forEach(btn => {
    if (!btn.hasAttribute('data-listener-added')) {
      btn.setAttribute('data-listener-added', 'true');
      
      btn.addEventListener('click', function(e) {
        e.preventDefault();
        e.stopPropagation();
        
        const solicitudId = this.dataset.id;
        
        if (!solicitudId) {
          alert('Error: No se pudo obtener el ID de la solicitud');
          return;
        }
        
        // Mostrar loading
        const btnOriginal = this;
        const textOriginal = btnOriginal.innerHTML;
        btnOriginal.disabled = true;
        btnOriginal.innerHTML = '<i class="ti ti-loader-2 spinner-border spinner-border-sm"></i>';
        
        // Obtener datos actuales de la solicitud
        fetch(BASE_URL + 'acciones/obtener-detalle-solicitud.php?id=' + solicitudId)
          .then(r => r.json())
          .then(data => {
            btnOriginal.disabled = false;
            btnOriginal.innerHTML = textOriginal;
            
            if (data.success) {
              const sol = data.solicitud;
              
              // Prellenar el formulario de edición
              document.getElementById('editarSolicitudId').value = solicitudId;
              document.getElementById('editarTipo').value = sol.tipo_texto || sol.tipo;
              document.getElementById('editarEmpleado').value = sol.empleado_nombre;
              document.getElementById('editarFechaInicio').value = sol.fecha_inicio;
              document.getElementById('editarFechaFin').value = sol.fecha_fin;
              document.getElementById('editarMedioDia').checked = sol.medio_dia == 1;
              
              if (sol.tipo === 'baja') {
                document.getElementById('grupoHoras').style.display = 'block';
                document.getElementById('editarHoras').value = sol.horas || '';
                document.getElementById('grupoMedioDia').style.display = 'none';
              } else {
                document.getElementById('grupoHoras').style.display = 'none';
                document.getElementById('grupoMedioDia').style.display = 'block';
              }
              
              document.getElementById('editarComentario').value = sol.comentario_admin || '';
              
              // Abrir modal de edición
              new bootstrap.Modal(document.getElementById('modalEditarSolicitud')).show();
            } else {
              if (typeof toastr !== 'undefined') {
                toastr.error(data.error || 'Error al cargar los datos');
              } else {
                alert(data.error || 'Error al cargar los datos');
              }
            }
          })
          .catch(error => {
            console.error('Error:', error);
            btnOriginal.disabled = false;
            btnOriginal.innerHTML = textOriginal;
            
            if (typeof toastr !== 'undefined') {
              toastr.error('Error al cargar los datos de la solicitud');
            } else {
              alert('Error al cargar los datos de la solicitud');
            }
          });
      });
    }
  });

  // Manejar guardado de cambios en edición
  const btnGuardarEdicion = document.getElementById('btnGuardarEdicion');
  if (btnGuardarEdicion && !btnGuardarEdicion.hasAttribute('data-listener-added')) {
    btnGuardarEdicion.setAttribute('data-listener-added', 'true');
    
    btnGuardarEdicion.addEventListener('click', function() {
      const solicitudId = document.getElementById('editarSolicitudId').value;
      const fechaInicio = document.getElementById('editarFechaInicio').value;
      const fechaFin = document.getElementById('editarFechaFin').value;
      const medioDia = document.getElementById('editarMedioDia').checked ? 1 : 0;
      const horas = document.getElementById('editarHoras').value;
      const comentario = document.getElementById('editarComentario').value;
      
      if (!solicitudId || !fechaInicio || !fechaFin) {
        if (typeof toastr !== 'undefined') {
          toastr.warning('Por favor completa todos los campos requeridos');
        } else {
          alert('Por favor completa todos los campos requeridos');
        }
        return;
      }
      
      // Validar que fecha inicio <= fecha fin
      if (new Date(fechaInicio) > new Date(fechaFin)) {
        if (typeof toastr !== 'undefined') {
          toastr.warning('La fecha de inicio no puede ser posterior a la fecha de fin');
        } else {
          alert('La fecha de inicio no puede ser posterior a la fecha de fin');
        }
        return;
      }
      
      // Mostrar loading
      this.disabled = true;
      const textOriginal = this.innerHTML;
      this.innerHTML = '<i class="ti ti-loader-2 spinner-border spinner-border-sm me-1"></i>Guardando...';
      const btnSave = this;
      
      // Crear FormData para enviar con archivo
      const formData = new FormData();
      formData.append('id', solicitudId);
      formData.append('fecha_inicio', fechaInicio);
      formData.append('fecha_fin', fechaFin);
      formData.append('medio_dia', medioDia);
      formData.append('horas', horas || '');
      formData.append('comentario_admin', comentario);
      
      // Agregar archivo si existe
      const archivoInput = document.getElementById('editarArchivo');
      if (archivoInput && archivoInput.files && archivoInput.files.length > 0) {
        formData.append('archivo', archivoInput.files[0]);
      }
      
      // Enviar cambios al servidor
      fetch(BASE_URL + 'acciones/editar-solicitud.php', {
        method: 'POST',
        body: formData
      })
      .then(r => r.json())
      .then(data => {
        btnSave.disabled = false;
        btnSave.innerHTML = textOriginal;
        
        if (data.success) {
          // Mostrar notificación de éxito PROMINENTE
          if (typeof toastr !== 'undefined') {
            toastr.options.timeOut = 10000; // 10 segundos
            toastr.options.positionClass = 'toast-top-center';
            toastr.success('✓ Cambios guardados correctamente', 'Solicitud actualizada', {
              timeOut: 10000
            });
          }
          
          // Cerrar modal de edición
          const modal = bootstrap.Modal.getInstance(document.getElementById('modalEditarSolicitud'));
          if (modal) modal.hide();
          
          // Obtener los datos actualizados para mostrar
          fetch(BASE_URL + 'acciones/obtener-detalle-solicitud.php?id=' + solicitudId)
            .then(r => r.json())
            .then(dataActualizado => {
              if (dataActualizado.success) {
                const sol = dataActualizado.solicitud;
                
                // Actualizar el HTML del modal de detalles
                const modalBody = document.getElementById('modalDetalleBody');
                modalBody.innerHTML = renderDetallesSolicitud(sol);
                
                // Mostrar modal con datos actualizados
                setTimeout(() => {
                  new bootstrap.Modal(document.getElementById('modalDetalle')).show();
                }, 500);
              }
            });
          
          // Recargar tabla en background después de 5 segundos
          setTimeout(() => {
            location.reload();
          }, 5000);
        } else {
          if (typeof toastr !== 'undefined') {
            toastr.error(data.error || 'Error al actualizar la solicitud', 'Error');
          } else {
            alert('Error: ' + (data.error || 'Error desconocido'));
          }
        }
      })
      .catch(error => {
        console.error('Error:', error);
        btnSave.disabled = false;
        btnSave.innerHTML = textOriginal;
        
        if (typeof toastr !== 'undefined') {
          toastr.error('Error de conexión al servidor', 'Error de red');
        } else {
          alert('Error de red');
        }
      });
    });
  }
  
  // --- FUNCIONALIDAD PARA CREAR SOLICITUD GENÉRICA ---
  const btnCrearSolicitud = document.getElementById('btnCrearSolicitud');
  const modalCrearBaja = document.getElementById('modalCrearBajaMedica');
  const btnGuardarBajaMedica = document.getElementById('btnGuardarBajaMedica');
  const crearTipoSolicitud = document.getElementById('crearTipoSolicitud');
  
  if (btnCrearSolicitud && !btnCrearSolicitud.hasAttribute('data-listener-added')) {
    btnCrearSolicitud.setAttribute('data-listener-added', 'true');
    btnCrearSolicitud.addEventListener('click', function() {
      // Cargar lista de empleados
      fetch(BASE_URL + 'acciones/obtener-empleados.php')
        .then(r => r.json())
        .then(data => {
          if (data.success) {
            const select = document.getElementById('crearEmpleadoId');
            select.innerHTML = '<option value="">-- Seleccionar empleado --</option>';
            
            data.empleados.forEach(emp => {
              const option = document.createElement('option');
              option.value = emp.id;
              option.textContent = emp.nombre + ' ' + emp.apellidos;
              select.appendChild(option);
            });
            
            // Limpiar formulario
            document.getElementById('formCrearBajaMedica').reset();
            if (crearTipoSolicitud) crearTipoSolicitud.value = 'baja';
            
            // Abrir modal
            new bootstrap.Modal(modalCrearBaja).show();
          } else {
            if (typeof toastr !== 'undefined') {
              toastr.error('Error al cargar empleados', 'Error');
            } else {
              alert('Error al cargar empleados');
            }
          }
        })
        .catch(error => {
          console.error('Error:', error);
          if (typeof toastr !== 'undefined') {
            toastr.error('Error de conexión', 'Error');
          } else {
            alert('Error de conexión');
          }
        });
    });
  }
  
  // Mostrar campo de horas cuando se activa medio día
  const crearMedioDia = document.getElementById('crearMedioDia');
  if (crearMedioDia) {
    crearMedioDia.addEventListener('change', function() {
      const grupoHoras = document.getElementById('crearGrupoHoras');
      if (this.checked) {
        grupoHoras.style.display = 'block';
      } else {
        grupoHoras.style.display = 'none';
      }
    });
  }
  
  /**
   * Detecta conflictos de solicitudes aprobadas
   * @param {string} empleadoId - ID del empleado
   * @param {string} tipo - Tipo de solicitud
   * @param {string} fechaInicio - Fecha inicio (YYYY-MM-DD)
   * @param {string} fechaFin - Fecha fin (YYYY-MM-DD)
   * @returns {Promise<Object>} {hasConflict, message, conflictType, conflictingSolicitudes}
   */
  async function verificarConflictos(empleadoId, tipo, fechaInicio, fechaFin) {
    try {
      const response = await fetch(BASE_URL + 'acciones/verificar-conflictos.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json'
        },
        body: JSON.stringify({
          empleado_id: empleadoId,
          tipo: tipo,
          fecha_inicio: fechaInicio,
          fecha_fin: fechaFin
        })
      });
      
      const data = await response.json();
      return data;
    } catch (error) {
      console.error('Error verificar conflictos:', error);
      return { hasConflict: false, error: true };
    }
  }
  
  // Guardar solicitud
  if (btnGuardarBajaMedica && !btnGuardarBajaMedica.hasAttribute('data-listener-added')) {
    btnGuardarBajaMedica.setAttribute('data-listener-added', 'true');
    
    btnGuardarBajaMedica.addEventListener('click', async function() {
      const empleadoId = document.getElementById('crearEmpleadoId').value;
      const tipo = crearTipoSolicitud ? crearTipoSolicitud.value : 'baja';
      const fechaInicio = document.getElementById('crearFechaInicio').value;
      const fechaFin = document.getElementById('crearFechaFin').value;
      const medioDia = document.getElementById('crearMedioDia').checked ? 1 : 0;
      const horas = document.getElementById('crearHoras').value;
      const comentario = document.getElementById('crearComentario').value;
      
      // Validaciones
      if (!empleadoId || !tipo || !fechaInicio || !fechaFin) {
        if (typeof toastr !== 'undefined') {
          toastr.warning('Por favor completa todos los campos requeridos');
        } else {
          alert('Por favor completa todos los campos requeridos');
        }
        return;
      }
      
      if (new Date(fechaInicio) > new Date(fechaFin)) {
        if (typeof toastr !== 'undefined') {
          toastr.warning('La fecha de inicio no puede ser posterior a la fecha de fin');
        } else {
          alert('La fecha de inicio no puede ser posterior a la fecha de fin');
        }
        return;
      }
      
      // Verificar conflictos
      this.disabled = true;
      const textOriginal = this.innerHTML;
      this.innerHTML = '<i class="ti ti-loader-2 spinner-border spinner-border-sm me-1"></i>Verificando...';
      const btnSave = this;
      
      const conflictResult = await verificarConflictos(empleadoId, tipo, fechaInicio, fechaFin);
      
      if (conflictResult.hasConflict) {
        btnSave.disabled = false;
        btnSave.innerHTML = textOriginal;
        
        if (typeof toastr !== 'undefined') {
          toastr.warning(conflictResult.message, 'Conflicto de fechas');
        } else {
          alert('Conflicto: ' + conflictResult.message);
        }
        return;
      }
      
      // Si todo es válido, continuar con la creación
      this.innerHTML = '<i class="ti ti-loader-2 spinner-border spinner-border-sm me-1"></i>Creando...';
      
      // Crear FormData para enviar con archivo
      const formData = new FormData();
      formData.append('empleado_id', empleadoId);
      formData.append('tipo', tipo);
      formData.append('fecha_inicio', fechaInicio);
      formData.append('fecha_fin', fechaFin);
      formData.append('medio_dia', medioDia);
      formData.append('horas', horas || '');
      formData.append('comentario_admin', comentario);
      
      // Agregar archivo si existe
      const archivoInput = document.getElementById('crearArchivo');
      if (archivoInput && archivoInput.files && archivoInput.files.length > 0) {
        formData.append('archivo', archivoInput.files[0]);
      }
      
      // Enviar solicitud al servidor
      fetch(BASE_URL + 'acciones/crear-solicitud.php', {
        method: 'POST',
        body: formData
      })
      .then(r => r.json())
      .then(data => {
        btnSave.disabled = false;
        btnSave.innerHTML = textOriginal;
        
        if (data.success) {
          // Mostrar notificación de éxito
          if (typeof toastr !== 'undefined') {
            toastr.success('✓ Solicitud creada correctamente', 'Éxito', {
              timeOut: 10000
            });
          }
          
          // Cerrar modal
          const modal = bootstrap.Modal.getInstance(modalCrearBaja);
          if (modal) modal.hide();
          
          // Recargar tabla después de 2 segundos
          setTimeout(() => {
            location.reload();
          }, 2000);
        } else {
          if (typeof toastr !== 'undefined') {
            toastr.error(data.error || 'Error al crear la solicitud', 'Error');
          } else {
            alert('Error: ' + (data.error || 'Error desconocido'));
          }
        }
      })
      .catch(error => {
        console.error('Error:', error);
        btnSave.disabled = false;
        btnSave.innerHTML = textOriginal;
        
        if (typeof toastr !== 'undefined') {
          toastr.error('Error de conexión al servidor', 'Error de red');
        } else {
          alert('Error de red');
        }
      });
    });
  }
  // --- FIN FUNCIONALIDAD CREAR SOLICITUD ---
}

// Inicializar gestión de solicitudes cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', function() {
  // Ejecutar initAdminSolicitudes_real si estamos en ver-solicitudes.php
  if (window.location.pathname.includes('ver-solicitudes.php')) {


    initAdminSolicitudes_real();
  } else {
    // En otras páginas, ejecutar normalmente
    initAdminSolicitudes_real();
  }
  
  // Inicializar filtros de administración: solicitudes y empleados
  initAdminFilters();
  initAdminFilters('empleados');
});

// Exponer función globalmente también
window.initAdminSolicitudes_real = initAdminSolicitudes_real;

/**
 * Inicializa la funcionalidad de filtros para la página de administración
 * @param {string} tipo - Tipo de página ('solicitudes' o 'empleados')
 */
function initAdminFilters(tipo = 'solicitudes') {
  // Manejar el formulario de filtros
  const filtroForm = document.querySelector('form[method="GET"]');
  const btnFiltrar = filtroForm?.querySelector('button[type="submit"]');
  const btnLimpiar = document.getElementById('btnLimpiarFiltros'); // Versión desktop
  const btnLimpiarMobile = document.getElementById('btnLimpiarFiltrosMobile'); // Versión mobile
  
  // Debug solo si hay parámetro debug
  if (window.location.search.includes('debug=1')) {
    // Debug mode enabled via URL parameter
  }
  
  // Prevenir envío duplicado del formulario
  if (btnFiltrar) {
    btnFiltrar.addEventListener('click', function(e) {
      // El formulario se enviará automáticamente por ser tipo submit
      this.disabled = true;
      this.innerHTML = '<i class="ti ti-loader-2 spin"></i> <span class="d-none d-lg-inline">Filtrando...</span>';
      
      // Re-habilitar el botón después de un tiempo en caso de error
      setTimeout(() => {
        this.disabled = false;
        this.innerHTML = '<i class="ti ti-search me-1"></i> <span class="d-none d-lg-inline">Filtrar</span>';
      }, 5000);
    });
  }
  
  // Función para limpiar filtros (reutilizable)
  function limpiarFiltros(e) {
    e.preventDefault();
    
    // Limpiar todos los campos del formulario
    if (filtroForm) {
      filtroForm.querySelectorAll('input, select').forEach(field => {
        if (field.name === 'page') return; // No limpiar el campo page
        if (field.name === 'pagina') {
          field.value = '1'; // Resetear a página 1
          return;
        }
        if (field.type === 'date' || field.type === 'text') {
          field.value = '';
        } else if (field.tagName === 'SELECT') {
          field.selectedIndex = 0;
        }
      });
      
      // Obtener la URL actual manteniendo solo el parámetro page
      const url = new URL(window.location);
      const pageParam = url.searchParams.get('page');
      
      // Construir URL limpia
      if (pageParam) {
        window.location.href = `${url.origin}${url.pathname}?page=${pageParam}`;
      } else {
        window.location.href = `${url.origin}${url.pathname}`;
      }
    }
  }
  
  // Manejar botón limpiar desktop
  if (btnLimpiar) {
    btnLimpiar.addEventListener('click', limpiarFiltros);
  }
  
  // Manejar botón limpiar mobile
  if (btnLimpiarMobile) {
    btnLimpiarMobile.addEventListener('click', limpiarFiltros);
  }
  
  // Configuración específica por tipo de página
  if (tipo === 'solicitudes') {
    // Auto-enviar formulario al cambiar filtros de estado y tipo
    if (filtroForm) {
      const autoSubmitFields = filtroForm.querySelectorAll('select[name="estado"], select[name="tipo"]');
      autoSubmitFields.forEach(field => {
        field.addEventListener('change', function() {
          // Auto-enviar cuando cambia estado o tipo
          filtroForm.submit();
        });
      });
      
      // Funcionalidad para tecla Enter en campo de empleado
      const empleadoInput = filtroForm.querySelector('input[name="empleado"]');
      if (empleadoInput) {
        empleadoInput.addEventListener('keypress', function(e) {
          if (e.key === 'Enter') {
            e.preventDefault();
            filtroForm.submit();
          }
        });
      }
    }
  } else if (tipo === 'empleados') {
    // Auto-enviar formulario al cambiar filtro de rol
    if (filtroForm) {
      const rolSelect = filtroForm.querySelector('select[name="rol"]');
      if (rolSelect) {
        rolSelect.addEventListener('change', function() {
          filtroForm.submit();
        });
      }
      
      // Funcionalidad para tecla Enter en campo de nombre
      const nombreInput = filtroForm.querySelector('input[name="nombre"]');
      if (nombreInput) {
        nombreInput.addEventListener('keypress', function(e) {
          if (e.key === 'Enter') {
            e.preventDefault();
            filtroForm.submit();
          }
        });
      }
    }
  }
}




