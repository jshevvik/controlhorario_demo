// /assets/js/geolocalizacion.js
// Aplicación moderna de geolocalización y fichaje

// Aplicación de Geolocalización
if (!window.GeoApp) {
  class GeoApp {
    constructor() {
        this.config = window.GEO_CONFIG;
        this.map = null;
        this.userMarker = null;
        this.officeMarker = null;
        this.accuracyCircle = null;
        this.watchId = null;
        this.isInRange = false;
        this.hasFichado = false;
        this.maxAccuracy = 100; // metros
        
        this.initElements();
        this.initMap();
        this.startGeolocation();
    }

    initElements() {
        this.panelUbicacion = document.getElementById('panelUbicacion');
        
        // Verificar que el elemento existe
        if (!this.panelUbicacion) {
            console.error('❌ Panel de ubicación no encontrado');
            throw new Error('No se puede inicializar: panel de ubicación faltante');
        }
        

    }

    initMap() {
        try {
            // Verificar que Leaflet está disponible
            if (typeof L === 'undefined') {
                console.error('Leaflet no está disponible');
                setTimeout(() => this.initMap(), 1000);
                return;
            }

            // Inicializar mapa centrado en la oficina
            this.map = L.map('mapa').setView([
                this.config.oficina.lat, 
                this.config.oficina.lng
            ], 16);

            // Añadir capa de tiles
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 19,
                attribution: '© OpenStreetMap contributors'
            }).addTo(this.map);

            // Marcador de oficina
            this.officeMarker = L.marker([this.config.oficina.lat, this.config.oficina.lng])
                .addTo(this.map)
                .bindPopup(`<strong>${this.config.oficina.nombre}</strong><br>Ubicación de la oficina`)
                .openPopup();


        } catch (error) {
            console.error('❌ Error inicializando mapa:', error);
            this.showError('Error al cargar el mapa');
        }
    }

    startGeolocation() {
        if (!navigator.geolocation) {
            this.showError('Tu navegador no soporta geolocalización');
            return;
        }


        
        const options = {
            enableHighAccuracy: true,
            timeout: 30000,
            maximumAge: 10000
        };

        this.watchId = navigator.geolocation.watchPosition(
            (position) => this.onLocationSuccess(position),
            (error) => this.onLocationError(error),
            options
        );
    }

    onLocationSuccess(position) {
        const { latitude: lat, longitude: lng, accuracy } = position.coords;
        

        // Actualizar mapa
        this.updateMap(lat, lng, accuracy);

        // Actualizar UI
        this.updateLocationPanel(lat, lng, accuracy);
    }

    onLocationError(error) {
        let message = 'Error desconocido';
        
        switch(error.code) {
            case error.PERMISSION_DENIED:
                message = 'Acceso a la ubicación denegado. Por favor, permite el acceso en tu navegador.';
                break;
            case error.POSITION_UNAVAILABLE:
                message = 'Información de ubicación no disponible. Verifica tu conexión GPS.';
                break;
            case error.TIMEOUT:
                message = 'Tiempo de espera agotado. Intenta de nuevo.';
                break;
        }

        console.error('❌ Error de geolocalización:', message);
        this.showError(message, true);
    }

    calculateDistance(lat1, lng1, lat2, lng2) {
        const R = 6371000; // Radio de la Tierra en metros
        const dLat = (lat2 - lat1) * Math.PI / 180;
        const dLng = (lng2 - lng1) * Math.PI / 180;
        const a = Math.sin(dLat/2) * Math.sin(dLat/2) +
                  Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) *
                  Math.sin(dLng/2) * Math.sin(dLng/2);
        const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));
        return R * c;
    }

    updateMap(lat, lng, accuracy) {
        // Actualizar marcador del usuario
        if (this.userMarker) {
            this.userMarker.setLatLng([lat, lng]);
        } else {
            this.userMarker = L.marker([lat, lng], {
                icon: L.divIcon({
                    className: 'user-location-marker',
                    html: '<div style="background: #007bff; width: 20px; height: 20px; border-radius: 50%; border: 3px solid white; box-shadow: 0 2px 10px rgba(0,0,0,0.3);"></div>',
                    iconSize: [20, 20],
                    iconAnchor: [10, 10]
                })
            }).addTo(this.map);
        }

        // Círculo de precisión
        if (this.accuracyCircle) {
            this.accuracyCircle.setLatLng([lat, lng]).setRadius(accuracy);
        } else {
            this.accuracyCircle = L.circle([lat, lng], {
                radius: accuracy,
                color: '#007bff',
                fillColor: '#007bff',
                fillOpacity: 0.1,
                weight: 1
            }).addTo(this.map);
        }

        // Centrar mapa en ubicación del usuario si es precisa
        if (accuracy <= this.maxAccuracy) {
            this.map.setView([lat, lng], 17);
        }
    }

    updateLocationPanel(lat, lng, accuracy) {
        const isAccurate = accuracy <= this.maxAccuracy;
        
        // Obtener dirección usando geocoding inverso
        this.getAddressFromCoords(lat, lng).then(address => {
            // Detectar si es móvil para ajustar el layout
            const isMobile = window.innerWidth < 768;
            
            this.panelUbicacion.innerHTML = `
                <div class="mb-3">
                    <div class="info-metric">
                        <div class="metric-icon text-info">
                            <i class="bi bi-geo"></i>
                        </div>
                        <div class="small text-muted">Dirección</div>
                        <div class="fw-bold small text-break">${address}</div>
                    </div>
                </div>
                <div class="row g-2">
                    <div class="col-6 col-md-4">
                        <div class="info-metric">
                            <div class="metric-icon text-primary">
                                <i class="bi bi-geo-alt"></i>
                            </div>
                            <div class="small text-muted">Latitud</div>
                            <div class="fw-bold small">${isMobile ? lat.toFixed(3) : lat.toFixed(6)}</div>
                        </div>
                    </div>
                    <div class="col-6 col-md-4">
                        <div class="info-metric">
                            <div class="metric-icon text-warning">
                                <i class="bi bi-geo"></i>
                            </div>
                            <div class="small text-muted">Longitud</div>
                            <div class="fw-bold small">${isMobile ? lng.toFixed(3) : lng.toFixed(6)}</div>
                        </div>
                    </div>
                    <div class="col-12 col-md-4">
                        <div class="info-metric">
                            <div class="metric-icon text-${isAccurate ? 'success' : 'warning'}">
                                <i class="bi bi-bullseye"></i>
                            </div>
                            <div class="small text-muted">Precisión</div>
                            <div class="fw-bold small">±${Math.round(accuracy)}m</div>
                        </div>
                    </div>
                </div>
                <div class="mt-2">
                    <div class="alert alert-info py-2 mb-0">
                        <small>
                            ${isAccurate 
                                ? 'La geolocalización se registrará automáticamente en tus fichajes' 
                                : 'Precisión baja - la ubicación puede no ser exacta'
                            }
                        </small>
                    </div>
                </div>
            `;
        });
    }

    showError(message, showRetry = false) {
        const retryButton = showRetry ? `
            <button class="btn btn-warning btn-sm mt-2" onclick="location.reload()">
                <i class="bi bi-arrow-clockwise me-1"></i>
                Reintentar
            </button>
        ` : '';

        this.panelUbicacion.innerHTML = `
            <div class="text-center py-4">
                <i class="bi bi-exclamation-triangle text-danger fs-1 mb-3"></i>
                <h6 class="text-danger">Error de Geolocalización</h6>
                <p class="text-muted mb-3">${message}</p>
                ${retryButton}
            </div>
        `;
    }

    async getAddressFromCoords(lat, lng) {
        try {
            const response = await fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lng}&zoom=18&addressdetails=1&extratags=1`);
            const data = await response.json();
            
            if (data && data.display_name) {
                // Formatear la dirección para que sea más legible
                const parts = [];
                if (data.address) {
                    if (data.address.house_number && data.address.road) {
                        parts.push(`${data.address.road} ${data.address.house_number}`);
                    } else if (data.address.road) {
                        parts.push(data.address.road);
                    }
                    
                    if (data.address.suburb || data.address.neighbourhood) {
                        parts.push(data.address.suburb || data.address.neighbourhood);
                    }
                    
                    if (data.address.city || data.address.town || data.address.village) {
                        parts.push(data.address.city || data.address.town || data.address.village);
                    }
                    
                    if (data.address.state) {
                        parts.push(data.address.state);
                    }
                }
                
                return parts.length > 0 ? parts.join(', ') : data.display_name;
            }
            
            return `${lat.toFixed(6)}, ${lng.toFixed(6)}`;
        } catch (error) {
            console.warn('No se pudo obtener la dirección:', error);
            return `${lat.toFixed(6)}, ${lng.toFixed(6)}`;
        }
    }

    destroy() {
        if (this.watchId) {
            navigator.geolocation.clearWatch(this.watchId);
        }
    }
}

// Inicializar aplicación cuando esté listo el DOM
document.addEventListener('DOMContentLoaded', () => {
    // Verificar que tenemos la configuración necesaria
    if (typeof window.GEO_CONFIG === 'undefined') {
        console.error('❌ Configuración de geolocalización no disponible');
        return;
    }



    
    try {
        if (!window.geoApp) {
            window.geoApp = new GeoApp();
        }
    } catch (error) {
        console.error('❌ Error inicializando GeoApp:', error);
        
        // Mostrar error en la interfaz si es posible
        const errorContainer = document.getElementById('panelUbicacion');
        if (errorContainer) {
            errorContainer.innerHTML = `
                <div class="alert alert-danger" role="alert">
                    <h6>Error de inicialización</h6>
                    <p class="mb-0">${error.message}</p>
                    <button class="btn btn-warning btn-sm mt-2" onclick="location.reload()">
                        Reintentar
                    </button>
                </div>
            `;
        }
    }
});

// Limpiar al salir
window.addEventListener('beforeunload', () => {
    if (window.geoApp) {
        window.geoApp.destroy();
    }
});

// ========================================================================
// FUNCIONES ADICIONALES DE GEOLOCALIZACIÓN
// ========================================================================

/**
 * Obtiene la dirección de la oficina usando OpenStreetMap
 */
async function obtenerDireccionOficina() {
    const direccionElement = document.getElementById('direccionOficina');
    
    if (direccionElement && window.GEO_CONFIG && window.GEO_CONFIG.oficina) {
        try {
            const lat = window.GEO_CONFIG.oficina.lat;
            const lng = window.GEO_CONFIG.oficina.lng;
            
            const response = await fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lng}&zoom=18&addressdetails=1&extratags=1`);
            const data = await response.json();
            
            if (data && data.display_name) {
                // Formatear la dirección para que sea más legible
                const parts = [];
                if (data.address) {
                    if (data.address.house_number && data.address.road) {
                        parts.push(`${data.address.road} ${data.address.house_number}`);
                    } else if (data.address.road) {
                        parts.push(data.address.road);
                    }
                    
                    if (data.address.suburb || data.address.neighbourhood) {
                        parts.push(data.address.suburb || data.address.neighbourhood);
                    }
                    
                    if (data.address.city || data.address.town || data.address.village) {
                        parts.push(data.address.city || data.address.town || data.address.village);
                    }
                    
                    if (data.address.state) {
                        parts.push(data.address.state);
                    }
                }
                
                const direccion = parts.length > 0 ? parts.join(', ') : data.display_name;
                direccionElement.innerHTML = direccion;
            } else {
                direccionElement.innerHTML = `${lat.toFixed(6)}, ${lng.toFixed(6)}`;
            }
        } catch (error) {
            console.warn('No se pudo obtener la dirección de la oficina:', error);
            direccionElement.innerHTML = `${window.GEO_CONFIG.oficina.lat.toFixed(6)}, ${window.GEO_CONFIG.oficina.lng.toFixed(6)}`;
        }
    }
}

/**
 * Inicialización de funciones adicionales cuando el DOM está listo
 */
document.addEventListener('DOMContentLoaded', function() {
    // Obtener dirección de la oficina
    obtenerDireccionOficina();
    
    // Debug: verificar variables disponibles
    if (typeof empleadoId !== 'undefined') {
        // Variables initialized
    }

    if (window.GEO_CONFIG) {

    }
});
} // Cierre del if (!window.GeoApp)
