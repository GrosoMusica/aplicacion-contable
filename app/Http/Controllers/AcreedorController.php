<?php

namespace App\Http\Controllers;

use App\Models\Acreedor;
use App\Models\Comprador;
use App\Models\Financiacion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use App\Models\Cuota;

class AcreedorController extends Controller
{
    /**
     * Mostrar vista para crear un nuevo acreedor
     */
    public function create()
    {
        return view('acreedores.create');
    }

    /**
     * Crear un nuevo acreedor sin asociarlo a ninguna financiación
     */
    public function storeSimple(Request $request)
    {
        // Validar datos básicos del acreedor
        $validated = $request->validate([
            'nombre' => 'required|string|max:255',
            'descripcion' => 'nullable|string',
        ]);

        try {
            // Crear el acreedor
            $acreedor = Acreedor::create([
                'nombre' => $request->nombre,
                'descripcion' => $request->descripcion,
            ]);
            
            return redirect()->route('gestion.acreedores.index')
                ->with('success', "Se ha creado el acreedor {$acreedor->nombre} correctamente.");
                
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Error al crear el acreedor: ' . $e->getMessage());
        }
    }

    /**
     * Almacenar un nuevo acreedor o asociar uno existente a una financiación
     */
    public function store(Request $request)
    {
        // Validación básica
        $request->validate([
            'nombre' => 'required|string|max:255',
            'financiacion_id' => 'required',
            'porcentaje' => 'required|numeric|min:1',
        ]);

        try {
            // Obtener el acreedor por nombre
            $acreedor = Acreedor::where('nombre', $request->nombre)->first();
            
            if ($acreedor) {
                // Obtener el porcentaje actual del admin
                $adminRelacion = DB::table('financiacion_acreedor')
                    ->where('financiacion_id', $request->financiacion_id)
                    ->where('acreedor_id', 1)
                    ->first();
                
                // Crear la relación entre financiación y acreedor
                DB::table('financiacion_acreedor')->insert([
                    'financiacion_id' => $request->financiacion_id,
                    'acreedor_id' => $acreedor->id,
                    'porcentaje' => $request->porcentaje
                ]);
                
                // Actualizar el porcentaje del admin
                $nuevoPorcentajeAdmin = $adminRelacion->porcentaje - $request->porcentaje;
                DB::table('financiacion_acreedor')
                    ->where('financiacion_id', $request->financiacion_id)
                    ->where('acreedor_id', 1)
                    ->update([
                        'porcentaje' => $nuevoPorcentajeAdmin
                    ]);
                
                return redirect()->back()->with('success', 'Acreedor asignado correctamente');
            } else {
                return redirect()->back()->with('error', 'Acreedor no encontrado');
            }
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Error: ' . $e->getMessage());
        }
    }

