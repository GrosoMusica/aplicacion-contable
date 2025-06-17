@extends('layouts.app')

@section('title', 'Gestión de Pagos')

@section('styles')
    <!-- Estilos personalizados para pagos -->
    <link href="{{ asset('css/pagos.css') }}" rel="stylesheet">
    <style>
        .filter-card {
            border-left: 4px solid #212529;
            display: none; /* Ocultar filtro actual */
        }
        
        /* Estilos para el nuevo layout */
        .sidebar-info {
            padding-left: 15px;
            transition: all 0.3s ease;
        }
        
        /* Hacer la barra lateral fija al hacer scroll */
        .sidebar-sticky {
            position: sticky;
            top: 20px; /* Distancia desde la parte superior */
            height: fit-content;
        }
        
        /* Fondo transparente para la tarjeta de cuotas */
        .cuotas-container {
            background-color: transparent;
            border: none;
        }
        
        .cuotas-container .card-header {
            background-color: #0d6efd;
            color: white;
            border-bottom: 1px solid rgba(0, 0, 0, 0.125);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .cuotas-container .card-body {
            background-color: transparent;
            padding-top: 0;
        }
        
        /* Botón para ocultar/mostrar panel */
        .toggle-sidebar-btn {
            background-color: transparent;
            border: none;
            color: rgba(255,255,255,0.7);
            cursor: pointer;
            padding: 5px 10px;
            border-radius: 3px;
            transition: all 0.2s;
            font-weight: 300;
            font-size: 0.9rem;
        }
        
        .toggle-sidebar-btn:hover {
            color: white;
        }
        
        /* Distribución del encabezado */
        .header-left {
            width: 30%;
            text-align: left;
        }
        
        .header-center {
            width: 40%;
            text-align: center;
            font-weight: 500;
        }
        
        .header-right {
            width: 30%;
            text-align: right;
        }
        
        /* Clases para cuando el panel esté oculto */
        .main-content-expanded {
            flex: 0 0 100%;
            max-width: 100%;
        }
        
        .sidebar-hidden {
            display: none;
        }
        
        /* Formato para el monto y fecha en línea */
        .vencimiento-monto {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        @media (max-width: 767px) {
            .sidebar-info {
                padding-left: 0;
                margin-top: 20px;
            }
        }
    </style>
@endsection

@section('content')
    <!-- Filtros de Búsqueda (ahora oculto) -->
    <div class="card filter-card mb-4">
        <div class="card-body">
            <form id="filtroForm" action="{{ route('pagos.index') }}" method="GET">
                <div class="row">
                    <div class="col-md-3">
                        <div class="mb-3">
                            <label for="comprador" class="form-label">Seleccionar Comprador</label>
                            <select id="comprador" name="comprador_id" class="form-select">
                                <option value="">-- Seleccione un comprador --</option>
                                @foreach($compradores as $comprador)
                                    <option value="{{ $comprador->id }}" {{ request('comprador_id') == $comprador->id ? 'selected' : '' }}>
                                        {{ $comprador->nombre }} {{ $comprador->apellido }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="mb-3">
                            <label for="dni" class="form-label">DNI</label>
                            <input type="text" id="dni" name="dni" class="form-control" value="{{ request('dni') }}">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" id="email" name="email" class="form-control" value="{{ request('email') }}">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="mb-3">
                            <label for="lote" class="form-label">Lote</label>
                            <input type="text" id="lote" name="lote" class="form-control" value="{{ request('lote') }}">
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-12 text-end">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-search me-1"></i> Buscar
                        </button>
                        <a href="{{ route('pagos.index') }}" class="btn btn-secondary ms-2">
                            <i class="fas fa-redo me-1"></i> Limpiar
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Información del Comprador (si se seleccionó uno) -->
    @if(isset($compradorSeleccionado) && $compradorSeleccionado)
        <!-- Botón flotante para mostrar detalles -->
        <button id="toggleDetails" class="btn-float text-uppercase">
            <span>{{ $compradorSeleccionado->nombre }} {{ $compradorSeleccionado->apellido }}</span>
            <i class="fas fa-angle-right ms-2"></i>
        </button>
        
        <!-- Panel flotante con detalles (inicialmente oculto) -->
        <div id="detailsPanel" class="panel-float panel-hidden">
            <div class="panel-body">
                <!-- Datos del comprador -->
                <div class="card">
                    <div class="card-header">
                        Datos del Comprador
                    </div>
                    <div class="card-body">
                        <p><strong>Nombre:</strong> {{ $compradorSeleccionado->nombre }} {{ $compradorSeleccionado->apellido }}</p>
                        <p><strong>Dirección:</strong> {{ $compradorSeleccionado->direccion }}</p>
                        <p><strong>DNI:</strong> {{ $compradorSeleccionado->dni }}</p>
                        <p><strong>Teléfono:</strong> {{ $compradorSeleccionado->telefono }}</p>
                        <p><strong>Email:</strong> {{ $compradorSeleccionado->email }}</p>
                    </div>
                </div>
                
                <!-- Información del lote -->
                <div class="card">
                    <div class="card-header">
                        Información del Lote
                    </div>
                    <div class="card-body">
                        <p><strong>Manzana:</strong> {{ $compradorSeleccionado->lote->manzana }}</p>
                        <p><strong>Lote:</strong> {{ $compradorSeleccionado->lote->lote }}</p>
                        <p><strong>Loteo:</strong> {{ $compradorSeleccionado->lote->loteo }}</p>
                        <p><span style="font-size: 1.2rem; font-style: italic;">{{ $compradorSeleccionado->lote->mts_cuadrados }} mt<sup>2</sup></span></p>
                    </div>
                </div>
                
                <!-- Botón para cerrar el panel -->
                <button id="closePanel" class="btn btn-outline-secondary w-100 mt-3">
                    <i class="fas fa-times me-2"></i>Cerrar
                </button>
            </div>
        </div>
        
        <!-- Grid de Cuotas (directamente 4 columnas sin encabezado) -->
        <div class="container-fluid">
            <div class="row row-cols-1 row-cols-md-2 row-cols-lg-4 g-4 cuotas-grid" id="cuotasContainer">
                @php
                    $hoy = \Carbon\Carbon::now();
                    $inicioMes = \Carbon\Carbon::now()->startOfMonth();
                    $finMes = \Carbon\Carbon::now()->endOfMonth();
                    $idCuotaActual = '';
                    
                    // Encontrar la cuota más cercana a la fecha actual para scroll
                    $fechaActual = \Carbon\Carbon::now();
                    $cuotaMasCercana = null;
                    $diferenciaMinima = PHP_INT_MAX;
                    
                    foreach($cuotas as $cuota) {
                        // Si es el mes actual o está pendiente, considerar para scroll
                        if ($cuota->estado != 'pagada') {
                            $diferencia = abs($fechaActual->diffInDays($cuota->fecha_de_vencimiento, false));
                            if ($diferencia < $diferenciaMinima) {
                                $diferenciaMinima = $diferencia;
                                $cuotaMasCercana = $cuota;
                            }
                        }
                    }
                    
                    if ($cuotaMasCercana) {
                        $idCuotaActual = 'cuota-' . $cuotaMasCercana->id;
                    }
                @endphp
                
                @foreach($cuotas as $index => $cuota)
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
                                <div class="vencimiento-monto mb-2 d-flex justify-content-between align-items-center">
                                    <div><strong>Vencimiento:</strong> {{ $cuota->fecha_de_vencimiento->format('d-m-Y') }}</div>
                                    <div><strong>U$D</strong> {{ number_format($cuota->monto, 2, ',', '.') }}</div>
                                </div>
                                
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
                                                @php
                                                    $totalPagadoUSD = $pagos->sum('monto_usd');
                                                    $totalPagadoPesos = $pagos->where('pago_divisa', 1)->sum('monto_pagado');
                                                    $totalPagadoDirectoUSD = $pagos->where('pago_divisa', 0)->sum('monto_usd');
                                                    
                                                    // Si hubo pagos en pesos, mostrar el total en ambas monedas
                                                    $mostrarDobleMoneda = $totalPagadoPesos > 0 && $totalPagadoDirectoUSD > 0;
                                                @endphp
                                                
                                                Total pagado: 
                                                @if($mostrarDobleMoneda)
                                                    ARS ${{ number_format($totalPagadoPesos, 2) }} + USD {{ number_format($totalPagadoDirectoUSD, 2) }}
                                                    <br>(Total USD: {{ number_format($totalPagadoUSD, 2) }})
                                                @elseif($totalPagadoPesos > 0)
                                                    ARS ${{ number_format($totalPagadoPesos, 2) }}
                                                    <br>(Equivale a USD {{ number_format($totalPagadoUSD, 2) }})
                                                @else
                                                    USD {{ number_format($totalPagadoUSD, 2) }}
                                                @endif
                                            </small>
                                            
                                            @if($cuota->estado === 'parcial')
                                                <small class="text-danger d-block">
                                                    <strong>Saldo pendiente: USD {{ number_format($saldoPendiente, 2) }}</strong>
                                                </small>
                                            @elseif($saldoPendiente < 0)
                                                <!-- Solo mostrar el excedente si no hay saldo pendiente -->
                                                <small class="text-primary d-block">
                                                    <strong>Saldo excedente: USD {{ number_format(abs($saldoPendiente), 2) }}</strong>
                                                </small>
                                            @endif
                                            
                                            <!-- PAGOS -->
                                            @foreach($pagos as $pago)
                                                <div class="pago-item border-top mt-2 pt-1">
                                                    <small class="{{ isset($pago->es_pago_excedente) && $pago->es_pago_excedente ? 'text-primary' : 'text-muted' }} d-block">
                                                        <i class="{{ isset($pago->es_pago_excedente) && $pago->es_pago_excedente ? 'fas fa-star' : 'fas fa-receipt' }}"></i>
                                                        @if($pago->pago_divisa)
                                                            ${{ number_format($pago->monto_pagado, 2) }} ARS
                                                        @else
                                                            U$D {{ number_format($pago->monto_usd, 2) }}
                                                        @endif
                                                        <span class="d-block">{{ $pago->fecha_de_pago->format('d/m/Y') }}</span>
                                                        
                                                        @if(isset($pago->es_pago_excedente) && $pago->es_pago_excedente)
                                                            <span class="text-primary d-block"><strong>* Pago con saldo excedente</strong></span>
                                                        @endif
                                                    </small>
                                                    
                                                    @if($pago->comprobante)
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
                                    
                                    @if($cuota->estado === 'parcial')
                                        <button class="btn btn-sm btn-warning mt-2 w-100 registrar-pago" 
                                                data-cuota-id="{{ $cuota->id }}"
                                                data-cuota-monto="{{ $saldoPendiente }}"
                                                data-financiacion-id="{{ $cuota->financiacion_id }}"
                                                data-bs-toggle="modal" 
                                                data-bs-target="#registrarPagoModal">
                                            <i class="fas fa-money-bill-wave me-1"></i> Completar Pago
                                        </button>
                                    @endif
                                @else
                                    <button class="btn btn-sm btn-dark mt-2 w-100 registrar-pago" 
                                            data-cuota-id="{{ $cuota->id }}"
                                            data-cuota-monto="{{ $cuota->monto }}"
                                            data-financiacion-id="{{ $cuota->financiacion_id }}"
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
        </div>
    @else
        <div class="alert alert-info">
            <i class="fas fa-info-circle me-2"></i> Seleccione un comprador para ver sus cuotas.
        </div>
    @endif

    <!-- Incluir el modal de registro de pago como componente separado -->
    @include('pagos.components.registrar-pago-modal')

    <!-- Modal de Historial de Pagos -->
    <div class="modal fade" id="historialPagosModal" tabindex="-1" aria-labelledby="historialPagosModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="historialPagosModalLabel">Historial de Pagos</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="historialPagosBody">
                    <div class="text-center">
                        <div class="spinner-border" role="status">
                            <span class="visually-hidden">Cargando...</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        $(document).ready(function() {
            // Toggle para el panel flotante
            $('#toggleDetails').on('click', function() {
                $('#detailsPanel').toggleClass('panel-hidden');
                $(this).find('i').toggleClass('fa-angle-right fa-angle-down');
            });
            
            // Cerrar panel
            $('#closePanel').on('click', function() {
                $('#detailsPanel').addClass('panel-hidden');
                $('#toggleDetails').find('i').removeClass('fa-angle-down').addClass('fa-angle-right');
            });
            
            // Hacer scroll hasta la cuota más cercana a la fecha actual
            @if(isset($idCuotaActual))
                // Retrasar un poco para asegurar que la página está completamente cargada
                setTimeout(function() {
                    const element = document.getElementById('{{ $idCuotaActual }}');
                    if (element) {
                        element.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    }
                }, 500);
            @endif
        });
    </script>
@endsection 