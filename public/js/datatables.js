/**
 * SIMA Contable - Configuración de DataTables
 */

$(document).ready(function() {
    // Configuración global de idioma para DataTables
    $.extend(true, $.fn.dataTable.defaults, {
        "language": {
            "processing": "Procesando...",
            "lengthMenu": "Ver _MENU_ filas",
            "zeroRecords": "No se encontraron resultados",
            "emptyTable": "Ningún dato disponible en esta tabla",
            "info": "Mostrando _START_ a _END_ de _TOTAL_ registros",
            "infoEmpty": "Mostrando 0 a 0 de 0 registros",
            "infoFiltered": "(filtrado de _MAX_ registros totales)",
            "search": "<i class='fas fa-search'></i> Buscar:",
            "searchPlaceholder": "Filtrar registros...",
            "paginate": {
                "first": "Primero",
                "last": "Último",
                "next": ">",
                "previous": "<"
            },
            "aria": {
                "sortAscending": ": activar para ordenar la columna de manera ascendente",
                "sortDescending": ": activar para ordenar la columna de manera descendente"
            }
        }
    });
}); 