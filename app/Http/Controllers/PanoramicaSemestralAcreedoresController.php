<?php

namespace App\Http\Controllers;

use App\Models\Acreedor;
use App\Models\Cuota;
use App\Models\Liquidacion;
use App\Models\Pago;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use stdClass;

class PanoramicaSemestralAcreedoresController extends Controller
{
    private $mesesEspanol = [
        'January' => 'Ene',
        'February' => 'Feb',
        'March' => 'Mar',
        'April' => 'Abr',
        'May' => 'May',
        'June' => 'Jun',
        'July' => 'Jul',
        'August' => 'Ago',
        'September' => 'Sep',
        'October' => 'Oct',
        'November' => 'Nov',
        'December' => 'Dic'
    ];

    public function prepararDatos()
    {
        // Obtener acreedores excluyendo al admin (id=1)
        $acreedores = Acreedor::where('id', '!=', 1)->get();
        $datosSemestral = [];
        
        // Generar los últimos 6 meses
        $months = $this->getLastSixMonths();
        
        foreach ($acreedores as $acreedor) {
            $acreedorData = new stdClass;
            $acreedorData->id = $acreedor->id;
            $acreedorData->nombre = $acreedor->nombre;
            $acreedorData->totalesMes = [];
            $acreedorData->recibidoMes = [];
            $acreedorData->saldoMes = [];
            
            foreach ($months as $month) {
                $mesKey = $this->formatearMes($month);
                
                $datosMes = $this->obtenerDatosMes($acreedor, $month);
                
                // Total a cobrar (solo cuotas de ese mes)
                $acreedorData->totalesMes[$mesKey] = $datosMes['montoTotal'];
                
                // Total recibido (pagos + liquidaciones)
                $acreedorData->recibidoMes[$mesKey] = $datosMes['montoPagado'] + $datosMes['liquidaciones'];
                
                // Saldo (diferencia entre total y recibido)
                $acreedorData->saldoMes[$mesKey] = $datosMes['montoTotal'] - 
                    ($datosMes['montoPagado'] + $datosMes['liquidaciones']);
            }
            
            $datosSemestral[] = $acreedorData;
        }
        
        return [
            'acreedores' => $datosSemestral,
            'months' => $months
        ];
    }
    
    private function formatearMes(Carbon $date)
    {
        $nombreMes = $date->format('F');
        return $this->mesesEspanol[$nombreMes] . ' ' . $date->format('Y');
    }

    private function getLastSixMonths()
    {
        $currentDate = Carbon::now();
        $months = [];
        
        for ($i = 5; $i >= 0; $i--) {
            $months[] = $currentDate->copy()->subMonths($i)->startOfMonth();
        }
        
        return $months;
    }
    
    private function obtenerDatosMes($acreedor, $month)
    {
        $monthStart = $month->copy()->startOfMonth();
        $monthEnd = $month->copy()->endOfMonth();
        
        // Calcular total a cobrar del mes
        $montoTotal = $this->calcularTotalMes($acreedor, $monthStart, $monthEnd);
        
        // Calcular pagos recibidos en el mes
        $montoPagado = Pago::where('acreedor_id', $acreedor->id)
            ->whereBetween('created_at', [$monthStart, $monthEnd])
            ->sum('monto_usd');
        
        // Calcular liquidaciones del mes
        $liquidaciones = Liquidacion::where('acreedor_id', $acreedor->id)
            ->whereBetween('fecha', [$monthStart, $monthEnd])
            ->sum('monto');
            
        return [
            'montoTotal' => round($montoTotal, 2),
            'montoPagado' => round($montoPagado, 2),
            'liquidaciones' => round($liquidaciones, 2)
        ];
    }

    private function calcularTotalMes($acreedor, $monthStart, $monthEnd)
    {
        $total = 0;
        
        // Obtener todas las cuotas que vencen en este mes
        $cuotas = Cuota::where('fecha_de_vencimiento', '>=', $monthStart)
            ->where('fecha_de_vencimiento', '<=', $monthEnd)
            ->whereHas('financiacion', function($query) use ($acreedor) {
                $query->whereHas('acreedores', function($q) use ($acreedor) {
                    $q->where('acreedor_id', $acreedor->id);
                });
            })
            ->with(['financiacion' => function($query) use ($acreedor) {
                $query->with(['acreedores' => function($q) use ($acreedor) {
                    $q->where('acreedor_id', $acreedor->id);
                }]);
            }])
            ->get();

        foreach ($cuotas as $cuota) {
            // Obtener el porcentaje del acreedor para esta financiación
            $porcentaje = DB::table('financiacion_acreedor')
                ->where('financiacion_id', $cuota->financiacion_id)
                ->where('acreedor_id', $acreedor->id)
                ->value('porcentaje');

            if ($porcentaje) {
                // Calcular el monto que le corresponde al acreedor
                $montoAcreedor = $cuota->monto * ($porcentaje / 100);
                $total += $montoAcreedor;
            }
        }

        return $total;
    }

    private function calcularPagosMes($acreedor, $monthStart, $monthEnd)
    {
        return Pago::where('acreedor_id', $acreedor->id)
            ->whereBetween('created_at', [$monthStart, $monthEnd])
            ->sum('monto_usd');
    }
} 