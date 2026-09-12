<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\ScopesSalonesPermitidos;
use App\Http\Controllers\Controller;
use App\Models\Reservacion;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

/**
 * Calendario maestro (11/09/2026, Fase 3) — ver AN-3 y §3.2 en
 * brain/Historias_Usuario_Pantallas_RentSalon_Pro.md. Vista nueva sobre
 * datos que ya existen (Reservacion ya tiene estado/salon/paquete/saldo) —
 * no toca el modelo. Reusa ReservacionPolicy::viewAny (mismo permiso que
 * el listado de reservaciones) y el scoping de salones ya usado ahí.
 */
class CalendarioController extends Controller
{
    use ScopesSalonesPermitidos;

    /**
     * GET /admin/calendario
     */
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Reservacion::class);

        $salones = $this->salonesDelUsuario($request)->activos()->get();

        return view('admin.calendario.index', compact('salones'));
    }

    /**
     * GET /admin/calendario/eventos
     * Fuente de eventos de FullCalendar — start/end los manda FullCalendar
     * solo (rango visible, incluye días de relleno del mes).
     */
    public function eventos(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Reservacion::class);

        $usuario = $request->user();
        // Carbon::parse en vez de $request->date(): FullCalendar manda
        // start/end con offset de timezone, un formato exacto podría fallar.
        $desde = Carbon::parse($request->query('start'))->toDateString();
        $hasta = Carbon::parse($request->query('end'))->toDateString();

        $reservaciones = Reservacion::query()
            ->with(['salon', 'paquete'])
            ->when(
                ! in_array($usuario->role, ['admin_negocio', 'super_admin_plataforma']),
                fn ($q) => $q->whereIn('salon_id', $usuario->salonesPermitidos())
            )
            ->when(
                $request->filled('salon_id'),
                fn ($q) => $q->whereIn('salon_id', (array) $request->input('salon_id'))
            )
            ->whereBetween('fecha_evento', [$desde, $hasta])
            ->get();

        return response()->json($reservaciones->map(fn (Reservacion $r) => [
            'id' => $r->id,
            'title' => "{$r->cliente_nombre} — {$r->salon->nombre}",
            'start' => $r->fecha_evento->format('Y-m-d')."T{$r->hora_inicio}",
            'end' => $r->fecha_evento->format('Y-m-d')."T{$r->hora_fin}",
            // Amarillo/verde/gris según la spec (§3.2); 'finalizada' no
            // tiene color definido ahí — se agrega azul, documentado acá.
            'color' => match ($r->estado) {
                'pendiente' => '#f59e0b',
                'confirmada' => '#10b981',
                'cancelada' => '#9ca3af',
                'finalizada' => '#3b82f6',
                default => '#6b7280',
            },
            'extendedProps' => [
                'folio' => $r->folio,
                'cliente' => $r->cliente_nombre,
                'salon' => $r->salon->nombre,
                'paquete' => $r->paquete?->nombre,
                'saldo' => $r->saldo_pendiente,
                'estado' => $r->estado,
                'showUrl' => route('admin.reservaciones.show', $r),
            ],
        ]));
    }
}
