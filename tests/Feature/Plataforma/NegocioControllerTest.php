<?php

namespace Tests\Feature\Plataforma;

use App\Models\Negocio;
use App\Models\Reservacion;
use App\Models\Salon;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Panel Super Admin Plataforma (11/09/2026) — Fase 1, ver SA-1/SA-2/SA-3
 * en brain/Historias_Usuario_Pantallas_RentSalon_Pro.md. Vive bajo
 * Route::domain('admin.rentsalon-pro.test'), fuera del middleware
 * 'negocio' — hay que pasar ese Host explícito en cada request de estos
 * tests (TestCase::setUp() solo siembra un Negocio para el host TENANT
 * default, no para el dominio de plataforma).
 */
class NegocioControllerTest extends TestCase
{
    use RefreshDatabase;

    private const HOST = ['HTTP_HOST' => 'admin.rentsalon-pro.test'];

    private User $superAdmin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->superAdmin = User::factory()->create([
            'negocio_id' => null,
            'role' => 'super_admin_plataforma',
        ]);
    }

    public function test_super_admin_puede_listar_negocios(): void
    {
        Negocio::factory()->count(2)->create();

        $response = $this->actingAs($this->superAdmin)
            ->get(route('plataforma.negocios.index'), self::HOST);

        $response->assertOk();
    }

    public function test_admin_negocio_no_puede_acceder_a_plataforma(): void
    {
        $negocio = Negocio::factory()->create();
        $adminNegocio = User::factory()->create(['negocio_id' => $negocio->id, 'role' => 'admin_negocio']);

        $response = $this->actingAs($adminNegocio)
            ->get(route('plataforma.negocios.index'), self::HOST);

        $response->assertForbidden();
    }

    public function test_login_de_super_admin_redirige_a_plataforma_negocios(): void
    {
        $response = $this->post('http://admin.rentsalon-pro.test/login', [
            'email' => $this->superAdmin->email,
            'password' => 'password',
        ], self::HOST);

        $response->assertRedirect(route('plataforma.negocios.index'));
    }

    public function test_crea_un_negocio_que_nace_activo(): void
    {
        $response = $this->actingAs($this->superAdmin)->post(route('plataforma.negocios.store'), [
            'nombre_comercial' => 'Salones Elegantes',
            'subdominio' => 'saloneselegantes',
            'color_primario' => '#1D9E75',
        ], self::HOST);

        $response->assertRedirect(route('plataforma.negocios.index'));
        $negocio = Negocio::where('subdominio', 'saloneselegantes')->first();
        $this->assertNotNull($negocio);
        $this->assertSame('activo', $negocio->estado);
    }

    public function test_rechaza_subdominio_duplicado(): void
    {
        Negocio::factory()->create(['subdominio' => 'saloneslapaz']);

        $response = $this->actingAs($this->superAdmin)->post(route('plataforma.negocios.store'), [
            'nombre_comercial' => 'Otro Negocio',
            'subdominio' => 'saloneslapaz',
        ], self::HOST);

        $response->assertSessionHasErrors('subdominio');
    }

    public function test_rechaza_subdominio_con_formato_invalido(): void
    {
        $response = $this->actingAs($this->superAdmin)->post(route('plataforma.negocios.store'), [
            'nombre_comercial' => 'Negocio Raro',
            'subdominio' => 'Salones La Paz', // mayúsculas y espacios
        ], self::HOST);

        $response->assertSessionHasErrors('subdominio');
    }

    public function test_lista_negocios_con_metricas_correctas(): void
    {
        $negocio = Negocio::factory()->create();
        Salon::factory()->count(3)->create(['negocio_id' => $negocio->id]);

        Reservacion::factory()->create([
            'negocio_id' => $negocio->id,
            'salon_id' => Salon::factory()->create(['negocio_id' => $negocio->id])->id,
            'created_at' => now(),
        ]);
        // Reservación de un mes anterior — no debe contarse en "del mes".
        Reservacion::factory()->create([
            'negocio_id' => $negocio->id,
            'salon_id' => Salon::factory()->create(['negocio_id' => $negocio->id])->id,
            'created_at' => now()->subMonthsNoOverflow(2),
        ]);

        $response = $this->actingAs($this->superAdmin)
            ->get(route('plataforma.negocios.index'), self::HOST);

        $response->assertOk();
        $negociosEnVista = $response->viewData('negocios');
        $fila = $negociosEnVista->firstWhere('id', $negocio->id);
        $this->assertSame(5, $fila->salones_count); // 3 + 2 de las reservaciones
        $this->assertSame(1, $fila->reservaciones_mes_count);
    }

    public function test_toggle_estado_suspende_y_reactiva(): void
    {
        $negocio = Negocio::factory()->create(['estado' => 'activo']);

        $this->actingAs($this->superAdmin)
            ->patch(route('plataforma.negocios.toggle-estado', $negocio), [], self::HOST);
        $this->assertSame('suspendido', $negocio->fresh()->estado);

        $this->actingAs($this->superAdmin)
            ->patch(route('plataforma.negocios.toggle-estado', $negocio), [], self::HOST);
        $this->assertSame('activo', $negocio->fresh()->estado);
    }

    public function test_negocio_suspendido_bloquea_su_subdominio(): void
    {
        $negocio = Negocio::factory()->create(['subdominio' => 'suspendido-test', 'estado' => 'suspendido']);

        $response = $this->get('http://suspendido-test.rentsalon-pro.test/login', [
            'HTTP_HOST' => 'suspendido-test.rentsalon-pro.test',
        ]);

        $response->assertForbidden();
    }
}
