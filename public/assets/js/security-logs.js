// Gestión de logs de seguridad
let currentPage = 1;
const limit = 25;
let totalPages = 1;

// Función para cargar los logs
function cargarLogs(page = 1, filtros = {}) {
    currentPage = page;
    const spinner = document.getElementById('logsSpinner');
    const tabla = document.getElementById('tablaLogs');
    const paginacion = document.getElementById('logsPaginacion');
    
    if (spinner) spinner.style.display = 'block';
    if (tabla) tabla.style.opacity = '0.5';

    // Construir URL con filtros
    let url = `obtener-logs-seguridad.php?page=${page}&limit=${limit}`;
    Object.keys(filtros).forEach(key => {
        if (filtros[key]) {
            url += `&${key}=${encodeURIComponent(filtros[key])}`;
        }
    });

    fetch(url)
        .then(response => response.json())
        .then(data => {
            if (spinner) spinner.style.display = 'none';
            if (tabla) tabla.style.opacity = '1';

            const tbody = document.getElementById('logsTableBody');
            if (!tbody) return;

            tbody.innerHTML = '';
            totalPages = data.pagination.pages;

            data.logs.forEach(log => {
                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td>${log.fecha_hora}</td>
                    <td>
                        <span class="badge bg-${getBadgeClass(log.resultado)}">
                            ${log.resultado.toUpperCase()}
                        </span>
                    </td>
                    <td>${log.accion}</td>
                    <td>${log.empleado_nombre || 'Sistema'}</td>
                    <td>
                        <button class="btn btn-sm btn-outline-info" 
                                onclick="mostrarDetallesLog(${JSON.stringify(log).replace(/"/g, '&quot;')})">
                            <i class="bi bi-info-circle"></i>
                        </button>
                    </td>
                `;
                tbody.appendChild(tr);
            });

            // Actualizar paginación
            actualizarPaginacion(data.pagination);
        })
        .catch(error => {
            console.error('Error:', error);
            if (spinner) spinner.style.display = 'none';
            if (tabla) tabla.style.opacity = '1';
        });
}

// Función para actualizar la paginación
function actualizarPaginacion(paginacion) {
    const paginacionElement = document.getElementById('logsPaginacion');
    if (!paginacionElement) return;

    let html = '';
    
    // Botón anterior
    html += `
        <li class="page-item ${paginacion.current === 1 ? 'disabled' : ''}">
            <a class="page-link" href="#" onclick="cargarLogs(${paginacion.current - 1}); return false;">
                <i class="bi bi-chevron-left"></i>
            </a>
        </li>
    `;

    // Páginas
    for (let i = Math.max(1, paginacion.current - 2); i <= Math.min(paginacion.pages, paginacion.current + 2); i++) {
        html += `
            <li class="page-item ${i === paginacion.current ? 'active' : ''}">
                <a class="page-link" href="#" onclick="cargarLogs(${i}); return false;">${i}</a>
            </li>
        `;
    }

    // Botón siguiente
    html += `
        <li class="page-item ${paginacion.current === paginacion.pages ? 'disabled' : ''}">
            <a class="page-link" href="#" onclick="cargarLogs(${paginacion.current + 1}); return false;">
                <i class="bi bi-chevron-right"></i>
            </a>
        </li>
    `;

    paginacionElement.innerHTML = html;
}

// Función para obtener la clase del badge según el resultado
function getBadgeClass(resultado) {
    switch (resultado.toLowerCase()) {
        case 'success': return 'success';
        case 'warning': return 'warning';
        case 'error': return 'danger';
        default: return 'info';
    }
}

// Función para mostrar detalles del log
function mostrarDetallesLog(log) {
    const modal = new bootstrap.Modal(document.getElementById('modalDetallesLog'));
    
    document.getElementById('logFecha').textContent = log.fecha_hora;
    document.getElementById('logAccion').textContent = log.accion;
    document.getElementById('logEmpleado').textContent = log.empleado_nombre || 'Sistema';
    document.getElementById('logDetalles').textContent = log.detalles || 'Sin detalles';
    document.getElementById('logIP').textContent = log.ip_address || 'N/A';
    document.getElementById('logUserAgent').textContent = log.user_agent || 'N/A';
    
    const resultadoBadge = document.getElementById('logResultado');
    resultadoBadge.className = `badge bg-${getBadgeClass(log.resultado)}`;
    resultadoBadge.textContent = log.resultado.toUpperCase();

    modal.show();
}

// Función para aplicar filtros
function aplicarFiltros() {
    const filtros = {
        resultado: document.getElementById('filtroResultado').value,
        desde: document.getElementById('filtroDesde').value,
        hasta: document.getElementById('filtroHasta').value,
        empleado_id: document.getElementById('filtroEmpleado').value,
        accion: document.getElementById('filtroAccion').value
    };

    cargarLogs(1, filtros);
}

// Cargar logs al iniciar
document.addEventListener('DOMContentLoaded', () => {
    cargarLogs();
});
