<?php

namespace App\Services\PagoQr;

use App\Contracts\PagoQrGatewayInterface;
use App\DataTransferObjects\QrResponse;
use Illuminate\Support\Str;

/**
 * Adaptador "de mentira" para poder construir y probar TODO el flujo
 * de pagos QR (UI, polling, confirmación) sin depender todavía de las
 * credenciales reales de Banco Unión / BNB / BCP.
 *
 * Simula que el QR se "paga solo" pasados unos segundos de haberse
 * generado, para poder ver en pantalla el cambio de estado sin
 * intervención manual. Cuando lleguen las credenciales reales de cada
 * banco, se crean BancoUnionQrGateway / BnbQrGateway / BcpQrGateway
 * implementando esta misma interfaz, y se reemplaza el binding en el
 * PagoQrGatewayFactory — el resto del sistema no cambia.
 */
class MockQrGateway implements PagoQrGatewayInterface
{
    public function generarQr(float $monto, string $referencia): QrResponse
    {
        // Imagen 1x1 transparente en base64 solo como placeholder visual.
        $imagenPlaceholder = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=';

        return new QrResponse(
            qrIdExterno: 'MOCK-' . strtoupper(Str::random(10)),
            imagenBase64: $imagenPlaceholder,
            monto: $monto,
            expiraEn: now()->addMinutes(15),
            payloadCrudo: ['referencia' => $referencia, 'simulado' => true],
        );
    }

    public function consultarEstado(string $qrIdExterno): string
    {
        // Simulación: se considera "pagado" 20 segundos después de generado.
        // En un adaptador real, aquí se llama a la API del banco.
        $pagoQr = \App\Models\PagoQr::where('qr_id_externo', $qrIdExterno)->first();

        if (! $pagoQr) {
            return 'fallido';
        }

        if ($pagoQr->fecha_generacion->diffInSeconds(now()) >= 20) {
            return 'pagado';
        }

        return 'generado';
    }
}
