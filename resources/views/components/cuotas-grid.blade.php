@props(['cuotas', 'showRegistrarPago' => true])

@php
    // Fechas básicas para comparación
    $hoy = \Carbon\Carbon::now();
    $inicioMes = \Carbon\Carbon::now()->startOfMonth();
    $finMes = \Carbon\Carbon::now()->endOfMonth();
@endphp

@push('styles')
    <link href="{{ asset('css/cuotas-grid.css') }}" rel="stylesheet">
@endpush

<div class="row row-cols-1 row-cols-md-2 row-cols-lg-4 g-4">
    @foreach($cuotas as $cuota)
        @php
            // Determinar el estilo basado en el estado y la fecha
            $cardClass = '';
            $estadoBadge = '';
            $estadoText = '';
            
            if ($cuota->estado == 'pagada') {
                // Pagado - VERDE
                $cardClass = 'cuota-pagada estado-pagada';
                $estadoBadge = 'bg-success';
                $estadoText = 'Pagada';
            } elseif ($cuota->estado == 'parcial') {
                // Pago parcial - NARANJA
                $cardClass = 'cuota-parcial estado-parcial';
                $estadoBadge = 'bg-warning';
                $estadoText = 'Pago Parcial';
            } elseif ($cuota->fecha_de_vencimiento < $inicioMes) {
                // Adeuda - ROJO con borde (mes anterior)
                $cardClass = 'cuota-adeuda';
                $estadoBadge = 'bg-danger';
                $estadoText = 'Adeuda';
            } elseif ($cuota->fecha_de_vencimiento <= $hoy) {
                // Vencido - ROJO (mismo mes, pasó la fecha)
                $cardClass = 'cuota-vencida';
                $estadoBadge = 'bg-danger';
                $estadoText = 'Vencida';
            } elseif ($cuota->fecha_de_vencimiento <= $finMes) {
                // Pendiente - AMARILLO (mismo mes, antes de la fecha)
                $cardClass = 'cuota-pendiente';
                $estadoBadge = 'bg-warning text-dark';
                $estadoText = 'Pendiente';
            } else {
                // Pendiente futuro
                $cardClass = 'cuota-futura';
                $estadoBadge = 'bg-secondary';
                $estadoText = 'Futura';
            }
            
            // Obtener pagos y calcular saldo pendiente usando monto_usd
            $pagos = \App\Models\Pago::where('cuota_id', $cuota->id)->orderBy('created_at', 'desc')->get();
            $totalPagado = $pagos->sum('monto_usd');
            $saldoPendiente = $cuota->monto - $totalPagado;
        @endphp
        
        <div class="col">
            <div class="card cuota-card {{ $cardClass }}" id="cuota-{{ $cuota->id }}">
                <div class="cuota-header bg-light d-flex justify-content-between align-items-center">
                    <span>Cuota #{{ $cuota->numero_de_cuota }}</span>
                    <span class="badge {{ $estadoBadge }} badge-cuota">
                        {{ $estadoText }}
                    </span>
                </div>
                <div class="card-body">
                    <p class="card-text">U$D {{ number_format($cuota->monto, 2, ',', '.') }}</p>
                    <p class="card-text"><strong>Vencimiento:</strong><br> {{ $cuota->fecha_de_vencimiento->format('d-m-Y') }}</p>
                    
                    @if($cuota->estado === 'pagada' || $cuota->estado === 'parcial')
                        <div class="mb-1">
                            <span class="{{ $cuota->estado === 'pagada' ? 'text-success' : 'text-warning' }}">
                                <i class="{{ $cuota->estado === 'pagada' ? 'fas fa-check-circle' : 'fas fa-clock' }}"></i> 
                                {{ $cuota->estado === 'pagada' ? 'Pagada' : 'Pago Parcial' }}
                            </span>
                            <small class="text-muted d-block">
                                Fecha último pago: {{ $cuota->updated_at->format('d/m/Y') }}
                            </small>
                            
                            @if($pagos->count() > 0)
                                <small class="text-muted d-block">
                                    Total pagado: USD {{ number_format($totalPagado, 2) }}
                                </small>
                                
                                @if($cuota->estado === 'parcial')
                                    <small class="text-danger d-block">
                                        <strong>Saldo pendiente: USD {{ number_format($saldoPendiente, 2) }}</strong>
                                    </small>
                                @endif
                                
                                <!-- Excedente (solo si es negativo) -->
                                @if($saldoPendiente < 0)
                                    <small class="text-primary d-block">
                                        <strong>Saldo excedente: USD {{ number_format(abs($saldoPendiente), 2) }}</strong>
                                    </small>
                                @endif
                                
                                <!-- PAGOS INDIVIDUALES -->
                                @foreach($pagos as $pago)
                                    <div class="pago-item border-top mt-2 pt-1">
                                        <small class="{{ ($pago->sin_comprobante && !$pago->comprobante) ? 'text-primary' : 'text-muted' }} d-block">
                                            <i class="{{ ($pago->sin_comprobante && !$pago->comprobante) ? 'fas fa-star' : 'fas fa-receipt' }}"></i>
                                            Pago {{ $loop->iteration }}: 
                                            @if($pago->pago_divisa)
                                                ${{ number_format($pago->monto_pagado, 2) }} ARS
                                            @else
                                                U$D {{ number_format($pago->monto_usd, 2) }}
                                            @endif
                                            <span class="d-block">{{ $pago->fecha_de_pago->format('d/m/Y') }}</span>
                                            
                                            @if($pago->sin_comprobante && !$pago->comprobante)
                                                <span class="text-primary d-block"><strong>* Pago con saldo excedente</strong></span>
                                            @endif
                                        </small>
                                        
                                        @if($pago->comprobante && !$pago->sin_comprobante)
                                            <a href="{{ route('pagos.comprobante', $pago->id) }}" 
                                               class="btn btn-sm btn-outline-info mt-1" 
                                               target="_blank">
                                                <i class="fas fa-eye me-1"></i> Ver comprobante
                                            </a>
                                        @endif
                                    </div>
                                @endforeach
                            @endif
                        </div>
                        
                        @if($showRegistrarPago && $cuota->estado === 'parcial')
                            <button class="btn btn-sm btn-warning mt-2 w-100 registrar-pago" 
                                    data-cuota-id="{{ $cuota->id }}"
                                    data-cuota-monto="{{ $saldoPendiente }}"
                                    data-bs-toggle="modal" 
                                    data-bs-target="#registrarPagoModal">
                                <i class="fas fa-money-bill-wave me-1"></i> Completar Pago
                            </button>
                        @endif
                    @elseif($showRegistrarPago)
                        <button class="btn btn-sm btn-dark mt-2 w-100 registrar-pago" 
                                data-cuota-id="{{ $cuota->id }}"
                                data-cuota-monto="{{ $cuota->monto }}"
                                data-bs-toggle="modal" 
                                data-bs-target="#registrarPagoModal">
                            <i class="fas fa-money-bill-wave me-1"></i> Registrar Pago
                        </button>
                    @endif
                </div>
            </div>
        </div>
    @endforeach
</div> 