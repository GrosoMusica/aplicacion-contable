<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Comprador;
use App\Models\Lote;
use App\Models\Financiacion;
use App\Models\Cuota;
use App\Models\Acreedor;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class CsvImportController extends Controller
{
    /**
     * Importa entradas desde un archivo CSV
     */
    public function import(Request $request)
    {
        $request->validate([
            'csv_file' => 'required|file|mimes:csv,txt|max:10240',
        ]);

        $file = $request->file('csv_file');
        $path = $file->getRealPath();
        
        // Intentar detectar el delimitador (coma, punto y coma, tab)
        $delimiters = [',', ';', "\t"];
        $firstLine = '';
        
        if (($handle = fopen($path, 'r')) !== FALSE) {
            $firstLine = fgets($handle);
            fclose($handle);
        }
        
        $delimiter = ','; // por defecto
        $maxCount = 0;
        
        foreach ($delimiters as $d) {
            $count = count(str_getcsv($firstLine, $d));
            if ($count > $maxCount) {
                $maxCount = $count;
                $delimiter = $d;
            }
        }
        
        // Abrir el archivo y leer el encabezado con el delimitador correcto
        $handle = fopen($path, 'r');
        $header = fgetcsv($handle, 1000, $delimiter);
        
        // Verificar las columnas requeridas
        $requiredColumns = [
            'nombre', 'direccion', 'telefono', 'email', 'dni', 
            'manzana', 'lote', 'loteo', 'mts_cuadrados', 
            'monto_a_financiar', 'cantidad_de_cuotas', 'fecha_de_vencimiento'
        ];
        
        // Normalizar el encabezado para hacerlo más tolerante
        $headerNormalized = [];
        foreach ($header as $column) {
            // Convertir a minúsculas, quitar espacios, acentos y caracteres especiales
            $normalized = strtolower(trim($column));
            $normalized = preg_replace('/\s+/', '_', $normalized); // Reemplazar espacios con guiones bajos
            $normalized = str_replace(['á','é','í','ó','ú','ñ'], ['a','e','i','o','u','n'], $normalized);
            $headerNormalized[] = $normalized;
        }
        
        // Normalizar también las columnas requeridas para la comparación
        $requiredNormalized = [];
        foreach ($requiredColumns as $column) {
            $normalized = strtolower(trim($column));
            $normalized = preg_replace('/\s+/', '_', $normalized);
            $normalized = str_replace(['á','é','í','ó','ú','ñ'], ['a','e','i','o','u','n'], $normalized);
            $requiredNormalized[$normalized] = $column; // Guardamos la referencia al nombre original
        }
        
        // Verificar columnas faltantes
        $missingColumns = [];
        foreach ($requiredNormalized as $normalized => $original) {
            if (!in_array($normalized, $headerNormalized)) {
                $missingColumns[] = $original;
            }
        }
        
        if (!empty($missingColumns)) {
            return redirect()->back()->withErrors(['csv_file' => 'Faltan columnas requeridas: ' . implode(', ', $missingColumns)]);
        }
        
        // Crear mapeo de índices
        $columnIndexes = [];
        foreach ($requiredNormalized as $normalized => $original) {
            $index = array_search($normalized, $headerNormalized);
            if ($index !== false) {
                $columnIndexes[$original] = $index;
            }
        }
        
        // Procesar el archivo
        $totalSuccess = 0;
        $totalErrors = 0;
        $totalDniDuplicados = 0; // Contador para DNIs duplicados
        $errors = [];
        $rowNumber = 1; // Empezamos en 1 porque la fila 0 es el encabezado
        
        // Iniciar transacción de base de datos
        DB::beginTransaction();
        
        try {
            // Procesar cada fila del CSV
            while (($row = fgetcsv($handle, 1000, $delimiter)) !== FALSE) {
                $rowNumber++;
                
                try {
                    // Mapear datos de la fila a un array asociativo usando los índices correctos
                    $data = [];
                    foreach ($requiredColumns as $column) {
                        $index = $columnIndexes[$column];
                        $data[$column] = $row[$index] ?? null;
                    }
                    
                    // Verificar si ya existe un comprador con el mismo DNI
                    $dniExistente = Comprador::where('dni', $data['dni'])->first();
                    if ($dniExistente) {
                        // Marcar el comprador existente como judicializado
                        $dniExistente->judicializado = 1;
                        $dniExistente->save();
                        $totalDniDuplicados++;
                    }
                    
                    // Crear el comprador (siempre crear uno nuevo, incluso si el DNI ya existe)
                    $comprador = new Comprador();
                    $comprador->nombre = $data['nombre'];
                    $comprador->direccion = $data['direccion'];
                    $comprador->telefono = $data['telefono'];
                    $comprador->email = $data['email'];
                    $comprador->dni = $data['dni'];
                    $comprador->judicializado = $dniExistente ? 1 : 0; // Si existe DNI duplicado, marcar como judicializado
                    $comprador->save();
                    
                    // SIEMPRE crear un nuevo lote
                    $lote = new Lote();
                    $lote->manzana = $data['manzana'];
                    $lote->lote = $data['lote'];
                    $lote->loteo = $data['loteo'];
                    $lote->mts_cuadrados = !empty($data['mts_cuadrados']) ? floatval($data['mts_cuadrados']) : null;
                    $lote->estado = 'comprado';
                    $lote->comprador_id = $comprador->id; // Asociar el lote con el comprador
                    $lote->save();
                    
                    // Crear la financiación y asociarla solo con el comprador
                    $financiacion = new Financiacion();
                    $financiacion->comprador_id = $comprador->id; // Asociación con comprador
                    $financiacion->monto_a_financiar = $this->cleanAmount($data['monto_a_financiar']);
                    $financiacion->cantidad_de_cuotas = $data['cantidad_de_cuotas'];
                    
                    // Calcular y establecer el monto de cada cuota
                    $cantidadCuotas = intval($data['cantidad_de_cuotas']);
                    $montoPorCuota = $this->cleanAmount($data['monto_a_financiar']) / $cantidadCuotas;
                    $financiacion->monto_de_las_cuotas = $montoPorCuota;
                    
                    // Parsear la fecha correctamente - manejando diferentes formatos posibles
                    $fechaVencimiento = null;
                    try {
                        // Intenta con formato ISO
                        $fechaVencimiento = Carbon::createFromFormat('Y-m-d', $data['fecha_de_vencimiento']);
                    } catch (\Exception $e) {
                        try {
                            // Intenta con formato dd/mm/yyyy
                            $fechaVencimiento = Carbon::createFromFormat('d/m/Y', $data['fecha_de_vencimiento']);
                        } catch (\Exception $e) {
                            try {
                                // Intenta con formato dd-mm-yyyy
                                $fechaVencimiento = Carbon::createFromFormat('d-m-Y', $data['fecha_de_vencimiento']);
                            } catch (\Exception $e) {
                                throw new \Exception("Formato de fecha inválido: {$data['fecha_de_vencimiento']}");
                            }
                        }
                    }
                    
                    $financiacion->fecha_de_vencimiento = $fechaVencimiento;
                    $financiacion->save();
                    
                    // Generar las cuotas
                    // Fecha base para las cuotas (el día 5 del mes siguiente a la fecha de vencimiento)
                    $fechaBase = $fechaVencimiento->copy()->addMonth();
                    $fechaBase->day = 5; // Fijar al día 5
                    
                    for ($i = 0; $i < $cantidadCuotas; $i++) {
                        $cuota = new Cuota();
                        $cuota->financiacion_id = $financiacion->id; // Asociación con financiación
                        $cuota->monto = $montoPorCuota;
                        $cuota->estado = 'pendiente';
                        $cuota->numero_de_cuota = $i + 1;  // Añadir número de cuota
                        
                        // Calcular fecha de vencimiento (día 5 de cada mes)
                        $fechaCuota = $fechaBase->copy()->addMonths($i);
                        $cuota->fecha_de_vencimiento = $fechaCuota;
                        
                        $cuota->save();
                    }
                    
                    // Asociar la financiación al acreedor "admin" (igual que en EntryController)
                    $adminAcreedor = Acreedor::firstOrCreate(['nombre' => 'admin'], ['saldo' => 0]);
                    $financiacion->acreedores()->attach($adminAcreedor->id, ['porcentaje' => 100]);
                    
                    // Establecer las relaciones en el comprador (igual que en EntryController)
                    $comprador->lote_comprado_id = $lote->id;
                    $comprador->financiacion_id = $financiacion->id;
                    $comprador->save();
                    
                    Log::info("CSV Import - Relaciones establecidas: comprador={$comprador->id}, lote_comprado_id={$lote->id}, financiacion_id={$financiacion->id}");
                    
                    $totalSuccess++;
                    
                } catch (\Exception $e) {
                    $totalErrors++;
                    $errors[] = "Error en la fila {$rowNumber}: " . $e->getMessage();
                    Log::error("Error al procesar CSV fila {$rowNumber}: " . $e->getMessage());
                }
            }
            
            // Si hubo más éxitos que errores, confirmar la transacción
            if ($totalSuccess > 0) {
                DB::commit();
                $message = "Se importaron {$totalSuccess} registros exitosamente.";
                
                if ($totalDniDuplicados > 0) {
                    $message .= " Se omitieron {$totalDniDuplicados} registros por DNI duplicado.";
                }
                
                if ($totalErrors > 0) {
                    $message .= " {$totalErrors} registros fallaron por otros errores.";
                }
                
                return redirect()->back()->with('success', $message)->with('csv_errors', $errors);
            } else {
                // Si todo falló, revertir la transacción
                DB::rollBack();
                
                $errorMessage = 'No se pudo importar ningún registro.';
                if ($totalDniDuplicados > 0) {
                    $errorMessage .= " {$totalDniDuplicados} registros tenían DNIs duplicados.";
                }
                
                return redirect()->back()->withErrors(['csv_file' => $errorMessage])->with('csv_errors', $errors);
            }
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error general al procesar CSV: " . $e->getMessage());
            return redirect()->back()->withErrors(['csv_file' => 'Error al procesar el archivo: ' . $e->getMessage()]);
        } finally {
            fclose($handle);
        }
    }
    
    /**
     * Descarga una plantilla CSV para importar entradas
     */
    public function downloadTemplate()
    {
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="plantilla_entradas.csv"',
        ];
        
        $columns = [
            'nombre', 'direccion', 'telefono', 'email', 'dni', 
            'manzana', 'lote', 'loteo', 'mts_cuadrados', 
            'monto_a_financiar', 'cantidad_de_cuotas', 'fecha_de_vencimiento'
        ];
        
        $callback = function() use ($columns) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);
            
            // Fila de ejemplo
            fputcsv($file, [
                'Juan Pérez',
                'Av. Principal 123',
                '123456789',
                'juan@ejemplo.com',
                '12345678',
                'A',
                '10',
                'Los Pinos',
                '500',
                '10000',
                '24',
                date('Y-m-d')
            ]);
            
            fclose($file);
        };
        
        return response()->stream($callback, 200, $headers);
    }

    private function cleanAmount($amount)
    {
        if (empty($amount)) {
            return 0;
        }
        
        // Eliminar espacios
        $amount = trim($amount);
        
        // Eliminar prefijos de moneda
        $amount = preg_replace('/USD\s*/i', '', $amount);
        $amount = preg_replace('/\$\s*/', '', $amount);
        $amount = preg_replace('/\s*\$/', '', $amount);
        
        // Eliminar espacios extra
        $amount = preg_replace('/\s+/', '', $amount);
        
        // Detectar el formato basado en la posición de comas y puntos
        $commaCount = substr_count($amount, ',');
        $dotCount = substr_count($amount, '.');
        
        if ($commaCount > 0 && $dotCount > 0) {
            // Formato mixto: 1,234.56 o 1.234,56
            $lastComma = strrpos($amount, ',');
            $lastDot = strrpos($amount, '.');
            
            if ($lastComma > $lastDot) {
                // Formato europeo: 1.234,56 (punto como miles, coma como decimal)
                $amount = str_replace('.', '', $amount); // Eliminar puntos de miles
                $amount = str_replace(',', '.', $amount); // Convertir coma decimal a punto
            } else {
                // Formato americano: 1,234.56 (coma como miles, punto como decimal)
                $amount = str_replace(',', '', $amount); // Eliminar comas de miles
            }
        } elseif ($commaCount > 0) {
            // Solo comas: 1,234 o 1,234,567
            if ($commaCount == 1 && strlen($amount) - strpos($amount, ',') == 3) {
                // Probablemente decimal: 1,23
                $amount = str_replace(',', '.', $amount);
            } else {
                // Probablemente miles: 1,234
                $amount = str_replace(',', '', $amount);
            }
        } elseif ($dotCount > 0) {
            // Solo puntos: 1.234 o 1.234.567
            if ($dotCount == 1 && strlen($amount) - strpos($amount, '.') == 3) {
                // Probablemente decimal: 1.23
                // Ya está en formato correcto
            } else {
                // Probablemente miles: 1.234
                $amount = str_replace('.', '', $amount);
            }
        }
        
        // Si después de limpiar está vacío, retornar 0
        if (empty($amount)) {
            return 0;
        }
        
        // Convertir a float y validar
        $floatValue = floatval($amount);
        
        // Log para debug
        Log::info("cleanAmount: original='{$amount}', cleaned={$floatValue}");
        
        return $floatValue;
    }
} 