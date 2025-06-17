<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ComprobanteController extends Controller
{
    public function ver(Request $request)
    {
        $path = $request->input('path');
        
        // Verificar si el archivo existe
        if (!Storage::disk('public')->exists($path)) {
            return response()->json(['error' => 'Archivo no encontrado'], 404);
        }
        
        // Obtener el tipo MIME
        $mimeType = Storage::disk('public')->mimeType($path);
        
        // Crear una respuesta con el archivo
        $file = Storage::disk('public')->get($path);
        
        return response($file, 200)->header('Content-Type', $mimeType);
    }
    
    public function descargar(Request $request)
    {
        $path = $request->input('path');
        
        // Verificar si el archivo existe
        if (!Storage::disk('public')->exists($path)) {
            return response()->json(['error' => 'Archivo no encontrado'], 404);
        }
        
        // Obtener el nombre del archivo
        $filename = basename($path);
        
        // Devolver la respuesta de descarga
        return Storage::disk('public')->download($path, $filename);
    }
} 