<?php

namespace App\Http\Controllers;

use App\Models\Comprador;
use App\Models\Cuota;
use App\Models\Pago;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;
use App\Models\Lote;
use Illuminate\Support\Facades\Log;
use App\Models\Acreedor;

class PagoController extends Controller
{
    public function index(Request $request)
    {
        // Obtener todos los compradores para el selector, ordenados por nombre
        $compradores = Comprador::orderBy('nombre')->get();
        
        // Comprador seleccionado (si existe)
        $compradorSeleccionado = null;
        $cuotas = collect();
        
        if ($request->has('comprador_id') && $request->comprador_id) {
            $compradorSeleccionado = Comprador::with(['lote', 'financiacion', 'financiacion.cuotas'])
                ->findOrFail($request->comprador_id);
            $cuotas = $compradorSeleccionado->financiacion->cuotas;
        } elseif ($request->has('dni') && $request->dni) {
            // Búsqueda por DNI
            $compradorSeleccionado = Comprador::with(['lote', 'financiacion', 'financiacion.cuotas'])
                ->where('dni', 'like', '%' . $request->dni . '%')
                ->first();
            
            if ($compradorSeleccionado) {
                $cuotas = $compradorSeleccionado->financiacion->cuotas;
            }
        } elseif ($request->has('email') && $request->email) {
            // Búsqueda por Email
            $compradorSeleccionado = Comprador::with(['lote', 'financiacion', 'financiacion.cuotas'])
                ->where('email', 'like', '%' . $request->email . '%')
                ->first();
            
            if ($compradorSeleccionado) {
                $cuotas = $compradorSeleccionado->financiacion->cuotas;
            }
        } elseif ($request->has('lote') && $request->lote) {
            // Búsqueda por Lote
            $lote = Lote::where('lote', 'like', '%' . $request->lote . '%')->first();
            
            if ($lote) {
                $compradorSeleccionado = Comprador::with(['lote', 'financiacion', 'financiacion.cuotas'])
                    ->where('lote_id', $lote->id)
                    ->first();
                
                if ($compradorSeleccionado) {
                    $cuotas = $compradorSeleccionado->financiacion->cuotas;
                }
            }
        }
        
        return view('pagos.index', compact('compradores', 'compradorSeleccionado', 'cuotas'));
    }
    
