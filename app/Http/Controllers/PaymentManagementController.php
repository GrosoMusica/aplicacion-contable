<?php

namespace App\Http\Controllers;

use App\Models\Pago;
use App\Models\Cuota;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class PaymentManagementController extends Controller
{
    public function getLastPayments()
    {
        $payments = DB::table('pagos as p')
            ->join('cuotas as c', 'p.cuota_id', '=', 'c.id')
            ->join('financiaciones as f', 'c.financiacion_id', '=', 'f.id')
            ->join('compradores as comp', 'f.comprador_id', '=', 'comp.id')
            ->select(
                'p.id',
                'p.cuota_id',
                'p.acreedor_id',
                'p.fecha_de_pago',
                'p.monto_usd',
                'comp.nombre as nombre_comprador'
            )
            ->orderBy('p.fecha_de_pago', 'desc')
            ->take(5)
            ->get();

        return response()->json($payments);
    }

    public function updatePayment(Request $request)
    {
        try {
            Log::info('Actualizando pago', $request->all());

            $request->validate([
                'id' => 'required|exists:pagos,id',
                'monto_usd' => 'required|numeric|min:0'
            ]);

            $payment = Pago::findOrFail($request->id);
            $payment->monto_usd = $request->monto_usd;
            $payment->save();

            Log::info('Pago actualizado correctamente', ['id' => $payment->id]);

            return response()->json([
                'success' => true,
                'payment' => [
                    'id' => $payment->id,
                    'cuota_id' => $payment->cuota_id,
                    'acreedor_id' => $payment->acreedor_id,
                    'fecha_de_pago' => $payment->fecha_de_pago ? Carbon::parse($payment->fecha_de_pago)->format('Y-m-d') : null,
                    'monto_usd' => number_format($payment->monto_usd, 2, '.', '')
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Error al actualizar pago: ' . $e->getMessage());
            return response()->json(['error' => 'Error al actualizar el pago'], 500);
        }
    }

    public function deletePayment(Request $request)
    {
        try {
            DB::beginTransaction();

            $payment = Pago::findOrFail($request->id);
            
            // Buscar y actualizar la cuota asociada
            if ($payment->cuota_id) {
                $cuota = Cuota::find($payment->cuota_id);
                if ($cuota) {
                    $cuota->estado = 'pendiente';
                    $cuota->save();
                    Log::info('Cuota actualizada a pendiente', ['cuota_id' => $cuota->id]);
                }
            }

            // Eliminar el pago
            $payment->delete();
            Log::info('Pago eliminado correctamente', ['id' => $request->id]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Pago eliminado y cuota actualizada correctamente'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al eliminar pago: ' . $e->getMessage());
            return response()->json([
                'error' => 'Error al eliminar el pago: ' . $e->getMessage()
            ], 500);
        }
    }
} 