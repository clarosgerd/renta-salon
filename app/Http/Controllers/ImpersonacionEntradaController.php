<?php

namespace App\Http\Controllers;

use App\Models\ImpersonacionLog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * SA-5 (11/09/2026) — lado TENANT del flujo de impersonación (ver
 * Plataforma\ImpersonacionController::iniciar para el origen del link
 * firmado). Vive dentro del grupo de rutas con middleware 'negocio', para
 * que app('negocio_actual') esté resuelto por el Host de este dominio.
 */
class ImpersonacionEntradaController extends Controller
{
    /**
     * GET /impersonar/entrar/{log} — protegida por el middleware 'signed'
     * (firma de un solo uso, 60s de vida, generada en
     * Plataforma\ImpersonacionController::iniciar). Doble chequeo extra
     * además de la firma: el log no puede estar ya finalizado (reuso de
     * URL) ni pertenecer a OTRO negocio (link firmado contra el host
     * equivocado).
     */
    public function entrar(Request $request, int $log): RedirectResponse
    {
        $registro = ImpersonacionLog::activa()->findOrFail($log);
        abort_unless($registro->negocio_id === app('negocio_actual')->id, 403);

        Auth::login($registro->usuarioImpersonado);
        $request->session()->regenerate();
        $request->session()->put('impersonacion_log_id', $registro->id);

        return redirect()->route('dashboard')->with('success', 'Sesión de soporte iniciada.');
    }

    /**
     * POST /impersonar/salir
     */
    public function salir(Request $request): RedirectResponse
    {
        $logId = $request->session()->pull('impersonacion_log_id');
        if ($logId) {
            ImpersonacionLog::whereKey($logId)->update(['finalizada_en' => now()]);
        }

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('success', 'Sesión de soporte finalizada.');
    }
}
