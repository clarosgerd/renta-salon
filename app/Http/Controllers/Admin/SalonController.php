<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSalonRequest;
use App\Http\Requests\UpdateSalonRequest;
use App\Models\Imagen;
use App\Models\Salon;
use App\Services\SalonService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SalonController extends Controller
{
    public function __construct(private readonly SalonService $salones)
    {
    }

    /**
     * GET /admin/salones
     */
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Salon::class);

        $salones = Salon::query()
            ->with(['imagenes' => fn ($q) => $q->orderBy('orden')->limit(1)])
            ->when($request->filled('buscar'), fn ($q) => $q->where('nombre', 'like', "%{$request->buscar}%"))
            ->orderBy('nombre')
            ->paginate(20)
            ->withQueryString();

        return view('admin.salones.index', compact('salones'));
    }

    /**
     * GET /admin/salones/crear
     */
    public function create(): View
    {
        $this->authorize('create', Salon::class);

        return view('admin.salones.create');
    }

    /**
     * POST /admin/salones
     */
    public function store(StoreSalonRequest $request): RedirectResponse
    {
        $datos = $request->safe()->except('imagenes');

        $salon = $this->salones->crear($datos, $request->file('imagenes', []));

        return redirect()
            ->route('admin.salones.index')
            ->with('success', "Salón \"{$salon->nombre}\" creado correctamente.");
    }

    /**
     * GET /admin/salones/{salon}/editar
     */
    public function edit(Salon $salon): View
    {
        $this->authorize('update', $salon);

        $salon->load(['imagenes' => fn ($q) => $q->orderBy('orden')]);

        return view('admin.salones.edit', compact('salon'));
    }

    /**
     * PUT /admin/salones/{salon}
     */
    public function update(UpdateSalonRequest $request, Salon $salon): RedirectResponse
    {
        $datos = $request->safe()->except(['imagenes', 'activo']);
        // Checkbox HTML: si no viene marcado, el campo ni siquiera llega en
        // el request — boolean() resuelve eso a false de forma segura, en
        // vez de que validated() simplemente omita la clave.
        $datos['activo'] = $request->boolean('activo');

        $this->salones->actualizar($salon, $datos, $request->file('imagenes', []));

        return redirect()
            ->route('admin.salones.index')
            ->with('success', 'Salón actualizado correctamente.');
    }

    /**
     * PATCH /admin/salones/{salon}/toggle-activo
     */
    public function toggleActivo(Salon $salon): RedirectResponse
    {
        $this->authorize('update', $salon);

        $this->salones->toggleActivo($salon);

        return back()->with('success', $salon->activo ? 'Salón activado.' : 'Salón desactivado.');
    }

    /**
     * PATCH /admin/salones/{salon}/imagenes/{imagen}/mover
     */
    public function moverImagen(Request $request, Salon $salon, Imagen $imagen): RedirectResponse
    {
        $this->authorize('update', $salon);
        abort_unless($imagen->imageable_type === Salon::class && $imagen->imageable_id === $salon->id, 404);

        $request->validate(['direccion' => ['required', 'in:arriba,abajo']]);

        $this->salones->moverImagen($imagen, $request->input('direccion'));

        return back()->with('success', 'Orden de la galería actualizado.');
    }

    /**
     * DELETE /admin/salones/{salon}/imagenes/{imagen}
     */
    public function eliminarImagen(Salon $salon, Imagen $imagen): RedirectResponse
    {
        $this->authorize('update', $salon);
        abort_unless($imagen->imageable_type === Salon::class && $imagen->imageable_id === $salon->id, 404);

        $this->salones->eliminarImagen($imagen);

        return back()->with('success', 'Foto eliminada.');
    }
}
