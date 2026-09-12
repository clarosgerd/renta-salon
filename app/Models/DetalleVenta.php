<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * No usa BelongsToNegocio directo: hereda el aislamiento a través
 * de su relación con VentaPos (que sí lleva negocio_id).
 */
class DetalleVenta extends Model
{
    protected $table = 'detalle_venta';

    protected $fillable = [
        'venta_id', 'producto_id', 'cantidad', 'precio_unitario', 'subtotal',
    ];

    protected $casts = [
        'precio_unitario' => 'decimal:2',
        'subtotal' => 'decimal:2',
    ];

    protected static function booted(): void
    {
        static::creating(function (DetalleVenta $detalle) {
            $detalle->subtotal ??= $detalle->cantidad * $detalle->precio_unitario;
        });
    }

    public function venta(): BelongsTo
    {
        return $this->belongsTo(VentaPos::class, 'venta_id');
    }

    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class);
    }
}
