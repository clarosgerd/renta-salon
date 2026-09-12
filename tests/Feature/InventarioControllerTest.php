<?php

namespace Tests\Feature;

use App\Models\Inventario;
use App\Models\Negocio;
use App\Models\Producto;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Fase 7 (11/09/2026) — ver §3.10. Reusa ProductoPolicy, sin Policy propia.
 */
class InventarioControllerTest extends TestCase
{
    use RefreshDatabase;

    private Negocio $negocio;
    private User $adminNegocio;

    protected function setUp(): void
    {
        parent::setUp();

        $this->negocio = Negocio::first();
        $this->adminNegocio = User::factory()->create(['negocio_id' => $this->negocio->id, 'role' => 'admin_negocio']);
    }

    public function test_admin_negocio_puede_ver_existencias(): void
    {
        $producto = Producto::factory()->create(['negocio_id' => $this->negocio->id]);
        Inventario::create(['negocio_id' => $this->negocio->id, 'producto_id' => $producto->id, 'salon_id' => null, 'stock_actual' => 5, 'stock_minimo' => 10]);

        $response = $this->actingAs($this->adminNegocio)->get(route('admin.inventario.index'));

        $response->assertOk();
        $response->assertSee('Stock bajo'); // 5 < 10
    }

    public function test_cajero_no_puede_ver_inventario(): void
    {
        $cajero = User::factory()->create(['negocio_id' => $this->negocio->id, 'role' => 'cajero']);

        $response = $this->actingAs($cajero)->get(route('admin.inventario.index'));

        $response->assertForbidden();
    }

    public function test_admin_negocio_puede_ver_movimientos(): void
    {
        $response = $this->actingAs($this->adminNegocio)->get(route('admin.inventario.movimientos'));

        $response->assertOk();
    }

    public function test_registrar_entrada_incrementa_stock_y_crea_movimiento(): void
    {
        $producto = Producto::factory()->create(['negocio_id' => $this->negocio->id]);
        Inventario::create(['negocio_id' => $this->negocio->id, 'producto_id' => $producto->id, 'salon_id' => null, 'stock_actual' => 10, 'stock_minimo' => 0]);

        $response = $this->actingAs($this->adminNegocio)->post(route('admin.inventario.movimientos.store'), [
            'producto_id' => $producto->id,
            'cantidad' => 25,
            'notas' => 'Reabasto de prueba',
        ]);

        $response->assertRedirect();
        $this->assertSame(35, $producto->stock_total);
        $this->assertDatabaseHas('movimientos_inventario', [
            'producto_id' => $producto->id, 'tipo' => 'entrada', 'cantidad' => 25, 'notas' => 'Reabasto de prueba',
        ]);
    }

    public function test_registrar_entrada_sin_fila_de_inventario_previa_la_crea(): void
    {
        $producto = Producto::factory()->create(['negocio_id' => $this->negocio->id]);

        $this->actingAs($this->adminNegocio)->post(route('admin.inventario.movimientos.store'), [
            'producto_id' => $producto->id,
            'cantidad' => 10,
        ]);

        $this->assertSame(10, $producto->stock_total);
    }

    public function test_rechaza_producto_de_otro_negocio(): void
    {
        $otroNegocio = Negocio::factory()->create();
        $productoAjeno = Producto::factory()->create(['negocio_id' => $otroNegocio->id]);

        $response = $this->actingAs($this->adminNegocio)->post(route('admin.inventario.movimientos.store'), [
            'producto_id' => $productoAjeno->id,
            'cantidad' => 10,
        ]);

        $response->assertSessionHasErrors('producto_id');
    }
}
