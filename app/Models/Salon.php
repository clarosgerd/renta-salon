<?php

namespace App\Models;

use App\Traits\BelongsToNegocio;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Salon extends Model
{
    use BelongsToNegocio, HasFactory;

    // Bug real (10/09/2026): sin esto, Eloquent adivina 'salons'
    // (pluralización en inglés) — la tabla real de la migración es
    // 'salones'. Mismo problema que Reservacion/PagoAbono — ver esos
    // modelos.
    protected $table = 'salones';

    protected $fillable = [
        'negocio_id', 'nombre', 'descripcion',
        'capacidad_min', 'capacidad_max', 'ubicacion', 'activo',
    ];

    protected $casts = ['activo' => 'boolean'];

    public function paquetes(): BelongsToMany
    {
        return $this->belongsToMany(Paquete::class, 'paquete_salon');
    }

    public function reservaciones(): HasMany
    {
        return $this->hasMany(Reservacion::class);
    }

    public function inventarios(): HasMany
    {
        return $this->hasMany(Inventario::class);
    }

    public function usuariosAsignados(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'usuario_salon');
    }

    public function imagenes(): MorphMany
    {
        return $this->morphMany(Imagen::class, 'imageable')->orderBy('orden');
    }

    public function scopeActivos($query)
    {
        return $query->where('activo', true);
    }
}
