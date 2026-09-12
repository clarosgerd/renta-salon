<?php

namespace App\Exceptions;

use Exception;

/**
 * Se lanza cuando dos reservaciones intentan ocupar el mismo salón
 * en un horario que se traslapa. El controlador la captura y la
 * convierte en un error de validación legible para el usuario.
 */
class ReservacionConflictoException extends Exception
{
    public function __construct(string $mensaje = 'El salón ya está ocupado en ese horario.')
    {
        parent::__construct($mensaje);
    }
}
