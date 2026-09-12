<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProductoRequest;
use App\Http\Requests\UpdateProductoRequest;
use App\Models\Producto;
use App\Services\ProductoService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Productos (Fase 7, 11/09/2026) — ver AN-7/§3.10. Mismo esqueleto que
 * SalonController. Sin destroy: "desactivar" cubre la baja, mismo criterio
 * "no borrar historial" que el resto del panel.
 */
class ProductoController extends Controller
{
    public function __construct(private readonly ProductoService $productos)
    {
    }

    /**
     * GET /admin/productos
     */
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Producto::class);

        $productos = Producto::query()
            // Alias distinto al accessor getStockTotalAttribute() a
            // propósito: si se llamara igual ("stock_total"), Eloquent
            // prioriza el accessor sobre el valor cargado por withSum,
            // disparando una query extra por fila (N+1) en vez de
            // reusar este agregado ya calculado en un solo query.
            ->withSum('inventarios as stock_actual_total', 'stock_actual')
            ->when($request->filled('buscar'), fn ($q) => $q->where('nombre', 'like', "%{$request->buscar}%"))
            ->when($request->filled('categoria'), fn ($q) => $q->where('categoria', $request->categoria))
            ->orderBy('nombre')
            ->paginate(20)
            ->withQueryString();

        return view('admin.productos.index', compact('productos'));
    }

    /**
     * GET /admin/productos/crear
     */
    public function create(): View
    {
        $this->authorize('create', Producto::class);

        return view('admin.productos.create');
    }

    /**
     * POST /admin/productos
     */
    public function store(StoreProductoRequest $request): RedirectResponse
    {
        $producto = $this->productos->crear($request->validated());

        return redirect()
            ->route('admin.productos.index')
            ->with('success', "Producto \"{$producto->nombre}\" creado correctamente.");
    }

    /**
     * GET /admin/productos/{producto}/editar
     */
    public function edit(Producto $producto): View
    {
        $this->authorize('update', $producto);

        return view('admin.productos.edit', compact('producto'));
    }

    /**
     * PUT /admin/productos/{producto}
     */
    public function update(UpdateProductoRequest $request, Producto $producto): RedirectResponse
    {
        $this->productos->actualizar($producto, $request->validated());

        return redirect()
            ->route('admin.productos.index')
            ->with('success', 'Producto actualizado correctamente.');
    }

    /**
     * PATCH /admin/productos/{producto}/toggle-activo
     */
    public function toggleActivo(Producto $producto): RedirectResponse
    {
        $this->authorize('update', $producto);

        $this->productos->toggleActivo($producto);

        return back()->with('success', $producto->activo ? 'Producto activado.' : 'Producto desactivado.');
    }
}
