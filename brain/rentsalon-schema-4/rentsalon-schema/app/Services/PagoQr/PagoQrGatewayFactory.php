<?php

namespace App\Services\PagoQr;

use App\Contracts\PagoQrGatewayInterface;
use App\Models\Negocio;

class PagoQrGatewayFactory
{
    public function paraNegocio(Negocio $negocio): PagoQrGatewayInterface
    {
        $config = $negocio->configPago;

        // Mientras no haya credenciales reales configuradas y activas,
        // se usa el adaptador mock para no bloquear el desarrollo/demo.
        if (! $config || ! $config->activo) {
            return new MockQrGateway();
        }

        return match ($config->banco) {
            // 'union' => new BancoUnionQrGateway($config->credenciales_json_cifrado),
            // 'bnb'   => new BnbQrGateway($config->credenciales_json_cifrado),
            // 'bcp'   => new BcpQrGateway($config->credenciales_json_cifrado),
            // Se activan conforme lleguen las credenciales de sandbox de cada banco (ver Fase 5).
            default => new MockQrGateway(),
        };
    }
}
