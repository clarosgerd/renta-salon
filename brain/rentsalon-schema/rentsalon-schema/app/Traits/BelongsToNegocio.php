<?php

namespace App\Traits;

use App\Scopes\NegocioScope;

/**
 * Trait a usar en todos los modelos "propiedad" de un negocio
 * (Salon, Paquete, Reservacion, Producto, Inventario, VentaPos, etc.)
 *
 * - Aplica el NegocioScope automáticamente en todas las consultas.
 * - Autoasigna negocio_id al crear un registro nuevo, tomando el
 *   negocio activo resuelto por el middleware IdentificarNegocio.
 */
trait BelongsToNegocio
{
    public static function bootBelongsToNegocio(): void
    {
        static::addGlobalScope(new NegocioScope());

        static::creating(function ($model) {
            if (empty($model->negocio_id) && app()->bound('negocio_actual')) {
                $model->negocio_id = app('negocio_actual')->id;
            }
        });
    }

    public function negocio()
    {
        return $this->belongsTo(\App\Models\Negocio::class);
    }
}
