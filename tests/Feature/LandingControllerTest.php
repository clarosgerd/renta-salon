<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Landing del producto (11/09/2026) — dominio PELADO rentsalon-pro.test,
 * fuera del middleware 'negocio' (no requiere ningún Negocio sembrado,
 * a propósito: es la página del producto, no de un tenant).
 */
class LandingControllerTest extends TestCase
{
    use RefreshDatabase;

    private const HOST = ['HTTP_HOST' => 'rentsalon-pro.test'];

    public function test_landing_carga_sin_ningun_negocio_registrado(): void
    {
        $response = $this->get('http://rentsalon-pro.test/', self::HOST);

        $response->assertOk();
        $response->assertSee('RentSalon');
        $response->assertSee('Solicitar una demo');
    }

    public function test_no_choca_con_el_host_de_testing_del_resto_de_la_suite(): void
    {
        // Regresión real (11/09/2026): esta ruta por poco se roba el '/'
        // del negocio de prueba sembrado por TestCase, porque ambos
        // coincidían con el mismo host por default — ver TestCase::setUp().
        $response = $this->get(route('portal.home'));

        $response->assertOk();
        $response->assertDontSee('Solicitar una demo');
    }
}
