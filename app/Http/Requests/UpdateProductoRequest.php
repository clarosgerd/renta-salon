<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProductoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('producto'));
    }

    public function rules(): array
    {
        return [
            'nombre' => ['required', 'string', 'max:150'],
            'categoria' => ['required', 'in:mobiliario,decoracion,bebidas,alimentos,otro'],
            'precio_venta' => ['required', 'numeric', 'min:0'],
            'costo' => ['nullable', 'numeric', 'min:0'],
            'unidad_medida' => ['nullable', 'string', 'max:30'],
        ];
    }
}
