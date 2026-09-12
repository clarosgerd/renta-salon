<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

/**
 * Bug real (11/09/2026): faltaba AuthorizesRequests — cualquier
 * $this->authorize(...) (ReservacionController, PagoAbonoController,
 * SalonController, PaqueteController) explotaba con "Call to undefined
 * method ::authorize()". Confirma que ningún controller de este proyecto
 * se había ejecutado contra una request HTTP real hasta ahora — ver
 * también los $table faltantes en Salon/Reservacion/PagoAbono/Imagen.
 */
abstract class Controller
{
    use AuthorizesRequests;
}
