<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePagoAbonoRequest extends FormRequest
{
    public function authorize(): bool
    {
        // La reservación se resuelve por route model binding; reusamos
        // la misma regla de acceso por salón que el resto del módulo.
        return $this->user()->can('cambiarEstado', $this->route('reservacion'));
    }

    public function rules(): array
    {
        return [
            'monto' => ['required', 'numeric', 'min:0.01'],
            'metodo_pago' => ['required', 'in:efectivo,qr'],
            'notas' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'monto.min' => 'El monto debe ser mayor a cero.',
        ];
    }
}
