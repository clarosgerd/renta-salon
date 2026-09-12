<?php

namespace App\Http\Controllers;

use App\Exceptions\ReservacionConflictoException;
use App\Http\Requests\SolicitarReservaRequest;
use App\Models\Paquete;
use App\Models\Salon;
use App\Services\ReservacionService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

/**
 * Solicitud de reserva desde el portal público (Fase 6, 11/09/2026) — ver
 * CL-3/§3.14. Reusa ReservacionService::crear() TAL CUAL (ya valida
 * conflicto de horario en una transacción con lock) — el único cambio es
 * origen='portal_publico'/estado='pendiente' en vez de 'manual'/
 * 'confirmada'. AN-4 (confirmar/rechazar del lado admin) ya existe desde
 * antes de esta fase, no se toca.
 */
class SolicitudController extends Controller
{
    public function __construct(private readonly ReservacionService $reservaciones)
    {
    }

    /**
     * GET /solicitar
     * Pre-carga desde cualquiera de las 2 entradas: ficha de salón
     * (?salon=) o ficha de paquete (?paquete=), + opcionalmente ?fecha=
     * (clic en un día libre del calendario público).
     */
    public function create(Request $request): View
    {
        $salones = Salon::activos()->get();
        $paquetes = Paquete::activos()->with('salones')->get();

        // Mapa paquete_id => [salon_ids] para que el <select> de paquete se
        // filtre client-side según el salón elegido (JS vainilla, mismo
        // criterio "sin dependencias nuevas" del resto del panel) — null =
        // aplica a todos los salones (Paquete::aplicaATodosLosSalones()).
        $salonesPorPaquete = $paquetes->mapWithKeys(fn (Paquete $p) => [
            $p->id => $p->aplicaATodosLosSalones() ? null : $p->salones->pluck('id'),
        ]);

        return view('portal.solicitud.create', compact('salones', 'paquetes', 'salonesPorPaquete'));
    }

    /**
     * POST /solicitar
     */
    public function store(SolicitarReservaRequest $request): View
    {
        $paquete = Paquete::findOrFail($request->paquete_id);

        $horaInicio = $request->hora_inicio;
        $horaFin = Carbon::parse($horaInicio)->addHours((int) $paquete->duracion_horas)->format('H:i');

        try {
            $reservacion = $this->reservaciones->crear([
                'negocio_id' => app('negocio_actual')->id,
                'salon_id' => $request->salon_id,
                'paquete_id' => $paquete->id,
                'cliente_nombre' => $request->cliente_nombre,
                'cliente_telefono' => $request->cliente_telefono,
                'cliente_email' => $request->cliente_email,
                'num_invitados' => $request->num_invitados,
                'fecha_evento' => $request->fecha_evento,
                'hora_inicio' => $horaInicio,
                'hora_fin' => $horaFin,
                // El servidor decide el precio desde el paquete vigente,
                // nunca del request — mismo criterio de revalidación que
                // VentaPosService (Fase 7).
                'precio_total' => $paquete->precio_base,
                'estado' => 'pendiente',
                'origen' => 'portal_publico',
                'notas' => $request->notas,
            ]);
        } catch (ReservacionConflictoException) {
            return back()
                ->withInput()
                ->withErrors(['fecha_evento' => 'Esa fecha/horario ya no está disponible para este salón. Probá con otro horario.']);
        }

        // Confirmación renderizada DIRECTO desde la respuesta del POST, no
        // un redirect a una URL con el id — a propósito: una ruta pública
        // GET /solicitud/confirmacion/{id} dejaría enumerar reservaciones
        // de OTROS clientes solo cambiando el número. El folio ya lo tiene
        // el cliente en pantalla, no hace falta una URL reconsultable.
        return view('portal.solicitud.confirmacion', ['folio' => $reservacion->folio]);
    }
}
