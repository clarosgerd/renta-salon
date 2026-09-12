<?php

namespace Tests\Feature;

use App\Models\Inventario;
use App\Models\Negocio;
use App\Models\Producto;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Fase 7 (11/09/2026) — ver AN-7/§3.10. Mismo patrón que SalonControllerTest.
 */
class ProductoControllerTest extends TestCase
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

    public function test_admin_negocio_puede_listar_productos(): void
    {
        Producto::factory()->count(2)->create(['negocio_id' => $this->negocio->id]);

        $response = $this->actingAs($this->adminNegocio)->get(route('admin.productos.index'));

        $response->assertOk();
    }

    public function test_cajero_no_puede_acceder_a_productos(): void
    {
        $cajero = User::factory()->create(['negocio_id' => $this->negocio->id, 'role' => 'cajero']);

        $response = $this->actingAs($cajero)->get(route('admin.productos.index'));

        $response->assertForbidden();
    }

    public function test_crea_un_producto_con_stock_inicial(): void
    {
        $response = $this->actingAs($this->adminNegocio)->post(route('admin.productos.store'), [
            'nombre' => 'Silla Tiffany',
            'categoria' => 'mobiliario',
            'precio_venta' => 15,
            'costo' => 8,
            'stock_inicial' => 50,
        ]);

        $response->assertRedirect(route('admin.productos.index'));
        $producto = Producto::where('nombre', 'Silla Tiffany')->first();
        $this->assertNotNull($producto);
        $this->assertSame($this->negocio->id, $producto->negocio_id);
        $this->assertSame(50, $producto->stock_total);

        $this->assertDatabaseHas('movimientos_inventario', [
            'producto_id' => $producto->id, 'tipo' => 'entrada', 'cantidad' => 50,
        ]);
    }

    public function test_crea_un_producto_sin_stock_inicial_no_genera_movimiento(): void
    {
        $this->actingAs($this->adminNegocio)->post(route('admin.productos.store'), [
            'nombre' => 'Mantel Blanco', 'categoria' => 'decoracion', 'precio_venta' => 20,
        ]);

        $producto = Producto::where('nombre', 'Mantel Blanco')->first();
        $this->assertSame(0, $producto->stock_total);
        $this->assertDatabaseCount('movimientos_inventario', 0);
    }

    public function test_actualizar_no_toca_el_stock(): void
    {
        $producto = Producto::factory()->create(['negocio_id' => $this->negocio->id, 'nombre' => 'Original']);
        // negocio_id explícito: fuera de una request HTTP real,
        // app('negocio_actual') no está bound todavía, así que
        // BelongsToNegocio no lo puede autoasignar (ver bootBelongsToNegocio).
        Inventario::create(['negocio_id' => $this->negocio->id, 'producto_id' => $producto->id, 'salon_id' => null, 'stock_actual' => 30, 'stock_minimo' => 5]);

        $this->actingAs($this->adminNegocio)->put(route('admin.productos.update', $producto), [
            'nombre' => 'Actualizado', 'categoria' => $producto->categoria, 'precio_venta' => 99,
        ]);

        $producto->refresh();
        $this->assertSame('Actualizado', $producto->nombre);
        $this->assertSame(30, $producto->stock_total);
    }

    public function test_toggle_activo_invierte_el_estado(): void
    {
        $producto = Producto::factory()->create(['negocio_id' => $this->negocio->id, 'activo' => true]);

        $this->actingAs($this->adminNegocio)->patch(route('admin.productos.toggle-activo', $producto));

        $this->assertFalse($producto->fresh()->activo);
    }

    public function test_aislamiento_multi_tenant_entre_negocios(): void
    {
        $otroNegocio = Negocio::factory()->create();
        Producto::factory()->create(['negocio_id' => $otroNegocio->id, 'nombre' => 'Ajeno']);

        $response = $this->actingAs($this->adminNegocio)->get(route('admin.productos.index'));
        $response->assertOk();
        $response->assertDontSee('Ajeno');
    }
}
