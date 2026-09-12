<?php

namespace App\Http\Controllers\Concerns;

use App\Models\Salon;
use Illuminate\Http\Request;

/**
 * Extraído de ReservacionController (11/09/2026, Fase 3 Calendario) —
 * CalendarioController necesita el mismo scoping de salones (Admin Negocio/
 * Super Admin ven todos, Admin Salón/Cajero solo los suyos vía
 * usuario_salon) para no mostrar el filtro/eventos de salones que el
 * usuario no debería ni ver.
 */
trait ScopesSalonesPermitidos
{
    protected function salonesDelUsuario(Request $request)
    {
        $usuario = $request->user();

        if (in_array($usuario->role, ['admin_negocio', 'super_admin_plataforma'])) {
            return Salon::query();
        }

        return Salon::whereIn('id', $usuario->salonesPermitidos());
    }
}
