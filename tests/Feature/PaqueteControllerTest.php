<?php

namespace Tests\Feature;

use App\Models\Negocio;
use App\Models\Paquete;
use App\Models\Salon;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaqueteControllerTest extends TestCase
{
    use RefreshDatabase;

    private Negocio $negocio;

    private User $adminNegocio;

    protected function setUp(): void
    {
        parent::setUp();

        $this->negocio = Negocio::first();
        $this->adminNegocio = User::factory()->create([
            'negocio_id' => $this->negocio->id,
            'role' => 'admin_negocio',
        ]);
    }

    public function test_admin_negocio_puede_listar_paquetes(): void
    {
        Paquete::factory()->count(2)->create(['negocio_id' => $this->negocio->id]);

        $response = $this->actingAs($this->adminNegocio)->get(route('admin.paquetes.index'));

        $response->assertOk();
    }

    public function test_rol_sin_permiso_no_puede_crear_paquetes(): void
    {
        $cajero = User::factory()->create(['negocio_id' => $this->negocio->id, 'role' => 'cajero']);

        $response = $this->actingAs($cajero)->get(route('admin.paquetes.create'));

        $response->assertForbidden();
    }

    public function test_crea_un_paquete_sin_salones_aplica_a_todos(): void
    {
        $response = $this->actingAs($this->adminNegocio)->post(route('admin.paquetes.store'), [
            'nombre' => 'Boda Premium',
            'tipo_evento' => 'boda',
            'precio_base' => 12000,
            'duracion_horas' => 8,
            'servicios_incluidos' => ['Mantelería', 'DJ'],
        ]);

        $response->assertRedirect(route('admin.paquetes.index'));
        $paquete = Paquete::where('nombre', 'Boda Premium')->first();
        $this->assertNotNull($paquete);
        $this->assertSame($this->negocio->id, $paquete->negocio_id);
        $this->assertSame(['Mantelería', 'DJ'], $paquete->servicios_incluidos);
        $this->assertTrue($paquete->aplicaATodosLosSalones());
    }

    public function test_crea_un_paquete_limitado_a_salones_especificos(): void
    {
        $salon = Salon::factory()->create(['negocio_id' => $this->negocio->id]);

        $response = $this->actingAs($this->adminNegocio)->post(route('admin.paquetes.store'), [
            'nombre' => 'Corporativo Chico',
            'tipo_evento' => 'corporativo',
            'precio_base' => 3000,
            'duracion_horas' => 4,
            'salones' => [$salon->id],
        ]);

        $response->assertRedirect(route('admin.paquetes.index'));
        $paquete = Paquete::where('nombre', 'Corporativo Chico')->first();
        $this->assertFalse($paquete->aplicaATodosLosSalones());
        $this->assertTrue($paquete->salones->contains($salon));
    }

    public function test_rechaza_un_salon_de_otro_negocio(): void
    {
        // Gotcha real corregido en Store/UpdatePaqueteRequest: exists:
        // corre contra la BD cruda, sin pasar por NegocioScope — sin el
        // Rule::exists()->where('negocio_id', ...) explícito, esto pasaría.
        $otroNegocio = Negocio::factory()->create();
        $salonAjeno = Salon::factory()->create(['negocio_id' => $otroNegocio->id]);

        $response = $this->actingAs($this->adminNegocio)->post(route('admin.paquetes.store'), [
            'nombre' => 'Intento Cruzado',
            'tipo_evento' => 'otro',
            'precio_base' => 1000,
            'duracion_horas' => 2,
            'salones' => [$salonAjeno->id],
        ]);

        $response->assertSessionHasErrors('salones.0');
        $this->assertDatabaseMissing('paquetes', ['nombre' => 'Intento Cruzado']);
    }

    public function test_actualiza_un_paquete_y_cambia_sus_salones(): void
    {
        $paquete = Paquete::factory()->create(['negocio_id' => $this->negocio->id]);
        $salonViejo = Salon::factory()->create(['negocio_id' => $this->negocio->id]);
        $salonNuevo = Salon::factory()->create(['negocio_id' => $this->negocio->id]);
        $paquete->salones()->sync([$salonViejo->id]);

        $this->actingAs($this->adminNegocio)->put(route('admin.paquetes.update', $paquete), [
            'nombre' => 'Renombrado',
            'tipo_evento' => $paquete->tipo_evento,
            'precio_base' => $paquete->precio_base,
            'duracion_horas' => $paquete->duracion_horas,
            'activo' => '1',
            'salones' => [$salonNuevo->id],
        ]);

        $paquete->refresh();
        $this->assertSame('Renombrado', $paquete->nombre);
        $this->assertTrue($paquete->salones->contains($salonNuevo));
        $this->assertFalse($paquete->salones->contains($salonViejo));
    }

    public function test_toggle_activo_invierte_el_estado(): void
    {
        $paquete = Paquete::factory()->create(['negocio_id' => $this->negocio->id, 'activo' => true]);

        $this->actingAs($this->adminNegocio)->patch(route('admin.paquetes.toggle-activo', $paquete));

        $this->assertFalse($paquete->fresh()->activo);
    }

    public function test_aislamiento_multi_tenant_entre_negocios(): void
    {
        $otroNegocio = Negocio::factory()->create();
        Paquete::factory()->create(['negocio_id' => $otroNegocio->id, 'nombre' => 'Ajeno']);

        $response = $this->actingAs($this->adminNegocio)->get(route('admin.paquetes.index'));

        $response->assertOk();
        $response->assertDontSee('Ajeno');
    }
}
