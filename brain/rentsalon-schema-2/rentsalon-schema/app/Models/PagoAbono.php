<?php

namespace App\Models;

use App\Traits\BelongsToNegocio;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class PagoAbono extends Model
{
    use BelongsToNegocio;

    protected $fillable = [
        'negocio_id', 'reservacion_id', 'monto', 'fecha_pago',
        'metodo_pago', 'estado_pago', 'referencia_transaccion',
        'comprobante_url', 'registrado_por', 'notas',
    ];

    protected $casts = [
        'fecha_pago' => 'date',
        'monto' => 'decimal:2',
    ];

    public function reservacion(): BelongsTo
    {
        return $this->belongsTo(Reservacion::class);
    }

    public function registradoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'registrado_por');
    }

    public function pagoQr(): HasOne
    {
        return $this->hasOne(PagoQr::class);
    }

    public function esQr(): bool
    {
        return $this->metodo_pago === 'qr';
    }
}
