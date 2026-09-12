<?php

namespace App\Models;

use App\Traits\BelongsToNegocio;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Inventario extends Model
{
    use BelongsToNegocio;

    protected $table = 'inventario';

    protected $fillable = [
        'negocio_id', 'producto_id', 'salon_id',
        'stock_actual', 'stock_minimo', 'ultima_actualizacion',
    ];

    protected $casts = [
        'ultima_actualizacion' => 'datetime',
    ];

    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class);
    }

    public function salon(): BelongsTo
    {
        return $this->belongsTo(Salon::class);
    }

    public function getStockBajoAttribute(): bool
    {
        return $this->stock_actual <= $this->stock_minimo;
    }

    public function scopeStockBajo($query)
    {
        return $query->whereColumn('stock_actual', '<=', 'stock_minimo');
    }
}
