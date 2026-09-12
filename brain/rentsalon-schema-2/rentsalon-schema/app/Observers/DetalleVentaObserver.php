<?php

namespace App\Observers;

use App\Models\DetalleVenta;
use App\Models\Inventario;
use App\Models\MovimientoInventario;
use Illuminate\Support\Facades\DB;

/**
 * Descuenta inventario automáticamente cada vez que se agrega
 * una línea a una venta del POS. Es la implementación concreta
 * del beneficio "el inventario se descuenta solo con cada venta".
 *
 * Registrar en AppServiceProvider::boot():
 *   DetalleVenta::observe(DetalleVentaObserver::class);
 */
class DetalleVentaObserver
{
    public function created(DetalleVenta $detalle): void
    {
        DB::transaction(function () use ($detalle) {
            $venta = $detalle->venta;

            $inventario = Inventario::query()
                ->where('producto_id', $detalle->producto_id)
                ->where('negocio_id', $venta->negocio_id)
                // Preferir stock del salón del evento si la venta está
                // vinculada a una reservación; si no, usa almacén general (salon_id null).
                ->when(
                    $venta->reservacion_id,
                    fn ($q) => $q->where('salon_id', $venta->reservacion->salon_id),
                    fn ($q) => $q->whereNull('salon_id')
                )
                ->lockForUpdate()
                ->first();

            if (! $inventario) {
                return; // producto sin registro de inventario configurado
            }

            $inventario->decrement('stock_actual', $detalle->cantidad);
            $inventario->update(['ultima_actualizacion' => now()]);

            MovimientoInventario::create([
                'negocio_id' => $venta->negocio_id,
                'producto_id' => $detalle->producto_id,
                'tipo' => 'salida_venta',
                'cantidad' => -$detalle->cantidad,
                'referencia_venta_id' => $venta->id,
                'fecha' => $venta->fecha,
                'usuario_id' => $venta->cajero_id,
            ]);
        });
    }
}
