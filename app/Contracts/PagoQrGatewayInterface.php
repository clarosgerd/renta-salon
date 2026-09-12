<?php

namespace App\Contracts;

use App\DataTransferObjects\QrResponse;

/**
 * Contrato común para los adaptadores de cobro QR de cada banco
 * (Banco Unión, BNB, BCP). Ver plan de arquitectura, módulo de
 * Pagos QR, para el detalle del flujo completo.
 */
interface PagoQrGatewayInterface
{
    public function generarQr(float $monto, string $referencia): QrResponse;

    /**
     * @return string 'generado' | 'pagado' | 'expirado' | 'fallido'
     */
    public function consultarEstado(string $qrIdExterno): string;
}
