<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateReservacionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('reservacion'));
    }

    public function rules(): array
    {
        return [
            'salon_id' => ['sometimes', 'exists:salones,id'],
            'paquete_id' => ['nullable', 'exists:paquetes,id'],

            'cliente_nombre' => ['sometimes', 'string', 'max:150'],
            'cliente_telefono' => ['sometimes', 'string', 'max:30'],
            'cliente_email' => ['nullable', 'email', 'max:150'],
            'num_invitados' => ['nullable', 'integer', 'min:1'],

            'fecha_evento' => ['sometimes', 'date'],
            'hora_inicio' => ['sometimes', 'date_format:H:i'],
            'hora_fin' => ['sometimes', 'date_format:H:i', 'after:hora_inicio'],

            'precio_total' => ['sometimes', 'numeric', 'min:0'],
            'notas' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
