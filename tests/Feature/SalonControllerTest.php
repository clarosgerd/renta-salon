<?php

namespace Tests\Feature;

use App\Models\Negocio;
use App\Models\Salon;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SalonControllerTest extends TestCase
{
    use RefreshDatabase;

    private Negocio $negocio;

    private User $adminNegocio;

    protected function setUp(): void
    {
        parent::setUp();

        // El Negocio "de plataforma" ya lo crea TestCase::setUp() para que
        // el resto de tests (login, etc.) resuelvan tenant — este es el
        // que usan LAS RUTAS reales acá (mismo host que Laravel usa por
        // default en testing, ver TestCase).
        $this->negocio = Negocio::first();

        $this->adminNegocio = User::factory()->create([
            'negocio_id' => $this->negocio->id,
            'role' => 'admin_negocio',
        ]);
    }

    public function test_admin_negocio_puede_listar_salones(): void
    {
        Salon::factory()->count(2)->create(['negocio_id' => $this->negocio->id]);

        $response = $this->actingAs($this->adminNegocio)->get(route('admin.salones.index'));

        $response->assertOk();
    }

    public function test_rol_sin_permiso_no_puede_listar_salones(): void
    {
        $cajero = User::factory()->create(['negocio_id' => $this->negocio->id, 'role' => 'cajero']);

        $response = $this->actingAs($cajero)->get(route('admin.salones.index'));

        $response->assertForbidden();
    }

    public function test_crea_un_salon_con_fotos(): void
    {
        Storage::fake('public');

        $response = $this->actingAs($this->adminNegocio)->post(route('admin.salones.store'), [
            'nombre' => 'Salón Nuevo',
            'descripcion' => 'Descripción de prueba',
            'capacidad_min' => 10,
            'capacidad_max' => 100,
            'ubicacion' => 'Zona Sur',
            'imagenes' => [UploadedFile::fake()->image('foto1.jpg'), UploadedFile::fake()->image('foto2.jpg')],
        ]);

        $response->assertRedirect(route('admin.salones.index'));
        $salon = Salon::where('nombre', 'Salón Nuevo')->first();
        $this->assertNotNull($salon);
        $this->assertSame($this->negocio->id, $salon->negocio_id);
        $this->assertTrue($salon->activo);
        $this->assertCount(2, $salon->imagenes);
        $this->assertSame(0, $salon->imagenes[0]->orden);
        $this->assertSame(1, $salon->imagenes[1]->orden);
        Storage::disk('public')->assertExists('salones/' . basename($salon->imagenes[0]->url));
    }

    public function test_capacidad_maxima_no_puede_ser_menor_a_la_minima(): void
    {
        $response = $this->actingAs($this->adminNegocio)->post(route('admin.salones.store'), [
            'nombre' => 'Salón Chico',
            'capacidad_min' => 50,
            'capacidad_max' => 10,
        ]);

        $response->assertSessionHasErrors('capacidad_max');
        $this->assertDatabaseMissing('salones', ['nombre' => 'Salón Chico']);
    }

    public function test_actualiza_un_salon_y_agrega_mas_fotos(): void
    {
        Storage::fake('public');
        $salon = Salon::factory()->create(['negocio_id' => $this->negocio->id, 'nombre' => 'Original']);

        $response = $this->actingAs($this->adminNegocio)->put(route('admin.salones.update', $salon), [
            'nombre' => 'Actualizado',
            'capacidad_min' => 5,
            'capacidad_max' => 50,
            'activo' => '1',
            'imagenes' => [UploadedFile::fake()->image('nueva.jpg')],
        ]);

        $response->assertRedirect(route('admin.salones.index'));
        $salon->refresh();
        $this->assertSame('Actualizado', $salon->nombre);
        $this->assertCount(1, $salon->imagenes);
    }

    public function test_checkbox_activo_sin_marcar_desactiva_el_salon(): void
    {
        $salon = Salon::factory()->create(['negocio_id' => $this->negocio->id, 'activo' => true]);

        // Un checkbox sin marcar simplemente no viaja en el request — ver
        // gotcha documentado en SalonController::update().
        $this->actingAs($this->adminNegocio)->put(route('admin.salones.update', $salon), [
            'nombre' => $salon->nombre,
            'capacidad_min' => $salon->capacidad_min,
            'capacidad_max' => $salon->capacidad_max,
        ]);

        $this->assertFalse($salon->fresh()->activo);
    }

    public function test_toggle_activo_invierte_el_estado(): void
    {
        $salon = Salon::factory()->create(['negocio_id' => $this->negocio->id, 'activo' => true]);

        $this->actingAs($this->adminNegocio)->patch(route('admin.salones.toggle-activo', $salon));

        $this->assertFalse($salon->fresh()->activo);
    }

    public function test_mover_y_eliminar_imagen(): void
    {
        Storage::fake('public');
        $salon = Salon::factory()->create(['negocio_id' => $this->negocio->id]);
        $img1 = $salon->imagenes()->create(['url' => 'http://x/a.jpg', 'orden' => 0]);
        $img2 = $salon->imagenes()->create(['url' => 'http://x/b.jpg', 'orden' => 1]);

        $this->actingAs($this->adminNegocio)
            ->patch(route('admin.salones.imagenes.mover', [$salon, $img2]), ['direccion' => 'arriba']);

        $this->assertSame(0, $img2->fresh()->orden);
        $this->assertSame(1, $img1->fresh()->orden);

        $this->actingAs($this->adminNegocio)->delete(route('admin.salones.imagenes.destroy', [$salon, $img1]));

        $this->assertDatabaseMissing('imagenes', ['id' => $img1->id]);
    }

    /**
     * El punto más crítico de todo el diseño multi-tenant: un Salon de un
     * negocio NUNCA debe ser visible/editable por un admin de OTRO
     * negocio — NegocioScope tiene que bloquear esto solo, sin que el
     * controller haga ningún chequeo manual de negocio_id.
     */
    public function test_aislamiento_multi_tenant_entre_negocios(): void
    {
        $otroNegocio = Negocio::factory()->create();
        $salonDeOtroNegocio = Salon::factory()->create(['negocio_id' => $otroNegocio->id, 'nombre' => 'Ajeno']);

        // La request HTTP real (host de testing = rentsalon-pro.test) pasa
        // por IdentificarNegocio igual que en producción, y resuelve
        // $this->negocio (el único Negocio con ese dominio) — nunca
        // $otroNegocio. NegocioScope debe filtrar solo, sin chequeo manual
        // en el controller.
        $response = $this->actingAs($this->adminNegocio)->get(route('admin.salones.index'));
        $response->assertOk();
        $response->assertDontSee('Ajeno');

        // Ni siquiera vía URL directa (route model binding no filtra por
        // negocio por sí solo, es el Global Scope el que lo hace).
        $edit = $this->actingAs($this->adminNegocio)->get(route('admin.salones.edit', $salonDeOtroNegocio));
        $edit->assertNotFound();
    }
}
