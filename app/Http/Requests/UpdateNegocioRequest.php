<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateNegocioRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('negocio'));
    }

    public function rules(): array
    {
        $negocioId = $this->route('negocio')?->id;

        return [
            'nombre_comercial' => ['required', 'string', 'max:150'],
            'subdominio' => [
                'required', 'string', 'max:63', 'regex:/^[a-z0-9-]+$/',
                Rule::unique('negocios', 'subdominio')->ignore($negocioId),
            ],
            'dominio_personalizado' => [
                'nullable', 'string', 'max:255',
                Rule::unique('negocios', 'dominio_personalizado')->ignore($negocioId),
            ],
            'logo_url' => ['nullable', 'url', 'max:255'],
            'color_primario' => ['nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'telefono_contacto' => ['nullable', 'string', 'max:30'],
            'email_contacto' => ['nullable', 'email', 'max:150'],
        ];
    }

    public function messages(): array
    {
        return [
            'subdominio.regex' => 'El subdominio solo puede tener minúsculas, números y guiones.',
            'color_primario.regex' => 'El color debe ser un código hexadecimal, ej. #1D9E75.',
        ];
    }
}
