<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSalonRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('salon'));
    }

    public function rules(): array
    {
        return [
            'nombre' => ['required', 'string', 'max:150'],
            'descripcion' => ['nullable', 'string', 'max:2000'],
            'capacidad_min' => ['nullable', 'integer', 'min:0'],
            'capacidad_max' => ['required', 'integer', 'min:1', 'gte:capacidad_min'],
            'ubicacion' => ['nullable', 'string', 'max:255'],
            'activo' => ['sometimes', 'boolean'],
            // Fotos nuevas a agregar a la galería existente (ver
            // SalonService::actualizar()) — mover/eliminar imágenes
            // puntuales va por sus propios endpoints, no por acá.
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
