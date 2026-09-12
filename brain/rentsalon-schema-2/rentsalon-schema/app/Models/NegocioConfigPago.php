<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NegocioConfigPago extends Model
{
    protected $table = 'negocio_config_pago';

    protected $fillable = [
        'negocio_id', 'banco', 'credenciales_json_cifrado',
        'cuenta_destino', 'activo', 'ultima_prueba_conexion',
    ];

    protected $casts = [
        // Laravel cifra/descifra automáticamente con la app key.
        // Nunca se expone en texto plano en respuestas JSON (ver $hidden).
        'credenciales_json_cifrado' => 'encrypted:array',
        'activo' => 'boolean',
        'ultima_prueba_conexion' => 'datetime',
    ];

    protected $hidden = ['credenciales_json_cifrado'];

    public function negocio(): BelongsTo
    {
        return $this->belongsTo(Negocio::class);
    }
}
