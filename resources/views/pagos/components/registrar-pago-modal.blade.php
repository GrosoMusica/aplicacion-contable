<!-- Modal para Registrar Pago -->
<div class="modal fade" id="registrarPagoModal" tabindex="-1" aria-labelledby="registrarPagoModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-dark text-white">
                <h5 class="modal-title" id="registrarPagoModalLabel">Registrar Pago de Cuota</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('cuotas.pagar') }}" method="POST" enctype="multipart/form-data" id="formPago">
                @csrf
                <input type="hidden" name="cuota_id" id="cuotaIdInput" value="">
                <input type="hidden" name="financiacion_id" id="financiacionIdInput" value="">
                
                <div class="modal-body">
                    <div class="row">
                        <!-- Columna izquierda -->
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="fechaPago" class="form-label">Fecha de Pago</label>
                                <input type="date" class="form-control" id="fechaPago" name="fecha_de_pago" value="{{ now()->format('Y-m-d') }}">
                            </div>
                            
                            <div class="mb-3">
                                <label for="acreedorSelect" class="form-label">Acreedor que recibe el pago</label>
                                <select class="form-select" id="acreedorSelect" name="acreedor_id">
                                    <!-- Admin siempre como primera opción -->
                                    <option value="1" selected>Admin</option>
                                    
                                    <!-- Otros acreedores -->
                                    @php
                                        // Obtener todos los acreedores que no sean Admin
                                        $otrosAcreedores = \App\Models\Acreedor::where('id', '!=', 1)->get();
                                    @endphp
                                    
                                    @foreach($otrosAcreedores as $acreedor)
                                        <option value="{{ $acreedor->id }}">{{ $acreedor->nombre }}</option>
                                    @endforeach
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <label for="montoPagado" class="form-label">Monto Pagado</label>
                                <div class="input-group">
                                    <span class="input-group-text" id="simboloMoneda">U$D</span>
                                    <input type="number" step="0.01" class="form-control" id="montoPagado" name="monto_pagado" required>
                                    <div class="input-group-text">
                                        <div class="form-check form-switch mb-0">
                                            <input class="form-check-input" type="checkbox" id="pagoDivisa" name="pago_divisa">
                                            <label class="form-check-label" for="pagoDivisa">Pago en pesos</label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mb-3" id="tipoCambioContainer" style="display: none;">
                                <label for="tipoCambioInput" class="form-label">Tipo de Cambio (ARS/USD)</label>
                                <div class="input-group">
                                    <span class="input-group-text">ARS $</span>
                                    <input type="number" step="0.01" class="form-control" id="tipoCambioInput" value="1250" min="0.01">
                                    <span class="input-group-text">= 1 USD</span>
                                </div>
                                <small class="text-muted">Ingrese el tipo de cambio actual</small>
                            </div>

                            <div class="mb-3">
                                <label for="montoUsd" class="form-label">Monto USD (para cálculos internos)</label>
                                <div class="input-group">
                                    <span class="input-group-text">U$D</span>
                                    <input type="number" step="0.01" class="form-control" id="montoUsd" name="monto_usd" readonly>
                                </div>
                                <input type="hidden" id="tipoCambio" name="tipo_cambio" value="1250">
                                <small class="text-muted" id="tipoCambioText">Tipo de cambio: $1,250 ARS = 1 USD</small>
                            </div>
                        </div>
                        
                        <!-- Columna derecha -->
                        <div class="col-md-6">
                            <div class="mb-3 form-check">
                                <input type="checkbox" class="form-check-input" id="sinComprobante" name="sin_comprobante">
                                <label class="form-check-label" for="sinComprobante">Sin comprobante</label>
                            </div>
                            
                            <div class="alert alert-warning mt-2 d-none" id="alertaSinComprobante">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                Usted acepta incluir el siguiente pago de <span id="montoAlerta"></span> sin adjuntar comprobante.
                            </div>
                            
                            <div class="mb-3" id="archivoComprobanteContainer">
                                <label for="archivoComprobante" class="form-label">Subir Comprobante <span class="text-danger">*</span></label>
                                <input type="file" class="form-control" id="archivoComprobante" name="archivo_comprobante">
                                <small class="text-muted">Formatos aceptados: JPG, PNG, PDF. Máximo 2MB.</small>
                            </div>
                            
                            <div id="errorComprobante" class="alert alert-danger mt-2 d-none">
                                Debe subir un comprobante o marcar la casilla "Sin comprobante".
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-success" id="btnRegistrarPago">
                        <i class="fas fa-money-bill-wave me-1"></i> Registrar Pago
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const pagoDivisaCheckbox = document.getElementById('pagoDivisa');
    const montoPagadoInput = document.getElementById('montoPagado');
    const montoUsdInput = document.getElementById('montoUsd');
    const tipoCambioInput = document.getElementById('tipoCambio');
    const tipoCambioContainer = document.getElementById('tipoCambioContainer');
    const tipoCambioInputField = document.getElementById('tipoCambioInput');
    const simboloMoneda = document.getElementById('simboloMoneda');
    const tipoCambioText = document.getElementById('tipoCambioText');
    
    // Función para actualizar el valor de montoUsd basado en montoPagado
    function actualizarMontoUsd() {
        const esPagoEnPesos = pagoDivisaCheckbox.checked;
        const montoPagado = parseFloat(montoPagadoInput.value) || 0;
        const tipoCambioValor = parseFloat(tipoCambioInputField.value) || 1250;
        
        // Actualizar el valor del tipo de cambio en el input hidden
        tipoCambioInput.value = tipoCambioValor;
        
        // Actualizar el símbolo de la moneda
        simboloMoneda.textContent = esPagoEnPesos ? "ARS $" : "U$D";
        
        // Actualizar el texto del tipo de cambio
        tipoCambioText.textContent = `Tipo de cambio: $${tipoCambioValor.toLocaleString()} ARS = 1 USD`;
        
        // Calcular monto_usd
        if (esPagoEnPesos) {
            // Si pago es en pesos, convertir a USD
            const montoUSD = montoPagado / tipoCambioValor;
            montoUsdInput.value = montoUSD.toFixed(2);
        } else {
            // Si pago es en USD, usar el mismo valor
            montoUsdInput.value = montoPagado.toFixed(2);
        }
        
        // Actualizar el monto en la alerta
        document.getElementById('montoAlerta').textContent = 'U$D ' + montoUsdInput.value;
    }
    
    // Eventos
    pagoDivisaCheckbox.addEventListener('change', function() {
        tipoCambioContainer.style.display = this.checked ? 'block' : 'none';
        actualizarMontoUsd();
    });
    
    montoPagadoInput.addEventListener('input', actualizarMontoUsd);
    tipoCambioInputField.addEventListener('input', actualizarMontoUsd);
    
    // Inicializar
    actualizarMontoUsd();
    
    // Manejar checkbox "Sin comprobante"
    document.getElementById('sinComprobante').addEventListener('change', function() {
        if (this.checked) {
            document.getElementById('archivoComprobanteContainer').style.display = 'none';
            document.getElementById('alertaSinComprobante').classList.remove('d-none');
        } else {
            document.getElementById('archivoComprobanteContainer').style.display = 'block';
            document.getElementById('alertaSinComprobante').classList.add('d-none');
        }
    });
    
    // Cargar acreedores cuando se abre el modal
    $('#registrarPagoModal').on('show.bs.modal', function (event) {
        const button = $(event.relatedTarget);
        const cuotaId = button.data('cuota-id');
        const financiacionId = button.data('financiacion-id');
        const cuotaMonto = button.data('cuota-monto'); // Capturar el monto de la cuota
        
        // Establecer valores en inputs ocultos
        $('#cuotaIdInput').val(cuotaId);
        $('#financiacionIdInput').val(financiacionId);
        
        // Establecer monto por defecto
        $('#montoPagado').val(cuotaMonto);
        actualizarMontoUsd(); // Actualizar el cálculo del monto USD
        
        // Cargar acreedores asociados a esta financiación
        $.ajax({
            url: '/api/financiaciones/' + financiacionId + '/acreedores',
            type: 'GET',
            success: function(data) {
                const select = $('#acreedorSelect');
                select.empty();
                
                // Siempre agregar Admin como primera opción
                select.append(new Option('Admin', 1, true, true));
                
                // Agregar otros acreedores asociados
                data.forEach(function(acreedor) {
                    if (acreedor.id !== 1) { // Excluir Admin que ya está agregado
                        select.append(new Option(acreedor.nombre, acreedor.id));
                    }
                });
            },
            error: function() {
                console.error('Error al cargar acreedores');
                // En caso de error, asegurar que Admin esté disponible
                const select = $('#acreedorSelect');
                select.empty();
                select.append(new Option('Admin', 1, true, true));
            }
        });
    });
    
    // Validación al enviar el formulario
    document.getElementById('formPago').addEventListener('submit', function(event) {
        const sinComprobante = document.getElementById('sinComprobante').checked;
        const archivoComprobante = document.getElementById('archivoComprobante').files.length > 0;
        
        if (!sinComprobante && !archivoComprobante) {
            event.preventDefault();
            document.getElementById('errorComprobante').classList.remove('d-none');
        } else {
            document.getElementById('errorComprobante').classList.add('d-none');
        }
    });
});
</script> 