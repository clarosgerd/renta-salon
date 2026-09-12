<?php

namespace App\Models;

use App\Traits\BelongsToNegocio;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

class VentaPos extends Model
{
    use BelongsToNegocio;

    protected $table = 'ventas_pos';

    protected $fillable = [
        'negocio_id', 'reservacion_id', 'folio', 'fecha',
        'total', 'metodo_pago', 'estado', 'cajero_id',
    ];

    protected $casts = [
        'fecha' => 'date',
        'total' => 'decimal:2',
    ];

    protected static function booted(): void
    {
        static::creating(function (VentaPos $venta) {
            $venta->folio ??= 'POS-' . strtoupper(Str::random(6));
        });
    }

    public function reservacion(): BelongsTo
    {
        return $this->belongsTo(Reservacion::class);
    }

    public function cajero(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cajero_id');
    }

    public function detalles(): HasMany
    {
        return $this->hasMany(DetalleVenta::class, 'venta_id');
    }

    public function pagoQr(): HasOne
    {
        return $this->hasOne(PagoQr::class);
    }

    public function recalcularTotal(): void
    {
        $this->update(['total' => $this->detalles()->sum('subtotal')]);
    }

    public function esQr(): bool
    {
        return $this->metodo_pago === 'qr';
    }
}
