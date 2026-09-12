<?php

namespace App\Http\Controllers\Plataforma;

use App\Http\Controllers\Controller;
use App\Models\ImpersonacionLog;
use App\Models\Negocio;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Illuminate\View\View;

/**
 * SA-5 (11/09/2026) — ver brain/Historias_Usuario_Pantallas_RentSalon_Pro.md.
 * Vive bajo Route::domain('admin.rentsalon-pro.test'). Reusa NegocioPolicy
 * (mismo criterio que Cobros/toggle-estado: "impersonar un negocio" es
 * parte de "administrar ese negocio", no amerita Policy propia).
 *
 * Confirmado con el usuario: sin pantalla de selección de usuario — entra
 * automáticamente como el primer admin_negocio del negocio; si no tiene
 * ninguno, se bloquea con un mensaje en vez de mostrar el botón.
 */
class ImpersonacionController extends Controller
{
    /**
     * POST /plataforma/negocios/{negocio}/impersonar
     *
     * SESSION_DOMAIN=null (.env) — las cookies de admin.rentsalon-pro.test
     * y {subdominio}.rentsalon-pro.test NO se comparten, y el tenant se
     * resuelve por Host header (IdentificarNegocio). Por eso "impersonar"
     * no es un cambio de usuario en la sesión actual: hay que llevar al
     * navegador al dominio del negocio con un link firmado de un solo uso.
     * URL::forceRootUrl() es la técnica estándar de Laravel para generar
     * una URL absoluta con un host distinto al de la request actual — se
     * restaura inmediatamente después.
     */
    public function iniciar(Negocio $negocio, Request $request): RedirectResponse
    {
        $this->authorize('update', $negocio);

        $usuario = $negocio->usuarios()->where('role', 'admin_negocio')->first();
        if (! $usuario) {
            return back()->with('error', 'Este negocio no tiene un Admin Negocio para impersonar.');
        }

        $log = ImpersonacionLog::create([
            'super_admin_user_id' => $request->user()->id,
            'negocio_id' => $negocio->id,
            'usuario_impersonado_id' => $usuario->id,
            'ip_origen' => $request->ip(),
            'iniciada_en' => now(),
        ]);

        $host = $negocio->dominio_personalizado ?: "{$negocio->subdominio}.rentsalon-pro.test";
        $puerto = $request->getPort();
        $root = $request->getScheme().'://'.$host.(in_array($puerto, [80, 443, null], true) ? '' : ':'.$puerto);

        URL::forceRootUrl($root);
        $signedUrl = URL::temporarySignedRoute('impersonar.entrar', now()->addSeconds(60), ['log' => $log->id]);
        URL::forceRootUrl(config('app.url'));

        return redirect()->away($signedUrl);
    }

    /**
     * GET /plataforma/impersonaciones — solo lectura, primera pieza de lo
     * que Fase 8 ("Usuarios y permisos... auditoría") generalizará después.
     */
    public function historial(): View
    {
        $this->authorize('viewAny', Negocio::class);

        $logs = ImpersonacionLog::with(['superAdmin', 'negocio', 'usuarioImpersonado'])
            ->latest('iniciada_en')
            ->paginate(20);

        return view('plataforma.impersonaciones.index', compact('logs'));
    }
}
