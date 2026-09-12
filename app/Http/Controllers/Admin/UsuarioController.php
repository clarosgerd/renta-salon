<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreUsuarioRequest;
use App\Http\Requests\UpdateUsuarioRequest;
use App\Models\Salon;
use App\Models\User;
use App\Services\UsuarioService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use InvalidArgumentException;

/**
 * Usuarios (Fase 8, 11/09/2026) — ver AN-6/§3.11. Mismo esqueleto que
 * SalonController. User NO tiene NegocioScope (ver UsuarioService) — cada
 * query acá filtra negocio_id a mano, a propósito.
 */
class UsuarioController extends Controller
{
    public function __construct(private readonly UsuarioService $usuarios)
    {
    }

    /**
     * GET /admin/usuarios
     */
    public function index(): View
    {
        $this->authorize('viewAny', User::class);

        $usuarios = User::where('negocio_id', app('negocio_actual')->id)
            ->with('salones')
            ->orderBy('name')
            ->paginate(20);

        return view('admin.usuarios.index', compact('usuarios'));
    }

    /**
     * GET /admin/usuarios/crear
     */
    public function create(): View
    {
        $this->authorize('create', User::class);

        $salones = Salon::activos()->orderBy('nombre')->get();

        return view('admin.usuarios.create', compact('salones'));
    }

    /**
     * POST /admin/usuarios
     */
    public function store(StoreUsuarioRequest $request): RedirectResponse
    {
        $usuario = $this->usuarios->crear($request->validated());

        return redirect()
            ->route('admin.usuarios.index')
            ->with('success', "Usuario \"{$usuario->name}\" creado correctamente.");
    }

    /**
     * GET /admin/usuarios/{usuario}/editar
     */
    public function edit(User $usuario): View
    {
        $this->authorize('update', $usuario);
        abort_unless($usuario->negocio_id === app('negocio_actual')->id, 404);

        $usuario->load('salones');
        $salones = Salon::activos()->orderBy('nombre')->get();

        return view('admin.usuarios.edit', compact('usuario', 'salones'));
    }

    /**
     * PUT /admin/usuarios/{usuario}
     */
    public function update(UpdateUsuarioRequest $request, User $usuario): RedirectResponse
    {
        abort_unless($usuario->negocio_id === app('negocio_actual')->id, 404);

        $this->usuarios->actualizar($usuario, $request->validated());

        return redirect()
            ->route('admin.usuarios.index')
            ->with('success', 'Usuario actualizado correctamente.');
    }

    /**
     * PATCH /admin/usuarios/{usuario}/toggle-activo
     */
    public function toggleActivo(Request $request, User $usuario): RedirectResponse
    {
        $this->authorize('update', $usuario);
        abort_unless($usuario->negocio_id === app('negocio_actual')->id, 404);

        try {
            $this->usuarios->toggleActivo($usuario, $request->user());
        } catch (InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', $usuario->activo ? 'Usuario activado.' : 'Usuario desactivado.');
    }
}
