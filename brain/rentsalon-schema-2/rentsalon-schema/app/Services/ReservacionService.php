<?php

namespace App\Services;

use App\Exceptions\ReservacionConflictoException;
use App\Models\Reservacion;
use Illuminate\Support\Facades\DB;

/**
 * Concentra las reglas de negocio de Reservaciones para que el mismo
 * código se reutilice desde el panel admin (alta manual) y desde el
 * portal público (solicitud de reserva del cliente).
 */
class ReservacionService
{
    /**
     * Crea una reservación validando disponibilidad dentro de una
     * transacción con lock, para que dos solicitudes simultáneas
     * nunca terminen ocupando el mismo salón/horario.
     *
     * @throws ReservacionConflictoException
     */
    public function crear(array $datos): Reservacion
    {
        return DB::transaction(function () use ($datos) {
            $hayConflicto = Reservacion::existeConflictoDeHorario(
                salonId: $datos['salon_id'],
                fecha: $datos['fecha_evento'],
                horaInicio: $datos['hora_inicio'],
                horaFin: $datos['hora_fin'],
            );

            if ($hayConflicto) {
                throw new ReservacionConflictoException();
            }

            return Reservacion::create($datos);
        });
    }

    /**
     * Actualiza fecha/hora/salón de una reservación existente,
     * revalidando disponibilidad (ignorando su propio registro).
     *
     * @throws ReservacionConflictoException
     */
    public function actualizar(Reservacion $reservacion, array $datos): Reservacion
    {
        return DB::transaction(function () use ($reservacion, $datos) {
            $salonId = $datos['salon_id'] ?? $reservacion->salon_id;
            $fecha = $datos['fecha_evento'] ?? $reservacion->fecha_evento->format('Y-m-d');
            $horaInicio = $datos['hora_inicio'] ?? $reservacion->hora_inicio;
            $horaFin = $datos['hora_fin'] ?? $reservacion->hora_fin;

            $hayConflicto = Reservacion::existeConflictoDeHorario(
                salonId: $salonId,
                fecha: $fecha,
                horaInicio: $horaInicio,
                horaFin: $horaFin,
                ignorarReservacionId: $reservacion->id,
            );

            if ($hayConflicto) {
                throw new ReservacionConflictoException();
            }

            $reservacion->update($datos);

            return $reservacion->fresh();
        });
    }

    public function confirmar(Reservacion $reservacion): Reservacion
    {
        $reservacion->update(['estado' => 'confirmada']);

        return $reservacion;
        // Aquí es donde, en Fase 2, se dispararía la notificación de
        // WhatsApp/correo "Tu reservación fue confirmada".
    }

    public function rechazar(Reservacion $reservacion): Reservacion
    {
        $reservacion->update(['estado' => 'cancelada']);

        return $reservacion;
    }

    public function cancelar(Reservacion $reservacion): Reservacion
    {
        $reservacion->update(['estado' => 'cancelada']);

        return $reservacion;
    }

    public function finalizar(Reservacion $reservacion): Reservacion
    {
        $reservacion->update(['estado' => 'finalizada']);

        return $reservacion;
    }
}
