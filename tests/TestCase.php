<?php

namespace Tests;

use App\Models\Negocio;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Schema;

abstract class TestCase extends BaseTestCase
{
    /**
     * Multi-tenant real (11/09/2026) — TODA request de prueba pasa por
     * IdentificarNegocio (ver routes/web.php), que resuelve el tenant por
     * host y aborta 404 si no encuentra ninguno. El host que usan los
     * tests HTTP por default es el de APP_URL (.env: rentsalon-pro.test,
     * confirmado empíricamente) — sin un Negocio que matchee ese dominio,
     * LITERALMENTE cualquier test HTTP (incluidos los propios de Breeze:
     * login, registro, perfil, reset de password) 404 antes de llegar a
     * la ruta real. Se siembra acá, una vez, para que ningún test
     * individual tenga que pensar en esto — mismo dominio_personalizado
     * que ya matchea request()->getHost() en el entorno de testing.
     */
    protected function setUp(): void
    {
        parent::setUp();

        // No todos los tests usan RefreshDatabase (ej. el stub genérico
        // ExampleTest, que ni siquiera migra) — sin este guard, CUALQUIER
        // test sin BD explota acá con "no such table: negocios".
        if (Schema::hasTable('negocios')) {
            Negocio::factory()->create([
                'dominio_personalizado' => 'rentsalon-pro.test',
                'estado' => 'activo',
            ]);
        }
    }
}
