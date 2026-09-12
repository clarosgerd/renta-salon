<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Inventario;
use App\Models\Reservacion;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Dashboard real (11/09/2026) — ver §3.1 en
 * Historias_Usuario_Pantallas_RentSalon_Pro.md. Reemplaza el stub mínimo
 * de la activación multi-tenant (10/09/2026) que solo mostraba 3 conteos
 * genéricos. Mismo criterio de scoping por rol que Reservaciones/
 * Calendario (Admin Negocio/Super Admin ven todo el negocio, Admin
 * Salón/Cajero solo sus salones asignados vía salonesPermitidos()) —
 * NegocioScope ya filtra por negocio, esto filtra además por salón.
 *
 * Sin el gráfico de ingresos del mes que también menciona §3.1 — no
 * pedido en el mockup que se usó como referencia para esta pantalla.
 */
class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        $usuario = $request->user();
        $verTodosLosSalones = in_array($usuario->role, ['admin_negocio', 'super_admin_plataforma']);
        $salonIds = $verTodosLosSalones ? null : $usuario->salonesPermitidos();

        $reservaciones = fn () => Reservacion::query()
            ->when(! $verTodosLosSalones, fn ($q) => $q->whereIn('salon_id', $salonIds));

        $eventosEstaSemana = $reservaciones()
            ->whereBetween('fecha_evento', [now()->startOfWeek()->toDateString(), now()->endOfWeek()->toDateString()])
            ->where('estado', '!=', 'cancelada')
            ->count();

        $solicitudesPendientes = $reservaciones()->where('estado', 'pendiente')->count();

        // saldo_pendiente es un accessor (precio_total - abonado confirmado),
        // no una columna — se suma sobre la colección ya cargada en vez de
        // en SQL. El volumen de reservaciones activas de un negocio no
        // justifica una query agregada más compleja acá.
        $saldoPorCobrar = $reservaciones()
            ->whereIn('estado', ['pendiente', 'confirmada'])
            ->get()
            ->sum(fn (Reservacion $r) => $r->saldo_pendiente);

        // Inventario/Productos no está escopeado por salón (ver ProductoPolicy,
        // Fase 7 — es un catálogo de todo el negocio) — cuenta distinct por
        // producto, no por fila de inventario (un producto con 2 almacenes
        // bajos no debe contar doble).
        $productosStockBajo = Inventario::stockBajo()->distinct('producto_id')->count('producto_id');

        $proximosEventos = $reservaciones()
            ->with(['salon', 'paquete'])
            ->where('fecha_evento', '>=', now()->toDateString())
            ->where('estado', '!=', 'cancelada')
            ->orderBy('fecha_evento')
            ->orderBy('hora_inicio')
            ->limit(5)
            ->get();

        $solicitudesNuevas = $reservaciones()
            ->with('salon')
            ->where('estado', 'pendiente')
            ->orderBy('fecha_evento')
            ->limit(5)
            ->get();

        return view('admin.dashboard', compact(
            'eventosEstaSemana', 'solicitudesPendientes', 'saldoPorCobrar', 'productosStockBajo',
            'proximosEventos', 'solicitudesNuevas'
        ));
    }
}
