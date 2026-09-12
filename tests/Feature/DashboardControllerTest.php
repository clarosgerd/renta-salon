<?php

namespace Tests\Feature;

use App\Models\Inventario;
use App\Models\Negocio;
use App\Models\Paquete;
use App\Models\Producto;
use App\Models\Reservacion;
use App\Models\Salon;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Dashboard real (11/09/2026) — ver §3.1, reemplaza el stub mínimo de la
 * activación multi-tenant. Mismo patrón de scoping por rol que
 * CalendarioControllerTest.
 */
class DashboardControllerTest extends TestCase
{
    use RefreshDatabase;

    private Negocio $negocio;
    private User $adminNegocio;
    private Salon $salon;
    private Paquete $paquete;

    protected function setUp(): void
    {
        parent::setUp();

        $this->negocio = Negocio::first();
        $this->adminNegocio = User::factory()->create(['negocio_id' => $this->negocio->id, 'role' => 'admin_negocio']);
        $this->salon = Salon::factory()->create(['negocio_id' => $this->negocio->id, 'nombre' => 'Salón Jardín']);
        $this->paquete = Paquete::factory()->create(['negocio_id' => $this->negocio->id]);
    }

    public function test_tarjetas_de_resumen_muestran_los_conteos_correctos(): void
    {
        // Eventos esta semana (2 activas, 1 cancelada no cuenta). precio_total
        // en 0 a propósito: confirmada/pendiente SÍ suman a saldoPorCobrar,
        // no queremos que interfieran con la aserción dedicada de abajo.
        Reservacion::factory()->create(['negocio_id' => $this->negocio->id, 'salon_id' => $this->salon->id, 'estado' => 'confirmada', 'fecha_evento' => now(), 'precio_total' => 0]);
        Reservacion::factory()->create(['negocio_id' => $this->negocio->id, 'salon_id' => $this->salon->id, 'estado' => 'pendiente', 'fecha_evento' => now(), 'precio_total' => 0]);
        Reservacion::factory()->create(['negocio_id' => $this->negocio->id, 'salon_id' => $this->salon->id, 'estado' => 'cancelada', 'fecha_evento' => now(), 'precio_total' => 0]);

        // Saldo por cobrar: 1000 de precio_total sin abonos = 1000 pendiente.
        Reservacion::factory()->create([
            'negocio_id' => $this->negocio->id, 'salon_id' => $this->salon->id,
            'estado' => 'confirmada', 'fecha_evento' => now()->addDays(10), 'precio_total' => 1000,
        ]);

        $producto = Producto::factory()->create(['negocio_id' => $this->negocio->id]);
        Inventario::create(['negocio_id' => $this->negocio->id, 'producto_id' => $producto->id, 'salon_id' => null, 'stock_actual' => 1, 'stock_minimo' => 5]);

        $response = $this->actingAs($this->adminNegocio)->get(route('dashboard'));

        $response->assertOk();
        $response->assertViewHas('eventosEstaSemana', 2);
        $response->assertViewHas('solicitudesPendientes', 1);
        $response->assertViewHas('saldoPorCobrar', 1000.0);
        $response->assertViewHas('productosStockBajo', 1);
    }

    public function test_proximos_eventos_lista_las_5_mas_cercanas_sin_canceladas(): void
    {
        $cercana = Reservacion::factory()->create([
            'negocio_id' => $this->negocio->id, 'salon_id' => $this->salon->id, 'paquete_id' => $this->paquete->id,
            'estado' => 'confirmada', 'fecha_evento' => now()->addDays(2), 'cliente_nombre' => 'María Fernández',
        ]);
        Reservacion::factory()->create([
            'negocio_id' => $this->negocio->id, 'salon_id' => $this->salon->id,
            'estado' => 'cancelada', 'fecha_evento' => now()->addDays(1), 'cliente_nombre' => 'Cancelado Oculto',
        ]);
        Reservacion::factory()->create([
            'negocio_id' => $this->negocio->id, 'salon_id' => $this->salon->id,
            'estado' => 'confirmada', 'fecha_evento' => now()->subDays(5), 'cliente_nombre' => 'Ya Pasó',
        ]);

        $response = $this->actingAs($this->adminNegocio)->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee('María Fernández');
        $response->assertSee('Salón Jardín');
        $response->assertDontSee('Cancelado Oculto');
        $response->assertDontSee('Ya Pasó');
    }

    public function test_solicitudes_nuevas_permite_confirmar_desde_el_dashboard(): void
    {
        $solicitud = Reservacion::factory()->create([
            'negocio_id' => $this->negocio->id, 'salon_id' => $this->salon->id,
            'estado' => 'pendiente', 'fecha_evento' => now()->addDays(3), 'cliente_nombre' => 'Ana Choque',
        ]);

        $response = $this->actingAs($this->adminNegocio)->get(route('dashboard'));
        $response->assertSee('Ana Choque');
        $response->assertSee('Confirmar');
        $response->assertSee('Rechazar');

        // Reusa la ruta ya existente (AN-4) — el dashboard no duplica lógica.
        $this->actingAs($this->adminNegocio)->patch(route('admin.reservaciones.confirmar', $solicitud));

        $this->assertSame('confirmada', $solicitud->fresh()->estado);
    }

    public function test_admin_salon_solo_ve_sus_propios_salones_en_el_resumen(): void
    {
        $otroSalon = Salon::factory()->create(['negocio_id' => $this->negocio->id, 'nombre' => 'Salón Ajeno']);
        $adminSalon = User::factory()->create(['negocio_id' => $this->negocio->id, 'role' => 'admin_salon']);
        $adminSalon->salones()->attach($this->salon->id);

        Reservacion::factory()->create(['negocio_id' => $this->negocio->id, 'salon_id' => $this->salon->id, 'estado' => 'pendiente']);
        Reservacion::factory()->create(['negocio_id' => $this->negocio->id, 'salon_id' => $otroSalon->id, 'estado' => 'pendiente']);

        $response = $this->actingAs($adminSalon)->get(route('dashboard'));

        $response->assertOk();
        $response->assertViewHas('solicitudesPendientes', 1); // no las 2 del negocio, solo la de su salón
    }
}
