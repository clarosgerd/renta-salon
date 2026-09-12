<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Actualizado en Fase 6 (11/09/2026) — '/' ya no es la vista `welcome`
     * de Breeze ni el placeholder de Fase 3 (redirect a /login): ahora es
     * el home real del portal público. Ver PortalControllerTest.php para
     * la cobertura completa de esa pantalla; esto solo confirma que la
     * raíz del subdominio sigue respondiendo (guarda contra otra regresión
     * del mismo tipo: la ruta '/' apuntando a algo roto).
     */
    public function test_root_muestra_el_home_del_portal_publico(): void
    {
        $response = $this->get('/');

        $response->assertOk();
    }
}
