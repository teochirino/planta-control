<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            \Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets::class,
        ]);
        
        // Aplicar Inertia solo a rutas específicas (no a admin ni supervisor)
        $middleware->group('inertia', [
            \App\Http\Middleware\HandleInertiaRequests::class,
        ]);

        // Registrar middlewares de perfiles
        $middleware->alias([
            'admin' => \App\Http\Middleware\EnsureUserIsAdmin::class,
            'gerencia' => \App\Http\Middleware\EnsureUserIsGerencia::class,
            'operador' => \App\Http\Middleware\EnsureUserIsOperador::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
