<?php

namespace App\Http\Controllers\Plataforma;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreNegocioRequest;
use App\Http\Requests\UpdateNegocioRequest;
use App\Models\Negocio;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * Panel Super Admin Plataforma (11/09/2026) — Fase 1, ver
 * brain/Plan_Implementacion_RentSalon_Pro_v2.md y SA-1/SA-2/SA-3 en
 * brain/Historias_Usuario_Pantallas_RentSalon_Pro.md. Vive bajo
 * Route::domain('admin.rentsalon-pro.test'), FUERA del middleware
 * 'negocio' (ver IdentificarNegocio) — acá no hay "negocio actual".
 *
 * Sin Service aparte: a diferencia de Salon/Paquete (galería de fotos,
 * sync de pivote), acá la lógica es CRUD simple + un toggle de estado,
 * no amerita una capa extra.
 */
class NegocioController extends Controller
{
    /**
     * GET /plataforma/negocios
     */
    public function index(): View
    {
        $this->authorize('viewAny', Negocio::class);

        // "reservaciones del mes" = creadas este mes (created_at), no la
        // fecha del evento — ver SA-3, criterio documentado acá porque el
        // enunciado de la historia no lo especifica.
        $negocios = Negocio::withCount([
            'salones',
            'reservaciones as reservaciones_mes_count' => fn ($q) => $q
                ->whereYear('created_at', now()->year)
                ->whereMonth('created_at', now()->month),
        ])
            ->orderBy('nombre_comercial')
            ->paginate(20);

        return view('plataforma.negocios.index', compact('negocios'));
    }

    /**
     * GET /plataforma/negocios/crear
     */
    public function create(): View
    {
        $this->authorize('create', Negocio::class);

        return view('plataforma.negocios.create');
    }

    /**
     * POST /plataforma/negocios
     */
    public function store(StoreNegocioRequest $request): RedirectResponse
    {
        // SA-1: nace activo y accesible de inmediato, sin pasos adicionales.
        $negocio = Negocio::create($request->validated() + ['estado' => 'activo']);

        return redirect()
            ->route('plataforma.negocios.index')
            ->with('success', "Negocio \"{$negocio->nombre_comercial}\" creado correctamente.");
    }

    /**
     * GET /plataforma/negocios/{negocio}/editar
     */
    public function edit(Negocio $negocio): View
    {
        $this->authorize('update', $negocio);

        return view('plataforma.negocios.edit', compact('negocio'));
    }

    /**
     * PUT /plataforma/negocios/{negocio}
     */
    public function update(UpdateNegocioRequest $request, Negocio $negocio): RedirectResponse
    {
        $negocio->update($request->validated());

        return redirect()
            ->route('plataforma.negocios.index')
            ->with('success', 'Negocio actualizado correctamente.');
    }

    /**
     * PATCH /plataforma/negocios/{negocio}/toggle-estado
     */
    public function toggleEstado(Negocio $negocio): RedirectResponse
    {
        $this->authorize('update', $negocio);

        $negocio->update(['estado' => $negocio->estado === 'activo' ? 'suspendido' : 'activo']);

        return back()->with(
            'success',
            $negocio->estado === 'activo' ? 'Negocio activado.' : 'Negocio suspendido.'
        );
    }
}
