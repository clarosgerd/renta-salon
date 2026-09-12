<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreMovimientoInventarioRequest;
use App\Models\Inventario;
use App\Models\MovimientoInventario;
use App\Models\Producto;
use App\Services\InventarioService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Inventario (Fase 7, 11/09/2026) — ver §3.10. Reusa ProductoPolicy
 * (viewAny/update sobre Producto::class) en vez de una Policy propia,
 * mismo patrón que NegocioConfigPagoController reusando NegocioPolicy.
 */
class InventarioController extends Controller
{
    public function __construct(private readonly InventarioService $inventario)
    {
    }

    /**
     * GET /admin/inventario — existencias actuales
     */
    public function index(): View
    {
        $this->authorize('viewAny', Producto::class);

        $inventarios = Inventario::with(['producto', 'salon'])
            ->whereHas('producto', fn ($q) => $q->activos())
            ->get()
            ->groupBy('producto_id');

        return view('admin.inventario.existencias', compact('inventarios'));
    }

    /**
     * GET /admin/inventario/movimientos
     */
    public function movimientos(Request $request): View
    {
        $this->authorize('viewAny', Producto::class);

        $productos = Producto::activos()->orderBy('nombre')->get();

        $movimientos = MovimientoInventario::with(['producto', 'usuario'])
            ->when($request->filled('producto_id'), fn ($q) => $q->where('producto_id', $request->producto_id))
            ->latest('fecha')->latest('id')
            ->paginate(30)
            ->withQueryString();

        return view('admin.inventario.movimientos', compact('movimientos', 'productos'));
    }

    /**
     * POST /admin/inventario/movimientos — registrar entrada manual (reabasto)
     */
    public function registrarEntrada(StoreMovimientoInventarioRequest $request): RedirectResponse
    {
        $this->inventario->registrarEntrada($request->validated() + ['usuario_id' => $request->user()->id]);

        return back()->with('success', 'Entrada registrada correctamente.');
    }
}
