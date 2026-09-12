<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Negocio = tenant. NO usa BelongsToNegocio (es la raíz del multi-tenant),
 * por eso el Super Admin de Plataforma puede consultarlo sin restricción.
 */
class Negocio extends Model
{
    protected $fillable = [
        'nombre_comercial', 'subdominio', 'dominio_personalizado',
        'logo_url', 'color_primario', 'telefono_contacto',
        'email_contacto', 'estado', 'plan',
    ];

    public function usuarios(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function salones(): HasMany
    {
        return $this->hasMany(Salon::class);
    }

    public function paquetes(): HasMany
    {
        return $this->hasMany(Paquete::class);
    }

    public function reservaciones(): HasMany
    {
        return $this->hasMany(Reservacion::class);
    }

    public function productos(): HasMany
    {
        return $this->hasMany(Producto::class);
    }

    public function configPago(): HasOne
    {
        return $this->hasOne(NegocioConfigPago::class);
    }

    public function estaActivo(): bool
    {
        return $this->estado === 'activo';
    }
}
