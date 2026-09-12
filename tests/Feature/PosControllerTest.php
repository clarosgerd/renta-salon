<?php

namespace Tests\Feature;

use App\Models\Inventario;
use App\Models\Negocio;
use App\Models\Producto;
use App\Models\Reservacion;
use App\Models\User;
use App\Models\VentaPos;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Fase 7 (11/09/2026) — ver CJ-1..CJ-4/§3.8-§3.9.
 */
class PosControllerTest extends TestCase
{
    use RefreshDatabase;

    private Negocio $negocio;
    private User $cajero;
    private Producto $producto;

    protected function setUp(): void
    {
        parent::setUp();

        $this->negocio = Negocio::first();
        $this->cajero = User::factory()->create(['negocio_id' => $this->negocio->id, 'role' => 'cajero']);
        $this->producto = Producto::factory()->create(['negocio_id' => $this->negocio->id, 'precio_venta' => 20]);
        Inventario::create([
            'negocio_id' => $this->negocio->id, 'producto_id' => $this->producto->id,
            'salon_id' => null, 'stock_actual' => 10, 'stock_minimo' => 0,
        ]);
    }

    public function test_cajero_puede_ver_la_pantalla_de_pos(): void
    {
        $response = $this->actingAs($this->cajero)->get(route('admin.pos.index'));

        $response->assertOk();
    }

    public function test_venta_en_efectivo_descuenta_stock_y_crea_detalle(): void
    {
        $response = $this->actingAs($this->cajero)->post(route('admin.pos.store'), [
            'items' => [['producto_id' => $this->producto->id, 'cantidad' => 3]],
            'metodo_pago' => 'efectivo',
        ]);

        $response->assertRedirect();
        $venta = VentaPos::first();
        $this->assertNotNull($venta);
        $this->assertSame($this->cajero->id, $venta->cajero_id);
        $this->assertSame('efectivo', $venta->metodo_pago);
        $this->assertEquals(60, $venta->total); // 3 * 20
        $this->assertCount(1, $venta->detalles);
        $this->assertSame(7, $this->producto->fresh()->stock_total); // 10 - 3

        $this->assertDatabaseHas('movimientos_inventario', [
            'producto_id' => $this->producto->id, 'tipo' => 'salida_venta', 'cantidad' => -3,
            'referencia_venta_id' => $venta->id,
        ]);
    }

    public function test_venta_qr_genera_pago_qr_asociado(): void
    {
        $this->actingAs($this->cajero)->post(route('admin.pos.store'), [
            'items' => [['producto_id' => $this->producto->id, 'cantidad' => 2]],
            'metodo_pago' => 'qr',
        ]);

        $venta = VentaPos::first();
        $this->assertTrue($venta->esQr());
        $this->assertNotNull($venta->pagoQr);
        $this->assertSame($venta->id, $venta->pagoQr->venta_pos_id);
        $this->assertSame('generado', $venta->pagoQr->estado);
        // El stock igual se descuenta de inmediato (el POS no espera
        // confirmación del banco para completar la venta, ver
        // VentaPosService::registrar).
        $this->assertSame(8, $this->producto->fresh()->stock_total);
    }

    public function test_stock_insuficiente_rechaza_sin_crear_nada(): void
    {
        $response = $this->actingAs($this->cajero)->post(route('admin.pos.store'), [
            'items' => [['producto_id' => $this->producto->id, 'cantidad' => 999]],
            'metodo_pago' => 'efectivo',
        ]);

        $response->assertSessionHasErrors('items');
        $this->assertDatabaseCount('ventas_pos', 0);
        $this->assertDatabaseCount('detalle_venta', 0);
        $this->assertDatabaseCount('movimientos_inventario', 0);
        $this->assertSame(10, $this->producto->fresh()->stock_total); // sin cambios
    }

    public function test_venta_vinculada_a_reservacion_aparece_en_su_historial(): void
    {
        $reservacion = Reservacion::factory()->create(['negocio_id' => $this->negocio->id, 'estado' => 'confirmada']);

        $this->actingAs($this->cajero)->post(route('admin.pos.store'), [
            'items' => [['producto_id' => $this->producto->id, 'cantidad' => 1]],
            'metodo_pago' => 'efectivo',
            'reservacion_id' => $reservacion->id,
        ]);

        $this->assertCount(1, $reservacion->fresh()->ventasPos);
    }

    public function test_rechaza_producto_de_otro_negocio(): void
    {
        $otroNegocio = Negocio::factory()->create();
        $productoAjeno = Producto::factory()->create(['negocio_id' => $otroNegocio->id]);

        $response = $this->actingAs($this->cajero)->post(route('admin.pos.store'), [
            'items' => [['producto_id' => $productoAjeno->id, 'cantidad' => 1]],
            'metodo_pago' => 'efectivo',
        ]);

        $response->assertSessionHasErrors('items.0.producto_id');
    }

    public function test_corte_del_dia_solo_trae_lo_del_cajero_autenticado(): void
    {
        $otroCajero = User::factory()->create(['negocio_id' => $this->negocio->id, 'role' => 'cajero']);

        VentaPos::create([
            'negocio_id' => $this->negocio->id, 'fecha' => now()->toDateString(),
            'total' => 100, 'metodo_pago' => 'efectivo', 'estado' => 'pagada', 'cajero_id' => $this->cajero->id,
        ]);
        VentaPos::create([
            'negocio_id' => $this->negocio->id, 'fecha' => now()->toDateString(),
            'total' => 500, 'metodo_pago' => 'efectivo', 'estado' => 'pagada', 'cajero_id' => $otroCajero->id,
        ]);

        $response = $this->actingAs($this->cajero)->get(route('admin.pos.corte'));

        $response->assertOk();
        $response->assertSee('Bs. 100.00');
        $response->assertDontSee('Bs. 500.00');
    }

    public function test_corte_separa_efectivo_qr_confirmado_y_qr_pendiente(): void
    {
        $ventaEfectivo = VentaPos::create([
            'negocio_id' => $this->negocio->id, 'fecha' => now()->toDateString(),
            'total' => 50, 'metodo_pago' => 'efectivo', 'estado' => 'pagada', 'cajero_id' => $this->cajero->id,
        ]);

        $ventaQrPagada = VentaPos::create([
            'negocio_id' => $this->negocio->id, 'fecha' => now()->toDateString(),
            'total' => 30, 'metodo_pago' => 'qr', 'estado' => 'pagada', 'cajero_id' => $this->cajero->id,
        ]);
        $ventaQrPagada->pagoQr()->create(['monto' => 30, 'estado' => 'pagado']);

        $ventaQrPendiente = VentaPos::create([
            'negocio_id' => $this->negocio->id, 'fecha' => now()->toDateString(),
            'total' => 15, 'metodo_pago' => 'qr', 'estado' => 'pagada', 'cajero_id' => $this->cajero->id,
        ]);
        $ventaQrPendiente->pagoQr()->create(['monto' => 15, 'estado' => 'generado']);

        $response = $this->actingAs($this->cajero)->get(route('admin.pos.corte'));

        $response->assertOk();
        $response->assertViewHas('efectivo', 50.0);
        $response->assertViewHas('qrConfirmado', 30.0);
        $response->assertViewHas('qrPendiente', 15.0);
        $response->assertViewHas('totalAEntregar', 80.0); // efectivo + qr confirmado, NO el pendiente
    }
}
