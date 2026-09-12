<?php

namespace Tests\Feature\Plataforma;

use App\Models\ImpersonacionLog;
use App\Models\Negocio;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * SA-5 (11/09/2026) — lado PLATAFORMA (iniciar impersonación + historial).
 * Mismo patrón de host que NegocioControllerTest/NegocioConfigPagoControllerTest.
 */
class ImpersonacionControllerTest extends TestCase
{
    use RefreshDatabase;

    private const HOST = ['HTTP_HOST' => 'admin.rentsalon-pro.test'];

    private User $superAdmin;
    private Negocio $negocio;

    protected function setUp(): void
    {
        parent::setUp();

        $this->superAdmin = User::factory()->create([
            'negocio_id' => null,
            'role' => 'super_admin_plataforma',
        ]);

        $this->negocio = Negocio::factory()->create(['subdominio' => 'demo-impersonar']);
    }

    public function test_admin_negocio_no_puede_iniciar_impersonacion(): void
    {
        $adminNegocio = User::factory()->create(['negocio_id' => $this->negocio->id, 'role' => 'admin_negocio']);

        $response = $this->actingAs($adminNegocio)
            ->post(route('plataforma.negocios.impersonar', $this->negocio), [], self::HOST);

        $response->assertForbidden();
    }

    public function test_negocio_sin_admin_negocio_bloquea_sin_crear_log(): void
    {
        $response = $this->actingAs($this->superAdmin)
            ->post(route('plataforma.negocios.impersonar', $this->negocio), [], self::HOST);

        $response->assertSessionHas('error');
        $this->assertDatabaseCount('impersonacion_logs', 0);
    }

    public function test_negocio_con_admin_negocio_crea_log_y_redirige_al_dominio_del_negocio(): void
    {
        $adminNegocio = User::factory()->create(['negocio_id' => $this->negocio->id, 'role' => 'admin_negocio']);

        $response = $this->actingAs($this->superAdmin)
            ->post(route('plataforma.negocios.impersonar', $this->negocio), [], self::HOST);

        $response->assertStatus(302);
        $this->assertStringStartsWith('http://demo-impersonar.rentsalon-pro.test', $response->headers->get('Location'));

        $this->assertDatabaseCount('impersonacion_logs', 1);
        $log = ImpersonacionLog::first();
        $this->assertSame($this->superAdmin->id, $log->super_admin_user_id);
        $this->assertSame($this->negocio->id, $log->negocio_id);
        $this->assertSame($adminNegocio->id, $log->usuario_impersonado_id);
        $this->assertNotNull($log->iniciada_en);
        $this->assertNull($log->finalizada_en);
    }

    public function test_historial_muestra_los_logs(): void
    {
        $adminNegocio = User::factory()->create(['negocio_id' => $this->negocio->id, 'role' => 'admin_negocio']);
        ImpersonacionLog::create([
            'super_admin_user_id' => $this->superAdmin->id,
            'negocio_id' => $this->negocio->id,
            'usuario_impersonado_id' => $adminNegocio->id,
            'iniciada_en' => now(),
        ]);

        $response = $this->actingAs($this->superAdmin)
            ->get(route('plataforma.impersonaciones.index'), self::HOST);

        $response->assertOk();
        $response->assertSee($this->negocio->nombre_comercial);
        $response->assertSee($adminNegocio->name);
        $response->assertSee('Activa');
    }
}
