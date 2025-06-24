<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Comprador;
use App\Models\Lote;
use App\Models\Financiacion;
use App\Models\Acreedor;
use App\Models\Cuota;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class EntryController extends Controller
{
    public function store(Request $request)
    {
        DB::beginTransaction();

        try {
            // Validar los datos del formulario
            $request->validate([
                'nombre' => 'nullable|string|max:255',
                'direccion' => 'nullable|string|max:255',
                'telefono' => 'nullable|string|max:255',
                'email' => 'nullable|email|max:255',
                'dni' => 'nullable|string|max:20',
                'loteo' => 'nullable|string|max:255',
                'manzana' => 'nullable|string|max:255',
                'lote' => 'nullable|string|max:255',
                'mts_cuadrados' => 'nullable|numeric',
                'monto_a_financiar' => 'required|numeric',
                'cantidad_de_cuotas' => 'required|integer',
                'fecha_de_vencimiento' => 'required|date',
                'acreedores' => 'array',
                'acreedores.*.id' => 'exists:acreedores,id',
                'acreedores.*.porcentaje' => 'numeric|min:0|max:100',
            ]);

            // Crear el nuevo comprador
            $comprador = Comprador::create([
                'nombre' => $request->nombre ?? '',
                'direccion' => $request->direccion ?? '',
                'telefono' => $request->telefono ?? '',
                'email' => $request->email ?? '',
                'dni' => $request->dni ?? '',
            ]);

            // Crear el lote asociado al comprador
            $lote = Lote::create([
                'estado' => 'comprado',
                'comprador_id' => $comprador->id,
                'loteo' => $request->loteo ?? '',
                'manzana' => $request->manzana ?? '',
                'lote' => $request->lote ?? '',
                'mts_cuadrados' => $request->mts_cuadrados ? floatval($request->mts_cuadrados) : null,
            ]);

            // Calcular el monto de las cuotas
            $montoDeLasCuotas = $request->monto_a_financiar / $request->cantidad_de_cuotas;

            // Crear la financiación asociada al comprador
            $financiacion = Financiacion::create([
                'comprador_id' => $comprador->id,
                'monto_a_financiar' => $request->monto_a_financiar,
                'cantidad_de_cuotas' => $request->cantidad_de_cuotas,
                'fecha_de_vencimiento' => $request->fecha_de_vencimiento,
                'monto_de_las_cuotas' => $montoDeLasCuotas,
            ]);

            // Crear las cuotas
            $fechaVencimiento = Carbon::parse($request->fecha_de_vencimiento);
            for ($i = 1; $i <= $request->cantidad_de_cuotas; $i++) {
                Cuota::create([
                    'financiacion_id' => $financiacion->id,
                    'monto' => $montoDeLasCuotas,
                    'fecha_de_vencimiento' => $fechaVencimiento->copy()->addMonths($i - 1),
                    'estado' => 'pendiente',
                    'numero_de_cuota' => $i,
                ]);
            }

            // Asociar la financiación al acreedor "admin"
            $adminAcreedor = Acreedor::firstOrCreate(['nombre' => 'admin'], ['saldo' => 0]);
            $financiacion->acreedores()->attach($adminAcreedor->id, ['porcentaje' => 100]);

            // Establecer las relaciones en el comprador
            $comprador->lote_comprado_id = $lote->id;
            $comprador->financiacion_id = $financiacion->id;
            $comprador->save();
            
            Log::info("Relaciones establecidas automáticamente: comprador={$comprador->id}, lote_comprado_id={$lote->id}, financiacion_id={$financiacion->id}");

            DB::commit();

            return redirect()->back()->with('success', 'Entradas creadas exitosamente.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->withErrors(['error' => 'Hubo un problema al crear las entradas: ' . $e->getMessage()]);
        }
    }

    public function show($id)
    {
        $comprador = Comprador::with('lote', 'financiacion.cuotas', 'financiacion.acreedores')->findOrFail($id);
        $cuotas = $comprador->financiacion->cuotas;
        $abonadoHastaLaFecha = $cuotas->where('estado', 'pagada')->sum('monto');
        $saldoPendiente = $comprador->financiacion->monto_a_financiar - $abonadoHastaLaFecha;

        // Obtener acreedores asociados a la financiación
        $acreedores = $comprador->financiacion->acreedores;

        // Si no hay acreedores, asociar el acreedor "admin" a la financiación
        if ($acreedores->isEmpty()) {
            $adminAcreedorId = 1; // ID del acreedor "admin"
            $comprador->financiacion->acreedores()->attach($adminAcreedorId, ['porcentaje' => 100]);
            $acreedores = $comprador->financiacion->acreedores()->get(); // Actualizar la colección de acreedores
        }

        return view('comprador_detalle', compact('comprador', 'cuotas', 'abonadoHastaLaFecha', 'saldoPendiente', 'acreedores'));
    }

    public function index()
    {
        $compradores = Comprador::with(['financiacion.cuotas' => function ($query) {
            $query->whereMonth('fecha_de_vencimiento', now()->month)
                  ->whereYear('fecha_de_vencimiento', now()->year);
        }])->get();

        foreach ($compradores as $comprador) {
            $cuotaActual = $comprador->financiacion->cuotas->first();
            
            if ($cuotaActual) {
                // Determina el color del estado directamente
                $estadoColor = $cuotaActual->estado_color;
            } else {
                $estadoColor = 'text-muted'; // Sin cuotas
            }

            // Pasar el color del estado a la vista
            $comprador->estado_color = $estadoColor;
        }

        return view('compradores_index', compact('compradores'));
    }

    public function edit($id)
    {
        $comprador = Comprador::findOrFail($id);
        return view('comprador_edit', compact('comprador'));
    }

    public function destroy($id)
    {
        $comprador = Comprador::findOrFail($id);
        $comprador->delete();

        return redirect()->route('compradores.index')->with('success', 'Comprador eliminado exitosamente.');
    }

    /**
     * Importar entradas desde archivo CSV
     */
    public function import(Request $request)
    {
        Log::info('EntryController@import: Iniciando proceso de importación');
        
        try {
            // 1. Verificar si hay un archivo
            if (!$request->hasFile('csv_file')) {
                Log::error('EntryController@import: No se ha enviado ningún archivo');
                return redirect()->back()->with('error', 'No se ha seleccionado ningún archivo.');
            }
            
            $file = $request->file('csv_file');
            Log::info('EntryController@import: Archivo recibido: ' . $file->getClientOriginalName());
            
            if (!$file->isValid()) {
                Log::error('EntryController@import: El archivo no es válido');
                return redirect()->back()->with('error', 'El archivo cargado no es válido.');
            }
            
            $path = $file->getRealPath();
            Log::info('EntryController@import: Ruta del archivo: ' . $path);
            
            // 2. Leer el contenido del CSV
            $csvData = file($path);
            $lineCount = count($csvData);
            Log::info('EntryController@import: El archivo tiene ' . $lineCount . ' líneas');
            
            if (empty($csvData) || $lineCount <= 1) {
                Log::error('EntryController@import: El archivo está vacío o solo tiene encabezados');
                return redirect()->back()->with('error', 'El archivo CSV está vacío o solo contiene encabezados.');
            }
            
            // 3. Separar encabezados y verificar
            $headers = str_getcsv(array_shift($csvData));
            Log::info('EntryController@import: Encabezados detectados: ' . implode(', ', $headers));
            
            // 4. Preparar para procesamiento
            $successCount = 0;
            $errors = [];
            
            // 5. Procesar cada línea
            foreach ($csvData as $index => $row) {
                $rowNumber = $index + 2; // +2 porque la primera fila es encabezado y los arrays empiezan en 0
                Log::info('EntryController@import: Procesando fila ' . $rowNumber);
                
                try {
                    // Parsear la línea CSV
                    $rowData = str_getcsv($row);
                    Log::info('EntryController@import: Datos de la fila ' . $rowNumber . ': ' . implode('|', $rowData));
                    
                    // Verificar cantidad de columnas
                    if (count($rowData) < count($headers)) {
                        Log::error('EntryController@import: La fila ' . $rowNumber . ' no tiene suficientes columnas');
                        $errors[] = "Fila {$rowNumber}: No tiene suficientes columnas";
                        continue;
                    }
                    
                    // Intentar crear registros en la base de datos
                    DB::beginTransaction();
                    Log::info('EntryController@import: Iniciando transacción para fila ' . $rowNumber);
                    
                    // Mapear datos a un arreglo asociativo para mayor claridad
                    $data = array_combine($headers, $rowData);
                    Log::info('EntryController@import: Datos mapeados: ' . json_encode($data));
                    
                    // Validar que existan los datos necesarios
                    $requiredFields = ['monto_a_financiar', 'cantidad_de_cuotas', 'fecha_de_vencimiento'];
                    
                    foreach ($requiredFields as $field) {
                        if (!isset($data[$field]) || empty($data[$field])) {
                            throw new \Exception("El campo '{$field}' es requerido y está vacío");
                        }
                    }
                    
                    // 1. Crear el lote
                    Log::info('EntryController@import: Creando lote');
                    $lote = new Lote();
                    $lote->manzana = $data['manzana'] ?? '';
                    $lote->lote = $data['lote'] ?? '';
                    $lote->loteo = $data['loteo'] ?? '';
                    $lote->mts_cuadrados = !empty($data['mts_cuadrados']) ? floatval($data['mts_cuadrados']) : null;
                    $lote->save();
                    Log::info('EntryController@import: Lote creado con ID ' . $lote->id);
                    
                    // 2. Crear el comprador
                    Log::info('EntryController@import: Creando comprador');
                    $comprador = new Comprador();
                    $comprador->nombre = $data['nombre'] ?? '';
                    $comprador->direccion = $data['direccion'] ?? '';
                    $comprador->telefono = $data['telefono'] ?? '';
                    $comprador->email = $data['email'] ?? '';
                    $comprador->dni = $data['dni'] ?? '';
                    $comprador->lote_id = $lote->id;
                    $comprador->save();
                    Log::info('EntryController@import: Comprador creado con ID ' . $comprador->id);
                    
                    // 3. Crear la financiación
                    Log::info('EntryController@import: Creando financiación');
                    $financiacion = new Financiacion();
                    $financiacion->comprador_id = $comprador->id;
                    $financiacion->monto_a_financiar = floatval($data['monto_a_financiar']);
                    $financiacion->cantidad_de_cuotas = intval($data['cantidad_de_cuotas']);
                    
                    // Procesar fecha
                    $fechaStr = trim($data['fecha_de_vencimiento']);
                    Log::info('EntryController@import: Procesando fecha: ' . $fechaStr);
                    
                    $fecha = null;
                    foreach (['Y-m-d', 'd-m-Y', 'd/m/Y', 'm/d/Y'] as $format) {
                        try {
                            $fecha = Carbon::createFromFormat($format, $fechaStr);
                            if ($fecha) {
                                Log::info('EntryController@import: Fecha parseada con formato ' . $format);
                                break;
                            }
                        } catch (\Exception $e) {
                            Log::warning('EntryController@import: Fallo al parsear fecha con formato ' . $format);
                        }
                    }
                    
                    if (!$fecha) {
                        throw new \Exception("No se pudo interpretar el formato de fecha: {$fechaStr}");
                    }
                    
                    $financiacion->fecha_de_vencimiento = $fecha;
                    $financiacion->save();
                    Log::info('EntryController@import: Financiación creada con ID ' . $financiacion->id);
                    
                    // 4. Generar cuotas
                    Log::info('EntryController@import: Generando cuotas');
                    $this->generarCuotas($financiacion);
                    
                    // Establecer las relaciones en el comprador
                    $comprador->lote_comprado_id = $lote->id;
                    $comprador->financiacion_id = $financiacion->id;
                    $comprador->save();
                    
                    Log::info("Relaciones establecidas automáticamente: comprador={$comprador->id}, lote_comprado_id={$lote->id}, financiacion_id={$financiacion->id}");
                    
                    // Confirmar transacción
                    DB::commit();
                    Log::info('EntryController@import: Transacción completada para fila ' . $rowNumber);
                    $successCount++;
                    
                } catch (\Exception $e) {
                    DB::rollBack();
                    Log::error('EntryController@import: Error en fila ' . $rowNumber . ': ' . $e->getMessage());
                    Log::error('EntryController@import: Traza: ' . $e->getTraceAsString());
                    $errors[] = "Error en fila {$rowNumber}: " . $e->getMessage();
                }
            }
            
            // 6. Preparar mensaje de respuesta
            if ($successCount > 0 && empty($errors)) {
                Log::info('EntryController@import: Proceso completado con éxito. Registros importados: ' . $successCount);
                return redirect()->back()->with('success', "{$successCount} entradas importadas correctamente");
            } elseif ($successCount > 0 && !empty($errors)) {
                Log::warning('EntryController@import: Proceso completado con advertencias. Éxitos: ' . $successCount . ', Errores: ' . count($errors));
                return redirect()->back()->with('warning', "{$successCount} entradas importadas con {count($errors)} errores: " . implode("; ", $errors));
            } else {
                Log::error('EntryController@import: No se pudo importar ninguna entrada');
                return redirect()->back()->with('error', "No se pudo importar ninguna entrada: " . implode("; ", $errors));
            }
            
        } catch (\Exception $e) {
            Log::error('EntryController@import: Error general: ' . $e->getMessage());
            Log::error('EntryController@import: Traza: ' . $e->getTraceAsString());
            return redirect()->back()->with('error', 'Error al procesar el archivo: ' . $e->getMessage());
        }
    }

    /**
     * Generar cuotas para una financiación
     */
    private function generarCuotas($financiacion)
    {
        Log::info('generarCuotas: Iniciando generación de cuotas para financiación ID ' . $financiacion->id);
        
        $montoPorCuota = $financiacion->monto_a_financiar / $financiacion->cantidad_de_cuotas;
        $fechaBase = $financiacion->fecha_de_vencimiento;
        
        Log::info('generarCuotas: Monto por cuota: ' . $montoPorCuota . ', Fecha base: ' . $fechaBase);
        
        for ($i = 1; $i <= $financiacion->cantidad_de_cuotas; $i++) {
            $cuota = new Cuota();
            $cuota->financiacion_id = $financiacion->id;
            $cuota->numero_de_cuota = $i;
            $cuota->monto = $montoPorCuota;
            
            if ($i == 1) {
                $cuota->fecha_de_vencimiento = $fechaBase;
            } else {
                $cuota->fecha_de_vencimiento = (clone $fechaBase)->addMonths($i - 1);
            }
            
            $cuota->estado = 'pendiente';
            $cuota->save();
            
            Log::info('generarCuotas: Cuota #' . $i . ' creada con ID ' . $cuota->id);
        }
        
        Log::info('generarCuotas: Generación de cuotas completada');
        return true;
    }

    /**
     * Descargar plantilla CSV
     */
    public function downloadTemplate()
    {
        Log::info('EntryController@downloadTemplate: Iniciando descarga de plantilla');
        
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="entradas_template.csv"',
        ];
        
        $callback = function() {
            $file = fopen('php://output', 'w');
            fputcsv($file, [
                'nombre', 'direccion', 'telefono', 'email', 'dni',
                'manzana', 'lote', 'loteo', 'mts_cuadrados',
                'monto_a_financiar', 'cantidad_de_cuotas', 'fecha_de_vencimiento'
            ]);
            
            // Ejemplo de datos
            fputcsv($file, [
                'Juan Pérez', 'Calle Principal 123', '123456789', 'juan@example.com', '12345678',
                'A', '15', 'Las Praderas', '500.00',
                '15000.00', '36', date('Y-m-d')
            ]);
            
            fclose($file);
        };
        
        Log::info('EntryController@downloadTemplate: Devolviendo respuesta');
        return response()->stream($callback, 200, $headers);
    }

    /**
     * Método para actualizar las relaciones faltantes
     */
    public function actualizarRelaciones()
    {
        Log::info('Iniciando actualización de relaciones');
        
        // Obtener compradores sin relaciones establecidas
        $compradores = Comprador::whereNull('lote_comprado_id')
                                ->orWhereNull('financiacion_id')
                                ->get();
        
        Log::info('Encontrados ' . count($compradores) . ' compradores con relaciones pendientes');
        
        $actualizados = 0;
        
        foreach ($compradores as $comprador) {
            try {
                // 1. Buscar lote relacionado
                $lote = Lote::where('id', $comprador->lote_id)->first();
                
                if ($lote) {
                    $comprador->lote_comprado_id = $lote->id;
                    Log::info("Actualización de comprador {$comprador->id}: lote_comprado_id = {$lote->id}");
                }
                
                // 2. Buscar financiación relacionada
                $financiacion = Financiacion::where('comprador_id', $comprador->id)->first();
                
                if ($financiacion) {
                    $comprador->financiacion_id = $financiacion->id;
                    Log::info("Actualización de comprador {$comprador->id}: financiacion_id = {$financiacion->id}");
                }
                
                // 3. Guardar los cambios
                $comprador->save();
                $actualizados++;
                
            } catch (\Exception $e) {
                Log::error("Error al actualizar relaciones para comprador {$comprador->id}: " . $e->getMessage());
            }
        }
        
        Log::info("Proceso completado: {$actualizados} compradores actualizados");
        return redirect()->back()->with('success', "{$actualizados} compradores actualizados correctamente");
    }

    /**
     * Método para diagnosticar y corregir relaciones
     */
    public function diagnosticarRelaciones()
    {
        DB::enableQueryLog(); // Habilitar el registro de consultas para depuración
        
        // Verificar estructura de tablas
        $tablaCompradorTieneColumnas = true;
        try {
            // Verificar si la tabla compradores tiene las columnas necesarias
            $columnas = DB::getSchemaBuilder()->getColumnListing('compradores');
            $columnasRequeridas = ['lote_comprado_id', 'financiacion_id'];
            $columnasFaltantes = array_diff($columnasRequeridas, $columnas);
            
            if (!empty($columnasFaltantes)) {
                $tablaCompradorTieneColumnas = false;
            }
        } catch (\Exception $e) {
            return view('diagnostico_relaciones', [
                'error' => 'Error al verificar estructura de la tabla: ' . $e->getMessage(),
                'compradoresInfo' => [],
                'tablaCompradorTieneColumnas' => false,
                'puedeCorregir' => false,
                'queryLog' => DB::getQueryLog(),
            ]);
        }
        
        // Obtener información detallada de compradores y sus relaciones
        $compradoresInfo = [];
        $totalCompradores = 0;
        $compradoresSinLoteCompradoId = 0;
        $compradoresSinFinanciacionId = 0;
        
        try {
            $compradores = Comprador::all();
            $totalCompradores = $compradores->count();
            
            foreach ($compradores as $comprador) {
                // Buscar lote relacionado (donde el lote tiene el comprador_id de este comprador)
                $loteRelacionado = Lote::where('comprador_id', $comprador->id)->first();
                
                // Buscar financiación relacionada
                $financiacionRelacionada = Financiacion::where('comprador_id', $comprador->id)->first();
                
                // Registrar problemas
                if (empty($comprador->lote_comprado_id)) $compradoresSinLoteCompradoId++;
                if (empty($comprador->financiacion_id)) $compradoresSinFinanciacionId++;
                
                $compradoresInfo[] = [
                    'id' => $comprador->id,
                    'nombre' => $comprador->nombre,
                    'lote_comprado_id' => $comprador->lote_comprado_id,
                    'lote_relacionado_id' => $loteRelacionado ? $loteRelacionado->id : null,
                    'lote_existe' => !empty($loteRelacionado),
                    'financiacion_id' => $comprador->financiacion_id,
                    'financiacion_existe' => !empty($financiacionRelacionada),
                    'financiacion_id_correcto' => $financiacionRelacionada ? $financiacionRelacionada->id : null,
                    'necesita_correccion' => (
                        (empty($comprador->lote_comprado_id) && !empty($loteRelacionado)) ||
                        (empty($comprador->financiacion_id) && !empty($financiacionRelacionada))
                    )
                ];
            }
        } catch (\Exception $e) {
            return view('diagnostico_relaciones', [
                'error' => 'Error al obtener datos: ' . $e->getMessage(),
                'compradoresInfo' => [],
                'tablaCompradorTieneColumnas' => $tablaCompradorTieneColumnas,
                'puedeCorregir' => false,
                'queryLog' => DB::getQueryLog(),
            ]);
        }
        
        return view('diagnostico_relaciones', [
            'error' => null,
            'compradoresInfo' => $compradoresInfo,
            'totalCompradores' => $totalCompradores,
            'compradoresSinLoteCompradoId' => $compradoresSinLoteCompradoId,
            'compradoresSinFinanciacionId' => $compradoresSinFinanciacionId,
            'tablaCompradorTieneColumnas' => $tablaCompradorTieneColumnas,
            'puedeCorregir' => true,
            'queryLog' => DB::getQueryLog(),
        ]);
    }

    /**
     * Método para corregir automáticamente las relaciones
     */
    public function corregirRelaciones(Request $request)
    {
        DB::beginTransaction();
        
        try {
            $compradores = Comprador::all();
            $corregidos = 0;
            $fallidos = 0;
            $mensajesError = [];
            
            foreach ($compradores as $comprador) {
                $actualizado = false;
                
                // 1. Corregir lote_comprado_id si está vacío
                if (empty($comprador->lote_comprado_id)) {
                    // Buscar el lote que tiene a este comprador asignado
                    $lote = Lote::where('comprador_id', $comprador->id)->first();
                    if ($lote) {
                        $comprador->lote_comprado_id = $lote->id;
                        $actualizado = true;
                        Log::info("Comprador {$comprador->id}: asignando lote_comprado_id = {$lote->id}");
                    }
                }
                
                // 2. Corregir financiacion_id si está vacío
                if (empty($comprador->financiacion_id)) {
                    $financiacion = Financiacion::where('comprador_id', $comprador->id)->first();
                    if ($financiacion) {
                        $comprador->financiacion_id = $financiacion->id;
                        $actualizado = true;
                        Log::info("Comprador {$comprador->id}: asignando financiacion_id = {$financiacion->id}");
                    }
                }
                
                // Guardar cambios si hubo actualizaciones
                if ($actualizado) {
                    try {
                        $comprador->save();
                        $corregidos++;
                    } catch (\Exception $e) {
                        $fallidos++;
                        $mensajesError[] = "Error al guardar comprador {$comprador->id}: " . $e->getMessage();
                        Log::error("Error al guardar comprador {$comprador->id}: " . $e->getMessage());
                    }
                }
            }
            
            DB::commit();
            
            if ($fallidos > 0) {
                return redirect()->route('diagnosticar.relaciones')
                    ->with('warning', "Se corrigieron {$corregidos} registros, pero fallaron {$fallidos}. Errores: " . implode("; ", $mensajesError));
            } else {
                return redirect()->route('diagnosticar.relaciones')
                    ->with('success', "Se corrigieron {$corregidos} registros correctamente.");
            }
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error general en corregirRelaciones: " . $e->getMessage());
            return redirect()->route('diagnosticar.relaciones')
                ->with('error', 'Error general: ' . $e->getMessage());
        }
    }

    /**
     * Método para verificar una entrada específica y mostrar información detallada
     */
    public function verificarEntrada($id)
    {
        try {
            $comprador = Comprador::findOrFail($id);
            
            // Recopilar información detallada
            $info = [
                'comprador' => $comprador->toArray(),
                'lote' => null,
                'financiacion' => null,
                'cuotas' => []
            ];
            
            // Verificar lote
            if ($comprador->lote_id) {
                $lote = Lote::find($comprador->lote_id);
                if ($lote) {
                    $info['lote'] = $lote->toArray();
                }
            }
            
            // Verificar financiación
            $financiacion = Financiacion::where('comprador_id', $comprador->id)->first();
            if ($financiacion) {
                $info['financiacion'] = $financiacion->toArray();
                
                // Verificar cuotas
                $cuotas = Cuota::where('financiacion_id', $financiacion->id)->get();
                if ($cuotas) {
                    $info['cuotas'] = $cuotas->toArray();
                }
            }
            
            return response()->json([
                'success' => true,
                'data' => $info
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }
}