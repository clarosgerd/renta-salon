<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * No usa BelongsToNegocio directo: hereda el aislamiento a través de
 * su relación con PagoAbono o VentaPos (que sí llevan negocio_id).
 */
class PagoQr extends Model
{
    protected $table = 'pagos_qr';

    protected $fillable = [
        'pago_abono_id', 'venta_pos_id', 'proveedor_qr', 'qr_id_externo',
        'monto', 'moneda', 'estado', 'fecha_generacion',
        'fecha_confirmacion', 'payload_respuesta',
    ];

    protected $casts = [
        'monto' => 'decimal:2',
        'fecha_generacion' => 'datetime',
        'fecha_confirmacion' => 'datetime',
        'payload_respuesta' => 'array',
    ];

    public function pagoAbono(): BelongsTo
    {
        return $this->belongsTo(PagoAbono::class);
    }

    public function ventaPos(): BelongsTo
    {
        return $this->belongsTo(VentaPos::class);
    }

    public function estaPagado(): bool
    {
        return $this->estado === 'pagado';
    }
}