    /**
     * Exportar datos de acreedores a PDF
     *
     * @param string|null $tipo Tipo de exportación: 'resumen', 'detallado', 'mensual'
     * @return \Illuminate\Http\Response
     */
    public function exportPDF($tipo = 'resumen')
    {
        // Obtener acreedores con sus relaciones
        $acreedores = Acreedor::with(['financiaciones', 'financiaciones.cuota'])->get();
        
        // Generar meses para la vista panorámica
        $currentDate = Carbon::now();
        $months = [];
        for ($i = 0; $i < 6; $i++) {
            $date = clone $currentDate;
            $date->subMonths($i);
            $months[] = $date;
        }
        // Revertir para mostrar de más antiguo a más reciente
        $months = array_reverse($months);
        
        // Calcular datos reales (reemplaza la lógica de simulación)
        $monthlyData = [];
        $totalesData = [];
        
        foreach ($acreedores as $acreedor) {
            // Inicializar datos para este acreedor
            $monthlyData[$acreedor->id] = [];
            $totalesPagados = 0;
            $totalesPendientes = 0;
            
            // Calcular datos para cada mes
            foreach ($months as $month) {
                $monthStart = clone $month;
                $monthStart->startOfMonth();
                $monthEnd = clone $month;
                $monthEnd->endOfMonth();
                
                // Buscar financiaciones de este mes
                $pagosDelMes = 0;
                $estadoDelMes = 'pendiente';
                
                foreach ($acreedor->financiaciones as $financiacion) {
                    if (isset($financiacion->cuota) && 
                        $financiacion->cuota->fecha_vencimiento >= $monthStart && 
                        $financiacion->cuota->fecha_vencimiento <= $monthEnd) {
                        
                        $montoPorcentaje = $financiacion->cuota->monto * ($financiacion->porcentaje / 100);
                        
                        if ($financiacion->estado == 'pagada' || $financiacion->estado == 'pagado') {
                            $pagosDelMes += $montoPorcentaje;
                            $totalesPagados += $montoPorcentaje;
                            $estadoDelMes = 'pagado';
                        } elseif ($financiacion->estado == 'parcial') {
                            $pagosDelMes += $financiacion->monto_pagado_acreedor;
                            $totalesPagados += $financiacion->monto_pagado_acreedor;
                            $totalesPendientes += $financiacion->monto_pendiente_acreedor;
                            if ($estadoDelMes != 'pagado') {
                                $estadoDelMes = 'parcial';
                            }
                        } else {
                            $totalesPendientes += $montoPorcentaje;
                        }
                    }
                }
                
                $monthlyData[$acreedor->id][$month->format('Y-m')] = [
                    'monto' => $pagosDelMes,
                    'estado' => $estadoDelMes
                ];
            }
            
            $totalesData[$acreedor->id] = [
                'pagado' => $totalesPagados,
                'pendiente' => $totalesPendientes,
                'total' => $totalesPagados + $totalesPendientes,
                'saldoAFavor' => false // Implementa tu lógica para saldo a favor
            ];
        }
        
        // Determinar qué vista usar según el tipo
        $view = 'pdf.acreedores';
        switch ($tipo) {
            case 'detallado':
                $view = 'pdf.acreedores-detallado';
                break;
            case 'mensual':
                $view = 'pdf.acreedores-mensual';
                break;
        }
        
        // Generar PDF con la vista
        $pdf = Pdf::loadView($view, [
            'acreedores' => $acreedores,
            'months' => $months,
            'monthlyData' => $monthlyData,
            'totalesData' => $totalesData,
            'fechaGeneracion' => now()->format('d/m/Y H:i')
        ]);
        
        // Opcional: personalizar el PDF
        $pdf->setPaper('a4', 'landscape');
        
        // Descargar PDF
        return $pdf->download('acreedores-' . $tipo . '-' . now()->format('dmY') . '.pdf');
    }

