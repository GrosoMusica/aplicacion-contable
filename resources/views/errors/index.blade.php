@extends('layouts.app')

@section('title', 'Errores del Sistema')

@section('styles')
    <link href="{{ asset('css/errors.css') }}" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
@endsection

@section('content')
<div class="container mt-4">
    <div class="row mb-4">
        <div class="col text-center">
            <h2 class="mb-2">
                <i class="fas fa-key text-warning me-2"></i>
                Operaciones del Sistema
            </h2>
            <p class="text-muted small mb-2">
                Estas operaciones permiten modificar datos directamente desde la tabla.
            </p>
            <p class="text-muted small mb-2">
                La eliminación de registros y otras modificaciones son operaciones irreversibles.
            </p>
            <p class="text-muted small mb-4">
                <i class="fas fa-exclamation-triangle text-warning me-2"></i>
                Proceda con precaución.
            </p>
        </div>
    </div>

    <div class="row">
        <!-- Eliminar Pago -->
        <div class="col-md-4 mb-4">
            <div class="card h-100 error-card" onclick="openPaymentModal()">
                <div class="card-body text-center">
                    <div class="error-icon text-danger">
                        <i class="fas fa-money-bill-wave fa-2x"></i>
                    </div>
                    <h3 class="error-title">Eliminar Pago</h3>
                    <p class="error-description">
                        Eliminar pagos registrados en el sistema.
                    </p>
                </div>
            </div>
        </div>

        <!-- Ajuste de Tablas -->
        <div class="col-md-4 mb-4">
            <div class="card h-100 error-card">
                <div class="card-body text-center">
                    <div class="error-icon text-warning">
                        <i class="fas fa-file-contract fa-2x"></i>
                    </div>
                    <h3 class="error-title">Ajuste de Tablas</h3>
                    <p class="error-description">
                        Realizar ajustes y correcciones en las tablas del sistema.
                    </p>
                </div>
            </div>
        </div>

        <!-- Eliminar Operación -->
        <div class="col-md-4 mb-4">
            <div class="card h-100 error-card">
                <div class="card-body text-center">
                    <div class="error-icon text-primary">
                        <i class="fas fa-user-edit fa-2x"></i>
                    </div>
                    <h3 class="error-title">Eliminar Operación</h3>
                    <p class="error-description">
                        Eliminar un comprador, lotes, cuotas y financiaciones asociadass.
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal de Gestión de Pagos -->
<div class="modal fade" id="paymentModal" tabindex="-1" aria-labelledby="paymentModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="paymentModalLabel">
                    Eliminar Pagos
                    <small class="d-block text-muted small mt-1">Se mostrarán las últimas 5 entradas cargadas</small>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-info mb-4">
                    <i class="fas fa-info-circle me-2"></i>
                    <strong>MODIFICAR DATOS DESDE LA TABLA!</strong>
                </div>
                <div class="table-responsive">
                    <table class="table table-striped" id="paymentsTable">
                        <thead>
                            <tr>
                                <th>Cuota ID</th>
                                <th>Comprador</th>
                                <th>Fecha de Pago</th>
                                <th>Monto USD</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody id="paymentsTableBody">
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal de Eliminación de Operación -->
<div class="modal fade" id="operationModal" tabindex="-1" aria-labelledby="operationModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="operationModalLabel">
                    Eliminar Operación Completa
                    <small class="d-block text-muted small mt-1">Esta acción eliminará registros de múltiples tablas</small>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-danger mb-4">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <strong>¡ADVERTENCIA! Esta operación eliminará datos de forma permanente</strong>

                </div>
                <div class="table-responsive">
                    <table class="table table-striped" id="operationsTable">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nombre</th>
                                <th>DNI</th>
                                <th>Lote ID</th>
                                <th>Financiación ID</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody id="operationsTableBody">
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    // Variables necesarias para errors.js
    const routeGetLastPayments = '{{ route("payment.getLastPayments") }}';
    const routePaymentUpdate = '{{ route("payment.update") }}';
    const routePaymentDelete = '{{ route("payment.delete") }}';
    const routeGetCompradores = '{{ route("operation.getCompradores") }}';
    const routeOperationDelete = '{{ route("operation.delete") }}';
    const csrfToken = '{{ csrf_token() }}';
</script>
<script src="{{ asset('js/errors.js') }}"></script>
@endsection 