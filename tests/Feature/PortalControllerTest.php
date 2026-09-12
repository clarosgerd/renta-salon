<?php

namespace Tests\Feature;

use App\Models\Negocio;
use App\Models\Paquete;
use App\Models\Reservacion;
use App\Models\Salon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Fase 6 (11/09/2026) — ver CL-1/CL-2/§3.14. Portal público, sin auth —
 * mismo host de testing que el resto (dominio_personalizado='rentsalon-pro.test',
 * sembrado por TestCase::setUp()).
 */
class PortalControllerTest extends TestCase
{
    use RefreshDatabase;

    private Negocio $negocio;

    protected function setUp(): void
    {
        parent::setUp();

        $this->negocio = Negocio::first();
    }

    public function test_home_carga_sin_login(): void
    {
        $response = $this->get(route('portal.home'));

        $response->assertOk();
        $response->assertSee($this->negocio->nombre_comercial);
    }

    public function test_listado_de_salones(): void
    {
        Salon::factory()->create(['negocio_id' => $this->negocio->id, 'nombre' => 'Salón Jardín']);
        Salon::factory()->inactivo()->create(['negocio_id' => $this->negocio->id, 'nombre' => 'Salón Oculto']);

        $response = $this->get(route('portal.salones'));

        $response->assertOk();
        $response->assertSee('Salón Jardín');
        $response->assertDontSee('Salón Oculto');
    }

    public function test_ficha_de_salon_inactivo_da_404(): void
    {
        $salon = Salon::factory()->inactivo()->create(['negocio_id' => $this->negocio->id]);

        $response = $this->get(route('portal.salon', $salon));

        $response->assertNotFound();
    }

    public function test_ficha_de_salon_muestra_paquetes_aplicables(): void
    {
        $salon = Salon::factory()->create(['negocio_id' => $this->negocio->id]);
        $paqueteGeneral = Paquete::factory()->create(['negocio_id' => $this->negocio->id, 'nombre' => 'Paquete General']);
        $otroSalon = Salon::factory()->create(['negocio_id' => $this->negocio->id]);
        $paqueteAjeno = Paquete::factory()->create(['negocio_id' => $this->negocio->id, 'nombre' => 'Paquete De Otro Salon']);
        $paqueteAjeno->salones()->sync([$otroSalon->id]);

        $response = $this->get(route('portal.salon', $salon));

        $response->assertOk();
        $response->assertSee('Paquete General'); // aplica a todos (sin filas en el pivote)
        $response->assertDontSee('Paquete De Otro Salon');
    }

    public function test_disponibilidad_no_expone_datos_del_cliente(): void
    {
        $salon = Salon::factory()->create(['negocio_id' => $this->negocio->id]);
        Reservacion::factory()->create([
            'negocio_id' => $this->negocio->id, 'salon_id' => $salon->id,
            'cliente_nombre' => 'Cliente Secreto', 'cliente_telefono' => '70000000',
            'estado' => 'confirmada', 'fecha_evento' => now()->addDays(5),
        ]);

        $response = $this->get(route('portal.disponibilidad', $salon) . '?start=' . now()->toDateString() . '&end=' . now()->addMonth()->toDateString());

        $response->assertOk();
        $response->assertDontSee('Cliente Secreto');
        $response->assertDontSee('70000000');
        $response->assertJsonFragment(['title' => 'Ocupado']);
    }

    public function test_disponibilidad_excluye_reservaciones_canceladas(): void
    {
        $salon = Salon::factory()->create(['negocio_id' => $this->negocio->id]);
        Reservacion::factory()->create([
            'negocio_id' => $this->negocio->id, 'salon_id' => $salon->id,
            'estado' => 'cancelada', 'fecha_evento' => now()->addDays(5),
        ]);

        $response = $this->getJson(route('portal.disponibilidad', $salon) . '?start=' . now()->toDateString() . '&end=' . now()->addMonth()->toDateString());

        $response->assertOk()->assertJsonCount(0);
    }

    public function test_paquete_inactivo_da_404(): void
    {
        $paquete = Paquete::factory()->create(['negocio_id' => $this->negocio->id, 'activo' => false]);

        $response = $this->get(route('portal.paquete', $paquete));

        $response->assertNotFound();
    }

    public function test_aislamiento_multi_tenant_salon_de_otro_negocio_da_404(): void
    {
        $otroNegocio = Negocio::factory()->create();
        $salonAjeno = Salon::factory()->create(['negocio_id' => $otroNegocio->id]);

        $response = $this->get(route('portal.salon', $salonAjeno));

        $response->assertNotFound();
    }
}
