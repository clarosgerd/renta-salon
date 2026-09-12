<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use InvalidArgumentException;

/**
 * Reglas de negocio de Usuarios (Fase 8, 11/09/2026) — ver AN-6. A
 * diferencia de Salon/Paquete/Producto, User NO usa el trait
 * BelongsToNegocio (a propósito: super_admin_plataforma tiene
 * negocio_id null) — acá el negocio_id se asigna a mano.
 */
class UsuarioService
{
    public function crear(array $datos): User
    {
        return DB::transaction(function () use ($datos) {
            $usuario = User::create([
                'negocio_id' => app('negocio_actual')->id,
                'name' => $datos['name'],
                'email' => $datos['email'],
                'password' => Hash::make($datos['password']),
                'role' => $datos['role'],
                'activo' => true,
                // Sin esto, una cuenta de staff nueva queda trabada en la
                // pantalla de verificación de correo (/dashboard exige
                // ['auth','verified']) — no hay infraestructura de envío
                // de emails en este proyecto todavía.
                'email_verified_at' => now(),
            ]);

            $this->sincronizarSalones($usuario, $datos);

            return $usuario;
        });
    }

    public function actualizar(User $usuario, array $datos): User
    {
        return DB::transaction(function () use ($usuario, $datos) {
            $usuario->update([
                'name' => $datos['name'],
                'email' => $datos['email'],
                'role' => $datos['role'],
                ...(filled($datos['password'] ?? null) ? ['password' => Hash::make($datos['password'])] : []),
            ]);

            $this->sincronizarSalones($usuario, $datos);

            return $usuario->fresh();
        });
    }

    protected function sincronizarSalones(User $usuario, array $datos): void
    {
        // admin_negocio ve TODOS los salones vía User::salonesPermitidos()
        // sin pivote — se limpia cualquier asignación vieja para no dejar
        // filas vestigiales si el rol cambió desde admin_salon/cajero.
        $usuario->salones()->sync($usuario->role === 'admin_negocio' ? [] : ($datos['salones'] ?? []));
    }

    public function toggleActivo(User $usuario, User $actor): User
    {
        if ($usuario->id === $actor->id) {
            throw new InvalidArgumentException('No podés desactivar tu propia cuenta.');
        }

        $usuario->update(['activo' => ! $usuario->activo]);

        return $usuario;
    }
}
