$(document).ready(function() {
    // Inicializar DataTables con ordenamiento solo en la columna nombre (índice 0)
    var tabla = $('#tablaCompradores').DataTable({
        "paging": false,
        "info": false,
        "searching": true,
        "ordering": true,
        "columnDefs": [
            { "orderable": true, "targets": 0 },  // Solo el nombre es ordenable
            { "orderable": false, "targets": "_all" } // El resto no son ordenables
        ],
        "language": {
            "url": "//cdn.datatables.net/plug-ins/1.11.5/i18n/es-ES.json",
            "search": "Buscar:",
            "searchPlaceholder": "Filtrar resultados..."
        },
        "dom": 't' // Solo mostrar la tabla, sin el buscador nativo (usaremos el nuestro)
    });
    
    // Conectar nuestro buscador personalizado con la funcionalidad de búsqueda de DataTables
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
            $(this).attr('placeholder', 'Búsqueda por nombre, email, mza o lote');
        }
    });
    
    // Enfoque automático al campo de búsqueda cuando se carga la página
    $('#dtSearchBox').focus();
}); 