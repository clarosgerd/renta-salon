<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Imagen extends Model
{
    // Bug real (10/09/2026): sin esto, Eloquent adivina 'imagens'
    // (pluralización en inglés) — la tabla real de la migración es
    // 'imagenes'. Mismo problema que Salon/Reservacion/PagoAbono.
    protected $table = 'imagenes';

    protected $fillable = ['url', 'orden'];

    public function imageable(): MorphTo
    {
        return $this->morphTo();
    }
}
