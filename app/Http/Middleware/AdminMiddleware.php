<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth; // Asegúrate de importar Auth
use Symfony\Component\HttpFoundation\Response;

class AdminMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // 1. Verifica si el usuario está autenticado
        // 2. Verifica si el campo 'role' del usuario es exactamente 'admin'
        if (!Auth::check() || Auth::user()->role !== 'admin') {
            // Si no es admin, redirige a la ruta 'home' (welcome) con un mensaje de error
            return redirect()->route('home')->with('error', 'No tienes permiso para acceder a esta sección.');
        }

        // Si es admin, permite continuar con la solicitud
        return $next($request);
    }
} 