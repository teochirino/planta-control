<?php
// app/Http/Middleware/InertiaAuth.php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Inertia\Inertia;

class InertiaAuth
{
    public function handle(Request $request, Closure $next)
    {
        Inertia::share('auth', function () use ($request) {
            $user = $request->user();
            return [
                'user' => $user ? [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'id_profile' => $user->id_profile,
                ] : null,
            ];
        });
        
        return $next($request);
    }
}