<?php

namespace App\Http\Requests;

use App\Models\Producto;
use Illuminate\Foundation\Http\FormRequest;

class StoreProductoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Producto::class);
    }

    public function rules(): array
    {
        return [
            'nombre' => ['required', 'string', 'max:150'],
            'categoria' => ['required', 'in:mobiliario,decoracion,bebidas,alimentos,otro'],
            'precio_venta' => ['required', 'numeric', 'min:0'],
            'costo' => ['nullable', 'numeric', 'min:0'],
            'unidad_medida' => ['nullable', 'string', 'max:30'],
            // Solo en el alta — crea la fila de Inventario general de una
            // vez, para no obligar un segundo paso (ver ProductoService::crear).
            'stock_inicial' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
