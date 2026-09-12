<?php

namespace App\Http\Requests;

use App\Models\Reservacion;
use Illuminate\Foundation\Http\FormRequest;

class StoreReservacionRequest extends FormRequest
{
    public function authorize(): bool
    {
        // La Policy de Reservacion valida que el usuario tenga acceso
        // al salon_id enviado (ver ReservacionPolicy::create).
        return $this->user()->can('create', Reservacion::class);
    }

    public function rules(): array
    {
        return [
            'salon_id' => ['required', 'exists:salones,id'],
            'paquete_id' => ['nullable', 'exists:paquetes,id'],

            'cliente_nombre' => ['required', 'string', 'max:150'],
            'cliente_telefono' => ['required', 'string', 'max:30'],
            'cliente_email' => ['nullable', 'email', 'max:150'],
            'num_invitados' => ['nullable', 'integer', 'min:1'],

            'fecha_evento' => ['required', 'date', 'after_or_equal:today'],
            'hora_inicio' => ['required', 'date_format:H:i'],
            'hora_fin' => ['required', 'date_format:H:i', 'after:hora_inicio'],

            'precio_total' => ['required', 'numeric', 'min:0'],
            'notas' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'hora_fin.after' => 'La hora de fin debe ser posterior a la hora de inicio.',
            'fecha_evento.after_or_equal' => 'No se pueden agendar eventos en fechas pasadas.',
        ];
    }
}
