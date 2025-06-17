@extends('pdf.layouts.pdf-base')

@section('content')
    <div class="title">Resumen de Acreedores</div>
    
    <table>
        <thead>
            <tr>
                <th>Acreedor</th>
                
                <!-- Meses -->
                @foreach($months as $month)
                    <th class="text-center">{{ $month->locale('es')->format('M y') }}</th>
                @endforeach
                
                <!-- Totales -->
                <th class="text-center">Pagado</th>
                <th class="text-center">Saldo</th>
                <th class="text-center">Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($acreedores as $acreedor)
                <tr>
                    <td>
                        <strong>{{ $acreedor->nombre }}</strong>
                        <div class="small text-gray">{{ count($acreedor->financiaciones) }} financiaciones</div>
                    </td>
                    
                    <!-- Datos por mes -->
                    @foreach($months as $month)
                        <td class="text-center">
                            @php
                                $monthData = $monthlyData[$acreedor->id][$month->format('Y-m')] ?? null;
                                $monto = $monthData ? $monthData['monto'] : 0;
                                $estado = $monthData ? $monthData['estado'] : 'pendiente';
                            @endphp
                            
                            @if($estado == 'pagado')
                                <span class="text-green">U$D {{ number_format($monto, 2) }}</span>
                            @elseif($estado == 'parcial')
                                <span class="text-yellow">U$D {{ number_format($monto, 2) }}</span>
                            @else
                                <span class="text-gray">-</span>
                            @endif
                        </td>
                    @endforeach
                    
                    <!-- Totales -->
                    @php
                        $totales = $totalesData[$acreedor->id] ?? [
                            'pagado' => 0,
                            'pendiente' => 0,
                            'total' => 0,
                            'saldoAFavor' => false
                        ];
                    @endphp
                    
                    <td class="text-center text-green">
                        <strong>U$D {{ number_format($totales['pagado'], 2) }}</strong>
                    </td>
                    <td class="text-center">
                        @if($totales['saldoAFavor'])
                            <strong class="text-green">U$D {{ number_format($totales['pendiente'], 2) }}</strong>
                            <div class="small text-green">saldo a favor</div>
                        @else
                            <strong class="text-red">U$D {{ number_format($totales['pendiente'], 2) }}</strong>
                        @endif
                    </td>
                    <td class="text-center text-gray">
                        <strong>U$D {{ number_format($totales['total'], 2) }}</strong>
                    </td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <th>TOTALES</th>
                
                <!-- Totales por mes -->
                @foreach($months as $month)
                    <th class="text-center">
                        @php
                            $totalMes = 0;
                            foreach($acreedores as $acreedor) {
                                $monthData = $monthlyData[$acreedor->id][$month->format('Y-m')] ?? null;
                                $totalMes += $monthData ? $monthData['monto'] : 0;
                            }
                        @endphp
                        U$D {{ number_format($totalMes, 2) }}
                    </th>
                @endforeach
                
                <!-- Totales generales -->
                @php
                    $grandTotalPagado = 0;
                    $grandTotalPendiente = 0;
                    
                    foreach($acreedores as $acreedor) {
                        $totales = $totalesData[$acreedor->id] ?? null;
                        if ($totales) {
                            $grandTotalPagado += $totales['pagado'];
                            $grandTotalPendiente += $totales['pendiente'];
                        }
                    }
                    
                    $grandTotal = $grandTotalPagado + $grandTotalPendiente;
                @endphp
                
                <th class="text-center text-green">U$D {{ number_format($grandTotalPagado, 2) }}</th>
                <th class="text-center text-red">U$D {{ number_format($grandTotalPendiente, 2) }}</th>
                <th class="text-center text-gray">U$D {{ number_format($grandTotal, 2) }}</th>
            </tr>
        </tfoot>
    </table>
@endsection 