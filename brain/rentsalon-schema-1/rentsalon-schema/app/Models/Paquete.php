<?php

namespace App\Models;

use App\Traits\BelongsToNegocio;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Paquete extends Model
{
    use BelongsToNegocio;

    protected $fillable = [
        'negocio_id', 'nombre', 'tipo_evento', 'descripcion',
        'precio_base', 'duracion_horas', 'servicios_incluidos', 'activo',
    ];

    protected $casts = [
        'servicios_incluidos' => 'array',
        'activo' => 'boolean',
        'precio_base' => 'decimal:2',
    ];

    public function salones(): BelongsToMany
    {
        return $this->belongsToMany(Salon::class, 'paquete_salon');
    }

    public function reservaciones(): HasMany
    {
        return $this->hasMany(Reservacion::class);
    }

    /**
     * Un paquete sin filas en paquete_salon se interpreta como
     * "aplica a todos los salones del negocio" (regla de negocio).
     */
    public function aplicaATodosLosSalones(): bool
    {
        return $this->salones()->count() === 0;
    }

    public function scopeActivos($query)
    {
        return $query->where('activo', true);
    }
}
