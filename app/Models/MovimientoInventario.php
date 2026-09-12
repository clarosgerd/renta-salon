<?php

namespace App\Models;

use App\Traits\BelongsToNegocio;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MovimientoInventario extends Model
{
    use BelongsToNegocio;

    protected $table = 'movimientos_inventario';

    protected $fillable = [
        'negocio_id', 'producto_id', 'tipo', 'cantidad',
        'referencia_venta_id', 'fecha', 'usuario_id', 'notas',
    ];

    protected $casts = ['fecha' => 'date'];

    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class);
    }

    public function venta(): BelongsTo
    {
        return $this->belongsTo(VentaPos::class, 'referencia_venta_id');
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }
}
