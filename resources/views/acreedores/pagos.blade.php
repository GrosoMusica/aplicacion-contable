@extends('layouts.app')

@section('title', 'Liquidaciones de Acreedores')

@section('styles')
    <!-- Estilos específicos para acreedores -->
    <link rel="stylesheet" href="{{ asset('css/acreedores.css') }}">
@endsection

@section('content')
<div class="container-fluid">
    <!-- Desplegable para filtrar acreedores -->
    <div class="row mb-4">
        <div class="col-md-6">
            <label for="selectAcreedor" class="form-label fw-bold">Filtrar por Acreedor:</label>
            <select id="selectAcreedor" class="form-select">
                <option value="todos">Mostrar Todos</option>
                @if(isset($otrosAcreedores))
                    @foreach($otrosAcreedores as $a)
                        <option value="{{ $a->id }}">{{ $a->nombre }}</option>
                    @endforeach
                @endif
            </select>
        </div>
    </div>

    <div class="row">
        @php
            // Separar Admin (id=1) para mostrarlo último
            $adminAcreedor = null;
            $otrosAcreedores = [];
            
            foreach($acreedores as $acreedor) {
                if($acreedor->id == 1) {
                    $adminAcreedor = $acreedor;
                } else {
                    $otrosAcreedores[] = $acreedor;
                }
            }
        @endphp
        
        <!-- Para los acreedores NORMALES (no admin) -->
        @foreach($otrosAcreedores as $acreedor)
        <div class="col-md-6 mb-4 acreedor-card" data-acreedor-id="{{ $acreedor->id }}">
            <div class="card h-100">
                <div class="card-header">
                    <h5 class="mb-0">{{ $acreedor->nombre }}</h5>
                </div>
                <div class="card-body">
                    @php
                    $calculosController = new \App\Http\Controllers\AcreedorCalculosController();
                    $resumen = $calculosController->obtenerResumenAcreedor($acreedor->id);
                    $totalACobrar = $resumen['total_a_cobrar'];
                    $totalCobrado = $resumen['total_cobrado'];
                    $saldo = $resumen['saldo'];
                    $pagosRecibidos = $resumen['pagos_recibidos'];
                    $liquidaciones = $resumen['liquidaciones'];
                    @endphp
                    
                    <!-- Mostramos el listado de pagos y liquidaciones para acreedor normal (sin título) -->
                    @if($pagosRecibidos->count() > 0 || $liquidaciones->count() > 0)
                    <div class="mb-4">
                        <div class="table-responsive">
                            <table class="table table-sm table-striped">
                                <thead>
                                    <tr>
                                        <th>Fecha</th>
                                        <th>Monto USD</th>
                                        <th>Origen</th>
                                        <th>Porcentaje</th>
                                        <th>Comprobantes</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <!-- Pagos normales -->
                                    @foreach($pagosRecibidos as $pago)
                                    <tr>
                                        <td>{{ $pago->created_at->format('d/m/Y') }}</td>
                                        <td>${{ number_format($pago->monto_usd, 2) }}</td>
                                        <td>
                                            @if($pago->cuota && $pago->cuota->financiacion && $pago->cuota->financiacion->comprador)
                                                {{ $pago->cuota->financiacion->comprador->nombre }} {{ $pago->cuota->financiacion->comprador->apellido }}
                                            @else
                                                -
                                            @endif
                                        </td>
                                        <td>
                                            @php
                                                $porcentaje = 0;
                                                if($pago->cuota && $pago->cuota->financiacion) {
                                                    $porcentaje = DB::table('financiacion_acreedor')
                                                        ->where('financiacion_id', $pago->cuota->financiacion_id)
                                                        ->where('acreedor_id', $acreedor->id)
                                                        ->value('porcentaje') ?? 0;
                                                }
                                            @endphp
                                            <span class="badge bg-info">{{ $porcentaje }}%</span>
                                        </td>
                                        <td>
                                            @if(!$pago->sin_comprobante && $pago->comprobante)
                                            <button class="btn btn-sm btn-outline-primary" onclick="verComprobante('{{ $pago->comprobante }}')">
                                                <i class="fas fa-receipt"></i> Ver
                                            </button>
                                            @else
                                            <span class="badge bg-secondary">Sin comprobante</span>
                                            @endif
                                        </td>
                                    </tr>
                                    @endforeach
                                    
                                    <!-- Liquidaciones (con estilo diferente) -->
                                    @foreach($liquidaciones as $liquidacion)
                                    <tr class="table-info">
                                        <td>{{ \Carbon\Carbon::parse($liquidacion->fecha)->format('d/m/Y') }}</td>
                                        <td>${{ number_format($liquidacion->monto, 2) }}</td>
                                        <td><strong class="text-success">ADMIN</strong></td>
                                        <td>-</td>
                                        <td>
                                            @if(!$liquidacion->sin_comprobante && $liquidacion->comprobante)
                                            <button class="btn btn-sm btn-outline-primary" onclick="verComprobante('{{ $liquidacion->comprobante }}')">
                                                <i class="fas fa-receipt"></i> Ver
                                            </button>
                                            @else
                                            <span class="badge bg-secondary">Sin comprobante</span>
                                            @endif
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                    @else
                    <div class="alert alert-info mb-4">
                        No hay movimientos registrados en este mes.
                    </div>
                    @endif
                    
                    <div class="mb-3">
                        <table class="table table-sm">
                            <tr class="text-secondary">
                                <td><i class="fas fa-money-check-alt text-info"></i> Total a cobrar:</td>
                                <td class="text-end">${{ number_format($totalACobrar, 2) }}</td>
                            </tr>
                            
                            <tr>
                                <td><i class="fas fa-wallet text-success"></i> Total cobrado:</td>
                                <td class="text-end fw-bold text-success">${{ number_format($totalCobrado, 2) }}</td>
                            </tr>
                            
                            <tr>
                                <td><i class="fas fa-balance-scale"></i> Saldo:</td>
                                <td class="text-end {{ $saldo < 0 ? 'saldo-negativo' : 'saldo-positivo' }}">
                                    ${{ number_format(abs($saldo), 2) }}
                                    @if($saldo < 0) <span class="text-success">(saldo a favor)</span> @endif
                                </td>
                            </tr>
                        </table>
                    </div>
                    
                    <div class="d-grid gap-2 mt-4">
                        <button type="button" 
                                class="btn btn-dark" 
                                data-bs-toggle="modal" 
                                data-bs-target="#liquidarModal{{ $acreedor->id }}"
                                {{ $saldo <= 0 ? 'disabled' : '' }}>
                            <i class="fas fa-hand-holding-usd"></i> Liquidar Pago
                        </button>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Modal para liquidar pagos -->
        <div class="modal fade" id="liquidarModal{{ $acreedor->id }}" tabindex="-1" aria-labelledby="liquidarModalLabel{{ $acreedor->id }}" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="liquidarModalLabel{{ $acreedor->id }}">
                            Liquidar pago a {{ $acreedor->nombre }}
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form action="{{ route('api.acreedores.actualizar-saldo', $acreedor->id) }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <div class="modal-body">
                            <div class="alert alert-info">
                                <p class="mb-1"><strong>Saldo pendiente:</strong> ${{ number_format($saldo > 0 ? $saldo : 0, 2) }}</p>
                                <p class="mb-1"><strong>Saldo actual en cuenta:</strong> ${{ number_format($acreedor->saldo, 2) }}</p>
                            </div>
                            
                            <div class="mb-3">
                                <label for="montoLiquidacion{{ $acreedor->id }}" class="form-label">Monto a liquidar (USD)</label>
                                <input type="text" class="form-control" id="montoLiquidacion{{ $acreedor->id }}" 
                                       name="monto" value="{{ $saldo > 0 ? min($saldo, $acreedor->saldo) : 0 }}" required>
                                <small class="form-text text-muted">Ingrese el monto sin símbolos, ejemplo: 400.00</small>
                            </div>
                            
                            <div class="mb-3">
                                <label for="fechaLiquidacion{{ $acreedor->id }}" class="form-label">Fecha de liquidación</label>
                                <input type="date" class="form-control" id="fechaLiquidacion{{ $acreedor->id }}" 
                                       name="fecha_liquidacion" value="{{ date('Y-m-d') }}" required>
                            </div>
                            
                            <div class="form-check mb-3">
                                <input class="form-check-input" type="checkbox" id="sinComprobante{{ $acreedor->id }}" name="sin_comprobante" value="1">
                                <label class="form-check-label" for="sinComprobante{{ $acreedor->id }}">
                                    Marcar como pago sin comprobante
                                </label>
                            </div>
                            
                            <!-- Área de comprobante que se mostrará/ocultará -->
                            <div id="areaComprobante{{ $acreedor->id }}">
                                <div class="mb-3">
                                    <label for="comprobante{{ $acreedor->id }}" class="form-label">
                                        Comprobante de pago <span class="text-danger">*</span>
                                    </label>
                                    <input type="file" class="form-control" id="comprobante{{ $acreedor->id }}" 
                                           name="comprobante" accept=".jpg,.jpeg,.png,.pdf" required>
                                    <div class="form-text">
                                        Formatos permitidos: JPG, PNG, PDF. Máximo 2MB.
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Mensaje que se mostrará cuando esté marcado "sin comprobante" -->
                            <div id="mensajeSinComprobante{{ $acreedor->id }}" class="alert alert-warning" style="display: none;">
                                <i class="fas fa-exclamation-triangle"></i> Se registrará el pago sin comprobante.
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                            <button type="submit" class="btn btn-primary">Confirmar Liquidación</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        @endforeach

        <!-- Para el ADMIN (ID=1) - Caja oculta según solicitud -->
        {{--
        @if($adminAcreedor)
        <div class="col-md-6 mb-4">
            <div class="card h-100">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0">{{ $adminAcreedor->nombre }}</h5>
                </div>
                <div class="card-body">
                    @php
                    // Calculamos las entradas para Admin - Cuotas del mes
                    $cuotasMes = \App\Models\Cuota::whereHas('pagos', function($query) {
                        $query->whereMonth('created_at', now()->month)
                            ->whereYear('created_at', now()->year);
                    })->get();
                    
                    // Total de dinero por operaciones del mes
                    $totalOperacionesMes = $cuotasMes->sum('monto');
                    
                    // Calcular pagos ya realizados a todos los acreedores
                    $liquidacionesMes = \App\Models\Liquidacion::whereMonth('fecha', now()->month)
                        ->whereYear('fecha', now()->year)
                        ->get();
                                        
                    $totalLiquidado = $liquidacionesMes->sum('monto');
                    
                    // Calcular saldo disponible para liquidar
                    $saldoDisponible = $adminAcreedor->saldo;
                    @endphp
                    
                    <div class="mb-3">
                        <table class="table table-sm">
                            <tr class="text-secondary">
                                <td><i class="fas fa-university text-primary"></i> Dinero recibido este mes:</td>
                                <td class="text-end">${{ number_format($totalOperacionesMes, 2) }}</td>
                            </tr>
                            
                            <tr>
                                <td><i class="fas fa-money-bill-wave text-danger"></i> Liquidado a acreedores:</td>
                                <td class="text-end">${{ number_format($totalLiquidado, 2) }}</td>
                            </tr>
                            
                            <tr>
                                <td><i class="fas fa-coins text-success"></i> Saldo disponible:</td>
                                <td class="text-end text-success fw-bold">${{ number_format($saldoDisponible, 2) }}</td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        @endif
        --}}
    </div>
</div>
@endsection

@section('scripts')
<script src="{{ asset('js/acreedores-liquidaciones.js') }}"></script>
@endsection 