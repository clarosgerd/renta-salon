<?php

namespace App\Services;

use App\Models\Inventario;
use App\Models\MovimientoInventario;
use Illuminate\Support\Facades\DB;

/**
 * Reglas de negocio de Inventario — Fase 7 (11/09/2026). Esta pasada solo
 * expone "entrada manual" (reabasto), igual que pide literalmente
 * Historias_Usuario_Pantallas §3.10 ("formulario para registrar entradas
 * manuales — las salidas por venta se generan solas"). El tipo `ajuste`
 * queda en el enum de la tabla para el futuro, sin UI todavía.
 */
class InventarioService
{
    public function registrarEntrada(array $datos): MovimientoInventario
    {
        return DB::transaction(function () use ($datos) {
            $inventario = Inventario::firstOrCreate(
                ['producto_id' => $datos['producto_id'], 'salon_id' => $datos['salon_id'] ?? null],
                ['stock_actual' => 0, 'stock_minimo' => 0]
            );

            $inventario->increment('stock_actual', $datos['cantidad']);
            $inventario->update(['ultima_actualizacion' => now()]);

            return MovimientoInventario::create([
                'negocio_id' => $inventario->negocio_id,
                'producto_id' => $datos['producto_id'],
                'tipo' => 'entrada',
                'cantidad' => $datos['cantidad'],
                'fecha' => now()->toDateString(),
                'usuario_id' => $datos['usuario_id'],
                'notas' => $datos['notas'] ?? null,
            ]);
        });
    }
}
