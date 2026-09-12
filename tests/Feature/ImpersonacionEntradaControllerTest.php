<?php

namespace Tests\Feature;

use App\Models\ImpersonacionLog;
use App\Models\Negocio;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

/**
 * SA-5 (11/09/2026) — lado TENANT del flujo de impersonación (recibir el
 * link firmado, entrar, salir). El "log de otro negocio" NO se prueba acá
 * como escenario HTTP: el host queda codificado dentro de la firma misma
 * (URL::temporarySignedRoute firma la URL absoluta completa, host
 * incluido) — no existe forma de producir una firma válida que combine el
 * host de un negocio con el log de OTRO, así que ese chequeo extra en el
 * controller es defensa en profundidad, no un camino alcanzable por HTTP.
 */
class ImpersonacionEntradaControllerTest extends TestCase
{
    use RefreshDatabase;

    private Negocio $negocio;
    private User $adminNegocio;
    private string $host;

    protected function setUp(): void
    {
        parent::setUp();

        $this->negocio = Negocio::factory()->create(['subdominio' => 'tenant-sa5', 'estado' => 'activo']);
        $this->adminNegocio = User::factory()->create(['negocio_id' => $this->negocio->id, 'role' => 'admin_negocio']);
        $this->host = 'tenant-sa5.rentsalon-pro.test';
    }

    private function crearLogYUrlFirmada(): array
    {
        $log = ImpersonacionLog::create([
            'negocio_id' => $this->negocio->id,
            'usuario_impersonado_id' => $this->adminNegocio->id,
            'iniciada_en' => now(),
        ]);

        URL::forceRootUrl("http://{$this->host}");
        $signedUrl = URL::temporarySignedRoute('impersonar.entrar', now()->addSeconds(60), ['log' => $log->id]);
        URL::forceRootUrl(config('app.url'));

        return [$log, $signedUrl];
    }

    public function test_url_sin_firma_es_rechazada(): void
    {
        $response = $this->get("http://{$this->host}/impersonar/entrar/1", ['HTTP_HOST' => $this->host]);

        $response->assertForbidden();
    }

    public function test_firma_valida_autentica_como_el_usuario_impersonado(): void
    {
        [$log, $signedUrl] = $this->crearLogYUrlFirmada();

        $response = $this->get($signedUrl, ['HTTP_HOST' => $this->host]);

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($this->adminNegocio);
        $this->assertEquals($log->id, session('impersonacion_log_id'));
    }

    public function test_log_ya_finalizado_da_404(): void
    {
        [$log, $signedUrl] = $this->crearLogYUrlFirmada();
        $log->update(['finalizada_en' => now()]);

        $response = $this->get($signedUrl, ['HTTP_HOST' => $this->host]);

        $response->assertNotFound();
    }

    public function test_negocio_suspendido_bloquea_incluso_con_firma_valida(): void
    {
        [, $signedUrl] = $this->crearLogYUrlFirmada();
        $this->negocio->update(['estado' => 'suspendido']);

        $response = $this->get($signedUrl, ['HTTP_HOST' => $this->host]);

        $response->assertForbidden();
    }

    public function test_salir_finaliza_el_log_cierra_sesion_y_redirige_a_login(): void
    {
        [$log, $signedUrl] = $this->crearLogYUrlFirmada();
        $this->get($signedUrl, ['HTTP_HOST' => $this->host]);
        $this->assertAuthenticatedAs($this->adminNegocio);

        $response = $this->post("http://{$this->host}/impersonar/salir", [], ['HTTP_HOST' => $this->host]);

        $response->assertRedirect(route('login'));
        $this->assertGuest();
        $this->assertNotNull($log->fresh()->finalizada_en);
    }
}
