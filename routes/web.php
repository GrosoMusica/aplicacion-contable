<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

use App\Http\Controllers\EntryController;
use App\Http\Controllers\CompradorController;
use App\Http\Controllers\AcreedorController;
use App\Http\Controllers\LoteController;
use App\Http\Controllers\PagoController;
use App\Http\Controllers\CsvImportController;
use App\Http\Controllers\DiagnosticoController;
use App\Http\Controllers\NewAcreedorController;
use App\Http\Controllers\PagosAcreedorController;
use App\Http\Controllers\ComprobanteController;
use App\Http\Controllers\FinanciacionController;
use App\Http\Controllers\ContratoController;
use App\Http\Controllers\ErrorController;
use App\Http\Controllers\PaymentManagementController;
use App\Http\Controllers\OperationManagementController;

// Deshabilitar registro y solo dejar login y logout
Auth::routes([
    'register' => false,  // Deshabilitar registro
    'reset' => true,      // Mantener la recuperación de contraseña
    'verify' => false,    // Deshabilitar verificación de email
]);

// Ruta principal que muestra welcome.blade.php
Route::get('/', function () {
    return view('welcome', ['active' => 'home']);
})->middleware('auth')->name('home');

// Redirigir /home a / (para compatibilidad con redirección por defecto)
Route::redirect('/home', '/');

// Rutas accesibles a todos los usuarios autenticados
Route::middleware(['auth'])->group(function () {
    // Rutas para compradores (disponible para todos los usuarios)
    Route::get('/compradores', [CompradorController::class, 'index'])->name('compradores.index');
    Route::get('/comprador/{id}', [EntryController::class, 'show'])->name('comprador.show');
    
    // Ruta para información general
    Route::get('/informes', [App\Http\Controllers\InformeController::class, 'index'])->name('informes.index');
    
    // Búsqueda de compradores
    Route::get('/compradores/buscar', [App\Http\Controllers\CompradorController::class, 'buscar'])->name('compradores.buscar');
    
    // Rutas para Pagos básicas
    Route::get('/pagos', [PagoController::class, 'index'])->name('pagos.index');
    Route::post('/cuotas/pagar', [PagoController::class, 'registrarPago'])->name('cuotas.pagar');
    Route::get('/pagos/comprobante/{id}', [App\Http\Controllers\PagoController::class, 'mostrarComprobante'])
        ->name('pagos.comprobante');
    
    // Ruta para errores del sistema
    Route::get('/errors', [ErrorController::class, 'index'])->name('errors.index');
    
    // Rutas para contratos
    Route::post('/contratos', [ContratoController::class, 'store'])->name('contratos.store');
    Route::get('/contratos/{id}', [ContratoController::class, 'ver'])->name('contratos.ver');
    Route::put('/contratos/{id}', [ContratoController::class, 'actualizar'])->name('contratos.actualizar');
    Route::put('/contratos/{id}/cuenta-rentas', [ContratoController::class, 'updateCuentaRentas'])->name('contratos.updateCuentaRentas');
    
    // Ruta para comprobantes (visualización)
    Route::get('comprobantes/ver', [ComprobanteController::class, 'ver'])->name('comprobantes.ver');

    Route::get('/create-entries', function () {
        return view('create_entries');
    })->name('entries.create');
    Route::post('/create-entries', [EntryController::class, 'store'])->name('entries.store');

    Route::get('/payment-management/last-payments', [PaymentManagementController::class, 'getLastPayments'])->name('payment.getLastPayments');
    Route::put('/payment-management/update', [PaymentManagementController::class, 'updatePayment'])->name('payment.update');
    Route::delete('/payment-management/delete', [PaymentManagementController::class, 'deletePayment'])->name('payment.delete');
});

