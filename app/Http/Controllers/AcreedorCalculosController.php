<?php

namespace App\Http\Controllers;

use App\Models\Cuota;
use App\Models\Acreedor;
use App\Models\Pago;
use App\Models\Liquidacion;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class AcreedorCalculosController extends Controller
{
    public function calcularTotalAcreedor($acreedorId)
    {
        // Obtener todas las cuotas hasta la fecha actual
        $cuotas = Cuota::where('fecha_de_vencimiento', '<=', Carbon::now())
                       ->whereHas('financiacion', function($query) use ($acreedorId) {
                           $query->whereHas('acreedores', function($q) use ($acreedorId) {
                               $q->where('acreedor_id', $acreedorId);
                           });
                       })
                       ->get();

        $totalAcreedor = 0;

        foreach ($cuotas as $cuota) {
            // Buscar el porcentaje que le corresponde al acreedor en esta financiación
            $porcentaje = DB::table('financiacion_acreedor')
                ->where('financiacion_id', $cuota->financiacion_id)
                ->where('acreedor_id', $acreedorId)
                ->value('porcentaje') ?? 0;

            // Calcular el monto correspondiente a este acreedor
            $montoAcreedor = ($cuota->monto * $porcentaje) / 100;
            $totalAcreedor += $montoAcreedor;
        }

        return $totalAcreedor;
    }

    public function obtenerTotalCobrado($acreedorId)
    {
        // Obtener suma de pagos del acreedor
        $totalPagos = DB::table('pagos')
            ->where('acreedor_id', $acreedorId)
            ->sum('monto_usd');  // Asumiendo que la columna se llama monto_usd en pagos

        // Obtener suma de liquidaciones del acreedor
        $totalLiquidaciones = DB::table('liquidaciones')
            ->where('acreedor_id', $acreedorId)
            ->sum('monto');

        // Retornar la suma total
        return $totalPagos + $totalLiquidaciones;
    }

    public function obtenerResumenAcreedor($acreedorId)
    {
        $totalACobrar = $this->calcularTotalAcreedor($acreedorId);
        $totalCobrado = $this->obtenerTotalCobrado($acreedorId);
        $saldo = $totalACobrar - $totalCobrado;

        // Obtener pagos del mes actual
        $pagosRecibidos = Pago::where('acreedor_id', $acreedorId)
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->get();

        // Obtener liquidaciones del mes actual
        $liquidaciones = Liquidacion::where('acreedor_id', $acreedorId)
            ->whereMonth('fecha', now()->month)
            ->whereYear('fecha', now()->year)
            ->get();

        return [
            'total_a_cobrar' => $totalACobrar,
            'total_cobrado' => $totalCobrado,
            'saldo' => $saldo,
            'pagos_recibidos' => $pagosRecibidos,
            'liquidaciones' => $liquidaciones
        ];
    }
} 