<?php

namespace App\Services;

use App\Models\Paquete;

/**
 * Reglas de negocio de Paquetes — mismo criterio que SalonService/
 * ReservacionService. sync() con un array vacío limpia el pivote
 * paquete_salon, que es justamente la regla ya modelada en
 * Paquete::aplicaATodosLosSalones() ("sin filas = aplica a todos").
 */
class PaqueteService
{
    public function crear(array $datos): Paquete
    {
        $salonIds = $datos['salones'] ?? [];
        unset($datos['salones']);

        $paquete = Paquete::create($datos);
        $paquete->salones()->sync($salonIds);

        return $paquete;
    }

    public function actualizar(Paquete $paquete, array $datos): Paquete
    {
        $salonIds = $datos['salones'] ?? [];
        unset($datos['salones']);

        $paquete->update($datos);
        $paquete->salones()->sync($salonIds);

        return $paquete->fresh();
    }

    public function toggleActivo(Paquete $paquete): Paquete
    {
        $paquete->update(['activo' => ! $paquete->activo]);

        return $paquete;
    }
}
