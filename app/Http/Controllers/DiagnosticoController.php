<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Cuota;
use App\Models\Pago;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DiagnosticoController extends Controller
{
    public function cobranzas()
    {
        $mesActual = Carbon::now()->month;
        $anoActual = Carbon::now()->year;
        
        $diagnostico = [
            'fecha_actual' => Carbon::now()->format('Y-m-d H:i:s'),
            'mes_consultado' => $mesActual,
            'ano_consultado' => $anoActual,
            'pasos' => []
        ];
        
        // PASO 1: Verificar cuotas en el mes actual
        $diagnostico['pasos'][] = [
            'paso' => '1. Cuotas en el mes actual',
            'consulta' => "SELECT * FROM cuotas WHERE MONTH(fecha_de_vencimiento) = $mesActual AND YEAR(fecha_de_vencimiento) = $anoActual",
            'resultado' => $this->ejecutarConsulta("SELECT * FROM cuotas WHERE MONTH(fecha_de_vencimiento) = ? AND YEAR(fecha_de_vencimiento) = ?", [$mesActual, $anoActual])
        ];
        
        // PASO 2: Verificar JOIN con financiaciones y compradores
        $diagnostico['pasos'][] = [
            'paso' => '2. JOIN con financiaciones y compradores',
            'consulta' => "SELECT c.id as cuota_id, c.monto, c.estado, c.financiacion_id, c.fecha_de_vencimiento, 
                          com.id as comprador_id, com.nombre, com.email
                          FROM cuotas c
                          JOIN financiaciones f ON c.financiacion_id = f.id
                          JOIN compradores com ON f.comprador_id = com.id
                          WHERE MONTH(c.fecha_de_vencimiento) = $mesActual
                          AND YEAR(c.fecha_de_vencimiento) = $anoActual",
            'resultado' => $this->ejecutarConsulta(
                "SELECT c.id as cuota_id, c.monto, c.estado, c.financiacion_id, c.fecha_de_vencimiento,
                        com.id as comprador_id, com.nombre, com.email
                 FROM cuotas c
                 JOIN financiaciones f ON c.financiacion_id = f.id
                 JOIN compradores com ON f.comprador_id = com.id
                 WHERE MONTH(c.fecha_de_vencimiento) = ? AND YEAR(c.fecha_de_vencimiento) = ?", 
                [$mesActual, $anoActual]
            )
        ];
        
        // Si hay cuotas, verificar los pagos
        $cuotasEncontradas = $diagnostico['pasos'][0]['resultado'];
        if (!empty($cuotasEncontradas)) {
            $idsCuotas = array_column($cuotasEncontradas, 'id');
            $idsCuotasStr = implode(',', $idsCuotas);
            
            // PASO 3: Verificar pagos para estas cuotas
            $diagnostico['pasos'][] = [
                'paso' => '3. Pagos para las cuotas del mes',
                'consulta' => "SELECT * FROM pagos WHERE cuota_id IN ($idsCuotasStr)",
                'resultado' => $this->ejecutarConsulta(
                    "SELECT * FROM pagos WHERE cuota_id IN (" . implode(',', array_fill(0, count($idsCuotas), '?')) . ")",
                    $idsCuotas
                )
            ];
            
            // PASO 4: Calcular totales
            $totalCuotas = 0;
            foreach ($cuotasEncontradas as $cuota) {
                $totalCuotas += $cuota->monto;
            }
            
            $diagnostico['totales'] = [
                'total_cuotas' => $totalCuotas,
                'cantidad_cuotas' => count($cuotasEncontradas)
            ];
            
            $pagosEncontrados = $diagnostico['pasos'][2]['resultado'] ?? [];
            if (!empty($pagosEncontrados)) {
                $totalPagado = 0;
                foreach ($pagosEncontrados as $pago) {
                    $totalPagado += $pago->monto_usd;
                }
                
                $diagnostico['totales']['total_pagado'] = $totalPagado;
                $diagnostico['totales']['saldo_pendiente'] = $totalCuotas - $totalPagado;
                $diagnostico['totales']['cantidad_pagos'] = count($pagosEncontrados);
            } else {
                $diagnostico['totales']['total_pagado'] = 0;
                $diagnostico['totales']['saldo_pendiente'] = $totalCuotas;
                $diagnostico['totales']['cantidad_pagos'] = 0;
            }
        }
        
        return view('diagnostico.cobranzas', ['diagnostico' => $diagnostico]);
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