<?php

namespace App\Policies;

use App\Models\Salon;
use App\Models\User;

/**
 * Gestión del catálogo de Salones — solo Admin Negocio (o Super Admin
 * Plataforma). Admin Salón/Cajero operan SOBRE los salones que les
 * asignen (reservaciones, POS, etc.) pero no administran el catálogo en
 * sí — ver Historias_Usuario_Pantallas_RentSalon_Pro.md AN-1 vs AS-1..4
 * (ninguna historia de Admin Salón/Cajero menciona crear/editar salones).
 */
class SalonPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->esAdmin($user);
    }

    public function view(User $user, Salon $salon): bool
    {
        return $this->esAdmin($user);
    }

    public function create(User $user): bool
    {
        return $this->esAdmin($user);
    }

    public function update(User $user, Salon $salon): bool
    {
        return $this->esAdmin($user);
    }

    protected function esAdmin(User $user): bool
    {
        return in_array($user->role, ['admin_negocio', 'super_admin_plataforma'], true);
    }
}
