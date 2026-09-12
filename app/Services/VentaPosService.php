<?php

namespace App\Services;

use App\Models\Inventario;
use App\Models\MovimientoInventario;
use App\Models\PagoQr;
use App\Models\Producto;
use App\Models\User;
use App\Models\VentaPos;
use App\Services\PagoQr\PagoQrGatewayFactory;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Fase 7 (11/09/2026) — análogo de PagoAbonoService pero para ventas POS.
 * El QR reusa exactamente la misma infraestructura (PagoQrGatewayFactory/
 * MockQrGateway/tabla pagos_qr, con venta_pos_id en vez de pago_abono_id).
 */
class VentaPosService
{
    public function __construct(private readonly PagoQrGatewayFactory $gatewayFactory)
    {
    }

    /**
     * @param  array{items: array<array{producto_id:int,cantidad:int}>, metodo_pago:string, reservacion_id?:?int}  $datos
     */
    public function registrar(array $datos, User $cajero): VentaPos
    {
        return DB::transaction(function () use ($datos, $cajero) {
            $venta = VentaPos::create([
                'reservacion_id' => $datos['reservacion_id'] ?? null,
                'fecha' => now()->toDateString(),
                'total' => 0, // se recalcula abajo, tras armar el detalle
                'metodo_pago' => $datos['metodo_pago'],
                'estado' => 'pagada',
                'cajero_id' => $cajero->id,
            ]);

            foreach ($datos['items'] as $item) {
                // NegocioScope ya filtra — un producto_id de otro negocio
                // ni siquiera pasó la validación del FormRequest, pero esto
                // es la segunda capa (igual criterio que el resto del panel).
                $producto = Producto::findOrFail($item['producto_id']);

                $this->descontarStock($producto, (int) $item['cantidad'], $venta);

                $venta->detalles()->create([
                    'producto_id' => $producto->id,
                    'cantidad' => $item['cantidad'],
                    // El servidor decide el precio, nunca el cliente —
                    // mismo criterio que toda revalidación de precios del
                    // ecosistema (ver Producto::precio_venta vigente).
                    'precio_unitario' => $producto->precio_venta,
                ]);
            }

            $venta->recalcularTotal();

            if ($datos['metodo_pago'] === 'qr') {
                $this->generarQrParaVenta($venta);
            }

            return $venta->fresh(['detalles.producto', 'pagoQr']);
        });
    }

    /**
     * Descuenta SIEMPRE del inventario general (salon_id null) — el POS
     * (§3.8) no tiene selector de salón, solo "vincular a reservación"
     * opcional. Dentro de la misma transacción de registrar(): si el
     * stock no alcanza, la excepción revierte TODA la venta (nada de
     * detalle/movimiento a medias).
     */
    protected function descontarStock(Producto $producto, int $cantidad, VentaPos $venta): void
    {
        $inventario = Inventario::firstOrCreate(
            ['producto_id' => $producto->id, 'salon_id' => null],
            ['stock_actual' => 0, 'stock_minimo' => 0]
        );

        if ($inventario->stock_actual < $cantidad) {
            throw new InvalidArgumentException(
                "Stock insuficiente de \"{$producto->nombre}\" (disponible: {$inventario->stock_actual})."
            );
        }

        $inventario->decrement('stock_actual', $cantidad);
        $inventario->update(['ultima_actualizacion' => now()]);

        MovimientoInventario::create([
            'negocio_id' => $producto->negocio_id,
            'producto_id' => $producto->id,
            'tipo' => 'salida_venta',
            'cantidad' => -$cantidad,
            'referencia_venta_id' => $venta->id,
            'fecha' => now()->toDateString(),
            'usuario_id' => auth()->id(),
        ]);
    }

    protected function generarQrParaVenta(VentaPos $venta): void
    {
        $gateway = $this->gatewayFactory->paraNegocio($venta->negocio);

        $respuesta = $gateway->generarQr(
            monto: (float) $venta->total,
            referencia: "Venta {$venta->folio}",
        );

        PagoQr::create([
            'venta_pos_id' => $venta->id,
            'proveedor_qr' => $venta->negocio->configPago?->banco,
            'qr_id_externo' => $respuesta->qrIdExterno,
            'monto' => $respuesta->monto,
            'estado' => 'generado',
            'fecha_generacion' => now(),
            // payload_respuesta guarda también la imagen del QR (base64):
            // el gateway la devuelve en el momento pero no queda en
            // ninguna otra columna — sin esto, un F5 de la pantalla del
            // POS pierde el código para siempre (el cliente ya está
            // parado frente a la pantalla esperando escanearlo).
            'payload_respuesta' => $respuesta->payloadCrudo + ['imagen_base64' => $respuesta->imagenBase64],
        ]);
    }

    public function consultarEstadoQr(VentaPos $venta): string
    {
        $pagoQr = $venta->pagoQr;

        if (! $pagoQr) {
            throw new InvalidArgumentException('Esta venta no tiene un QR asociado.');
        }

        if ($pagoQr->estaPagado()) {
            return 'pagado';
        }

        $gateway = $this->gatewayFactory->paraNegocio($venta->negocio);
        $estado = $gateway->consultarEstado($pagoQr->qr_id_externo);

        $pagoQr->update([
            'estado' => $estado,
            'fecha_confirmacion' => $estado === 'pagado' ? now() : $pagoQr->fecha_confirmacion,
        ]);

        return $estado;
    }

    public function confirmarManualmente(VentaPos $venta): VentaPos
    {
        $venta->pagoQr?->update(['estado' => 'pagado', 'fecha_confirmacion' => now()]);

        return $venta->fresh();
    }
}
