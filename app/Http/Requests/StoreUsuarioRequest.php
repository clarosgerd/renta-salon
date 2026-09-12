<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreUsuarioRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', User::class);
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:150'],
            'email' => ['required', 'email', 'max:150', Rule::unique('users', 'email')],
            'password' => ['required', 'string', 'min:8'],
            // Super Admin Plataforma no se crea desde acá (se crea por
            // seeder/tinker, ver Fase 1).
            'role' => ['required', 'in:admin_negocio,admin_salon,cajero'],
            // Un Admin Salón/Cajero con CERO salones asignados queda sin
            // poder hacer nada útil (AN-6: "solo ve/edita los salones que
            // le asigné") — se exige al menos uno para esos 2 roles.
            'salones' => ['required_if:role,admin_salon,cajero', 'array'],
            'salones.*' => [
                'integer',
                Rule::exists('salones', 'id')->where('negocio_id', app('negocio_actual')->id),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'salones.required_if' => 'Asigná al menos un salón para este rol.',
        ];
    }
}
