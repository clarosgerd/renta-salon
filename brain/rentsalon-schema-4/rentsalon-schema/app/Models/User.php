<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasRoles;

    protected $fillable = [
        'negocio_id', 'name', 'email', 'password', 'role', 'activo',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'activo' => 'boolean',
    ];

    public function negocio(): BelongsTo
    {
        return $this->belongsTo(Negocio::class);
    }

    /**
     * Salones que este usuario puede administrar (Admin Salón / Cajero).
     * Un Admin Negocio no necesita filas aquí: ve todos los salones de su negocio.
     */
    public function salones(): BelongsToMany
    {
        return $this->belongsToMany(Salon::class, 'usuario_salon');
    }

    public function esSuperAdminPlataforma(): bool
    {
        return $this->role === 'super_admin_plataforma';
    }

    public function esAdminNegocio(): bool
    {
        return $this->role === 'admin_negocio';
    }

    /**
     * IDs de los salones que este usuario puede ver/operar,
     * usado para filtrar listados según el rol.
     */
    public function salonesPermitidos(): \Illuminate\Support\Collection
    {
        if ($this->esAdminNegocio() || $this->esSuperAdminPlataforma()) {
            return Salon::pluck('id'); // ya filtrado por NegocioScope si aplica
        }

        return $this->salones()->pluck('salones.id');
    }
}