    /**
     * Exportar la distribución de ingresos de un acreedor específico
     *
     * @param int $acreedorId ID del acreedor
     * @param string|null $mes Mes en formato Y-m (opcional)
     * @return \Illuminate\Http\Response
     */
    public function exportDistribucion($acreedorId, $mes = null)
    {
        // Si no se proporciona un mes, usar el mes actual
        if (!$mes) {
            $mes = now()->format('Y-m');
        }
        
        // Crear objetos de fecha para mes actual, anterior y siguiente
        $fechaActual = \Carbon\Carbon::createFromFormat('Y-m', $mes);
        $mesAnterior = $fechaActual->copy()->subMonth()->format('Y-m');
        $mesSiguiente = $fechaActual->copy()->addMonth()->format('Y-m');
        
        // Obtener el acreedor
        $acreedor = Acreedor::findOrFail($acreedorId);
        
        // Obtener el mes seleccionado (ya no usamos now() sino el mes proporcionado)
        $mesSeleccionado = $mes;
        
        // Preparar las financiaciones igual que en NewAcreedorController
        // Obtener financiaciones del acreedor con los datos del comprador
        $financiaciones = DB::table('financiacion_acreedor as fa')
            ->join('financiaciones as f', 'fa.financiacion_id', '=', 'f.id')
            ->join('compradores as c', 'f.comprador_id', '=', 'c.id')
            ->where('fa.acreedor_id', $acreedor->id)
            ->select('fa.financiacion_id', 'fa.porcentaje', 'c.nombre as nombre_comprador')
            ->get();
        
        $acreedor->financiaciones = $financiaciones;
        
        // Para cada financiación, obtener la cuota del mes seleccionado (igual que en NewAcreedorController)
        foreach ($financiaciones as $financiacion) {
            $cuota = Cuota::where('financiacion_id', $financiacion->financiacion_id)
                ->where('fecha_de_vencimiento', 'like', $mesSeleccionado . '%')
                ->first();
                
            $financiacion->cuota = $cuota;
            
            if ($cuota) {
                // Usar directamente el estado de la cuota
                $financiacion->estado = $cuota->estado;
                
                // Obtener pagos para calcular montos
                $pagos = DB::table('pagos')
                    ->where('cuota_id', $cuota->id)
                    ->get();
                
                $montoPagado = $pagos->sum('monto_usd');
                $financiacion->monto_pagado = $montoPagado;
                
                // Calcular montos según porcentaje del acreedor
                $financiacion->monto_total = $cuota->monto;
                $financiacion->monto_porcentaje = ($cuota->monto * $financiacion->porcentaje) / 100;
                $financiacion->monto_pagado_acreedor = ($montoPagado * $financiacion->porcentaje) / 100;
                $financiacion->monto_pendiente_acreedor = $financiacion->monto_porcentaje - $financiacion->monto_pagado_acreedor;
            } else {
                $financiacion->estado = 'sin_cuota';
                $financiacion->monto_total = 0;
                $financiacion->monto_porcentaje = 0;
                $financiacion->monto_pagado = 0;
                $financiacion->monto_pagado_acreedor = 0;
                $financiacion->monto_pendiente_acreedor = 0;
            }
        }
        
        // Filtrar solo financiaciones con cuotas
        $financiacionesActivas = $financiaciones->filter(function($item) {
            return $item->estado != 'sin_cuota';
        })->values();
        
        // Calcular los totales para el mes
        $montoTotalMes = 0;
        $montoPagadoTotal = 0;
        $montoPendienteTotal = 0;
        
        foreach ($financiacionesActivas as $item) {
            if ($item->estado != 'sin_cuota') {
                // Calcular montos según la lógica de la vista
                if ($item->estado == 'pagada' || $item->estado == 'pagado') {
                    $montoPagadoTotal += $item->monto_porcentaje;
                    $montoTotalMes += $item->monto_total;
                } elseif ($item->estado == 'parcial') {
                    $montoPagadoTotal += $item->monto_pagado_acreedor;
                    $montoPendienteTotal += $item->monto_pendiente_acreedor;
                    $montoTotalMes += $item->monto_total;
                } else {
                    // Pendiente
                    $montoPendienteTotal += $item->monto_porcentaje;
                    $montoTotalMes += $item->monto_total;
                }
            }
        }
        
        // Generar el PDF
        try {
            // Formatear el mes actual para mostrarlo en el PDF
            $mesActualFormateado = $fechaActual->locale('es')->format('F Y');
            
            $pdf = PDF::loadView('pdf.distribucion-ingresos', [
                'acreedor' => $acreedor,
                'financiaciones' => $financiacionesActivas,
                'montoTotalMes' => $montoPagadoTotal,
                'montoPagadoTotal' => $montoPagadoTotal,
                'montoPendienteTotal' => $montoPendienteTotal,
                'fechaGeneracion' => now()->format('d/m/Y'),
                'mesActual' => $mesActualFormateado,
                'mesAnteriorUrl' => route('acreedores.export-distribucion', ['acreedor' => $acreedorId, 'mes' => $mesAnterior]),
                'mesSiguienteUrl' => route('acreedores.export-distribucion', ['acreedor' => $acreedorId, 'mes' => $mesSiguiente])
            ]);
            
            $pdf->setPaper('a4', 'portrait');
            
            // Formato del nombre: balance-año-mes-nombreacreedor.pdf
            $nombreArchivo = 'balance-' . $mesSeleccionado . '-' . str_replace(' ', '_', $acreedor->nombre) . '.pdf';
            
            return $pdf->download($nombreArchivo);
        } catch (\Exception $e) {
            \Log::error('Error generando PDF: ' . $e->getMessage());
            return back()->with('error', 'Error al generar el PDF: ' . $e->getMessage());
        }
    }
} 