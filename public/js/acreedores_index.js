/**
 * Acreedores Index JS
 * Script para el manejo de la vista principal de acreedores
 */

$(document).ready(function() {
    // Variables para el selector de mes
    let currentDate = moment();
    
    // Actualizar el texto del mes seleccionado
    function updateMonthDisplay() {
        $('#currentMonth').text(currentDate.locale('es').format('MMMM YYYY'));
    }
    
    // Evento para ir al mes anterior
    $('#prevMonth').click(function(e) {
        e.preventDefault();
        currentDate.subtract(1, 'month');
        updateMonthDisplay();
        // Aquí iría el código para cargar los datos del mes anterior
    });
    
    // Evento para ir al mes siguiente
    $('#nextMonth').click(function(e) {
        e.preventDefault();
        currentDate.add(1, 'month');
        updateMonthDisplay();
        // Aquí iría el código para cargar los datos del mes siguiente
    });
    
    // Para cada acreedor, configurar los toggles
    // Usamos la clase en lugar de IDs específicos para permitir que el código funcione con cualquier número de acreedores
    $('.togglePendientes').click(function() {
        const acreedorId = $(this).data('acreedor-id');
        const pendientesTexts = $(`#content-${acreedorId}`).find('.pendientes-text');
        const $this = $(this);
        
        if (pendientesTexts.is(':visible')) {
            pendientesTexts.hide();
            $this.html('<i class="fas fa-eye me-1"></i> Mostrar Pendientes');
        } else {
            pendientesTexts.show();
            $this.html('<i class="fas fa-eye-slash me-1"></i> Ocultar Pendientes');
        }
    });
    
    // Toggle para mostrar/ocultar totales
    $('.toggleTotales').click(function() {
        const acreedorId = $(this).data('acreedor-id');
        const totalesTexts = $(`#content-${acreedorId}`).find('.totales-text');
        const $this = $(this);
        
        if (totalesTexts.is(':visible')) {
            totalesTexts.hide();
            $this.html('<i class="fas fa-list-alt me-1"></i> Ver Totales');
        } else {
            totalesTexts.show();
            $this.html('<i class="fas fa-list-alt me-1"></i> Ocultar Totales');
        }
    });
});
