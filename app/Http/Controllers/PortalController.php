<?php

namespace App\Http\Controllers;

use App\Models\Paquete;
use App\Models\Reservacion;
use App\Models\Salon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

/**
 * Portal público (Fase 6, 11/09/2026) — ver CL-1/CL-2/§3.14. Vive dentro
 * del grupo de rutas con middleware 'negocio' pero FUERA de 'auth' — sin
 * login, cualquiera puede navegarlo. NegocioScope ya filtra Salon/Paquete
 * por el negocio resuelto vía subdominio, sin código extra acá.
 */
class PortalController extends Controller
{
    /**
     * GET /
     */
    public function home(): View
    {
        return view('portal.home', ['negocio' => app('negocio_actual')]);
    }

    /**
     * GET /salones
     */
    public function salones(): View
    {
        $salones = Salon::activos()->with(['imagenes' => fn ($q) => $q->orderBy('orden')->limit(1)])->get();

        return view('portal.salones.index', compact('salones'));
    }

    /**
     * GET /salones/{salon}
     */
    public function salon(Salon $salon): View
    {
        abort_unless($salon->activo, 404);

        $salon->load(['imagenes' => fn ($q) => $q->orderBy('orden')]);

        // Paquetes que aplican a ESTE salón: los que tienen fila en el
        // pivote paquete_salon, o los que "aplican a todos"
        // (Paquete::aplicaATodosLosSalones() — 0 filas en el pivote).
        $paquetes = Paquete::activos()
            ->where(fn ($q) => $q->whereDoesntHave('salones')->orWhereHas('salones', fn ($q2) => $q2->where('salones.id', $salon->id)))
            ->get();

        return view('portal.salones.show', compact('salon', 'paquetes'));
    }

    /**
     * GET /salones/{salon}/disponibilidad
     * Fuente de eventos de FullCalendar, análoga a
     * Admin\CalendarioController::eventos() pero SIN ningún dato de
     * cliente (§3.2 CL-2: "sin datos de otros clientes") — solo bloques
     * ocupado/libre.
     */
    public function disponibilidad(Request $request, Salon $salon): JsonResponse
    {
        abort_unless($salon->activo, 404);

        $desde = Carbon::parse($request->query('start'))->toDateString();
        $hasta = Carbon::parse($request->query('end'))->toDateString();

        $ocupados = Reservacion::where('salon_id', $salon->id)
            ->where('estado', '!=', 'cancelada')
            ->whereBetween('fecha_evento', [$desde, $hasta])
            ->get(['fecha_evento', 'hora_inicio', 'hora_fin']);

        return response()->json($ocupados->map(fn (Reservacion $r) => [
            'title' => 'Ocupado',
            'start' => $r->fecha_evento->format('Y-m-d')."T{$r->hora_inicio}",
            'end' => $r->fecha_evento->format('Y-m-d')."T{$r->hora_fin}",
            'color' => '#9ca3af',
            'display' => 'block',
        ]));
    }

    /**
     * GET /paquetes
     */
    public function paquetes(): View
    {
        return view('portal.paquetes.index', ['paquetes' => Paquete::activos()->get()]);
    }

    /**
     * GET /paquetes/{paquete}
     */
    public function paquete(Paquete $paquete): View
    {
        abort_unless($paquete->activo, 404);

        $salonesDelPaquete = $paquete->aplicaATodosLosSalones()
            ? Salon::activos()->get()
            : $paquete->salones()->activos()->get();

        return view('portal.paquetes.show', compact('paquete', 'salonesDelPaquete'));
    }
}
