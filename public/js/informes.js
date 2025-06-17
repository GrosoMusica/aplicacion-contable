// Usar un evento temprano para asegurar configuración antes de inicializar
$(document).on('preInit.dt', function(e, settings) {
    if (settings.nTable.id === 'tablaDeudores') {
        // Establecer columnas ordenables/no ordenables
        settings.aoColumns.forEach(function(column, index) {
            if (index !== 0) { // Solo columna 0 es ordenable
                column.bSortable = false;
            }
        });
    }
});

$(document).ready(function() {
    // FORMA RECOMENDADA PARA ELIMINAR ALERTAS DE REINICIALIZACIÓN
    // 1. Para la tabla deudores
    var tablaDeudores;
    if ($.fn.dataTable.isDataTable('#tablaDeudores')) {
        // Si ya existe, obtener la instancia
        tablaDeudores = $('#tablaDeudores').DataTable();
    } else {
        // En el caso improbable de que no esté inicializada
        tablaDeudores = $('#tablaDeudores').DataTable({
            "language": {
                "url": "//cdn.datatables.net/plug-ins/1.11.5/i18n/es-ES.json"
            }
        });
    }
    
    // 2. Para la tabla de detalles
    var tablaDetalles;
    if ($.fn.dataTable.isDataTable('#tablaDetallesCuotas')) {
        // Si ya existe, obtener la instancia
        tablaDetalles = $('#tablaDetallesCuotas').DataTable();
    } else {
        // En el caso improbable de que no esté inicializada
        tablaDetalles = $('#tablaDetallesCuotas').DataTable({
            "order": [[0, 'asc']], 
            "language": {
                "url": "//cdn.datatables.net/plug-ins/1.11.5/i18n/es-ES.json"
            },
            "pageLength": 25,
            "columnDefs": [
                { "orderable": true, "targets": [0, 2, 3] },
                { "orderable": false, "targets": [1, 4, 5, 6, 7] },
                { "type": "num", "targets": [1, 4, 5, 6] }
            ],
            "dom": 't<"bottom"p>'
        });
    }
    
    // Eliminar completamente ordenamiento y flechas de todas las columnas excepto la primera
    function eliminarOrdenamientoColumnas() {
        // Deshabilitar el ordenamiento a nivel de API
        tablaDeudores.columns().every(function(index) {
            if (index !== 0) {
                // Eliminar todas las clases de ordenamiento
                $(this.header())
                    .removeClass('sorting sorting_asc sorting_desc')
                    .addClass('sorting_disabled')
                    .css('background-image', 'none')
                    .css('cursor', 'default')
                    .css('padding-right', '0.75rem');
                
                // Eliminar también atributos de ordenamiento
                $(this.header()).removeAttr('aria-sort');
                $(this.header()).removeAttr('aria-label');
                
                // Eliminar eventos de clic
                $(this.header()).off('click.dt');
            }
        });
    }
    
    // Ejecutar inmediatamente y después de cualquier redibujado
    eliminarOrdenamientoColumnas();
    tablaDeudores.on('draw.dt', eliminarOrdenamientoColumnas);
    
    // Conectar nuestro buscador personalizado
    $('#dtSearchBox').on('keyup', function() {
        tablaDeudores.search($(this).val()).draw();
    });
    
    // Comportamiento de placeholder
    $('#dtSearchBox').on('focus', function() {
        $(this).attr('placeholder', '');
    }).on('blur', function() {
        if ($(this).val() === '') {
            $(this).attr('placeholder', 'Buscar deudor...');
        }
    });
    
    // Arreglar el botón de colapso
    $('[data-bs-toggle="collapse"]').off('click').on('click', function() {
        var targetId = $(this).data('bs-target');
        var $icon = $(this).find('i');
        
        setTimeout(function() {
            if ($(targetId).hasClass('show')) {
                $icon.removeClass('fa-chevron-down').addClass('fa-chevron-up');
            } else {
                $icon.removeClass('fa-chevron-up').addClass('fa-chevron-down');
            }
        }, 350);
    });
    
    // Funcionalidad para el botón de balance
    let balanceVisible = true;
    
    function actualizarBotonBalance() {
        if (balanceVisible) {
            $('#verBalance').html('<i class="fas fa-eye-slash"></i> Ocultar Balance');
        } else {
            $('#verBalance').html('<i class="fas fa-chart-line"></i> Ver Balance');
        }
    }
    
    $('#verBalance').on('click', function() {
        balanceVisible = !balanceVisible;
        
        if (balanceVisible) {
            $('#filaCajas').slideDown(300);
        } else {
            $('#filaCajas').slideUp(300);
        }
        
        actualizarBotonBalance();
    });
    
    actualizarBotonBalance();
    
    // Activar tooltips
    $('[data-toggle="tooltip"]').tooltip();
    
    // Suprimir advertencias de DataTables en la consola
    $.fn.dataTable.ext.errMode = 'none';
    
    // Funcionalidad judicializar
    $('.judicializar-btn').on('click', function() {
        var loteid = $(this).data('lote-id');
        var estado = $(this).data('estado');
        
        $('#judicializado-value').val(estado === 1 ? 0 : 1);
        $('#judicializar-form').attr('action', '/lotes/' + loteid + '/judicializar');
        $('#judicializar-form').submit();
    });
    
    // Funcionalidad para generar reportes o gráficos si existe
    $('#exportar-pdf').on('click', function() {
        window.location.href = $(this).data('url');
    });
});

// Funciones globales que podrían estar definidas en el archivo original
function formatearMoneda(valor) {
    return 'U$D ' + parseFloat(valor).toFixed(2).replace(/\d(?=(\d{3})+\.)/g, '$&,');
}

function actualizarEstadisticas() {
    // Código para actualizar estadísticas si existe
} 