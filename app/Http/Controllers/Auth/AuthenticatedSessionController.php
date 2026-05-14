<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Inertia\Response;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): Response
    {
        return Inertia::render('Auth/Login', [
            'canResetPassword' => Route::has('password.request'),
            'status' => session('status'),
        ]);
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $request->session()->regenerate();

        // Redirigir según el perfil del usuario
        $user = auth()->user();
        
        if ($user->id_profile === 7) {
            // Administrador (CRUD)
            return redirect()->route('admin.users.index');
        } elseif ($user->id_profile === 1) {
            // Gerencia (Dashboard)
            return redirect()->route('gerencia.dashboard');
        } elseif ($user->id_profile === 5) {
            // Supervisor
            return redirect()->route('supervisor.dashboard');
        } elseif ($user->id_profile === 4) {
            // Calidad
            return redirect()->route('calidad.registrar-rechazo');
        } elseif ($user->id_profile === 8) {
            // Operador
            return redirect()->route('operador.dashboard');
        }

        return redirect()->intended(route('dashboard', absolute: false));
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}
