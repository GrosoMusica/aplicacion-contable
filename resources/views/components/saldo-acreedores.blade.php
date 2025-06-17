<div class="card">
    <div class="card-header bg-white">
        <div class="d-flex justify-content-between align-items-center">
            <h5 class="mb-0">
                <i class="fas fa-table me-2 text-primary"></i>Vista panorámica de acreedores
            </h5>
            <div class="dropdown">
                <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" id="exportDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="fas fa-download me-1"></i>Exportar
                </button>
                <ul class="dropdown-menu" aria-labelledby="exportDropdown">
                    <li><a class="dropdown-item" href="{{ route('acreedores.export-pdf', 'resumen') }}" target="_blank">Resumen (PDF)</a></li>
                    <li><a class="dropdown-item" href="{{ route('acreedores.export-pdf', 'detallado') }}" target="_blank">Detallado (PDF)</a></li>
                    <li><a class="dropdown-item" href="{{ route('acreedores.export-pdf', 'mensual') }}" target="_blank">Mensual (PDF)</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item" href="#" id="exportExcel">Exportar a Excel</a></li>
                </ul>
            </div>
        </div>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-sm table-bordered panoramic-table">
                <thead>
                    <tr class="bg-light">
                        <th class="acreedor-column">Acreedor</th>
                        
                        <!-- Columnas de meses -->
                        @foreach($months as $month)
                            <th class="month-column text-center">
                                {{ $month->locale('es')->format('M y') }}
                            </th>
                        @endforeach
                        
                        <!-- Columnas de totales -->
                        <th class="total-column text-center bg-success bg-opacity-10">
                            <div>Pagado</div>
                            <div class="small text-muted">hasta la fecha</div>
                        </th>
                        <th class="total-column text-center bg-danger bg-opacity-10">
                            <div>Saldo</div>
                            <div class="small text-muted">pendiente</div>
                        </th>
                        <th class="total-column text-center bg-secondary bg-opacity-10">
                            <div>Total</div>
                        </th>
                    </tr>
                </thead>
                <tbody>
                    @php
                        // Separar el admin de los demás acreedores
                        $adminAcreedor = null;
                        $otrosAcreedores = collect();
                        
                        foreach($acreedores as $acr) {
                            if (strtolower($acr->nombre) === 'admin') {
                                $adminAcreedor = $acr;
                            } else {
                                $otrosAcreedores->push($acr);
                            }
                        }
                    @endphp

                    <!-- Primero mostramos todos los acreedores excepto admin -->
                    @foreach($otrosAcreedores as $acreedor)
                        <tr>
                            <td class="align-middle">
                                <div class="d-flex align-items-center">
                                    <div class="acreedor-avatar me-2">
                                        {{ strtoupper(substr($acreedor->nombre, 0, 1)) }}
                                    </div>
                                    <div>
                                        <div class="fw-bold">{{ $acreedor->nombre }}</div>
                                        <div class="small text-muted">{{ count($acreedor->financiaciones) }} financiaciones</div>
                                    </div>
                                </div>
                            </td>
                            
                            <!-- Datos de pagos por mes -->
                            @foreach($months as $month)
                                <td class="text-center align-middle month-data">
                                    <!-- IMPORTANTE: Aquí debe usar exactamente la misma lógica de cálculo que en totalmes -->
                                    @php
                                        // TODO: Reemplazar este cálculo con el mismo que se usa en totalmes
                                        // $valor = $acreedor->getTotalMesPorPeriodo($month->format('Y-m'));
                                        $valor = isset($acreedor->totalesMes[$month->format('Y-m')]) 
                                                ? $acreedor->totalesMes[$month->format('Y-m')] 
                                                : 0;
                                    @endphp
                                    
                                    <!-- Todos los valores con el mismo estilo -->
                                    @if($valor > 0)
                                        <span class="monto-tabla">{{ number_format($valor, 2) }}</span>
                                    @else
                                        <span class="monto-tabla">-</span>
                                    @endif
                                </td>
                            @endforeach
                            
                            <!-- Datos de totales -->
                            @php
                                // TODO: Reemplazar estos cálculos con los mismos que se usan en totalmes
                                // $totalPagado = $acreedor->getTotalPagado();
                                // $totalPendiente = $acreedor->getTotalPendiente();
                                // $total = $totalPagado + $totalPendiente;
                                $totalPagado = isset($acreedor->totalPagado) ? $acreedor->totalPagado : 0;
                                $totalPendiente = isset($acreedor->totalPendiente) ? $acreedor->totalPendiente : 0;
                                $total = $totalPagado + $totalPendiente;
                            @endphp
                            
                            <td class="text-center align-middle bg-success bg-opacity-10">
                                <span class="monto-tabla">{{ number_format($totalPagado, 2) }}</span>
                            </td>
                            <td class="text-center align-middle bg-danger bg-opacity-10">
                                <span class="monto-tabla">{{ number_format($totalPendiente, 2) }}</span>
                            </td>
                            <td class="text-center align-middle bg-secondary bg-opacity-10">
                                <span class="monto-tabla">{{ number_format($total, 2) }}</span>
                            </td>
                        </tr>
                    @endforeach

                    <!-- Finalmente, mostramos el admin con un estilo diferente (si existe) -->
                    @if($adminAcreedor)
                        <tr class="admin-row bg-light">
                            <td class="align-middle">
                                <div class="d-flex align-items-center">
                                    <div class="acreedor-avatar me-2 bg-secondary text-white">
                                        A
                                    </div>
                                    <div>
                                        <div class="fw-bold">{{ $adminAcreedor->nombre }}</div>
                                        <div class="small text-muted">{{ count($adminAcreedor->financiaciones) }} financiaciones</div>
                                    </div>
                                </div>
                            </td>
                            
                            <!-- Datos de pagos por mes para admin -->
                            @foreach($months as $month)
                                <td class="text-center align-middle month-data">
                                    <!-- IMPORTANTE: Aquí debe usar exactamente la misma lógica de cálculo que en totalmes -->
                                    @php
                                        // TODO: Reemplazar este cálculo con el mismo que se usa en totalmes
                                        // $valor = $adminAcreedor->getTotalMesPorPeriodo($month->format('Y-m'));
                                        $valor = isset($adminAcreedor->totalesMes[$month->format('Y-m')]) 
                                                ? $adminAcreedor->totalesMes[$month->format('Y-m')] 
                                                : 0;
                                    @endphp
                                    
                                    <!-- Todos los valores con el mismo estilo -->
                                    @if($valor > 0)
                                        <span class="monto-tabla">{{ number_format($valor, 2) }}</span>
                                    @else
                                        <span class="monto-tabla">-</span>
                                    @endif
                                </td>
                            @endforeach
                            
                            <!-- Datos de totales para admin -->
                            @php
                                // TODO: Reemplazar estos cálculos con los mismos que se usan en totalmes
                                // $totalPagado = $adminAcreedor->getTotalPagado();
                                // $totalPendiente = $adminAcreedor->getTotalPendiente();
                                // $total = $totalPagado + $totalPendiente;
                                $totalPagado = isset($adminAcreedor->totalPagado) ? $adminAcreedor->totalPagado : 0;
                                $totalPendiente = isset($adminAcreedor->totalPendiente) ? $adminAcreedor->totalPendiente : 0;
                                $total = $totalPagado + $totalPendiente;
                            @endphp
                            
                            <td class="text-center align-middle bg-success bg-opacity-10">
                                <span class="monto-tabla">{{ number_format($totalPagado, 2) }}</span>
                            </td>
                            <td class="text-center align-middle bg-danger bg-opacity-10">
                                <span class="monto-tabla">{{ number_format($totalPendiente, 2) }}</span>
                            </td>
                            <td class="text-center align-middle bg-secondary bg-opacity-10">
                                <span class="monto-tabla">{{ number_format($total, 2) }}</span>
                            </td>
                        </tr>
                    @endif
                </tbody>
            </table>
        </div>
    </div>
</div>

<style>
/* Estilo para la fila del administrador */
.admin-row {
    border-top: 2px solid #aaa !important;
}
.admin-row .acreedor-avatar {
    background-color: #6c757d !important;
    color: white !important;
}
/* Estilo unificado para todos los montos (sin colores específicos) */
.monto-tabla {
    color: #333;
    font-weight: 500;
}
</style> 