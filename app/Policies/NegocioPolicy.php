<?php

namespace App\Policies;

use App\Models\Negocio;
use App\Models\User;

/**
 * Solo Super Admin Plataforma administra el catálogo de negocios (SA-1/
 * SA-2/SA-3) — la restricción de dominio (admin.rentsalon-pro.test) ya
 * limita quién LLEGA hasta acá, esta Policy evita que alguien autenticado
 * con otro rol opere si de alguna forma entra a la URL.
 */
class NegocioPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->esSuperAdminPlataforma();
    }

    public function view(User $user, Negocio $negocio): bool
    {
        return $user->esSuperAdminPlataforma();
    }

    public function create(User $user): bool
    {
        return $user->esSuperAdminPlataforma();
    }

    public function update(User $user, Negocio $negocio): bool
    {
        return $user->esSuperAdminPlataforma();
    }
}
