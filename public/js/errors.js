function formatDate(dateString) {
    if (!dateString) return 'Sin fecha';
    
    const date = new Date(dateString);
    const months = ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];
    
    return `
        <div class="d-inline-flex align-items-center small">
            <span class="month me-1">${months[date.getMonth()]}</span>
            <span class="day me-1">${date.getDate()}</span>
            <span class="year text-muted">${date.getFullYear()}</span>
        </div>
    `;
}

function openPaymentModal() {
    $('#paymentModal').modal('show');
    loadPayments();
}

function loadPayments() {
    $.ajax({
        url: routeGetLastPayments,
        method: 'GET',
        success: function(data) {
            console.log('Datos recibidos del servidor:', data);
            const tbody = $('#paymentsTableBody');
            tbody.empty();
            
            if (data.length === 0) {
                tbody.append(`
                    <tr>
                        <td colspan="5" class="text-center">No hay pagos registrados</td>
                    </tr>
                `);
                return;
            }
            
            data.forEach(payment => {
                console.log('Procesando pago:', payment);
                tbody.append(`
                    <tr data-payment-id="${payment.id}">
                        <td class="align-middle text-center">${payment.cuota_id || 'N/A'}</td>
                        <td class="align-middle text-center">${payment.nombre_comprador || 'N/A'}</td>
                        <td class="align-middle py-2">${formatDate(payment.fecha_de_pago)}</td>
                        <td class="align-middle text-center">
                            <span class="monto-display">${payment.monto_usd || '0.00'}</span>
                            <div class="input-group d-none monto-edit">
                                <input type="number" class="form-control form-control-sm" 
                                       value="${payment.monto_usd || '0.00'}" step="0.01" min="0">
                                <button class="btn btn-success btn-sm save-monto" type="button">
                                    <i class="fas fa-check"></i>
                                </button>
                                <button class="btn btn-danger btn-sm cancel-edit" type="button">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                        </td>
                        <td class="align-middle text-center">
                            <button class="btn btn-primary btn-sm edit-monto me-2">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button class="btn btn-danger btn-sm delete-payment">
                                <i class="fas fa-trash"></i>
                            </button>
                        </td>
                    </tr>
                `);
            });

            // Event listeners
            $('.edit-monto').on('click', function() {
                const row = $(this).closest('tr');
                row.find('.monto-display').addClass('d-none');
                row.find('.monto-edit').removeClass('d-none');
                $(this).addClass('d-none');
            });

            $('.cancel-edit').on('click', function() {
                const row = $(this).closest('tr');
                row.find('.monto-display').removeClass('d-none');
                row.find('.monto-edit').addClass('d-none');
                row.find('.edit-monto').removeClass('d-none');
            });

            $('.save-monto').on('click', function() {
                const row = $(this).closest('tr');
                const newValue = row.find('input').val();
                const paymentId = row.data('payment-id');
                updateValue(paymentId, newValue, row);
            });

            $('.delete-payment').on('click', function() {
                const row = $(this).closest('tr');
                const paymentId = row.data('payment-id');
                
                Swal.fire({
                    title: '¡Atención!',
                    html: '<strong>VAS A ELIMINAR UN REGISTRO DE LA TABLA DE FORMA PERMANENTE!!!</strong>',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#3085d6',
                    confirmButtonText: 'Sí, eliminar',
                    cancelButtonText: 'Cancelar'
                }).then((result) => {
                    if (result.isConfirmed) {
                        deletePayment(paymentId, row);
                    }
                });
            });
        },
        error: function(xhr, status, error) {
            const tbody = $('#paymentsTableBody');
            tbody.empty();
            tbody.append(`
                <tr>
                    <td colspan="5" class="text-center text-danger">
                        Error al cargar los pagos. Por favor, intente nuevamente.
                    </td>
                </tr>
            `);
            toastr.error('Error al cargar los pagos');
        }
    });
}

function updateValue(paymentId, newValue, row) {
    $.ajax({
        url: routePaymentUpdate,
        method: 'PUT',
        data: {
            id: paymentId,
            monto_usd: newValue,
            _token: csrfToken
        },
        success: function(response) {
            if (response.success) {
                row.find('.monto-display')
                   .text(parseFloat(newValue).toFixed(2))
                   .removeClass('d-none');
                row.find('.monto-edit').addClass('d-none');
                row.find('.edit-monto').removeClass('d-none');
                toastr.success('Monto actualizado correctamente');
            }
        },
        error: function(xhr, status, error) {
            toastr.error('Error al actualizar el monto');
            row.find('.monto-edit').addClass('d-none');
            row.find('.monto-display').removeClass('d-none');
            row.find('.edit-monto').removeClass('d-none');
        }
    });
}

