<?php

namespace App\Services;

use App\Models\MovimientoInventario;
use App\Models\Producto;
use Illuminate\Support\Facades\DB;

/**
 * Reglas de negocio de Productos — separado del Controller mismo criterio
 * que SalonService/PaqueteService.
 */
class ProductoService
{
    public function crear(array $datos): Producto
    {
        return DB::transaction(function () use ($datos) {
            $stockInicial = (int) ($datos['stock_inicial'] ?? 0);
            unset($datos['stock_inicial']);

            $producto = Producto::create($datos);

            // Fila de Inventario "almacén general" (salon_id null) creada
            // de una vez, para no obligar un segundo paso solo para
            // registrar el stock inicial.
            $producto->inventarios()->create([
                'salon_id' => null,
                'stock_actual' => $stockInicial,
                'stock_minimo' => 0,
                'ultima_actualizacion' => now(),
            ]);

            if ($stockInicial > 0) {
                MovimientoInventario::create([
                    'negocio_id' => $producto->negocio_id,
                    'producto_id' => $producto->id,
                    'tipo' => 'entrada',
                    'cantidad' => $stockInicial,
                    'fecha' => now()->toDateString(),
                    'usuario_id' => auth()->id(),
                    'notas' => 'Stock inicial al crear el producto',
                ]);
            }

            return $producto;
        });
    }

    public function actualizar(Producto $producto, array $datos): Producto
    {
        // Sin tocar stock — eso pasa exclusivamente por Inventario/Movimientos.
        $producto->update($datos);

        return $producto->fresh();
    }

    public function toggleActivo(Producto $producto): Producto
    {
        $producto->update(['activo' => ! $producto->activo]);

        return $producto;
    }
}
