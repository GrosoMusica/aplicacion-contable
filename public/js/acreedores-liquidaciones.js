/**
 * Acreedores Liquidaciones JS
 * Script para el manejo de la vista de liquidaciones de acreedores
 */

document.addEventListener('DOMContentLoaded', function() {
    // Para cada acreedor (NO admin), configurar la interacción del modal de liquidación
    const checkboxesSinComprobante = document.querySelectorAll('[id^="sinComprobante"]');
    
    checkboxesSinComprobante.forEach(checkbox => {
        const acreedorId = checkbox.id.replace('sinComprobante', '');
        const fileInput = document.getElementById('comprobante' + acreedorId);
        const areaComprobante = document.getElementById('areaComprobante' + acreedorId);
        const mensajeSinComprobante = document.getElementById('mensajeSinComprobante' + acreedorId);
        
        function actualizarVisibilidad() {
            if (checkbox.checked) {
                if (areaComprobante) areaComprobante.style.display = 'none';
                if (mensajeSinComprobante) mensajeSinComprobante.style.display = 'block';
                if (fileInput) {
                    fileInput.removeAttribute('required');
                    fileInput.value = '';
                }
            } else {
                if (areaComprobante) areaComprobante.style.display = 'block';
                if (mensajeSinComprobante) mensajeSinComprobante.style.display = 'none';
                if (fileInput) {
                    fileInput.setAttribute('required', 'required');
                }
            }
        }
        
        actualizarVisibilidad();
        checkbox.addEventListener('change', actualizarVisibilidad);
    });
    
    // Inicializar tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl)
    });

    // Lógica para el desplegable de filtro de acreedores
    const selectAcreedor = document.getElementById('selectAcreedor');
    const acreedorCards = document.querySelectorAll('.acreedor-card');

    if (selectAcreedor && acreedorCards.length > 0) {
        selectAcreedor.addEventListener('change', function() {
            const selectedAcreedorId = this.value;

            acreedorCards.forEach(card => {
                // Mostrar todas las tarjetas si se selecciona "Mostrar Todos" o no hay selección válida
                if (selectedAcreedorId === "" || selectedAcreedorId === "todos") {
                    card.style.display = ''; 
                } else {
                    // Mostrar solo la tarjeta del acreedor seleccionado
                    if (card.dataset.acreedorId === selectedAcreedorId) {
                        card.style.display = '';
                    } else {
                        card.style.display = 'none';
                    }
                }
            });
        });
    }
});

// Función para ver comprobantes
function verComprobante(path) {
    // Usamos el controlador dedicado para ver comprobantes
    const url = '/comprobantes/ver?path=' + encodeURIComponent(path);
    
    // Abrir en una nueva ventana o modal
    window.open(url, '_blank');
}
