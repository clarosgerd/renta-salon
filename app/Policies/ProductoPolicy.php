<?php

namespace App\Policies;

use App\Models\Producto;
use App\Models\User;

/**
 * Gestión del catálogo de Productos e Inventario (Fase 7, 11/09/2026) —
 * mismo criterio que SalonPolicy: solo Admin Negocio (o Super Admin
 * Plataforma) administra el catálogo. Ni Cajero ni Admin Salón tienen
 * ninguna historia que les dé acceso (AN-6: "un Cajero solo ve POS y
 * pagos"). InventarioController reusa esta misma Policy sobre
 * Producto::class (viewAny/update) — no necesita una propia, mismo
 * patrón que NegocioConfigPagoController reusando NegocioPolicy.
 */
class ProductoPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->esAdmin($user);
    }

    public function view(User $user, Producto $producto): bool
    {
        return $this->esAdmin($user);
    }

    public function create(User $user): bool
    {
        return $this->esAdmin($user);
    }

    public function update(User $user, Producto $producto = null): bool
    {
        return $this->esAdmin($user);
    }

    protected function esAdmin(User $user): bool
    {
        return in_array($user->role, ['admin_negocio', 'super_admin_plataforma'], true);
    }
}
