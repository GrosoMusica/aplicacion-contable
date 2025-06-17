<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Cuota;
use App\Models\Pago;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class InformeController extends Controller
{
    public function index(Request $request)
    {
        try {
            // Obtener mes y año de los parámetros de la solicitud o usar valores actuales
            $mesConsultado = $request->input('mes', Carbon::now()->month);
            $anoConsultado = $request->input('ano', Carbon::now()->year);
            
            // Crear fechas para filtrado
            $fechaConsulta = Carbon::createFromDate($anoConsultado, $mesConsultado, 1);
            $primerDiaMes = $fechaConsulta->copy()->startOfMonth()->format('Y-m-d');
            $ultimoDiaMes = $fechaConsulta->copy()->endOfMonth()->format('Y-m-d');
            
            Log::info("Consultando período", ["mes" => $mesConsultado, "año" => $anoConsultado, "desde" => $primerDiaMes, "hasta" => $ultimoDiaMes]);
            
            // CONSULTA 1: Todas las cuotas del mes
            $sqlCuotas = "SELECT 
                        c.id as cuota_id, 
                        c.monto, 
                        c.estado, 
                        c.fecha_de_vencimiento,
                        c.financiacion_id,
                        f.comprador_id,
                        comp.nombre as nombre_comprador,
                        comp.email,
                        comp.telefono,
                        COALESCE(comp.judicializado, 0) as judicializado
                    FROM cuotas c 
                    JOIN financiaciones f ON c.financiacion_id = f.id 
                    JOIN compradores comp ON f.comprador_id = comp.id 
                    WHERE c.fecha_de_vencimiento BETWEEN ? AND ?";
            
            $cuotas = DB::select($sqlCuotas, [$primerDiaMes, $ultimoDiaMes]);
            Log::info("Cuotas encontradas: " . count($cuotas));
            
            // CONSULTA 2: Todos los pagos relacionados con las cuotas del mes
            $sqlPagos = "SELECT p.* 
                         FROM pagos p 
                         JOIN cuotas c ON p.cuota_id = c.id 
                         WHERE c.fecha_de_vencimiento BETWEEN ? AND ?";
            
            $pagos = DB::select($sqlPagos, [$primerDiaMes, $ultimoDiaMes]);
            Log::info("Pagos encontrados: " . count($pagos));
            
            // Calcular totales
            $totalCuotas = count($cuotas);
            $totalMonto = 0;
            
            // Para Balance: solo cuotas completamente pagadas
            $cuotasPagadas = 0;
            
            foreach ($cuotas as $cuota) {
                $totalMonto += $cuota->monto;
                
                if ($cuota->estado == 'pagada') {
                    $cuotasPagadas++;
                }
            }
            
            // Para Recibido: suma de pagos
            $montoRecibido = 0;
            foreach ($pagos as $pago) {
                $montoRecibido += $pago->monto_usd;
            }
            
            // Para depuración y verificación
            $detalleCuotas = [];
            $detallePagos = [];
            
            // CALCULAMOS PENDIENTES: solo cuotas pendientes y el residuo de las parciales
            $montoPendiente = 0;
            foreach ($cuotas as $cuota) {
                $detalleCuotas[$cuota->cuota_id] = [
                    'id' => $cuota->cuota_id,
                    'monto' => $cuota->monto,
                    'estado' => $cuota->estado,
                    'comprador' => $cuota->nombre_comprador,
                    'pagado' => 0,
                    'pendiente' => 0
                ];
                
                // Sumar pagos por cuota
                $pagadoEnEstaCuota = 0;
                foreach ($pagos as $pago) {
                    if ($pago->cuota_id == $cuota->cuota_id) {
                        $pagadoEnEstaCuota += $pago->monto_usd;
                        $detallePagos[] = [
                            'pago_id' => $pago->id,
                            'cuota_id' => $pago->cuota_id,
                            'monto' => $pago->monto_usd
                        ];
                    }
                }
                
                $detalleCuotas[$cuota->cuota_id]['pagado'] = $pagadoEnEstaCuota;
                
                // Calcular montos pendientes según el estado
                if ($cuota->estado == 'pendiente') {
                    $pendienteEnEstaCuota = $cuota->monto;
                    $montoPendiente += $pendienteEnEstaCuota;
                    $detalleCuotas[$cuota->cuota_id]['pendiente'] = $pendienteEnEstaCuota;
                } 
                else if ($cuota->estado == 'parcial') {
                    $pendienteEnEstaCuota = max(0, $cuota->monto - $pagadoEnEstaCuota);
                    $montoPendiente += $pendienteEnEstaCuota;
                    $detalleCuotas[$cuota->cuota_id]['pendiente'] = $pendienteEnEstaCuota;
                }
            }
            
            Log::info("DESGLOSE DE CÁLCULOS PARA PENDIENTES", [
                'total_cuotas' => $totalCuotas,
                'monto_total' => $totalMonto,
                'monto_recibido' => $montoRecibido,
                'monto_pendiente_calculado' => $montoPendiente,
                'detalle_por_cuota' => $detalleCuotas,
                'detalle_pagos' => $detallePagos
            ]);
            
            // SQL para consultar las deudas directamente (para verificar)
            $sqlVerificacion = "SELECT
                c.id as cuota_id,
                c.monto,
                c.estado,
                COALESCE(SUM(p.monto_usd), 0) as pagado,
                c.monto - COALESCE(SUM(p.monto_usd), 0) as pendiente
            FROM
                cuotas c
                LEFT JOIN pagos p ON c.id = p.cuota_id
            WHERE
                c.fecha_de_vencimiento BETWEEN ? AND ?
                AND (c.estado = 'pendiente' OR c.estado = 'parcial')
            GROUP BY
                c.id, c.monto, c.estado";
            
            $verificacion = DB::select($sqlVerificacion, [$primerDiaMes, $ultimoDiaMes]);
            
            $totalPendienteSQL = 0;
            foreach ($verificacion as $v) {
                $totalPendienteSQL += max(0, $v->pendiente);
            }
            
            Log::info("VERIFICACIÓN SQL DIRECTA", [
                'pendiente_sql' => $totalPendienteSQL,
                'desglose' => $verificacion
            ]);
            
            // Usar el valor de SQL directo para mayor precisión
            $montoPendiente = $totalPendienteSQL;
            
            // Obtener deudores sin modificar la consulta original
            $sqlDeudores = "SELECT DISTINCT comp.id, comp.nombre, comp.email, comp.telefono, COALESCE(comp.judicializado, 0) as judicializado
                            FROM cuotas c 
                            JOIN financiaciones f ON c.financiacion_id = f.id 
                            JOIN compradores comp ON f.comprador_id = comp.id 
                            WHERE (c.estado = 'pendiente' OR c.estado = 'parcial') 
                                AND c.fecha_de_vencimiento BETWEEN ? AND ?";
            
            $deudores = DB::select($sqlDeudores, [$primerDiaMes, $ultimoDiaMes]);
            
            // Preparar diagnóstico con valor correcto
            $diagnostico = [
                'fecha_actual' => Carbon::now()->format('Y-m-d H:i:s'),
                'mes_consultado' => $mesConsultado,
                'ano_consultado' => $anoConsultado,
                'pasos' => [
                    0 => [
                        'paso' => '1. Cuotas en el mes consultado',
                        'sql' => $sqlCuotas,
                        'resultado' => $cuotas
                    ],
                    1 => [
                        'paso' => '2. JOIN con financiaciones y compradores',
                        'sql' => $sqlCuotas,
                        'resultado' => $cuotas
                    ],
                    2 => [
                        'paso' => '3. Pagos para las cuotas del mes',
                        'sql' => $sqlPagos,
                        'resultado' => $pagos
                    ]
                ],
                'totales' => [
                    'cantidad_cuotas' => $totalCuotas,
                    'monto' => $totalMonto,
                    'cuotas_pagadas' => $cuotasPagadas, // Solo cuotas completamente pagadas
                    'cobrado' => $montoRecibido,
                    'deuda' => $montoPendiente,
                    'cantidad_pagos' => count($pagos)
                ],
                'deudores' => $deudores
            ];
            
            return view('informes.informes', compact('diagnostico'));
            
        } catch (\Exception $e) {
            Log::error("Error en InformeController: " . $e->getMessage());
            Log::error($e->getTraceAsString());
            
            return view('informes.informes', [
                'error' => 'Error al generar el informe: ' . $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }
    
    private function ejecutarConsulta($sql, $params = [])
    {
        try {
            $resultados = DB::select($sql, $params);
            return $resultados;
        } catch (\Exception $e) {
            return [
                'error' => $e->getMessage()
            ];
        }
    }
} 