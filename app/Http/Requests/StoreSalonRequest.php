<?php

namespace App\Http\Requests;

use App\Models\Salon;
use Illuminate\Foundation\Http\FormRequest;

class StoreSalonRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Salon::class);
    }

    public function rules(): array
    {
        return [
            'nombre' => ['required', 'string', 'max:150'],
            'descripcion' => ['nullable', 'string', 'max:2000'],
            'capacidad_min' => ['nullable', 'integer', 'min:0'],
            'capacidad_max' => ['required', 'integer', 'min:1', 'gte:capacidad_min'],
            'ubicacion' => ['nullable', 'string', 'max:255'],
            'imagenes' => ['nullable', 'array', 'max:10'],
            'imagenes.*' => ['image', 'max:4096'],
        ];
    }

    public function messages(): array
    {
        return [
            'capacidad_max.gte' => 'La capacidad máxima debe ser mayor o igual a la mínima.',
        ];
    }
}
