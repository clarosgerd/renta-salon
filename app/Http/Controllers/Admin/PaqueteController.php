<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePaqueteRequest;
use App\Http\Requests\UpdatePaqueteRequest;
use App\Models\Paquete;
use App\Models\Salon;
use App\Services\PaqueteService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PaqueteController extends Controller
{
    public function __construct(private readonly PaqueteService $paquetes)
    {
    }

    /**
     * GET /admin/paquetes
     */
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Paquete::class);

        $paquetes = Paquete::query()
            ->with('salones')
            ->when($request->filled('buscar'), fn ($q) => $q->where('nombre', 'like', "%{$request->buscar}%"))
            ->orderBy('nombre')
            ->paginate(20)
            ->withQueryString();

        return view('admin.paquetes.index', compact('paquetes'));
    }

    /**
     * GET /admin/paquetes/crear
     */
    public function create(): View
    {
        $this->authorize('create', Paquete::class);

        $salones = Salon::activos()->orderBy('nombre')->get();

        return view('admin.paquetes.create', compact('salones'));
    }

    /**
     * POST /admin/paquetes
     */
    public function store(StorePaqueteRequest $request): RedirectResponse
    {
        $paquete = $this->paquetes->crear($request->validated());

        return redirect()
            ->route('admin.paquetes.index')
            ->with('success', "Paquete \"{$paquete->nombre}\" creado correctamente.");
    }

    /**
     * GET /admin/paquetes/{paquete}/editar
     */
    public function edit(Paquete $paquete): View
    {
        $this->authorize('update', $paquete);

        $salones = Salon::activos()->orderBy('nombre')->get();
        $paquete->load('salones');

        return view('admin.paquetes.edit', compact('paquete', 'salones'));
    }

    /**
     * PUT /admin/paquetes/{paquete}
     */
    public function update(UpdatePaqueteRequest $request, Paquete $paquete): RedirectResponse
    {
        $datos = $request->validated();
        // Mismo criterio que SalonController::update() — un checkbox sin
        // marcar no llega en el request, validated() simplemente omite la
        // clave en vez de traer `false`.
        $datos['activo'] = $request->boolean('activo');

        $this->paquetes->actualizar($paquete, $datos);

        return redirect()
            ->route('admin.paquetes.index')
            ->with('success', 'Paquete actualizado correctamente.');
    }

    /**
     * PATCH /admin/paquetes/{paquete}/toggle-activo
     */
    public function toggleActivo(Paquete $paquete): RedirectResponse
    {
        $this->authorize('update', $paquete);

        $this->paquetes->toggleActivo($paquete);

        return back()->with('success', $paquete->activo ? 'Paquete activado.' : 'Paquete desactivado.');
    }
}
