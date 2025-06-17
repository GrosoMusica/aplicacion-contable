<?php

namespace App\Http\Controllers;

use App\Models\Contrato;
use App\Models\Comprador;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ContratoController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'contrato' => 'required|mimes:pdf|max:10240', // máximo 10MB
            'id_comprador' => 'required|exists:compradores,id',
            'cuenta_rentas' => 'nullable|integer'
        ]);

        // Obtener el comprador
        $comprador = Comprador::findOrFail($request->id_comprador);
        
        $file = $request->file('contrato');
        
        // Crear nombre del archivo: nombre_comprador_fecha.pdf
        $nombreArchivo = Str::slug($comprador->nombre) . '_' . 
                        date('Y-m-d') . '.' . 
                        $file->getClientOriginalExtension();
        
        // Guardar en una carpeta específica para el comprador
        $path = $file->storeAs(
            'contratos/' . $request->id_comprador . '_' . Str::slug($comprador->nombre),
            $nombreArchivo,
            'public'
        );

        Contrato::create([
            'id_comprador' => $request->id_comprador,
            'ruta_contrato' => $path,
            'cuenta_rentas' => $request->cuenta_rentas
        ]);

        return back()->with('success', 'Contrato subido correctamente');
    }

    public function ver($id)
    {
        $contrato = Contrato::findOrFail($id);
        return response()->file(storage_path('app/public/' . $contrato->ruta_contrato));
    }

    public function actualizar(Request $request, $id)
    {
        $request->validate([
            'contrato' => 'required|mimes:pdf|max:10240', // máximo 10MB
        ]);

        $contrato = Contrato::findOrFail($id);
        $comprador = Comprador::findOrFail($contrato->id_comprador);

        // Eliminar el archivo anterior si existe
        if (Storage::disk('public')->exists($contrato->ruta_contrato)) {
            Storage::disk('public')->delete($contrato->ruta_contrato);
        }

        $file = $request->file('contrato');
        
        // Crear nombre del archivo: nombre_comprador_fecha.pdf
        $nombreArchivo = Str::slug($comprador->nombre) . '_' . 
                        date('Y-m-d') . '.' . 
                        $file->getClientOriginalExtension();
        
        // Guardar en una carpeta específica para el comprador
        $path = $file->storeAs(
            'contratos/' . $contrato->id_comprador . '_' . Str::slug($comprador->nombre),
            $nombreArchivo,
            'public'
        );

        $contrato->update([
            'ruta_contrato' => $path
        ]);

        return back()->with('success', 'Contrato actualizado correctamente');
    }

    public function updateCuentaRentas(Request $request, $id)
    {
        $request->validate([
            'cuenta_rentas' => 'required|integer'
        ]);

        $contrato = Contrato::findOrFail($id);
        
        $contrato->update([
            'cuenta_rentas' => $request->cuenta_rentas
        ]);

        return back()->with('success', 'Número de cuenta de rentas actualizado correctamente');
    }
} 