    /**
     * Registra un nuevo pago para una cuota específica
     */
    public function registrarPago(Request $request)
    {
        try {
            // Log para depuración
            Log::info('Inicio de registrarPago', ['request' => $request->all()]);
            
            // Validar solo lo esencial
            $request->validate([
                'cuota_id' => 'required|integer',
                'fecha_de_pago' => 'required|date',
                'monto_pagado' => 'required|numeric',
                'archivo_comprobante' => 'nullable|file|mimes:jpg,png,pdf|max:2048',
                'tipo_cambio' => 'required_if:pago_divisa,1|nullable|numeric|min:0.01',
            ]);
            
            // Obtener la cuota
            $cuota = Cuota::find($request->cuota_id);
            if (!$cuota) {
                Log::error('Cuota no encontrada', ['cuota_id' => $request->cuota_id]);
                return redirect()->back()->with('error', 'No se encontró la cuota especificada.');
            }
            
            $rutaComprobante = null;

            if ($request->hasFile('archivo_comprobante')) {
                // Obtener el ID del comprador, su nombre, y el número de cuota asociado a la cuota
                $comprador = $cuota->financiacion->comprador;
                $compradorId = $comprador->id;
                $compradorNombre = preg_replace('/[^A-Za-z0-9\-]/', '', str_replace(' ', '-', $comprador->nombre)); // Limpiar el nombre
                $numeroDeCuota = $cuota->numero_de_cuota;
                $fechaDePago = $request->fecha_de_pago;

                // Definir el nombre del archivo
                $nombreArchivo = "CUOTA-{$numeroDeCuota}-{$fechaDePago}.png";

                // Definir la ruta de almacenamiento
                $rutaComprobante = $request->file('archivo_comprobante')->storeAs(
                    "COMPROBANTES/{$compradorId}-{$compradorNombre}",
                    $nombreArchivo,
                    'public'
                );
            }
            
            // Crear pago con datos mínimos
            $pago = new Pago();
            $pago->cuota_id = $request->cuota_id;
            $pago->fecha_de_pago = $request->fecha_de_pago;
            $pago->monto_pagado = $request->monto_pagado;
            $pago->pago_divisa = $request->has('pago_divisa') ? 1 : 0;
            $pago->monto_usd = $request->monto_usd;
            $pago->tipo_cambio = $request->has('pago_divisa') ? $request->tipo_cambio : null;
            $pago->acreedor_id = $request->acreedor_id ?? 1;
            $pago->sin_comprobante = $request->has('sin_comprobante') ? 1 : 0;
            $pago->comprobante = $rutaComprobante;
            
            // Guardar el pago
            $pago->save();

            // Después de guardar el pago, incrementar el saldo del acreedor
            $acreedor = Acreedor::find($pago->acreedor_id);
            if ($acreedor) {
                $acreedor->incrementarSaldo($pago->monto_usd);
            }

            // Actualizar el estado de la cuota actual
            $totalPagadoCuotaActual = Pago::where('cuota_id', $cuota->id)->sum('monto_usd');
            $excedente = $totalPagadoCuotaActual - $cuota->monto;

            // Si hay excedente, lo aplicamos a otras cuotas pendientes
            if ($excedente > 0) {
                // Actualizar estado de la cuota actual
                $cuota->estado = 'pagada';
                $cuota->save();
                
                // Buscar TODAS las cuotas pendientes o con pago parcial de la misma financiación
                // Ordenadas por fecha de vencimiento (primeras a últimas)
                $cuotasPendientes = Cuota::where('financiacion_id', $cuota->financiacion_id)
                    ->whereIn('estado', ['pendiente', 'parcial'])
                    ->where('id', '!=', $cuota->id)
                    ->orderBy('fecha_de_vencimiento', 'asc')
                    ->get();
                
                // Aplicar excedente a las cuotas pendientes, empezando desde la más antigua
                foreach ($cuotasPendientes as $cuotaPendiente) {
                    if ($excedente <= 0) break;
                    
                    // Calcular cuánto falta para completar la cuota
                    $pagadoEnCuota = Pago::where('cuota_id', $cuotaPendiente->id)->sum('monto_usd');
                    $pendienteEnCuota = $cuotaPendiente->monto - $pagadoEnCuota;
                    
                    // Ver cuánto podemos aplicar del excedente
                    $montoAplicar = min($excedente, $pendienteEnCuota);
                    
                    if ($montoAplicar > 0) {
                        // Crear un nuevo pago automático con el excedente
                        $pagoCuotaSiguiente = new Pago();
                        $pagoCuotaSiguiente->cuota_id = $cuotaPendiente->id;
                        $pagoCuotaSiguiente->fecha_de_pago = now();
                        $pagoCuotaSiguiente->monto_pagado = $montoAplicar; // En USD
                        $pagoCuotaSiguiente->monto_usd = $montoAplicar;
                        $pagoCuotaSiguiente->pago_divisa = 0; // Es en USD
                        $pagoCuotaSiguiente->sin_comprobante = true; // Sin comprobante físico
                        $pagoCuotaSiguiente->acreedor_id = 1; // Valor fijo para acreedor_id
                        $pagoCuotaSiguiente->es_pago_excedente = 1; // Marcar este pago como generado por excedente (1 = true en MySQL)
                        $pagoCuotaSiguiente->comprobante = null; // No se guarda el comprobante
                        $pagoCuotaSiguiente->save();
                        
                        // Actualizar el excedente restante
                        $excedente -= $montoAplicar;
                        
                        // Actualizar estado de la cuota
                        $totalPagadoSiguiente = Pago::where('cuota_id', $cuotaPendiente->id)->sum('monto_usd');
                        if ($totalPagadoSiguiente >= $cuotaPendiente->monto) {
                            $cuotaPendiente->estado = 'pagada';
                        } else {
                            $cuotaPendiente->estado = 'parcial';
                        }
                        $cuotaPendiente->save();
                    }
                }
                
                // Si todavía queda excedente, mostramos un mensaje adicional
                if ($excedente > 0) {
                    $mensaje = "Se registró correctamente el pago para la Cuota #{$cuota->numero_de_cuota}. ";
                    $mensaje .= "Se generó un excedente de U$D " . number_format($excedente, 2) . 
                               " que no pudo aplicarse a más cuotas pendientes.";
                    
                    return redirect()->back()
                        ->with('success', $mensaje)
                        ->with('cuota_pagada_id', $cuota->id);
                }
            } else {
                // Actualizar estado de la cuota actual (sin excedente)
                if ($totalPagadoCuotaActual >= $cuota->monto) {
                    $cuota->estado = 'pagada';
                } else {
                    $cuota->estado = 'parcial';
                }
                $cuota->save();
            }
            
            // Mensaje de éxito con formato de moneda correcto
            $monedaTexto = $pago->pago_divisa ? 
                'ARS $' . number_format($pago->monto_pagado, 2) : 
                'U$D ' . number_format($pago->monto_usd, 2);
            
            $mensaje = "Se registró correctamente el pago de {$monedaTexto} para la Cuota #{$cuota->numero_de_cuota}.";
            
            // Redirigir y pasar el ID de la cuota para hacer scroll
            return redirect()->back()
                ->with('success', $mensaje)
                ->with('cuota_pagada_id', $cuota->id);
            
        } catch (\Exception $e) {
            // Log detallado del error
            Log::error('Error en registrarPago', [
                'mensaje' => $e->getMessage(),
                'linea' => $e->getLine(),
                'archivo' => $e->getFile(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return redirect()->back()
                ->with('error', 'Error al registrar el pago: ' . $e->getMessage())
                ->withInput();
        }
    }
    
    /**
     * Muestra un comprobante específico
     */
    public function mostrarComprobante($id)
    {
        try {
            $pago = Pago::findOrFail($id);
            
            if (!$pago->comprobante) {
                return abort(404, 'No se encontró el comprobante');
            }
            
            return Storage::disk('public')->response($pago->comprobante);
        } catch (\Exception $e) {
            Log::error('Error al mostrar comprobante', [
                'id' => $id,
                'error' => $e->getMessage()
            ]);
            
            return abort(500, 'Error al procesar el comprobante');
        }
    }
} 