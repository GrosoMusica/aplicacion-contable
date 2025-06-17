<?php

namespace App\Http\Controllers;

use App\Models\Acreedor;
use App\Models\Cuota;
use App\Models\Financiacion;
use App\Models\Liquidacion;
use App\Models\Pago;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use stdClass;

class PanoramicaSemestralController extends Controller
{
    /**
     * Preparar datos para el componente de panorámica semestral
     */
    public function prepararDatos()
    {
        // Obtener todos los acreedores
        $acreedores = Acreedor::all();
        $datosSemestral = []; // Este array guardará los datos procesados
        
        // Generar los últimos 6 meses (incluyendo el actual)
        $months = $this->getLastSixMonths();
        
        // Preparar datos para cada acreedor (usando objetos independientes, NO los modelos)
        foreach ($acreedores as $acreedor) {
            // Crear un objeto independiente para cada acreedor (NO modificar el modelo)
            $acreedorData = new stdClass;
            $acreedorData->id = $acreedor->id;
            $acreedorData->nombre = $acreedor->nombre;
            $acreedorData->totalesMes = [];
            $acreedorData->liquidacionesMes = [];
            
            // Calcular totales para cada mes
            foreach ($months as $month) {
                $mesKey = $month->format('Y-m');
                
                // Datos para este mes (usando métodos auxiliares)
                $datosMes = $this->obtenerDatosMes($acreedor, $month);
                
                // Guardar datos en el objeto independiente (NO en el modelo)
                $acreedorData->totalesMes[$mesKey] = $datosMes['montoPagado'];
                $acreedorData->liquidacionesMes[$mesKey] = $datosMes['liquidaciones'];
            }
            
            // Agregar el objeto al array de resultados
            $datosSemestral[] = $acreedorData;
        }
        
        return [
            'acreedores' => $datosSemestral, // Devolver objetos independientes, NO modelos
            'months' => $months
        ];
    }
    
    /**
     * Obtener los últimos 6 meses
     */
    private function getLastSixMonths()
    {
        $currentDate = Carbon::now();
        $months = [];
        
        for ($i = 5; $i >= 0; $i--) {
            $date = clone $currentDate;
            $date->subMonths($i);
            $months[] = $date;
        }
        
        return $months;
    }
    
    /**
     * Obtener todos los datos para un acreedor en un mes específico
     * Similar a la lógica usada en las pestañas
     */
    private function obtenerDatosMes($acreedor, $month)
    {
        $monthStart = $month->copy()->startOfMonth();
        $monthEnd = $month->copy()->endOfMonth();
        
        // Inicializar los valores
        $montoPagado = 0;
        $montoPendiente = 0;
        $montoTotal = 0;
        
        // Obtener financiaciones de este acreedor
        $financiacionesIds = DB::table('financiacion_acreedor')
            ->where('acreedor_id', $acreedor->id)
            ->pluck('financiacion_id')
            ->toArray();
            
        // Buscar cuotas que vencen en este mes para las financiaciones del acreedor
        $cuotas = Cuota::whereIn('financiacion_id', $financiacionesIds)
            ->whereBetween('fecha_de_vencimiento', [$monthStart->format('Y-m-d'), $monthEnd->format('Y-m-d')])
            ->get();
            
        foreach ($cuotas as $cuota) {
            // Obtener el porcentaje que le corresponde a este acreedor para esta financiación
            $porcentaje = DB::table('financiacion_acreedor')
                ->where('acreedor_id', $acreedor->id)
                ->where('financiacion_id', $cuota->financiacion_id)
                ->value('porcentaje');
                
            if (!$porcentaje) continue;
            
            // Monto que le corresponde al acreedor según su porcentaje
            $montoAcreedorCuota = $cuota->monto * ($porcentaje / 100);
            
            // Evaluar según el estado de la cuota
            if ($cuota->estado == 'pagada') {
                $montoPagado += $montoAcreedorCuota;
                $montoTotal += $montoAcreedorCuota;
            } elseif ($cuota->estado == 'parcial') {
                // Obtener pagos reales para esta cuota
                $pagosRealizados = Pago::where('cuota_id', $cuota->id)
                    ->whereBetween('created_at', [$monthStart, $monthEnd])
                    ->sum('monto_usd');
                
                $montoPagadoAcreedor = ($pagosRealizados * $porcentaje) / 100;
                $montoPagado += $montoPagadoAcreedor;
                
                // El pendiente es la diferencia
                $montoPendiente += $montoAcreedorCuota - $montoPagadoAcreedor;
                $montoTotal += $montoAcreedorCuota;
            } else {
                // Pendiente
                $montoPendiente += $montoAcreedorCuota;
                $montoTotal += $montoAcreedorCuota;
            }
        }
        
        // Calcular liquidaciones para este mes
        $liquidaciones = Liquidacion::where('acreedor_id', $acreedor->id)
            ->whereBetween('created_at', [$monthStart, $monthEnd])
            ->sum('monto');
            
        return [
            'montoPagado' => round($montoPagado, 2),
            'montoPendiente' => round($montoPendiente, 2),
            'montoTotal' => round($montoTotal, 2),
            'liquidaciones' => round($liquidaciones, 2)
        ];
    }
} 