$(document).ready(function() {
    // Inicializar DataTables con ordenamiento en columnas específicas
    var tabla = $('#tablaLotes').DataTable({
        "paging": false,
        "info": false,
        "searching": true,
        "ordering": true,
        "order": [[0, 'asc']], // Ordenar por lote ascendente por defecto
        "columnDefs": [
            { "orderable": true, "targets": [0, 2, 3, 4] },  // Ordenar lote, loteo, comprador y precio
            { "orderable": false, "targets": [1, 5] }  // Manzana y acciones no ordenables
        ],
        "language": {
            "url": "//cdn.datatables.net/plug-ins/1.11.5/i18n/es-ES.json",
            "search": "Buscar:",
            "searchPlaceholder": "Filtrar resultados..."
        },
        "dom": 't' // Solo mostrar la tabla, sin el buscador nativo
    });
    
    // Conectar buscador personalizado
    $('#dtSearchBox').on('keyup', function() {
        tabla.search($(this).val()).draw();
    });
    
    // Quitar placeholder al obtener el foco
    $('#dtSearchBox').on('focus', function() {
        $(this).attr('placeholder', '');
    });
    
    // Restaurar placeholder al perder el foco si el campo está vacío
    $('#dtSearchBox').on('blur', function() {
        if ($(this).val() === '') {
            $(this).attr('placeholder', 'Buscar lote o comprador...');
        }
    });
    
    // Enfoque automático al campo de búsqueda
    $('#dtSearchBox').focus();
});