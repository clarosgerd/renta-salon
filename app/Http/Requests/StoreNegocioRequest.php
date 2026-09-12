<?php

namespace App\Http\Requests;

use App\Models\Negocio;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreNegocioRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Negocio::class);
    }

    public function rules(): array
    {
        return [
            'nombre_comercial' => ['required', 'string', 'max:150'],
            // Formato acotado (minúsculas/números/guiones) — IdentificarNegocio
            // hace explode('.', $host)[0] para resolver el subdominio; un
            // valor con espacios/mayúsculas/símbolos rompería esa
            // comparación contra el host real de la request.
            'subdominio' => [
                'required', 'string', 'max:63', 'regex:/^[a-z0-9-]+$/',
                Rule::unique('negocios', 'subdominio'),
            ],
            'dominio_personalizado' => [
                'nullable', 'string', 'max:255',
                Rule::unique('negocios', 'dominio_personalizado'),
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
