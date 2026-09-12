<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

/**
 * SA-4 (11/09/2026) — ver brain/Historias_Usuario_Pantallas_RentSalon_Pro.md.
 * Las credenciales nunca se re-muestran en texto plano tras guardar (§3.13):
 * solo se tocan si el Super Admin marca explícitamente "reemplazar_credenciales",
 * de lo contrario el valor cifrado existente queda intacto.
 */
class UpdateNegocioConfigPagoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('negocio'));
    }

    public function rules(): array
    {
        return [
            'banco' => ['nullable', 'in:union,bnb,bcp'],
            'cuenta_destino' => ['nullable', 'string', 'max:100'],
            'activo' => ['sometimes', 'boolean'],
            'reemplazar_credenciales' => ['sometimes', 'boolean'],
            'numero_comercio' => ['nullable', 'required_if:reemplazar_credenciales,1', 'string', 'max:100'],
            'api_key' => ['nullable', 'required_if:reemplazar_credenciales,1', 'string', 'max:255'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if (! $this->boolean('activo')) {
                return;
            }

            $config = $this->route('negocio')->configPago;
            $tieneBanco = $this->filled('banco') || $config?->banco;
            $tendraCredenciales = $this->boolean('reemplazar_credenciales') || ! empty($config?->credenciales_json_cifrado);

            if (! $tieneBanco || ! $tendraCredenciales) {
                $validator->errors()->add(
                    'activo',
                    'Selecciona un banco y guarda las credenciales antes de activar los pagos reales.'
                );
            }
        });
    }
}
