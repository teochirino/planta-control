<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    public function share(Request $request): array
    {
        $user = $request->user();
        
        $userData = null;
        if ($user) {
            $userData = [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'id_profile' => $user->id_profile,
                'is_admin' => $user->id_profile === 7,
                'is_supervisor' => $user->id_profile === 5,   // Comparación directa
                'is_gerencia' => $user->id_profile === 1,
                'is_operador' => $user->id_profile === 8,
            ];
        }
        
        return [
            ...parent::share($request),
            'auth' => [
                'user' => $userData,
            ],
            'impersonating' => $user ? $user->isImpersonated() : false,
            'flash' => [
                'success' => $request->session()->get('success'),
                'error' => $request->session()->get('error'),
                'program_created' => $request->session()->get('program_created'),
            ],
        ];
    }
}