@extends('layouts.app')

@section('title', 'Informes - Sistema Contable')

@section('styles')
<!-- Importar CSS específico de informes -->
<link href="{{ asset('css/informes.css') }}" rel="stylesheet">

<style>
    /* Ocultar los controles nativos de DataTables */
    .dataTables_length, .dataTables_filter, .dataTables_info, .dataTables_paginate {
        display: none !important;
    }
    
    /* Estilo para nuestro buscador personalizado */
    #dtSearchBox:focus {
        box-shadow: none;
        border-color: #ced4da;
    }
</style>

@endsection

@section('content')
    <div class="container mt-4">
        <div class="card informe-section">
            <div class="card-body">
                @if(isset($error))
                    <div class="alert alert-danger">
                        {{ $error }}
                    </div>
                    <div class="small text-muted bg-light p-3">
                        <pre>{{ $trace ?? '' }}</pre>
                    </div>
                @else
                    <!-- Selector de mes (siempre visible) -->
                    @if(isset($diagnostico))
                        @php
                            $mesActual = now()->month;
                            $anoActual = now()->year;
                            $esElMesActual = ($diagnostico['mes_consultado'] == $mesActual && $diagnostico['ano_consultado'] == $anoActual);
                        @endphp
                        
                        <!-- Fila con las 5 cajas de información ORIGINAL - Será toggleable -->
                        <div class="row mb-3" id="filaCajas">
                            <!-- Caja 1: Balance -->
                            <div class="col">
                                <div class="card bg-info text-white h-100">
                                    <div class="card-body text-center">
                                        <i class="fas fa-chart-line fa-2x mb-2"></i>
                                        <h6 class="card-title">Balance</h6>
                                        @php
                                            // Calcular número de cuotas pagadas y total de cuotas del mes
                                            $cuotasPagadas = 0;
                                            $totalCuotasMes = 0;
                                            
                                            if(isset($diagnostico['pasos'][1]['resultado'])) {
                                                foreach($diagnostico['pasos'][1]['resultado'] as $cuota) {
                                                    $fechaVencimiento = new DateTime($cuota->fecha_de_vencimiento);
                                                    if($fechaVencimiento->format('m') == $diagnostico['mes_consultado'] && 
                                                       $fechaVencimiento->format('Y') == $diagnostico['ano_consultado']) {
                                                        $totalCuotasMes++;
                                                        if($cuota->estado == 'pagada') {
                                                            $cuotasPagadas++;
                                                        }
                                                    }
                                                }
                                            }
                                        @endphp
                                        <h4>{{ $cuotasPagadas }} / {{ $totalCuotasMes }}</h4>
                                        <small>{{ $cuotasPagadas == $totalCuotasMes ? 'Todas al día' : 'Cuotas pagadas' }}</small>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Caja 2: Pagos Recibidos (verde) -->
                            <div class="col">
                                <div class="card bg-success text-white h-100">
                                    <div class="card-body text-center">
                                        <i class="fas fa-dollar-sign fa-2x mb-2"></i>
                                        <h6 class="card-title">Recibido</h6>
                                        @php
                                            $montoRecibido = 0;
                                            $saldoExcedente = 0;
                                            $pagosContados = 0;
                                            
                                            if(isset($diagnostico['pasos'][1]['resultado']) && isset($diagnostico['pasos'][2]['resultado'])) {
                                                // Crear un mapa de cuotas por ID para búsqueda rápida
                                                $cuotasPorId = [];
                                                foreach($diagnostico['pasos'][1]['resultado'] as $cuota) {
                                                    $cuotasPorId[$cuota->cuota_id] = $cuota;
                                                }
                                                
                                                // Procesar pagos
                                                foreach($diagnostico['pasos'][2]['resultado'] as $pago) {
                                                    // Verificar si es un pago que debe ignorarse
                                                    if(property_exists($pago, 'es_pago_excedente') && $pago->es_pago_excedente == 1) {
                                                        continue; // No considerar estos pagos
                                                    }
                                                    
                                                    $pagosContados++;
                                                    
                                                    // Verificar si hay excedente
                                                    if(isset($cuotasPorId[$pago->cuota_id])) {
                                                        $cuota = $cuotasPorId[$pago->cuota_id];
                                                        
                                                        if($pago->monto_usd > $cuota->monto) {
                                                            // Hay excedente
                                                            $montoRecibido += $cuota->monto;
                                                            $saldoExcedente += ($pago->monto_usd - $cuota->monto);
                                                        } else {
                                                            // No hay excedente
                                                            $montoRecibido += $pago->monto_usd;
                                                        }
                                                    } else {
                                                        // Si no encontramos la cuota, sumamos el monto completo
                                                        $montoRecibido += $pago->monto_usd;
                                                    }
                                                }
                                            }
                                        @endphp
                                        <h4>U$D {{ number_format($montoRecibido, 2) }}</h4>
                                        @if($saldoExcedente > 0)
                                            <div class="text-info fw-bold">* U$D {{ number_format($saldoExcedente, 2) }} (Saldo Excedente)</div>
                                        @endif
                                        <small>{{ $pagosContados }} pagos</small>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Caja 3: Pendientes (rojo) -->
                            <div class="col">
                                <div class="card bg-danger text-white h-100">
                                    <div class="card-body text-center">
                                        <i class="fas fa-exclamation-triangle fa-2x mb-2"></i>
                                        <h6 class="card-title">Pendientes</h6>
                                        @php
                                            // Mantener el cálculo de totalMes para que esté disponible para otras partes de la vista
                                            $totalMes = 0;
                                            
                                            if(isset($diagnostico['pasos'][1]['resultado'])) {
                                                foreach($diagnostico['pasos'][1]['resultado'] as $cuota) {
                                                    $fechaVencimiento = new DateTime($cuota->fecha_de_vencimiento);
                                                    if($fechaVencimiento->format('m') == $diagnostico['mes_consultado'] && 
                                                       $fechaVencimiento->format('Y') == $diagnostico['ano_consultado']) {
                                                        $totalMes += $cuota->monto;
                                                    }
                                                }
                                            }
                                            
                                            // Usar directamente el valor calculado en el controlador
                                            $montoPendiente = $diagnostico['totales']['deuda'];
                                            
                                            // Calcular cuantas cuotas están pendientes
                                            $cuotasPendientes = 0;
                                            if(isset($diagnostico['pasos'][1]['resultado'])) {
                                                foreach($diagnostico['pasos'][1]['resultado'] as $cuota) {
                                                    $fechaVencimiento = new DateTime($cuota->fecha_de_vencimiento);
                                                    if($fechaVencimiento->format('m') == $diagnostico['mes_consultado'] && 
                                                       $fechaVencimiento->format('Y') == $diagnostico['ano_consultado']) {
                                                        if($cuota->estado == 'pendiente' || $cuota->estado == 'parcial') {
                                                            $cuotasPendientes++;
                                                        }
                                                    }
                                                }
                                            }
                                        @endphp
                                        <h4>U$D {{ number_format($montoPendiente, 2) }}</h4>
                                        <small>{{ $cuotasPendientes }} pendientes</small>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Caja 4: Total Mes (azul) -->
                            <div class="col">
                                <div class="card bg-primary text-white h-100">
                                    <div class="card-body text-center">
                                        <i class="fas fa-calendar-alt fa-2x mb-2"></i>
                                        <h6 class="card-title">Total Mes</h6>
                                        <h4>U$D {{ number_format($totalMes, 2) }}</h4>
                                        <small>{{ $totalCuotasMes }} cuotas</small>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Caja 5: Navegador de mes -->
                            <div class="col">
                                <div class="card {{ $esElMesActual ? 'bg-warning' : 'bg-light' }} h-100">
                                    <div class="card-body p-2">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <a href="{{ route('informes.index', ['mes' => $diagnostico['mes_consultado'] == 1 ? 12 : $diagnostico['mes_consultado'] - 1, 'ano' => $diagnostico['mes_consultado'] == 1 ? $diagnostico['ano_consultado'] - 1 : $diagnostico['ano_consultado']]) }}" class="btn btn-sm btn-outline-primary">
                                                <i class="fas fa-chevron-left"></i>
                                            </a>
                                            <h6 class="mb-0 text-uppercase font-weight-bold">
                                                {{ Carbon\Carbon::createFromDate($diagnostico['ano_consultado'], $diagnostico['mes_consultado'], 1)->locale('es')->monthName }}
                                            </h6>
                                            <a href="{{ route('informes.index', ['mes' => $diagnostico['mes_consultado'] == 12 ? 1 : $diagnostico['mes_consultado'] + 1, 'ano' => $diagnostico['mes_consultado'] == 12 ? $diagnostico['ano_consultado'] + 1 : $diagnostico['ano_consultado']]) }}" class="btn btn-sm btn-outline-primary">
                                                <i class="fas fa-chevron-right"></i>
                                            </a>
                                        </div>
                                        <div class="text-center mt-2">
                                            <span class="badge bg-dark">{{ $diagnostico['ano_consultado'] }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Lista de Deudores con el botón de balance -->
                        <div class="card mt-4">
                            <div class="card-header bg-secondary text-white">
                                <div class="d-flex justify-content-between align-items-center">
                                    <h5 class="mb-0">
                                        <i class="fas fa-list"></i> 
                                        Cuotas Pendientes del mes de {{ Carbon\Carbon::createFromDate($diagnostico['ano_consultado'], $diagnostico['mes_consultado'], 1)->locale('es')->monthName }}
                                    </h5>
                                    <div>
                                        <button id="verBalance" class="btn btn-sm btn-light">
                                            <i class="fas fa-chart-line"></i> Ver Balance
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <div class="card-body">
                                <!-- Buscador personalizado exactamente como en los ejemplos -->
                                <div class="row justify-content-start mb-3">
                                    <div class="col-md-5">
                                        <div class="input-group">
                                            <span class="input-group-text bg-secondary text-white">
                                                <i class="fas fa-search"></i>
                                            </span>
                                            <input type="text" id="dtSearchBox" class="form-control" 
                                                   placeholder="Buscar deudor...">
                                        </div>
                                    </div>
                                </div>

                                @if(isset($diagnostico['deudores']) && count($diagnostico['deudores']) > 0)
                                    <div class="table-responsive">
                                        <table class="table table-bordered table-striped" id="tablaDeudores">
                                            <thead class="thead-dark">
                                                <tr>
                                                    <th>Nombre</th>
                                                    <th><i class="far fa-envelope"></i> Email</th>
                                                    <th><i class="fas fa-phone"></i> Teléfono</th>
                                                    <th>Valor de Cuota (U$D)</th>
                                                    <th>Deuda (U$D)</th>
                                                    <th>Acciones</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($diagnostico['deudores'] as $deudor)
                                                <tr id="fila-deudor-{{ $deudor->id }}" class="{{ $deudor->judicializado == 1 ? 'multipropietario' : '' }}">
                                                    <td>{{ $deudor->nombre }}</td>
                                                    <td>{{ $deudor->email }}</td>
                                                    <td>{{ $deudor->telefono }}</td>
                                                    <td data-sort="{{ $valorCuota ?? 0 }}">
                                                        @php
                                                            $valorCuota = 0;
                                                            if(isset($diagnostico['pasos'][1]['resultado'])) {
                                                                foreach ($diagnostico['pasos'][1]['resultado'] as $cuota) {
                                                                    if ($cuota->comprador_id == $deudor->id) {
                                                                        $valorCuota = $cuota->monto;
                                                                        break;
                                                                    }
                                                                }
                                                            }
                                                        @endphp
                                                        {{ number_format($valorCuota, 2) }}
                                                    </td>
                                                    <td data-sort="{{ $deuda ?? 0 }}">
                                                        @php
                                                            $deuda = 0;
                                                            if(isset($diagnostico['pasos'][1]['resultado'])) {
                                                                foreach ($diagnostico['pasos'][1]['resultado'] as $cuota) {
                                                                    if ($cuota->comprador_id == $deudor->id) {
                                                                        if ($cuota->estado == 'pendiente') {
                                                                            $deuda = $cuota->monto;
                                                                        } elseif ($cuota->estado == 'parcial') {
                                                                            $montoOriginal = $cuota->monto;
                                                                            $pagosRealizados = 0;
                                                                            
                                                                            if(isset($diagnostico['pasos'][2]['resultado'])) {
                                                                                foreach ($diagnostico['pasos'][2]['resultado'] as $pago) {
                                                                                    if (property_exists($pago, 'cuota_id') && $pago->cuota_id == $cuota->cuota_id && 
                                                                                        property_exists($pago, 'monto_usd')) {
                                                                                        $pagosRealizados += $pago->monto_usd;
                                                                                    }
                                                                                }
                                                                            }
                                                                            
                                                                    $deuda = $montoOriginal - $pagosRealizados;
                                                                        }
                                                                        break;
                                                                    }
                                                                }
                                                            }
                                                        @endphp
                                                        <span class="text-danger font-weight-bold">{{ number_format($deuda, 2) }}</span>
                                                    </td>
                                                    <td>
                                                        <div class="d-flex gap-2">
                                                            <a href="{{ route('comprador.show', $deudor->id) }}" class="btn btn-sm btn-primary" data-toggle="tooltip" title="Ver detalles">
                                                                <i class="fas fa-eye"></i>
                                                            </a>
                                                            <a href="mailto:{{ $deudor->email }}" class="btn btn-sm btn-warning" data-toggle="tooltip" title="Enviar email">
                                                                <i class="far fa-envelope"></i>
                                                            </a>
                                                            <a href="{{ route('pagos.index', ['comprador_id' => $deudor->id]) }}" class="btn btn-success btn-sm btn-accion" title="Registrar pago">
                                                                <i class="fas fa-money-bill-wave"></i>
                                                            </a>
                                                        </div>
                                                    </td>
                                                </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                @else
                                    <div class="alert alert-info">
                                        No hay deudores para mostrar en este período.
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endif
                @endif
            </div>
        </div>
    </div>
                                                
    <!-- Formulario oculto para actualizar el estado judicializado -->
    <form id="judicializar-form" method="POST" style="display: none;">
        @csrf
        @method('PATCH')
        <input type="hidden" name="judicializado" id="judicializado-value">
    </form>

    <!-- Sección de Detalles (agregar después de la tabla de deudores) -->
    <div class="card mt-4 border-secondary">
        <div class="card-header bg-secondary text-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0 text-center w-100">Mostrar/Ocultar Todas las cuotas</h5>
            <button class="btn btn-sm btn-light" type="button" data-bs-toggle="collapse" data-bs-target="#collapseDebug" aria-expanded="false">
                <i class="fas fa-chevron-down"></i>
            </button>
        </div>
        <div class="collapse" id="collapseDebug">
            <div class="card-body">
                @php
                    // Análisis de cuotas
                    $cuotasAnalisis = [];
                    $totalMonto = 0;
                    $totalPagado = 0;
                    $totalExcedente = 0;
                    $totalPendiente = 0;
                    $cuotasPagadas = 0;
                    $cuotasPendientes = 0;
                    $cuotasParciales = 0;
                    
                    if(isset($diagnostico['pasos'][1]['resultado'])) {
                        foreach($diagnostico['pasos'][1]['resultado'] as $cuota) {
                            $fechaVencimiento = new DateTime($cuota->fecha_de_vencimiento);
                            $enMesConsultado = ($fechaVencimiento->format('m') == $diagnostico['mes_consultado'] && 
                                               $fechaVencimiento->format('Y') == $diagnostico['ano_consultado']);
                            
                            // Calcular pagos para esta cuota
                            $pagadoEnCuota = 0;
                            $excedenteEnCuota = 0;
                            $pagosCuota = [];
                            
                            if(isset($diagnostico['pasos'][2]['resultado'])) {
                                foreach($diagnostico['pasos'][2]['resultado'] as $pago) {
                                    if($pago->cuota_id == $cuota->cuota_id) {
                                        // Verificar si es un pago que debe ignorarse
                                        if(property_exists($pago, 'es_pago_excedente') && $pago->es_pago_excedente == 1) {
                                            continue; // No considerar estos pagos
                                        }
                                        
                                        // Verificar si hay excedente
                                        if($pago->monto_usd > $cuota->monto) {
                                            $pagadoEnCuota += $cuota->monto;
                                            $excedenteEnCuota += ($pago->monto_usd - $cuota->monto);
                                            
                                            $pagosCuota[] = [
                                                'id' => $pago->id,
                                                'monto' => $pago->monto_usd,
                                                'excedente' => ($pago->monto_usd - $cuota->monto),
                                                'fecha' => property_exists($pago, 'fecha_pago') ? $pago->fecha_pago : '-'
                                            ];
                                        } else {
                                            $pagadoEnCuota += $pago->monto_usd;
                                            
                                            $pagosCuota[] = [
                                                'id' => $pago->id,
                                                'monto' => $pago->monto_usd,
                                                'excedente' => 0,
                                                'fecha' => property_exists($pago, 'fecha_pago') ? $pago->fecha_pago : '-'
                                            ];
                                        }
                                    }
                                }
                            }
                            
                            if($enMesConsultado) {
                                $totalMonto += $cuota->monto;
                                $totalPagado += min($pagadoEnCuota, $cuota->monto);
                                $totalExcedente += $excedenteEnCuota;
                                $totalPendiente += max(0, $cuota->monto - $pagadoEnCuota);
                                
                                if($cuota->estado == 'pagada') {
                                    $cuotasPagadas++;
                                } else if($cuota->estado == 'pendiente') {
                                    $cuotasPendientes++;
                                } else if($cuota->estado == 'parcial') {
                                    $cuotasParciales++;
                                }
                            }
                            
                            $cuotasAnalisis[] = [
                                'id' => $cuota->cuota_id,
                                'monto' => $cuota->monto,
                                'estado' => $cuota->estado,
                                'comprador' => $cuota->nombre_comprador,
                                'fecha_vencimiento' => is_object($cuota->fecha_de_vencimiento) ? 
                                                      $cuota->fecha_de_vencimiento->format('d') : 
                                                      (is_string($cuota->fecha_de_vencimiento) ? 
                                                       substr($cuota->fecha_de_vencimiento, 8, 2) : ''),
                                'en_mes_consultado' => $enMesConsultado,
                                'pagado' => min($pagadoEnCuota, $cuota->monto),
                                'excedente' => $excedenteEnCuota,
                                'pendiente' => max(0, $cuota->monto - $pagadoEnCuota),
                                'pagos' => $pagosCuota
                            ];
                        }
                    }
                @endphp
                
                <div class="table-responsive">
                    <table class="table table-sm table-striped table-bordered" id="tablaDetallesCuotas">
                        <thead class="table-dark">
                            <tr>
                                <th>Comprador</th>
                                <th>Valor de Cuota</th>
                                <th>Estado</th>
                                <th style="width: 80px;"><i class="fas fa-calendar"></i> Pago Día</th>
                                <th>Pagado</th>
                                <th>Excedente</th>
                                <th>Deuda</th>
                                <th style="width: 25%;">Detalles</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($cuotasAnalisis as $c)
                            <tr class="{{ $c['en_mes_consultado'] ? 'table-primary' : '' }}">
                                <td>{{ $c['comprador'] }}</td>
                                <td>{{ number_format($c['monto'], 2) }}</td>
                                <td>
                                    <span class="badge bg-{{ $c['estado'] == 'pagada' ? 'success' : ($c['estado'] == 'parcial' ? 'warning' : 'danger') }}">
                                        {{ $c['estado'] }}
                                    </span>
                                </td>
                                <td>{{ $c['fecha_vencimiento'] }}</td>
                                <td>{{ number_format($c['pagado'], 2) }}</td>
                                <td>
                                    @if($c['excedente'] > 0)
                                        <span class="text-primary">{{ number_format($c['excedente'], 2) }}</span>
                                    @else
                                        -
                                    @endif
                                </td>
                                <td>{{ number_format($c['pendiente'], 2) }}</td>
                                <td>
                                    @if($c['estado'] == 'pendiente')
                                        <span class="text-muted">Sin pagos</span>
                                    @else
                                        @php
                                            $acreedorInfo = '';
                                            // Busco el acreedor en los datos originales
                                            if(isset($diagnostico['pasos'][2]['resultado'])) {
                                                foreach($diagnostico['pasos'][2]['resultado'] as $pago) {
                                                    foreach($c['pagos'] as $p) {
                                                        if($pago->id == $p['id']) {
                                                            if(isset($pago->acreedor_id)) {
                                                                $acreedorInfo = obtenerNombreAcreedor($pago->acreedor_id);
                                                                break 2;
                                                            }
                                                        }
                                                    }
                                                }
                                            }
                                            // Si no se encontró información, mostrar guión por defecto
                                            if(empty($acreedorInfo) && $c['estado'] != 'pendiente') {
                                                $acreedorInfo = '-';
                                            }
                                        @endphp
                                        {{ $acreedorInfo }}
                                    @endif
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                        <tfoot class="table-dark">
                            <tr>
                                <td><strong>TOTALES</strong></td>
                                <td>U$D {{ number_format($totalMonto, 2) }}</td>
                                <td>
                                    <span class="badge bg-success">{{ $cuotasPagadas }} pagadas</span>
                                    <span class="badge bg-warning">{{ $cuotasParciales }} parciales</span>
                                    <span class="badge bg-danger">{{ $cuotasPendientes }} pendientes</span>
                                </td>
                                <td></td>
                                <td>U$D {{ number_format($totalPagado, 2) }}</td>
                                <td>
                                    <span class="text-primary">U$D {{ number_format($totalExcedente, 2) }}</span>
                                </td>
                                <td>U$D {{ number_format($totalPendiente, 2) }}</td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>

@endsection

@php
function obtenerNombreAcreedor($acreedorId) {
    // Si el ID es 1, retornamos guión
    if($acreedorId == 1) {
        return '-';
    }
    
    // Buscar en la base de datos directamente sin afectar otros métodos
    $acreedor = \App\Models\Acreedor::find($acreedorId);
    
    if($acreedor) {
        return 'Recibió ' . $acreedor->nombre;
    } else {
        return 'Recibió (Acreedor no encontrado)';
    }
}
@endphp 