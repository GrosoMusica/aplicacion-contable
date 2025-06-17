<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Distribución de Ingresos - {{ $acreedor->nombre }}</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 12px; }
        .subtitle { font-size: 18px; font-weight: bold; margin-bottom: 10px; }
        .acreedor-nombre { font-size: 16px; font-weight: bold; color: #3366cc; margin-bottom: 8px; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        table, th, td { border: 1px solid #ddd; }
        th, td { padding: 8px; text-align: left; }
        th { background-color: #f3f3f3; }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        tfoot { font-weight: bold; }
        .info-cabecera { margin-bottom: 20px; }
        .mes-navegacion { display: flex; justify-content: space-between; margin-bottom: 15px; }
        .mes-navegacion .mes-anterior { float: left; }
        .mes-navegacion .mes-siguiente { float: right; }
        .vista-meses { font-size: 11px; margin-top: 20px; color: #666; }
    </style>
</head>
<body>
    <div class="subtitle">Distribución de Ingresos - {{ $acreedor->nombre }}</div>
    
    <div class="info-cabecera">
        <div>Mes Actual: {{ $mesActual }}</div>
    </div>
    
    <div class="mes-navegacion">
        <div class="mes-anterior">« Mes Anterior</div>
        <div class="mes-siguiente">Mes Siguiente »</div>
    </div>
    
    <table>
        <thead>
            <tr>
                <th>Comprador</th>
                <th class="text-center">Porcentaje</th>
                <th class="text-center">Estado</th>
                <th class="text-right">Monto</th>
            </tr>
        </thead>
        <tbody>
            @if(count($financiaciones) > 0)
                @foreach($financiaciones as $item)
                    <tr>
                        <td>{{ $item->nombre_comprador }}</td>
                        <td class="text-center">{{ number_format($item->porcentaje, 2) }}%</td>
                        <td class="text-center">{{ strtoupper($item->estado) }}</td>
                        <td class="text-right">
                            @if($item->estado == 'pagada' || $item->estado == 'pagado')
                                {{ number_format($item->monto_porcentaje, 2) }}
                            @elseif($item->estado == 'parcial')
                                {{ number_format($item->monto_pagado_acreedor, 2) }}
                            @else
                                0.00
                            @endif
                        </td>
                    </tr>
                @endforeach
            @else
                <tr>
                    <td colspan="4" class="text-center">No hay financiaciones activas</td>
                </tr>
            @endif
        </tbody>
        <tfoot>
            <tr>
                <th colspan="3" class="text-right">Total Mes:</th>
                <th class="text-right">U$D {{ number_format($montoTotalMes, 2) }}</th>
            </tr>
        </tfoot>
    </table>
   
    
    <div style="margin-top: 30px; font-size: 11px;">
        <p>Fecha de generación: {{ $fechaGeneracion }}</p>
    </div>
</body>
</html> 