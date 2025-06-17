<?php

namespace App\Http\Controllers;

use App\Models\Comprador;
use App\Models\Lote;
use App\Models\Cuota;
use App\Models\Financiacion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OperationManagementController extends Controller
{
    public function getCompradores()
    {
        try {
            $compradores = DB::table('compradores')
                ->select('id', 'nombre', 'dni', 'lote_comprado_id', 'financiacion_id')
                ->get();

            return response()->json($compradores);
        } catch (\Exception $e) {
            Log::error('Error al obtener compradores: ' . $e->getMessage());
            return response()->json(['error' => 'Error al obtener los compradores'], 500);
        }
    }

    public function deleteOperation(Request $request)
    {
        try {
            DB::beginTransaction();

            // Desactivar verificación de claves foráneas
            DB::statement('SET FOREIGN_KEY_CHECKS=0');

            $comprador = Comprador::findOrFail($request->id);
            $lote_id = $comprador->lote_comprado_id;
            $financiacion_id = $comprador->financiacion_id;

            // Registrar los IDs antes de la eliminación
            Log::info('Iniciando eliminación en cascada para:', [
                'comprador_id' => $request->id,
                'lote_id' => $lote_id,
                'financiacion_id' => $financiacion_id
            ]);

            // 1. Eliminar cuotas relacionadas con la financiación
            if ($financiacion_id) {
                $cuotasEliminadas = Cuota::where('financiacion_id', $financiacion_id)->delete();
                Log::info("Cuotas eliminadas: {$cuotasEliminadas} registros");
            }

            // 2. Eliminar la financiación
            if ($financiacion_id) {
                Financiacion::where('id', $financiacion_id)->delete();
                Log::info('Financiación eliminada: ' . $financiacion_id);
            }

            // 3. Eliminar el lote
            if ($lote_id) {
                Lote::where('id', $lote_id)->delete();
                Log::info('Lote eliminado: ' . $lote_id);
            }

            // 4. Eliminar el comprador
            $comprador->delete();
            Log::info('Comprador eliminado: ' . $request->id);

            // Reactivar verificación de claves foráneas
            DB::statement('SET FOREIGN_KEY_CHECKS=1');

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Operación eliminada correctamente',
                'deleted_ids' => [
                    'comprador' => $request->id,
                    'lote' => $lote_id,
                    'financiacion' => $financiacion_id
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            
            // Asegurarse de reactivar las claves foráneas incluso si hay error
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
            
            Log::error('Error al eliminar operación: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());
            
            return response()->json([
                'error' => true,
                'message' => 'Error al eliminar la operación: ' . $e->getMessage()
            ], 500);
        }
    }
} 