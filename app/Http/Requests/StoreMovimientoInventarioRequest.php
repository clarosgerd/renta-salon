<?php

namespace App\Http\Requests;

use App\Models\Producto;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreMovimientoInventarioRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', Producto::class);
    }

    public function rules(): array
    {
        return [
            // Rule::exists()->where(...) en vez del string "exists:productos,id"
            // a propósito: exists corre contra la BD cruda sin pasar por
            // NegocioScope — sin este where, un producto_id de OTRO negocio
            // pasaría la validación igual (mismo criterio ya usado en
            // StorePaqueteRequest para salones).
            'producto_id' => [
                'required', 'integer',
                Rule::exists('productos', 'id')->where('negocio_id', app('negocio_actual')->id),
            ],
            'salon_id' => [
                'nullable', 'integer',
                Rule::exists('salones', 'id')->where('negocio_id', app('negocio_actual')->id),
            ],
            'cantidad' => ['required', 'integer', 'min:1'],
            'notas' => ['nullable', 'string', 'max:500'],
        ];
    }
}
