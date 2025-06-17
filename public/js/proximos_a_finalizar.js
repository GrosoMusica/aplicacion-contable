/**
 * Próximos a Finalizar JS
 * Script para el manejo de la página de compradores próximos a finalizar sus pagos
 */

$(document).ready(function() {
    // Variables globales
    let tabla;
    let mostrandoSoloFinalizados = false;
    
    // Inicializar DataTables con configuración avanzada
    function inicializarTabla() {
        tabla = $('#tablaProximos').DataTable({
            "paging": false,
            "info": false,
            "searching": true,
            "ordering": true,
            "dom": 't',
            "language": {
                "url": "//cdn.datatables.net/plug-ins/1.11.5/i18n/es-ES.json"
            },
            "columnDefs": [
                { "searchable": true, "targets": [0, 1] },  // Solo buscar en nombre y email
                { "searchable": false, "targets": [2, 3, 4, 5] },  // No buscar en el resto
                { "orderable": true, "targets": [0, 1] },  // Solo ordenar nombre y email
                { "orderable": false, "targets": [2, 3, 4, 5] }  // No ordenar el resto
            ],
            // Personalizar el renderizado de las filas
            "rowCallback": function(row, data, index) {
                // Aplicar clases adicionales o lógica según sea necesario
                if ($(row).hasClass('finalizado-row')) {
                    $(row).find('td').css('opacity', '0.9');
                }
            }
        });

        return tabla;
    }
    
    // Configurar filtrado por nombre en tiempo real
    function configurarBusqueda() {
        $('#nombreBusqueda').on('keyup', function() {
            const valorBusqueda = $(this).val();
            tabla.search(valorBusqueda).draw();
            
            // Destacar resultados de búsqueda si se desea
            if (valorBusqueda.length > 2) {
                // Se puede agregar efecto visual para destacar coincidencias
                $('.comprador-row:visible').addClass('filtrado');
            } else {
                $('.comprador-row').removeClass('filtrado');
            }
        });
    }
    
    // Configurar botón de mostrar solo finalizados
    function configurarBotonFinalizados() {
        $('#mostrarFinalizados').click(function() {
            toggleFinalizados();
            
            // Alternar clase activa en el botón
            $(this).toggleClass('active', mostrandoSoloFinalizados);
        });
    }
    
    // Función para alternar entre todos y solo finalizados
    function toggleFinalizados() {
        mostrandoSoloFinalizados = !mostrandoSoloFinalizados;
        
        if (mostrandoSoloFinalizados) {
            // Mostrar solo finalizados
            $('.comprador-row').hide();
            $('.comprador-row[data-finalizado="1"]').show();
            
            // Notificaciones toast eliminadas
        } else {
            // Restaurar vista de todos los compradores
            $('.comprador-row').show();
            
            // Notificaciones toast eliminadas
        }
        
        // Redibujar tabla para ajustar correctamente
        tabla.draw();
    }
    
    // Función para exportar los datos a Excel
    function exportarExcel() {
        // Implementación futura
    }
    
    // Inicializar componentes
    const tablaInicializada = inicializarTabla();
    configurarBusqueda();
    configurarBotonFinalizados();
    
    // Exponer funciones al ámbito global si es necesario
    window.proximosFinalizarTools = {
        toggleFinalizados: toggleFinalizados,
        exportarExcel: exportarExcel
    };
});
