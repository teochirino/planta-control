<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsGerenciaOrGerenteProduccion
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!auth()->check()) {
            return redirect()->route('login');
        }
        
        if (!auth()->user()->isGerencia() && !auth()->user()->isGerenteProduccion()) {
            abort(403, 'No tienes permisos para acceder a esta sección.');
        }
        
        return $next($request);
    }
}
