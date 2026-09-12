<?php

namespace App\Http\Middleware;

use App\Models\Negocio;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Resuelve el negocio activo a partir del host de la petición
 * (subdominio o dominio personalizado) y lo deja disponible en
 * el contenedor de servicios como 'negocio_actual'.
 *
 * Rutas de la plataforma central (admin.rentsalonpro.com) NO pasan
 * por este middleware, por eso el Super Admin ve todos los negocios.
 */
class IdentificarNegocio
{
    public function handle(Request $request, Closure $next): Response
    {
        $host = $request->getHost();

        $negocio = Negocio::query()
            ->where('dominio_personalizado', $host)
            ->orWhere('subdominio', explode('.', $host)[0])
            ->first();

        if (! $negocio) {
            abort(404, 'Negocio no encontrado.');
        }

        if ($negocio->estado !== 'activo') {
            abort(403, 'Este negocio tiene su servicio suspendido.');
        }

        app()->instance('negocio_actual', $negocio);

        return $next($request);
    }
}
