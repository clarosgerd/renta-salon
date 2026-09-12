<?php

namespace App\Http\Controllers\Admin;

use App\Exceptions\ReservacionConflictoException;
use App\Http\Controllers\Concerns\ScopesSalonesPermitidos;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreReservacionRequest;
use App\Http\Requests\UpdateReservacionRequest;
use App\Models\Paquete;
use App\Models\Reservacion;
use App\Models\Salon;
use App\Services\ReservacionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReservacionController extends Controller
{
    use ScopesSalonesPermitidos;

    public function __construct(private readonly ReservacionService $reservaciones)
    {
    }

    /**
     * GET /admin/reservaciones
     * Listado con filtros por estado, salón, rango de fecha y búsqueda de folio/cliente.
     */
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Reservacion::class);

        $usuario = $request->user();

        $salones = $this->salonesDelUsuario($request)->activos()->get();

        $reservaciones = Reservacion::query()
            ->with(['salon', 'paquete'])
            ->when(
                ! in_array($usuario->role, ['admin_negocio', 'super_admin_plataforma']),
                fn ($q) => $q->whereIn('salon_id', $usuario->salonesPermitidos())
            )
            ->when($request->filled('estado'), fn ($q) => $q->where('estado', $request->estado))
            ->when($request->filled('salon_id'), fn ($q) => $q->where('salon_id', $request->salon_id))
            ->when($request->filled('desde'), fn ($q) => $q->where('fecha_evento', '>=', $request->desde))
            ->when($request->filled('hasta'), fn ($q) => $q->where('fecha_evento', '<=', $request->hasta))
            ->when($request->filled('buscar'), function ($q) use ($request) {
                $q->where(function ($sub) use ($request) {
                    $sub->where('folio', 'like', "%{$request->buscar}%")
                        ->orWhere('cliente_nombre', 'like', "%{$request->buscar}%");
                });
            })
            ->orderByDesc('fecha_evento')
            ->paginate(20)
            ->withQueryString();

        return view('admin.reservaciones.index', compact('reservaciones', 'salones'));
    }

    /**
     * GET /admin/reservaciones/crear
     */
    public function create(Request $request): View
    {
        $this->authorize('create', Reservacion::class);

        $salones = $this->salonesDelUsuario($request)->activos()->get();
        $paquetes = Paquete::activos()->get();

        return view('admin.reservaciones.create', compact('salones', 'paquetes'));
    }

    /**
     * POST /admin/reservaciones
     */
    public function store(StoreReservacionRequest $request): RedirectResponse
    {
        // Reconfirma acceso al salón específico enviado (la Policy::create
        // solo valida el rol; aquí ya conocemos el salon_id real).
        $this->authorize('view', Salon::findOrFail($request->salon_id));

        try {
            $reservacion = $this->reservaciones->crear([
                ...$request->validated(),
                'origen' => 'manual',
                'estado' => 'confirmada',
                'creado_por' => $request->user()->id,
            ]);
        } catch (ReservacionConflictoException $e) {
            return back()
                ->withInput()
                ->withErrors(['fecha_evento' => $e->getMessage()]);
        }

        return redirect()
            ->route('admin.reservaciones.show', $reservacion)
            ->with('success', "Reservación {$reservacion->folio} creada correctamente.");
    }

    /**
     * GET /admin/reservaciones/{reservacion}
     */
    public function show(Reservacion $reservacion): View
    {
        $this->authorize('view', $reservacion);

        $reservacion->load([
            'salon', 'paquete',
            'pagosAbonos' => fn ($q) => $q->orderByDesc('fecha_pago'),
            'pagosAbonos.registradoPor',
            'pagosAbonos.pagoQr',
            'ventasPos.detalles.producto',
        ]);

        return view('admin.reservaciones.show', compact('reservacion'));
    }

    /**
     * GET /admin/reservaciones/{reservacion}/editar
     */
    public function edit(Request $request, Reservacion $reservacion): View
    {
        $this->authorize('update', $reservacion);

        $salones = $this->salonesDelUsuario($request)->activos()->get();
        $paquetes = Paquete::activos()->get();

        return view('admin.reservaciones.edit', compact('reservacion', 'salones', 'paquetes'));
    }

    /**
     * PUT/PATCH /admin/reservaciones/{reservacion}
     */
    public function update(UpdateReservacionRequest $request, Reservacion $reservacion): RedirectResponse
    {
        try {
            $this->reservaciones->actualizar($reservacion, $request->validated());
        } catch (ReservacionConflictoException $e) {
            return back()
                ->withInput()
                ->withErrors(['fecha_evento' => $e->getMessage()]);
        }

        return redirect()
            ->route('admin.reservaciones.show', $reservacion)
            ->with('success', 'Reservación actualizada correctamente.');
    }

    /**
     * PATCH /admin/reservaciones/{reservacion}/confirmar
     */
    public function confirmar(Reservacion $reservacion): RedirectResponse
    {
        $this->authorize('cambiarEstado', $reservacion);

        $this->reservaciones->confirmar($reservacion);

        return back()->with('success', 'Reservación confirmada.');
    }

    /**
     * PATCH /admin/reservaciones/{reservacion}/rechazar
     */
    public function rechazar(Reservacion $reservacion): RedirectResponse
    {
        $this->authorize('cambiarEstado', $reservacion);

        $this->reservaciones->rechazar($reservacion);

        return back()->with('success', 'Solicitud rechazada; la fecha quedó liberada.');
    }

    /**
     * PATCH /admin/reservaciones/{reservacion}/cancelar
     */
    public function cancelar(Reservacion $reservacion): RedirectResponse
    {
        $this->authorize('cambiarEstado', $reservacion);

        $this->reservaciones->cancelar($reservacion);

        return back()->with('success', 'Reservación cancelada.');
    }

    /**
     * PATCH /admin/reservaciones/{reservacion}/finalizar
     */
    public function finalizar(Reservacion $reservacion): RedirectResponse
    {
        $this->authorize('cambiarEstado', $reservacion);

        $this->reservaciones->finalizar($reservacion);

        return back()->with('success', 'Reservación marcada como finalizada.');
    }
}
