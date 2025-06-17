@php
    if (!function_exists('supNum')) {
        function supNum($number) {
            return number_format($number, 2, '.', ',');
        }
    }
@endphp

<div class="card">
    <div class="card-header bg-white">
        <div class="text-center">
            <h5 class="mb-0 text-uppercase">
                <i class="fas fa-chart-line me-2 text-primary"></i>VISTA SEMESTRAL ACREEDORES
            </h5>
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
                                {{ $month->locale('es')->isoFormat('MMM YY') }}
                            </th>
                        @endforeach
                        
                        <!-- Columna de total -->
                        <th class="total-column text-center bg-light">
                            <div>Total</div>
                            <div class="small text-muted">semestral</div>
                        </th>
                        
                        <!-- Columna de pagos recibidos -->
                        <th class="total-column text-center bg-primary bg-opacity-10">
                            <div>Recibido</div>
                            <div class="small text-muted">semestral</div>
                        </th>
                        
                        <!-- Columna de saldo -->
                        <th class="total-column text-center bg-success bg-opacity-10">
                            <div>Saldo</div>
                            <div class="small text-muted">actual</div>
                        </th>
                    </tr>
                </thead>
                <tbody>
                    <!-- Filas para cada acreedor -->
                    @foreach($acreedores as $index => $acreedor)
                        @php
                            $totalSemestral = 0;
                            $totalLiquidaciones = 0;
                            $totalPagosDirectos = 0;
                            
                            // Obtener el saldo actual del acreedor desde la tabla
                            $acreedorOriginal = \App\Models\Acreedor::find($acreedor->id);
                            $saldoActual = $acreedorOriginal ? $acreedorOriginal->saldo : 0;
                        @endphp
                        
                        <tr>
                            <td class="align-middle">
                                <div class="d-flex align-items-center">
                                    <div class="acreedor-avatar me-2 bg-primary text-white">
                                        <i class="fas fa-user"></i>
                                    </div>
                                    <span>{{ $acreedor->nombre }}</span>
                                </div>
                            </td>
                            
                            <!-- Celdas para cada mes -->
                            @foreach($months as $month)
                                @php
                                    $mesKey = $month->format('Y-m');
                                    $monthStart = $month->copy()->startOfMonth();
                                    $monthEnd = $month->copy()->endOfMonth();
                                    
                                    // Total de cuotas para este mes y acreedor usando el mismo método del controlador
                                    $totalCuotasMes = \App\Models\Cuota::where('fecha_de_vencimiento', '>=', $monthStart)
                                        ->where('fecha_de_vencimiento', '<=', $monthEnd)
                                        ->whereIn('estado', ['pagada', 'parcial'])
                                        ->whereHas('financiacion', function($query) use ($acreedor) {
                                            $query->whereHas('acreedores', function($q) use ($acreedor) {
                                                $q->where('acreedor_id', $acreedor->id);
                                            });
                                        })
                                        ->with('pagos')  // Eager load pagos
                                        ->get()
                                        ->sum(function($cuota) use ($acreedor) {
                                            // Obtener el porcentaje del acreedor para esta financiación
                                            $porcentaje = DB::table('financiacion_acreedor')
                                                ->where('financiacion_id', $cuota->financiacion_id)
                                                ->where('acreedor_id', $acreedor->id)
                                                ->value('porcentaje');
                                            
                                            // Calcular el total de pagos de la cuota
                                            $totalPagos = $cuota->pagos->sum('monto_usd');
                                            
                                            // Aplicar el porcentaje al total de pagos
                                            return $porcentaje ? ($totalPagos * ($porcentaje / 100)) : 0;
                                        });
                                    
                                    // Liquidaciones (azul)
                                    $liquidaciones = isset($acreedor->liquidacionesMes[$mesKey]) ? $acreedor->liquidacionesMes[$mesKey] : 0;
                                    
                                    // Pagos directos al acreedor desde la tabla pagos
                                    $pagosDirectos = \App\Models\Pago::where('acreedor_id', $acreedor->id)
                                        ->whereBetween('created_at', [$monthStart, $monthEnd])
                                        ->sum('monto_usd');
                                    
                                    $totalSemestral += $totalCuotasMes;
                                    $totalLiquidaciones += $liquidaciones;
                                    $totalPagosDirectos += $pagosDirectos;
                                @endphp
                                
                                <td class="text-center align-middle">
                                    <div class="cell-content">
                                        @if($totalCuotasMes > 0)
                                            <div class="text-success">{!! supNum($totalCuotasMes) !!}</div>
                                        @else
                                            <div class="text-muted">-</div>
                                        @endif
                                        
                                        @if($liquidaciones > 0)
                                            <div class="badge bg-primary mb-1 w-100">Liq: {!! supNum($liquidaciones) !!}</div>
                                        @endif
                                        
                                        @if($pagosDirectos > 0)
                                            <div class="badge bg-primary mb-1 w-100">Pago: {!! supNum($pagosDirectos) !!}</div>
                                        @endif
                                    </div>
                                </td>
                            @endforeach
                            
                            <!-- Total semestral (desactivado) -->
                            <td class="text-center align-middle bg-light">
                                <span class="text-muted">U$D {!! supNum($totalSemestral) !!}</span>
                            </td>
                            
                            <!-- Total pagos recibidos -->
                            <td class="text-center align-middle bg-primary bg-opacity-10">
                                <span class="fw-bold text-primary">U$D {!! supNum($totalLiquidaciones + $totalPagosDirectos) !!}</span>
                            </td>
                            
                            <!-- Saldo actual -->
                            <td class="text-center align-middle bg-success bg-opacity-10">
                                @php
                                    $totalRecibido = $totalLiquidaciones + $totalPagosDirectos;
                                    $saldo = $totalSemestral - $totalRecibido;
                                    $colorSaldo = $saldo > 0 ? 'text-danger' : 'text-success';
                                @endphp
                                <span class="fw-bold {{ $colorSaldo }}">U$D {!! supNum($saldo) !!}</span>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="mt-3">
            <p class="small text-muted">
                <span class="badge bg-success me-1">Verde</span> Cuotas a recibir
                <span class="badge bg-primary ms-3 me-1">Azul</span> Recibido (liquidaciones + pagos)
            </p>
        </div>
    </div>
</div> 