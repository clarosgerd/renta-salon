<?php

namespace App\Policies;

use App\Models\User;
use App\Models\VentaPos;

/**
 * POS (Fase 7, 11/09/2026) — los 4 roles que pueden operar una venta:
 * CJ-1..CJ-3 son historias de Cajero explícitamente, y AN-6 confirma que
 * POS SÍ está en su alcance ("un Cajero solo ve POS y pagos"). El corte de
 * caja (CJ-4) no tiene policy method propio: siempre es "mis ventas de
 * hoy", auto-scopeado por cajero_id en el controller — no hay registro
 * ajeno que autorizar.
 */
class VentaPosPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->puedeOperarPos($user);
    }

    public function create(User $user): bool
    {
        return $this->puedeOperarPos($user);
    }

    public function view(User $user, VentaPos $venta): bool
    {
        return $this->puedeOperarPos($user);
    }

    protected function puedeOperarPos(User $user): bool
    {
        return in_array($user->role, ['cajero', 'admin_salon', 'admin_negocio', 'super_admin_plataforma'], true);
    }
}
