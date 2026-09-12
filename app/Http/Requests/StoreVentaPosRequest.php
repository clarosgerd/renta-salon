<?php

namespace App\Http\Requests;

use App\Models\VentaPos;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreVentaPosRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', VentaPos::class);
    }

    public function rules(): array
    {
        return [
            'items' => ['required', 'array', 'min:1'],
            'items.*.producto_id' => [
                'required', 'integer',
                Rule::exists('productos', 'id')->where('negocio_id', app('negocio_actual')->id),
            ],
            'items.*.cantidad' => ['required', 'integer', 'min:1'],
            'metodo_pago' => ['required', 'in:efectivo,qr'],
            // "Vincular a reservación" es opcional (venta de mostrador si
            // se deja vacío, ver §3.8) — no se acepta una cancelada.
            'reservacion_id' => [
                'nullable', 'integer',
                Rule::exists('reservaciones', 'id')
                    ->where('negocio_id', app('negocio_actual')->id)
                    ->where('estado', '!=', 'cancelada'),
            ],
        ];
    }
}
