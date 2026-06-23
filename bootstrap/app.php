<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Agregar Inertia al grupo web (para que funcione en todas las rutas web)
        $middleware->web(append: [
            \Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets::class,
            \App\Http\Middleware\HandleInertiaRequests::class,  // Agregar Inertia aquí
        ]);
        
        // Registrar middlewares de perfiles
        $middleware->alias([
            'admin' => \App\Http\Middleware\EnsureUserIsAdmin::class,
            'gerencia' => \App\Http\Middleware\EnsureUserIsGerencia::class,
            'gerente_produccion' => \App\Http\Middleware\EnsureUserIsGerenteProduccion::class,
            'gerencia_or_gerente_produccion' => \App\Http\Middleware\EnsureUserIsGerenciaOrGerenteProduccion::class,
            'operador' => \App\Http\Middleware\EnsureUserIsOperador::class,
            'calidad' => \App\Http\Middleware\EnsureUserIsCalidad::class,
            'ingeniero_procesos' => \App\Http\Middleware\EnsureUserIsIngenieroProcesos::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();