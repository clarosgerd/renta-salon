<?php

namespace App\Services;

use App\Exceptions\ReservacionConflictoException;
use App\Models\PagoAbono;
use App\Models\PagoQr;
use App\Models\Reservacion;
use App\Services\PagoQr\PagoQrGatewayFactory;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class PagoAbonoService
{
    public function __construct(private readonly PagoQrGatewayFactory $gatewayFactory)
    {
    }

    /**
     * Registra un abono. Si es efectivo, queda confirmado de inmediato.
     * Si es QR, genera el código con el banco configurado del negocio
     * y lo deja en estado "pendiente" hasta que se confirme el pago.
     */
    public function registrar(Reservacion $reservacion, array $datos): PagoAbono
    {
        $this->validarMontoNoExcedeSaldo($reservacion, (float) $datos['monto']);

        return DB::transaction(function () use ($reservacion, $datos) {
            $esQr = $datos['metodo_pago'] === 'qr';

            $abono = PagoAbono::create([
                'negocio_id' => $reservacion->negocio_id,
                'reservacion_id' => $reservacion->id,
                'monto' => $datos['monto'],
                'fecha_pago' => now()->toDateString(),
                'metodo_pago' => $datos['metodo_pago'],
                'estado_pago' => $esQr ? 'pendiente' : 'confirmado',
                'registrado_por' => $datos['registrado_por'] ?? null,
                'notas' => $datos['notas'] ?? null,
            ]);

            if ($esQr) {
                $this->generarQrParaAbono($abono);
            }

            return $abono->fresh('pagoQr');
        });
    }

    protected function generarQrParaAbono(PagoAbono $abono): void
    {
        $gateway = $this->gatewayFactory->paraNegocio($abono->reservacion->negocio);

        $respuesta = $gateway->generarQr(
            monto: (float) $abono->monto,
            referencia: "Abono {$abono->reservacion->folio}",
        );

        PagoQr::create([
            'pago_abono_id' => $abono->id,
            'proveedor_qr' => $abono->reservacion->negocio->configPago?->banco,
            'qr_id_externo' => $respuesta->qrIdExterno,
            'monto' => $respuesta->monto,
            'estado' => 'generado',
            'fecha_generacion' => now(),
            'payload_respuesta' => $respuesta->payloadCrudo,
        ]);
    }

    /**
     * Se llama desde el endpoint de polling que consulta el modal de abono
     * cada pocos segundos mientras el QR está en pantalla.
     */
    public function consultarEstadoQr(PagoAbono $abono): string
    {
        $pagoQr = $abono->pagoQr;

        if (! $pagoQr) {
            throw new InvalidArgumentException('Este abono no tiene un QR asociado.');
        }

        if ($pagoQr->estaPagado()) {
            return 'pagado';
        }

        $gateway = $this->gatewayFactory->paraNegocio($abono->reservacion->negocio);
        $estado = $gateway->consultarEstado($pagoQr->qr_id_externo);

        $pagoQr->update([
            'estado' => $estado,
            'fecha_confirmacion' => $estado === 'pagado' ? now() : $pagoQr->fecha_confirmacion,
        ]);

        if ($estado === 'pagado') {
            $abono->update(['estado_pago' => 'confirmado']);
        }

        return $estado;
    }

    /**
     * Respaldo manual: el cajero confirma que vio el depósito en su banco,
     * por si la consulta automática falla o el banco no notifica a tiempo.
     */
    public function confirmarManualmente(PagoAbono $abono, ?string $comprobanteUrl = null): PagoAbono
    {
        $abono->update([
            'estado_pago' => 'confirmado',
            'comprobante_url' => $comprobanteUrl ?? $abono->comprobante_url,
        ]);

        $abono->pagoQr?->update(['estado' => 'pagado', 'fecha_confirmacion' => now()]);

        return $abono->fresh();
    }

    protected function validarMontoNoExcedeSaldo(Reservacion $reservacion, float $monto): void
    {
        if ($monto <= 0) {
            throw new InvalidArgumentException('El monto debe ser mayor a cero.');
        }

        if ($monto > $reservacion->saldo_pendiente) {
            throw new InvalidArgumentException('El monto no puede ser mayor al saldo pendiente.');
        }
    }
}
