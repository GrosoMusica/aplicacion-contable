<?php

namespace App\Http\Controllers;

use App\Models\Acreedor;
use App\Models\Pago;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class PagosAcreedorController extends Controller
{
    /**
     * Mostrar el balance de pagos realizados a acreedores
     */
    public function index(Request $request)
    {
        // Obtener el mes seleccionado o usar el actual
        $mesSeleccionado = $request->get('mes', now()->format('Y-m'));
        $mesActual = now()->format('Y-m');
        
        // Obtener todos los acreedores
        $acreedores = Acreedor::all();
        
        // Preparar array para almacenar datos de pagos por mes
        $datosPagosPorMes = [];
        
        // Generar los últimos 6 meses para la visualización
        $months = [];
        $currentDate = Carbon::now();
        for ($i = 0; $i < 6; $i++) {
            $date = clone $currentDate;
            $date->subMonths($i);
            $months[] = $date->format('Y-m');
        }
        // Revertir para mostrar de más antiguo a más reciente
        $months = array_reverse($months);
        
        // Para cada acreedor, obtener sus pagos en los últimos 6 meses
        foreach ($acreedores as $acreedor) {
            $datosPagosPorMes[$acreedor->id] = [];
            
            foreach ($months as $month) {
                // Para el ADMIN (id=1), obtener todos los pagos excepto los asignados a otros acreedores
                if ($acreedor->id == 1) {
                    $pagos = DB::table('pagos as p')
                        ->leftJoin('cuotas as c', 'p.cuota_id', '=', 'c.id')
                        ->where(function ($query) {
                            $query->where('p.acreedor_id', 1)
                                  ->orWhereNull('p.acreedor_id');
                        })
                        ->whereRaw("DATE_FORMAT(p.fecha_de_pago, '%Y-%m') = ?", [$month])
                        ->select(
                            DB::raw('SUM(p.monto_usd) as total_pagado'),
                            DB::raw('COUNT(p.id) as cantidad_pagos')
                        )
                        ->first();
                } else {
                    // Para los demás acreedores, obtener solo sus pagos directos
                    $pagos = DB::table('pagos as p')
                        ->leftJoin('cuotas as c', 'p.cuota_id', '=', 'c.id')
                        ->where('p.acreedor_id', $acreedor->id)
                        ->whereRaw("DATE_FORMAT(p.fecha_de_pago, '%Y-%m') = ?", [$month])
                        ->select(
                            DB::raw('SUM(p.monto_usd) as total_pagado'),
                            DB::raw('COUNT(p.id) as cantidad_pagos')
                        )
                        ->first();
                }
                
                // Calcular el porcentaje que le corresponde según las financiaciones
                $pagosPorFinanciaciones = $this->calcularPagosPorFinanciaciones($acreedor->id, $month);
                
                $datosPagosPorMes[$acreedor->id][$month] = [
                    'pagos_directos' => $pagos->total_pagado ?? 0,
                    'cantidad_pagos' => $pagos->cantidad_pagos ?? 0,
                    'pagos_por_financiaciones' => $pagosPorFinanciaciones,
                    'total' => ($pagos->total_pagado ?? 0) + $pagosPorFinanciaciones
                ];
            }
        }
        
        // Preparar meses para navegación
        $fechaBase = Carbon::createFromFormat('Y-m', $mesSeleccionado);
        $mesesNavegacion = [];
        
        for ($i = -6; $i <= 6; $i++) {
            $fecha = $fechaBase->copy()->addMonths($i);
            $mesesNavegacion[] = [
                'valor' => $fecha->format('Y-m'),
                'etiqueta' => ucfirst($fecha->locale('es')->isoFormat('MMMM [de] YYYY')),
                'es_actual' => $fecha->format('Y-m') === $mesActual
            ];
        }
        
        return view('acreedores.pagos', compact(
            'acreedores', 
            'datosPagosPorMes', 
            'months', 
            'mesSeleccionado', 
            'mesActual', 
            'mesesNavegacion'
        ));
    }
    
    /**
     * Calcular los pagos correspondientes a un acreedor según sus porcentajes en financiaciones
     */
    private function calcularPagosPorFinanciaciones($acreedorId, $month)
    {
        $total = 0;
        
        // Obtener todas las financiaciones donde participa este acreedor
        $financiaciones = DB::table('financiacion_acreedor as fa')
            ->where('fa.acreedor_id', $acreedorId)
            ->select('fa.financiacion_id', 'fa.porcentaje')
            ->get();
        
        foreach ($financiaciones as $financiacion) {
            // Obtener las cuotas de esta financiación en el mes especificado
            $cuotas = DB::table('cuotas')
                ->where('financiacion_id', $financiacion->financiacion_id)
                ->whereRaw("DATE_FORMAT(fecha_de_vencimiento, '%Y-%m') = ?", [$month])
                ->pluck('id');
            
            if (count($cuotas) > 0) {
                // Obtener los pagos para estas cuotas
                $pagosDeEstaCuota = DB::table('pagos')
                    ->whereIn('cuota_id', $cuotas)
                    ->whereNull('acreedor_id') // Solo pagos no asignados a acreedores específicos
                    ->sum('monto_usd');
                
                // Calcular el monto que le corresponde al acreedor según su porcentaje
                $total += ($pagosDeEstaCuota * $financiacion->porcentaje) / 100;
            }
        }
        
        return $total;
    }
} 