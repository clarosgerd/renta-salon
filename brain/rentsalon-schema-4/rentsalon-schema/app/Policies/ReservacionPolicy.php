<?php

namespace App\Policies;

use App\Models\Reservacion;
use App\Models\User;

class ReservacionPolicy
{
    public function viewAny(User $user): bool
    {
        return in_array($user->role, ['admin_negocio', 'admin_salon', 'cajero']);
    }

    public function view(User $user, Reservacion $reservacion): bool
    {
        return $this->tieneAccesoAlSalon($user, $reservacion->salon_id);
    }

    /**
     * Para "create" no hay $reservacion todavía; el salon_id llega en el
     * request, así que el controlador vuelve a chequear tras validar
     * los datos (ver ReservacionController::store).
     */
    public function create(User $user): bool
    {
        return in_array($user->role, ['admin_negocio', 'admin_salon']);
    }

    public function update(User $user, Reservacion $reservacion): bool
    {
        return $this->tieneAccesoAlSalon($user, $reservacion->salon_id);
    }

    public function cambiarEstado(User $user, Reservacion $reservacion): bool
    {
        return $this->tieneAccesoAlSalon($user, $reservacion->salon_id);
    }

    protected function tieneAccesoAlSalon(User $user, int $salonId): bool
    {
        if (in_array($user->role, ['admin_negocio', 'super_admin_plataforma'])) {
            return true;
        }

        return $user->salones()->where('salones.id', $salonId)->exists();
    }
}
