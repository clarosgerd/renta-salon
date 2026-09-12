<?php

namespace Tests\Feature;

use App\Models\Negocio;
use App\Models\Reservacion;
use App\Models\Salon;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Calendario maestro (11/09/2026, Fase 3) — ver AN-3 en
 * brain/Historias_Usuario_Pantallas_RentSalon_Pro.md.
 */
class CalendarioControllerTest extends TestCase
{
    use RefreshDatabase;

    private Negocio $negocio;

    private User $adminNegocio;

    private Salon $salon;

    protected function setUp(): void
    {
        parent::setUp();

        $this->negocio = Negocio::first();
        $this->adminNegocio = User::factory()->create([
            'negocio_id' => $this->negocio->id,
            'role' => 'admin_negocio',
        ]);
        $this->salon = Salon::factory()->create(['negocio_id' => $this->negocio->id]);
    }

    public function test_admin_negocio_puede_ver_el_calendario(): void
    {
        $response = $this->actingAs($this->adminNegocio)->get(route('admin.calendario.index'));

        $response->assertOk();
    }

    public function test_rol_sin_permiso_no_puede_ver_el_calendario(): void
    {
        // ReservacionPolicy::viewAny() solo permite admin_negocio/
        // admin_salon/cajero — super_admin_plataforma (negocio_id=null,
        // opera desde admin.rentsalon-pro.test) es el único rol real del
        // sistema que NO pasa ese chequeo si de alguna forma llega hasta
        // acá.
        $superAdmin = User::factory()->create(['negocio_id' => null, 'role' => 'super_admin_plataforma']);

        $response = $this->actingAs($superAdmin)->get(route('admin.calendario.index'));

        $response->assertForbidden();
    }

    public function test_eventos_filtra_por_rango_de_fechas(): void
    {
        Reservacion::factory()->create([
            'negocio_id' => $this->negocio->id,
            'salon_id' => $this->salon->id,
            'fecha_evento' => '2026-09-15',
        ]);
        Reservacion::factory()->create([
            'negocio_id' => $this->negocio->id,
            'salon_id' => $this->salon->id,
            'fecha_evento' => '2026-11-01',
        ]);

        $response = $this->actingAs($this->adminNegocio)->getJson(
            route('admin.calendario.eventos', ['start' => '2026-09-01', 'end' => '2026-09-30'])
        );

        $response->assertOk()->assertJsonCount(1);
    }

    public function test_eventos_filtra_por_salon(): void
    {
        $otroSalon = Salon::factory()->create(['negocio_id' => $this->negocio->id]);
        Reservacion::factory()->create([
            'negocio_id' => $this->negocio->id, 'salon_id' => $this->salon->id, 'fecha_evento' => '2026-09-15',
        ]);
        Reservacion::factory()->create([
            'negocio_id' => $this->negocio->id, 'salon_id' => $otroSalon->id, 'fecha_evento' => '2026-09-16',
        ]);

        $response = $this->actingAs($this->adminNegocio)->getJson(route('admin.calendario.eventos', [
            'start' => '2026-09-01', 'end' => '2026-09-30', 'salon_id' => [$this->salon->id],
        ]));

        $response->assertOk()->assertJsonCount(1);
    }

    public function test_eventos_trae_el_color_correcto_por_estado(): void
    {
        $reservacion = Reservacion::factory()->create([
            'negocio_id' => $this->negocio->id, 'salon_id' => $this->salon->id,
            'fecha_evento' => '2026-09-15', 'estado' => 'pendiente',
        ]);

        $response = $this->actingAs($this->adminNegocio)->getJson(
            route('admin.calendario.eventos', ['start' => '2026-09-01', 'end' => '2026-09-30'])
        );

        $response->assertOk()->assertJsonFragment(['color' => '#f59e0b', 'id' => $reservacion->id]);
    }

    public function test_cajero_solo_ve_eventos_de_sus_salones_asignados(): void
    {
        $otroSalon = Salon::factory()->create(['negocio_id' => $this->negocio->id]);
        $cajero = User::factory()->create(['negocio_id' => $this->negocio->id, 'role' => 'cajero']);
        $cajero->salones()->sync([$this->salon->id]);

        Reservacion::factory()->create([
            'negocio_id' => $this->negocio->id, 'salon_id' => $this->salon->id, 'fecha_evento' => '2026-09-15',
        ]);
        Reservacion::factory()->create([
            'negocio_id' => $this->negocio->id, 'salon_id' => $otroSalon->id, 'fecha_evento' => '2026-09-16',
        ]);

        $response = $this->actingAs($cajero)->getJson(
            route('admin.calendario.eventos', ['start' => '2026-09-01', 'end' => '2026-09-30'])
        );

        $response->assertOk()->assertJsonCount(1);
    }

    public function test_aislamiento_multi_tenant_entre_negocios(): void
    {
        $otroNegocio = Negocio::factory()->create();
        $salonAjeno = Salon::factory()->create(['negocio_id' => $otroNegocio->id]);
        Reservacion::factory()->create([
            'negocio_id' => $otroNegocio->id, 'salon_id' => $salonAjeno->id, 'fecha_evento' => '2026-09-15',
        ]);

        $response = $this->actingAs($this->adminNegocio)->getJson(
            route('admin.calendario.eventos', ['start' => '2026-09-01', 'end' => '2026-09-30'])
        );

        $response->assertOk()->assertJsonCount(0);
    }
}
