<?php

namespace App\Policies;

use App\Models\User;

/**
 * Gestión de Usuarios (Fase 8, 11/09/2026) — ver AN-6. Mismo criterio que
 * SalonPolicy: solo Admin Negocio (o Super Admin Plataforma) administra
 * usuarios — es la única historia que lo pide.
 *
 * Nombre de archivo/clase "UserPolicy" (no "UsuarioPolicy" como el resto
 * del panel en español) A PROPÓSITO: el modelo es App\Models\User (viene
 * de Breeze), y Laravel resuelve la Policy por convención de nombre de
 * clase del modelo — "UsuarioPolicy" nunca se hubiera auto-registrado.
 */
class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->esAdmin($user);
    }

    public function view(User $user, User $usuario): bool
    {
        return $this->esAdmin($user);
    }

    public function create(User $user): bool
    {
        return $this->esAdmin($user);
    }

    public function update(User $user, User $usuario): bool
    {
        return $this->esAdmin($user);
    }

    protected function esAdmin(User $user): bool
    {
        return in_array($user->role, ['admin_negocio', 'super_admin_plataforma'], true);
    }
}