// Rutas exclusivas para administradores
Route::middleware(['auth', 'admin'])->group(function () {
    // Rutas para edición de compradores
    Route::get('/comprador/{id}/edit', [CompradorController::class, 'edit'])->name('comprador.edit');
    Route::put('/comprador/{id}', [CompradorController::class, 'update'])->name('comprador.update');
    Route::patch('/comprador/{id}/toggle-judicializado', [CompradorController::class, 'toggleJudicializado'])->name('comprador.toggleJudicializado');
    
    // Rutas para lotes
    Route::get('/lotes', [LoteController::class, 'index'])->name('lotes.index');
    Route::get('/lotes/{id}', [LoteController::class, 'show'])->name('lotes.show');
    
    // Rutas para crear acreedores
    Route::post('/acreedores', [AcreedorController::class, 'store'])->name('acreedores.store');
    
    // Rutas para Pagos avanzadas
    Route::post('/pagos/registrar', [App\Http\Controllers\PagoController::class, 'registrarPago'])->name('pagos.registrar');
    
    // Rutas para importación CSV
    Route::post('/csv/import', [CsvImportController::class, 'import'])->name('entries.import');
    Route::get('/csv/template', [CsvImportController::class, 'downloadTemplate'])->name('entries.template');

    // Rutas para la gestión de acreedores
    Route::get('/gestion-acreedores', [NewAcreedorController::class, 'index'])->name('gestion.acreedores.index');
    Route::post('/gestion-acreedores', [NewAcreedorController::class, 'store'])->name('gestion.acreedores.store');
    Route::get('/gestion-acreedores/{acreedor}', [NewAcreedorController::class, 'show'])->name('gestion.acreedores.show');
    Route::delete('/gestion-acreedores/{acreedor}', [NewAcreedorController::class, 'destroy'])->name('gestion.acreedores.destroy');
    Route::get('/gestion-acreedores/{acreedor}/financiaciones', [NewAcreedorController::class, 'getFinanciaciones'])
        ->name('gestion.acreedores.financiaciones');
    
    // Rutas para exportación de PDF
    Route::get('acreedores/export-pdf/{tipo?}', 'AcreedorController@exportPDF')->name('acreedores.export-pdf');
    Route::get('/acreedores/{acreedor}/distribucion-ingresos/{mes?}', [AcreedorController::class, 'exportDistribucion'])
        ->name('acreedores.export-distribucion');
    
    // Rutas para pagos a acreedores - Cambiado 'pagos' por 'index'
    Route::get('/gestion/acreedores/pagos', [PagosAcreedorController::class, 'index'])->name('gestion.acreedores.pagos');
    
    // Rutas para los comprobantes (descarga)
    Route::get('comprobantes/descargar', [ComprobanteController::class, 'descargar'])->name('comprobantes.descargar');
    
    // Endpoint para obtener acreedores asociados a una financiación
    Route::get('/api/financiaciones/{financiacion}/acreedores', function($financiacionId) {
        // Obtener los acreedores relacionados con esta financiación
        $acreedorIds = DB::table('financiacion_acreedor')
            ->where('financiacion_id', $financiacionId)
            ->pluck('acreedor_id');
            
        $acreedores = App\Models\Acreedor::whereIn('id', $acreedorIds)->get();
        
        // Asegurarnos que el Admin (id=1) siempre esté incluido
        $adminIncluido = $acreedores->contains('id', 1);
        if (!$adminIncluido) {
            $admin = App\Models\Acreedor::find(1);
            if ($admin) {
                $acreedores->prepend($admin);
            }
        }
        
        return $acreedores;
    });
    
    // Actualizar la ruta para INCREMENTAR el saldo al hacer liquidación
    Route::post('/api/acreedores/{id}/actualizar-saldo', function($id, Request $request) {
        $acreedor = App\Models\Acreedor::findOrFail($id);
        
        // Validar que el monto sea numérico
        $request->validate([
            'monto' => 'required|numeric',
            'comprobante' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:2048',
            'sin_comprobante' => 'nullable'
        ]);
        
        $monto = $request->monto;
        
        // Incrementar el saldo (no decrementar)
        $acreedor->saldo += $monto;
        $acreedor->save();
        
        // Guardar comprobante si existe
        $rutaComprobante = null;
        $sinComprobante = $request->has('sin_comprobante');
        
        if ($request->hasFile('comprobante') && !$sinComprobante) {
            $file = $request->file('comprobante');
            $nombreArchivo = "Liquidacion-" . date('Y-m-d-His') . "." . $file->extension();
            $rutaComprobante = $file->storeAs(
                "LIQUIDACIONES/{$acreedor->id}-{$acreedor->nombre}",
                $nombreArchivo,
                'public'
            );
        }
        
        // Crear registro de liquidación
        App\Models\Liquidacion::create([
            'acreedor_id' => $acreedor->id,
            'monto' => $monto,
            'fecha' => $request->fecha_liquidacion ?? now()->format('Y-m-d'),
            'comprobante' => $rutaComprobante,
            'sin_comprobante' => $sinComprobante,
            'usuario_id' => auth()->id() ?? 1
        ]);
        
        return redirect()->back()->with('success', "Liquidación de $" . number_format($monto, 2) . " realizada con éxito a {$acreedor->nombre}");
    })->name('api.acreedores.actualizar-saldo');
});

// Ruta para la página de morosos (disponible para todos)
Route::get('/morosos', [FinanciacionController::class, 'morosos'])->name('morosos.index')->middleware('auth');

// Ruta para la página de próximos a finalizar (disponible para todos)
Route::get('/proximos-a-finalizar', [App\Http\Controllers\FinanciacionController::class, 'proximosAFinalizar'])
    ->name('proximos.index')->middleware('auth');

// Rutas para la gestión de operaciones
Route::get('/operation/compradores', [App\Http\Controllers\OperationManagementController::class, 'getCompradores'])->name('operation.getCompradores');
Route::delete('/operation/delete', [App\Http\Controllers\OperationManagementController::class, 'deleteOperation'])->name('operation.delete');
