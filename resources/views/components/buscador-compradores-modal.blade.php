<!-- Modal Buscador de Compradores -->
<div class="modal fade" id="buscarCompradorModal" tabindex="-1" aria-labelledby="buscarCompradorModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="buscarCompradorModalLabel">Buscar Comprador</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <div class="input-group">
                        <span class="input-group-text bg-primary text-white">
                            <i class="fas fa-search"></i>
                        </span>
                        <input type="text" class="form-control" id="buscadorGlobalComprador" 
                               placeholder="Nombre, apellido o DNI del comprador">
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover" id="tablaResultadosCompradores">
                        <thead>
                            <tr>
                                <th>Nombre</th>
                                <th>DNI/CUIT</th>
                                <th>Lote</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Los resultados se cargarán aquí dinámicamente -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- CSS más agresivo para ocultar CUALQUIER campo de búsqueda adicional -->
<style>
    /* Ocultar el buscador de DataTables específicamente */
    .dataTables_filter {
        display: none !important;
    }
    
    /* Ocultar cualquier otra cosa que pueda contener un buscador */
    div.dt-search,
    div.search-form,
    .dataTables_wrapper .dt-buttons + .dataTables_filter,
    input[type="search"],
    input.form-control[type="search"],
    .dataTables_wrapper label:contains("Buscar"),
    .dataTables_wrapper .row:first-child {
        display: none !important;
    }
    
    /* Asegurarse de que el buscador personalizado sea visible */
    #buscadorGlobalComprador {
        display: block !important;
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Variable para controlar si se han cargado los datos
        let datosCompradorCargados = false;
        
        // Inicializar DataTable para los resultados si existe el elemento
        if (document.getElementById('tablaResultadosCompradores')) {
            var tablaResultados = $('#tablaResultadosCompradores').DataTable({
                "paging": true,
                "pageLength": 5,
                "info": false,
                "searching": true, // Mantener búsqueda pero ocultar su UI
                "language": {
                    "url": "//cdn.datatables.net/plug-ins/1.11.5/i18n/es-ES.json",
                    "zeroRecords": "No se encontraron compradores",
                    "paginate": {
                        "first": "Primero",
                        "last": "Último",
                        "next": "Siguiente",
                        "previous": "Anterior"
                    }
                },
                "columns": [
                    { "data": "nombre" },
                    { "data": "dni" },
                    { "data": "lote" },
                    { "data": "acciones" }
                ],
                // Eliminar todo excepto la tabla y paginación
                "dom": 'rt<"bottom"p><"clear">'
            });
            
            // Eliminar cualquier buscador que pueda haber aparecido después de la inicialización
            setTimeout(function() {
                $('.dataTables_filter').remove();
                $('.dataTables_wrapper .row:first-child').remove();
            }, 100);
            
            // Asegurarse que el evento se registre correctamente
            $('#buscarCompradorModal').on('shown.bs.modal', function () {
                // Eliminar cualquier buscador cada vez que se abre el modal
                $('.dataTables_filter').remove();
                
                if (!datosCompradorCargados) {
                    $.ajax({
                        url: "{{ route('compradores.buscar') }}",
                        type: "GET",
                        dataType: "json",
                        success: function(data) {
                            tablaResultados.clear();
                            
                            // Cargar los datos en la tabla
                            data.forEach(function(comprador) {
                                let loteInfo = comprador.lote ? 
                                    `Mza: ${comprador.lote.manzana} - Lote: ${comprador.lote.lote}` : 
                                    'No asignado';
                                    
                                let acciones = `
                                    <a href="{{ url('pagos') }}?comprador_id=${comprador.id}" class="btn btn-success btn-sm" title="Ver pagos">
                                        <i class="fas fa-money-bill-wave"></i>
                                    </a>`;
                                    
                                tablaResultados.row.add({
                                    "nombre": comprador.nombre + ' ' + (comprador.apellido || ''),
                                    "dni": comprador.dni || comprador.cuit || '-',
                                    "lote": loteInfo,
                                    "acciones": acciones
                                });
                            });
                            
                            tablaResultados.draw();
                            datosCompradorCargados = true;
                            
                            // Eliminar el buscador una vez más después de cargar datos
                            $('.dataTables_filter').remove();
                        },
                        error: function() {
                            console.error("Error al cargar los compradores");
                        }
                    });
                }
                
                // Enfocar el campo de búsqueda personalizado
                $('#buscadorGlobalComprador').focus();
            });
            
            // Conectar el buscador personalizado con DataTables
            $('#buscadorGlobalComprador').on('keyup', function() {
                tablaResultados.search($(this).val()).draw();
                
                // Eliminar el buscador cada vez que se usa el campo personalizado
                $('.dataTables_filter').remove();
            });
        }
    });
</script> 