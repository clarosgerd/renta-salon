<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * SA-5 (11/09/2026) — log de auditoría de impersonación (Super Admin
 * "entrando como" un negocio, ver ImpersonacionController/
 * ImpersonacionEntradaController). Sin timestamps propios de Eloquent:
 * usa iniciada_en/finalizada_en, más expresivos para este dominio.
 */
class ImpersonacionLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'super_admin_user_id', 'negocio_id', 'usuario_impersonado_id',
        'ip_origen', 'iniciada_en', 'finalizada_en',
    ];

    protected $casts = [
        'iniciada_en' => 'datetime',
        'finalizada_en' => 'datetime',
    ];

    public function superAdmin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'super_admin_user_id');
    }

    public function negocio(): BelongsTo
    {
        return $this->belongsTo(Negocio::class);
    }

    public function usuarioImpersonado(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_impersonado_id');
    }

    public function scopeActiva(Builder $query): Builder
    {
        return $query->whereNull('finalizada_en');
    }
}
