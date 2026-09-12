<?php

namespace App\Policies;

use App\Models\Paquete;
use App\Models\User;

/**
 * Mismo criterio que SalonPolicy — solo Admin Negocio / Super Admin
 * Plataforma administran el catálogo de paquetes (ver AN-2).
 */
class PaquetePolicy
{
    public function viewAny(User $user): bool
    {
        return $this->esAdmin($user);
    }

    public function view(User $user, Paquete $paquete): bool
    {
        return $this->esAdmin($user);
    }

    public function create(User $user): bool
    {
        return $this->esAdmin($user);
    }

    public function update(User $user, Paquete $paquete): bool
    {
        return $this->esAdmin($user);
    }

    protected function esAdmin(User $user): bool
    {
        return in_array($user->role, ['admin_negocio', 'super_admin_plataforma'], true);
    }
}
