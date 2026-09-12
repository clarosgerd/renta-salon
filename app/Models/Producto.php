<?php

namespace App\Models;

use App\Traits\BelongsToNegocio;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Producto extends Model
{
    use BelongsToNegocio, HasFactory;

    protected $fillable = [
        'negocio_id', 'nombre', 'categoria', 'precio_venta',
        'costo', 'unidad_medida', 'activo',
    ];

    protected $casts = [
        'precio_venta' => 'decimal:2',
        'costo' => 'decimal:2',
        'activo' => 'boolean',
    ];

    public function inventarios(): HasMany
    {
        return $this->hasMany(Inventario::class);
    }

    public function movimientos(): HasMany
    {
        return $this->hasMany(MovimientoInventario::class);
    }

    public function imagenes(): MorphMany
    {
        return $this->morphMany(Imagen::class, 'imageable')->orderBy('orden');
    }

    /**
     * Stock total sumando todos los almacenes/salones del negocio.
     */
    public function getStockTotalAttribute(): int
    {
        return (int) $this->inventarios()->sum('stock_actual');
    }

    public function scopeActivos($query)
    {
        return $query->where('activo', true);
    }
}
