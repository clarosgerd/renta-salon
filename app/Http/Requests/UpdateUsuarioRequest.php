<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUsuarioRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('usuario'));
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:150'],
            'email' => ['required', 'email', 'max:150', Rule::unique('users', 'email')->ignore($this->route('usuario'))],
            // Vacío = no cambia la contraseña actual.
            'password' => ['nullable', 'string', 'min:8'],
            'role' => ['required', 'in:admin_negocio,admin_salon,cajero'],
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
