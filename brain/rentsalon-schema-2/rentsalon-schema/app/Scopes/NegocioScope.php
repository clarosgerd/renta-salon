<?php

namespace App\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

/**
 * Filtra automáticamente cualquier consulta Eloquent por el negocio activo
 * de la petición actual. Se aplica vía el trait BelongsToNegocio.
 *
 * El Super Admin de Plataforma (sin negocio activo, ej. en /plataforma)
 * no tiene este scope aplicado, por eso puede ver todos los negocios.
 */
class NegocioScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        if (app()->bound('negocio_actual')) {
            $builder->where($model->getTable() . '.negocio_id', app('negocio_actual')->id);
        }
    }
}
