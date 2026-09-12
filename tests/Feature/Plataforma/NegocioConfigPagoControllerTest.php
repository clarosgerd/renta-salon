<?php

namespace Tests\Feature\Plataforma;

use App\Models\Negocio;
use App\Models\NegocioConfigPago;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * SA-4 (11/09/2026) — ver brain/Historias_Usuario_Pantallas_RentSalon_Pro.md.
 * Mismo patrón de host que NegocioControllerTest.
 */
class NegocioConfigPagoControllerTest extends TestCase
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

        $this->negocio = Negocio::factory()->create();
    }

    public function test_guest_es_redirigido_a_login(): void
    {
        $response = $this->get(route('plataforma.negocios.config-pago.edit', $this->negocio), self::HOST);

        $response->assertRedirect(route('plataforma.login'));
    }

    public function test_admin_negocio_no_puede_acceder(): void
    {
        $adminNegocio = User::factory()->create(['negocio_id' => $this->negocio->id, 'role' => 'admin_negocio']);

        $response = $this->actingAs($adminNegocio)
            ->get(route('plataforma.negocios.config-pago.edit', $this->negocio), self::HOST);

        $response->assertForbidden();
    }

    public function test_get_edit_sin_config_previa_muestra_formulario_vacio(): void
    {
        $response = $this->actingAs($this->superAdmin)
            ->get(route('plataforma.negocios.config-pago.edit', $this->negocio), self::HOST);

        $response->assertOk();
        $response->assertDontSee('Reemplazar');
    }

    public function test_put_crea_config_la_primera_vez(): void
    {
        $this->assertNull($this->negocio->configPago);

        $response = $this->actingAs($this->superAdmin)->put(
            route('plataforma.negocios.config-pago.update', $this->negocio),
            [
                'banco' => 'union',
                'cuenta_destino' => '123-456',
                'reemplazar_credenciales' => '1',
                'numero_comercio' => 'COM-001',
                'api_key' => 'clave-secreta',
            ],
            self::HOST
        );

        $response->assertRedirect(route('plataforma.negocios.config-pago.edit', $this->negocio));

        $config = $this->negocio->fresh()->configPago;
        $this->assertNotNull($config);
        $this->assertSame('union', $config->banco);
        $this->assertSame('123-456', $config->cuenta_destino);
        $this->assertSame('COM-001', $config->credenciales_json_cifrado['numero_comercio']);
        $this->assertFalse($config->activo);
    }

    public function test_put_sin_reemplazar_no_toca_las_credenciales_existentes(): void
    {
        $config = NegocioConfigPago::create([
            'negocio_id' => $this->negocio->id,
            'banco' => 'union',
            'credenciales_json_cifrado' => ['numero_comercio' => 'ORIGINAL', 'api_key' => 'clave-original'],
        ]);

        $this->actingAs($this->superAdmin)->put(
            route('plataforma.negocios.config-pago.update', $this->negocio),
            [
                'banco' => 'bnb',
                'cuenta_destino' => '999-999',
                // reemplazar_credenciales NO enviado
            ],
            self::HOST
        );

        $fresh = $config->fresh();
        $this->assertSame('bnb', $fresh->banco);
        $this->assertSame('999-999', $fresh->cuenta_destino);
        $this->assertSame('ORIGINAL', $fresh->credenciales_json_cifrado['numero_comercio']);
    }

    public function test_put_con_reemplazar_si_cambia_las_credenciales(): void
    {
        $config = NegocioConfigPago::create([
            'negocio_id' => $this->negocio->id,
            'banco' => 'union',
            'credenciales_json_cifrado' => ['numero_comercio' => 'VIEJO', 'api_key' => 'clave-vieja'],
        ]);

        $this->actingAs($this->superAdmin)->put(
            route('plataforma.negocios.config-pago.update', $this->negocio),
            [
                'banco' => 'union',
                'reemplazar_credenciales' => '1',
                'numero_comercio' => 'NUEVO',
                'api_key' => 'clave-nueva',
            ],
            self::HOST
        );

        $this->assertSame('NUEVO', $config->fresh()->credenciales_json_cifrado['numero_comercio']);
    }

    public function test_put_activo_sin_banco_ni_credenciales_falla_validacion(): void
    {
        $response = $this->actingAs($this->superAdmin)->put(
            route('plataforma.negocios.config-pago.update', $this->negocio),
            ['activo' => '1'],
            self::HOST
        );

        $response->assertSessionHasErrors('activo');
        $this->assertNull($this->negocio->fresh()->configPago);
    }

    public function test_put_activo_con_banco_y_credenciales_ya_guardados_pasa(): void
    {
        NegocioConfigPago::create([
            'negocio_id' => $this->negocio->id,
            'banco' => 'union',
            'credenciales_json_cifrado' => ['numero_comercio' => 'COM-001', 'api_key' => 'clave'],
        ]);

        $response = $this->actingAs($this->superAdmin)->put(
            route('plataforma.negocios.config-pago.update', $this->negocio),
            ['banco' => 'union', 'activo' => '1'],
            self::HOST
        );

        $response->assertSessionHasNoErrors();
        $this->assertTrue($this->negocio->fresh()->configPago->activo);
    }

    public function test_probar_conexion_sin_config_redirige_con_error(): void
    {
        $response = $this->actingAs($this->superAdmin)->post(
            route('plataforma.negocios.config-pago.probar-conexion', $this->negocio),
            [],
            self::HOST
        );

        $response->assertSessionHas('error');
        $this->assertNull($this->negocio->fresh()->configPago);
    }

    public function test_probar_conexion_con_config_actualiza_ultima_prueba(): void
    {
        $config = NegocioConfigPago::create([
            'negocio_id' => $this->negocio->id,
            'banco' => 'union',
            'credenciales_json_cifrado' => ['numero_comercio' => 'COM-001', 'api_key' => 'clave'],
        ]);
        $this->assertNull($config->ultima_prueba_conexion);

        $response = $this->actingAs($this->superAdmin)->post(
            route('plataforma.negocios.config-pago.probar-conexion', $this->negocio),
            [],
            self::HOST
        );

        $response->assertSessionHas('success');
        $this->assertNotNull($config->fresh()->ultima_prueba_conexion);
    }

    public function test_credenciales_cifradas_nunca_aparecen_en_texto_plano_en_la_vista(): void
    {
        NegocioConfigPago::create([
            'negocio_id' => $this->negocio->id,
            'banco' => 'union',
            'credenciales_json_cifrado' => ['numero_comercio' => 'COMERCIO-SECRETO-123', 'api_key' => 'llave-super-secreta'],
        ]);

        $response = $this->actingAs($this->superAdmin)
            ->get(route('plataforma.negocios.config-pago.edit', $this->negocio), self::HOST);

        $response->assertOk();
        $response->assertDontSee('COMERCIO-SECRETO-123');
        $response->assertDontSee('llave-super-secreta');
    }
}
