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
     * tests HTTP por default es el de APP_URL — se siembra acá, una vez,
     * para que ningún test individual tenga que pensar en esto — mismo
     * dominio_personalizado que ya matchea request()->getHost().
     *
     * host dedicado 'negocio-test.rentsalon-pro.test' (antes literalmente
     * 'rentsalon-pro.test', porque coincidía "gratis" con el APP_URL de
     * .env) — dejó de servir el día que la Fase Landing reclamó el host
     * PELADO rentsalon-pro.test para la página del producto
     * (Route::domain('rentsalon-pro.test'), ver routes/web.php): con el
     * viejo default, CUALQUIER test que pegara a '/' sin overridear el
     * host terminaba en el landing en vez del portal del tenant de
     * prueba — mismo tipo de colisión de dominios ya documentado para
     * admin.rentsalon-pro.test en Fase 1, ahora contra el entorno de
     * testing en vez de producción. APP_URL se overridea en phpunit.xml
     * para que coincida.
     */
    protected function setUp(): void
    {
        parent::setUp();

        // No todos los tests usan RefreshDatabase (ej. el stub genérico
        // ExampleTest, que ni siquiera migra) — sin este guard, CUALQUIER
        // test sin BD explota acá con "no such table: negocios".
        if (Schema::hasTable('negocios')) {
            Negocio::factory()->create([
                'dominio_personalizado' => 'negocio-test.rentsalon-pro.test',
                'estado' => 'activo',
            ]);
        }
    }
}
