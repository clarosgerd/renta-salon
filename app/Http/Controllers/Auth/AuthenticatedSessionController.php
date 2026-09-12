<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): View
    {
        return view('auth.login');
    }

    /**
     * Handle an incoming authentication request.
     *
     * Panel Super Admin Plataforma (11/09/2026) — el destino post-login ya
     * no es fijo: un super_admin_plataforma aterriza en /plataforma/negocios
     * (route('dashboard') ni siquiera resuelve tenant en su dominio, ver
     * IdentificarNegocio). Este mismo controller/vista se reusa para el
     * login de admin.rentsalon-pro.test — ver routes/web.php.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $request->session()->regenerate();

        $home = $request->user()->esSuperAdminPlataforma()
            ? route('plataforma.negocios.index', absolute: false)
            : route('dashboard', absolute: false);

        return redirect()->intended($home);
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
