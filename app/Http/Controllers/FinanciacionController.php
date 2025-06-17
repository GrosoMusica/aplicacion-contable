<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Comprador;
use App\Models\Cuota;
use App\Models\Financiacion;
use Illuminate\Support\Facades\DB;
use DateTime;

class FinanciacionController extends Controller
{
    public function morosos()
    {
        // Obtener IDs de compradores con al menos 2 cuotas pendientes
        $compradorIds = DB::table('compradores')
            ->join('financiaciones', 'compradores.id', '=', 'financiaciones.comprador_id')
            ->join('cuotas', 'financiaciones.id', '=', 'cuotas.financiacion_id')
            ->where('cuotas.estado', 'pendiente')
            ->groupBy('compradores.id')
            ->havingRaw('COUNT(cuotas.id) >= 2')
            ->pluck('compradores.id');

        // Obtener los datos completos de los compradores
        $compradores = Comprador::whereIn('id', $compradorIds)->get();

        // Obtener datos detallados para cada comprador
        $detallesCompradores = [];
        foreach ($compradores as $comprador) {
            // Buscar las financiaciones asociadas a este comprador
            $financiaciones = Financiacion::where('comprador_id', $comprador->id)->pluck('id');
            
            // Obtener todas las cuotas del comprador a través de sus financiaciones hasta la fecha actual
            $cuotas = Cuota::whereIn('financiacion_id', $financiaciones)
                        ->where('fecha_de_vencimiento', '<=', now())
                        ->orderBy('fecha_de_vencimiento')
                        ->get();
            
            $totalCuotas = $cuotas->count();
            $pendientes = $cuotas->where('estado', 'pendiente')->count();
            $parciales = $cuotas->where('estado', 'parcial')->count();
            $pagadas = $cuotas->where('estado', 'pagada')->count();
            $futuras = Cuota::whereIn('financiacion_id', $financiaciones)
                        ->where('fecha_de_vencimiento', '>', now())
                        ->count();
            
            // Solo incluir si tiene al menos 2 cuotas pendientes
            if ($pendientes >= 2) {
                $detallesCompradores[] = [
                    'comprador' => $comprador,
                    'estadisticas' => [
                        'total' => $totalCuotas + $futuras,
                        'pendientes' => $pendientes,
                        'parciales' => $parciales,
                        'pagadas' => $pagadas,
                        'futuras' => $futuras
                    ]
                ];
            }
        }

        return view('morosos', compact('detallesCompradores'));
    }

    /**
     * Muestra la página de compradores próximos a finalizar sus pagos.
     * Incluye aquellos con 3 o menos cuotas futuras.
     *
     * @return \Illuminate\Http\Response
     */
    public function proximosAFinalizar()
    {
        // Obtener IDs de compradores con 3 o menos cuotas futuras
        $compradorIds = DB::table('compradores')
            ->join('financiaciones', 'compradores.id', '=', 'financiaciones.comprador_id')
            ->join('cuotas', 'financiaciones.id', '=', 'cuotas.financiacion_id')
            ->where('cuotas.fecha_de_vencimiento', '>', now())
            ->groupBy('compradores.id', 'financiaciones.monto_a_financiar')
            ->havingRaw('COUNT(cuotas.id) <= 3')
            ->select('compradores.id', 'financiaciones.monto_a_financiar')
            ->pluck('compradores.id');

        // Obtener datos completos de los compradores
        $compradores = Comprador::whereIn('id', $compradorIds)->get();

        // Obtener compradores que ya finalizaron (sin cuotas futuras)
        $finalizadosIds = DB::table('compradores')
            ->join('financiaciones', 'compradores.id', '=', 'financiaciones.comprador_id')
            ->leftJoin('cuotas', function($join) {
                $join->on('financiaciones.id', '=', 'cuotas.financiacion_id')
                    ->where('cuotas.fecha_de_vencimiento', '>', now());
            })
            ->whereNull('cuotas.id')
            ->where('financiaciones.id', '>', 0) // Asegura que tiene financiación
            ->groupBy('compradores.id')
            ->pluck('compradores.id');

        $finalizados = Comprador::whereIn('id', $finalizadosIds)->get();

        // Obtener datos detallados para cada comprador
        $detallesCompradores = [];
        $compradoresAll = $compradores->merge($finalizados)->unique('id');

        foreach ($compradoresAll as $comprador) {
            // Obtener las financiaciones de este comprador
            $financiaciones = Financiacion::where('comprador_id', $comprador->id)->get();
            
            foreach ($financiaciones as $financiacion) {
                // Obtener todas las cuotas
                $todasCuotas = Cuota::where('financiacion_id', $financiacion->id)
                            ->orderBy('fecha_de_vencimiento')
                            ->get();
                
                // Separar cuotas por estado
                $cuotasPasadas = $todasCuotas->where('fecha_de_vencimiento', '<=', now());
                $cuotasFuturas = $todasCuotas->where('fecha_de_vencimiento', '>', now());
                
                $totalCuotas = $todasCuotas->count();
                $pendientes = $cuotasPasadas->where('estado', 'pendiente')->count();
                $parciales = $cuotasPasadas->where('estado', 'parcial')->count();
                $pagadas = $cuotasPasadas->where('estado', 'pagada')->count();
                $futuras = $cuotasFuturas->count();
                
                // Comprador finalizado o próximo a finalizar
                if ($futuras <= 3) {
                    $detallesCompradores[] = [
                        'comprador' => $comprador,
                        'financiacion' => $financiacion,
                        'estadisticas' => [
                            'total' => $totalCuotas,
                            'pendientes' => $pendientes,
                            'parciales' => $parciales,
                            'pagadas' => $pagadas,
                            'futuras' => $futuras
                        ],
                        'finalizado' => ($futuras == 0 && $pendientes == 0 && $parciales == 0)
                    ];
                }
            }
        }

        return view('proximos_a_finalizar', compact('detallesCompradores'));
    }
} 