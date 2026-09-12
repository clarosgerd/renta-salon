<?php

namespace App\Http\Requests;

use App\Models\Paquete;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePaqueteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Paquete::class);
    }

    public function rules(): array
    {
        return [
            'nombre' => ['required', 'string', 'max:150'],
            'tipo_evento' => ['required', 'in:boda,xv_anos,corporativo,otro'],
            'descripcion' => ['nullable', 'string', 'max:2000'],
            'precio_base' => ['required', 'numeric', 'min:0'],
            'duracion_horas' => ['required', 'integer', 'min:1'],
            // Vacío/ausente = aplica a todos los salones del negocio,
            // ver Paquete::aplicaATodosLosSalones(). El `exists` va con
            // Rule::exists()->where(...) en vez del string "exists:salones,id"
            // a propósito: la validación `exists` corre contra la BD cruda,
            // sin pasar por NegocioScope — sin este where explícito, un
            // salon_id de OTRO negocio pasaría la validación igual.
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
