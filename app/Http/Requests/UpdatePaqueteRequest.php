<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePaqueteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('paquete'));
    }

    public function rules(): array
    {
        return [
            'nombre' => ['required', 'string', 'max:150'],
            'tipo_evento' => ['required', 'in:boda,xv_anos,corporativo,otro'],
            'descripcion' => ['nullable', 'string', 'max:2000'],
            'precio_base' => ['required', 'numeric', 'min:0'],
            'duracion_horas' => ['required', 'integer', 'min:1'],
            'activo' => ['sometimes', 'boolean'],
            'salones' => ['nullable', 'array'],
            'salones.*' => [
                'integer',
                Rule::exists('salones', 'id')->where('negocio_id', app('negocio_actual')->id),
            ],
            'servicios_incluidos' => ['nullable', 'array'],
            'servicios_incluidos.*' => ['string', 'max:150'],
        ];
    }
}
