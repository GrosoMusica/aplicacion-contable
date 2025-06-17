/**
 * Morosos JS
 * Script para la gestión de la vista de compradores morosos
 */

$(document).ready(function() {
    // Inicializar DataTables
    var tabla = $('#tablaMorosos').DataTable({
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
        ]
    });
    
    // Buscar por nombre
    $('#nombreBusqueda').on('keyup', function() {
        tabla.search(this.value).draw();
    });
    
    // Filtro para 2 cuotas pendientes
    $('#filtrarDos').click(function() {
        filtrarPorPendientes(2, 2);
    });
    
    // Filtro para 3 o más cuotas pendientes
    $('#filtrarTresOMas').click(function() {
        filtrarPorPendientes(3, 999);
    });
    
    // Filtro para mostrar todos
    $('#filtrarTodos').click(function() {
        $('.moroso-row').show();
        tabla.draw();
    });
    
    // Función para filtrar por cantidad de cuotas pendientes
    function filtrarPorPendientes(min, max) {
        $('.moroso-row').hide();
        $('.moroso-row').each(function() {
            var pendientes = parseInt($(this).data('pendientes'));
            if (pendientes >= min && pendientes <= max) {
                $(this).show();
            }
        });
        tabla.draw();
    }
});
