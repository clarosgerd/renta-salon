<?php

namespace App\Http\Requests;

use App\Models\Paquete;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * Formulario de solicitud de reserva del portal público (Fase 6,
 * 11/09/2026) — ver CL-3/§3.14. Público, sin login: authorize() siempre
 * true, cualquier visitante del subdominio puede enviarlo (con throttle
 * en la ruta, ver routes/web.php).
 */
class SolicitarReservaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'salon_id' => [
                'required', 'integer',
                Rule::exists('salones', 'id')->where('negocio_id', app('negocio_actual')->id)->where('activo', true),
            ],
            'paquete_id' => [
                'required', 'integer',
                Rule::exists('paquetes', 'id')->where('negocio_id', app('negocio_actual')->id)->where('activo', true),
            ],
            'cliente_nombre' => ['required', 'string', 'max:150'],
            'cliente_telefono' => ['required', 'string', 'max:30'],
            'cliente_email' => ['nullable', 'email', 'max:150'],
            'num_invitados' => ['nullable', 'integer', 'min:1'],
            'fecha_evento' => ['required', 'date', 'after_or_equal:today'],
            'hora_inicio' => ['required', 'date_format:H:i'],
            'notas' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'fecha_evento.after_or_equal' => 'No se pueden solicitar fechas pasadas.',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if (! $this->filled('salon_id') || ! $this->filled('paquete_id')) {
                return;
            }

            $paquete = Paquete::find($this->input('paquete_id'));
            if (! $paquete) {
                return; // ya lo rechazó la regla exists de arriba
            }

            $aplica = $paquete->aplicaATodosLosSalones()
                || $paquete->salones()->where('salones.id', $this->input('salon_id'))->exists();

            if (! $aplica) {
                $validator->errors()->add('paquete_id', 'Este paquete no está disponible para el salón elegido.');
            }
        });
    }
}