function deletePayment(paymentId, row) {
    $.ajax({
        url: routePaymentDelete,
        method: 'DELETE',
        data: {
            id: paymentId,
            _token: csrfToken
        },
        success: function(response) {
            if (response.success) {
                row.fadeOut(400, function() {
                    $(this).remove();
                    if ($('#paymentsTableBody tr').length === 0) {
                        $('#paymentsTableBody').append(`
                            <tr>
                                <td colspan="5" class="text-center">No hay pagos registrados</td>
                            </tr>
                        `);
                    }
                });
                toastr.success('Pago eliminado correctamente');
            }
        },
        error: function(xhr, status, error) {
            toastr.error('Error al eliminar el pago');
        }
    });
}

function openOperationModal() {
    $('#operationModal').modal('show');
    loadOperations();
}

function loadOperations() {
    $.ajax({
        url: routeGetCompradores,
        method: 'GET',
        success: function(data) {
            console.log('Datos de compradores recibidos:', data);
            const tbody = $('#operationsTableBody');
            tbody.empty();
            
            if (data.length === 0) {
                tbody.append(`
                    <tr>
                        <td colspan="6" class="text-center">No hay compradores registrados</td>
                    </tr>
                `);
                return;
            }
            
            data.forEach(comprador => {
                tbody.append(`
                    <tr data-comprador-id="${comprador.id}">
                        <td class="align-middle text-center">${comprador.id}</td>
                        <td class="align-middle">${comprador.nombre || 'N/A'}</td>
                        <td class="align-middle text-center">${comprador.dni || 'N/A'}</td>
                        <td class="align-middle text-center">${comprador.lote_comprado_id || 'N/A'}</td>
                        <td class="align-middle text-center">${comprador.financiacion_id || 'N/A'}</td>
                        <td class="align-middle text-center">
                            <button class="btn btn-danger btn-sm delete-operation">
                                <i class="fas fa-trash"></i>
                            </button>
                        </td>
                    </tr>
                `);
            });

            // Event listener para el botón de eliminar
            $('.delete-operation').on('click', function() {
                const row = $(this).closest('tr');
                const compradorId = row.data('comprador-id');
                
                Swal.fire({
                    title: '¡ADVERTENCIA!',
                    html: `
                        <div class="text-danger">
                            <i class="fas fa-exclamation-triangle fa-2x mb-3"></i>
                            <p class="mb-3"><strong>Esta operación es IRREVERSIBLE y eliminará:</strong></p>
                            <ul class="text-start">
                                <li>El registro del comprador</li>
                                <li>El lote asociado</li>
                                <li>Todas las cuotas relacionadas</li>
                                <li>La financiación correspondiente</li>
                            </ul>
                            <p class="mt-3">¿Está seguro de que desea continuar?</p>
                        </div>
                    `,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#3085d6',
                    confirmButtonText: 'Sí, eliminar todo',
                    cancelButtonText: 'Cancelar'
                }).then((result) => {
                    if (result.isConfirmed) {
                        deleteOperation(compradorId, row);
                    }
                });
            });
        },
        error: function(xhr, status, error) {
            const tbody = $('#operationsTableBody');
            tbody.empty();
            tbody.append(`
                <tr>
                    <td colspan="6" class="text-center text-danger">
                        Error al cargar los compradores. Por favor, intente nuevamente.
                    </td>
                </tr>
            `);
            toastr.error('Error al cargar los compradores');
        }
    });
}

function deleteOperation(compradorId, row) {
    $.ajax({
        url: routeOperationDelete,
        method: 'DELETE',
        data: {
            id: compradorId,
            _token: csrfToken
        },
        success: function(response) {
            if (response.success) {
                row.fadeOut(400, function() {
                    $(this).remove();
                    if ($('#operationsTableBody tr').length === 0) {
                        $('#operationsTableBody').append(`
                            <tr>
                                <td colspan="6" class="text-center">No hay compradores registrados</td>
                            </tr>
                        `);
                    }
                });
                
                const deletedIds = response.deleted_ids;
                toastr.success(`
                    Operación eliminada correctamente:
                    <br>- Comprador ID: ${deletedIds.comprador}
                    <br>- Lote ID: ${deletedIds.lote || 'N/A'}
                    <br>- Financiación ID: ${deletedIds.financiacion || 'N/A'}
                `);
            }
        },
        error: function(xhr, status, error) {
            console.error('Error completo:', xhr.responseJSON);
            
            let errorMessage = 'Error al eliminar la operación';
            if (xhr.responseJSON && xhr.responseJSON.message) {
                errorMessage = xhr.responseJSON.message;
            }
            
            Swal.fire({
                title: 'Error en la eliminación',
                html: `
                    <div class="text-danger">
                        <p><strong>Detalles del error:</strong></p>
                        <p>${errorMessage}</p>
                        <p class="mt-2"><small>Si el problema persiste, contacte al administrador del sistema.</small></p>
                    </div>
                `,
                icon: 'error',
                confirmButtonText: 'Entendido'
            });
        }
    });
}

// Configuración de toastr
toastr.options = {
    "closeButton": true,
    "progressBar": true,
    "positionClass": "toast-top-right",
    "timeOut": "3000"
};

// Inicializar cuando el documento esté listo
$(document).ready(function() {
    console.log('Documento listo');
    $('.error-card').eq(2).on('click', function() {
        openOperationModal();
    });
});
